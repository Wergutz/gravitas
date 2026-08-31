<?php

if (session_status() === PHP_SESSION_NONE) {
    session_name('GM2_PAINEL');
    session_set_cookie_params(['path' => '/GM/GM2/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

require_once __DIR__ . '/../app/config/database.php';

$usuario = trim($_POST['usuario'] ?? '');
$senha   = $_POST['senha'] ?? '';

if ($usuario === '' || $senha === '') {
    $_SESSION['erro'] = 'Informe usuário e senha';
    header('Location: /GM/GM2/public/login.php');
    exit;
}

/* Busca usuário ativo */
$stmt = $pdo->prepare("
    SELECT id, senha, tipo_usuario, ativo
    FROM usuarios
    WHERE usuario = ?
    LIMIT 1
");
$stmt->execute([$usuario]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['erro'] = 'Usuário ou senha inválidos';
    header('Location: /GM/GM2/public/login.php');
    exit;
}

/* Verifica se está ativo */
if ((int)$user['ativo'] !== 1) {
    $_SESSION['erro'] = 'Usuário desativado';
    header('Location: /GM/GM2/public/login.php');
    exit;
}

/* Verifica senha */
if (!password_verify($senha, $user['senha'])) {
    $_SESSION['erro'] = 'Usuário ou senha inválidos';
    header('Location: /GM/GM2/public/login.php');
    exit;
}

/* LOGIN OK */
$_SESSION['usuario_id']   = (int)$user['id'];
$_SESSION['tipo_usuario'] = (int)$user['tipo_usuario'];

header('Location: /GM/GM2/index.php');
exit;
