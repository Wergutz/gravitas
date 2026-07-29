<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('MU2_PAINEL');
    session_set_cookie_params(['path' => '/marco_urbano2/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

$base = '/marco_urbano2';

if (!isset($_SESSION['usuario_id'], $_SESSION['tipo_usuario'])) {
    header("Location: $base/public/login.php");
    exit;
}

switch ($_SESSION['tipo_usuario']) {

    case 1: // MASTER
        header("Location: $base/master/menu.php");
        exit;

    case 2: // PROPRIETÁRIO
        header("Location: $base/proprietario/menu.php");
        exit;

    case 3: // PLANEJADOR
        header("Location: $base/planejador/menu.php");
        exit;

    case 4: // EXECUTOR
        header("Location: $base/executor/menu.php");
        exit;

    default:
        session_destroy();
        header("Location: $base/public/login.php");
        exit;
}
