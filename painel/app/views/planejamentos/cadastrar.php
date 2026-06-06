<?php
$title = 'Novo Planejamento';
$pageTitle = 'Novo Planejamento';
$pageSubtitle = 'Cadastro do planejamento';

ob_start();
?>

<div class="form-card">
<form method="post" action="<?= APP_BASE ?>/planejamentos/salvar">

    <div class="form-group">
        <label>Equipe</label>
        <select name="equipe_id" required>
            <option value="">Selecione</option>
            <?php foreach ($equipes as $e): ?>
                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Data de Execução</label>
        <input type="date" name="data_execucao" required>
    </div>

    <div class="form-group"><label>Macro</label><input name="macro"></div>
    <div class="form-group"><label>Medição</label><input name="medicao"></div>
    <div class="form-group"><label>Cidade</label><input name="cidade"></div>
    <div class="form-group"><label>Contrato</label><input name="contrato"></div>
    <div class="form-group"><label>Bacia</label><input name="bacia"></div>
    <div class="form-group"><label>Trecho</label><input name="trecho"></div>
    <div class="form-group"><label>PV Montante</label><input name="pv_montante"></div>
    <div class="form-group"><label>Tipo PI Montante</label><input name="tipo_pi_montante"></div>
    <div class="form-group"><label>Quantidade de PVs</label><input type="number" name="quantidade_pvs"></div>
    <div class="form-group"><label>Altura do PV</label><input type="number" step="0.01" name="altura_pv"></div>

    <div class="form-actions">
        <button class="btn-primary btn-sm">Salvar</button>
        <a href="<?= APP_BASE ?>/planejamentos" class="btn-secondary btn-sm">Cancelar</a>
    </div>

</form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
