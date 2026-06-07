<?php
function auth_required_master(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /painel/login.php');
        exit;
    }

    if ((int)($_SESSION['nivel'] ?? 0) !== 6) {
        header('Location: /painel/login.php?msg=acesso');
        exit;
    }
}
