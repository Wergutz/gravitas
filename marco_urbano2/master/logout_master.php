<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['master_autenticado']);
header('Location: /visionhub_locar/planejador/menu.php');
exit;
