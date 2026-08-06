<?php
require_once __DIR__ . '/../../helpers/csrf.php';

$title     = 'Novo Material';
$pageTitle = 'Novo Material';
$pageSubtitle = 'Adicionar ao catálogo';

ob_start();
?>

<div class="card" style="max-width:600px;">
    <form method="post" action="<?= APP_BASE ?>/materiais/salvar">
        <?= csrf_input() ?>

        <div class="form-grid col2">
            <div class="campo">
                <label>Código</label>
                <input type="text" name="codigo" placeholder="Ex: MAT-001">
            </div>
            <div class="campo">
                <label>Unidade</label>
                <select name="unidade">
                    <option value="un">un (unidade)</option>
                    <option value="m">m (metro)</option>
                    <option value="m2">m² (metro quadrado)</option>
                    <option value="m3">m³ (metro cúbico)</option>
                    <option value="kg">kg (quilograma)</option>
                    <option value="l">l (litro)</option>
                    <option value="cj">cj (conjunto)</option>
                </select>
            </div>
        </div>

        <div class="campo">
            <label>Nome do Material <span style="color:var(--erro)">*</span></label>
            <input type="text" name="nome" required placeholder="Descrição do material">
        </div>

        <div class="campo" style="margin-bottom:16px;">
            <label>Estoque Mínimo</label>
            <input type="text" name="estoque_minimo" value="0" placeholder="0">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-pri">Salvar material</button>
            <a href="<?= APP_BASE ?>/materiais" class="btn btn-sec">Cancelar</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
