<?php
session_start();

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

/* 🔐 SOMENTE MASTER OU ADMIN */
auth_required([1, 2]);

/* =========================
   CRIAR / EDITAR USUÁRIO
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id           = $_POST['id'] ?? null;
    $nome         = trim($_POST['nome']);
    $email        = trim($_POST['email']);
    $tipo_usuario = (int) $_POST['tipo_usuario'];
    $ativo        = isset($_POST['ativo']) ? 1 : 0;
    $senha        = $_POST['senha'] ?? '';

    if ($id) {
        // EDITAR
        if (!empty($senha)) {
            $hash = password_hash($senha, PASSWORD_DEFAULT);

            $sql = "UPDATE usuarios
                    SET nome=?, email=?, tipo_usuario=?, ativo=?, senha=?
                    WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $email, $tipo_usuario, $ativo, $hash, $id]);
        } else {
            $sql = "UPDATE usuarios
                    SET nome=?, email=?, tipo_usuario=?, ativo=?
                    WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $email, $tipo_usuario, $ativo, $id]);
        }
    } else {
        // CRIAR
        $hash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario, ativo)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $email, $hash, $tipo_usuario, $ativo]);
    }

    header("Location: admin_usuarios.php");
    exit;
}

/* =========================
   LISTAR USUÁRIOS
   ========================= */
$usuarios = $pdo->query("
    SELECT id, nome, email, tipo_usuario, ativo
    FROM usuarios
    ORDER BY nome
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   TIPOS DE USUÁRIO
   ========================= */
$tipos = [
    1 => 'Master',
    2 => 'Administrador',
    3 => 'Proprietário',
    4 => 'Planejador',
    5 => 'Executor'
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Vision Hub | Administração de Usuários</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="/visionhub/assets/css/planejador.css">
</head>

<body>

<div class="app">

    <!-- SIDEBAR ADMIN -->
    <aside class="sidebar">
        <div class="logo">
            <img src="/visionhub/assets/img/farol.png" alt="Vision Hub">
            <span>VISION HUB</span>
        </div>

        <nav>
            <a href="/visionhub/admin.php">
                <span class="vh-icon">🛠️</span>
                <span class="vh-label">Administração</span>
            </a>

            <a href="/visionhub/admin_usuarios.php" class="active">
                <span class="vh-icon">👥</span>
                <span class="vh-label">Usuários</span>
            </a>

            <a href="/visionhub/logout.php">
                <span class="vh-icon">🚪</span>
                <span class="vh-label">Sair</span>
            </a>
        </nav>
    </aside>

    <!-- CONTEÚDO -->
    <main class="content">

        <div class="topbar">
            <div>
                <h1>Usuários do Sistema</h1>
                <span>Cadastro e gerenciamento de acessos</span>
            </div>
            <div class="managed">MANAGED BY GRAVITAS</div>
        </div>

        <!-- FORMULÁRIO -->
        <form method="post" class="form-card">

            <input type="hidden" name="id" id="id">

            <div class="form-group">
                <label>Nome</label>
                <input type="text" name="nome" id="nome" required>
            </div>

            <div class="form-group">
                <label>E-mail (login)</label>
                <input type="email" name="email" id="email" required>
            </div>

            <div class="form-group">
                <label>Tipo de Usuário</label>
                <select name="tipo_usuario" id="tipo_usuario" required>
                    <?php foreach ($tipos as $k => $v): ?>
                        <option value="<?= $k ?>"><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Senha (preencher para criar ou alterar)</label>
                <input type="password" name="senha">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="ativo" id="ativo" checked>
                    Usuário ativo
                </label>
            </div>

            <div class="form-actions">
                <button class="btn-primary">Salvar Usuário</button>
            </div>

        </form>

        <!-- LISTAGEM -->
        <div class="card">

            <table width="100%" cellpadding="10">
                <thead>
                    <tr style="opacity:0.7;">
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['nome']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= $tipos[$u['tipo_usuario']] ?></td>
                        <td><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                        <td>
                            <button class="btn-info"
                                onclick='editarUsuario(<?= json_encode($u) ?>)'>
                                Editar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        </div>

    </main>
</div>

<script>
function editarUsuario(u) {
    document.getElementById('id').value = u.id;
    document.getElementById('nome').value = u.nome;
    document.getElementById('email').value = u.email;
    document.getElementById('tipo_usuario').value = u.tipo_usuario;
    document.getElementById('ativo').checked = u.ativo == 1;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

</body>
</html>
