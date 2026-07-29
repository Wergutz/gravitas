<?php


session_start();

require_once __DIR__ . '/../app/config/database.php';

$usuario = trim($_POST['usuario'] ?? '');
$senha   = $_POST['senha'] ?? '';

if ($usuario === '' || $senha === '') {
    $_SESSION['erro'] = 'Informe usuário e senha';
    header('Location: /visionhub_locar/public/login.php');
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
    header('Location: /visionhub_locar/public/login.php');
    exit;
}

/* Verifica se está ativo */
if ((int)$user['ativo'] !== 1) {
    $_SESSION['erro'] = 'Usuário desativado';
    header('Location: /visionhub_locar/public/login.php');
    exit;
}

/* Verifica senha */
if (!password_verify($senha, $user['senha'])) {
    $_SESSION['erro'] = 'Usuário ou senha inválidos';
    header('Location: /visionhub_locar/public/login.php');
    exit;
}

/* LOGIN OK */
$_SESSION['usuario_id']   = (int)$user['id'];
$_SESSION['tipo_usuario'] = (int)$user['tipo_usuario'];

header('Location: /visionhub_locar/index.php');
exit;
