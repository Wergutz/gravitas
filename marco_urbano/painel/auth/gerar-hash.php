<?php
// ============================================================
// Painel Gravitas — Gerador de hash de senha
// Só funciona LOGADO. Use para criar/trocar senhas:
// 1. Digite a nova senha e clique em "Gerar hash"
// 2. Copie o hash e cole no auth/config.php (campo 'hash')
// ============================================================
require __DIR__ . '/protege.php';

$hash = '';
$erro = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  $nova = (string)($_POST['nova_senha'] ?? '');
  if (strlen($nova) < 8) {
    $erro = 'Use ao menos 8 caracteres.';
  } else {
    $hash = password_hash($nova, PASSWORD_BCRYPT);
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Gerar hash de senha · Painel Gravitas</title>
<style>
  body{font-family:"Inter",-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#F4F6FA;color:#1E2738;
       display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:24px}
  .box{background:#fff;border:1px solid #E4E8EF;border-radius:14px;padding:32px;max-width:560px;width:100%}
  h1{font-size:19px;color:#1A2D4F;margin:0 0 8px}
  p{font-size:13.5px;color:#6B7686;line-height:1.55;margin:0 0 18px}
  input{width:100%;box-sizing:border-box;font:inherit;font-size:14.5px;padding:12px 14px;
        border:1px solid #E4E8EF;border-radius:10px;margin-bottom:14px}
  input:focus{outline:0;border-color:#3A578A;box-shadow:0 0 0 3px #3a578a26}
  button{font:inherit;font-weight:700;font-size:14.5px;background:#E0A53D;color:#3A2A06;border:0;
         border-radius:10px;padding:12px 22px;cursor:pointer}
  button:hover{background:#C68C28}
  .erro{color:#B23A2C;font-size:13px;font-weight:600;margin:0 0 14px}
  code{display:block;background:#11203B;color:#9CC4EC;border-radius:10px;padding:16px;
       font-size:12.5px;word-break:break-all;margin-top:18px;user-select:all}
  .ok{margin-top:10px;font-size:12.5px;color:#1f7a6e}
  a{color:#3A578A;font-weight:600;font-size:13px}
</style>
</head>
<body>
  <div class="box">
    <h1>Gerar hash de nova senha</h1>
    <p>Digite a nova senha. Copie o hash gerado e cole no arquivo <strong>auth/config.php</strong>,
       no campo <strong>'hash'</strong> do usuário desejado. A senha em si não é gravada em lugar nenhum.</p>
    <?php if ($erro !== ''): ?><p class="erro"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
    <form method="post" autocomplete="off">
      <input type="text" name="nova_senha" placeholder="Nova senha (mín. 8 caracteres)" required minlength="8">
      <button type="submit">Gerar hash</button>
    </form>
    <?php if ($hash !== ''): ?>
      <code><?php echo htmlspecialchars($hash, ENT_QUOTES, 'UTF-8'); ?></code>
      <p class="ok">Hash gerado. Cole no config.php e salve — a senha nova vale no próximo login.</p>
    <?php endif; ?>
    <p style="margin-top:20px"><a href="../app/">← Voltar ao painel</a></p>
  </div>
</body>
</html>
