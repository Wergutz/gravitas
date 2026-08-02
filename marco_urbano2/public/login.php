<?php
require_once __DIR__ . '/../app/helpers/asset.php';
if (session_status() === PHP_SESSION_NONE) {
    session_name('MU2_PAINEL');
    session_set_cookie_params(['path' => '/marco_urbano2/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}
$erro = $_SESSION['erro'] ?? null;
unset($_SESSION['erro']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VisionHub Locar | Login</title>

<link rel="stylesheet" href="<?= mu_asset('/assets/css/login.css') ?>">

</head>
<body>

<header class="top-bar">
    <span class="menu">&#9776;</span>
</header>

<main class="login-screen">

    <form class="login-form" action="process_login.php" method="post">

        <div class="logo">
        <img src="/marco_urbano2/assets/img/logo-marco-urbano.svg" alt="Marco Urbano">
        <h1>MARCO URBANO</h1>
        </div>

        <div class="row">
            <span class="icon">👤</span>
            <input type="text" name="usuario" placeholder="LOGIN" required>
        </div>

        <div class="row">
            <span class="icon">🔒</span>
            <input type="password" name="senha" placeholder="SENHA" required>
        </div>

        <?php if ($erro): ?>
            <div style="margin-top:10px; color:#ffb3b3; font-size:14px;">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn-login">
            → ENTRAR
        </button>

    </form>

    <footer class="footer-link">
        <a href="#">FALE CONOSCO</a>
    </footer>

</main>

</body>
</html>
