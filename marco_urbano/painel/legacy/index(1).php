<?php
session_start();
$erro = $_SESSION['erro'] ?? null;
unset($_SESSION['erro']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vision Hub | Login</title>

<!-- CSS NO PADRÃO ORIGINAL -->
<link rel="stylesheet" href="/visionhub/assets/css/login.css">
</head>
<body>

<header class="top-bar">
    <span class="menu">&#9776;</span>
</header>

<main class="login-screen">

    <form class="login-form" action="login.php" method="post" id="loginForm">

        <div class="logo">
            <img src="/visionhub/assets/img/farol4.png" alt="Vision Hub">
        </div>

        <div class="row">
            <span class="icon">👤</span>
            <input type="email" name="email" placeholder="LOGIN" required>
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

<script src="js/login.js"></script>
</body>
</html>
