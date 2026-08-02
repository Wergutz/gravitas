<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/asset.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/midia_url.php';

auth_required([3]);

$id = (int)$_GET['id'];

/* Planejamento */
$planejamento = $pdo->prepare("
    SELECT * FROM planejamentos WHERE id = ?
");
$planejamento->execute([$id]);

/* Trechos */
$trechos = $pdo->prepare("
    SELECT * FROM trechos WHERE planejamento_id = ?
");
$trechos->execute([$id]);
$trechos = $trechos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Visualizar Planejamento</title>
<link rel="stylesheet" href="<?= mu_asset('/assets/css/planejador.css') ?>">
</head>
<body>

<div class="app">

<aside class="sidebar">
    <div class="logo">
        <img src="/marco_urbano2/assets/img/logo-marco-urbano.svg" alt="Marco Urbano">
        <span>MARCO URBANO</span>
    </div>
    <nav>
        <a href="menu.php">📊 Dashboard</a>
        <a href="listagem.php">📋 Listagem</a>
        <a href="/marco_urbano2/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content">

<div class="topbar">
    <h1>Planejamento #<?= $id ?></h1>
</div>

<?php foreach ($trechos as $t): ?>

<div class="form-card">
<h3>Trecho – <?= htmlspecialchars($t['cidade']) ?></h3>

<p><b>Medição:</b> <?= $t['medicao'] ?></p>
<p><b>Contrato:</b> <?= $t['contrato'] ?></p>
<p><b>Bacia:</b> <?= $t['bacia'] ?></p>
<p><b>PV:</b> <?= $t['pv_montante'] ?> → <?= $t['pv_jusante'] ?></p>
<p><b>Área Total:</b> <?= $t['area_total'] ?> m²</p>

<p>
<b>Croqui:</b>
<a href="<?= htmlspecialchars(mu_url_midia($t['croqui'], 'croqui')) ?>" target="_blank">
📎 Abrir croqui
</a>
</p>

<h4>Fotos</h4>
<div style="display:flex;gap:10px;flex-wrap:wrap;">
<?php
$fotos = $pdo->prepare("SELECT * FROM trecho_fotos WHERE trecho_id = ?");
$fotos->execute([$t['id']]);
foreach ($fotos as $f):
?>
<a href="<?= htmlspecialchars(mu_url_midia($f['arquivo'])) ?>" target="_blank">
<img src="<?= htmlspecialchars(mu_url_thumb($f['arquivo'], 220)) ?>"
     alt="Foto do trecho <?= htmlspecialchars($t['pv_montante'].' → '.$t['pv_jusante']) ?>"
     loading="lazy" decoding="async"
     onerror="this.onerror=null;this.src='<?= htmlspecialchars(mu_url_midia($f['arquivo'])) ?>';"
     style="width:150px;border-radius:8px;">
</a>
<?php endforeach; ?>
</div>

<h4>Pavimento Produzido</h4>
<table class="table">
<thead>
<tr>
    <th>Comprimento</th>
    <th>Largura</th>
    <th>Área</th>
</tr>
</thead>
<tbody>
<?php
$pavs = $pdo->prepare("SELECT * FROM pavimento_produzido WHERE trecho_id = ?");
$pavs->execute([$t['id']]);
foreach ($pavs as $p):
?>
<tr>
    <td><?= $p['comprimento'] ?></td>
    <td><?= $p['largura'] ?></td>
    <td><?= $p['area'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</div>

<?php endforeach; ?>

</main>
</div>

</body>
</html>
