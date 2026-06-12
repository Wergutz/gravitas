<?php
require_once __DIR__ . '/../../helpers/csrf.php';

$title     = 'Novo Trecho';
$pageTitle = 'Novo Trecho';
$pageSubtitle = 'Cadastro de trecho de rede';

ob_start();
?>

<div class="card" style="max-width:860px;">
    <form method="post" action="<?= APP_BASE ?>/trechos/salvar">
        <?= csrf_input() ?>

        <div class="form-grid col2">
            <div class="campo">
                <label>PV Montante <span style="color:var(--erro)">*</span></label>
                <input type="text" name="pv_montante" required placeholder="Ex: PV-001">
            </div>
            <div class="campo">
                <label>PV Jusante</label>
                <input type="text" name="pv_jusante" placeholder="Ex: PV-002">
            </div>
            <div class="campo">
                <label>Bacia</label>
                <input type="text" name="bacia" placeholder="Ex: Bacia A">
            </div>
            <div class="campo">
                <label>Tipo PI Montante</label>
                <input type="text" name="tipo_pi_montante" placeholder="Ex: PV Circular">
            </div>
            <div class="campo">
                <label>Extensão (m)</label>
                <input type="text" name="extensao" placeholder="Ex: 125.50">
            </div>
            <div class="campo">
                <label>Profundidade Média (m)</label>
                <input type="text" name="profundidade_media" placeholder="Ex: 2.30">
            </div>
            <div class="campo">
                <label>DN (diâmetro nominal)</label>
                <input type="text" name="dn" placeholder="Ex: 200 PVC">
            </div>
            <div class="campo">
                <label>Ramais</label>
                <input type="number" name="ramais" value="0" min="0">
            </div>
        </div>

        <div class="form-grid col3">
            <div class="campo">
                <label>Rua</label>
                <input type="text" name="rua" placeholder="Rua / Logradouro">
            </div>
            <div class="campo">
                <label>Cidade</label>
                <input type="text" name="cidade" placeholder="Cidade">
            </div>
            <div class="campo">
                <label>Contrato</label>
                <input type="text" name="contrato" placeholder="Nº do contrato">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-pri">Salvar trecho</button>
            <a href="<?= APP_BASE ?>/trechos" class="btn btn-sec">Cancelar</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
