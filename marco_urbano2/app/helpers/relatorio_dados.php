<?php
/**
 * =====================================================================
 *  Dados do Relatório Oficial de Medição
 * =====================================================================
 *
 *  Tudo o que o relatório precisa saber sai daqui: planejamentos do
 *  usuário, trechos selecionados, medições, fotos, resumo consolidado,
 *  município, contrato e período.
 *
 *  Este arquivo é incluído por DOIS consumidores:
 *
 *    - planejador/relatorio.php      — a pré-visualização em tela
 *    - planejador/relatorio-pdf.php  — o PDF gerado no servidor
 *
 *  A separação existe justamente para que os dois não possam divergir.
 *  Um laudo em que a tela mostra uma coisa e o PDF mostra outra não
 *  serve para nada — então a apuração é uma só, e cada consumidor
 *  apenas desenha o que recebe.
 *
 *  Espera encontrar no escopo do chamador: $pdo e a sessão iniciada.
 *  Define, entre outras: $planejamentos, $planejamentoSelecionado,
 *  $trechosDisponiveis, $dados, $resumo, $totalPorTipo, $municipioDoc,
 *  $contratoDoc, $municipioAssinatura, $periodoTexto, $emissao.
 * =====================================================================
 */

/* =========================================
   PLANEJAMENTOS DO USUÁRIO LOGADO
========================================= */
$stmt = $pdo->prepare("
    SELECT id, nome
    FROM planejamentos
    WHERE usuario_id = ?
    ORDER BY id DESC
");
$stmt->execute([$_SESSION['usuario_id']]);
$planejamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================
   PLANEJAMENTO SELECIONADO
========================================= */
$planejamento = null;
$planejamentoSelecionado = null;

if (!empty($_GET['planejamento_id'])) {
    $planejamentoSelecionado = (int)$_GET['planejamento_id'];

    $stmt = $pdo->prepare("
        SELECT nome
        FROM planejamentos
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([
        $planejamentoSelecionado,
        $_SESSION['usuario_id']
    ]);
    $planejamento = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =========================================
   TRECHOS DISPONÍVEIS (APENAS MEDIDOS)
========================================= */
$trechosDisponiveis = [];

if ($planejamentoSelecionado) {
    $stmt = $pdo->prepare("
        SELECT id, pv_montante, pv_jusante, medicao
        FROM trechos
        WHERE planejamento_id = ?
          AND area_total > 0
        ORDER BY medicao, id
    ");
    $stmt->execute([$planejamentoSelecionado]);
    $trechosDisponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================================
   TRECHOS SELECIONADOS
   Restrito ao planejamento do próprio usuário: sem isso, um id na mão
   traria trecho de outro planejamento para dentro do relatório.
========================================= */
$trechosSelecionados = [];

if (!empty($_POST['trechos']) && $planejamentoSelecionado) {
    $ids = array_values(array_filter(array_map('intval', (array) $_POST['trechos'])));
    if ($ids) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("
            SELECT t.*
            FROM trechos t
            JOIN planejamentos p ON p.id = t.planejamento_id
            WHERE t.id IN ($ph)
              AND t.planejamento_id = ?
              AND p.usuario_id = ?
            ORDER BY t.medicao, t.id
        ");
        $stmt->execute(array_merge($ids, [$planejamentoSelecionado, $_SESSION['usuario_id']]));
        $trechosSelecionados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

/* =========================================
   CARREGA MEDIÇÕES, CROQUI E FOTOS + CONSOLIDA TOTAIS

   Os totais saem separados por tipo de pavimento: são itens distintos
   da planilha contratual, com preços unitários próprios. Somar CBUQ
   com paralelepípedo não teria significado contratual.
========================================= */
$dados       = [];
$resumo      = [];   // [medicao][tipo_pavimento] => ['trechos'=>n, 'area'=>m2]
$totalPorTipo = [];  // [tipo_pavimento] => ['trechos'=>n, 'area'=>m2]
$medicoesSet = [];
$bacias      = [];
$cidades     = [];
$contratos   = [];

foreach ($trechosSelecionados as $t) {
    $st = $pdo->prepare("SELECT * FROM pavimento_produzido WHERE trecho_id = ? ORDER BY id");
    $st->execute([$t['id']]);
    $segmentos = $st->fetchAll(PDO::FETCH_ASSOC);

    $st = $pdo->prepare("SELECT * FROM trecho_fotos WHERE trecho_id = ? ORDER BY id");
    $st->execute([$t['id']]);
    $fotos = $st->fetchAll(PDO::FETCH_ASSOC);

    $areaTrecho = array_sum(array_column($segmentos, 'area'));

    $dados[] = ['t' => $t, 'segmentos' => $segmentos, 'fotos' => $fotos, 'area' => $areaTrecho];

    $med  = trim((string) $t['medicao']) !== '' ? $t['medicao'] : '—';
    $tipo = (string) $t['tipo_pavimento'];

    if (!isset($resumo[$med][$tipo])) {
        $resumo[$med][$tipo] = ['trechos' => 0, 'area' => 0.0];
    }
    $resumo[$med][$tipo]['trechos']++;
    $resumo[$med][$tipo]['area'] += $areaTrecho;

    if (!isset($totalPorTipo[$tipo])) {
        $totalPorTipo[$tipo] = ['trechos' => 0, 'area' => 0.0];
    }
    $totalPorTipo[$tipo]['trechos']++;
    $totalPorTipo[$tipo]['area'] += $areaTrecho;

    $medicoesSet[$med] = true;
    if (trim((string) $t['bacia']) !== '')    { $bacias[$t['bacia']] = true; }
    if (trim((string) $t['cidade']) !== '')   { $cidades[$t['cidade']] = true; }
    if (trim((string) $t['contrato']) !== '') { $contratos[$t['contrato']] = true; }
}

ksort($resumo);
$listaMedicoes = implode(' e ', array_keys($medicoesSet));
$listaBacias   = implode(', ', array_keys($bacias));
$emissao       = mu_rel_pendente('emissao') ? date('d/m/Y') : mu_rel('emissao');

/* =========================================
   MUNICÍPIO E CONTRATO — VÊM DOS TRECHOS

   Estavam fixos no config e saíam iguais em todo relatório, mesmo em
   planejamento de outra cidade. Passam a ser lidos dos trechos que
   entram no documento. Se todos são da mesma cidade, é ela; se houver
   mais de uma, saem todas, porque omitir esconderia informação do
   documento oficial.

   O config continua valendo como sobreposição, para o caso de o
   contrato exigir uma denominação diferente da digitada nos trechos.
========================================= */
$municipioDoc = trim((string) (MU_RELATORIO['municipio'] ?? ''));
if ($cidades) {
    $municipioDoc = implode(' · ', array_keys($cidades));
}
if ($municipioDoc === '') { $municipioDoc = '—'; }

$contratoDoc = trim((string) (MU_RELATORIO['contrato'] ?? ''));
if ($contratos) {
    $contratoDoc = implode(' · ', array_keys($contratos));
}
if ($contratoDoc === '') { $contratoDoc = '—'; }

/* Para o rodapé e a linha de local e data, uma cidade só. Com várias,
   fica a primeira — assinar em duas cidades ao mesmo tempo não existe. */
$municipioAssinatura = $cidades ? array_key_first($cidades) : $municipioDoc;

/* =========================================
   PERÍODO DA MEDIÇÃO — APURADO DOS PRÓPRIOS DADOS

   Não é digitado: sai do que está registrado nos trechos do relatório.

   A fonte preferida é a data em que a FOTO FOI TIRADA (EXIF), porque é
   a evidência de quando o serviço aconteceu em campo — e é o que um
   perito confere. Onde não houver EXIF (acervo anterior a esta regra),
   cai para a data em que a medição foi lançada no sistema.

   O campo `periodo` do config, se preenchido, vence: o contrato pode
   fixar um período diferente do que as fotos mostram.
========================================= */
$periodoAuto   = null;
$periodoOrigem = null;

if ($trechosSelecionados) {
    $ids = array_column($trechosSelecionados, 'id');
    $ph  = implode(',', array_fill(0, count($ids), '?'));

    // 1ª escolha: quando a foto foi tirada.
    try {
        $st = $pdo->prepare("
            SELECT MIN(exif_datahora) AS ini, MAX(exif_datahora) AS fim
            FROM midia_custodia
            WHERE trecho_id IN ($ph)
              AND tipo = 'foto'
              AND exif_datahora IS NOT NULL
        ");
        $st->execute($ids);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if (!empty($r['ini'])) {
            $periodoAuto   = [$r['ini'], $r['fim']];
            $periodoOrigem = 'data de captura das fotos';
        }
    } catch (Throwable $e) {
        // Tabela de custódia ainda não criada: segue para o fallback.
    }

    // 2ª escolha: quando a medição foi lançada.
    if ($periodoAuto === null) {
        $st = $pdo->prepare("
            SELECT MIN(created_at) AS ini, MAX(created_at) AS fim
            FROM pavimento_produzido
            WHERE trecho_id IN ($ph)
        ");
        $st->execute($ids);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if (!empty($r['ini'])) {
            $periodoAuto   = [$r['ini'], $r['fim']];
            $periodoOrigem = 'lançamento da medição';
        }
    }
}

if (!mu_rel_pendente('periodo')) {
    $periodoTexto  = mu_rel('periodo');          // config vence
    $periodoOrigem = 'definido no contrato';
} elseif ($periodoAuto !== null) {
    $ini = date('d/m/Y', strtotime($periodoAuto[0]));
    $fim = date('d/m/Y', strtotime($periodoAuto[1]));
    $periodoTexto = ($ini === $fim) ? $ini : "$ini a $fim";
} else {
    $periodoTexto  = 'a confirmar';
    $periodoOrigem = null;
}
$periodoPendente = ($periodoTexto === 'a confirmar');

/** Formata número no padrão brasileiro. */
function mu_num($v, int $casas = 2): string {
    return number_format((float) $v, $casas, ',', '.');
}
