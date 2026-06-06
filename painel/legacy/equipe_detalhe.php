<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([4]); // Planejador

if (empty($_GET['id'])) {
    die('Equipe não informada.');
}

$equipeId = (int)$_GET['id'];

/* ================= DADOS DA EQUIPE ================= */
$stmt = $pdo->prepare("SELECT * FROM equipes WHERE id = ?");
$stmt->execute([$equipeId]);
$equipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipe) {
    die('Equipe não encontrada.');
}

/* ================= VEÍCULOS ================= */
$stmt = $pdo->prepare("SELECT * FROM equipes_veiculos WHERE equipe_id = ?");
$stmt->execute([$equipeId]);
$veiculos = $stmt->fetch(PDO::FETCH_ASSOC);

/* ================= EQUIPAMENTOS ================= */
$stmt = $pdo->prepare("SELECT * FROM equipes_equipamentos WHERE equipe_id = ?");
$stmt->execute([$equipeId]);
$equip = $stmt->fetch(PDO::FETCH_ASSOC);

/* ================= FUNCIONÁRIOS ================= */
$stmt = $pdo->prepare("
    SELECT * FROM equipes_funcionarios
    WHERE equipe_id = ?
    ORDER BY numero
");
$stmt->execute([$equipeId]);
$funcs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Vision Hub | Detalhe da Equipe</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="/visionhub/assets/css/planejador.css">
</head>
<body>

<div class="app">

<aside class="sidebar">
    <div class="logo">
        <img src="/visionhub/assets/img/farol.png">
        <span>VISION HUB</span>
    </div>
    <nav>
        <a href="/visionhub/equipes_todas.php">⬅ Voltar</a>
        <a href="/visionhub/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content">

<div class="topbar">
    <div>
        <h1>Equipe: <?= htmlspecialchars($equipe['nome']) ?></h1>
        <span>Visualização completa (somente leitura)</span>
    </div>
</div>

<div class="form-card">
<h3>Dados da Equipe</h3>
<p><strong>Responsável:</strong> <?= htmlspecialchars($equipe['responsavel']) ?></p>
<p><strong>Usuário Executor:</strong> <?= htmlspecialchars($equipe['usuario_sistema']) ?></p>
<p><strong>Status:</strong> <?= $equipe['ativo'] ? 'ATIVA' : 'INATIVA' ?></p>
</div>

<?php if ($veiculos): ?>
<div class="form-card">
<h3>Veículos</h3>
<p><strong>Retroescavadeira:</strong> <?= htmlspecialchars($veiculos['retro_modelo_placa']) ?></p>
<p><strong>Operador:</strong> <?= htmlspecialchars($veiculos['retro_operador']) ?></p>
<p><strong>Caçamba:</strong> <?= htmlspecialchars($veiculos['cacamba_modelo_placa']) ?></p>
<p><strong>Motorista:</strong> <?= htmlspecialchars($veiculos['cacamba_motorista']) ?></p>
</div>
<?php endif; ?>

<?php if ($equip): ?>
<div class="form-card">
<h3>Equipamentos</h3>
<p>Compactador: <?= (int)$equip['compactador'] ?></p>
<p>Placa vibratória: <?= (int)$equip['placa'] ?></p>
<p>Motobomba: <?= (int)$equip['motobomba'] ?></p>
<p>Cortadora: <?= (int)$equip['cortadora'] ?></p>
</div>
<?php endif; ?>

<?php if ($funcs): ?>
<div class="form-card">
<h3>Funcionários</h3>
<table class="table">
    <thead>
        <tr>
            <th>#</th>
            <th>Nome</th>
            <th>Função</th>
            <th>SERTRAS</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($funcs as $f): ?>
        <tr>
            <td><?= $f['numero'] ?></td>
            <td><?= htmlspecialchars($f['nome']) ?></td>
            <td><?= htmlspecialchars($f['funcao']) ?></td>
            <td><?= htmlspecialchars($f['sertras']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

</main>
</div>

</body>
</html>
