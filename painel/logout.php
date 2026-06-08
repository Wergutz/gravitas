<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('GV_PAINEL');
    session_set_cookie_params(['path' => '/principal/painel/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}
require_once __DIR__ . '/app/config/app.php';
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', ['expires' => time()-86400,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
}
session_destroy();
header('Location: ' . APP_BASE . '/login.php');
exit;
