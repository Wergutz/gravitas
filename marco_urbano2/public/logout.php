<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('MU2_PAINEL');
    session_set_cookie_params(['path' => '/marco_urbano2/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

/* Remove todas as variáveis de sessão */
$_SESSION = [];

/* Destroi a sessão */
session_destroy();

/* Redireciona para o login */
header('Location: /marco_urbano2/public/login.php');
exit;
