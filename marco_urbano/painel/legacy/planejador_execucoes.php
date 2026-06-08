<?php
session_start();
require_once __DIR__ . '/app/config/database.php';
// auth_required([4]); // Planejador

/* ===============================
   BUSCAR EXECUÇÕES
   =============================== */
$sql = "
SELECT 
    e.id,
    e.metragem,
    e.ramais,
    e.latitude,
    e.longitude,
    e.data_execucao,

    u.nome AS executor_nome,
    eq.nome AS equipe_nome

FROM execucoes e
JOIN usuarios u ON u.id = e.executor_id
LEFT JOIN planejamentos p ON p.id = e.planejamento_id
LEFT JOIN equipes eq ON eq.id = p.equipe_id
ORDER BY e.data_execucao DESC
";

$execucoes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Vision Hub | Execuções</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="/visionhub/assets/css/planejador.css">

<style>
.execucao-card {
    margin-bottom: 24px;
}

.execucao-mapa {
    width: 100%;
    height: 220px;
    border-radius: 8px;
    border: 0;
}

.fotos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 10px;
}

.fotos-grid img {
    width: 100%;
    border-radius: 6px;
}
</style>
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
            <a href="/visionhub/planejador.php">
                <span class="vh-icon">📊</span>
                <span class="vh-label">Dashboard</span>
            </a>

            <a href="/visionhub/planejador_execucoes.php" class="active">
                <span class="vh-icon">🛠️</span>
                <span class="vh-label">Execuções</span>
            </a>

            <a href="/visionhub/logout.php">
                <span class="vh-icon">🚪</span>
                <span class="vh-label">Sair</span>
            </a>
        </nav>
    </aside>

    <!-- CONTEÚDO -->
    <main class="content">

        <div class="topbar">
            <div>
                <h1>Execuções Registradas</h1>
                <span>Produção realizada em campo</span>
            </div>
            <div class="managed">MANAGED BY GRAVITAS</div>
        </div>

        <?php if (empty($execucoes)): ?>
            <div class="form-card">
                Nenhuma execução registrada até o momento.
            </div>
        <?php endif; ?>

        <?php foreach ($execucoes as $e): ?>
            <div class="form-card execucao-card">

                <strong>Equipe:</strong> <?= htmlspecialchars($e['equipe_nome'] ?? '—') ?><br>
                <strong>Executor:</strong> <?= htmlspecialchars($e['executor_nome']) ?><br>
                <strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($e['data_execucao'])) ?><br>

                <hr style="margin:12px 0; opacity:0.3;">

                <strong>Metragem executada:</strong> <?= $e['metragem'] ?> m<br>
                <strong>Ramais:</strong> <?= $e['ramais'] ?><br>

                <?php if ($e['latitude'] && $e['longitude']): ?>
                    <iframe
                        class="execucao-mapa"
                        loading="lazy"
                        src="https://www.openstreetmap.org/export/embed.html?bbox=<?= $e['longitude']-0.002 ?>%2C<?= $e['latitude']-0.002 ?>%2C<?= $e['longitude']+0.002 ?>%2C<?= $e['latitude']+0.002 ?>&layer=mapnik&marker=<?= $e['latitude'] ?>%2C<?= $e['longitude'] ?>">
                    </iframe>
                <?php endif; ?>

                <?php
                $stmt = $pdo->prepare("
                    SELECT * FROM execucao_fotos
                    WHERE execucao_id = ?
                ");
                $stmt->execute([$e['id']]);
                $fotos = $stmt->fetchAll();
                ?>

                <?php if ($fotos): ?>
                    <h4 style="margin-top:15px;">Fotos da Execução</h4>
                    <div class="fotos-grid">
                        <?php foreach ($fotos as $f): ?>
                            <img src="/visionhub/uploads/execucoes/<?= strtolower($f['tipo']) ?>/<?= $f['arquivo'] ?>">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

    </main>
</div>

</body>
</html>
