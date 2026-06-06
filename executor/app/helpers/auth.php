<?php
if (!defined('EXECUTOR_BASE')) require_once __DIR__ . '/../config/app.php';

function auth_required_executor() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . EXECUTOR_BASE . '/login.php');
        exit;
    }

    if ((int)($_SESSION['nivel'] ?? 0) !== 5) {
        http_response_code(403);
        echo "Acesso restrito ao perfil Executor.";
        exit;
    }
}
