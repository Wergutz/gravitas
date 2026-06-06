<?php
$title = 'Editar Equipamento Leve';
$pageTitle = 'Editar Equipamento Leve';
$pageSubtitle = 'Atualize os dados do equipamento';

$erro = $_GET['erro'] ?? null;

ob_start();
?>

<?php if ($erro === 'campos'): ?>
    <div class="card">
        <span class="badge badge-danger">Preencha todos os campos.</span>
    </div>
<?php endif; ?>

<form method="post" action="<?= APP_BASE ?>/equipamentos-leves/atualizar" class="card">

    <input type="hidden" name="id" value="<?= $equipamento['id'] ?>">

    <div class="campo">
        <label>Referência</label>
        <input type="text" name="referencia" value="<?= htmlspecialchars($equipamento['referencia']) ?>" required>
    </div>

    <div class="campo">
        <label>Fabricante</label>
        <input type="text" name="fabricante" value="<?= htmlspecialchars($equipamento['fabricante']) ?>" required>
    </div>

    <div class="campo">
        <label>Modelo</label>
        <input type="text" name="modelo" value="<?= htmlspecialchars($equipamento['modelo']) ?>" required>
    </div>

    <div class="campo">
        <label>Ano</label>
        <input type="number" name="ano" value="<?= $equipamento['ano'] ?>" required>
    </div>

    <div class="campo">
        <label>Proprietário</label>
        <input type="text" name="proprietario" value="<?= htmlspecialchars($equipamento['proprietario']) ?>" required>
    </div>

    <div class="campo">
        <label>Combustível</label>
        <select name="combustivel" required>
            <?php foreach (['GASOLINA','DIESEL','ELETRICO','OUTRO'] as $c): ?>
                <option value="<?= $c ?>" <?= $equipamento['combustivel'] === $c ? 'selected' : '' ?>>
                    <?= $c ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-actions">
        <button class="btn btn-pri">Salvar</button>
        <a href="<?= APP_BASE ?>/equipamentos-leves" class="btn btn-sec">Cancelar</a>
    </div>

</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
