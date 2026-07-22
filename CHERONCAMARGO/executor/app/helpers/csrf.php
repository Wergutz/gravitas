<?php
function csrf_token_executor(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input_executor(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token_executor()) . '">';
}

function csrf_verify_executor(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Token inválido. Recarregue e tente novamente.');
    }
}
