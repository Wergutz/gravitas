<?php
require_once __DIR__ . '/../../helpers/csrf.php';
$title     = 'Importar Equipamentos Leves';
$pageTitle = 'Importar Equipamentos Leves';
$pageSubtitle = 'Importação em lote via Excel (.xlsx)';
ob_start();
?>

<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
    <a href="<?= APP_BASE ?>/equipamentos-leves" class="btn btn-sec btn-sm">← Voltar</a>
    <a href="<?= APP_BASE ?>/assets/modelos/equipamentos-leves.xlsx" class="btn btn-sec btn-sm" download>⬇ Baixar modelo</a>
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
                    <th>Status</th><th>Linha</th><th>Tipo</th><th>Referência</th>
                    <th>Modelo</th><th>Ano</th><th>Proprietário</th><th>Combustível</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($preview['rows'] as $r): ?>
                <?php $cls = match($r['_status']) {'novo'=>'imp-novo','atualizar'=>'imp-atualizar','erro'=>'imp-erro',default=>'imp-ignorar'}; ?>
                <tr class="<?= $cls ?>">
                    <td><small><?= match($r['_status']){'novo'=>'✅ Novo','atualizar'=>'🔄 Atualizar','erro'=>'❌ '.$r['_msg'],default=>'—'} ?></small></td>
                    <td><?= $r['_linha'] ?></td>
                    <td><?= htmlspecialchars($r['tipo']) ?></td>
                    <td><?= htmlspecialchars($r['referencia']) ?></td>
                    <td><?= htmlspecialchars($r['modelo']) ?></td>
                    <td><?= $r['ano'] ?></td>
                    <td><?= htmlspecialchars($r['proprietario']) ?></td>
                    <td><?= htmlspecialchars($r['combustivel']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="post" action="<?= APP_BASE ?>/equipamentos-leves/importar" style="margin-top:16px;">
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
    <form method="post" action="<?= APP_BASE ?>/equipamentos-leves/importar" enctype="multipart/form-data">
        <?= csrf_input() ?>
        <input type="hidden" name="fase" value="upload">
        <div class="campo" style="margin-bottom:16px;">
            <label>Arquivo Excel (.xlsx)</label>
            <input type="file" name="arquivo" accept=".xlsx" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-pri">Analisar arquivo</button>
            <a href="<?= APP_BASE ?>/equipamentos-leves" class="btn btn-sec">Cancelar</a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
