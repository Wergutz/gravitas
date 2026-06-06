<?php

date_default_timezone_set('America/Sao_Paulo');
 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function auth_required(array $perfisPermitidos = [])
{
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: /visionhub/index.php");
        exit;
    }

    if ($perfisPermitidos && !in_array($_SESSION['tipo_usuario'], $perfisPermitidos)) {
        http_response_code(403);
        exit("Acesso negado.");
    }
}
