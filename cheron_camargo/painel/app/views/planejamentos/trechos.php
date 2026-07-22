<?php
$title = 'Trechos';
$pageTitle = 'Trechos por Dia';
$pageSubtitle = 'Detalhamento do planejamento';

ob_start();
?>

<div class="card">
<form method="post" action="<?= APP_BASE ?>/planejamentos/salvar-trechos">

<?php foreach ($dias as $d): ?>
    <h3 class="dia-titulo"><?= date('d/m/Y', strtotime($d['data'])) ?></h3>

    <input type="hidden" name="trechos[][dia_id]" value="<?= $d['id'] ?>">

    <div class="campo">
        <label>PV Montante</label>
        <input name="trechos[][pv_montante]">
    </div>

    <div class="campo">
        <label>PV Juzante</label>
        <input name="trechos[][pv_juzante]">
    </div>

    <div class="campo">
        <label>Comprimento (m)</label>
        <input type="number" step="0.01" name="trechos[][comprimento]">
    </div>

    <div class="campo">
        <label>Ramais</label>
        <input type="number" name="trechos[][ramais]">
    </div>
<?php endforeach; ?>

<div class="form-actions">
    <button class="btn btn-pri btn-sm">Finalizar Planejamento</button>
</div>

</form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
