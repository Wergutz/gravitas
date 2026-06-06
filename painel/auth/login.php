<?php
// ============================================================
// Painel Gravitas — Processa o formulário de login (POST)
// ============================================================
define('PAINEL_GRAVITAS', true);
require __DIR__ . '/config.php';
gv_sessao_iniciar();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  header('Location: ' . GV_BASE);
  exit;
}

function gv_falha($msg) {
  $_SESSION['gv_erro'] = $msg;
  $_SESSION['gv_usuario_prev'] = trim((string)($_POST['usuario'] ?? ''));
  header('Location: ' . GV_BASE);
  exit;
}

// pequeno atraso fixo — dificulta força bruta e ataques de timing
usleep(500000);

// bloqueio por excesso de tentativas
if (!empty($_SESSION['gv_bloq_ate']) && time() < $_SESSION['gv_bloq_ate']) {
  $min = (int)ceil(($_SESSION['gv_bloq_ate'] - time()) / 60);
  gv_falha('Muitas tentativas. Aguarde ' . $min . ' minuto(s) e tente novamente.');
}

// token anti-CSRF
$csrf = (string)($_POST['csrf'] ?? '');
if (empty($_SESSION['gv_csrf']) || !hash_equals($_SESSION['gv_csrf'], $csrf)) {
  gv_falha('A página ficou aberta por muito tempo. Tente novamente.');
}

$usuario = strtolower(trim((string)($_POST['usuario'] ?? '')));
$senha   = (string)($_POST['senha'] ?? '');
$perfil  = (($_POST['perfil'] ?? '') === 'usuario') ? 'usuario' : 'empresa';

$ok = false;
if ($usuario !== '' && $senha !== '' && isset($GV_USUARIOS[$usuario])) {
  $ok = password_verify($senha, $GV_USUARIOS[$usuario]['hash']);
} else {
  // verificação "fantasma" para igualar o tempo de resposta
  password_verify($senha, '$2a$10$FBplN0O.oPY/8hai5HT8uOEcicwt18ABXXVHAwPUsW7JXf8Lc8sPW');
  $ok = false;
}

if (!$ok) {
  $_SESSION['gv_tentativas'] = (int)($_SESSION['gv_tentativas'] ?? 0) + 1;
  if ($_SESSION['gv_tentativas'] >= GV_MAX_TENTATIVAS) {
    $_SESSION['gv_bloq_ate'] = time() + GV_BLOQUEIO_SEG;
    $_SESSION['gv_tentativas'] = 0;
    gv_falha('Muitas tentativas. Acesso bloqueado por 10 minutos.');
  }
  gv_falha('Usuário ou senha incorretos.');
}

// ---------- login aprovado ----------
session_regenerate_id(true);
$_SESSION['gv_logado']  = true;
$_SESSION['gv_usuario'] = $usuario;
$_SESSION['gv_nome']    = $GV_USUARIOS[$usuario]['nome'];
$_SESSION['gv_perfil']  = $perfil;
$_SESSION['gv_ultimo']  = time();
unset($_SESSION['gv_tentativas'], $_SESSION['gv_bloq_ate'], $_SESSION['gv_csrf'],
      $_SESSION['gv_erro'], $_SESSION['gv_usuario_prev']);

// "Manter conectado" → cookie de 30 dias
if (!empty($_POST['manter'])) {
  $_SESSION['gv_persistente'] = true;
  setcookie(session_name(), session_id(), [
    'expires'  => time() + GV_COOKIE_PERSISTENTE,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
}

header('Location: ' . GV_BASE . 'app/');
exit;
