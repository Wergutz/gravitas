<?php
function auth_required_master(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /principal/painel/login.php');
        exit;
    }

    if ((int)($_SESSION['nivel'] ?? 0) !== 6) {
        session_unset();
        session_destroy();
        header('Location: /principal/painel/login.php?msg=acesso');
        exit;
    }
}
