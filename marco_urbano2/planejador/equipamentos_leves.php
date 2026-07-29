<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/database.php';

auth_required([3]); // Planejador

$editando = false;
$eqEdicao = null;

/* =========================
   EDITAR
========================= */
if (isset($_GET['editar'])) {
    $idEdit = (int) $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM equipamentos_leves WHERE id = ?");
    $stmt->execute([$idEdit]);
    $eqEdicao = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($eqEdicao) $editando = true;
}

/* =========================
   ATIVAR / INATIVAR
========================= */
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $pdo->prepare("
        UPDATE equipamentos_leves
        SET ativo = IF(ativo = 1, 0, 1)
        WHERE id = ?
    ")->execute([$id]);

    header('Location: equipamentos_leves.php');
    exit;
}

/* =========================
   SALVAR (FORMULÁRIO)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {

    $dados = [
        $_POST['tipo'] ?? '',
        $_POST['fabricante'] ?? null,
        $_POST['modelo'] ?? '',
        $_POST['numero_serie'] ?? null,
        $_POST['ano'] ?? null,
        $_POST['proprietario'] ?? null,
        $_POST['valor_contratado'] ?? null,
        $_POST['unidade_contrato'] ?? null,
        $_POST['combustivel'] ?? null
    ];

    if (!empty($_POST['id'])) {
        $dados[] = (int)$_POST['id'];
        $sql = "
            UPDATE equipamentos_leves SET
                tipo = ?, fabricante = ?, modelo = ?, numero_serie = ?, ano = ?,
                proprietario = ?, valor_contratado = ?, unidade_contrato = ?, combustivel = ?
            WHERE id = ?
        ";
    } else {
        $sql = "
            INSERT INTO equipamentos_leves (
                tipo, fabricante, modelo, numero_serie, ano,
                proprietario, valor_contratado, unidade_contrato, combustivel, ativo
            ) VALUES (?,?,?,?,?,?,?,?,?,1)
        ";
    }

    $pdo->prepare($sql)->execute($dados);
    header('Location: equipamentos_leves.php');
    exit;
}

/* =========================
   IMPORTAÇÃO EXCEL
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csv_data'])) {

    $linhas = explode("\n", $_POST['csv_data']);
    array_shift($linhas);

    $mapUnidade = [
        '1' => 'MES',
        '2' => 'HORA',
        '3' => 'METRO',
        '4' => 'PROPRIA'
    ];

    $mapCombustivel = [
        '1' => 'CONTRATADA',
        '2' => 'CONTRATANTE',
        '3' => 'PROPRIO'
    ];

    $sql = "
        INSERT INTO equipamentos_leves (
            tipo, fabricante, modelo, numero_serie, ano,
            proprietario, valor_contratado,
            unidade_contrato, combustivel, ativo
        ) VALUES (?,?,?,?,?,?,?,?,?,1)
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($linhas as $linha) {
        $c = str_getcsv($linha, ';');
        if (empty(trim($c[0] ?? ''))) continue;

        $stmt->execute([
            trim($c[0]),                     // tipo
            trim($c[1] ?? null),             // fabricante
            trim($c[2] ?? null),             // modelo
            trim($c[3] ?? null),             // numero_serie
            $c[4] !== '' ? $c[4] : null,     // ano
            trim($c[5] ?? null),             // proprietario
            $c[6] !== '' ? $c[6] : null,     // valor
            $mapUnidade[$c[7] ?? ''] ?? null,
            $mapCombustivel[$c[8] ?? ''] ?? null
        ]);
    }

    header('Location: equipamentos_leves.php');
    exit;
}

/* =========================
   LISTAGEM
========================= */
$equipamentos = $pdo->query("
    SELECT id, tipo, modelo, numero_serie, ativo
    FROM equipamentos_leves
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Equipamentos Leves | VisionHub Locar</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="/visionhub_locar/assets/css/planejador.css?v=1">
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</head>
<body>

<div class="app">

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="logo">
        <img src="/visionhub_locar/assets/img/farol.png" alt="VisionHub">
        <span>VISION HUB</span>
    </div>
    <nav>
        <a href="/visionhub_locar/planejador/menu.php">📊 Dashboard</a>
        <a href="/visionhub_locar/planejador/equipamentos_pesados.php">🚜 Equip. Pesados</a>
        <a href="/visionhub_locar/planejador/equipamentos_leves.php" class="active">🧰 Equip. Leves</a>
        <a href="/visionhub_locar/planejador/funcionarios.php">👷 Funcionários</a>
        <a href="/visionhub_locar/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<!-- CONTEÚDO -->
<main class="content">

<div class="topbar">
    <div>
        <h1>Equipamentos Leves</h1>
        <span>Cadastro contratual</span>
    </div>
    <div class="managed">MANAGED BY GRAVITAS</div>
</div>

<!-- FORMULÁRIO -->
<div class="form-card">
<h3><?= $editando ? 'Editar Equipamento Leve' : 'Cadastrar Equipamento Leve' ?></h3>

<form method="post">
<input type="hidden" name="salvar" value="1">
<?php if ($editando): ?>
<input type="hidden" name="id" value="<?= $eqEdicao['id'] ?>">
<?php endif; ?>

<div class="form-group">
<label>Tipo</label>
<select name="tipo" required>
<?php
$tipos = ['COMPACTADOR','MOTOBOMBA','PLACA VIBRATÓRIA','CORTADORA DE ASFALTO','OUTRO'];
foreach ($tipos as $t):
?>
<option value="<?= $t ?>" <?= ($eqEdicao['tipo'] ?? '') === $t ? 'selected' : '' ?>>
    <?= $t ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group">
<label>Modelo</label>
<input name="modelo" required value="<?= $eqEdicao['modelo'] ?? '' ?>">
</div>

<div class="form-group">
<label>Número de Referência</label>
<input name="numero_serie" value="<?= $eqEdicao['numero_serie'] ?? '' ?>">
</div>

<div class="form-group">
<label>Ano</label>
<input type="number" name="ano" value="<?= $eqEdicao['ano'] ?? '' ?>">
</div>

<div class="form-group">
<label>Proprietário</label>
<input name="proprietario" value="<?= $eqEdicao['proprietario'] ?? '' ?>">
</div>

<div class="form-group">
<label>Valor Contratado</label>
<input type="number" step="0.01" name="valor_contratado" value="<?= $eqEdicao['valor_contratado'] ?? '' ?>">
</div>

<div class="form-group">
<label>Unidade de Referência do Contrato</label>
<select name="unidade_contrato" required>
<option value="">Selecione</option>
<option value="MES" <?= ($eqEdicao['unidade_contrato'] ?? '')==='MES'?'selected':'' ?>>MÊS</option>
<option value="HORA" <?= ($eqEdicao['unidade_contrato'] ?? '')==='HORA'?'selected':'' ?>>HORA</option>
<option value="METRO" <?= ($eqEdicao['unidade_contrato'] ?? '')==='METRO'?'selected':'' ?>>METRO</option>
<option value="PROPRIA" <?= ($eqEdicao['unidade_contrato'] ?? '')==='PROPRIA'?'selected':'' ?>>PRÓPRIA</option>
</select>
</div>

<div class="form-group">
<label>Combustível</label>
<select name="combustivel" required>
<option value="">Selecione</option>
<option value="CONTRATADA" <?= ($eqEdicao['combustivel'] ?? '')==='CONTRATADA'?'selected':'' ?>>Por conta da CONTRATADA</option>
<option value="CONTRATANTE" <?= ($eqEdicao['combustivel'] ?? '')==='CONTRATANTE'?'selected':'' ?>>Por conta da CONTRATANTE</option>
<option value="PROPRIO" <?= ($eqEdicao['combustivel'] ?? '')==='PROPRIO'?'selected':'' ?>>Equipamento PRÓPRIO</option>
</select>
</div>

<div class="form-actions">
<button class="btn-primary"><?= $editando ? 'Salvar Alterações' : 'Cadastrar' ?></button>
<?php if ($editando): ?>
<a href="equipamentos_leves.php" class="btn-secondary">Cancelar</a>
<?php endif; ?>
</div>

</form>
</div>

<!-- IMPORTAÇÃO -->
<div class="form-card">
<h3>Importar Equipamentos Leves (Excel)</h3>
<input type="file" id="excel" accept=".xlsx">

<form method="post" id="formCsv">
<input type="hidden" name="csv_data" id="csv_data">
<button class="btn-primary">Importar</button>
</form>

<p style="color:#9ca3af;margin-top:10px;">
Colunas: Tipo | Fabricante | Modelo | Nº Referência | Ano | Proprietário | Valor | Unidade(1–4) | Combustível(1–3)
</p>
</div>

<!-- LISTAGEM -->
<div class="card">
<table class="table">
<thead>
<tr>
<th>ID</th>
<th>Tipo</th>
<th>Modelo</th>
<th>Nº Referência</th>
<th>Status</th>
<th>Ações</th>
</tr>
</thead>
<tbody>
<?php foreach ($equipamentos as $e): ?>
<tr>
<td><?= $e['id'] ?></td>
<td><?= htmlspecialchars($e['tipo']) ?></td>
<td><?= htmlspecialchars($e['modelo']) ?></td>
<td><?= htmlspecialchars($e['numero_serie']) ?></td>
<td><?= $e['ativo']
    ? '<span class="badge badge-success">ATIVO</span>'
    : '<span class="badge badge-danger">INATIVO</span>' ?>
</td>
<td>
<a href="?editar=<?= $e['id'] ?>">✏️ Editar</a>
<a href="?toggle=<?= $e['id'] ?>" onclick="return confirm('Deseja alterar o status?')">
🔄 <?= $e['ativo'] ? 'Inativar' : 'Ativar' ?>
</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</main>
</div>

<script>
document.getElementById('excel').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(evt) {
        const data = new Uint8Array(evt.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const sheet = workbook.Sheets[workbook.SheetNames[0]];
        document.getElementById('csv_data').value =
            XLSX.utils.sheet_to_csv(sheet, { FS: ';' });
    };
    reader.readAsArrayBuffer(file);
});
</script>

</body>
</html>
