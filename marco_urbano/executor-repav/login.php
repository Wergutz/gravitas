<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('MU_PAINEL');
    session_set_cookie_params(['path' => '/marco_urbano/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/app.php';

header('X-Robots-Tag: noindex, nofollow');

if (isset($_GET['sair'])) {
    session_unset();
    session_destroy();
    header('Location: /login/');
    exit;
}

if (isset($_SESSION['usuario_id']) && (int)($_SESSION['nivel'] ?? 0) === 7) {
    header('Location: ' . REPAV_BASE . '/');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$bloqueado = false;
if (!empty($_SESSION['login_tentativas']) && $_SESSION['login_tentativas'] >= 5) {
    $bloqueioAte = $_SESSION['login_bloqueio_ate'] ?? 0;
    if (time() < $bloqueioAte) {
        $bloqueado = true;
        $minutos = ceil(($bloqueioAte - time()) / 60);
        $erro = "Muitas tentativas. Aguarde {$minutos} minuto(s).";
    } else {
        unset($_SESSION['login_tentativas'], $_SESSION['login_bloqueio_ate']);
    }
}

$erro = $erro ?? '';
if (empty($erro) && ($_GET['msg'] ?? '') === 'acesso') {
    $erro = 'Acesso negado. Seu perfil não tem permissão para este sistema. Entre com as credenciais corretas.';
}

if (!$bloqueado && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenPost = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $tokenPost)) {
        $erro = 'Requisição inválida. Recarregue a página.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if ($email && $senha) {
            $stmt = $pdo->prepare("
                SELECT id, nome, email, senha, tipo_usuario
                FROM usuarios
                WHERE (email = ? OR nome = ?) AND ativo = 1 AND tipo_usuario = 7
                LIMIT 1
            ");
            $stmt->execute([$email, $email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                unset($_SESSION['login_tentativas'], $_SESSION['login_bloqueio_ate']);
                $_SESSION['usuario_id'] = (int) $usuario['id'];
                $_SESSION['nome']       = $usuario['nome'];
                $_SESSION['nivel']      = (int) $usuario['tipo_usuario'];
                session_regenerate_id(true);
                header('Location: ' . REPAV_BASE . '/');
                exit;
            } else {
                $_SESSION['login_tentativas'] = ($_SESSION['login_tentativas'] ?? 0) + 1;
                if ($_SESSION['login_tentativas'] >= 5) {
                    $_SESSION['login_bloqueio_ate'] = time() + 600;
                }
                $erro = 'Usuário ou senha incorretos.';
            }
        } else {
            $erro = 'Informe e-mail e senha.';
        }
    }
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1A2D4F">
<meta name="robots" content="noindex,nofollow">
<title>Entrar · Repavimentação · MARCO URBANO</title>
<style>
  :root{
    --navy:#1A2D4F; --navy-900:#11203B; --navy-500:#3A578A;
    --ink:#1E2738; --muted:#6B7686; --line:#E4E8EF;
    --gold:#E0A53D; --gold-600:#C68C28; --gold-ink:#3A2A06;
    --font:"Inter",-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:var(--font);min-height:100vh;display:flex;justify-content:center;
       background:#0d1828;color:var(--ink)}
  .phone{width:100%;max-width:430px;min-height:100vh;
       background:radial-gradient(120% 90% at 50% 0%, #21386180 0%, transparent 55%),
       linear-gradient(170deg,var(--navy),var(--navy-900));
       color:#fff;display:flex;flex-direction:column;padding:0 26px}
  @media(min-width:480px){
    body{align-items:flex-start;padding:18px 0}
    .phone{min-height:auto;height:calc(100vh - 36px);border-radius:30px;overflow:hidden;
       border:9px solid #0a1320;box-shadow:0 24px 70px rgba(0,0,0,.5)}
  }
  .marca{display:flex;flex-direction:column;align-items:center;margin-top:64px}
  .marca svg{width:72px;height:72px}
  .marca b{margin-top:14px;font-weight:800;letter-spacing:5px;font-size:21px}
  .marca small{font-size:10px;letter-spacing:4px;color:#9FB4D6;font-weight:700;margin-top:3px}
  .eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:11px;letter-spacing:2px;
       text-transform:uppercase;color:var(--gold);font-weight:700;justify-content:center;margin:30px 0 4px}
  .lead{text-align:center;color:#C7D2E5;font-size:13.5px;line-height:1.5;margin-bottom:26px}
  .card{background:#fff;color:var(--ink);border-radius:18px;padding:22px 20px;margin-top:auto;margin-bottom:34px}
  .field{margin-bottom:14px}
  .field label{display:block;font-size:12.5px;font-weight:700;margin-bottom:7px}
  .inp{position:relative}
  .inp .ic{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted)}
  .inp .ic svg{width:17px;height:17px}
  .inp input{width:100%;font-family:inherit;font-size:15px;padding:14px 14px 14px 42px;
       border:1px solid var(--line);border-radius:12px;outline:none}
  .inp input:focus{border-color:var(--navy-500);box-shadow:0 0 0 3px #3a578a26}
  .btn{width:100%;border:0;font-family:inherit;font-size:16px;font-weight:800;padding:15px;
       border-radius:12px;background:var(--gold);color:var(--gold-ink);display:flex;
       align-items:center;justify-content:center;gap:8px;margin-top:4px;cursor:pointer}
  .btn:hover{background:var(--gold-600)}
  .erro{background:#fee2e2;color:#b91c1c;border-radius:10px;padding:10px 14px;font-size:13px;margin-bottom:12px}
  .nota{font-size:11px;color:#8FA0BF;text-align:center;line-height:1.5;margin-bottom:18px}
</style>
</head>
<body>
<div class="phone">
  <div class="marca">
    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Marco Urbano Urbanizadora">
  <rect x="4" y="4" width="92" height="92" rx="26" fill="none" stroke="#3CB86A" stroke-width="3"/>
  <rect x="14" y="14" width="72" height="72" rx="19" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="50" cy="33" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="50" cy="67" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="33" cy="50" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="67" cy="50" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="50" cy="50" r="5" fill="#3CB86A"/>
</svg>
    <b>MARCO URBANO</b><small>REPAVIMENTAÇÃO</small>
  </div>

  <div class="eyebrow">&#9679; Executor de Repavimentação</div>
  <p class="lead">Acesso do responsável de equipe de asfalto.<br>Controle de área, volume e NF em um só lugar.</p>

  <div class="card">
    <?php if ($erro): ?>
    <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <div class="field">
        <label for="u">E-mail ou nome de usuário</label>
        <div class="inp">
          <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.9 3.1-7 7-7s7 3.1 7 7"/></svg></span>
          <input id="u" name="email" type="text" placeholder="repav@gravitas.net.br ou seu nome" autocomplete="username" required>
        </div>
      </div>

      <div class="field">
        <label for="s">Senha</label>
        <div class="inp">
          <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></span>
          <input id="s" name="senha" type="password" placeholder="••••••••" autocomplete="current-password" required>
        </div>
      </div>

      <button type="submit" class="btn">
        Entrar na frente de repavimentação
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="12" x2="20" y2="12"/><polyline points="13 5 20 12 13 19"/></svg>
      </button>
    </form>
  </div>

  <p class="nota">Ambiente seguro · acesso restrito ao perfil Executor de Repavimentação.</p>
  <p class="nota" style="margin-bottom:28px">
    <a href="/marco_urbano/painel/login.php" style="color:#9FB4D6;font-weight:700;text-decoration:none">
      ← Voltar ao menu principal
    </a>
  </p>
</div>
</body>
</html>
