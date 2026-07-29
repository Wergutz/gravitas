<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('MU2_PAINEL');
    session_set_cookie_params(['path' => '/marco_urbano2/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

unset($_SESSION['master_autenticado']);
header('Location: /marco_urbano2/planejador/menu.php');
exit;
