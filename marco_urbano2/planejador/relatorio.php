<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/asset.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/config/relatorio.php';
require_once __DIR__ . '/../app/helpers/tipos_pavimento.php';
require_once __DIR__ . '/../app/helpers/midia_url.php';

auth_required([3]);

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
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Relatório Oficial de Medição | Marco Urbano</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="<?= mu_asset('/assets/css/planejador.css') ?>">
<link rel="stylesheet" href="<?= mu_asset('/assets/css/relatorio-marco-urbano.css') ?>">
</head>

<body>
<div class="app">

<aside class="sidebar no-print">
    <div class="logo">
        <img src="/marco_urbano2/assets/img/logo-marco-urbano.svg" alt="Marco Urbano">
        <span>MARCO URBANO</span>
    </div>
    <nav>
        <a href="/marco_urbano2/planejador/menu.php">📊 Dashboard</a>
        <a href="/marco_urbano2/planejador/planejamento.php">🗂 Novo Planejamento</a>
        <a href="/marco_urbano2/planejador/medicao.php">📐 Medição</a>
        <a href="/marco_urbano2/planejador/relatorio.php" class="active">📄 Relatório</a>
        <a href="/marco_urbano2/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content relatorio">

<div class="topbar no-print">
    <div>
        <h1>Relatório Oficial de Medição</h1>
        <?php if ($planejamento): ?>
            <span><?= htmlspecialchars($planejamento['nome']) ?></span>
        <?php endif; ?>
    </div>
</div>

<div class="form-card no-print">

<form method="get">
<label>Selecione o Planejamento</label>
<select name="planejamento_id" onchange="this.form.submit()" required>
<option value="">Selecione</option>
<?php foreach ($planejamentos as $p): ?>
<option value="<?= $p['id'] ?>" <?= $planejamentoSelecionado==$p['id']?'selected':'' ?>>
<?= htmlspecialchars($p['nome']) ?>
</option>
<?php endforeach; ?>
</select>
</form>

<?php if ($planejamentoSelecionado && !$trechosDisponiveis): ?>
<p style="color:#9ca3af;margin-top:10px;">
    Nenhum trecho medido neste planejamento ainda. O relatório só inclui trechos com medição lançada.
</p>
<?php endif; ?>

<?php if ($trechosDisponiveis): ?>
<hr>

<form method="post">
<h4>Selecione os Trechos Medidos</h4>

<?php foreach ($trechosDisponiveis as $t): ?>
<label style="display:block;margin-bottom:8px;">
    <input type="checkbox" name="trechos[]" value="<?= $t['id'] ?>" checked>
    <?= htmlspecialchars($t['pv_montante']) ?> → <?= htmlspecialchars($t['pv_jusante']) ?>
    <?php if (trim((string)$t['medicao']) !== ''): ?>
        <span style="color:#9ca3af;">· <?= htmlspecialchars($t['medicao']) ?></span>
    <?php endif; ?>
</label>
<?php endforeach; ?>

<div class="form-actions">
    <button class="btn-primary">📄 Gerar Relatório</button>
</div>
</form>
<?php endif; ?>

</div>

<?php if ($dados): ?>

<div class="form-actions no-print" style="justify-content:flex-start;">
    <button class="btn-primary" onclick="window.print()">🖨 Imprimir / Salvar PDF</button>
</div>

<?php if ($periodoOrigem !== null && !$periodoPendente): ?>
<div class="form-card no-print" style="border:1px solid #22c55e;background:#052e16;">
    <strong style="color:#bbf7d0;">Período apurado pelo sistema: <?= htmlspecialchars($periodoTexto) ?></strong>
    <p style="color:#dcfce7;font-size:13px;margin-top:6px;line-height:1.5;">
        Fonte: <?= htmlspecialchars($periodoOrigem) ?>. Não é digitado — sai dos trechos
        selecionados acima. Se o contrato fixar um período diferente, preencha
        <code>periodo</code> em <code>app/config/relatorio.php</code> e ele passa a valer.
    </p>
</div>
<?php endif; ?>

<?php if ($periodoPendente || mu_rel_pendente('resp_tecnico') || mu_rel_pendente('art')): ?>
<div class="form-card no-print" style="border:1px solid #f59e0b;background:#2a1a06;">
    <strong style="color:#fde68a;">Documento com campos pendentes</strong>
    <ul style="color:#fef3c7;font-size:13px;margin:8px 0 0 18px;line-height:1.6;">
        <?php if ($periodoPendente): ?>
        <li><strong>Período</strong> — os trechos selecionados não têm data de captura
            nem data de lançamento registrada. Assim que houver medição lançada com foto,
            o sistema apura sozinho.</li>
        <?php endif; ?>
        <?php if (mu_rel_pendente('resp_tecnico')): ?>
        <li><strong>Responsável técnico</strong> — preencher em <code>app/config/relatorio.php</code>.</li>
        <?php endif; ?>
        <?php if (mu_rel_pendente('art')): ?>
        <li><strong>ART</strong> — preencher em <code>app/config/relatorio.php</code>.</li>
        <?php endif; ?>
    </ul>
</div>
<?php endif; ?>

<!-- =====================================================================
     DOCUMENTO OFICIAL
     Cabeçalho e rodapé repetem em todas as páginas por meio de thead e
     tfoot da tabela que envolve o documento — position:fixed não funciona
     na paginação do Chrome.
====================================================================== -->
<div class="mu-doc">
<table class="mu-sheet">

<thead><tr><td>
  <header class="mu-header">
    <img class="mu-logo" src="/marco_urbano2/assets/img/marco-urbano-logo.png" alt="Marco Urbano Urbanizadora">
    <div class="mu-header-doc">
      <strong>Relatório de Medição</strong>
      Contrato <?= htmlspecialchars($contratoDoc) ?><br>
      <?= htmlspecialchars($municipioDoc) ?>
    </div>
  </header>
</td></tr></thead>

<tfoot><tr><td>
  <footer class="mu-footer">
    <span><?= htmlspecialchars(MU_RELATORIO['contratada']) ?> &nbsp;·&nbsp;
          CNPJ: <?= htmlspecialchars(MU_RELATORIO['cnpj']) ?> &nbsp;·&nbsp;
          <?= htmlspecialchars(MU_RELATORIO['crea']) ?></span>
    <span class="mu-doc-id"><?= htmlspecialchars(MU_RELATORIO['titulo']) ?> · Contrato <?= htmlspecialchars($contratoDoc) ?></span>
  </footer>
</td></tr></tfoot>

<tbody><tr><td>

  <h1 class="mu-title"><?= htmlspecialchars(MU_RELATORIO['titulo']) ?></h1>
  <p class="mu-subtitle"><?= htmlspecialchars(MU_RELATORIO['subtitulo']) ?></p>

  <table class="mu-ident">
    <tr>
      <th>Contratante</th><td><?= htmlspecialchars(MU_RELATORIO['contratante']) ?></td>
      <th>Contrato</th><td><?= htmlspecialchars($contratoDoc) ?></td>
    </tr>
    <tr>
      <th>Contratada</th><td><?= htmlspecialchars(MU_RELATORIO['contratada']) ?></td>
      <th>Município</th><td><?= htmlspecialchars($municipioDoc) ?></td>
    </tr>
    <tr>
      <th>Bacia</th><td><?= htmlspecialchars($listaBacias !== '' ? $listaBacias : '—') ?></td>
      <th>Medições</th><td><?= htmlspecialchars($listaMedicoes !== '' ? $listaMedicoes : '—') ?></td>
    </tr>
    <tr>
      <th>Período</th>
      <td<?= $periodoPendente ? ' class="mu-conf"' : '' ?>><?= htmlspecialchars($periodoTexto) ?></td>
      <th>Emissão</th><td><?= htmlspecialchars($emissao) ?></td>
    </tr>
    <tr>
      <th>Resp. técnico</th>
      <td<?= mu_rel_pendente('resp_tecnico') ? ' class="mu-conf"' : '' ?>><?= htmlspecialchars(mu_rel('resp_tecnico')) ?></td>
      <th>ART</th>
      <td<?= mu_rel_pendente('art') ? ' class="mu-conf"' : '' ?>><?= htmlspecialchars(mu_rel('art')) ?></td>
    </tr>
  </table>

<?php foreach ($dados as $d):
    $t = $d['t'];
    $rotuloTrecho = $t['pv_montante'] . ' → ' . $t['pv_jusante'];
?>
    <section class="mu-trecho">
      <div class="mu-trecho-head">
        <span>Trecho <?= htmlspecialchars($rotuloTrecho) ?></span>
        <?php if (trim((string)$t['medicao']) !== ''): ?>
          <span class="mu-med"><?= htmlspecialchars($t['medicao']) ?></span>
        <?php endif; ?>
      </div>
      <div class="mu-trecho-body">

        <div class="mu-meta">
          <div><span class="mu-rot">Município</span><span class="mu-val"><?= htmlspecialchars($t['cidade']) ?></span></div>
          <div><span class="mu-rot">Bacia</span><span class="mu-val"><?= htmlspecialchars($t['bacia']) ?></span></div>
          <div><span class="mu-rot">Tipo de pavimento</span><span class="mu-val"><?= htmlspecialchars(tipo_pavimento_label($t['tipo_pavimento'])) ?></span></div>
        </div>

        <?php if ($d['segmentos']): ?>
        <table class="mu-tab">
          <thead>
            <tr><th style="width:12mm">Seg.</th><th>Comprimento (m)</th><th>Largura (m)</th><th>Área (m²)</th></tr>
          </thead>
          <tbody>
          <?php foreach ($d['segmentos'] as $k => $s): ?>
            <tr>
              <td><?= $k + 1 ?></td>
              <td><?= mu_num($s['comprimento']) ?></td>
              <td><?= mu_num($s['largura']) ?></td>
              <td><?= mu_num($s['area']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td colspan="3">Área total do trecho</td>
                <td class="mu-tot-val"><?= mu_num($d['area']) ?> m²</td></tr>
          </tfoot>
        </table>
        <?php endif; ?>

        <div class="mu-figs">
          <?php if (!empty($t['croqui'])): ?>
          <h5>Croqui</h5>
          <div class="mu-galeria mu-croqui">
            <figure class="mu-fig">
              <img src="<?= htmlspecialchars(mu_url_thumb($t['croqui'], 880, 'croqui')) ?>"
                   alt="Croqui do trecho <?= htmlspecialchars($rotuloTrecho) ?>"
                   loading="eager" decoding="sync"
                   onerror="this.onerror=null;this.src='<?= htmlspecialchars(mu_url_midia($t['croqui'], 'croqui')) ?>';">
              <figcaption>Croqui 1 — trecho <?= htmlspecialchars($rotuloTrecho) ?></figcaption>
            </figure>
          </div>
          <?php endif; ?>

          <?php if ($d['fotos']): ?>
          <h5>Registro fotográfico</h5>
          <div class="mu-galeria">
            <?php foreach ($d['fotos'] as $k => $f):
                $dataFoto = !empty($f['created_at']) ? date('d/m/Y', strtotime($f['created_at'])) : '';
            ?>
            <figure class="mu-fig">
              <img src="<?= htmlspecialchars(mu_url_thumb($f['arquivo'], 440)) ?>"
                   alt="Foto da repavimentação do trecho <?= htmlspecialchars($rotuloTrecho) ?>"
                   loading="eager" decoding="sync"
                   onerror="this.onerror=null;this.src='<?= htmlspecialchars(mu_url_midia($f['arquivo'])) ?>';">
              <figcaption>Foto <?= $k + 1 ?><?= $dataFoto !== '' ? ' — ' . $dataFoto : '' ?></figcaption>
            </figure>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

      </div>
    </section>
<?php endforeach; ?>

  <section class="mu-resumo">
    <div class="mu-trecho-head">
      <span>Resumo consolidado</span>
      <span class="mu-med"><?= count($dados) ?> trecho<?= count($dados) === 1 ? '' : 's' ?></span>
    </div>
    <div class="mu-trecho-body">
      <table>
        <thead>
          <tr><th>Medição</th><th>Tipo de pavimento</th><th class="num">Trechos</th><th class="num">Área reposta (m²)</th></tr>
        </thead>
        <tbody>
        <?php foreach ($resumo as $med => $porTipo): ?>
            <?php ksort($porTipo); foreach ($porTipo as $tipo => $r): ?>
            <tr>
              <td><?= htmlspecialchars($med) ?></td>
              <td><?= htmlspecialchars(tipo_pavimento_label($tipo)) ?></td>
              <td class="num"><?= $r['trechos'] ?></td>
              <td class="num"><?= mu_num($r['area']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <?php ksort($totalPorTipo); foreach ($totalPorTipo as $tipo => $r): ?>
          <tr>
            <td colspan="2">Total <?= htmlspecialchars(tipo_pavimento_label($tipo)) ?></td>
            <td class="num"><?= $r['trechos'] ?></td>
            <td class="num"><?= mu_num($r['area']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tfoot>
      </table>
      <p class="mu-nota">
        Os totais são apresentados <strong>separadamente por tipo de pavimento</strong>,
        por corresponderem a itens distintos da planilha contratual, com preços unitários
        próprios. Não se aplica soma geral entre tipos diferentes.
      </p>
    </div>
  </section>

  <p class="mu-local-data">
    <?= htmlspecialchars($municipioAssinatura) ?>, <?= htmlspecialchars($emissao) ?>.
  </p>

  <div class="mu-assin">
    <div>
      <div class="mu-linha-assin"></div>
      <strong<?= mu_rel_pendente('resp_tecnico') ? ' class="mu-conf"' : '' ?>>
        <?= htmlspecialchars(mu_rel('resp_tecnico', 'Responsável Técnico — a confirmar')) ?>
      </strong>
      <span><?= htmlspecialchars(MU_RELATORIO['contratada']) ?><br>
            CNPJ <?= htmlspecialchars(MU_RELATORIO['cnpj']) ?> · <?= htmlspecialchars(MU_RELATORIO['crea']) ?></span>
    </div>
  </div>

</td></tr></tbody>
</table>
</div>

<?php endif; ?>

</main>
</div>

<script>
/* Foto que ainda não foi rolada sai em branco no PDF: antes de imprimir,
   converte todo lazy em eager e espera o que faltar carregar. */
window.addEventListener('beforeprint', function () {
    document.querySelectorAll('img[loading="lazy"]').forEach(function (img) {
        img.loading = 'eager';
        if (!img.complete && img.src) { img.src = img.src; }
    });
});
</script>

</body>
</html>
