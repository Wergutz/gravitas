<?php
$title = 'Cadastrar Funcionário';
$pageTitle = 'Novo Funcionário';
$pageSubtitle = 'Todos os campos são obrigatórios';

$erro = $_GET['erro'] ?? null;

function opcao($v) {
    return "<option value='$v'>" . ($v==1?'Apto':($v==2?'Inapto':'Não se aplica')) . "</option>";
}

ob_start();
?>

<?php if ($erro === 'campos'): ?>
    <div class="card">
        <span class="badge badge-danger">Preencha todos os campos.</span>
    </div>
<?php endif; ?>

<form method="post" action="<?= APP_BASE ?>/funcionarios/salvar" class="card">

<?php
$inputs = [
    'nome'=>'Nome',
    'cpf'=>'CPF',
    'empresa'=>'Empresa',
    'funcao'=>'Função',
    'salario'=>'Salário'
];

foreach ($inputs as $n=>$l):
?>
<div class="campo">
    <label><?= $l ?></label>
    <input name="<?= $n ?>" required>
</div>
<?php endforeach; ?>

<?php
$checks = [
 'aso'=>'ASO','nr06'=>'NR06','nr10'=>'NR10','nr11'=>'NR11','nr12'=>'NR12',
 'nr18'=>'NR18','nr20'=>'NR20','nr23'=>'NR23','nr33'=>'NR33','nr35'=>'NR35',
 'integracao_corsan'=>'Integração CORSAN','sertras'=>'SERTRAS'
];

foreach ($checks as $n=>$l):
?>
<div class="campo">
    <label><?= $l ?></label>
    <select name="<?= $n ?>" required>
        <option value="">Selecione</option>
        <?= opcao(1) ?>
        <?= opcao(2) ?>
        <?= opcao(3) ?>
    </select>
</div>
<?php endforeach; ?>

<div class="form-actions">
    <button class="btn btn-pri">Salvar</button>
    <a href="<?= APP_BASE ?>/funcionarios" class="btn btn-sec">Cancelar</a>
</div>

</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
