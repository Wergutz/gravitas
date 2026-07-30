<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/database.php';

auth_required([3]); // Planejador

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
<title>Selecionar Planejamento | VisionHub Locar</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/marco_urbano2/assets/css/planejador.css?v=1">
</head>
<body>

<div class="app">

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="logo">
        <img src="/marco_urbano2/assets/img/logo-marco-urbano.svg" alt="Marco Urbano">
        <span>MARCO URBANO</span>
    </div>
    <nav>
        <a href="/marco_urbano2/planejador/menu.php">📊 Dashboard</a>
        <a href="/marco_urbano2/planejador/planejamento.php">🛣 Novo Planejamento</a>
        <a href="#" class="active">📂 Selecionar Planejamento</a>
        <a href="/marco_urbano2/public/logout.php">🚪 Sair</a>
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
