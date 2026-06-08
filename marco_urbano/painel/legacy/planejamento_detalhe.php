<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([4]); // Planejador

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Planejamento inválido.');
}

/* ===============================
   PLANEJAMENTO
=============================== */
$stmt = $pdo->prepare("
    SELECT p.*, e.nome AS equipe_nome, u.nome AS planejador_nome
    FROM planejamentos p
    JOIN equipes e ON e.id = p.equipe_id
    JOIN usuarios u ON u.id = p.planejador_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$planejamento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$planejamento) {
    die('Planejamento não encontrado.');
}

/* ===============================
   DIAS
=============================== */
$stmt = $pdo->prepare("
    SELECT *
    FROM planejamento_dias
    WHERE planejamento_id = ?
    ORDER BY data
");
$stmt->execute([$id]);
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

<aside class="sidebar">
    <div class="logo">
        <img src="/visionhub/assets/img/farol.png">
        <span>VISION HUB</span>
    </div>
    <nav>
        <a href="/visionhub/planejamentos_ativos.php">⬅ Voltar</a>
        <a href="/visionhub/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content">

<div class="topbar">
    <div>
        <h1>Detalhe do Planejamento</h1>
        <span><?= htmlspecialchars($planejamento['equipe_nome']) ?></span>
    </div>
    <div class="managed">MANAGED BY GRAVITAS</div>
</div>

<div class="form-card">
    <strong>Equipe:</strong> <?= htmlspecialchars($planejamento['equipe_nome']) ?><br>
    <strong>Planejador:</strong> <?= htmlspecialchars($planejamento['planejador_nome']) ?><br>
    <strong>Status:</strong> <?= htmlspecialchars($planejamento['status']) ?><br>
    <strong>Criado em:</strong> <?= date('d/m/Y H:i', strtotime($planejamento['data_criacao'])) ?>
</div>

<?php foreach ($dias as $dia): ?>

<div class="card" style="margin-top:20px;">
    <h3><?= date('d/m/Y', strtotime($dia['data'])) ?> — <?= htmlspecialchars($dia['dia_semana']) ?></h3>

    <?php if (!$dia['producao']): ?>
        <p><strong>Sem produção:</strong> <?= htmlspecialchars($dia['motivo_sem_producao']) ?></p>
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
            <?php
            $stmt = $pdo->prepare("
                SELECT *
                FROM planejamento_trechos
                WHERE dia_id = ?
            ");
            $stmt->execute([$dia['id']]);
            $trechos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <?php foreach ($trechos as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['pv_montante']) ?></td>
                    <td><?= htmlspecialchars($t['pv_juzante']) ?></td>
                    <td><?= number_format($t['comprimento'], 2, ',', '.') ?></td>
                    <td><?= (int)$t['ramais'] ?></td>
                    <td>
                        <?php if ($t['os_pdf']): ?>
                            <a
                                href="/visionhub/uploads/os_trechos/<?= htmlspecialchars($t['os_pdf']) ?>"
                                target="_blank"
                                class="btn-info btn-sm">
                                📄 Abrir OS
                            </a>
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
