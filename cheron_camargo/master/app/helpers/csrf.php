<?php
function csrf_token_master(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input_master(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token_master()) . '">';
}
