<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('GM2_PAINEL');
    session_set_cookie_params(['path' => '/GM/GM2/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

/* Remove todas as variáveis de sessão */
$_SESSION = [];

/* Destroi a sessão */
session_destroy();

/* Redireciona para o login */
header('Location: /GM/GM2/public/login.php');
exit;
