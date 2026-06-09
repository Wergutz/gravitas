<?php
if (!defined('REPAV_BASE')) require_once __DIR__ . '/../config/app.php';

function auth_required_repav(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . REPAV_BASE . '/login.php');
        exit;
    }

    $nivel = (int)($_SESSION['nivel'] ?? 0);
    if ($nivel !== 7 && $nivel !== 1) {
        header('Location: ' . REPAV_BASE . '/login.php?msg=acesso');
        exit;
    }
}
