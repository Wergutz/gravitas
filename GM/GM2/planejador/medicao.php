<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/asset.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/tipos_pavimento.php';
require_once __DIR__ . '/../app/helpers/validacao_midia.php';
require_once __DIR__ . '/../app/helpers/midia_url.php';

auth_required([3, 4]);

$erro      = null;
$recusados = [];   // arquivos barrados (só quando MU_VALIDACAO_BLOQUEIA)
$avisos    = [];   // observações que não impedem o lançamento

/* ===============================
   PLANEJAMENTOS
   -------------------------------
   Todos os planejamentos, de qualquer planejador — não só os do usuário
   logado. A equipe trabalha na mesma obra: quem cria o planejamento e
   quem lança a medição costumam ser pessoas diferentes.

   A coluna planejamentos.usuario_id continua guardando QUEM criou, e as
   telas de listagem mostram esse nome. O que mudou é que ela não filtra
   mais o acesso.
================================ */
$stmt = $pdo->prepare("
    SELECT p.id, p.nome, u.nome AS criado_por
    FROM planejamentos p
    JOIN usuarios u ON u.id = p.usuario_id
    ORDER BY p.id DESC
");
$stmt->execute();
$planejamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   PLANEJAMENTO SELECIONADO
================================ */
$planejamentoSelecionado = null;
$trechos = [];
$trechoSelecionado = null;

if (!empty($_GET['planejamento_id'])) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM planejamentos
        WHERE id = ?
    ");
    $stmt->execute([$_GET['planejamento_id']]);
    $planejamentoSelecionado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($planejamentoSelecionado) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM trechos
            WHERE planejamento_id = ?
            ORDER BY id
        ");
        $stmt->execute([$_GET['planejamento_id']]);
        $trechos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

/* ===============================
   TRECHO SELECIONADO
================================ */
if (!empty($_GET['trecho_id'])) {
    $stmt = $pdo->prepare("
        SELECT t.*
        FROM trechos t
        JOIN planejamentos p ON p.id = t.planejamento_id
        WHERE t.id = ?
          AND t.area_total = 0
    ");
    $stmt->execute([$_GET['trecho_id']]);
    $trechoSelecionado = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ===============================
   SALVAR MEDIÇÃO
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

        if (empty($_POST['trecho_id'])) throw new Exception('Trecho não informado.');
        if (empty($_POST['tipo_pavimento'])) throw new Exception('Selecione o tipo de pavimento.');
        if (empty($_POST['comprimento']) || empty($_POST['largura'])) throw new Exception('Informe comprimento e largura.');

        $trechoId  = (int) $_POST['trecho_id'];
        $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);

        /* ---------------------------------------------------------------
           1) Fila dos arquivos recebidos

           O croqui pode vir de dois campos — câmera ou arquivo. São nomes
           distintos de propósito: com o mesmo nome, o campo vazio
           sobrescreveria o preenchido.

           As fotos vêm dos dois campos com o mesmo `fotos[]`, e o PHP já
           junta tudo num array só.
        --------------------------------------------------------------- */
        $fila = [];

        foreach (['croqui_camera', 'croqui'] as $campo) {
            if (($_FILES[$campo]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $fila[] = ['tipo' => 'croqui', 'f' => $_FILES[$campo]];
                break;   // um croqui basta; a câmera tem preferência
            }
        }

        $temCroqui = (bool) $fila;

        $nFotos = count($_FILES['fotos']['name'] ?? []);
        for ($i = 0; $i < $nFotos; $i++) {
            if (($_FILES['fotos']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            $fila[] = ['tipo' => 'foto', 'f' => [
                'name'     => $_FILES['fotos']['name'][$i],
                'tmp_name' => $_FILES['fotos']['tmp_name'][$i],
                'size'     => $_FILES['fotos']['size'][$i],
                'error'    => $_FILES['fotos']['error'][$i],
            ]];
        }

        $temFoto = count($fila) > ($temCroqui ? 1 : 0);

        /* Trecho sem pavimentação não tem pavimento a repor, logo não há
           área cotada a desenhar: nesse caso o croqui é dispensado. Nos
           demais tipos continua obrigatório. */
        if (!$temCroqui && exige_croqui($_POST['tipo_pavimento'])) {
            throw new Exception(
                'Croqui é obrigatório para ' . tipo_pavimento_label($_POST['tipo_pavimento']) . '.'
            );
        }
        if (!$temFoto) throw new Exception('Envie ao menos uma foto.');

        /* ---------------------------------------------------------------
           2) Conferir TUDO antes de gravar QUALQUER COISA.

              Com MU_VALIDACAO_BLOQUEIA em false nada é recusado.
        --------------------------------------------------------------- */
        foreach ($fila as $item) {
            $v = mu_validar_midia(
                $item['f']['tmp_name'],
                (string) $item['f']['name'],
                $item['tipo'],
                $trechoId,
                null,               // referência de data: o momento do lançamento
                $pdo
            );

            if (!$v['ok']) {
                $recusados[] = ['arquivo' => $item['f']['name'], 'erros' => $v['erros']];
            }

            foreach ($v['avisos'] as $a) {
                $avisos[] = ['arquivo' => $item['f']['name'], 'aviso' => $a];
            }
        }

        /* ---------------------------------------------------------------
           3) No modo bloqueante, um reprovado e nada é gravado — nem as
              medidas do trecho. Fora dele, segue direto.
        --------------------------------------------------------------- */
        if ($recusados) {
            throw new Exception('Lançamento recusado: há arquivo sem valor probatório.');
        }

        /* ---------------------------------------------------------------
           4) Só agora grava
        --------------------------------------------------------------- */
        $pdo->beginTransaction();

        $areaTotal = 0;
        foreach ($_POST['comprimento'] as $i => $c) {
            $l = $_POST['largura'][$i];
            $a = $c * $l;
            $areaTotal += $a;

            $pdo->prepare("
                INSERT INTO pavimento_produzido (trecho_id, comprimento, largura, area)
                VALUES (?, ?, ?, ?)
            ")->execute([$trechoId, $c, $l, $a]);
        }

        $pdo->prepare("
            UPDATE trechos
            SET area_total = ?, tipo_pavimento = ?
            WHERE id = ?
        ")->execute([$areaTotal, $_POST['tipo_pavimento'], $trechoId]);

        foreach ($fila as $item) {
            $reg = mu_arquivar_midia(
                $item['f'], $trechoId, $item['tipo'], null, $usuarioId ?: null, $pdo
            );

            if ($item['tipo'] === 'croqui') {
                $pdo->prepare("UPDATE trechos SET croqui = ? WHERE id = ?")
                    ->execute([$reg['caminho'], $trechoId]);
            } else {
                $pdo->prepare("INSERT INTO trecho_fotos (trecho_id, arquivo) VALUES (?, ?)")
                    ->execute([$trechoId, $reg['caminho']]);
            }
        }

        $pdo->commit();
        header("Location: medicao.php?planejamento_id=".$_GET['planejamento_id']."&ok=1");
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Medição | GM SERVIÇOS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= mu_asset('/assets/css/planejador.css') ?>">
</head>
<body>

<div class="app">

<aside class="sidebar">
    <div class="logo">
        <img src="/GM/GM2/assets/img/icon-gm.png" alt="GM SERVIÇOS">
        <span>GM SERVIÇOS</span>
    </div>
    <?= bloco_identidade() ?>
    <nav>
        <a href="/GM/GM2/planejador/menu.php">📊 Dashboard</a>
        <?php if (eh_planejador()): ?>
        <a href="/GM/GM2/planejador/planejamento.php">🗂 Planejamento</a>
        <?php endif; ?>
        <a href="/GM/GM2/planejador/medicao.php" class="active">📐 Incluir Medições</a>
        <a href="/GM/GM2/planejador/relatorio.php">📄 Relatório</a>
        <a href="/GM/GM2/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content">

<div class="topbar">
    <div>
        <h1>Medição por Trecho</h1>
        <span><?= $planejamentoSelecionado ? htmlspecialchars($planejamentoSelecionado['nome']) : 'Selecione um planejamento' ?></span>
    </div>
</div>

<?php if (!empty($_GET['ok'])): ?>
<div class="form-card" style="border:1px solid #22c55e;background:#052e16;">
    <strong style="color:#bbf7d0;font-size:16px;">✅ Medição gravada.</strong>
    <p style="color:#dcfce7;font-size:14px;margin-top:6px;line-height:1.5;">
        O trecho já aparece como medido.
        Selecione outro trecho abaixo para continuar.
    </p>
</div>
<?php endif; ?>

<?php if ($erro && !$recusados): ?>
<div class="form-card" style="border:1px solid #ef4444;background:#2a0606;">
    <strong style="color:#fecaca;">Erro:</strong>
    <span style="color:#fee2e2;"><?= htmlspecialchars($erro) ?></span>
</div>
<?php endif; ?>

<?php if ($recusados): ?>
<div class="form-card" style="border:1px solid #ef4444;background:#2a0606;">
    <h3 style="color:#fecaca;margin-bottom:6px;">Lançamento recusado</h3>
    <p style="color:#fee2e2;font-size:14px;margin-bottom:14px;">
        Nada foi gravado — nem as medidas do trecho. Corrija os arquivos abaixo e envie tudo de novo.
    </p>

    <?php foreach ($recusados as $r): ?>
        <div style="margin-bottom:14px;padding-left:12px;border-left:3px solid #ef4444;">
            <div style="color:#fff;font-weight:600;font-size:14px;word-break:break-all;">
                <?= htmlspecialchars($r['arquivo']) ?>
            </div>
            <?php foreach ($r['erros'] as $e): ?>
                <div style="margin-top:8px;">
                    <div style="color:#fecaca;font-weight:600;font-size:13px;">
                        <?= htmlspecialchars($e['titulo']) ?>
                    </div>
                    <div style="color:#e5e7eb;font-size:13px;line-height:1.5;">
                        <?= htmlspecialchars($e['como_corrigir']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($avisos): ?>
<div class="form-card" style="border:1px solid #f59e0b;background:#2a1a06;">
    <strong style="color:#fde68a;">Observações</strong>
    <?php foreach ($avisos as $a): ?>
        <div style="margin-top:8px;color:#fef3c7;font-size:13px;">
            <strong><?= htmlspecialchars($a['arquivo']) ?>:</strong>
            <?= htmlspecialchars($a['aviso']['titulo']) ?>
            <?= htmlspecialchars($a['aviso']['como_corrigir']) ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="form-card">
<form method="get">
<label>Planejamento</label>
<select name="planejamento_id" onchange="this.form.submit()" required>
<option value="">Selecione</option>
<?php foreach ($planejamentos as $p): ?>
<option value="<?= $p['id'] ?>" <?= (!empty($_GET['planejamento_id']) && $_GET['planejamento_id']==$p['id'])?'selected':'' ?>>
<?= htmlspecialchars($p['nome'] ?? ('Planejamento ' . $p['id'])) ?> — <?= htmlspecialchars($p['criado_por']) ?>
</option>
<?php endforeach; ?>
</select>
</form>

<?php if ($trechos): ?>
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-top:10px;">
<?php foreach ($trechos as $t): ?>
<a
href="<?= $t['area_total']>0
    ? 'editar_medicao.php?trecho_id='.$t['id']
    : 'medicao.php?planejamento_id='.$_GET['planejamento_id'].'&trecho_id='.$t['id']
?>"
style="padding:6px;border-radius:6px;text-align:center;font-size:12px;font-weight:600;text-decoration:none;
background:<?= $t['area_total']>0?'#22c55e':'#9ca3af' ?>;
color:<?= $t['area_total']>0?'#022c22':'#0D1A2E' ?>;">
<?= htmlspecialchars($t['pv_montante'].' → '.$t['pv_jusante']) ?><br>
<small><?= $t['area_total']>0?'Editar medição':'Medir trecho' ?></small>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<?php if ($trechoSelecionado): ?>
<form method="post" enctype="multipart/form-data" class="form-card" style="margin-top:20px;">
<h3>
Trecho selecionado:<br>
<strong><?= htmlspecialchars($trechoSelecionado['pv_montante'].' → '.$trechoSelecionado['pv_jusante']) ?></strong>
</h3>

<input type="hidden" name="trecho_id" value="<?= $trechoSelecionado['id'] ?>">

<?php
/* Diz, antes do envio, contra qual cidade a foto será conferida. Sem
   isto a conferência de distância pode simplesmente não rodar — quando
   a cidade do trecho não tem coordenada — e quem lança não fica sabendo,
   achando que passou na verificação. */
$obraTrecho = mu_coordenada_municipio($trechoSelecionado['cidade'] ?? '');
?>
<?php if ($obraTrecho !== null): ?>
<p style="margin:-4px 0 14px;font-size:13px;color:#86efac;">
    📍 A localização das fotos será conferida contra
    <strong><?= htmlspecialchars($obraTrecho['nome']) ?></strong>,
    com tolerância de <?= number_format($obraTrecho['raio_km'], 0, ',', '.') ?> km.
</p>
<?php else: ?>
<p style="margin:-4px 0 14px;padding:10px;border-left:3px solid #f59e0b;background:#2a1a06;
          font-size:13px;color:#fef3c7;line-height:1.6;">
    ⚠️ <strong>A localização das fotos não será conferida neste trecho.</strong>
    <?php if (trim((string)($trechoSelecionado['cidade'] ?? '')) === ''): ?>
        A cidade do trecho está em branco.
    <?php else: ?>
        Não existe município chamado
        “<?= htmlspecialchars($trechoSelecionado['cidade']) ?>”
        <?= MU_UF_PADRAO ? 'no ' . htmlspecialchars(MU_UF_PADRAO) : 'na base do IBGE' ?> —
        confira se o nome está escrito corretamente no cadastro do trecho.
        <?php if (MU_UF_PADRAO): ?>
            Se o trecho for mesmo de outro estado, informe a UF junto
            (ex.: <em>Chapecó / SC</em>).
        <?php endif; ?>
    <?php endif; ?>
    As demais regras continuam valendo.
</p>
<?php endif; ?>

<div class="form-group">
<label>Tipo de Pavimento</label>
<select name="tipo_pavimento" id="tipo_pavimento" required>
<option value="">Selecione</option>
<?php foreach (TIPOS_PAVIMENTO as $valor => $label): ?>
<option value="<?= $valor ?>" <?= exige_croqui($valor) ? '' : 'data-sem-croqui="1"' ?>>
    <?= htmlspecialchars($label) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div id="linhas">
<div class="form-row">
<div class="form-group">
<label>Comprimento (m)</label>
<input type="number" step="0.01" name="comprimento[]" oninput="calcular()" required>
</div>
<div class="form-group">
<label>Largura (m)</label>
<input type="number" step="0.01" name="largura[]" oninput="calcular()" required>
</div>
</div>
</div>

<p style="margin-top:10px;font-weight:600;">
Área total: <span id="areaTotal">0.00</span> m²
</p>

<button type="button" class="btn-secondary" onclick="addLinha()">➕ Adicionar Linha</button>

<hr style="margin:15px 0;">

<div class="form-group" id="grupo-croqui">
<label>Croqui <span id="croqui-exigencia"></span></label>
<div class="mu-envio">
    <label class="mu-envio-op">
        📷 Fotografar croqui
        <input type="file" name="croqui_camera" accept="image/*" capture="environment">
    </label>
    <label class="mu-envio-op">
        📁 Escolher arquivo
        <input type="file" name="croqui" accept="image/*,application/pdf">
    </label>
</div>
<small class="mu-dica" id="croqui-dica">Pode fotografar o croqui em papel ou enviar o arquivo digitalizado.</small>
</div>

<div class="form-group">
<label>Fotos do serviço</label>
<div class="mu-envio">
    <label class="mu-envio-op mu-envio-camera">
        📷 Tirar foto agora
        <input type="file" name="fotos[]" accept="image/*" capture="environment" multiple>
    </label>
    <label class="mu-envio-op">
        🖼️ Enviar da galeria
        <input type="file" name="fotos[]" accept="image/*" multiple>
    </label>
</div>
<small class="mu-dica">
    <strong>Prefira “Enviar da galeria”</strong>, escolhendo a foto tirada pelo aplicativo
    de câmera do celular. É o caminho que preserva data e localização — sem esses dados
    a foto é recusada. Use “Tirar foto agora” só se o aparelho estiver comprovadamente
    gravando a localização por este caminho.
</small>
</div>

<div class="form-actions">
<button type="submit" class="btn-primary">💾 Salvar Medição</button>
</div>

</form>
<?php endif; ?>

</main>
</div>

<script>
function addLinha(){
    const div=document.createElement('div');
    div.className='form-row';
    div.innerHTML=`
    <div class="form-group">
        <label>Comprimento (m)</label>
        <input type="number" step="0.01" name="comprimento[]" oninput="calcular()" required>
    </div>
    <div class="form-group">
        <label>Largura (m)</label>
        <input type="number" step="0.01" name="largura[]" oninput="calcular()" required>
    </div>`;
    document.getElementById('linhas').appendChild(div);
}

function calcular(){
    let total=0;
    document.querySelectorAll('#linhas .form-row').forEach(row=>{
        const c=parseFloat(row.querySelector('[name="comprimento[]"]').value)||0;
        const l=parseFloat(row.querySelector('[name="largura[]"]').value)||0;
        total+=c*l;
    });
    document.getElementById('areaTotal').innerText=total.toFixed(2);
}
</script>

<!-- Conferência no navegador: avisa antes de subir 12 MB para ser recusado.
     Conveniência apenas — quem decide é a validação do servidor. -->
<script src="<?= mu_asset('/assets/js/validacao-midia.js') ?>"></script>
<script>
/* Coordenada do município DESTE trecho, para a conferência no navegador
   usar a mesma referência do servidor. Sem isto a tela acusaria "fora da
   obra" em qualquer contrato que não fosse o de Barra do Quaraí. */
var MU_OBRA = <?= json_encode(
    (function () use ($trechoSelecionado) {
        if (!$trechoSelecionado) return null;
        $c = mu_coordenada_municipio($trechoSelecionado['cidade'] ?? '');
        if (!$c) return null;
        return [
            'obraLat'  => $c['lat'],
            'obraLon'  => $c['lon'],
            'raioKm'   => $c['raio_km'],
            'obraNome' => $c['nome'],
            /* Homônimos em outros estados, para a tela usar a mesma
               regra do servidor: vale o mais próximo da foto. */
            'obraPontos' => array_map(fn($h) => [
                'lat'  => $h['lat'],
                'lon'  => $h['lon'],
                'nome' => $h['nome'] . ' / ' . $h['uf'],
            ], $c['homonimos'] ?? []),
        ];
    })(),
    JSON_UNESCAPED_UNICODE
) ?>;

document.addEventListener('DOMContentLoaded', function () {
    /* Conferência no navegador, nos dois caminhos de envio de foto. */
    document.querySelectorAll('input[name="fotos[]"]').forEach(function (inp) {
        if (window.MUValidacao) { MUValidacao.ligar(inp, MU_OBRA || {}); }
    });

    /* O croqui é dispensado em trecho sem pavimentação: não há área
       reposta a cotar. A tela diz isso na hora da escolha, para não
       descobrir só ao tentar salvar. */
    var selPav  = document.getElementById('tipo_pavimento');
    var marca   = document.getElementById('croqui-exigencia');
    var dica    = document.getElementById('croqui-dica');
    var dicaOrig = dica ? dica.textContent : '';

    function atualizarCroqui() {
        if (!selPav || !marca) return;
        var op = selPav.options[selPav.selectedIndex];
        var dispensado = !!(op && op.getAttribute('data-sem-croqui'));

        if (dispensado) {
            marca.textContent = '(opcional)';
            marca.style.color = '#86efac';
            if (dica) {
                dica.textContent = 'Neste tipo de pavimento o croqui não é exigido — não há '
                                 + 'área reposta a cotar. Se tiver o desenho, pode enviar mesmo assim.';
            }
        } else {
            marca.textContent = selPav.value ? '(obrigatório)' : '';
            marca.style.color = '#fca5a5';
            if (dica) { dica.textContent = dicaOrig; }
        }
    }

    if (selPav) {
        selPav.addEventListener('change', atualizarCroqui);
        atualizarCroqui();
    }

    /* Retorno visual: sem isto o input fica escondido no label e a pessoa
       não sabe se o arquivo foi mesmo anexado. */
    document.querySelectorAll('.mu-envio-op input[type="file"]').forEach(function (inp) {
        inp.addEventListener('change', function () {
            var box = inp.closest('.mu-envio-op');
            var n   = inp.files ? inp.files.length : 0;
            if (!box) return;

            var rotulo = box.querySelector('.mu-envio-rotulo');
            if (!rotulo) {
                rotulo = document.createElement('span');
                rotulo.className = 'mu-envio-rotulo';
                box.appendChild(rotulo);
            }

            if (n > 0) {
                box.classList.add('tem-arquivo');
                rotulo.textContent = n === 1 ? '— 1 arquivo' : '— ' + n + ' arquivos';
            } else {
                box.classList.remove('tem-arquivo');
                rotulo.textContent = '';
            }

            /* Croqui: os dois campos são alternativas, não somam.
               Escolher num deles limpa o outro. */
            if (inp.name.indexOf('croqui') === 0 && n > 0) {
                document.querySelectorAll('input[name="croqui"], input[name="croqui_camera"]')
                    .forEach(function (outro) {
                        if (outro === inp) return;
                        outro.value = '';
                        var b = outro.closest('.mu-envio-op');
                        if (b) {
                            b.classList.remove('tem-arquivo');
                            var r = b.querySelector('.mu-envio-rotulo');
                            if (r) { r.textContent = ''; }
                        }
                    });
            }
        });
    });
});
</script>

</body>
</html>
