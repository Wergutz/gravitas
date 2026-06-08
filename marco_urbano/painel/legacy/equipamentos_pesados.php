<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([4]); // Planejador

/* ===============================
   SALVAR EQUIPAMENTO PESADO
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tipo   = $_POST['tipo'] ?? '';
    $modelo = trim($_POST['modelo'] ?? '');
    $placa  = trim($_POST['placa'] ?? '');

    if ($tipo && $modelo) {
        $stmt = $pdo->prepare("
            INSERT INTO equipamentos_pesados (tipo, modelo, placa)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$tipo, $modelo, $placa]);

        header("Location: equipamentos_pesados.php");
        exit;
    }
}

/* ===============================
   LISTAR EQUIPAMENTOS PESADOS
=============================== */
$equipamentos = $pdo->query("
    SELECT id, tipo, modelo, placa, ativo
    FROM equipamentos_pesados
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Vision Hub | Equipamentos Pesados</title>
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
        <a href="/visionhub/planejador.php">📊 Dashboard</a>
        <a href="/visionhub/equipamentos_pesados.php" class="active">🚜 Equip. Pesados</a>
        <a href="/visionhub/equipamentos_leves.php">🧰 Equip. Leves</a>
        <a href="/visionhub/logout.php">🚪 Sair</a>
    </nav>
</aside>

<!-- CONTEÚDO -->
<main class="content">

<div class="topbar">
    <div>
        <h1>Equipamentos Pesados</h1>
        <span>Cadastro de máquinas e veículos</span>
    </div>
    <div class="managed">MANAGED BY GRAVITAS</div>
</div>

<!-- FORMULÁRIO -->
<div class="form-card">
<form method="post">

<h3>Novo Equipamento Pesado</h3>

<div class="form-group">
    <label>Tipo</label>
    <select name="tipo" required>
        <option value="">Selecione</option>
        <option value="RETROESCAVADEIRA">Retroescavadeira</option>
        <option value="ESCAVADEIRA">Escavadeira</option>
        <option value="CACAMBA">Caçamba</option>
        <option value="OUTRO">Outro</option>
    </select>
</div>

<div class="form-group">
    <label>Modelo</label>
    <input type="text" name="modelo" required>
</div>

<div class="form-group">
    <label>Placa</label>
    <input type="text" name="placa">
</div>

<div class="form-actions">
    <button class="btn-primary">Cadastrar</button>
</div>

</form>
</div>

<!-- LISTAGEM -->
<div class="card">
<table class="table">
<thead>
<tr>
    <th>Tipo</th>
    <th>Modelo</th>
    <th>Placa</th>
    <th>Status</th>
</tr>
</thead>
<tbody>

<?php foreach ($equipamentos as $e): ?>
<tr>
    <td><?= htmlspecialchars($e['tipo']) ?></td>
    <td><?= htmlspecialchars($e['modelo']) ?></td>
    <td><?= htmlspecialchars($e['placa']) ?></td>
    <td>
        <?php if ($e['ativo']): ?>
            <span class="badge badge-success">ATIVO</span>
        <?php else: ?>
            <span class="badge badge-danger">INATIVO</span>
        <?php endif; ?>
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
