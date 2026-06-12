<?php
$title = 'Adicionar Equipamento Pesado';
$pageTitle = 'Equipamento Pesado';

ob_start();
?>

<div class="form-card">
<form method="post" action="<?= APP_BASE ?>/equipes/equipamentos/pesados/salvar">
<?= csrf_input() ?>
<input type="hidden" name="equipe_id" value="<?= (int)$equipe_id ?>">

<div class="form-group">
    <label>Equipamento</label>
    <select name="equipamento_id" required>
        <?php foreach ($equipamentos as $e): ?>
            <option value="<?= $e['id'] ?>">
                <?= $e['tipo'] ?> — <?= $e['modelo'] ?> (<?= $e['placa'] ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label>Operador</label>
    <input type="text" name="operador">
</div>

<div class="form-actions">
    <button class="btn-primary">Salvar</button>
    <a href="<?= APP_BASE ?>/equipes/equipamentos/pesados?equipe_id=<?= $equipe_id ?>" class="btn-secondary">
        Cancelar
    </a>
</div>

</form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../layouts/planejador.php';
