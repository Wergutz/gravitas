<?php
// ============================================================
// Painel Gravitas — Encerra a sessão
// ============================================================
define('PAINEL_GRAVITAS', true);
require __DIR__ . '/config.php';
gv_sessao_iniciar();

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

header('Location: ' . GV_BASE);
exit;
