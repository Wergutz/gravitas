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
    $stmt = $pdo->prepare("SELECT * FROM equipamentos_pesados WHERE id = ?");
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
        UPDATE equipamentos_pesados
        SET ativo = IF(ativo=1,0,1)
        WHERE id=?
    ")->execute([$id]);

    header('Location: equipamentos_pesados.php');
    exit;
}

/* =========================
   SALVAR (CRIAR / EDITAR)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {

    $dados = [
        $_POST['tipo'] ?? '',
        $_POST['placa'] ?? null,
        $_POST['fabricante'] ?? null,
        $_POST['modelo'] ?? '',
        $_POST['ano'] ?? null,
        $_POST['proprietario'] ?? null,
        $_POST['valor_contratado'] ?? null,
        $_POST['unidade_contrato'] ?? null,
        $_POST['combustivel'] ?? null
    ];

    if (!empty($_POST['id'])) {
        $dados[] = (int)$_POST['id'];
        $sql = "
            UPDATE equipamentos_pesados SET
                tipo=?, placa=?, fabricante=?, modelo=?, ano=?,
                proprietario=?, valor_contratado=?, unidade_contrato=?, combustivel=?
            WHERE id=?
        ";
    } else {
        $sql = "
            INSERT INTO equipamentos_pesados (
                tipo, placa, fabricante, modelo, ano,
                proprietario, valor_contratado,
                unidade_contrato, combustivel, ativo
            ) VALUES (?,?,?,?,?,?,?,?,?,1)
        ";
    }

    $pdo->prepare($sql)->execute($dados);
    header('Location: equipamentos_pesados.php');
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

    $stmt = $pdo->prepare("
        INSERT INTO equipamentos_pesados (
            tipo, placa, fabricante, modelo, ano,
            proprietario, valor_contratado,
            unidade_contrato, combustivel, ativo
        ) VALUES (?,?,?,?,?,?,?,?,?,1)
    ");

    foreach ($linhas as $linha) {
        $c = str_getcsv($linha, ';');
        if (empty(trim($c[0] ?? ''))) continue;

        $stmt->execute([
            trim($c[0]),                 // tipo
            trim($c[1] ?? null),         // placa
            trim($c[2] ?? null),         // fabricante
            trim($c[3] ?? null),         // modelo
            $c[4] !== '' ? $c[4] : null, // ano
            trim($c[5] ?? null),         // proprietario
            $c[6] !== '' ? $c[6] : null, // valor
            $mapUnidade[$c[7] ?? ''] ?? null,
            $mapCombustivel[$c[8] ?? ''] ?? null
        ]);
    }

    header('Location: equipamentos_pesados.php');
    exit;
}

/* =========================
   LISTAGEM
========================= */
$equipamentos = $pdo->query("
    SELECT id, tipo, placa, modelo, ativo
    FROM equipamentos_pesados
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Equipamentos Pesados | VisionHub Locar</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="/marco_urbano2/assets/css/planejador.css?v=1">
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</head>
<body>

<div class="app">

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="logo">
        <img src="/marco_urbano2/assets/img/logo-marco-urbano.svg" alt="Marco Urbano">
        <span>MARCO URBANO</span>
    </div>
    <nav>
        <a href="/marco_urbano2/planejador/menu.php">📊 Dashboard</a>
        <a href="/marco_urbano2/planejador/equipamentos_pesados.php" class="active">🚜 Equip. Pesados</a>
        <a href="/marco_urbano2/planejador/equipamentos_leves.php">🧰 Equip. Leves</a>
        <a href="/marco_urbano2/planejador/funcionarios.php">👷 Funcionários</a>
        <a href="/marco_urbano2/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<!-- CONTEÚDO -->
<main class="content">

<div class="topbar">
    <div>
        <h1>Equipamentos Pesados</h1>
        <span>Cadastro contratual</span>
    </div>
</div>

<!-- FORMULÁRIO -->
<div class="form-card">
<h3><?= $editando ? 'Editar Equipamento Pesado' : 'Cadastrar Equipamento Pesado' ?></h3>

<form method="post">
<input type="hidden" name="salvar" value="1">
<?php if ($editando): ?>
<input type="hidden" name="id" value="<?= $eqEdicao['id'] ?>">
<?php endif; ?>

<div class="form-group">
<label>Tipo</label>
<select name="tipo" required>
<option value="">Selecione</option>
<option value="ESCAVADEIRA" <?= ($eqEdicao['tipo'] ?? '')==='ESCAVADEIRA'?'selected':'' ?>>Escavadeira</option>
<option value="RETROESCAVADEIRA" <?= ($eqEdicao['tipo'] ?? '')==='RETROESCAVADEIRA'?'selected':'' ?>>Retroescavadeira</option>
<option value="CAMINHAO CACAMBA" <?= ($eqEdicao['tipo'] ?? '')==='CAMINHAO CACAMBA'?'selected':'' ?>>Caminhão Caçamba</option>
<option value="ROLO COMPACTADOR" <?= ($eqEdicao['tipo'] ?? '')==='ROLO COMPACTADOR'?'selected':'' ?>>Rolo Compactador</option>
<option value="MOTONIVELADORA" <?= ($eqEdicao['tipo'] ?? '')==='MOTONIVELADORA'?'selected':'' ?>>Motoniveladora</option>
<option value="OUTRO" <?= ($eqEdicao['tipo'] ?? '')==='OUTRO'?'selected':'' ?>>Outro</option>
</select>
</div>

<div class="form-group"><label>Placa</label><input name="placa" value="<?= $eqEdicao['placa'] ?? '' ?>"></div>
<div class="form-group"><label>Fabricante</label><input name="fabricante" value="<?= $eqEdicao['fabricante'] ?? '' ?>"></div>
<div class="form-group"><label>Modelo</label><input name="modelo" required value="<?= $eqEdicao['modelo'] ?? '' ?>"></div>
<div class="form-group"><label>Ano</label><input type="number" name="ano" value="<?= $eqEdicao['ano'] ?? '' ?>"></div>
<div class="form-group"><label>Proprietário</label><input name="proprietario" value="<?= $eqEdicao['proprietario'] ?? '' ?>"></div>
<div class="form-group"><label>Valor Contratado</label><input type="number" step="0.01" name="valor_contratado" value="<?= $eqEdicao['valor_contratado'] ?? '' ?>"></div>

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
<?php if ($editando): ?><a href="equipamentos_pesados.php" class="btn-secondary">Cancelar</a><?php endif; ?>
</div>
</form>
</div>

<!-- IMPORTAÇÃO -->
<div class="form-card">
<h3>Importar Equipamentos Pesados (Excel)</h3>

<input type="file" id="excel" accept=".xlsx">
<form method="post" id="formCsv">
<input type="hidden" name="csv_data" id="csv_data">
<button class="btn-primary">Importar</button>
</form>

<p class="excel-hint">Cabeçalhos esperados no arquivo Excel (.xlsx)</p>
<p class="excel-cols">
TIPO | PLACA | FABRICANTE | MODELO | ANO | PROPRIETÁRIO | VALOR CONTRATADO | UNIDADE | COMBUSTÍVEL
</p>
</div>

<!-- LISTAGEM -->
<div class="card">
<table class="table">
<thead>
<tr>
<th>ID</th>
<th>Tipo</th>
<th>Placa</th>
<th>Modelo</th>
<th>Status</th>
<th>Ações</th>
</tr>
</thead>
<tbody>
<?php foreach ($equipamentos as $e): ?>
<tr>
<td><?= $e['id'] ?></td>
<td><?= htmlspecialchars($e['tipo']) ?></td>
<td><?= htmlspecialchars($e['placa']) ?></td>
<td><?= htmlspecialchars($e['modelo']) ?></td>
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
