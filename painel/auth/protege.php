<?php
// ============================================================
// Painel Gravitas — Proteção de página
// Inclua no TOPO de toda página restrita:
//   require __DIR__ . '/../auth/protege.php';
// ============================================================
if (!defined('PAINEL_GRAVITAS')) {
  define('PAINEL_GRAVITAS', true);
}
require_once __DIR__ . '/config.php';
gv_sessao_iniciar();

// não logado → volta para o login
if (empty($_SESSION['gv_logado'])) {
  header('Location: ' . GV_BASE);
  exit;
}

// sessão expirada por inatividade
if (time() - (int)($_SESSION['gv_ultimo'] ?? 0) > GV_TEMPO_INATIVIDADE) {
  $_SESSION = [];
  session_destroy();
  header('Location: ' . GV_BASE . '?expirou=1');
  exit;
}
$_SESSION['gv_ultimo'] = time();

// renova o cookie de quem marcou "manter conectado"
if (!empty($_SESSION['gv_persistente'])) {
  setcookie(session_name(), session_id(), [
    'expires'  => time() + GV_COOKIE_PERSISTENTE,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
}
