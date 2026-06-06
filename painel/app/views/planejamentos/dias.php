<?php
$title = 'Planejamento por Dias';
$pageTitle = 'Dias de Produção';
$pageSubtitle = '';

ob_start();
?>

<div class="form-card">
<form method="post" action="<?= APP_BASE ?>/planejamentos/salvar-dia">

    <input type="hidden" name="planejamento_id" value="<?= $_GET['id'] ?>">

    <div class="form-group">
        <label>Data</label>
        <input type="date" name="data" required>
    </div>

    <div class="form-group">
        <label>Produção</label>
        <select name="producao">
            <option value="1">Sim</option>
            <option value="0">Não</option>
        </select>
    </div>

    <div class="form-group">
        <label>Motivo (se não produziu)</label>
        <textarea name="motivo"></textarea>
    </div>

    <button class="btn-info btn-sm">Adicionar Dia</button>
</form>
</div>

<div class="form-card">
<table class="table">
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
