<?php
if (!defined('APP_BASE')) require_once __DIR__ . '/../config/app.php';

function mu_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('CC_PAINEL');
        session_set_cookie_params(['path' => '/cheron_camargo/', 'samesite' => 'Lax', 'httponly' => true]);
        session_start();
    }
}

function auth_required($niveis = []) {
    mu_session_start();

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /login/');
        exit;
    }

    if (empty($niveis)) return;

    $nivel = (int)($_SESSION['nivel'] ?? 0);
    if ($nivel === 1) return; // superadmin: acesso total
    if (!in_array($nivel, $niveis, true)) {
        $_SESSION['flash_aviso'] = 'Acesso restrito. Você não tem permissão para esta área.';
        $destinos = [5 => EXECUTOR_BASE . '/', 6 => MASTER_BASE . '/', 7 => REPAV_BASE . '/'];
        $destino = $destinos[$nivel] ?? '/login/';
        header('Location: ' . $destino);
        exit;
    }
}
