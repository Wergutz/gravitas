<?php
function auth_required_master(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /login/');
        exit;
    }

    $nivel = (int)($_SESSION['nivel'] ?? 0);
    if ($nivel !== 6 && $nivel !== 1) {
        header('Location: /login/');
        exit;
    }
}
