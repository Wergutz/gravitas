<?php
$currentRoute = $currentRoute ?? '';
if (!defined('APP_BASE')) require_once __DIR__ . '/../../../config/app.php';

function navAtivo(string $route, string $prefix): string {
    return (strpos($route, $prefix) === 0) ? 'ativo' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> · <?= htmlspecialchars(APP_CLIENT) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PAINEL_ASSETS ?>/css/pa4.css">
</head>
<body>
<div class="app">

    <aside class="sidebar">
        <div class="brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="32" height="32">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                <line x1="12" y1="3" x2="12" y2="8"/>
            </svg>
            <div>
                <b>TOPOGRAFIA</b>
                <small><?= htmlspecialchars(APP_CLIENT) ?></small>
            </div>
        </div>

        <nav class="nav">
            <a href="<?= APP_BASE ?>/topografia" class="<?= navAtivo($currentRoute, '/topografia') ?>">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    <line x1="12" y1="3" x2="12" y2="8"/>
                </svg>
                Topografia
            </a>

            <a href="<?= APP_BASE ?>/alterar-senha.php">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="8" cy="14" r="4.5"/><path d="M11.5 10.5 20 2m-3.5 1.5 3 3M14 8l2.5 2.5"/>
                </svg>
                Alterar Senha
            </a>

            <a href="<?= APP_BASE ?>/logout.php" class="sair">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Sair
            </a>
        </nav>
    </aside>

    <main>
        <div class="topo">
            <div>
                <h1><?= htmlspecialchars($pageTitle ?? '') ?></h1>
                <?php if (!empty($pageSubtitle)): ?>
                    <p><?= htmlspecialchars($pageSubtitle) ?></p>
                <?php endif; ?>
            </div>
            <?php $nomeLogado = $_SESSION['nome'] ?? null;
            if ($nomeLogado): $iniciais = strtoupper(substr($nomeLogado, 0, 1)); ?>
            <div class="perfil">
                <div class="avatar"><?= htmlspecialchars($iniciais) ?></div>
                <span><?= htmlspecialchars($nomeLogado) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($_SESSION['flash_ok'])): ?>
            <div class="flash flash-ok"><?= htmlspecialchars($_SESSION['flash_ok']) ?></div>
            <?php unset($_SESSION['flash_ok']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_erro'])): ?>
            <div class="flash flash-erro"><?= htmlspecialchars($_SESSION['flash_erro']) ?></div>
            <?php unset($_SESSION['flash_erro']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_aviso'])): ?>
            <div class="flash flash-aviso"><?= htmlspecialchars($_SESSION['flash_aviso']) ?></div>
            <?php unset($_SESSION['flash_aviso']); ?>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </main>
</div>
</body>
</html>
