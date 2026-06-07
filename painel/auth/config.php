<?php
// ============================================================
// Painel Gravitas — Configurações de autenticação
// NÃO compartilhe este arquivo. Senhas ficam apenas como hash.
// ============================================================
if (!defined('PAINEL_GRAVITAS')) {
  http_response_code(403);
  exit('Acesso negado.');
}

// Caminho público do painel no domínio (ajuste se mudar a pasta)
const GV_BASE = '/painel/';

// Sessão expira após 8h sem atividade
const GV_TEMPO_INATIVIDADE = 28800;

// Proteção contra força bruta
const GV_MAX_TENTATIVAS = 5;
const GV_BLOQUEIO_SEG   = 600; // 10 minutos

// "Manter conectado" — cookie válido por 30 dias
const GV_COOKIE_PERSISTENTE = 2592000;

// ------------------------------------------------------------
// Usuários autorizados.
// Para criar/trocar uma senha: entre no painel e acesse
// auth/gerar-hash.php — cole o hash gerado aqui.
// ------------------------------------------------------------
$GV_USUARIOS = [
  'gravitas' => [
    'nome'   => 'Administrador Gravitas',
    'perfil' => 'empresa',
    // senha provisória — TROCAR no primeiro acesso (ver DEPLOY-login-painel.md)
    'hash'   => '$2a$10$FBplN0O.oPY/8hai5HT8uOEcicwt18ABXXVHAwPUsW7JXf8Lc8sPW',
  ],
  // exemplo de segundo usuário:
  // 'nome.sobrenome' => [
  //   'nome'   => 'Nome Completo',
  //   'perfil' => 'usuario',
  //   'hash'   => '$2y$10$________________________________________',
  // ],
];

// ------------------------------------------------------------
// Inicia a sessão com cookie seguro (HttpOnly + SameSite)
// ------------------------------------------------------------
function gv_sessao_iniciar() {
  if (session_status() === PHP_SESSION_ACTIVE) {
    return;
  }
  session_name('GVPAINEL');
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}
