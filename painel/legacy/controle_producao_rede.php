<?php
// public/controle_producao_rede.php
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
    <title>Vision Hub | Controle da Produção - Rede</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS GLOBAL -->
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
            <a href="planejamento_producao.php">🗓 Planejamento Produção</a>
            <a href="logout.php">🚪 Sair</a>
        </nav>
    </aside>

    <!-- CONTEÚDO -->
    <main class="content">

        <!-- TOPO -->
        <header class="topbar">
            <div class="title">
                <h1>Controle da Produção – Rede</h1>
                <span>
                    Acompanhamento diário da produção das equipes de rede
                </span>
            </div>

            <div class="right">
                <span class="managed">MANAGED BY GRAVITAS</span>
            </div>
        </header>

        <!-- RESUMO GERAL -->
        <section class="stats">

            <div class="card">
                <small>Equipes em Operação</small>
                <h2>2</h2>
            </div>

            <div class="card">
                <small>Produção Prevista (Semana)</small>
                <h2>320 m</h2>
            </div>

            <div class="card">
                <small>Produção Executada</small>
                <h2>185 m</h2>
            </div>

            <div class="card alert">
                <small>Status Geral</small>
                <p>⚠ 1 equipe com atraso</p>
            </div>

        </section>

        <!-- CONTROLE POR EQUIPE -->
        <section class="features">

            <!-- EQUIPE 01 -->
            <div class="feature-card">
                <h3>🌐 Rede 01</h3>
                <p><strong>Encarregado:</strong> João da Silva</p>
                <p><strong>Frente:</strong> Rua das Flores</p>
                <p><strong>Meta diária:</strong> 40 m</p>
                <p><strong>Produção hoje:</strong> 32 m</p>

                <div style="margin-top:10px;">
                    <small>Status:</small>
                    <span style="color:#ffc107;"> Em atenção</span>
                </div>
            </div>

            <!-- EQUIPE 02 -->
            <div class="feature-card">
                <h3>🌐 Rede 02</h3>
                <p><strong>Encarregado:</strong> Carlos Pereira</p>
                <p><strong>Frente:</strong> Av. Central</p>
                <p><strong>Meta diária:</strong> 45 m</p>
                <p><strong>Produção hoje:</strong> 50 m</p>

                <div style="margin-top:10px;">
                    <small>Status:</small>
                    <span style="color:#28a745;"> Dentro do esperado</span>
                </div>
            </div>

        </section>

        <!-- ALERTAS -->
        <div class="card alert" style="margin-top:30px;">
            <h3>Alertas Operacionais</h3>
            <ul style="margin-top:10px;">
                <li>Equipe Rede 01 abaixo da meta diária</li>
                <li>Possível atraso na entrega semanal se mantido o ritmo</li>
            </ul>
        </div>

    </main>
</div>

</body>
</html>
