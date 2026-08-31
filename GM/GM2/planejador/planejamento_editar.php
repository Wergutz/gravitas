<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/asset.php';
require_once __DIR__ . '/../app/config/database.php';

auth_required([3]); // Planejador

if (empty($_GET['id'])) {
    die('Planejamento não informado');
}

$id = (int)$_GET['id'];

/* ===== BUSCA PLANEJAMENTO DO USUÁRIO ===== */
$stmt = $pdo->prepare("
    SELECT *
    FROM planejamentos
    WHERE id = ? AND usuario_id = ?
");
$stmt->execute([$id, $_SESSION['usuario_id']]);
$planejamento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$planejamento) {
    die('Planejamento não encontrado ou acesso negado');
}

/* ===== SALVAR ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['nome'])) {
        die('Nome do planejamento é obrigatório');
    }

    $stmt = $pdo->prepare("
        UPDATE planejamentos
        SET nome = ?
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([
        trim($_POST['nome']),
        $id,
        $_SESSION['usuario_id']
    ]);

    header("Location: menu.php?editado=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Editar Planejamento | GM SERVIÇOS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= mu_asset('/assets/css/planejador.css') ?>">
</head>
<body>

<div class="app">

<aside class="sidebar">
    <div class="logo">
        <img src="/GM/GM2/assets/img/icon-gm.png" alt="GM SERVIÇOS">
        <span>GM SERVIÇOS</span>
    </div>
    <?= bloco_identidade() ?>
    <nav>
        <a href="/GM/GM2/planejador/menu.php">📊 Dashboard</a>
        <a href="/GM/GM2/planejador/planejamento.php">🗂 Novo Planejamento</a>
        <a href="/GM/GM2/planejador/medicao.php">📐 Medição</a>
        <a href="/GM/GM2/planejador/relatorio.php">📄 Relatório</a>
        <a href="/GM/GM2/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content">

<div class="topbar">
    <div>
        <h1>Editar Planejamento</h1>
        <span>Identificação do planejamento</span>
    </div>
</div>

<form method="post">
<div class="form-card">

    <div class="form-group">
        <label>Nome do Planejamento</label>
        <input name="nome" value="<?= htmlspecialchars($planejamento['nome'] ?? '') ?>" required>
    </div>

    <div class="form-actions">
        <button class="btn-primary">💾 Salvar Nome</button>
    </div>

</div>
</form>

</main>
</div>

</body>
</html>
