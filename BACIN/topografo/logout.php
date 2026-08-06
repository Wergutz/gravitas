<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('BC_PAINEL');
    session_set_cookie_params(['path' => '/BACIN/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}
$_SESSION = [];
setcookie('BC_PAINEL', '', ['expires' => time() - 86400, 'path' => '/BACIN/', 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
header('Location: /login/');
exit;
