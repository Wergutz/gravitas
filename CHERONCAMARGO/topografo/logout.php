<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('CC_PAINEL');
    session_set_cookie_params(['path' => '/CHERONCAMARGO/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}
$_SESSION = [];
setcookie('CC_PAINEL', '', ['expires' => time() - 86400, 'path' => '/CHERONCAMARGO/', 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
header('Location: /login/');
exit;
