<?php
session_start();

require_once __DIR__ . '/app/helpers/auth.php';
auth_required([2]); // Administrador
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Vision Hub | Administração do Sistema</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="/visionhub/assets/css/planejador.css">
</head>

<body>

<div class="app">

    <!-- SIDEBAR ADMIN -->
    <aside class="sidebar">
        <div class="logo">
            <img src="/visionhub/assets/img/farol.png" alt="Vision Hub">
            <span>VISION HUB</span>
        </div>

        <nav>

            <a href="/visionhub/admin.php" class="active">
                <span class="vh-icon">🛠️</span>
                <span class="vh-label">Administração</span>
            </a>

            <a href="/visionhub/admin_usuarios.php">
                <span class="vh-icon">👥</span>
                <span class="vh-label">Usuários</span>
            </a>

            <a href="/visionhub/admin_perfis.php">
                <span class="vh-icon">🔐</span>
                <span class="vh-label">Perfis & Permissões</span>
            </a>

            <a href="/visionhub/admin_logs.php">
                <span class="vh-icon">📜</span>
                <span class="vh-label">Logs do Sistema</span>
            </a>

            <a href="/visionhub/admin_config.php">
                <span class="vh-icon">⚙️</span>
                <span class="vh-label">Configurações</span>
            </a>

            <a href="/visionhub/logout.php">
                <span class="vh-icon">🚪</span>
                <span class="vh-label">Sair</span>
            </a>

        </nav>
    </aside>

    <!-- CONTEÚDO -->
    <main class="content">

        <!-- TOPBAR -->
        <div class="topbar">
            <div>
                <h1>Administração do Sistema</h1>
                <span>Controle geral do Vision Hub</span>
            </div>
            <div class="managed">MANAGED BY GRAVITAS</div>
        </div>

        <!-- KPIs ADMIN -->
        <div class="dashboard-kpis">

            <div class="card">
                <strong>Usuários Ativos</strong>
                <h2 style="margin-top:10px;">—</h2>
            </div>

            <div class="card">
                <strong>Perfis Cadastrados</strong>
                <h2 style="margin-top:10px;">—</h2>
            </div>

            <div class="card" style="border-left:4px solid #ffc107;">
                <strong>Alertas do Sistema</strong>
                <p style="margin-top:10px;">Nenhum alerta crítico</p>
            </div>

        </div>

        <!-- AÇÕES ADMIN -->
        <div class="dashboard-actions">

            <a href="/visionhub/admin_usuarios.php" class="feature-card">
                <h3>Gerenciar Usuários</h3>
                <p>
                    Criar, editar, ativar ou desativar usuários do sistema.
                </p>
            </a>

            <a href="/visionhub/admin_perfis.php" class="feature-card">
                <h3>Perfis e Permissões</h3>
                <p>
                    Definir níveis de acesso e permissões por perfil.
                </p>
            </a>

            <!-- 🔹 NOVO BOTÃO ADICIONADO -->
            <a href="/visionhub/vincular_usuario_equipe.php" class="feature-card">
                <h3>Vincular Usuários às Equipes</h3>
                <p>
                    Associar executores às equipes de trabalho e definir vínculos ativos.
                </p>
            </a>

            <a href="/visionhub/admin_logs.php" class="feature-card">
                <h3>Logs do Sistema</h3>
                <p>
                    Auditoria de acessos, ações e eventos críticos.
                </p>
            </a>

            <a href="/visionhub/admin_config.php" class="feature-card">
                <h3>Configurações Gerais</h3>
                <p>
                    Ajustes globais do Vision Hub.
                </p>
            </a>

        </div>

    </main>
</div>

</body>
</html>
