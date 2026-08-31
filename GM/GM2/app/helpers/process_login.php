<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('GM2_PAINEL');
    session_set_cookie_params(['path' => '/GM/GM2/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

require_once __DIR__ . '/../app/config/database.php';

/* =========================
   RECEBE DADOS DO FORM
========================= */
$usuario = $_POST['usuario'] ?? '';
$senha   = $_POST['senha'] ?? '';

if (!$usuario || !$senha) {
    $_SESSION['erro'] = 'Informe usuário e senha';
    header('Location: /GM/GM2/public/login.php');
    exit;
}

/* =========================
   BUSCA USUÁRIO
========================= */
$stmt = $pdo->prepare("
    SELECT id, nome, usuario, senha, tipo_usuario, ativo
    FROM usuarios
    WHERE usuario = ?
    LIMIT 1
");
$stmt->execute([$usuario]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   VALIDAÇÕES
========================= */
if (!$user) {
    $_SESSION['erro'] = 'Usuário não encontrado';
    header('Location: /GM/GM2/public/login.php');
    exit;
}

if ((int)$user['ativo'] !== 1) {
    $_SESSION['erro'] = 'Usuário desativado';
    header('Location: /GM/GM2/public/login.php');
    exit;
}

if (!password_verify($senha, $user['senha'])) {
    $_SESSION['erro'] = 'Senha incorreta';
    header('Location: /GM/GM2/public/login.php');
    exit;
}

/* =========================
   LOGIN OK
========================= */
$_SESSION['usuario_id']   = (int)$user['id'];
$_SESSION['tipo_usuario'] = (int)$user['tipo_usuario'];
// Nome e login ficam na sessão para a tela poder dizer quem está logado
// e em que perfil — sem isso o executor não distingue a sessão dele da
// do planejador, já que as duas usam as mesmas telas.
$_SESSION['nome']         = (string)$user['nome'];
$_SESSION['usuario']      = (string)$user['usuario'];

header('Location: /GM/GM2/index.php');
exit;
