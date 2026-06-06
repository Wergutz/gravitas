<?php
require_once __DIR__ . '/../../helpers/csrf.php';

$title     = 'Editar Trecho';
$pageTitle = 'Editar Trecho';
$pageSubtitle = 'PV ' . htmlspecialchars($trecho['pv_montante'] ?? '');

ob_start();
?>

<div class="card" style="max-width:860px;">
    <form method="post" action="<?= APP_BASE ?>/trechos/atualizar">
        <?= csrf_input() ?>
        <input type="hidden" name="id" value="<?= (int)$trecho['id'] ?>">

        <div class="form-grid col2">
            <div class="campo">
                <label>PV Montante <span style="color:var(--erro)">*</span></label>
                <input type="text" name="pv_montante" required
                       value="<?= htmlspecialchars($trecho['pv_montante']) ?>">
            </div>
            <div class="campo">
                <label>PV Jusante</label>
                <input type="text" name="pv_jusante"
                       value="<?= htmlspecialchars($trecho['pv_jusante'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Bacia</label>
                <input type="text" name="bacia"
                       value="<?= htmlspecialchars($trecho['bacia'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Tipo PI Montante</label>
                <input type="text" name="tipo_pi_montante"
                       value="<?= htmlspecialchars($trecho['tipo_pi_montante'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Extensão (m)</label>
                <input type="text" name="extensao"
                       value="<?= htmlspecialchars($trecho['extensao'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Profundidade Média (m)</label>
                <input type="text" name="profundidade_media"
                       value="<?= htmlspecialchars($trecho['profundidade_media'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>DN</label>
                <input type="text" name="dn"
                       value="<?= htmlspecialchars($trecho['dn'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Ramais</label>
                <input type="number" name="ramais" min="0"
                       value="<?= (int)($trecho['ramais'] ?? 0) ?>">
            </div>
        </div>

        <div class="form-grid col3">
            <div class="campo">
                <label>Rua</label>
                <input type="text" name="rua"
                       value="<?= htmlspecialchars($trecho['rua'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Cidade</label>
                <input type="text" name="cidade"
                       value="<?= htmlspecialchars($trecho['cidade'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Contrato</label>
                <input type="text" name="contrato"
                       value="<?= htmlspecialchars($trecho['contrato'] ?? '') ?>">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-pri">Salvar alterações</button>
            <a href="<?= APP_BASE ?>/trechos?sel=<?= (int)$trecho['id'] ?>" class="btn btn-sec">Cancelar</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
