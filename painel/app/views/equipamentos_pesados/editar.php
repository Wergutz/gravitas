<?php
$title = 'Editar Equipamento Pesado';
$pageTitle = 'Editar Equipamento Pesado';
$pageSubtitle = 'Atualize os dados do equipamento';

$erro = $_GET['erro'] ?? null;

ob_start();
?>

<?php if ($erro === 'campos'): ?>
    <div class="form-card">
        <span class="badge badge-danger">
            Preencha todos os campos.
        </span>
    </div>
<?php endif; ?>

<form method="post" action="<?= APP_BASE ?>/equipamentos-pesados/atualizar" class="form-card">

    <input type="hidden" name="id" value="<?= $equipamento['id'] ?>">

    <div class="form-group">
        <label>Tipo</label>
        <select name="tipo" required>
            <?php
            $tipos = ['RETROESCAVADEIRA','ESCAVADEIRA','CACAMBA','OUTRO'];
            foreach ($tipos as $t):
            ?>
                <option value="<?= $t ?>" <?= $equipamento['tipo'] === $t ? 'selected' : '' ?>>
                    <?= $t ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Placa</label>
        <input type="text" name="placa" value="<?= htmlspecialchars($equipamento['placa']) ?>" required>
    </div>

    <div class="form-group">
        <label>Fabricante</label>
        <input type="text" name="fabricante" value="<?= htmlspecialchars($equipamento['fabricante']) ?>" required>
    </div>

    <div class="form-group">
        <label>Modelo</label>
        <input type="text" name="modelo" value="<?= htmlspecialchars($equipamento['modelo']) ?>" required>
    </div>

    <div class="form-group">
        <label>Ano</label>
        <input type="number" name="ano" value="<?= $equipamento['ano'] ?>" required>
    </div>

    <div class="form-group">
        <label>Proprietário</label>
        <input type="text" name="proprietario" value="<?= htmlspecialchars($equipamento['proprietario']) ?>" required>
    </div>

    <div class="form-group">
        <label>Combustível</label>
        <select name="combustivel" required>
            <?php
            $comb = ['DIESEL','GASOLINA','OUTRO'];
            foreach ($comb as $c):
            ?>
                <option value="<?= $c ?>" <?= $equipamento['combustivel'] === $c ? 'selected' : '' ?>>
                    <?= $c ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-actions">
        <button class="btn-primary">Salvar</button>
        <a href="<?= APP_BASE ?>/equipamentos-pesados" class="btn-secondary">Cancelar</a>
    </div>

</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
