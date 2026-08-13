<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('GM_PAINEL');
    session_set_cookie_params(['path' => '/GM/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}
$_SESSION = [];
setcookie('GM_PAINEL', '', ['expires' => time() - 86400, 'path' => '/GM/', 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
header('Location: /login/');
exit;
