<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('GM2_PAINEL');
    session_set_cookie_params(['path' => '/GM/GM2/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}


/* ===== SOMENTE USUÁRIO MASTER LOGADO ===== */
if (
    !isset($_SESSION['usuario_id'], $_SESSION['tipo_usuario']) ||
    $_SESSION['tipo_usuario'] != 1
) {
    header('Location: /GM/GM2/public/login.php');
    exit;
}

require_once __DIR__ . '/../app/config/master.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($_POST['senha_master'] === MASTER_PASSWORD) {
        $_SESSION['master_autenticado'] = true;
        header('Location: menu.php');
        exit;
    }

    $erro = 'Senha master incorreta';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Acesso MASTER</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f9fafb;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
}
.box {
    background: #fff;
    padding: 30px;
    border-radius: 6px;
    border: 1px solid #ddd;
    width: 320px;
}
h2 { margin-bottom: 10px; }
input {
    width: 100%;
    padding: 8px;
    margin-top: 8px;
}
button {
    margin-top: 15px;
    width: 100%;
    padding: 10px;
    background: #111827;
    color: #fff;
    border: none;
    cursor: pointer;
}
.error {
    color: #dc2626;
    margin-top: 10px;
}
</style>
</head>
<body>

<div class="box">
    <h2>Área MASTER</h2>
    <p>Informe a senha master</p>

    <form method="post">
        <input type="password" name="senha_master" required placeholder="Senha master">
        <button>Acessar</button>
    </form>

    <?php if (!empty($erro)): ?>
        <div class="error"><?= $erro ?></div>
    <?php endif; ?>
</div>

</body>
</html>
