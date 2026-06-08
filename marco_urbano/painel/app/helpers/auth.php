<?php
if (!defined('APP_BASE')) require_once __DIR__ . '/../config/app.php';

function mu_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('MU_PAINEL');
        session_set_cookie_params(['path' => '/marco_urbano/painel/', 'samesite' => 'Lax', 'httponly' => true]);
        session_start();
    }
}

function auth_required($niveis = []) {
    mu_session_start();

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . APP_BASE . '/login.php');
        exit;
    }

    if (empty($niveis)) return;

    $nivel = (int)($_SESSION['nivel'] ?? 0);
    if (!in_array($nivel, $niveis, true)) {
        $_SESSION['flash_aviso'] = 'Acesso restrito. Você não tem permissão para esta área.';
        $destinos = [5 => EXECUTOR_BASE . '/', 6 => MASTER_BASE . '/', 7 => REPAV_BASE . '/'];
        // Se o nivel não tem destino mapeado, vai para login (evita loop)
        $destino = $destinos[$nivel] ?? (APP_BASE . '/login.php?msg=acesso');
        header('Location: ' . $destino);
        exit;
    }
}
