<?php
// ============================================================
// Painel de Controle Gravitas — Login
// ============================================================

session_start();

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/app.php';

// Se já estiver logado, redireciona para o app correto pelo papel
if (isset($_SESSION['usuario_id'])) {
    $nivel = (int)($_SESSION['nivel'] ?? 0);
    if ($nivel === 5)      $dest = EXECUTOR_BASE . '/';
    elseif ($nivel === 6)  $dest = MASTER_BASE . '/';
    elseif ($nivel === 7)  $dest = REPAV_BASE . '/';
    else                   $dest = APP_BASE . '/';
    header('Location: ' . $dest);
    exit;
}

// ----- CSRF -----
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ----- Rate limiting (5 tentativas → bloquear 10 min) -----
$bloqueado = false;
if (!empty($_SESSION['login_tentativas']) && $_SESSION['login_tentativas'] >= 5) {
    $bloqueioAte = $_SESSION['login_bloqueio_ate'] ?? 0;
    if (time() < $bloqueioAte) {
        $bloqueado = true;
        $minutos = ceil(($bloqueioAte - time()) / 60);
        $erro = "Muitas tentativas. Aguarde {$minutos} minuto(s) e tente novamente.";
    } else {
        // bloqueio expirou — zera
        unset($_SESSION['login_tentativas'], $_SESSION['login_bloqueio_ate']);
    }
}

$erro = $erro ?? '';

// Mensagem de acesso negado vinda dos helpers de auth
if (empty($erro) && ($_GET['msg'] ?? '') === 'acesso') {
    $erro = 'Acesso negado. Seu perfil não tem permissão para este sistema. Entre com as credenciais corretas.';
}

// ----- Processa POST -----
if (!$bloqueado && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verifica CSRF
    $tokenPost = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $tokenPost)) {
        $erro = 'Requisição inválida. Recarregue a página e tente novamente.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if ($email && $senha) {
            $stmt = $pdo->prepare("
                SELECT id, nome, email, senha, tipo_usuario
                FROM usuarios
                WHERE email = ? AND ativo = 1
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                // Login bem-sucedido — zera tentativas
                unset($_SESSION['login_tentativas'], $_SESSION['login_bloqueio_ate']);
                $_SESSION['usuario_id'] = (int) $usuario['id'];
                $_SESSION['nome']       = $usuario['nome'];
                $_SESSION['nivel']      = (int) $usuario['tipo_usuario'];
                session_regenerate_id(true);
                $tipo = (int)$usuario['tipo_usuario'];
                if ($tipo === 5)      $dest = EXECUTOR_BASE . '/';
                elseif ($tipo === 6)  $dest = MASTER_BASE . '/';
                elseif ($tipo === 7)  $dest = REPAV_BASE . '/';
                else                  $dest = APP_BASE . '/';
                header('Location: ' . $dest);
                exit;
            } else {
                // Falha — incrementa tentativas
                $_SESSION['login_tentativas'] = ($_SESSION['login_tentativas'] ?? 0) + 1;
                if ($_SESSION['login_tentativas'] >= 5) {
                    $_SESSION['login_bloqueio_ate'] = time() + 600; // 10 min
                }
                $erro = 'Usuário ou senha incorretos.';
            }
        } else {
            $erro = 'Informe e-mail e senha.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1A2D4F">
<meta name="robots" content="noindex,nofollow">
<title>Painel de Controle · GRAVITAS</title>
<style>
  :root{
    --navy:#1A2D4F; --navy-900:#11203B; --navy-700:#27406A; --navy-500:#3A578A;
    --ink:#1E2738; --muted:#6B7686; --line:#E4E8EF; --surface:#FFFFFF; --bg:#F4F6FA;
    --gold:#E0A53D; --gold-600:#C68C28;
    --erro:#B23A2C; --erro-bg:#C0392B12; --erro-bd:#C0392B33;
    --font:"Inter",-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:var(--font);background:var(--bg);color:var(--ink);
       -webkit-font-smoothing:antialiased;min-height:100vh}
  a{color:inherit}
  svg{display:block}
  .shell{display:grid;grid-template-columns:1.05fr .95fr;min-height:100vh}

  .brandside{position:relative;background:
      radial-gradient(120% 120% at 0% 0%, #21386180 0%, transparent 55%),
      linear-gradient(160deg,var(--navy) 0%,var(--navy-900) 100%);
      color:#fff;padding:52px 56px;display:flex;flex-direction:column;overflow:hidden}
  .brandside::after{content:"";position:absolute;inset:0;
      background-image:linear-gradient(#ffffff10 1px,transparent 1px),linear-gradient(90deg,#ffffff10 1px,transparent 1px);
      background-size:44px 44px;mask-image:radial-gradient(70% 70% at 70% 30%,#000 0%,transparent 75%);opacity:.5;pointer-events:none}
  .brand-top{display:flex;align-items:center;gap:14px;position:relative;z-index:1}
  .brand-mark{width:34px;height:34px;flex:0 0 auto}
  .brand-name{font-weight:800;letter-spacing:4px;font-size:19px}
  .brand-divider{width:1px;height:26px;background:#ffffff33}
  .brand-sub{font-size:12px;letter-spacing:.5px;color:#C7D2E5;max-width:190px;line-height:1.35}

  .brand-body{margin-top:auto;position:relative;z-index:1}
  .eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:12px;letter-spacing:2px;
      text-transform:uppercase;color:var(--gold);font-weight:600;margin-bottom:18px}
  .eyebrow::before{content:"";width:22px;height:2px;background:var(--gold)}
  .brand-title{font-size:40px;line-height:1.08;font-weight:800;letter-spacing:-.5px}
  .brand-title span{color:#9FB4D6;font-weight:600}
  .brand-text{margin-top:16px;color:#C7D2E5;font-size:15px;line-height:1.6;max-width:430px}

  .modules{margin-top:34px;display:flex;flex-direction:column;gap:12px;max-width:440px}
  .mod{display:flex;align-items:center;gap:14px;background:#ffffff0d;border:1px solid #ffffff1f;
      border-radius:12px;padding:14px 16px;backdrop-filter:blur(2px)}
  .mod-ic{width:40px;height:40px;border-radius:10px;display:grid;place-items:center;flex:0 0 auto}
  .mod-ic svg{width:20px;height:20px}
  .mod-app{background:#3e86c926;color:#9CC4EC}
  .mod-plan{background:#2e9e8f26;color:#86D6CA}
  .mod-ctrl{background:#c9853d26;color:#EBBE85}
  .mod h4{font-size:14.5px;font-weight:700}
  .mod p{font-size:12.5px;color:#A9B7D1;margin-top:2px}

  .brand-foot{margin-top:34px;font-size:12px;color:#8FA0BF;position:relative;z-index:1}

  .loginside{display:flex;align-items:center;justify-content:center;padding:40px 32px}
  .card{width:100%;max-width:400px}
  .card-head{margin-bottom:26px}
  .badge{display:inline-flex;align-items:center;gap:8px;font-size:11.5px;letter-spacing:1.5px;
      text-transform:uppercase;color:var(--navy);background:#1a2d4f0f;border:1px solid #1a2d4f1a;
      padding:6px 12px;border-radius:999px;font-weight:600}
  .badge svg{width:13px;height:13px}
  .card h1{font-size:25px;font-weight:800;margin:16px 0 6px;letter-spacing:-.3px}
  .card .lede{color:var(--muted);font-size:14px;line-height:1.5}

  .alert{display:flex;gap:10px;align-items:flex-start;background:var(--erro-bg);
      border:1px solid var(--erro-bd);color:var(--erro);border-radius:11px;
      padding:12px 14px;font-size:13.5px;line-height:1.45;font-weight:600;margin-bottom:18px}
  .alert svg{width:16px;height:16px;flex:0 0 auto;margin-top:1px}

  .field{margin-bottom:16px}
  .field label{display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:7px}
  .input{position:relative}
  .input input{width:100%;font-family:inherit;font-size:14.5px;color:var(--ink);
      padding:13px 14px 13px 42px;border:1px solid var(--line);border-radius:11px;background:#fff;
      transition:border-color .15s,box-shadow .15s}
  .input input::placeholder{color:#A6AEBC}
  .input input:focus{outline:0;border-color:var(--navy-500);box-shadow:0 0 0 3px #3a578a26}
  .input .ic{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted)}
  .input .ic svg{width:16px;height:16px}
  .input .toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:12px;
      color:var(--navy-500);background:0;border:0;cursor:pointer;font-weight:600;font-family:inherit}

  .row{display:flex;align-items:center;justify-content:space-between;margin:4px 0 22px;font-size:13px}
  .check{display:flex;align-items:center;gap:8px;color:var(--ink);cursor:pointer}
  .check input{width:16px;height:16px;accent-color:var(--navy)}
  .link{color:var(--navy-500);font-weight:600;text-decoration:none}
  .link:hover{text-decoration:underline}

  .btn{width:100%;border:0;font-family:inherit;font-size:15px;font-weight:700;cursor:pointer;
      padding:14px;border-radius:11px;display:flex;align-items:center;justify-content:center;gap:8px;
      background:var(--gold);color:#3A2A06;transition:background .15s,transform .05s}
  .btn svg{width:17px;height:17px}
  .btn:hover{background:var(--gold-600);color:#fff}
  .btn:active{transform:translateY(1px)}
  .btn[disabled]{opacity:.65;cursor:wait}

  .help{margin-top:26px;text-align:center;font-size:13px;color:var(--muted)}
  .help a{color:var(--navy);font-weight:600;text-decoration:none}
  .help a:hover{text-decoration:underline}
  .legal{margin-top:30px;text-align:center;font-size:11.5px;color:#9AA3B2;line-height:1.5}

  @media (max-width:880px){
    .shell{grid-template-columns:1fr}
    .brandside{padding:34px 30px 30px}
    .brand-body{margin-top:30px}
    .brand-title{font-size:30px}
    .modules{margin-top:24px}
    .brand-foot{display:none}
  }
</style>
</head>
<body>
<div class="shell">

  <aside class="brandside">
    <div class="brand-top">
      <svg class="brand-mark" viewBox="-4 -4 108 108" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Painel de Controle Gravitas">
        <ellipse cx="50" cy="50" rx="54" ry="19" fill="none" stroke="#B9C1CC" stroke-width="3.5" transform="rotate(-24 50 50)"/>
        <circle cx="50" cy="50" r="44" fill="#1A2D4F" stroke="#B9C1CC" stroke-width="2.5"/>
        <path d="M 7.2 76.9 A 54 19 -24 0 1 92.8 23.1" fill="none" stroke="#B9C1CC" stroke-width="3.5" opacity="0.55"/>
        <circle cx="92.8" cy="23.1" r="4.5" fill="#C9A227"/>
        <path d="M 26.7 73.3 A 33 33 0 1 1 73.3 73.3" fill="none" stroke="#FFFFFF" stroke-width="6" stroke-linecap="round"/>
        <g stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" opacity="0.85">
          <line x1="50" y1="17" x2="50" y2="24"/>
          <line x1="26.7" y1="26.7" x2="31.6" y2="31.6"/>
          <line x1="73.3" y1="26.7" x2="68.4" y2="31.6"/>
        </g>
        <path d="M 50 50 L 67.4 29.3 L 55.5 53.5 Z" fill="#C9A227"/>
        <circle cx="50" cy="50" r="6.5" fill="#C9A227"/>
        <circle cx="50" cy="50" r="3" fill="#1A2D4F"/>
      </svg>
      <span class="brand-name">GRAVITAS</span>
      <span class="brand-divider"></span>
      <span class="brand-sub">Suporte Técnico e Administrativo para Contratos</span>
    </div>

    <div class="brand-body">
      <span class="eyebrow">Painel de Controle</span>
      <h2 class="brand-title">Sua obra,<br><span>sob controle.</span></h2>
      <p class="brand-text">Acesse a operação da sua empresa em um só lugar: produção lançada do campo,
        planejamento do dia e controle de equipes, equipamentos e indicadores — em tempo real.</p>

      <div class="modules">
        <div class="mod">
          <div class="mod-ic mod-app" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2" width="10" height="20" rx="2.5"/><line x1="10.5" y1="18.5" x2="13.5" y2="18.5"/></svg>
          </div>
          <div><h4>App de Produção</h4><p>Coleta de campo, medição automática e RDO.</p></div>
        </div>
        <div class="mod">
          <div class="mod-ic mod-plan" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="17" rx="2.5"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="2.5" x2="8" y2="7"/><line x1="16" y1="2.5" x2="16" y2="7"/></svg>
          </div>
          <div><h4>Planejamento</h4><p>Programação diária de equipes e equipamentos.</p></div>
        </div>
        <div class="mod">
          <div class="mod-ic mod-ctrl" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="5" y1="20" x2="5" y2="12"/><line x1="12" y1="20" x2="12" y2="5"/><line x1="19" y1="20" x2="19" y2="9"/></svg>
          </div>
          <div><h4>Controle &amp; Indicadores</h4><p>Materiais, estoque e dashboards gerenciais.</p></div>
        </div>
      </div>
    </div>

    <div class="brand-foot">© 2026 Gravitas · Foco total na sua produção. Nós cuidamos do resto.</div>
  </aside>

  <main class="loginside">
    <div class="card">
      <div class="card-head">
        <span class="badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
          Acesso restrito
        </span>
        <h1>Entrar no painel</h1>
        <p class="lede">Use as credenciais fornecidas pela Gravitas para acessar os serviços da sua empresa.</p>
      </div>

      <?php if (!empty($erro)): ?>
      <div class="alert" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="16.5" x2="12" y2="16.5"/></svg>
        <span><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <?php endif; ?>

      <form id="form-login" method="post" action="<?= APP_BASE ?>/login.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <div class="field">
          <label for="email">E-mail ou usuário</label>
          <div class="input">
            <span class="ic" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3.5 7l8.5 6 8.5-6"/></svg>
            </span>
            <input type="email" id="email" name="email" placeholder="nome@empresa.com.br"
                   autocomplete="username" required autofocus
                   value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
          </div>
        </div>

        <div class="field">
          <label for="senha">Senha</label>
          <div class="input">
            <span class="ic" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="14" r="4.5"/><path d="M11.5 10.5 20 2m-3.5 1.5 3 3M14 8l2.5 2.5"/></svg>
            </span>
            <input type="password" id="senha" name="senha" placeholder="••••••••" autocomplete="current-password" required>
            <button type="button" class="toggle" aria-label="Mostrar senha">Mostrar</button>
          </div>
        </div>

        <div class="row">
          <span></span>
          <a class="link" href="https://wa.me/5551993311500?text=Ol%C3%A1%2C%20esqueci%20minha%20senha%20do%20Painel%20Gravitas.">Esqueci minha senha</a>
        </div>

        <button class="btn" id="btn-entrar" type="submit">
          <span>Acessar painel</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" y1="12" x2="20" y2="12"/><polyline points="13 5 20 12 13 19"/></svg>
        </button>
      </form>

      <p class="help">Ainda não tem acesso? <a href="https://wa.me/5551993311500">Falar com a Gravitas</a></p>
      <p class="legal">Ambiente seguro. Ao entrar você concorda com os Termos de Uso e a Política de Privacidade da Gravitas.</p>
    </div>
  </main>
</div>

<script>
  var tgl = document.querySelector('.toggle');
  if (tgl) {
    tgl.addEventListener('click', function () {
      var i = document.getElementById('senha');
      var mostrar = i.type === 'password';
      i.type = mostrar ? 'text' : 'password';
      tgl.textContent = mostrar ? 'Ocultar' : 'Mostrar';
      tgl.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
    });
  }
  document.getElementById('form-login').addEventListener('submit', function () {
    var b = document.getElementById('btn-entrar');
    b.disabled = true;
    b.querySelector('span').textContent = 'Verificando…';
  });
</script>
</body>
</html>
