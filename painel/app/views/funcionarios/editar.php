<?php
$title = 'Editar Funcionário';
$pageTitle = 'Editar Funcionário';
$pageSubtitle = $funcionario['nome'];

ob_start();
?>

<div class="form-card">

<form method="post" action="<?= APP_BASE ?>/funcionarios/atualizar">

<input type="hidden" name="id" value="<?= $funcionario['id'] ?>">

<div class="form-group">
    <label>Nome</label>
    <input type="text" name="nome" required value="<?= $funcionario['nome'] ?>">
</div>

<div class="form-group">
    <label>Empresa</label>
    <input type="text" name="empresa" required value="<?= $funcionario['empresa'] ?>">
</div>

<div class="form-group">
    <label>Função</label>
    <input type="text" name="funcao" required value="<?= $funcionario['funcao'] ?>">
</div>

<div class="form-group">
    <label>Salário</label>
    <input type="number" step="0.01" name="salario" required value="<?= $funcionario['salario'] ?>">
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
    'integracao_corsan' => 'Integração CORSAN',
    'sertras' => 'SERTRAS'
];
?>

<?php foreach ($campos as $campo => $label): ?>
<div class="form-group">
    <label><?= $label ?></label>
    <select name="<?= $campo ?>">
        <option value="1" <?= $funcionario[$campo]==1?'selected':'' ?>>Apto</option>
        <option value="2" <?= $funcionario[$campo]==2?'selected':'' ?>>Inapto</option>
        <option value="3" <?= $funcionario[$campo]==3?'selected':'' ?>>N/A</option>
    </select>
</div>
<?php endforeach; ?>

<div class="form-actions">
    <button class="btn-primary">Salvar</button>
    <a href="<?= APP_BASE ?>/funcionarios" class="btn-secondary">Cancelar</a>
</div>

</form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
