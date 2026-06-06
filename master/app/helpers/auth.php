<?php
function auth_required_master(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /principal/painel/login.php');
        exit;
    }
    if ((int)($_SESSION['nivel'] ?? 0) !== 6) {
        http_response_code(403);
        echo '<h2 style="font-family:sans-serif;padding:40px">Acesso restrito ao perfil Cliente Master.</h2>';
        exit;
    }
}
