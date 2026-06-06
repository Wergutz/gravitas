<?php
$title = 'Novo Planejamento';
$pageTitle = 'Novo Planejamento';
$pageSubtitle = 'Cadastro do planejamento';

ob_start();
?>

<div class="card">
<form method="post" action="<?= APP_BASE ?>/planejamentos/salvar">

    <div class="campo">
        <label>Equipe</label>
        <select name="equipe_id" required>
            <option value="">Selecione</option>
            <?php foreach ($equipes as $e): ?>
                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label>Data de Execução</label>
        <input type="date" name="data_execucao" required>
    </div>

    <div class="campo"><label>Macro</label><input name="macro"></div>
    <div class="campo"><label>Medição</label><input name="medicao"></div>
    <div class="campo"><label>Cidade</label><input name="cidade"></div>
    <div class="campo"><label>Contrato</label><input name="contrato"></div>
    <div class="campo"><label>Bacia</label><input name="bacia"></div>
    <div class="campo"><label>Trecho</label><input name="trecho"></div>
    <div class="campo"><label>PV Montante</label><input name="pv_montante"></div>
    <div class="campo"><label>Tipo PI Montante</label><input name="tipo_pi_montante"></div>
    <div class="campo"><label>Quantidade de PVs</label><input type="number" name="quantidade_pvs"></div>
    <div class="campo"><label>Altura do PV</label><input type="number" step="0.01" name="altura_pv"></div>

    <div class="form-actions">
        <button class="btn btn-pri btn-sm">Salvar</button>
        <a href="<?= APP_BASE ?>/planejamentos" class="btn btn-sec btn-sm">Cancelar</a>
    </div>

</form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
