<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([4]); // Planejador

if (empty($_GET['id'])) {
    die('Planejamento não informado.');
}

$planejamentoId = (int) $_GET['id'];

/* ===============================
   DADOS DO PLANEJAMENTO
   =============================== */
$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.data_criacao,
        p.status,
        e.nome AS equipe_nome,
        u.nome AS planejador_nome
    FROM planejamentos p
    JOIN equipes e ON e.id = p.equipe_id
    JOIN usuarios u ON u.id = p.planejador_id
    WHERE p.id = ?
");
$stmt->execute([$planejamentoId]);
$planejamento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$planejamento) {
    die('Planejamento não encontrado.');
}

/* ===============================
   DIAS DO PLANEJAMENTO
   =============================== */
$stmt = $pdo->prepare("
    SELECT *
    FROM planejamento_dias
    WHERE planejamento_id = ?
    ORDER BY data
");
$stmt->execute([$planejamentoId]);
$dias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Vision Hub | Detalhe do Planejamento</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="/visionhub/assets/css/planejador.css">
</head>

<body>

<div class="app">

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="logo">
        <img src="/visionhub/assets/img/farol.png">
        <span>VISION HUB</span>
    </div>

    <nav>
        <a href="/visionhub/planejamentos_ativos.php">⬅ Planejamentos Ativos</a>
        <a href="/visionhub/planejador.php">📊 Dashboard</a>
        <a href="/visionhub/logout.php">🚪 Sair</a>
    </nav>
</aside>

<!-- CONTEÚDO -->
<main class="content">

<div class="topbar">
    <div>
        <h1>Detalhe do Planejamento</h1>
        <span>Visualização completa do planejamento ativo</span>
    </div>
    <div class="managed">MANAGED BY GRAVITAS</div>
</div>

<!-- DADOS GERAIS -->
<div class="form-card">
    <h3>Informações Gerais</h3>

    <p><strong>Equipe:</strong> <?= htmlspecialchars($planejamento['equipe_nome']) ?></p>
    <p><strong>Planejador:</strong> <?= htmlspecialchars($planejamento['planejador_nome']) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($planejamento['status']) ?></p>
    <p><strong>Criado em:</strong> <?= date('d/m/Y H:i', strtotime($planejamento['data_criacao'])) ?></p>
</div>

<!-- DIAS E TRECHOS -->
<?php foreach ($dias as $dia): ?>

<div class="form-card" style="margin-top:25px;">
    <h3>
        <?= date('d/m/Y', strtotime($dia['data'])) ?> —
        <?= strtoupper($dia['dia_semana']) ?>
    </h3>

    <?php
    $stmt = $pdo->prepare("
        SELECT *
        FROM planejamento_trechos
        WHERE dia_id = ?
    ");
    $stmt->execute([$dia['id']]);
    $trechos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if (empty($trechos)): ?>
        <p style="opacity:.7;">Nenhum trecho planejado.</p>
    <?php else: ?>

        <table class="table">
            <thead>
                <tr>
                    <th>PV Montante</th>
                    <th>PV Jusante</th>
                    <th>Comprimento (m)</th>
                    <th>Ramais</th>
                    <th>OS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trechos as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['pv_montante']) ?></td>
                    <td><?= htmlspecialchars($t['pv_juzante']) ?></td>
                    <td><?= number_format($t['comprimento'], 2, ',', '.') ?></td>
                    <td><?= (int)$t['ramais'] ?></td>
                    <td>
                        <?php if ($t['os_pdf']): ?>
                            <a href="/visionhub/uploads/<?= $t['os_pdf'] ?>" target="_blank">Ver OS</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>
</div>

<?php endforeach; ?>

</main>
</div>

</body>
</html>
