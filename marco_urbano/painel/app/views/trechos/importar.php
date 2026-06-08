<?php
require_once __DIR__ . '/../../helpers/csrf.php';
$title     = 'Importar Trechos';
$pageTitle = 'Importar Trechos';
$pageSubtitle = 'Importação em lote via Excel (.xlsx)';
ob_start();
?>

<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
    <a href="<?= APP_BASE ?>/trechos" class="btn btn-sec btn-sm">← Voltar</a>
    <a href="<?= APP_BASE ?>/assets/modelos/trechos.xlsx" class="btn btn-sec btn-sm" download>⬇ Baixar modelo</a>
</div>

<?php if ($preview): ?>
<div class="card">
    <div class="label" style="margin-bottom:12px;">Prévia da importação — <?= count($preview['rows']) ?> linha(s)</div>
    <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;font-size:13px;">
        <span style="background:#d4edda;color:#155724;padding:3px 12px;border-radius:6px;">✅ <?= $preview['totals']['novo'] ?> novo(s)</span>
        <span style="background:#cce5ff;color:#004085;padding:3px 12px;border-radius:6px;">🔄 <?= $preview['totals']['atualizar'] ?> atualizar</span>
        <span style="background:#f8d7da;color:#721c24;padding:3px 12px;border-radius:6px;">❌ <?= $preview['totals']['erro'] ?> erro(s)</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Status</th><th>Linha</th><th>PV Mont.</th><th>PV Jus.</th>
                    <th>Bacia</th><th>Tipo PI</th><th>Ext.(m)</th><th>Prof.(m)</th>
                    <th>DN</th><th>Ramais</th><th>Rua</th><th>Cidade</th><th>Contrato</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($preview['rows'] as $r): ?>
                <?php $cls = match($r['_status']) {'novo'=>'imp-novo','atualizar'=>'imp-atualizar','erro'=>'imp-erro',default=>'imp-ignorar'}; ?>
                <tr class="<?= $cls ?>">
                    <td><small><?= match($r['_status']){'novo'=>'✅ Novo','atualizar'=>'🔄 Atualizar','erro'=>'❌ '.$r['_msg'],default=>'—'} ?></small></td>
                    <td><?= $r['_linha'] ?></td>
                    <td><?= htmlspecialchars($r['pv_montante']) ?></td>
                    <td><?= htmlspecialchars($r['pv_jusante']) ?></td>
                    <td><?= htmlspecialchars($r['bacia']) ?></td>
                    <td><?= htmlspecialchars($r['tipo_pi_montante']) ?></td>
                    <td><?= $r['extensao'] !== null ? number_format($r['extensao'],1,',','.') : '—' ?></td>
                    <td><?= $r['profundidade'] !== null ? number_format($r['profundidade'],2,',','.') : '—' ?></td>
                    <td><?= htmlspecialchars($r['dn']) ?></td>
                    <td><?= $r['ramais'] ?></td>
                    <td><?= htmlspecialchars($r['rua']) ?></td>
                    <td><?= htmlspecialchars($r['cidade']) ?></td>
                    <td><?= htmlspecialchars($r['contrato']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="post" action="<?= APP_BASE ?>/trechos/importar" style="margin-top:16px;">
        <?= csrf_input() ?>
        <div class="form-actions">
            <?php $temErros = ($preview['totals']['erro'] ?? 0) > 0; ?>
            <button type="submit" name="fase" value="confirmar" class="btn btn-pri"
                <?= $temErros ? 'disabled' : '' ?>>
                Confirmar importação
            </button>
            <?php if ($temErros): ?>
            <span style="color:#721c24;font-size:12px;">❌ <?= $preview['totals']['erro'] ?> erro(s) — corrija na planilha e reimporte</span>
            <?php endif; ?>
            <button type="submit" name="fase" value="cancelar" class="btn btn-sec">Cancelar</button>
        </div>
    </form>
</div>
<?php else: ?>
<div class="card">
    <form method="post" action="<?= APP_BASE ?>/trechos/importar" enctype="multipart/form-data">
        <?= csrf_input() ?>
        <input type="hidden" name="fase" value="upload">
        <div class="campo" style="margin-bottom:16px;">
            <label>Arquivo Excel (.xlsx)</label>
            <input type="file" name="arquivo" accept=".xlsx" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-pri">Analisar arquivo</button>
            <a href="<?= APP_BASE ?>/trechos" class="btn btn-sec">Cancelar</a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
