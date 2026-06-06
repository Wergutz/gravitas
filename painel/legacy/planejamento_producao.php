<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/helpers/auth.php';
auth_required([4]); // somente Planejador
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Vision Hub | Planejamento de Produção</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/visionhub/assets/css/planejador.css">
</head>
<body>

<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">
            <img src="/visionhub/assets/img/farol.png" alt="Vision Hub">
            <span>VISION HUB</span>
        </div>

        <nav>
            <a href="planejador.php">📊 Dashboard</a>
            <a href="logout.php">🚪 Sair</a>
        </nav>
    </aside>

    <!-- CONTEÚDO -->
    <main class="content">

        <!-- TOPO -->
        <header class="topbar">
            <div class="title">
                <h1>Planejamento de Produção</h1>
                <span>Selecione o tipo de novo planejamento ou gerencie equipes</span>
            </div>

            <div class="right">
                <span class="managed">MANAGED BY GRAVITAS</span>
            </div>
        </header>

        <!-- CARDS -->
        <section class="features">

            <a href="planejamento_rede.php" class="feature-card clickable">
                <h3>🌐 Novo Planejamento de Produção – Rede</h3>
                <p>
                    Crie um novo planejamento para atividades de escavação
                    e produção de redes.
                </p>
            </a>

            <a href="#" class="feature-card clickable">
                <h3>🛣 Novo Planejamento de Produção – Pavimentação</h3>
                <p>
                    Crie um novo planejamento para atividades de pavimentação,
                    asfalto, paralelepípedo ou bloco.
                </p>
            </a>

            <a href="#" class="feature-card clickable">
                <h3>🔧 Novo Planejamento de Produção – Retrabalho</h3>
                <p>
                    Crie um novo planejamento para correções,
                    ajustes e retrabalhos identificados.
                </p>
            </a>

            <a href="equipes.php" class="feature-card clickable">
                <h3>👥 Editar Equipes</h3>
                <p>
                    Gerencie a composição das equipes de produção,
                    recursos e funções.
                </p>
            </a>

        </section>

    </main>
</div>

</body>
</html>
