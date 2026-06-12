<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('PHPSESSID');
    session_set_cookie_params(['path' => '/principal/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}
$_SESSION = [];
setcookie('PHPSESSID', '', ['expires' => time() - 86400, 'path' => '/principal/', 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
header('Location: /login/');
exit;
