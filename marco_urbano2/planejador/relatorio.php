<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/database.php';

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
        SELECT id, pv_montante, pv_jusante
        FROM trechos
        WHERE planejamento_id = ?
          AND area_total > 0
        ORDER BY id
    ");
    $stmt->execute([$planejamentoSelecionado]);
    $trechosDisponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================================
   TRECHOS SELECIONADOS
========================================= */
$trechosSelecionados = [];

if (!empty($_POST['trechos'])) {
    $placeholders = implode(',', array_fill(0, count($_POST['trechos']), '?'));
    $stmt = $pdo->prepare("
        SELECT *
        FROM trechos
        WHERE id IN ($placeholders)
        ORDER BY id
    ");
    $stmt->execute($_POST['trechos']);
    $trechosSelecionados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Relatório de Medição | VisionHub Locar</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="/visionhub_locar/assets/css/planejador.css?v=1">

<style>
@media print {

    .sidebar, .topbar, .no-print, nav, button {
        display: none !important;
    }

    body, .app, .content {
        background: #ffffff !important;
        color: #000000 !important;
        font-family: Arial, Helvetica, sans-serif !important;
    }

    .form-card {
        background: #ffffff !important;
        border: none !important;
        box-shadow: none !important;
        padding: 10px 8px !important;
        margin-bottom: 34px !important;
    }

    h3 {
        font-size: 18px !important;
        font-weight: bold !important;
        margin-bottom: 10px !important;
    }

    h4 {
        font-size: 15px !important;
        font-weight: bold !important;
        margin-top: 14px !important;
        margin-bottom: 6px !important;
    }

    p {
        font-size: 13px !important;
        color: #000000 !important;
        margin-bottom: 10px !important;
        line-height: 1.4 !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin-top: 10px !important;
        margin-bottom: 14px !important;
    }

    table th, table td {
        border: 1px solid #000 !important;
        padding: 8px !important;
        font-size: 13px !important;
        text-align: center !important;
    }

    img {
        max-width: 240px !important;
        border: 1px solid #000 !important;
        margin: 8px 6px !important;
    }

    .page-break {
        page-break-after: always;
    }
}
</style>
</head>

<body>
<div class="app">

<aside class="sidebar no-print">
    <div class="logo">
        <img src="/visionhub_locar/assets/img/farol.png">
        <span>VISION HUB</span>
    </div>
    <nav>
        <a href="/visionhub_locar/planejador/menu.php">📊 Dashboard</a>
        <a href="/visionhub_locar/planejador/planejamento.php">🗂 Novo Planejamento</a>
        <a href="/visionhub_locar/planejador/medicao.php">📐 Medição</a>
        <a href="/visionhub_locar/planejador/relatorio.php" class="active">📄 Relatório</a>
        <a href="/visionhub_locar/public/logout.php">🚪 Sair</a>
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
    <div class="managed">MANAGED BY GRAVITAS</div>
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

<?php if ($trechosDisponiveis): ?>
<hr>

<form method="post">
<h4>Selecione os Trechos Medidos</h4>

<?php foreach ($trechosDisponiveis as $t): ?>
<label style="display:block;margin-bottom:8px;">
    <input type="checkbox" name="trechos[]" value="<?= $t['id'] ?>" checked>
    <?= htmlspecialchars($t['pv_montante']) ?> → <?= htmlspecialchars($t['pv_jusante']) ?>
</label>
<?php endforeach; ?>

<div class="form-actions">
    <button class="btn-primary">📄 Gerar Relatório</button>
</div>
</form>
<?php endif; ?>

</div>

<?php if ($trechosSelecionados): ?>

<?php foreach ($trechosSelecionados as $i => $t): ?>

<?php
$stmt = $pdo->prepare("SELECT * FROM pavimento_produzido WHERE trecho_id = ?");
$stmt->execute([$t['id']]);
$medicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$areaTotal = array_sum(array_column($medicoes,'area'));
?>

<div class="form-card">

<h3>Trecho <?= htmlspecialchars($t['pv_montante']) ?> → <?= htmlspecialchars($t['pv_jusante']) ?></h3>

<p>
<strong>Medição:</strong> <?= htmlspecialchars($t['medicao']) ?><br>
<strong>Cidade:</strong> <?= htmlspecialchars($t['cidade']) ?><br>
<strong>Contrato:</strong> <?= htmlspecialchars($t['contrato']) ?><br>
<strong>Bacia:</strong> <?= htmlspecialchars($t['bacia']) ?><br>
<strong>Tipo de Pavimento:</strong> <?= ucwords(str_replace('_',' ',$t['tipo_pavimento'])) ?>
</p>

<?php if ($medicoes): ?>
<table>
<thead>
<tr><th>#</th><th>Comprimento</th><th>Largura</th><th>Área</th></tr>
</thead>
<tbody>
<?php foreach ($medicoes as $k=>$m): ?>
<tr>
<td><?= $k+1 ?></td>
<td><?= number_format($m['comprimento'],2,',','.') ?></td>
<td><?= number_format($m['largura'],2,',','.') ?></td>
<td><?= number_format($m['area'],2,',','.') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<p><strong>Área Total:</strong> <?= number_format($areaTotal,2,',','.') ?> m²</p>
<?php endif; ?>

<?php if (!empty($t['croqui'])): ?>
<h4>Croqui</h4>
<img src="/visionhub_locar/uploads/croquis/<?= htmlspecialchars($t['croqui']) ?>">
<?php endif; ?>

<h4>Fotos</h4>
<?php
$stmt = $pdo->prepare("SELECT * FROM trecho_fotos WHERE trecho_id = ?");
$stmt->execute([$t['id']]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f):
?>
<img src="/visionhub_locar/uploads/fotos/<?= htmlspecialchars($f['arquivo']) ?>">
<?php endforeach; ?>

</div>

<?php if ($i < count($trechosSelecionados)-1): ?>
<div class="page-break"></div>
<?php endif; ?>

<?php endforeach; ?>

<div class="form-actions no-print">
<button class="btn-primary" onclick="window.print()">🖨 Imprimir / PDF</button>
</div>

<?php endif; ?>

</main>
</div>
</body>
</html>
