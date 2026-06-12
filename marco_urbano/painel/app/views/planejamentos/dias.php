<?php
$title = 'Planejamento por Dias';
$pageTitle = 'Dias de Produção';
$pageSubtitle = '';

ob_start();
?>

<div class="card">
<form method="post" action="<?= APP_BASE ?>/planejamentos/salvar-dia">
<?= csrf_input() ?>

    <input type="hidden" name="planejamento_id" value="<?= (int)($_GET['id'] ?? 0) ?>">

    <div class="campo">
        <label>Data</label>
        <input type="date" name="data" required>
    </div>

    <div class="campo">
        <label>Produção</label>
        <select name="producao">
            <option value="1">Sim</option>
            <option value="0">Não</option>
        </select>
    </div>

    <div class="campo">
        <label>Motivo (se não produziu)</label>
        <textarea name="motivo"></textarea>
    </div>

    <button class="btn btn-sec btn-sm">Adicionar Dia</button>
</form>
</div>

<div class="card">
<table class="">
<thead>
<tr><th>Data</th><th>Produção</th></tr>
</thead>
<tbody>
<?php foreach ($dias as $d): ?>
<tr>
    <td><?= date('d/m/Y', strtotime($d['data'])) ?></td>
    <td><?= $d['producao'] ? 'Sim' : 'Não' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
