<?php
// planejamentos_ativos.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([4]); // Planejador

$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.status,
        p.data_criacao,
        e.nome AS equipe_nome,
        u.nome AS planejador_nome,
        COUNT(DISTINCT d.id) AS total_dias,
        COUNT(t.id) AS total_trechos
    FROM planejamentos p
    JOIN equipes e ON e.id = p.equipe_id
    JOIN usuarios u ON u.id = p.planejador_id
    LEFT JOIN planejamento_dias d ON d.planejamento_id = p.id
    LEFT JOIN planejamento_trechos t ON t.dia_id = d.id
    WHERE p.status = 'ATIVO'
    GROUP BY p.id
    ORDER BY p.data_criacao DESC
");
$stmt->execute();
$planejamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Vision Hub | Planejamentos Ativos</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="/visionhub/assets/css/planejador.css">
</head>

<body>

<div class="app">

<aside class="sidebar">
    <div class="logo">
        <img src="/visionhub/assets/img/farol.png">
        <span>VISION HUB</span>
    </div>
    <nav>
        <a href="/visionhub/planejador.php">📊 Dashboard</a>
        <a href="/visionhub/planejamento_rede.php">🗓️ Planejamento Rede</a>
        <a href="/visionhub/planejamentos_ativos.php" class="active">📌 Planejamentos Ativos</a>
        <a href="/visionhub/planejamentos.php">📂 Todos os Planejamentos</a>
        <a href="/visionhub/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content">

<div class="topbar">
    <?php if (!empty($_SESSION['sucesso'])): ?>
    <script>
        alert("<?= $_SESSION['sucesso'] ?>");
    </script>
    <?php unset($_SESSION['sucesso']); ?>
<?php endif; ?>

    <div>
        <h1>Planejamentos Ativos</h1>
        <span>Todos os planejamentos em andamento</span>
    </div>
    <div class="managed">MANAGED BY GRAVITAS</div>
</div>

<div class="card">

<?php if (empty($planejamentos)): ?>
    <p>Nenhum planejamento ativo no momento.</p>
<?php else: ?>

<table class="table">
    <thead>
        <tr>
            <th>Equipe</th>
            <th>Planejador</th>
            <th>Criado em</th>
            <th>Dias</th>
            <th>Trechos</th>
            <th>Status</th>
            <th style="width:160px;">Ações</th>
        </tr>
    </thead>
    <tbody>

    <?php foreach ($planejamentos as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['equipe_nome']) ?></td>
            <td><?= htmlspecialchars($p['planejador_nome']) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($p['data_criacao'])) ?></td>
            <td><?= (int)$p['total_dias'] ?></td>
            <td><?= (int)$p['total_trechos'] ?></td>
            <td>
                <span class="badge badge-success">ATIVO</span>
            </td>
            <td>
                <div style="display:flex; gap:8px;">
                    <a href="/visionhub/planejamento_detalhe.php?id=<?= $p['id'] ?>"
                       class="btn-info btn-sm">
                        Abrir
                    </a>
                    <a href="/visionhub/encerrar_planejamento.php?id=<?= $p['id'] ?>"
                       class="btn-secondary btn-sm"
                       onclick="return confirm('Deseja realmente encerrar este planejamento?');">
                        Encerrar
                    </a>

                </div>
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
