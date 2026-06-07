<?php
require_once __DIR__ . '/../../helpers/csrf.php';
$title     = 'Importar Posição de Estoque';
$pageTitle = 'Importar Posição de Estoque';
$pageSubtitle = 'Contagem física via Excel (.xlsx)';
ob_start();
?>

<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
    <a href="<?= APP_BASE ?>/materiais" class="btn btn-sec btn-sm">← Voltar</a>
    <a href="<?= APP_BASE ?>/assets/modelos/posicao-estoque.xlsx" class="btn btn-sec btn-sm" download>⬇ Baixar modelo</a>
</div>

<?php if ($preview): ?>
<div class="card">
    <?php if (!empty($preview['meta'])): ?>
    <div class="alerta a-info" style="margin-bottom:14px;">
        <b>Data da contagem:</b> <?= htmlspecialchars($preview['meta']['data_contagem']) ?>
        <?php if (!empty($preview['meta']['responsavel'])): ?>
        &nbsp;|&nbsp; <b>Responsável:</b> <?= htmlspecialchars($preview['meta']['responsavel']) ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="label" style="margin-bottom:12px;">Prévia da contagem — <?= count($preview['rows']) ?> linha(s)</div>
    <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;font-size:13px;">
        <span style="background:#cce5ff;color:#004085;padding:3px 12px;border-radius:6px;">🔄 <?= $preview['totals']['atualizar'] ?> atualizar</span>
        <span style="background:#f8d7da;color:#721c24;padding:3px 12px;border-radius:6px;">❌ <?= $preview['totals']['erro'] ?> erro(s)</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Status</th><th>Linha</th><th>Código</th>
                    <th>Material (cadastro)</th><th>Unid.</th><th>Estq. Encontrado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($preview['rows'] as $r): ?>
                <?php $cls = match($r['_status']) {'atualizar'=>'imp-atualizar','erro'=>'imp-erro',default=>'imp-ignorar'}; ?>
                <tr class="<?= $cls ?>">
                    <td><small><?= match($r['_status']){'atualizar'=>'🔄 Atualizar','erro'=>'❌ '.$r['_msg'],default=>'—'} ?></small></td>
                    <td><?= $r['_linha'] ?></td>
                    <td><?= htmlspecialchars($r['codigo']) ?></td>
                    <td><?= htmlspecialchars($r['nome'] ?: $r['tipo_material']) ?></td>
                    <td><?= htmlspecialchars($r['unidade']) ?></td>
                    <td><?= is_numeric($r['estoque_atual']) ? number_format((float)$r['estoque_atual'],3,',','.') : $r['estoque_atual'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="post" action="<?= APP_BASE ?>/materiais/importar-estoque" style="margin-top:16px;">
        <?= csrf_input() ?>
        <div class="form-actions">
            <button type="submit" name="fase" value="confirmar" class="btn btn-pri">Confirmar contagem</button>
            <button type="submit" name="fase" value="cancelar" class="btn btn-sec">Cancelar</button>
        </div>
    </form>
</div>
<?php else: ?>
<div class="card">
    <div class="alerta a-info" style="margin-bottom:14px;">
        Importe a planilha de posição de estoque. Os campos de data e responsável serão lidos automaticamente da planilha.
        A contagem substitui o estoque físico atual dos materiais encontrados.
    </div>
    <form method="post" action="<?= APP_BASE ?>/materiais/importar-estoque" enctype="multipart/form-data">
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
