<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/database.php';

auth_required([3]);

$erro = null;

/* ===============================
   TRECHO
================================ */
if (empty($_GET['trecho_id'])) {
    die('Trecho não informado');
}

$stmt = $pdo->prepare("
    SELECT t.*, p.nome AS planejamento
    FROM trechos t
    JOIN planejamentos p ON p.id = t.planejamento_id
    WHERE t.id = ? AND p.usuario_id = ?
");
$stmt->execute([$_GET['trecho_id'], $_SESSION['usuario_id']]);
$trecho = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trecho) {
    die('Trecho não encontrado');
}

/* ===============================
   FOTOS DO TRECHO
================================ */
$stmt = $pdo->prepare("
    SELECT arquivo
    FROM trecho_fotos
    WHERE trecho_id = ?
");
$stmt->execute([$trecho['id']]);
$fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   MEDIÇÕES EXISTENTES
================================ */
$stmt = $pdo->prepare("
    SELECT *
    FROM pavimento_produzido
    WHERE trecho_id = ?
");
$stmt->execute([$trecho['id']]);
$medicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   SALVAR EDIÇÃO
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

        if (empty($_POST['tipo_pavimento'])) {
            throw new Exception('Selecione o tipo de pavimento.');
        }

        $pdo->beginTransaction();

        /* limpa medições antigas */
        $pdo->prepare("DELETE FROM pavimento_produzido WHERE trecho_id = ?")
            ->execute([$trecho['id']]);

        $areaTotal = 0;
        foreach ($_POST['comprimento'] as $i => $c) {
            $l = $_POST['largura'][$i];
            $a = $c * $l;
            $areaTotal += $a;

            $pdo->prepare("
                INSERT INTO pavimento_produzido
                (trecho_id, comprimento, largura, area)
                VALUES (?, ?, ?, ?)
            ")->execute([$trecho['id'], $c, $l, $a]);
        }

        $pdo->prepare("
            UPDATE trechos
            SET area_total = ?, tipo_pavimento = ?
            WHERE id = ?
        ")->execute([$areaTotal, $_POST['tipo_pavimento'], $trecho['id']]);

        /* novas fotos (opcional) */
        if (!empty($_FILES['fotos']['tmp_name'][0])) {
            $dir = __DIR__ . '/../uploads/fotos/';
            if (!is_dir($dir)) mkdir($dir, 0775, true);

            foreach ($_FILES['fotos']['tmp_name'] as $k => $tmp) {
                $nome = uniqid('foto_') . '.' . pathinfo($_FILES['fotos']['name'][$k], PATHINFO_EXTENSION);
                move_uploaded_file($tmp, $dir . $nome);

                $pdo->prepare("
                    INSERT INTO trecho_fotos (trecho_id, arquivo)
                    VALUES (?, ?)
                ")->execute([$trecho['id'], $nome]);
            }
        }

        $pdo->commit();
        header("Location: medicao.php?planejamento_id=".$trecho['planejamento_id']."&ok=1");
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
<title>Editar Medição | VisionHub Locar</title>
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
        <a href="/visionhub_locar/planejador/medicao.php" class="active">📐 Medições</a>
        <a href="/visionhub_locar/planejador/relatorio.php">📄 Relatório</a>
        <a href="/visionhub_locar/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content">

<div class="topbar">
    <div>
        <h1>Editar Medição</h1>
        <span><?= htmlspecialchars($trecho['pv_montante'].' → '.$trecho['pv_jusante']) ?></span>
    </div>
</div>

<?php if ($erro): ?>
<div class="form-card" style="border:1px solid #ef4444;background:#2a0606;">
    <strong style="color:#fecaca;">Erro:</strong>
    <span style="color:#fee2e2;"><?= htmlspecialchars($erro) ?></span>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form-card">

<h3>📸 Fotos do Trecho</h3>

<?php if ($fotos): ?>
<div style="display:flex;flex-wrap:wrap;gap:10px;">
<?php foreach ($fotos as $f): ?>
<img
    src="/visionhub_locar/uploads/fotos/<?= htmlspecialchars($f['arquivo']) ?>"
    style="width:160px;border-radius:6px;border:1px solid #374151;">
<?php endforeach; ?>
</div>
<?php else: ?>
<p style="color:#9ca3af;">Nenhuma foto cadastrada.</p>
<?php endif; ?>

<hr style="margin:15px 0;">

<div class="form-group">
<label>Tipo de Pavimento</label>
<select name="tipo_pavimento" required>
<option value="">Selecione</option>
<?php
$tipos = [
    'paralelepipedo_regular'=>'Paralelepípedo Regular',
    'paralelepipedo_irregular'=>'Paralelepípedo Irregular',
    'bloco_concreto'=>'Bloco de Concreto',
    'asfalto'=>'Asfalto',
    'asfalto_paralelepipedo'=>'Asfalto + Paralelepípedo',
    'chao_batido'=>'Chão Batido',
    'calcada'=>'Calçada'
];
foreach ($tipos as $k=>$v):
?>
<option value="<?= $k ?>" <?= $trecho['tipo_pavimento']===$k?'selected':'' ?>>
<?= $v ?>
</option>
<?php endforeach; ?>
</select>
</div>

<hr>

<h3>📐 Medições</h3>

<div id="linhas">
<?php foreach ($medicoes as $m): ?>
<div class="form-row">
    <div class="form-group">
        <label>Comprimento</label>
        <input type="number" step="0.01" name="comprimento[]" value="<?= $m['comprimento'] ?>" required>
    </div>
    <div class="form-group">
        <label>Largura</label>
        <input type="number" step="0.01" name="largura[]" value="<?= $m['largura'] ?>" required>
    </div>
</div>
<?php endforeach; ?>
</div>

<button type="button" class="btn-secondary" onclick="addLinha()">➕ Linha</button>

<hr>

<div class="form-group">
<label>Adicionar novas fotos</label>
<input type="file" name="fotos[]" multiple>
</div>

<div class="form-actions">
<button class="btn-primary">💾 Salvar Correção</button>
</div>

</form>

</main>
</div>

<script>
function addLinha(){
    const div=document.createElement('div');
    div.className='form-row';
    div.innerHTML=`
    <div class="form-group">
        <label>Comprimento</label>
        <input type="number" step="0.01" name="comprimento[]" required>
    </div>
    <div class="form-group">
        <label>Largura</label>
        <input type="number" step="0.01" name="largura[]" required>
    </div>`;
    document.getElementById('linhas').appendChild(div);
}
</script>

</body>
</html>
