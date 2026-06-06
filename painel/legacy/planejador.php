<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([4]); // Planejador

/* ===============================
   KPIs DO DASHBOARD
   =============================== */

// Planejamentos ativos
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM planejamentos 
    WHERE status = 'ATIVO'
");
$total_planejamentos_ativos = (int)$stmt->fetchColumn();

// Equipes ativas
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM equipes 
    WHERE ativo = 1
");
$total_equipes_ativas = (int)$stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Vision Hub | Dashboard do Planejador</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="/visionhub/assets/css/planejador.css">
</head>

<body>

<div class="app">

    <aside class="sidebar">
        <div class="logo">
            <img src="/visionhub/assets/img/farol.png" alt="Vision Hub">
            <span>VISION HUB</span>
        </div>

        <nav>

            <a href="/visionhub/planejador.php" class="active">
                <span class="vh-icon">📊</span>
                <span class="vh-label">Dashboard</span>
            </a>

            <a href="/visionhub/planejamento_rede.php">
                <span class="vh-icon">🗓️</span>
                <span class="vh-label">Inserir novo planejamento</span>
            </a>

            <a href="/visionhub/planejamentos_ativos.php">
                <span class="vh-icon">📌</span>
                <span class="vh-label">Planejamentos ativos</span>
            </a>

            <a href="/visionhub/planejamentos.php">
                <span class="vh-icon">📂</span>
                <span class="vh-label">Todos os planejamentos</span>
            </a>

            <a href="/visionhub/equipes_rede_nova.php">
                <span class="vh-icon">👷</span>
                <span class="vh-label">Cadastrar equipes de Rede</span>
            </a>

            <a href="/visionhub/equipes_rede.php">
                <span class="vh-icon">✏️</span>
                <span class="vh-label">Editar equipes de Rede</span>
            </a>

            <a href="/visionhub/equipes_todas.php">
                <span class="vh-icon">👥</span>
                <span class="vh-label">Todas as Equipes</span>
            </a>

            <!-- 🔧 NOVOS BOTÕES – SEM ALTERAR PADRÃO -->
            <a href="/visionhub/equipamentos_pesados.php">
                <span class="vh-icon">🚜</span>
                <span class="vh-label">Equipamentos Pesados</span>
            </a>

            <a href="/visionhub/equipamentos_leves.php">
                <span class="vh-icon">🧰</span>
                <span class="vh-label">Equipamentos Leves</span>
            </a>

            <a href="/visionhub/perfil.php">
                <span class="vh-icon">👤</span>
                <span class="vh-label">Perfil</span>
            </a>

            <a href="/visionhub/logout.php">
                <span class="vh-icon">🚪</span>
                <span class="vh-label">Sair</span>
            </a>

        </nav>
    </aside>

    <main class="content">

        <div class="topbar">
            <div>
                <h1>Dashboard do Planejador de Redes</h1>
                <span>Visão geral da produção</span>
            </div>
            <div class="managed">MANAGED BY GRAVITAS</div>
        </div>

        <div class="dashboard">

            <div class="dashboard-kpis">

                <div class="card">
                    <strong>Planejamentos Ativos</strong>
                    <h2 style="margin-top:10px;">
                        <?= $total_planejamentos_ativos ?>
                    </h2>
                </div>

                <div class="card">
                    <strong>Equipes Ativas</strong>
                    <h2 style="margin-top:10px;">
                        <?= $total_equipes_ativas ?>
                    </h2>
                </div>

                <div class="card" style="border-left:4px solid #dc3545;">
                    <strong>Alertas</strong>
                    <p style="margin-top:10px;">Sem alertas críticos</p>
                </div>

            </div>

            <div class="dashboard-actions">

                <a href="/visionhub/planejamento_rede.php" class="feature-card">
                    <h3>Inserir novo planejamento</h3>
                    <p>Criar novo planejamento semanal.</p>
                </a>

                <a href="/visionhub/planejamentos_ativos.php" class="feature-card">
                    <h3>Planejamentos ativos</h3>
                    <p>Acompanhar planejamentos em andamento.</p>
                </a>

            </div>

        </div>

    </main>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get("salvo") === "1") {
        alert("Salvo com sucesso!!");
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>

</body>
</html>
