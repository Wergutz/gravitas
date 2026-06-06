<?php
$title = 'Cadastrar Equipamento Leve';
$pageTitle = 'Equipamentos Leves';
$pageSubtitle = 'Cadastro manual';

ob_start();
?>

<div class="form-card">

<form method="post" action="<?= APP_BASE ?>/equipamentos-leves/salvar">

    <!-- 1. Tipo -->
    <div class="form-group">
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
    <div class="form-group">
        <label>Referência</label>
        <input type="text" name="referencia" required>
    </div>

    <!-- 3. Modelo -->
    <div class="form-group">
        <label>Modelo</label>
        <input type="text" name="modelo" required>
    </div>

    <!-- 4. Ano -->
    <div class="form-group">
        <label>Ano</label>
        <input type="number" name="ano">
    </div>

    <!-- 5. Proprietário -->
    <div class="form-group">
        <label>Proprietário</label>
        <input type="text" name="proprietario">
    </div>

    <!-- 6. Combustível -->
    <div class="form-group">
        <label>Combustível</label>
        <select name="combustivel">
            <option value="GASOLINA">Gasolina</option>
            <option value="DIESEL">Diesel</option>
            <option value="ELETRICO">Elétrico</option>
            <option value="OUTRO">Outro</option>
        </select>
    </div>

    <div class="form-actions">
        <button class="btn-primary">Salvar</button>
        <a href="<?= APP_BASE ?>/equipamentos-leves" class="btn-secondary">Cancelar</a>
    </div>

</form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
