<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([2]); // Administrador

/* ===============================
   BUSCAR USUÁRIOS (EXECUTORES)
   =============================== */
$stmt = $pdo->query("
    SELECT id, nome
    FROM usuarios
    WHERE CAST(tipo_usuario AS CHAR) = '5'
    ORDER BY nome
");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   BUSCAR EQUIPES
   =============================== */
$stmt = $pdo->query("
    SELECT id, nome
    FROM equipes
    ORDER BY nome, id
");
$equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   VINCULAR USUÁRIO À EQUIPE
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'vincular') {

    $usuario_id = (int)($_POST['usuario_id'] ?? 0);
    $equipe_id  = (int)($_POST['equipe_id'] ?? 0);

    if ($usuario_id > 0 && $equipe_id > 0) {

        $stmt = $pdo->prepare("
            INSERT INTO equipes_usuarios (usuario_id, equipe_id, ativo)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE ativo = 1
        ");
        $stmt->execute([$usuario_id, $equipe_id]);

        $_SESSION['sucesso'] = 'Usuário vinculado à equipe com sucesso.';
    } else {
        $_SESSION['erro'] = 'Selecione um usuário e uma equipe válidos.';
    }

    header('Location: vincular_usuario_equipe.php');
    exit;
}

/* ===============================
   ATIVAR / DESATIVAR VÍNCULO
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'toggle') {

    $vinculo_id = (int)($_POST['vinculo_id'] ?? 0);

    if ($vinculo_id > 0) {
        $stmt = $pdo->prepare("
            UPDATE equipes_usuarios
            SET ativo = IF(ativo = 1, 0, 1)
            WHERE id = ?
        ");
        $stmt->execute([$vinculo_id]);
    }

    header('Location: vincular_usuario_equipe.php');
    exit;
}

/* ===============================
   LISTAR VÍNCULOS EXISTENTES
   =============================== */
$stmt = $pdo->query("
    SELECT 
        eu.id,
        u.nome AS usuario,
        e.id AS equipe_id,
        e.nome AS equipe,
        eu.ativo
    FROM equipes_usuarios eu
    INNER JOIN usuarios u ON u.id = eu.usuario_id
    INNER JOIN equipes e ON e.id = eu.equipe_id
    ORDER BY u.nome, e.nome, e.id
");
$vinculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sucesso = $_SESSION['sucesso'] ?? null;
$erro    = $_SESSION['erro'] ?? null;
unset($_SESSION['sucesso'], $_SESSION['erro']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Vision Hub | Vincular Usuário à Equipe</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="/visionhub/assets/css/planejador.css">
</head>
<body>

<div class="app">

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="logo">
        <img src="/visionhub/assets/img/farol.png">
        <span>VISION HUB</span>
    </div>

    <nav>
        <a href="admin.php">⬅ Administração</a>
        <a href="logout.php">🚪 Sair</a>
    </nav>
</aside>

<!-- CONTEÚDO -->
<main class="content">

<div class="topbar">
    <div>
        <h1>Vincular Usuário à Equipe</h1>
        <span>Gerenciamento de executores por equipe</span>
    </div>
    <div class="managed">MANAGED BY GRAVITAS</div>
</div>

<?php if ($sucesso): ?>
    <div class="form-card"><?= htmlspecialchars($sucesso) ?></div>
<?php endif; ?>

<?php if ($erro): ?>
    <div class="form-card"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<!-- FORMULÁRIO DE VÍNCULO -->
<div class="form-card">
    <form method="post">
        <input type="hidden" name="acao" value="vincular">

        <div class="form-group">
            <label>Usuário (Executor)</label>
            <select name="usuario_id" required>
                <option value="">Selecione</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['id'] ?>">
                        [<?= $u['id'] ?>] <?= htmlspecialchars($u['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Equipe</label>
            <select name="equipe_id" required>
                <option value="">Selecione</option>
                <?php foreach ($equipes as $e): ?>
                    <option value="<?= $e['id'] ?>">
                        [<?= $e['id'] ?>] <?= htmlspecialchars($e['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-actions">
            <button class="btn-primary">Vincular</button>
            <a href="admin.php" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<!-- LISTA DE VÍNCULOS -->
<div class="form-card" style="margin-top:30px;">
    <h2>Vínculos Existentes</h2>

    <?php if (!$vinculos): ?>
        <p>Nenhum vínculo cadastrado.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Equipe</th>
                    <th>Status</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($vinculos as $v): ?>
                <tr>
                    <td><?= htmlspecialchars($v['usuario']) ?></td>
                    <td>[<?= $v['equipe_id'] ?>] <?= htmlspecialchars($v['equipe']) ?></td>
                    <td><?= $v['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="acao" value="toggle">
                            <input type="hidden" name="vinculo_id" value="<?= $v['id'] ?>">
                            <button class="btn-secondary">
                                <?= $v['ativo'] ? 'Desativar' : 'Ativar' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</main>
</div>

</body>
</html>
