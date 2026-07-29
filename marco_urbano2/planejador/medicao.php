<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/database.php';

auth_required([3]);

$erro = null;

/* ===============================
   PLANEJAMENTOS DO USUÁRIO
================================ */
$stmt = $pdo->prepare("
    SELECT id, nome
    FROM planejamentos
    WHERE usuario_id = ?
    ORDER BY id DESC
");
$stmt->execute([$_SESSION['usuario_id']]);
$planejamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   PLANEJAMENTO SELECIONADO
================================ */
$planejamentoSelecionado = null;
$trechos = [];
$trechoSelecionado = null;

if (!empty($_GET['planejamento_id'])) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM planejamentos
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([
        $_GET['planejamento_id'],
        $_SESSION['usuario_id']
    ]);
    $planejamentoSelecionado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($planejamentoSelecionado) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM trechos
            WHERE planejamento_id = ?
            ORDER BY id
        ");
        $stmt->execute([$_GET['planejamento_id']]);
        $trechos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

/* ===============================
   TRECHO SELECIONADO
================================ */
if (!empty($_GET['trecho_id'])) {
    $stmt = $pdo->prepare("
        SELECT t.*
        FROM trechos t
        JOIN planejamentos p ON p.id = t.planejamento_id
        WHERE t.id = ?
          AND p.usuario_id = ?
          AND t.area_total = 0
    ");
    $stmt->execute([
        $_GET['trecho_id'],
        $_SESSION['usuario_id']
    ]);
    $trechoSelecionado = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ===============================
   SALVAR MEDIÇÃO
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

        if (empty($_POST['trecho_id'])) throw new Exception('Trecho não informado.');
        if (empty($_POST['tipo_pavimento'])) throw new Exception('Selecione o tipo de pavimento.');
        if (empty($_POST['comprimento']) || empty($_POST['largura'])) throw new Exception('Informe comprimento e largura.');
        if (empty($_FILES['croqui']['tmp_name'])) throw new Exception('Croqui é obrigatório.');
        if (empty($_FILES['fotos']['tmp_name'][0])) throw new Exception('Envie ao menos uma foto.');

        $pdo->beginTransaction();

        $trechoId = $_POST['trecho_id'];
        $areaTotal = 0;

        foreach ($_POST['comprimento'] as $i => $c) {
            $l = $_POST['largura'][$i];
            $a = $c * $l;
            $areaTotal += $a;

            $pdo->prepare("
                INSERT INTO pavimento_produzido (trecho_id, comprimento, largura, area)
                VALUES (?, ?, ?, ?)
            ")->execute([$trechoId, $c, $l, $a]);
        }

        $pdo->prepare("
            UPDATE trechos
            SET area_total = ?, tipo_pavimento = ?
            WHERE id = ?
        ")->execute([$areaTotal, $_POST['tipo_pavimento'], $trechoId]);

        /* CROQUI */
        $dirCroqui = __DIR__ . '/../uploads/croquis/';
        if (!is_dir($dirCroqui)) mkdir($dirCroqui, 0775, true);

        $croquiNome = uniqid('croqui_') . '.' . pathinfo($_FILES['croqui']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['croqui']['tmp_name'], $dirCroqui . $croquiNome);

        $pdo->prepare("UPDATE trechos SET croqui = ? WHERE id = ?")
            ->execute([$croquiNome, $trechoId]);

        /* FOTOS */
        $dirFotos = __DIR__ . '/../uploads/fotos/';
        if (!is_dir($dirFotos)) mkdir($dirFotos, 0775, true);

        foreach ($_FILES['fotos']['tmp_name'] as $k => $tmp) {
            $fotoNome = uniqid('foto_') . '.' . pathinfo($_FILES['fotos']['name'][$k], PATHINFO_EXTENSION);
            move_uploaded_file($tmp, $dirFotos . $fotoNome);

            $pdo->prepare("
                INSERT INTO trecho_fotos (trecho_id, arquivo)
                VALUES (?, ?)
            ")->execute([$trechoId, $fotoNome]);
        }

        $pdo->commit();
        header("Location: medicao.php?planejamento_id=".$_GET['planejamento_id']."&ok=1");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Medição | VisionHub Locar</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/visionhub_locar/assets/css/planejador.css?v=1">
</head>
<body>

<div class="app">

<aside class="sidebar">
    <div class="logo">
        <img src="/visionhub_locar/assets/img/farol.png">
        <span>VISION HUB</span>
    </div>
    <nav>
        <a href="/visionhub_locar/planejador/menu.php">📊 Dashboard</a>
        <a href="/visionhub_locar/planejador/planejamento.php">🗂 Planejamento</a>
        <a href="/visionhub_locar/planejador/medicao.php" class="active">📐 Incluir Medições</a>
        <a href="/visionhub_locar/planejador/relatorio.php">📄 Relatório</a>
        <a href="/visionhub_locar/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content">

<div class="topbar">
    <div>
        <h1>Medição por Trecho</h1>
        <span><?= $planejamentoSelecionado ? htmlspecialchars($planejamentoSelecionado['nome']) : 'Selecione um planejamento' ?></span>
    </div>
</div>

<?php if ($erro): ?>
<div class="form-card" style="border:1px solid #ef4444;background:#2a0606;">
    <strong style="color:#fecaca;">Erro:</strong>
    <span style="color:#fee2e2;"><?= htmlspecialchars($erro) ?></span>
</div>
<?php endif; ?>

<div class="form-card">
<form method="get">
<label>Planejamento</label>
<select name="planejamento_id" onchange="this.form.submit()" required>
<option value="">Selecione</option>
<?php foreach ($planejamentos as $p): ?>
<option value="<?= $p['id'] ?>" <?= (!empty($_GET['planejamento_id']) && $_GET['planejamento_id']==$p['id'])?'selected':'' ?>>
<?= htmlspecialchars($p['nome']) ?>
</option>
<?php endforeach; ?>
</select>
</form>

<?php if ($trechos): ?>
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-top:10px;">
<?php foreach ($trechos as $t): ?>
<a
href="<?= $t['area_total']>0
    ? 'editar_medicao.php?trecho_id='.$t['id']
    : 'medicao.php?planejamento_id='.$_GET['planejamento_id'].'&trecho_id='.$t['id']
?>"
style="padding:6px;border-radius:6px;text-align:center;font-size:12px;font-weight:600;text-decoration:none;
background:<?= $t['area_total']>0?'#22c55e':'#9ca3af' ?>;
color:<?= $t['area_total']>0?'#022c22':'#020617' ?>;">
<?= htmlspecialchars($t['pv_montante'].' → '.$t['pv_jusante']) ?><br>
<small><?= $t['area_total']>0?'Editar medição':'Medir trecho' ?></small>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<?php if ($trechoSelecionado): ?>
<form method="post" enctype="multipart/form-data" class="form-card" style="margin-top:20px;">
<h3>
Trecho selecionado:<br>
<strong><?= htmlspecialchars($trechoSelecionado['pv_montante'].' → '.$trechoSelecionado['pv_jusante']) ?></strong>
</h3>

<input type="hidden" name="trecho_id" value="<?= $trechoSelecionado['id'] ?>">

<div class="form-group">
<label>Tipo de Pavimento</label>
<select name="tipo_pavimento" required>
<option value="">Selecione</option>
<option value="paralelepipedo_regular">Paralelepípedo Regular</option>
<option value="paralelepipedo_irregular">Paralelepípedo Irregular</option>
<option value="bloco_concreto">Bloco de Concreto</option>
<option value="asfalto">Asfalto</option>
<option value="asfalto_paralelepipedo">Asfalto + Paralelepípedo</option>
<option value="chao_batido">Chão Batido</option>
<option value="calcada">Calçada</option>
</select>
</div>

<div id="linhas">
<div class="form-row">
<div class="form-group">
<label>Comprimento (m)</label>
<input type="number" step="0.01" name="comprimento[]" oninput="calcular()" required>
</div>
<div class="form-group">
<label>Largura (m)</label>
<input type="number" step="0.01" name="largura[]" oninput="calcular()" required>
</div>
</div>
</div>

<p style="margin-top:10px;font-weight:600;">
Área total: <span id="areaTotal">0.00</span> m²
</p>

<button type="button" class="btn-secondary" onclick="addLinha()">➕ Adicionar Linha</button>

<hr style="margin:15px 0;">

<div class="form-group">
<label>Croqui</label>
<input type="file" name="croqui" required>
</div>

<div class="form-group">
<label>Fotos</label>
<input type="file" name="fotos[]" multiple required>
</div>

<div class="form-actions">
<button type="submit" class="btn-primary">💾 Salvar Medição</button>
</div>

</form>
<?php endif; ?>

</main>
</div>

<script>
function addLinha(){
    const div=document.createElement('div');
    div.className='form-row';
    div.innerHTML=`
    <div class="form-group">
        <label>Comprimento (m)</label>
        <input type="number" step="0.01" name="comprimento[]" oninput="calcular()" required>
    </div>
    <div class="form-group">
        <label>Largura (m)</label>
        <input type="number" step="0.01" name="largura[]" oninput="calcular()" required>
    </div>`;
    document.getElementById('linhas').appendChild(div);
}

function calcular(){
    let total=0;
    document.querySelectorAll('#linhas .form-row').forEach(row=>{
        const c=parseFloat(row.querySelector('[name="comprimento[]"]').value)||0;
        const l=parseFloat(row.querySelector('[name="largura[]"]').value)||0;
        total+=c*l;
    });
    document.getElementById('areaTotal').innerText=total.toFixed(2);
}
</script>

</body>
</html>
