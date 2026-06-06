<?php
$title = 'Cadastrar Equipamento Pesado';
$pageTitle = 'Equipamentos Pesados';
$pageSubtitle = 'Cadastro manual';

ob_start();
?>

<div class="card">

<form method="post" action="<?= APP_BASE ?>/equipamentos-pesados/salvar">

    <!-- 1. Tipo -->
    <div class="campo">
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
    <div class="campo">
        <label>Placa</label>
        <input type="text" name="placa">
    </div>

    <!-- 3. Modelo -->
    <div class="campo">
        <label>Modelo</label>
        <input type="text" name="modelo" required>
    </div>

    <!-- 4. Fabricante -->
    <div class="campo">
        <label>Fabricante</label>
        <input type="text" name="fabricante">
    </div>

    <!-- 5. Ano -->
    <div class="campo">
        <label>Ano</label>
        <input type="number" name="ano">
    </div>

    <!-- 6. Proprietário -->
    <div class="campo">
        <label>Proprietário</label>
        <input type="text" name="proprietario">
    </div>

    <!-- 7. Combustível -->
    <div class="campo">
        <label>Combustível</label>
        <select name="combustivel">
            <option value="DIESEL">Diesel</option>
            <option value="GASOLINA">Gasolina</option>
            <option value="OUTRO">Outro</option>
        </select>
    </div>

    <div class="form-actions">
        <button class="btn btn-pri">Salvar</button>
        <a href="<?= APP_BASE ?>/equipamentos-pesados" class="btn btn-sec">Cancelar</a>
    </div>

</form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
