<?php
$title = 'Adicionar Funcionário';
$pageTitle = 'Funcionário';
$pageSubtitle = 'Adicionar funcionário à equipe';

ob_start();
?>

<div class="form-card">
<form method="post" action="<?= APP_BASE ?>/equipes/funcionarios/salvar">

<input type="hidden" name="equipe_id" value="<?= $equipe_id ?>">

<div class="form-group">
    <label>Nome</label>
    <input type="text" name="nome" required>
</div>

<div class="form-group">
    <label>Função</label>
    <input type="text" name="funcao" required>
</div>

<?php
$campos = [
    'aso' => 'ASO',
    'nr06' => 'NR06',
    'nr10' => 'NR10',
    'nr11' => 'NR11',
    'nr12' => 'NR12',
    'nr18' => 'NR18',
    'nr20' => 'NR20',
    'nr23' => 'NR23',
    'nr33' => 'NR33',
    'nr35' => 'NR35',
    'sertras' => 'SERTRAS'
];
?>

<?php foreach ($campos as $campo => $label): ?>
<div class="form-group">
    <label><?= $label ?></label>
    <select name="<?= $campo ?>">
        <option value="1">Apto</option>
        <option value="2">Não apto</option>
        <option value="3">Não se aplica</option>
    </select>
</div>
<?php endforeach; ?>

<div class="form-actions">
    <button class="btn-primary">Salvar</button>
    <a href="<?= APP_BASE ?>/equipes/funcionarios?equipe_id=<?= $equipe_id ?>" class="btn-secondary">
        Cancelar
    </a>
</div>

</form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/planejador.php';
