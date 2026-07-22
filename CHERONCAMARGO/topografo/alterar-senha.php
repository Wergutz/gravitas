<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('CC_PAINEL');
    session_set_cookie_params(['path' => '/CHERONCAMARGO/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

require_once __DIR__ . '/app/config/app.php';
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /login/');
    exit;
}

$userId = (int)$_SESSION['usuario_id'];
$nome   = $_SESSION['nome'] ?? 'Usuário';
$voltar = APP_BASE . '/';

if (empty($_SESSION['csrf_altsenha'])) {
    $_SESSION['csrf_altsenha'] = bin2hex(random_bytes(32));
}

$erro = '';
$ok   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf_altsenha'], $token)) {
        $erro = 'Requisição inválida. Recarregue e tente novamente.';
    } else {
        $senhaAtual  = $_POST['senha_atual']    ?? '';
        $novaSenha   = $_POST['nova_senha']      ?? '';
        $confirmacao = $_POST['confirmar_senha'] ?? '';

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
<title>Alterar Senha · Topografia · <?= htmlspecialchars(APP_CLIENT) ?></title>
<link rel="stylesheet" href="<?= PAINEL_ASSETS ?>/css/pa4.css">
<style>
body{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px;background:#F4F6FA}
.card{background:#fff;border:1px solid #E4E8EF;border-radius:18px;padding:36px 38px;width:100%;max-width:420px;box-shadow:0 8px 40px #1a2d4f0d}
</style>
</head>
<body>
<div class="card">
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:28px">
    <svg viewBox="0 0 24 24" fill="none" stroke="#1A2D4F" stroke-width="2" width="28" height="28" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/><line x1="12" y1="3" x2="12" y2="8"/></svg>
    <b style="font-weight:800;letter-spacing:3px;font-size:14px">TOPOGRAFIA</b>
  </div>

  <h1 style="font-size:20px;font-weight:800;color:#1A2D4F;margin-bottom:4px">Alterar senha</h1>
  <p style="font-size:13px;color:#6B7686;margin-bottom:24px">Usuário: <b><?= htmlspecialchars($nome) ?></b></p>

  <?php if ($ok): ?>
    <div class="flash flash-ok">Senha alterada com sucesso!</div>
    <a class="btn" href="<?= htmlspecialchars($voltar) ?>" style="display:block;text-align:center;text-decoration:none;margin-top:12px">Voltar à Topografia</a>
  <?php else: ?>
    <?php if ($erro): ?>
      <div class="flash flash-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="post" id="frm" novalidate>
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['csrf_altsenha']) ?>">
      <div class="field" style="margin-bottom:14px">
        <label style="display:block;font-size:12.5px;font-weight:600;margin-bottom:7px">Senha atual</label>
        <input class="inp" type="password" name="senha_atual" autocomplete="current-password" required placeholder="••••••••" style="width:100%;font-size:14px;padding:11px 14px;border:1px solid #E4E8EF;border-radius:10px;font-family:inherit">
      </div>
      <div class="field" style="margin-bottom:14px">
        <label style="display:block;font-size:12.5px;font-weight:600;margin-bottom:7px">Nova senha</label>
        <input class="inp" type="password" name="nova_senha" autocomplete="new-password" required placeholder="••••••••" style="width:100%;font-size:14px;padding:11px 14px;border:1px solid #E4E8EF;border-radius:10px;font-family:inherit">
        <small style="font-size:11px;color:#6B7686">Mínimo 6 caracteres.</small>
      </div>
      <div class="field" style="margin-bottom:18px">
        <label style="display:block;font-size:12.5px;font-weight:600;margin-bottom:7px">Confirmar nova senha</label>
        <input class="inp" type="password" name="confirmar_senha" autocomplete="new-password" required placeholder="••••••••" style="width:100%;font-size:14px;padding:11px 14px;border:1px solid #E4E8EF;border-radius:10px;font-family:inherit">
      </div>
      <button class="btn" type="submit" id="btn-s" style="width:100%">Salvar nova senha</button>
    </form>
    <a href="<?= htmlspecialchars($voltar) ?>" style="display:block;text-align:center;margin-top:16px;font-size:13px;color:#3A578A;font-weight:600;text-decoration:none">← Voltar sem alterar</a>
  <?php endif; ?>
</div>
<script>
document.getElementById('frm')?.addEventListener('submit',function(){
  var b=document.getElementById('btn-s'); b.disabled=true; b.textContent='Salvando…';
});
</script>
</body>
</html>
