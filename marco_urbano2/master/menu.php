<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('MU2_PAINEL');
    session_set_cookie_params(['path' => '/marco_urbano2/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}


/* ===== PROTEÇÃO TOTAL MASTER ===== */
if (
    !isset($_SESSION['usuario_id'], $_SESSION['tipo_usuario']) ||
    $_SESSION['tipo_usuario'] != 1 ||
    empty($_SESSION['master_autenticado'])
) {
    header('Location: auth.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Menu MASTER</title>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 40px;
}
a {
    display: block;
    margin: 10px 0;
    font-weight: bold;
    text-decoration: none;
}
</style>
</head>
<body>

<h1>Menu MASTER</h1>

<a href="usuarios.php">👤 Gerenciar Usuários</a>
<a href="planejamentos.php">🗂 Gerenciar Planejamentos</a>
<a href="/marco_urbano2/planejador/relatorio.php">📄 Relatórios</a>

<hr>

<a href="logout_master.php">🚪 Sair do MENU MASTER</a>

</body>
</html>
