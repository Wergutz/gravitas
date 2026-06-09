<?php
// ============================================================
// Alterar Senha — acessível a qualquer usuário logado
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_name('MU_PAINEL');
    session_set_cookie_params(['path' => '/marco_urbano/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

require_once __DIR__ . '/app/config/app.php';
require_once __DIR__ . '/app/config/database.php';

// Qualquer nivel autenticado pode acessar
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . APP_BASE . '/login.php');
    exit;
}

$userId = (int)$_SESSION['usuario_id'];
$nivel  = (int)($_SESSION['nivel'] ?? 0);
$nome   = $_SESSION['nome'] ?? 'Usuário';

// Destino após salvar
$destinos = [5 => EXECUTOR_BASE.'/', 6 => MASTER_BASE.'/', 7 => REPAV_BASE.'/'];
$voltar   = $destinos[$nivel] ?? APP_BASE.'/';

// ── CSRF inline ──────────────────────────────────────────
if (empty($_SESSION['csrf_altsenha'])) {
    $_SESSION['csrf_altsenha'] = bin2hex(random_bytes(32));
}

$erro = '';
$ok   = false;

// ── Processa POST ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf_altsenha'], $token)) {
        $erro = 'Requisição inválida. Recarregue e tente novamente.';
    } else {
        $senhaAtual   = $_POST['senha_atual']    ?? '';
        $novaSenha    = $_POST['nova_senha']      ?? '';
        $confirmacao  = $_POST['confirmar_senha'] ?? '';

        if (!$senhaAtual || !$novaSenha || !$confirmacao) {
            $erro = 'Preencha todos os campos.';
        } elseif ($novaSenha !== $confirmacao) {
            $erro = 'A nova senha e a confirmação não coincidem.';
        } elseif (strlen($novaSenha) < 6) {
            $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
        } else {
            $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ? AND ativo = 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($senhaAtual, $row['senha'])) {
                $erro = 'Senha atual incorreta.';
            } else {
                $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE usuarios SET senha = ?, force_password_change = 0 WHERE id = ?")
                    ->execute([$hash, $userId]);
                // Regenera token
                $_SESSION['csrf_altsenha'] = bin2hex(random_bytes(32));
                $ok = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Alterar Senha · MARCO URBANO</title>
<style>
:root{
  --navy:#1A2D4F; --navy-900:#11203B; --navy-500:#3A578A;
  --ink:#1E2738; --muted:#6B7686; --line:#E4E8EF; --bg:#F4F6FA;
  --gold:#E0A53D; --gold-600:#C68C28; --gold-ink:#3A2A06;
  --ok:#1F7A6E; --ok-bg:#2E9E8F14;
  --erro:#B23A2C; --erro-bg:#C0392B12; --erro-bd:#C0392B33;
  --font:"Inter",-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--font);background:var(--bg);color:var(--ink);
     -webkit-font-smoothing:antialiased;min-height:100vh;
     display:flex;align-items:center;justify-content:center;padding:24px}
svg{display:block}
.card{background:#fff;border:1px solid var(--line);border-radius:18px;
      padding:36px 38px;width:100%;max-width:420px;box-shadow:0 8px 40px #1a2d4f0d}
.brand{display:flex;align-items:center;gap:10px;margin-bottom:28px}
.brand svg{width:28px;height:28px}
.brand b{font-weight:800;letter-spacing:3px;font-size:14px}
h1{font-size:20px;font-weight:800;color:var(--navy);margin-bottom:4px}
.sub{font-size:13px;color:var(--muted);margin-bottom:24px}
.campo{margin-bottom:16px}
.campo label{display:block;font-size:12.5px;font-weight:600;margin-bottom:7px}
.wrap-input{position:relative}
.wrap-input input{width:100%;font-family:inherit;font-size:14px;color:var(--ink);
  padding:12px 50px 12px 14px;border:1px solid var(--line);border-radius:11px;background:#fff}
.wrap-input input:focus{outline:0;border-color:var(--navy-500);box-shadow:0 0 0 3px #3a578a26}
.tgl{position:absolute;right:12px;top:50%;transform:translateY(-50%);
     border:0;background:0;font-size:12px;font-weight:700;color:var(--navy-500);cursor:pointer;font-family:inherit}
.btn{width:100%;border:0;font-family:inherit;font-size:15px;font-weight:700;cursor:pointer;
     padding:13px;border-radius:11px;background:var(--gold);color:var(--gold-ink);margin-top:6px}
.btn:hover{background:var(--gold-600)}
.btn[disabled]{opacity:.6;cursor:wait}
.alerta{display:flex;gap:9px;align-items:flex-start;border-radius:10px;padding:12px 14px;
        font-size:13px;font-weight:600;line-height:1.45;margin-bottom:18px}
.a-erro{background:var(--erro-bg);border:1px solid var(--erro-bd);color:var(--erro)}
.a-ok{background:var(--ok-bg);border:1px solid #2e9e8f33;color:var(--ok)}
.alerta svg{width:16px;height:16px;flex:0 0 auto;margin-top:1px}
.voltar{display:block;text-align:center;margin-top:18px;font-size:13px;
        color:var(--navy-500);font-weight:600;text-decoration:none}
.voltar:hover{text-decoration:underline}
.req{font-size:11px;color:var(--muted);margin-top:5px}
</style>
</head>
<body>
<div class="card">
  <div class="brand">
    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Marco Urbano Urbanizadora">
  <rect x="4" y="4" width="92" height="92" rx="26" fill="none" stroke="#3CB86A" stroke-width="3"/>
  <rect x="14" y="14" width="72" height="72" rx="19" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="50" cy="33" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="50" cy="67" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="33" cy="50" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="67" cy="50" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="50" cy="50" r="5" fill="#3CB86A"/>
</svg>
    <b>MARCO URBANO</b>
  </div>

  <h1>Alterar senha</h1>
  <p class="sub">Usuário: <b><?= htmlspecialchars($nome) ?></b></p>

  <?php if ($ok): ?>
    <div class="alerta a-ok">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      Senha alterada com sucesso!
    </div>
    <a class="btn" href="<?= htmlspecialchars($voltar) ?>" style="display:block;text-align:center;text-decoration:none">Voltar ao sistema</a>
  <?php else: ?>

    <?php if ($erro): ?>
      <div class="alerta a-erro">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="16.5" x2="12.01" y2="16.5"/></svg>
        <?= htmlspecialchars($erro) ?>
      </div>
    <?php endif; ?>

    <form method="post" id="frm" novalidate>
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['csrf_altsenha']) ?>">

      <div class="campo">
        <label for="s-atual">Senha atual</label>
        <div class="wrap-input">
          <input type="password" id="s-atual" name="senha_atual" autocomplete="current-password" required placeholder="••••••••">
          <button type="button" class="tgl" onclick="tgl('s-atual',this)">Mostrar</button>
        </div>
      </div>

      <div class="campo">
        <label for="s-nova">Nova senha</label>
        <div class="wrap-input">
          <input type="password" id="s-nova" name="nova_senha" autocomplete="new-password" required placeholder="••••••••">
          <button type="button" class="tgl" onclick="tgl('s-nova',this)">Mostrar</button>
        </div>
        <p class="req">Mínimo 6 caracteres.</p>
      </div>

      <div class="campo">
        <label for="s-conf">Confirmar nova senha</label>
        <div class="wrap-input">
          <input type="password" id="s-conf" name="confirmar_senha" autocomplete="new-password" required placeholder="••••••••">
          <button type="button" class="tgl" onclick="tgl('s-conf',this)">Mostrar</button>
        </div>
      </div>

      <button class="btn" id="btn-salvar" type="submit">Salvar nova senha</button>
    </form>

    <a class="voltar" href="<?= htmlspecialchars($voltar) ?>">← Voltar sem alterar</a>

  <?php endif; ?>
</div>

<script>
function tgl(id, btn) {
  const i = document.getElementById(id);
  i.type = i.type === 'password' ? 'text' : 'password';
  btn.textContent = i.type === 'password' ? 'Mostrar' : 'Ocultar';
}
document.getElementById('frm')?.addEventListener('submit', function() {
  const b = document.getElementById('btn-salvar');
  b.disabled = true; b.textContent = 'Salvando…';
});
</script>
</body>
</html>
