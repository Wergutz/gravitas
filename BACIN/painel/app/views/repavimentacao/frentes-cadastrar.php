<?php
require_once __DIR__ . '/../../helpers/csrf.php';

$title        = 'Nova Frente de Repavimentação';
$pageTitle    = 'Nova Frente de Repavimentação';
$pageSubtitle = 'Programar equipe de pavimento';

$tiposReponsavel = [5 => 'Executor Rede', 7 => 'Executor Pavimento'];

ob_start();
?>

<?php $erro = $_GET['erro'] ?? ''; ?>
<?php if ($erro === 'trechos'): ?>
<div class="alert alert-erro" style="margin-bottom:14px;">Selecione ao menos um trecho da fila.</div>
<?php elseif ($erro === 'dados'): ?>
<div class="alert alert-erro" style="margin-bottom:14px;">Preencha equipe e data.</div>
<?php elseif ($erro === 'db'): ?>
<div class="alert alert-erro" style="margin-bottom:14px;">Erro ao salvar. Tente novamente.</div>
<?php endif; ?>

<div class="card">
    <form method="post" action="<?= APP_BASE ?>/repavimentacao/frentes/salvar">
        <?= csrf_input() ?>

        <div class="form-grid col2">
            <div class="campo">
                <label for="equipe_id">Equipe <span style="color:var(--erro)">*</span></label>
                <select name="equipe_id" id="equipe_id" required>
                    <option value="">Selecione a equipe</option>
                    <?php foreach ($equipes as $eq): ?>
                    <option value="<?= $eq['id'] ?>">
                        <?= htmlspecialchars($eq['nome']) ?>
                        <?php if ($eq['responsavel_nome']): ?>
                        — <?= htmlspecialchars($eq['responsavel_nome']) ?>
                        (<?= $tiposReponsavel[$eq['tipo_usuario']] ?? 'Executor' ?>)
                        <?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="data_execucao">Data de execução <span style="color:var(--erro)">*</span></label>
                <input type="date" name="data_execucao" id="data_execucao"
                       value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <div class="campo">
            <label for="obs">Observações <small style="color:var(--muted)">(opcional)</small></label>
            <input type="text" name="obs" id="obs" placeholder="Ex.: Priorizar Rua das Flores" maxlength="500">
        </div>

        <?php if (empty($trechos_fila)): ?>
        <div class="alert" style="background:#FEF3C7;border:1px solid #F59E0B;border-radius:8px;padding:12px 14px;margin-top:14px;font-size:13px;">
            <b>Nenhum trecho na fila de repavimentação.</b><br>
            Trechos entram na fila quando a rede é concluída pelo Executor de Rede.
            <br><a href="<?= APP_BASE ?>/repavimentacao" style="color:#1A2D4F;font-weight:700;">→ Ver fila de repavimentação</a>
        </div>
        <?php else: ?>

        <div class="campo" style="margin-top:8px;">
            <label>Trechos da fila <span style="color:var(--erro)">*</span></label>
            <small style="color:var(--muted);display:block;margin-bottom:8px;">
                Selecione os trechos que esta equipe vai repavimentar.
                Apenas trechos com status <b>aguardando</b> são exibidos.
            </small>

            <div style="border:1px solid var(--line);border-radius:10px;overflow:hidden;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:var(--navy);color:#fff;">
                            <th style="padding:8px 12px;text-align:center;width:40px;">
                                <input type="checkbox" id="sel-todos" title="Selecionar todos">
                            </th>
                            <th style="padding:8px 10px;">PV Montante → Jusante</th>
                            <th style="padding:8px 10px;">Bacia</th>
                            <th style="padding:8px 10px;">Rua</th>
                            <th style="padding:8px 10px;text-align:right;">Extensão</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($trechos_fila as $i => $t): ?>
                    <tr style="<?= $i % 2 ? 'background:#F7F9FC' : '' ?>;border-top:1px solid var(--line);">
                        <td style="padding:8px 12px;text-align:center;">
                            <input type="checkbox" name="trechos[]" value="<?= $t['id'] ?>" class="cb-trecho">
                        </td>
                        <td style="padding:8px 10px;font-weight:600;">
                            <?= htmlspecialchars($t['pv_montante']) ?> → <?= htmlspecialchars($t['pv_jusante'] ?? '—') ?>
                        </td>
                        <td style="padding:8px 10px;color:var(--muted);"><?= htmlspecialchars($t['bacia'] ?? '—') ?></td>
                        <td style="padding:8px 10px;"><?= htmlspecialchars($t['rua'] ?? '—') ?></td>
                        <td style="padding:8px 10px;text-align:right;font-weight:600;">
                            <?= $t['extensao'] ? number_format((float)$t['extensao'], 1, ',', '.') . ' m' : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; ?>

        <div class="form-actions" style="margin-top:18px;">
            <button type="submit" class="btn btn-pri" <?= empty($trechos_fila) ? 'disabled' : '' ?>>
                Criar Frente (Rascunho)
            </button>
            <a href="<?= APP_BASE ?>/repavimentacao/frentes" class="btn btn-sec">Cancelar</a>
        </div>
    </form>
</div>

<script>
document.getElementById('sel-todos')?.addEventListener('change', function() {
    document.querySelectorAll('.cb-trecho').forEach(cb => cb.checked = this.checked);
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
