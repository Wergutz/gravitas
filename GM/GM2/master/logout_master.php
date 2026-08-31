<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('GM2_PAINEL');
    session_set_cookie_params(['path' => '/GM/GM2/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

unset($_SESSION['master_autenticado']);
header('Location: /GM/GM2/planejador/menu.php');
exit;
