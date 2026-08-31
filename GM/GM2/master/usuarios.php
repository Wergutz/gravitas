<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('GM2_PAINEL');
    session_set_cookie_params(['path' => '/GM/GM2/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

/* ===== PROTEÇÃO: SOMENTE MASTER ===== */
if (!isset($_SESSION['usuario_id'], $_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] != 1) {
    header('Location: /GM/GM2/public/login.php');
    exit;
}

require_once __DIR__ . '/../app/config/database.php';

/* ===== VARIÁVEIS ===== */
$editando = false;
$usuarioEdicao = null;

/* ===== CARREGAR USUÁRIO PARA EDIÇÃO ===== */
if (isset($_GET['editar'])) {
    $idEdit = (int) $_GET['editar'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$idEdit]);
    $usuarioEdicao = $stmt->fetch();

    if ($usuarioEdicao) {
        $editando = true;
    }
}

/* ===== SALVAR (CRIAR OU EDITAR) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome    = trim($_POST['nome']);
    $usuario = trim($_POST['usuario']);
    $tipo    = (int) $_POST['tipo_usuario'];
    $ativo   = (int) $_POST['ativo'];

    if (isset($_POST['id']) && $_POST['id']) {
        // ===== EDIÇÃO =====
        $id = (int) $_POST['id'];

        if (!empty($_POST['senha'])) {
            $hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE usuarios
                SET nome = ?, usuario = ?, senha = ?, tipo_usuario = ?, ativo = ?
                WHERE id = ?
            ");
            $stmt->execute([$nome, $usuario, $hash, $tipo, $ativo, $id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE usuarios
                SET nome = ?, usuario = ?, tipo_usuario = ?, ativo = ?
                WHERE id = ?
            ");
            $stmt->execute([$nome, $usuario, $tipo, $ativo, $id]);
        }

    } else {
        // ===== CADASTRO =====
        $hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, usuario, senha, tipo_usuario, ativo)
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->execute([$nome, $usuario, $hash, $tipo]);
    }

    header('Location: usuarios.php');
    exit;
}

/* ===== LISTAGEM ===== */
$usuarios = $pdo->query("
    SELECT id, nome, usuario, tipo_usuario, ativo
    FROM usuarios
    ORDER BY nome
")->fetchAll();

function perfil($t) {
    return match ($t) {
        1 => 'MASTER',
        2 => 'PROPRIETÁRIO',
        3 => 'PLANEJADOR',
        4 => 'EXECUTOR',
        default => '—'
    };
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Usuários | MASTER</title>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 30px;
    font-size: 14px;
    background: #f9fafb;
}

h1 { margin-bottom: 10px; }

.logout {
    position: fixed;
    bottom: 20px;
    left: 20px;
    background: #dc2626;
    color: #fff;
    padding: 8px 14px;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
    font-size: 13px;
    z-index: 999;
}

form {
    background: #ffffff;
    padding: 20px;
    border: 1px solid #ddd;
    margin-bottom: 30px;
    border-radius: 6px;
}

label {
    font-weight: bold;
    display: block;
    margin-top: 10px;
}

input, select {
    width: 320px;
    padding: 6px;
    margin-top: 4px;
}

button {
    margin-top: 15px;
    padding: 8px 14px;
    cursor: pointer;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}

th {
    background: #111827;
    color: #fff;
    padding: 8px;
}

td {
    padding: 8px;
    border: 1px solid #ddd;
    text-align: center;
}

.inativo {
    background: #fee2e2;
}

.editar {
    color: #C68C28;
    text-decoration: none;
    font-weight: bold;
}
</style>
</head>

<body>

<h1>Painel MASTER – Usuários</h1>

<a class="logout" href="/GM/GM2/public/logout.php">Sair</a>

<form method="post">
    <h3><?= $editando ? 'Editar usuário' : 'Cadastrar novo usuário' ?></h3>

    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= $usuarioEdicao['id'] ?>">
    <?php endif; ?>

    <label>Nome</label>
    <input name="nome" required value="<?= $usuarioEdicao['nome'] ?? '' ?>">

    <label>Login</label>
    <input name="usuario" required value="<?= $usuarioEdicao['usuario'] ?? '' ?>">

    <label>Senha <?= $editando ? '(preencher apenas para alterar)' : '' ?></label>
    <input type="password" name="senha">

    <label>Perfil</label>
    <select name="tipo_usuario">
        <?php foreach ([1,2,3,4] as $t): ?>
            <option value="<?= $t ?>"
                <?= ($usuarioEdicao['tipo_usuario'] ?? '') == $t ? 'selected' : '' ?>>
                <?= perfil($t) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Status</label>
    <select name="ativo">
        <option value="1" <?= ($usuarioEdicao['ativo'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo</option>
        <option value="0" <?= ($usuarioEdicao['ativo'] ?? 1) == 0 ? 'selected' : '' ?>>Inativo</option>
    </select>

    <button type="submit"><?= $editando ? 'Salvar alterações' : 'Criar usuário' ?></button>

    <?php if ($editando): ?>
        <a href="usuarios.php" style="margin-left:10px;">Cancelar</a>
    <?php endif; ?>
</form>

<h3>Usuários cadastrados</h3>

<table>
<thead>
<tr>
    <th>Nome</th>
    <th>Login</th>
    <th>Perfil</th>
    <th>Status</th>
    <th>Ação</th>
</tr>
</thead>
<tbody>
<?php foreach ($usuarios as $u): ?>
<tr class="<?= $u['ativo'] ? '' : 'inativo' ?>">
    <td><?= htmlspecialchars($u['nome']) ?></td>
    <td><?= htmlspecialchars($u['usuario']) ?></td>
    <td><?= perfil($u['tipo_usuario']) ?></td>
    <td><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></td>
    <td>
        <a class="editar" href="usuarios.php?editar=<?= $u['id'] ?>">Editar</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</body>
</html>
