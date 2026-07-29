<?php
function auth_required(array $perfis) {
    session_start();

    if (
        !isset($_SESSION['usuario_id'], $_SESSION['tipo_usuario']) ||
        !in_array($_SESSION['tipo_usuario'], $perfis)
    ) {
        header('Location: /visionhub_locar/public/login.php');
        exit;
    }
}
