<?php
function auth_required(array $perfis) {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('MU2_PAINEL');
        session_set_cookie_params(['path' => '/marco_urbano2/', 'samesite' => 'Lax', 'httponly' => true]);
        session_start();
    }

    if (
        !isset($_SESSION['usuario_id'], $_SESSION['tipo_usuario']) ||
        !in_array($_SESSION['tipo_usuario'], $perfis)
    ) {
        header('Location: /marco_urbano2/public/login.php');
        exit;
    }
}
