<?php
$title = 'Cadastrar Equipamento Pesado';
$pageTitle = 'Equipamentos Pesados';
$pageSubtitle = 'Cadastro manual';

ob_start();
?>

<div class="form-card">

<form method="post" action="<?= APP_BASE ?>/equipamentos-pesados/salvar">

    <!-- 1. Tipo -->
    <div class="form-group">
        <label>Tipo de Equipamento</label>
        <input type="text" name="tipo" list="tipos-pesados" required placeholder="Digite ou selecione">
        <datalist id="tipos-pesados">
            <option value="Retroescavadeira">
            <option value="Escavadeira">
            <option value="Caçamba">
            <option value="Guindaste hidráulico">
        </datalist>
    </div>

    <!-- 2. Placa -->
    <div class="form-group">
        <label>Placa</label>
        <input type="text" name="placa">
    </div>

    <!-- 3. Modelo -->
    <div class="form-group">
        <label>Modelo</label>
        <input type="text" name="modelo" required>
    </div>

    <!-- 4. Fabricante -->
    <div class="form-group">
        <label>Fabricante</label>
        <input type="text" name="fabricante">
    </div>

    <!-- 5. Ano -->
    <div class="form-group">
        <label>Ano</label>
        <input type="number" name="ano">
    </div>

    <!-- 6. Proprietário -->
    <div class="form-group">
        <label>Proprietário</label>
        <input type="text" name="proprietario">
    </div>

    <!-- 7. Combustível -->
    <div class="form-group">
        <label>Combustível</label>
        <select name="combustivel">
            <option value="DIESEL">Diesel</option>
            <option value="GASOLINA">Gasolina</option>
            <option value="OUTRO">Outro</option>
        </select>
    </div>

    <div class="form-actions">
        <button class="btn-primary">Salvar</button>
        <a href="<?= APP_BASE ?>/equipamentos-pesados" class="btn-secondary">Cancelar</a>
    </div>

</form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
