<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/asset.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/tipos_pavimento.php';
require_once __DIR__ . '/../app/helpers/midia_url.php';

auth_required([3]); // Planejador

if (!isset($_GET['id'])) {
    die('Planejamento não informado');
}

$planejamentoId = (int) $_GET['id'];

/* ===============================
   PLANEJAMENTO
================================ */
$stmt = $pdo->prepare("
    SELECT p.*, u.nome AS usuario
    FROM planejamentos p
    JOIN usuarios u ON u.id = p.usuario_id
    WHERE p.id = ?
");
$stmt->execute([$planejamentoId]);
$planejamento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$planejamento) {
    die('Planejamento não encontrado');
}

/* ===============================
   TRECHOS
================================ */
$stmt = $pdo->prepare("
    SELECT * FROM trechos
    WHERE planejamento_id = ?
    ORDER BY id ASC
");
$stmt->execute([$planejamentoId]);
$trechos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Visualizar Planejamento | GM SERVIÇOS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= mu_asset('/assets/css/planejador.css') ?>">
</head>
<body>

<div class="app">

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="logo">
        <img src="/GM/GM2/assets/img/icon-gm.png" alt="GM SERVIÇOS">
        <span>GM SERVIÇOS</span>
    </div>
    <nav>
        <a href="/GM/GM2/planejador/menu.php">📊 Dashboard</a>
        <a href="/GM/GM2/planejador/planejamento_selecionar.php">
            📂 Selecionar Planejamento
        </a>
        <a href="/GM/GM2/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<!-- CONTEÚDO -->
<main class="content">

<!-- TOPBAR -->
<div class="topbar">
    <div>
        <h1>Planejamento #<?= $planejamento['id'] ?></h1>
        <span>Criado por <?= htmlspecialchars($planejamento['usuario']) ?></span>
    </div>
</div>

<!-- BOTÃO RELATÓRIO -->
<div class="form-actions" style="justify-content:flex-start; gap:10px;">
    <a
        href="planejamento_relatorio.php?id=<?= $planejamento['id'] ?>"
        target="_blank"
        class="btn-primary"
    >
        📄 Gerar Relatório Oficial (PDF)
    </a>
</div>

<!-- DADOS GERAIS -->
<div class="form-card">
    <strong>Data de criação:</strong>
    <?= date('d/m/Y H:i', strtotime($planejamento['created_at'])) ?>
</div>

<?php foreach ($trechos as $index => $trecho): ?>

<div class="form-card">
    <h3>Trecho <?= $index + 1 ?></h3>

    <div class="form-group"><strong>Medição:</strong> <?= htmlspecialchars($trecho['medicao']) ?></div>
    <div class="form-group"><strong>Cidade:</strong> <?= htmlspecialchars($trecho['cidade']) ?></div>
    <div class="form-group"><strong>Contrato:</strong> <?= htmlspecialchars($trecho['contrato']) ?></div>
    <div class="form-group"><strong>Bacia:</strong> <?= htmlspecialchars($trecho['bacia']) ?></div>

    <div class="form-row">
        <div class="form-group"><strong>PV Montante:</strong> <?= $trecho['pv_montante'] ?></div>
        <div class="form-group"><strong>PV Jusante:</strong> <?= $trecho['pv_jusante'] ?></div>
    </div>

    <div class="form-group">
        <strong>Tipo PI Montante:</strong> <?= $trecho['tipo_pi_montante'] ?>
    </div>

    <div class="form-group">
        <strong>Quantidade de PVs:</strong> <?= $trecho['quantidade_pvs'] ?>
    </div>

    <div class="form-group">
        <strong>Tipo de Pavimento:</strong>
        <?= htmlspecialchars(tipo_pavimento_label($trecho['tipo_pavimento'])) ?>
    </div>

    <div class="form-group">
        <strong>Área Total Produzida:</strong> <?= $trecho['area_total'] ?> m²
    </div>

    <!-- CROQUI -->
    <div class="form-group">
        <strong>Croqui:</strong><br>
        <a href="<?= htmlspecialchars(mu_url_midia($trecho['croqui'], 'croqui')) ?>" target="_blank">
            📄 Visualizar Croqui
        </a>
    </div>

    <!-- PAVIMENTO PRODUZIDO -->
    <h4>Pavimento Produzido</h4>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Comprimento</th>
                <th>Largura</th>
                <th>Área</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->prepare("
            SELECT * FROM pavimento_produzido
            WHERE trecho_id = ?
        ");
        $stmt->execute([$trecho['id']]);
        $pavs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <?php foreach ($pavs as $k => $p): ?>
            <tr>
                <td><?= $k + 1 ?></td>
                <td><?= $p['comprimento'] ?></td>
                <td><?= $p['largura'] ?></td>
                <td><?= $p['area'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- FOTOS -->
    <h4>Fotos do Trecho</h4>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <?php
        $stmt = $pdo->prepare("
            SELECT * FROM trecho_fotos
            WHERE trecho_id = ?
        ");
        $stmt->execute([$trecho['id']]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $foto):
        ?>
            <a href="<?= htmlspecialchars(mu_url_midia($foto['arquivo'])) ?>" target="_blank">
                <img
                    src="<?= htmlspecialchars(mu_url_thumb($foto['arquivo'], 220)) ?>"
                    alt="Foto do trecho <?= htmlspecialchars($trecho['pv_montante'].' → '.$trecho['pv_jusante']) ?>"
                    loading="lazy" decoding="async"
                    onerror="this.onerror=null;this.src='<?= htmlspecialchars(mu_url_midia($foto['arquivo'])) ?>';"
                    style="height:100px;border-radius:6px;"
                >
            </a>
        <?php endforeach; ?>
    </div>

</div>

<?php endforeach; ?>

</main>
</div>

</body>
</html>
