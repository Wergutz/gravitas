<?php
// Garante que as variáveis existam
$currentRoute = $currentRoute ?? '';
if (!defined('APP_BASE')) require_once __DIR__ . '/../../../config/app.php';

// Helper inline para marcar nav item ativo
function navAtivo(string $route, string $prefix): string {
    return (strpos($route, $prefix) === 0) ? 'ativo' : '';
}
function navAtivoExato(string $route, string $exact): string {
    return ($route === $exact || $route === '') ? 'ativo' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? APP_NAME) ?> · <?= htmlspecialchars(APP_CLIENT) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/pa4.css">
</head>
<body>
<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="brand">
            <svg viewBox="-4 -4 108 108" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <ellipse cx="50" cy="50" rx="54" ry="19" fill="none" stroke="#B9C1CC" stroke-width="3.5" transform="rotate(-24 50 50)"/>
                <circle cx="50" cy="50" r="44" fill="#1A2D4F" stroke="#B9C1CC" stroke-width="2.5"/>
                <circle cx="92.8" cy="23.1" r="4.5" fill="#C9A227"/>
                <path d="M 26.7 73.3 A 33 33 0 1 1 73.3 73.3" fill="none" stroke="#FFFFFF" stroke-width="6" stroke-linecap="round"/>
                <path d="M 50 50 L 67.4 29.3 L 55.5 53.5 Z" fill="#C9A227"/>
                <circle cx="50" cy="50" r="6.5" fill="#C9A227"/>
            </svg>
            <div>
                <b>GRAVITAS</b>
                <small>PAINEL DE CONTROLE</small>
            </div>
        </div>

        <?php $nivelLayout = (int)($_SESSION['nivel'] ?? 0); ?>
        <nav class="nav">
            <?php if ($nivelLayout === 3): ?>

            <a href="<?= APP_BASE ?>/admin/usuarios" class="<?= navAtivo($currentRoute, '/admin') ?>">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Usuários &amp; Acessos
            </a>

            <?php else: ?>

            <a href="<?= APP_BASE ?>/" class="<?= ($currentRoute === '/' || $currentRoute === '') ? 'ativo' : '' ?>">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                Dashboard
            </a>

            <a href="<?= APP_BASE ?>/equipes" class="<?= navAtivo($currentRoute, '/equipes') ?>">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Equipes
            </a>

            <a href="<?= APP_BASE ?>/funcionarios" class="<?= navAtivo($currentRoute, '/funcionarios') ?>">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                Funcionários
            </a>

            <a href="<?= APP_BASE ?>/equipamentos-leves" class="<?= navAtivo($currentRoute, '/equipamentos-leves') ?>">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
                Equipamentos Leves
            </a>

            <a href="<?= APP_BASE ?>/equipamentos-pesados" class="<?= navAtivo($currentRoute, '/equipamentos-pesados') ?>">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="3" width="15" height="13"/>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                    <circle cx="5.5" cy="18.5" r="2.5"/>
                    <circle cx="18.5" cy="18.5" r="2.5"/>
                </svg>
                Equipamentos Pesados
            </a>

            <a href="<?= APP_BASE ?>/trechos" class="<?= navAtivo($currentRoute, '/trechos') ?>">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <polyline points="8 7 3 12 8 17"/>
                    <polyline points="16 7 21 12 16 17"/>
                </svg>
                Trechos &amp; OS
            </a>

            <a href="<?= APP_BASE ?>/materiais" class="<?= navAtivo($currentRoute, '/materiais') ?>">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="8" y1="6" x2="21" y2="6"/>
                    <line x1="8" y1="12" x2="21" y2="12"/>
                    <line x1="8" y1="18" x2="21" y2="18"/>
                    <line x1="3" y1="6" x2="3.01" y2="6"/>
                    <line x1="3" y1="12" x2="3.01" y2="12"/>
                    <line x1="3" y1="18" x2="3.01" y2="18"/>
                </svg>
                Materiais
            </a>

            <a href="<?= APP_BASE ?>/caminhamentos" class="<?= navAtivo($currentRoute, '/caminhamentos') ?>">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
                Caminhamentos
            </a>

            <a href="<?= APP_BASE ?>/repavimentacao" class="<?= navAtivo($currentRoute, '/repavimentacao') ?>">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                Repavimentação
            </a>

            <a href="<?= APP_BASE ?>/diarios" class="<?= navAtivo($currentRoute, '/diarios') ?>">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                Diários
            </a>

            <?php endif; ?>

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

    <!-- CONTEÚDO PRINCIPAL -->
    <main>

        <!-- Topo com título + perfil -->
        <div class="topo">
            <div>
                <h1><?= htmlspecialchars($pageTitle ?? '') ?></h1>
                <?php if (!empty($pageSubtitle)): ?>
                    <p><?= htmlspecialchars($pageSubtitle) ?></p>
                <?php endif; ?>
            </div>
            <?php
            $usuario = $_SESSION['user'] ?? null;
            if ($usuario):
                $iniciais = strtoupper(substr($usuario['nome'] ?? 'U', 0, 1));
            ?>
            <div class="perfil">
                <div class="avatar"><?= htmlspecialchars($iniciais) ?></div>
                <span><?= htmlspecialchars($usuario['nome'] ?? '') ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Flash messages -->
        <?php if (!empty($_SESSION['flash_ok'])): ?>
            <div class="flash flash-ok"><?= htmlspecialchars($_SESSION['flash_ok']) ?></div>
            <?php unset($_SESSION['flash_ok']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_erro'])): ?>
            <div class="flash flash-erro"><?= htmlspecialchars($_SESSION['flash_erro']) ?></div>
            <?php unset($_SESSION['flash_erro']); ?>
        <?php endif; ?>

        <!-- A VIEW É INJETADA AQUI -->
        <?= $content ?? '' ?>

    </main>

</div>
</body>
</html>
