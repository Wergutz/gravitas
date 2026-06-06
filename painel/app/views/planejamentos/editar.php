<?php
$title = 'Editar Planejamento';
$pageTitle = 'Planejamentos';
$pageSubtitle = 'Editar planejamento existente';

ob_start();
?>

<div class="form-card">

    <h3>✏️ Editar Planejamento</h3>

    <form action="<?= APP_BASE ?>/planejamentos/atualizar" method="post">

        <input type="hidden" name="id" value="<?= (int)($planejamento['id'] ?? 0) ?>">

        <div class="form-grid">

            <!-- Equipe -->
            <div class="form-group">
                <label>Equipe</label>
                <select name="equipe_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($equipes as $e): ?>
                        <option value="<?= $e['id'] ?>"
                            <?= ($e['id'] == ($planejamento['equipe_id'] ?? 0)) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Data -->
            <div class="form-group">
                <label>Data de Execução</label>
                <input type="date" name="data_execucao"
                       value="<?= htmlspecialchars($planejamento['data_execucao'] ?? '') ?>">
            </div>

            <!-- Macro -->
            <div class="form-group">
                <label>Macro</label>
                <input type="text" name="macro"
                       value="<?= htmlspecialchars($planejamento['macro'] ?? '') ?>">
            </div>

            <!-- Medição -->
            <div class="form-group">
                <label>Medição</label>
                <input type="text" name="medicao"
                       value="<?= htmlspecialchars($planejamento['medicao'] ?? '') ?>">
            </div>

            <!-- Cidade -->
            <div class="form-group">
                <label>Cidade</label>
                <input type="text" name="cidade"
                       value="<?= htmlspecialchars($planejamento['cidade'] ?? '') ?>">
            </div>

            <!-- Contrato -->
            <div class="form-group">
                <label>Contrato</label>
                <input type="text" name="contrato"
                       value="<?= htmlspecialchars($planejamento['contrato'] ?? '') ?>">
            </div>

            <!-- Bacia -->
            <div class="form-group">
                <label>Bacia</label>
                <input type="text" name="bacia"
                       value="<?= htmlspecialchars($planejamento['bacia'] ?? '') ?>">
            </div>

            <!-- Trecho -->
            <div class="form-group">
                <label>Trecho</label>
                <input type="text" name="trecho"
                       value="<?= htmlspecialchars($planejamento['trecho'] ?? '') ?>">
            </div>

            <!-- PV Montante -->
            <div class="form-group">
                <label>PV Montante</label>
                <input type="text" name="pv_montante"
                       value="<?= htmlspecialchars($planejamento['pv_montante'] ?? '') ?>">
            </div>

            <!-- Tipo PI Montante -->
            <div class="form-group">
                <label>Tipo PI Montante</label>
                <input type="text" name="tipo_pi_montante"
                       value="<?= htmlspecialchars($planejamento['tipo_pi_montante'] ?? '') ?>">
            </div>

            <!-- Quantidade PVS -->
            <div class="form-group">
                <label>Quantidade PVS</label>
                <input type="number" name="quantidade_pvs"
                       value="<?= htmlspecialchars($planejamento['quantidade_pvs'] ?? '') ?>">
            </div>

            <!-- Altura PV -->
            <div class="form-group">
                <label>Altura PV</label>
                <input type="text" name="altura_pv"
                       value="<?= htmlspecialchars($planejamento['altura_pv'] ?? '') ?>">
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                💾 Atualizar Planejamento
            </button>

            <a href="<?= APP_BASE ?>/planejamentos" class="btn-secondary">
                Cancelar
            </a>
        </div>

    </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';