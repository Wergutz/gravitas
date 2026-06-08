<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([4]); // Planejador

/* ===============================
   SALVAR EQUIPAMENTO LEVE
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tipo   = $_POST['tipo'] ?? '';
    $modelo = trim($_POST['modelo'] ?? '');
    $serie  = trim($_POST['numero_serie'] ?? '');

    if ($tipo && $modelo) {
        $stmt = $pdo->prepare("
            INSERT INTO equipamentos_leves (tipo, modelo, numero_serie)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$tipo, $modelo, $serie]);

        header("Location: equipamentos_leves.php");
        exit;
    }
}

/* ===============================
   LISTAR EQUIPAMENTOS LEVES
=============================== */
$equipamentos = $pdo->query("
    SELECT *
    FROM equipamentos_leves
    ORDER BY tipo, modelo
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Equipamentos Leves</title>
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
        <a href="/visionhub/planejador.php">📊 Dashboard</a>
        <a href="/visionhub/equipamentos_pesados.php">🚜 Equip. Pesados</a>
        <a href="/visionhub/equipamentos_leves.php" class="active">🧰 Equip. Leves</a>
        <a href="/visionhub/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content">

<div class="topbar">
    <div>
        <h1>Equipamentos Leves</h1>
        <span>Cadastro de equipamentos auxiliares</span>
    </div>
</div>

<div class="form-card">
<form method="post">

<h3>Novo Equipamento Leve</h3>

<div class="form-group">
    <label>Tipo</label>
    <select name="tipo" required>
        <option value="">Selecione</option>
        <option value="COMPACTADOR">Compactador</option>
        <option value="PLACA_VIBRATORIA">Placa Vibratória</option>
        <option value="MOTOBOMBA">Motobomba</option>
        <option value="CORTADORA_ASFALTO">Cortadora de Asfalto</option>
        <option value="OUTRO">Outro</option>
    </select>
</div>

<div class="form-group">
    <label>Modelo</label>
    <input type="text" name="modelo" required>
</div>

<div class="form-group">
    <label>Número de Série</label>
    <input type="text" name="numero_serie">
</div>

<button class="btn-primary">Cadastrar</button>

</form>
</div>

<div class="card">
<table class="table">
<thead>
<tr>
    <th>Tipo</th>
    <th>Modelo</th>
    <th>Nº Série</th>
    <th>Status</th>
</tr>
</thead>
<tbody>

<?php foreach ($equipamentos as $e): ?>
<tr>
    <td><?= htmlspecialchars($e['tipo']) ?></td>
    <td><?= htmlspecialchars($e['modelo']) ?></td>
    <td><?= htmlspecialchars($e['numero_serie']) ?></td>
    <td>
        <?= $e['ativo']
            ? '<span class="badge badge-success">ATIVO</span>'
            : '<span class="badge badge-danger">INATIVO</span>' ?>
    </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>

</main>
</div>

</body>
</html>
