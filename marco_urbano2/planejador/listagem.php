<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/asset.php';
require_once __DIR__ . '/../app/config/database.php';

auth_required([3]);

$lista = $pdo->query("
    SELECT p.id, p.created_at, u.nome
    FROM planejamentos p
    JOIN usuarios u ON u.id = p.usuario_id
    ORDER BY p.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Listagem de Planejamentos</title>
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
        <a href="planejamento.php">🛣 Planejamento</a>
        <a href="listagem.php" class="active">📋 Listagem</a>
        <a href="/marco_urbano2/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content">

<div class="topbar">
    <h1>Listagem de Planejamentos</h1>
</div>

<div class="form-card">

<table class="table">
<thead>
<tr>
    <th>ID</th>
    <th>Usuário</th>
    <th>Data</th>
    <th>Ação</th>
</tr>
</thead>
<tbody>
<?php foreach ($lista as $p): ?>
<tr>
    <td><?= $p['id'] ?></td>
    <td><?= htmlspecialchars($p['nome']) ?></td>
    <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
    <td>
        <a class="btn-secondary" href="visualizar.php?id=<?= $p['id'] ?>">
            👁 Visualizar
        </a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</div>

</main>
</div>

</body>
</html>
