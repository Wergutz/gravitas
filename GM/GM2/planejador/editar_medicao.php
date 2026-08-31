<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/asset.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/tipos_pavimento.php';
require_once __DIR__ . '/../app/helpers/validacao_midia.php';
require_once __DIR__ . '/../app/helpers/midia_url.php';

auth_required([3, 4]);

$erro      = null;
$recusados = [];
$avisos    = [];

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
    WHERE t.id = ?
");
$stmt->execute([$_GET['trecho_id']]);
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

        $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);

        /* --- fila das fotos novas (opcional nesta tela) ---------------- */
        $fila = [];
        $nFotos = count($_FILES['fotos']['name'] ?? []);
        for ($i = 0; $i < $nFotos; $i++) {
            if (($_FILES['fotos']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            $fila[] = ['tipo' => 'foto', 'f' => [
                'name'     => $_FILES['fotos']['name'][$i],
                'tmp_name' => $_FILES['fotos']['tmp_name'][$i],
                'size'     => $_FILES['fotos']['size'][$i],
                'error'    => $_FILES['fotos']['error'][$i],
            ]];
        }

        /* --- confere tudo antes de gravar qualquer coisa ----------------
           Com MU_VALIDACAO_BLOQUEIA em false nada é recusado. */
        foreach ($fila as $item) {
            $v = mu_validar_midia(
                $item['f']['tmp_name'], (string) $item['f']['name'],
                $item['tipo'], (int) $trecho['id'], null, $pdo
            );

            if (!$v['ok']) {
                $recusados[] = ['arquivo' => $item['f']['name'], 'erros' => $v['erros']];
            }

            foreach ($v['avisos'] as $a) {
                $avisos[] = ['arquivo' => $item['f']['name'], 'aviso' => $a];
            }
        }

        /* No modo bloqueante, um reprovado e nada muda — nem a correção
           das medidas. Fora dele, segue direto. */
        if ($recusados) {
            throw new Exception('Correção recusada: há arquivo sem valor probatório.');
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

        foreach ($fila as $item) {
            $reg = mu_arquivar_midia(
                $item['f'], (int) $trecho['id'], 'foto', null, $usuarioId ?: null, $pdo
            );
            $pdo->prepare("INSERT INTO trecho_fotos (trecho_id, arquivo) VALUES (?, ?)")
                ->execute([$trecho['id'], $reg['caminho']]);
        }

        $pdo->commit();
        header("Location: medicao.php?planejamento_id=".$trecho['planejamento_id']."&ok=1");
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Editar Medição | GM SERVIÇOS</title>
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
    <nav>
        <a href="/GM/GM2/planejador/menu.php">📊 Dashboard</a>
        <a href="/GM/GM2/planejador/medicao.php" class="active">📐 Medições</a>
        <a href="/GM/GM2/planejador/relatorio.php">📄 Relatório</a>
        <a href="/GM/GM2/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content">

<div class="topbar">
    <div>
        <h1>Editar Medição</h1>
        <span><?= htmlspecialchars($trecho['pv_montante'].' → '.$trecho['pv_jusante']) ?></span>
    </div>
</div>

<?php if ($erro && !$recusados): ?>
<div class="form-card" style="border:1px solid #ef4444;background:#2a0606;">
    <strong style="color:#fecaca;">Erro:</strong>
    <span style="color:#fee2e2;"><?= htmlspecialchars($erro) ?></span>
</div>
<?php endif; ?>

<?php if ($recusados): ?>
<div class="form-card" style="border:1px solid #ef4444;background:#2a0606;">
    <h3 style="color:#fecaca;margin-bottom:6px;">Correção recusada</h3>
    <p style="color:#fee2e2;font-size:14px;margin-bottom:14px;">
        Nada foi alterado. Corrija os arquivos abaixo e envie de novo.
    </p>
    <?php foreach ($recusados as $r): ?>
        <div style="margin-bottom:14px;padding-left:12px;border-left:3px solid #ef4444;">
            <div style="color:#fff;font-weight:600;font-size:14px;word-break:break-all;">
                <?= htmlspecialchars($r['arquivo']) ?>
            </div>
            <?php foreach ($r['erros'] as $e): ?>
                <div style="margin-top:8px;">
                    <div style="color:#fecaca;font-weight:600;font-size:13px;">
                        <?= htmlspecialchars($e['titulo']) ?>
                    </div>
                    <div style="color:#e5e7eb;font-size:13px;line-height:1.5;">
                        <?= htmlspecialchars($e['como_corrigir']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form-card">

<h3>📸 Fotos do Trecho</h3>

<?php if ($fotos): ?>
<div style="display:flex;flex-wrap:wrap;gap:10px;">
<?php foreach ($fotos as $f): ?>
<a href="<?= htmlspecialchars(mu_url_midia($f['arquivo'], 'foto')) ?>" target="_blank">
<img
    src="<?= htmlspecialchars(mu_url_thumb($f['arquivo'], 440)) ?>"
    alt="Foto do trecho <?= htmlspecialchars($trecho['pv_montante'].' → '.$trecho['pv_jusante']) ?>"
    loading="lazy" decoding="async"
    onerror="this.onerror=null;this.src='<?= htmlspecialchars(mu_url_midia($f['arquivo'], 'foto')) ?>';"
    style="width:160px;border-radius:6px;border:1px solid #374151;">
</a>
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
<?php foreach (TIPOS_PAVIMENTO as $k=>$v): ?>
<option value="<?= $k ?>" <?= $trecho['tipo_pavimento']===$k?'selected':'' ?>>
<?= htmlspecialchars($v) ?>
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
<div class="mu-envio">
    <label class="mu-envio-op mu-envio-camera">
        📷 Tirar foto agora
        <input type="file" name="fotos[]" accept="image/*" capture="environment" multiple>
    </label>
    <label class="mu-envio-op">
        🖼️ Enviar da galeria
        <input type="file" name="fotos[]" accept="image/*" multiple>
    </label>
</div>
<small class="mu-dica">
    <strong>Prefira “Enviar da galeria”</strong>, escolhendo a foto tirada pelo aplicativo
    de câmera do celular — é o caminho que preserva data e localização.
</small>
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


<script src="<?= mu_asset('/assets/js/validacao-midia.js') ?>"></script>
<script>
/* Coordenada do município deste trecho — mesma referência do servidor. */
var MU_OBRA = <?= json_encode(
    (function () use ($trecho) {
        $c = mu_coordenada_municipio($trecho['cidade'] ?? '');
        if (!$c) return null;
        return [
            'obraLat'  => $c['lat'],
            'obraLon'  => $c['lon'],
            'raioKm'   => $c['raio_km'],
            'obraNome' => $c['nome'],
            'obraPontos' => array_map(fn($h) => [
                'lat'  => $h['lat'],
                'lon'  => $h['lon'],
                'nome' => $h['nome'] . ' / ' . $h['uf'],
            ], $c['homonimos'] ?? []),
        ];
    })(),
    JSON_UNESCAPED_UNICODE
) ?>;

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input[name="fotos[]"]').forEach(function (inp) {
        if (window.MUValidacao) { MUValidacao.ligar(inp, MU_OBRA || {}); }
        inp.addEventListener('change', function () {
            var box = inp.closest('.mu-envio-op');
            var n = inp.files ? inp.files.length : 0;
            if (!box) return;
            var r = box.querySelector('.mu-envio-rotulo');
            if (!r) { r = document.createElement('span'); r.className = 'mu-envio-rotulo'; box.appendChild(r); }
            box.classList.toggle('tem-arquivo', n > 0);
            r.textContent = n > 0 ? (n === 1 ? '\u2014 1 arquivo' : '\u2014 ' + n + ' arquivos') : '';
        });
    });
});
</script>
</body>
</html>
