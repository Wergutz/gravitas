<?php
$title = 'Cadastrar Equipamento Leve';
$pageTitle = 'Equipamentos Leves';
$pageSubtitle = 'Cadastro manual';

ob_start();
?>

<div class="card">

<form method="post" action="<?= APP_BASE ?>/equipamentos-leves/salvar">

    <!-- 1. Tipo -->
    <div class="campo">
        <label>Tipo de Equipamento</label>
        <input type="text" name="tipo" list="tipos-leves" required placeholder="Digite ou selecione">
        <datalist id="tipos-leves">
            <option value="Compactador">
            <option value="Placa Vibratória">
            <option value="Motobomba">
            <option value="Cortadora de Asfalto">
            <option value="Martelete elétrico">
        </datalist>
    </div>

    <!-- 2. Referência -->
    <div class="campo">
        <label>Referência</label>
        <input type="text" name="referencia" required>
    </div>

    <!-- 3. Modelo -->
    <div class="campo">
        <label>Modelo</label>
        <input type="text" name="modelo" required>
    </div>

    <!-- 4. Ano -->
    <div class="campo">
        <label>Ano</label>
        <input type="number" name="ano">
    </div>

    <!-- 5. Proprietário -->
    <div class="campo">
        <label>Proprietário</label>
        <input type="text" name="proprietario">
    </div>

    <!-- 6. Combustível -->
    <div class="campo">
        <label>Combustível</label>
        <select name="combustivel">
            <option value="GASOLINA">Gasolina</option>
            <option value="DIESEL">Diesel</option>
            <option value="ELETRICO">Elétrico</option>
            <option value="OUTRO">Outro</option>
        </select>
    </div>

    <div class="form-actions">
        <button class="btn btn-pri">Salvar</button>
        <a href="<?= APP_BASE ?>/equipamentos-leves" class="btn btn-sec">Cancelar</a>
    </div>

</form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
