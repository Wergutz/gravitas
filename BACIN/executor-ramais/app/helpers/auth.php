<?php
if (!defined('RAMAIS_BASE')) require_once __DIR__ . '/../config/app.php';

/** Nível 9 = Executor de Ramais. Nível 1 (superadmin) também entra. */
function auth_required_ramais(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /login/');
        exit;
    }

    $nivel = (int)($_SESSION['nivel'] ?? 0);
    if ($nivel !== 9 && $nivel !== 1) {
        header('Location: /login/');
        exit;
    }
}
