<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/asset.php';
require_once __DIR__ . '/../app/config/database.php';

auth_required([3]); // Planejador

$editando = false;
$funcEdicao = null;

/* ===== FUNÇÃO SELECT STATUS (3 ESTADOS) ===== */
function selectStatus($name, $label, $value = null) {
?>
<div class="form-group">
    <label><?= $label ?></label>
    <select name="<?= $name ?>">
        <option value="">Selecione</option>
        <option value="1" <?= $value==1?'selected':'' ?>>Apto</option>
        <option value="2" <?= $value==2?'selected':'' ?>>Não apto</option>
        <option value="3" <?= $value==3?'selected':'' ?>>Não se aplica</option>
    </select>
</div>
<?php
}

/* ===== CARREGAR PARA EDIÇÃO ===== */
if (isset($_GET['editar'])) {
    $idEdit = (int) $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE id = ?");
    $stmt->execute([$idEdit]);
    $funcEdicao = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($funcEdicao) $editando = true;
}

/* ===== ATIVAR / INATIVAR ===== */
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $pdo->prepare("UPDATE funcionarios SET ativo = IF(ativo=1,0,1) WHERE id=?")
        ->execute([$id]);
    header('Location: funcionarios.php');
    exit;
}

/* ===== SALVAR (CRIAR / EDITAR) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {

    $cpf = trim($_POST['cpf'] ?? '');

    // 🔒 BLOQUEIO SOMENTE POR CPF DUPLICADO
    if ($cpf !== '') {
        if (!empty($_POST['id'])) {
            $stmt = $pdo->prepare("
                SELECT id FROM funcionarios
                WHERE cpf = ? AND id <> ?
                LIMIT 1
            ");
            $stmt->execute([$cpf, (int)$_POST['id']]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id FROM funcionarios
                WHERE cpf = ?
                LIMIT 1
            ");
            $stmt->execute([$cpf]);
        }

        if ($stmt->fetch()) {
            die('CPF já cadastrado no sistema.');
        }
    }

    $dados = [
        $_POST['nome'] ?? '',
        $cpf ?: null,
        $_POST['empresa'] ?? null,
        $_POST['funcao'] ?? '',
        $_POST['salario'] ?? null,

        $_POST['aso'] ?? null,
        $_POST['nr06'] ?? null,
        $_POST['nr10'] ?? null,
        $_POST['nr11'] ?? null,
        $_POST['nr12'] ?? null,
        $_POST['nr18'] ?? null,
        $_POST['nr20'] ?? null,
        $_POST['nr23'] ?? null,
        $_POST['nr33'] ?? null,
        $_POST['nr35'] ?? null,
        $_POST['integracao_corsan'] ?? null,
        $_POST['sertras'] ?? null
    ];

    if (!empty($_POST['id'])) {
        $dados[] = (int)$_POST['id'];
        $sql = "
            UPDATE funcionarios SET
            nome=?, cpf=?, empresa=?, funcao=?, salario=?,
            aso=?, nr06=?, nr10=?, nr11=?, nr12=?, nr18=?, nr20=?, nr23=?, nr33=?, nr35=?,
            integracao_corsan=?, sertras=?
            WHERE id=?
        ";
    } else {
        $sql = "
            INSERT INTO funcionarios (
                nome, cpf, empresa, funcao, salario,
                aso, nr06, nr10, nr11, nr12, nr18, nr20, nr23, nr33, nr35,
                integracao_corsan, sertras, ativo
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)
        ";
    }

    $pdo->prepare($sql)->execute($dados);
    header('Location: funcionarios.php');
    exit;
}

/* ===== IMPORTAÇÃO EXCEL (CSV) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csv_data'])) {

    $linhas = explode("\n", $_POST['csv_data']);
    array_shift($linhas);

    $checkCpf = $pdo->prepare("SELECT id FROM funcionarios WHERE cpf = ? LIMIT 1");

    $stmt = $pdo->prepare("
        INSERT INTO funcionarios (
            nome, cpf, empresa, funcao, salario,
            aso, nr06, nr10, nr11, nr12, nr18, nr20, nr23, nr33, nr35,
            integracao_corsan, sertras, ativo
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)
    ");

    foreach ($linhas as $linha) {
        $c = str_getcsv($linha);
        if (empty(trim($c[0] ?? ''))) continue;

        $cpf = trim($c[1] ?? '');

        // 🔒 BLOQUEIA CPF DUPLICADO
        if ($cpf !== '') {
            $checkCpf->execute([$cpf]);
            if ($checkCpf->fetch()) continue;
        }

        $stmt->execute([
            trim($c[0]),
            $cpf ?: null,
            trim($c[2] ?? null),
            trim($c[3] ?? null),
            $c[4] !== '' ? $c[4] : null,

            $c[5] ?? null,
            $c[6] ?? null,
            $c[7] ?? null,
            $c[8] ?? null,
            $c[9] ?? null,
            $c[10] ?? null,
            $c[11] ?? null,
            $c[12] ?? null,
            $c[13] ?? null,
            $c[14] ?? null,
            $c[15] ?? null,
            $c[16] ?? null
        ]);
    }

    header('Location: funcionarios.php');
    exit;
}

/* ===== LISTAGEM ===== */
$funcionarios = $pdo->query("
    SELECT id, nome, funcao, ativo
    FROM funcionarios
    ORDER BY nome
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Funcionários | GM SERVIÇOS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= mu_asset('/assets/css/planejador.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</head>
<body>

<div class="app">

<aside class="sidebar">
    <div class="logo">
        <img src="/GM/GM2/assets/img/icon-gm.png" alt="GM SERVIÇOS">
        <span>GM SERVIÇOS</span>
    </div>
    <nav>
        <a href="/GM/GM2/planejador/menu.php">📊 Dashboard</a>
        <a href="/GM/GM2/planejador/equipamentos_pesados.php">🚜 Equip. Pesados</a>
        <a href="/GM/GM2/planejador/equipamentos_leves.php">🧰 Equip. Leves</a>
        <a href="/GM/GM2/planejador/funcionarios.php" class="active">👷 Funcionários</a>
        <a href="/GM/GM2/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content">

<div class="topbar">
    <div>
        <h1>Funcionários</h1>
        <span>Cadastro e conformidade operacional</span>
    </div>
</div>

<div class="form-card">
<h3><?= $editando ? 'Editar Funcionário' : 'Cadastrar Funcionário' ?></h3>

<form method="post">
<input type="hidden" name="salvar" value="1">
<?php if ($editando): ?>
<input type="hidden" name="id" value="<?= $funcEdicao['id'] ?>">
<div class="form-group">
<label>Inscrição</label>
<input value="<?= $funcEdicao['id'] ?>" disabled>
</div>
<?php endif; ?>

<div class="form-group"><label>Nome</label><input name="nome" required value="<?= $funcEdicao['nome'] ?? '' ?>"></div>
<div class="form-group"><label>CPF</label><input name="cpf" value="<?= $funcEdicao['cpf'] ?? '' ?>"></div>
<div class="form-group"><label>Empresa</label><input name="empresa" value="<?= $funcEdicao['empresa'] ?? '' ?>"></div>
<div class="form-group"><label>Função</label><input name="funcao" required value="<?= $funcEdicao['funcao'] ?? '' ?>"></div>
<div class="form-group"><label>Salário</label><input type="number" step="0.01" name="salario" value="<?= $funcEdicao['salario'] ?? '' ?>"></div>

<h4 style="margin-top:20px;">Documentações / NRs</h4>

<?php
selectStatus('aso','ASO',$funcEdicao['aso'] ?? null);
selectStatus('nr06','NR06',$funcEdicao['nr06'] ?? null);
selectStatus('nr10','NR10',$funcEdicao['nr10'] ?? null);
selectStatus('nr11','NR11',$funcEdicao['nr11'] ?? null);
selectStatus('nr12','NR12',$funcEdicao['nr12'] ?? null);
selectStatus('nr18','NR18',$funcEdicao['nr18'] ?? null);
selectStatus('nr20','NR20',$funcEdicao['nr20'] ?? null);
selectStatus('nr23','NR23',$funcEdicao['nr23'] ?? null);
selectStatus('nr33','NR33',$funcEdicao['nr33'] ?? null);
selectStatus('nr35','NR35',$funcEdicao['nr35'] ?? null);
selectStatus('integracao_corsan','Integração CORSAN',$funcEdicao['integracao_corsan'] ?? null);
selectStatus('sertras','SERTRAS',$funcEdicao['sertras'] ?? null);
?>

<div class="form-actions">
<button class="btn-primary"><?= $editando ? 'Salvar Alterações' : 'Cadastrar' ?></button>
<?php if ($editando): ?><a href="funcionarios.php" class="btn-secondary">Cancelar</a><?php endif; ?>
</div>
</form>
</div>

<div class="form-card">
<h3>Importar Funcionários (Excel)</h3>
<input type="file" id="excel" accept=".xlsx">
<form method="post" id="formCsv">
<input type="hidden" name="csv_data" id="csv_data">
<div class="form-actions">
<button class="btn-primary">Importar</button>
</div>
</form>
<p style="color:#9ca3af;margin-top:10px;">Colunas:  NOME | CPF | EMPRESA | FUNÇÃO | SALÁRIO | ASO | NR06 | NR10 | NR11 | NR12 | NR18 | NR20 | NR23 | NR33 | NR35 | INTEGRAÇÃO CORSAN | SERTRAS</p>

</div>

<div class="card">
<table class="table">
<thead>
<tr>
<th>Inscrição</th>
<th>Nome</th>
<th>Função</th>
<th>Status</th>
<th>Ações</th>
</tr>
</thead>
<tbody>
<?php foreach ($funcionarios as $f): ?>
<tr>
<td><?= $f['id'] ?></td>
<td><?= htmlspecialchars($f['nome']) ?></td>
<td><?= htmlspecialchars($f['funcao']) ?></td>
<td><?= $f['ativo'] ? '<span class="badge badge-success">ATIVO</span>' : '<span class="badge badge-danger">INATIVO</span>' ?></td>
<td>
<a href="?editar=<?= $f['id'] ?>">✏️ Editar</a>
<a href="?toggle=<?= $f['id'] ?>" onclick="return confirm('Deseja alterar o status deste funcionário?')">
🔄 <?= $f['ativo'] ? 'Inativar' : 'Ativar' ?>
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
            XLSX.utils.sheet_to_csv(sheet);
    };
    reader.readAsArrayBuffer(file);
});
</script>

</body>
</html>
