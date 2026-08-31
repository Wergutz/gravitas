<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/asset.php';
require_once __DIR__ . '/../app/config/database.php';

auth_required([3, 4]); // Planejador

/* ===============================
   BUSCA PLANEJAMENTOS
================================ */
$stmt = $pdo->query("
    SELECT 
        p.id,
        p.created_at,
        u.nome AS usuario
    FROM planejamentos p
    JOIN usuarios u ON u.id = p.usuario_id
    ORDER BY p.created_at DESC
");

$planejamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Selecionar Planejamento | GM SERVIÇOS</title>
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
        <?php if (eh_planejador()): ?>
        <a href="/GM/GM2/planejador/planejamento.php">🛣 Novo Planejamento</a>
        <?php endif; ?>
        <a href="#" class="active">📂 Selecionar Planejamento</a>
        <a href="/GM/GM2/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<!-- CONTEÚDO -->
<main class="content">

<div class="topbar">
    <div>
        <h1>Selecionar Planejamento</h1>
        <span>Escolha um planejamento existente</span>
    </div>
</div>

<div class="form-card">
    <h3>Planejamentos Cadastrados</h3>

    <?php if (empty($planejamentos)): ?>
        <p style="color:#9ca3af;">Nenhum planejamento encontrado.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Planejador</th>
                    <th>Data</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($planejamentos as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['usuario']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
                    <td>
                        <a 
                          href="planejamento_visualizar.php?id=<?= $p['id'] ?>" 
                          class="btn-secondary">
                          👁 Visualizar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</main>
</div>

</body>
</html>
