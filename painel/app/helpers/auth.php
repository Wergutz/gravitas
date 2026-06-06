<?php
if (!defined('APP_BASE')) require_once __DIR__ . '/../config/app.php';

function auth_required($niveis = []) {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . APP_BASE . '/login.php');
        exit;
    }

    if (empty($niveis)) return;

    $nivelUsuario = (int) ($_SESSION['nivel'] ?? 0);

    if (!in_array($nivelUsuario, $niveis, true)) {
        http_response_code(403);
        echo "Acesso negado";
        exit;
    }
}
