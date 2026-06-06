<?php
if (!defined('EXECUTOR_BASE')) require_once __DIR__ . '/../config/app.php';

function auth_required_executor(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . EXECUTOR_BASE . '/login.php');
        exit;
    }

    if ((int)($_SESSION['nivel'] ?? 0) !== 5) {
        session_unset();
        session_destroy();
        header('Location: ' . EXECUTOR_BASE . '/login.php?msg=acesso');
        exit;
    }
}
