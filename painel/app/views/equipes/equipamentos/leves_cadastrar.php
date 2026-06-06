<?php
$title = 'Adicionar Equipamento Leve';
$pageTitle = 'Equipamento Leve';

ob_start();
?>

<div class="form-card">
<form method="post" action="<?= APP_BASE ?>/equipes/equipamentos/leves/salvar">

<input type="hidden" name="equipe_id" value="<?= $equipe_id ?>">

<div class="form-group">
    <label>Equipamento</label>
    <select name="equipamento_id" required>
        <?php foreach ($equipamentos as $e): ?>
            <option value="<?= $e['id'] ?>">
                <?= $e['tipo'] ?> — <?= $e['modelo'] ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label>Quantidade</label>
    <input type="number" name="quantidade" value="1" min="1">
</div>

<div class="form-actions">
    <button class="btn-primary">Salvar</button>
    <a href="<?= APP_BASE ?>/equipes/equipamentos/leves?equipe_id=<?= $equipe_id ?>" class="btn-secondary">
        Cancelar
    </a>
</div>

</form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../layouts/planejador.php';
