<?php
require_once __DIR__ . '/../../helpers/csrf.php';
$title     = 'Importar Catálogo de Materiais';
$pageTitle = 'Importar Catálogo de Materiais';
$pageSubtitle = 'Importação em lote via Excel (.xlsx)';
ob_start();
?>

<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
    <a href="<?= APP_BASE ?>/materiais" class="btn btn-sec btn-sm">← Voltar</a>
    <a href="<?= APP_BASE ?>/assets/modelos/materiais.xlsx" class="btn btn-sec btn-sm" download>⬇ Baixar modelo</a>
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
                    <th>Status</th><th>Linha</th><th>Código</th><th>Nome</th>
                    <th>Unid.</th><th>Estq. Mín.</th><th>Estq. Atual</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($preview['rows'] as $r): ?>
                <?php $cls = match($r['_status']) {'novo'=>'imp-novo','atualizar'=>'imp-atualizar','erro'=>'imp-erro',default=>'imp-ignorar'}; ?>
                <tr class="<?= $cls ?>">
                    <td><small><?= match($r['_status']){'novo'=>'✅ Novo','atualizar'=>'🔄 Atualizar','erro'=>'❌ '.$r['_msg'],default=>'—'} ?></small></td>
                    <td><?= $r['_linha'] ?></td>
                    <td><?= htmlspecialchars($r['codigo']) ?></td>
                    <td><?= htmlspecialchars($r['nome']) ?></td>
                    <td><?= htmlspecialchars($r['unidade']) ?></td>
                    <td><?= number_format($r['estoque_minimo'],3,',','.') ?></td>
                    <td><?= $r['estoque_atual'] !== null ? number_format($r['estoque_atual'],3,',','.') : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="post" action="<?= APP_BASE ?>/materiais/importar" style="margin-top:16px;">
        <?= csrf_input() ?>
        <div class="form-actions">
            <button type="submit" name="fase" value="confirmar" class="btn btn-pri">Confirmar importação</button>
            <button type="submit" name="fase" value="cancelar" class="btn btn-sec">Cancelar</button>
        </div>
    </form>
</div>
<?php else: ?>
<div class="card">
    <form method="post" action="<?= APP_BASE ?>/materiais/importar" enctype="multipart/form-data">
        <?= csrf_input() ?>
        <input type="hidden" name="fase" value="upload">
        <div class="campo" style="margin-bottom:16px;">
            <label>Arquivo Excel (.xlsx)</label>
            <input type="file" name="arquivo" accept=".xlsx" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-pri">Analisar arquivo</button>
            <a href="<?= APP_BASE ?>/materiais" class="btn btn-sec">Cancelar</a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
