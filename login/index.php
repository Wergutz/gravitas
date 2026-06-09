<?php
// ============================================================
// Login Centralizado — Plataforma Gravitas
// ============================================================

$sistemas = [
    [
        'id'            => 'gravitas',
        'db'            => 'u278289683_vh_planeja',
        'user'          => 'u278289683_visionhub_2',
        'pass'          => 'geb91/RS',
        'session'       => 'PHPSESSID',
        'cookie_path'   => '/principal/',
        'label'         => 'GRAVITAS',
        'sub'           => 'Plataforma Principal',
        'destinos'      => [
            1 => '/principal/painel/',
            3 => '/principal/painel/',
            4 => '/principal/painel/',
            5 => '/principal/executor/',
            6 => '/principal/master/',
            7 => '/principal/executor-repav/',
        ],
        'alterar_senha' => '/principal/painel/alterar-senha.php',
    ],
    [
        'id'            => 'marco_urbano',
        'db'            => 'u278289683_marco_urbano',
        'user'          => 'u278289683_marco_urbano',
        'pass'          => 'geb91/RS',
        'session'       => 'MU_PAINEL',
        'cookie_path'   => '/marco_urbano/',
        'label'         => 'MARCO URBANO',
        'sub'           => 'Urbanizadora',
        'destinos'      => [
            1 => '/marco_urbano/painel/',
            3 => '/marco_urbano/painel/',
            4 => '/marco_urbano/painel/',
            5 => '/marco_urbano/executor/',
            6 => '/marco_urbano/master/',
            7 => '/marco_urbano/executor-repav/',
        ],
        'alterar_senha' => '/marco_urbano/painel/alterar-senha.php',
    ],
];

// Sessão GV_LOGIN: CSRF + rate limiting + estado do seletor
session_name('GV_LOGIN');
session_set_cookie_params(['path' => '/login/', 'samesite' => 'Lax', 'httponly' => true]);
session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// ── Sair do seletor ───────────────────────────────────────────────────────────
if (isset($_GET['sair'])) {
    unset($_SESSION['sa_ok'], $_SESSION['sa_id'], $_SESSION['sa_nome']);
    header('Location: /login/');
    exit;
}

// ── Passo 2: superadmin escolhe o sistema ────────────────────────────────────
if (!empty($_SESSION['sa_ok'])) {
    $sa_nome  = $_SESSION['sa_nome'] ?? 'Admin';
    $erro_sel = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sistema'])) {
        if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
            $erro_sel = 'Requisição inválida. Recarregue a página.';
        } else {
            foreach ($sistemas as $cfg) {
                if ($cfg['id'] === ($_POST['sistema'] ?? '')) {
                    $uid  = (int)$_SESSION['sa_id'];
                    $nome = $_SESSION['sa_nome'];
                    unset($_SESSION['sa_ok'], $_SESSION['sa_id'], $_SESSION['sa_nome']);
                    session_write_close();

                    session_name($cfg['session']);
                    session_set_cookie_params([
                        'path'     => $cfg['cookie_path'],
                        'samesite' => 'Lax',
                        'httponly' => true,
                    ]);
                    session_start();
                    session_regenerate_id(true);
                    $_SESSION['usuario_id'] = $uid;
                    $_SESSION['nome']       = $nome;
                    $_SESSION['nivel']      = 1;
                    session_write_close();

                    header('Location: ' . $cfg['destinos'][1]);
                    exit;
                }
            }
            $erro_sel = 'Sistema inválido.';
        }
    }
    ?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1A2D4F">
<meta name="robots" content="noindex,nofollow">
<title>Selecionar Sistema · Gravitas</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --navy:#1A2D4F;--navy-900:#11203B;--navy-500:#3A578A;
    --ink:#1E2738;--muted:#6B7686;--line:#E4E8EF;--bg:#F4F6FA;
    --gold:#E0A53D;--gold-600:#C68C28;
    --erro:#B23A2C;--erro-bg:#C0392B12;--erro-bd:#C0392B33;
    --green:#3CB86A;
    --font:"Inter",-apple-system,Segoe UI,Roboto,sans-serif;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:var(--font);background:var(--bg);color:var(--ink);
       display:flex;align-items:center;justify-content:center;
       min-height:100vh;-webkit-font-smoothing:antialiased}
  svg{display:block}
  .shell{width:100%;max-width:580px;padding:24px 16px}
  .logo-wrap{display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:32px}
  .logo-icon{width:42px;height:42px}
  .logo-name{font-size:20px;font-weight:800;letter-spacing:4px;color:var(--navy)}
  .logo-sep{width:1px;height:28px;background:var(--line)}
  .logo-sub{font-size:12px;color:var(--muted);letter-spacing:.4px}
  .card{background:#fff;border:1px solid var(--line);border-radius:18px;
        padding:32px 32px 28px;box-shadow:0 4px 24px #1a2d4f0a}
  .card-head{margin-bottom:26px;text-align:center}
  .badge{display:inline-flex;align-items:center;gap:6px;background:#e0a53d1a;color:var(--gold-600);
         border-radius:999px;padding:4px 12px;font-size:11.5px;font-weight:700;
         letter-spacing:.5px;margin-bottom:14px}
  .badge-dot{width:6px;height:6px;background:var(--gold);border-radius:99px}
  .card-head h1{font-size:21px;font-weight:800;letter-spacing:-.3px;margin-bottom:6px}
  .card-head p{color:var(--muted);font-size:14px}
  .alert{display:flex;gap:10px;align-items:flex-start;background:var(--erro-bg);
         border:1px solid var(--erro-bd);color:var(--erro);border-radius:10px;
         padding:11px 13px;font-size:13px;font-weight:600;margin-bottom:16px;line-height:1.4}
  .sistemas{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  @media(max-width:500px){.sistemas{grid-template-columns:1fr}}
  .sis-btn{border:2px solid var(--line);border-radius:14px;background:#fff;
           padding:22px 20px 18px;cursor:pointer;text-align:left;
           transition:border-color .15s,box-shadow .15s,transform .08s;
           display:flex;flex-direction:column;gap:8px;width:100%;font-family:inherit}
  .sis-btn:hover{border-color:var(--gold);box-shadow:0 6px 20px #e0a53d1e}
  .sis-btn:active{transform:translateY(1px)}
  .sis-icon{width:46px;height:46px;margin-bottom:4px}
  .sis-name{font-size:14.5px;font-weight:800;color:var(--navy);letter-spacing:.5px}
  .sis-sub{font-size:12px;color:var(--muted)}
  .sis-arrow{font-size:12px;color:var(--gold-600);font-weight:700;margin-top:6px}
  .actions{display:flex;justify-content:center;margin-top:20px}
  .sair-link{font-size:12.5px;color:var(--muted);text-decoration:none;font-weight:600}
  .sair-link:hover{color:var(--navy)}
  .footer{text-align:center;margin-top:18px;font-size:12px;color:#9AA3B2}
</style>
</head>
<body>
<div class="shell">

  <div class="logo-wrap">
    <svg class="logo-icon" viewBox="-4 -4 108 108" xmlns="http://www.w3.org/2000/svg" aria-label="Gravitas" role="img">
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
    <span class="logo-sep"></span>
    <div>
      <div class="logo-name">GRAVITAS</div>
      <div class="logo-sub">Plataforma de Gestão</div>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <div class="badge"><span class="badge-dot"></span>SUPER ADMIN</div>
      <h1>Selecione o sistema</h1>
      <p>Olá, <strong><?= htmlspecialchars($sa_nome, ENT_QUOTES, 'UTF-8') ?></strong>. Escolha o sistema que deseja acessar.</p>
    </div>

    <?php if ($erro_sel): ?>
    <div class="alert"><?= htmlspecialchars($erro_sel, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" action="/login/">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
      <div class="sistemas">

        <button type="submit" name="sistema" value="gravitas" class="sis-btn">
          <svg class="sis-icon" viewBox="-4 -4 108 108" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <ellipse cx="50" cy="50" rx="54" ry="19" fill="none" stroke="#B9C1CC" stroke-width="3.5" transform="rotate(-24 50 50)"/>
            <circle cx="50" cy="50" r="44" fill="#1A2D4F" stroke="#B9C1CC" stroke-width="2.5"/>
            <circle cx="92.8" cy="23.1" r="4.5" fill="#C9A227"/>
            <path d="M 26.7 73.3 A 33 33 0 1 1 73.3 73.3" fill="none" stroke="#FFFFFF" stroke-width="6" stroke-linecap="round"/>
            <path d="M 50 50 L 67.4 29.3 L 55.5 53.5 Z" fill="#C9A227"/>
            <circle cx="50" cy="50" r="6.5" fill="#C9A227"/>
          </svg>
          <div class="sis-name">GRAVITAS</div>
          <div class="sis-sub">Plataforma Principal</div>
          <div class="sis-arrow">Acessar →</div>
        </button>

        <button type="submit" name="sistema" value="marco_urbano" class="sis-btn">
          <svg class="sis-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <rect x="4" y="4" width="92" height="92" rx="26" fill="none" stroke="#3CB86A" stroke-width="3"/>
            <rect x="14" y="14" width="72" height="72" rx="19" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
            <circle cx="50" cy="33" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
            <circle cx="50" cy="67" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
            <circle cx="33" cy="50" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
            <circle cx="67" cy="50" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
            <circle cx="50" cy="50" r="5" fill="#3CB86A"/>
          </svg>
          <div class="sis-name">MARCO URBANO</div>
          <div class="sis-sub">Urbanizadora</div>
          <div class="sis-arrow">Acessar →</div>
        </button>

      </div>
    </form>

    <div class="actions">
      <a href="/login/?sair=1" class="sair-link">← Fazer login com outra conta</a>
    </div>
  </div>

  <p class="footer">Acesso restrito a usuários autorizados · © 2026 Gravitas</p>
</div>
</body>
</html>
<?php
    exit;
}

// ── Rate limiting ─────────────────────────────────────────────────────────────
if (!empty($_SESSION['tentativas']) && $_SESSION['tentativas'] >= 5) {
    $ate = $_SESSION['bloqueio_ate'] ?? 0;
    if (time() < $ate) {
        $bloqueado = true;
        $minutos   = ceil(($ate - time()) / 60);
        $erro      = "Muitas tentativas. Aguarde {$minutos} minuto(s) e tente novamente.";
    } else {
        unset($_SESSION['tentativas'], $_SESSION['bloqueio_ate']);
    }
}

// ── Autenticação ──────────────────────────────────────────────────────────────
if (!$bloqueado && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $erro = 'Requisição inválida. Recarregue a página e tente novamente.';
    } else {
        $login = trim($_POST['login'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if ($login && $senha) {
            $achou = false;

            foreach ($sistemas as $cfg) {
                try {
                    $pdo = new PDO(
                        "mysql:host=localhost;dbname={$cfg['db']};charset=utf8mb4",
                        $cfg['user'],
                        $cfg['pass'],
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                    $pdo->exec("SET time_zone = '-03:00'");

                    $stmt = $pdo->prepare("
                        SELECT id, nome, senha, tipo_usuario, force_password_change
                        FROM usuarios
                        WHERE (email = ? OR nome = ?) AND ativo = 1
                        LIMIT 1
                    ");
                    $stmt->execute([$login, $login]);
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($usuario && password_verify($senha, $usuario['senha'])) {
                        $achou = true;
                        $nivel = (int)$usuario['tipo_usuario'];

                        // Superadmin: guarda na sessão GV_LOGIN e mostra seletor
                        if ($nivel === 1) {
                            unset($_SESSION['tentativas'], $_SESSION['bloqueio_ate']);
                            $_SESSION['sa_ok']   = true;
                            $_SESSION['sa_id']   = (int)$usuario['id'];
                            $_SESSION['sa_nome'] = $usuario['nome'];
                            header('Location: /login/');
                            exit;
                        }

                        $dest = $cfg['destinos'][$nivel] ?? null;

                        if (!$dest) {
                            $erro = 'Usuário sem perfil de acesso configurado. Fale com o suporte.';
                            break;
                        }

                        unset($_SESSION['tentativas'], $_SESSION['bloqueio_ate']);
                        session_write_close();

                        session_name($cfg['session']);
                        session_set_cookie_params([
                            'path'     => $cfg['cookie_path'],
                            'samesite' => 'Lax',
                            'httponly' => true,
                        ]);
                        session_start();
                        session_regenerate_id(true);
                        $_SESSION['usuario_id'] = (int)$usuario['id'];
                        $_SESSION['nome']       = $usuario['nome'];
                        $_SESSION['nivel']      = $nivel;
                        session_write_close();

                        $redir = !empty($usuario['force_password_change'])
                            ? $cfg['alterar_senha']
                            : $dest;

                        header('Location: ' . $redir);
                        exit;
                    }
                } catch (PDOException $e) {
                    continue;
                }
            }

            if (!$achou && !$erro) {
                $_SESSION['tentativas'] = ($_SESSION['tentativas'] ?? 0) + 1;
                if ($_SESSION['tentativas'] >= 5) {
                    $_SESSION['bloqueio_ate'] = time() + 600;
                }
                $erro = 'Usuário ou senha incorretos.';
            }
        } else {
            $erro = 'Informe o usuário e a senha.';
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
<title>Entrar · Plataforma Gravitas</title>
<style>
  :root{
    --navy:#1A2D4F;--navy-900:#11203B;--navy-500:#3A578A;
    --ink:#1E2738;--muted:#6B7686;--line:#E4E8EF;--bg:#F4F6FA;
    --gold:#E0A53D;--gold-600:#C68C28;
    --erro:#B23A2C;--erro-bg:#C0392B12;--erro-bd:#C0392B33;
    --font:"Inter",-apple-system,Segoe UI,Roboto,sans-serif;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:var(--font);background:var(--bg);color:var(--ink);
       display:flex;align-items:center;justify-content:center;
       min-height:100vh;-webkit-font-smoothing:antialiased}
  svg{display:block}

  .shell{width:100%;max-width:440px;padding:24px 16px}

  .logo-wrap{display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:32px}
  .logo-icon{width:42px;height:42px}
  .logo-name{font-size:20px;font-weight:800;letter-spacing:4px;color:var(--navy)}
  .logo-sep{width:1px;height:28px;background:var(--line)}
  .logo-sub{font-size:12px;color:var(--muted);letter-spacing:.4px}

  .card{background:#fff;border:1px solid var(--line);border-radius:18px;
        padding:36px 36px 32px;box-shadow:0 4px 24px #1a2d4f0a}

  .card-head{margin-bottom:24px}
  .card-head h1{font-size:22px;font-weight:800;letter-spacing:-.3px;margin-bottom:6px}
  .card-head p{color:var(--muted);font-size:14px;line-height:1.5}

  .alert{display:flex;gap:10px;align-items:flex-start;background:var(--erro-bg);
         border:1px solid var(--erro-bd);color:var(--erro);border-radius:10px;
         padding:11px 13px;font-size:13.5px;font-weight:600;margin-bottom:18px;line-height:1.4}
  .alert svg{width:15px;height:15px;flex:0 0 auto;margin-top:1px}

  .field{margin-bottom:15px}
  .field label{display:block;font-size:13px;font-weight:600;margin-bottom:6px}
  .inp-wrap{position:relative}
  .inp-wrap input{width:100%;font-family:inherit;font-size:14.5px;color:var(--ink);
                  padding:12px 14px 12px 42px;border:1px solid var(--line);border-radius:10px;
                  background:#fff;transition:border-color .15s,box-shadow .15s}
  .inp-wrap input::placeholder{color:#A6AEBC}
  .inp-wrap input:focus{outline:0;border-color:var(--navy-500);box-shadow:0 0 0 3px #3a578a22}
  .inp-ic{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted)}
  .inp-ic svg{width:16px;height:16px}
  .show-btn{position:absolute;right:12px;top:50%;transform:translateY(-50%);
            font-size:12px;color:var(--navy-500);background:0;border:0;
            cursor:pointer;font-weight:600;font-family:inherit}

  .btn{width:100%;border:0;font-family:inherit;font-size:15px;font-weight:700;cursor:pointer;
       padding:14px;border-radius:10px;display:flex;align-items:center;justify-content:center;
       gap:8px;background:var(--gold);color:#3A2A06;margin-top:22px;
       transition:background .15s,transform .05s}
  .btn svg{width:17px;height:17px}
  .btn:hover{background:var(--gold-600);color:#fff}
  .btn:active{transform:translateY(1px)}
  .btn[disabled]{opacity:.6;cursor:wait}

  .footer{text-align:center;margin-top:20px;font-size:12px;color:#9AA3B2}

  @media(max-width:480px){
    .card{padding:28px 22px}
  }
</style>
</head>
<body>
<div class="shell">

  <div class="logo-wrap">
    <svg class="logo-icon" viewBox="-4 -4 108 108" xmlns="http://www.w3.org/2000/svg" aria-label="Gravitas" role="img">
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
    <span class="logo-sep"></span>
    <div>
      <div class="logo-name">GRAVITAS</div>
      <div class="logo-sub">Plataforma de Gestão</div>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <h1>Entrar na plataforma</h1>
      <p>Use as credenciais fornecidas pelo seu administrador. O sistema detecta automaticamente seu acesso.</p>
    </div>

    <?php if (!empty($erro)): ?>
    <div class="alert" role="alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
        <circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="16.5" x2="12" y2="16.5"/>
      </svg>
      <span><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php endif; ?>

    <form id="f" method="post" action="/login/" novalidate>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">

      <div class="field">
        <label for="login">E-mail ou usuário</label>
        <div class="inp-wrap">
          <span class="inp-ic" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
          </span>
          <input type="text" id="login" name="login" placeholder="seu@email.com ou nome de usuário"
                 autocomplete="username" required autofocus
                 value="<?= htmlspecialchars($_POST['login'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <div class="field">
        <label for="senha">Senha</label>
        <div class="inp-wrap">
          <span class="inp-ic" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>
            </svg>
          </span>
          <input type="password" id="senha" name="senha" placeholder="••••••••" autocomplete="current-password" required>
          <button type="button" class="show-btn" aria-label="Mostrar senha">Mostrar</button>
        </div>
      </div>

      <button class="btn" id="btn" type="submit">
        <span>Entrar</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <line x1="4" y1="12" x2="20" y2="12"/><polyline points="13 5 20 12 13 19"/>
        </svg>
      </button>
    </form>
  </div>

  <p class="footer">Acesso restrito a usuários autorizados · © 2026 Gravitas</p>
</div>

<script>
  var tgl = document.querySelector('.show-btn');
  if (tgl) {
    tgl.addEventListener('click', function() {
      var i = document.getElementById('senha');
      var show = i.type === 'password';
      i.type = show ? 'text' : 'password';
      tgl.textContent = show ? 'Ocultar' : 'Mostrar';
    });
  }
  document.getElementById('f').addEventListener('submit', function() {
    var b = document.getElementById('btn');
    b.disabled = true;
    b.querySelector('span').textContent = 'Verificando…';
  });
</script>
</body>
</html>
