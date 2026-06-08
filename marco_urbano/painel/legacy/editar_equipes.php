<?php
session_start();
// auth_required([4]);
require_once __DIR__ . '/app/config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) die('Equipe inválida');

$stmt = $pdo->prepare("SELECT * FROM equipes WHERE id = ?");
$stmt->execute([$id]);
$equipe = $stmt->fetch();

if (!$equipe) die('Equipe não encontrada');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Editar Equipe</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/visionhub/assets/css/planejador.css">
</head>

<body>
<div class="app">
<main class="content">

<h1>Editar Equipe</h1>

<form method="post" action="atualizar_equipe.php">
<input type="hidden" name="id" value="<?= $equipe['id'] ?>">

<div class="form-card">
    <div class="form-group">
        <label>Nome</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($equipe['nome']) ?>" required>
    </div>

    <div class="form-group">
        <label>Responsável</label>
        <input type="text" name="responsavel" value="<?= htmlspecialchars($equipe['responsavel']) ?>">
    </div>

    <div class="form-group">
        <label>Tipo</label>
        <select name="tipo">
            <option value="REDE" <?= $equipe['tipo']=='REDE'?'selected':'' ?>>REDE</option>
            <option value="OUTRO" <?= $equipe['tipo']=='OUTRO'?'selected':'' ?>>OUTRO</option>
        </select>
    </div>

    <div class="form-actions">
        <button class="btn-primary">Salvar Alterações</button>
        <a href="equipes_rede.php" class="btn-info">Voltar</a>
    </div>
</div>
</form>

</main>
</div>
</body>
</html>
