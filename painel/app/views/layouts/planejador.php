<?php
// Garante que as variáveis existam (evita erro 500)
$currentRoute = $currentRoute ?? '';
if (!defined('APP_BASE')) require_once __DIR__ . '/../../../config/app.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?? APP_NAME ?> · <?= APP_CLIENT ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">

    <!-- CSS PRINCIPAL DO PLANEJADOR -->
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/planejador.css">
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/tema-gravitas.css">
</head>

<body>

<div class="app">

    <!-- ===============================
         SIDEBAR
         =============================== -->
    <aside class="sidebar">
        <div class="logo">
            <img src="<?= APP_BASE ?>/assets/img/logo-painel-gravitas-dark.svg" alt="<?= APP_CLIENT ?> — Painel de Controle" style="height:48px;width:auto">
            <span></span>
        </div>

        <nav>
            <a href="<?= APP_BASE ?>/"
               class="<?= ($currentRoute === '/' ? 'active' : '') ?>">
                <span class="vh-icon">📊</span>
                <span class="vh-label">Dashboard</span>
            </a>

            <a href="<?= APP_BASE ?>/equipes"
               class="<?= (strpos($currentRoute, '/equipes') === 0 ? 'active' : '') ?>">
                <span class="vh-icon">👷</span>
                <span class="vh-label">Equipes</span>
            </a>

            <a href="<?= APP_BASE ?>/funcionarios"
               class="<?= (strpos($currentRoute, '/funcionarios') === 0 ? 'active' : '') ?>">
                <span class="vh-icon">🧑‍🏭</span>
                <span class="vh-label">Funcionários</span>
            </a>

            <a href="<?= APP_BASE ?>/equipamentos-leves"
               class="<?= (strpos($currentRoute, '/equipamentos-leves') === 0 ? 'active' : '') ?>">
                <span class="vh-icon">🧰</span>
                <span class="vh-label">Equipamentos Leves</span>
            </a>

            <a href="<?= APP_BASE ?>/equipamentos-pesados"
               class="<?= (strpos($currentRoute, '/equipamentos-pesados') === 0 ? 'active' : '') ?>">
                <span class="vh-icon">🚜</span>
                <span class="vh-label">Equipamentos Pesados</span>
            </a>

            <a href="<?= APP_BASE ?>/planejamentos"
               class="<?= ($currentRoute === '/planejamentos') ? 'active' : '' ?>">
               📋 Planejamentos
            </a>

            <a href="<?= APP_BASE ?>/logout.php">
                <span class="vh-icon">🚪</span>
                <span class="vh-label">Sair</span>
            </a>
        </nav>
    </aside>

    <!-- ===============================
         CONTEÚDO PRINCIPAL
         =============================== -->
    <main class="content">

        <div class="topbar">
            <div>
                <h1><?= $pageTitle ?? '' ?></h1>
                <span><?= $pageSubtitle ?? '' ?></span>
            </div>
            <div class="managed"><?= APP_CLIENT . ' · ' . APP_NAME ?></div>
        </div>

        <!-- A VIEW É INJETADA AQUI -->
        <?= $content ?? '' ?>

    </main>

</div>

</body>
</html>
