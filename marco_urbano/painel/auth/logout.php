<?php
// ============================================================
// Painel Gravitas — Encerra a sessão
// ============================================================
define('PAINEL_MARCO_URBANO', true);
require __DIR__ . '/config.php';
mu_sessao_iniciar();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
  setcookie(session_name(), '', [
    'expires'  => time() - 86400,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
}
session_destroy();

header('Location: ' . MU_BASE);
exit;
