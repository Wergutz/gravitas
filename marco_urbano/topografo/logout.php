<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('MU_TOPO');
    session_set_cookie_params(['path' => '/marco_urbano/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}
$_SESSION = [];
setcookie('MU_TOPO', '', ['expires' => time() - 86400, 'path' => '/marco_urbano/', 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
header('Location: /login/');
exit;
