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
    <title>Vision Hub | Editar Equipes</title>
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
            <a href="planejamento_producao.php">⬅ Voltar</a>
            <a href="logout.php">🚪 Sair</a>
        </nav>
    </aside>

    <!-- CONTEÚDO -->
    <main class="content">

        <!-- CABEÇALHO -->
        <header class="topbar">
            <div class="title">
                <h1>Editar Equipes</h1>
                <span>
                    Gerencie a composição das equipes por categoria de atuação
                </span>
            </div>

            <div class="right">
                <span class="managed">MANAGED BY GRAVITAS</span>
            </div>
        </header>

        <!-- CARDS PRINCIPAIS -->
        <section class="features">

            <!-- EQUIPES DE REDE -->
            <a href="equipes_rede.php" class="feature-card clickable">
                <h3>🌐 Editar Equipes de Rede</h3>
                <p>
                    Gerencie equipes focadas em produção de rede:
                    adicionar ou remover membros, definir funções,
                    papéis e alocar recursos específicos.
                </p>
            </a>

            <!-- EQUIPES DE PAVIMENTAÇÃO -->
            <a href="equipes_pavimentacao.php" class="feature-card clickable">
                <h3>🛣 Editar Equipes de Pavimentação</h3>
                <p>
                    Gerencie equipes de pavimentação, asfalto,
                    paralelepípedo (PARAL/BLOCO) e frentes de obra,
                    com definição clara de responsabilidades.
                </p>
            </a>

            <!-- EQUIPES DE RETRABALHO -->
            <a href="equipes_retrabalho.php" class="feature-card clickable">
                <h3>🔧 Editar Equipes de Retrabalho</h3>
                <p>
                    Gerencie equipes dedicadas a correções,
                    ajustes e retrabalhos identificados durante
                    a execução ou fiscalização.
                </p>
            </a>

        </section>

    </main>
</div>

</body>
</html>
