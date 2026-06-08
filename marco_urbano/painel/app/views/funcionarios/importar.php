<?php
require_once __DIR__ . '/../../helpers/csrf.php';
$title     = 'Importar Funcionários';
$pageTitle = 'Importar Funcionários';
$pageSubtitle = 'Importação em lote via Excel (.xlsx)';
ob_start();
?>

<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
    <a href="<?= APP_BASE ?>/funcionarios" class="btn btn-sec btn-sm">← Voltar</a>
    <a href="<?= APP_BASE ?>/assets/modelos/funcionarios.xlsx" class="btn btn-sec btn-sm" download>⬇ Baixar modelo</a>
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
                    <th>Status</th><th>Linha</th><th>Nome</th><th>CPF</th>
                    <th>Empresa</th><th>Função</th><th>Salário</th>
                    <th>ASO</th><th>NR-06</th><th>NR-10</th><th>NR-11</th>
                    <th>NR-12</th><th>NR-18</th><th>NR-20</th><th>NR-23</th>
                    <th>NR-33</th><th>NR-35</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($preview['rows'] as $r): ?>
                <?php $cls = match($r['_status']) { 'novo'=>'imp-novo','atualizar'=>'imp-atualizar','erro'=>'imp-erro',default=>'imp-ignorar'}; ?>
                <tr class="<?= $cls ?>">
                    <td><small><?= match($r['_status']){'novo'=>'✅ Novo','atualizar'=>'🔄 Atualizar','erro'=>'❌ '.$r['_msg'],default=>'—'} ?></small></td>
                    <td><?= $r['_linha'] ?></td>
                    <td><?= htmlspecialchars($r['nome']) ?></td>
                    <td><?= htmlspecialchars($r['cpf']) ?></td>
                    <td><?= htmlspecialchars($r['empresa']) ?></td>
                    <td><?= htmlspecialchars($r['funcao']) ?></td>
                    <td><?= number_format($r['salario'],2,',','.') ?></td>
                    <td><?= $r['aso'] ? 'Apto' : 'Inapto' ?><?= $r['val_aso'] ? '<br><small style="color:var(--muted)">'.$r['val_aso'].'</small>' : '' ?></td>
                    <td><?= $r['nr06'] ? 'Apto' : 'Inapto' ?><?= $r['val_nr06'] ? '<br><small style="color:var(--muted)">'.$r['val_nr06'].'</small>' : '' ?></td>
                    <td><?= $r['nr10'] ? 'Apto' : 'Inapto' ?><?= $r['val_nr10'] ? '<br><small style="color:var(--muted)">'.$r['val_nr10'].'</small>' : '' ?></td>
                    <td><?= $r['nr11'] ? 'Apto' : 'Inapto' ?><?= $r['val_nr11'] ? '<br><small style="color:var(--muted)">'.$r['val_nr11'].'</small>' : '' ?></td>
                    <td><?= $r['nr12'] ? 'Apto' : 'Inapto' ?><?= $r['val_nr12'] ? '<br><small style="color:var(--muted)">'.$r['val_nr12'].'</small>' : '' ?></td>
                    <td><?= $r['nr18'] ? 'Apto' : 'Inapto' ?><?= $r['val_nr18'] ? '<br><small style="color:var(--muted)">'.$r['val_nr18'].'</small>' : '' ?></td>
                    <td><?= $r['nr20'] ? 'Apto' : 'Inapto' ?><?= $r['val_nr20'] ? '<br><small style="color:var(--muted)">'.$r['val_nr20'].'</small>' : '' ?></td>
                    <td><?= $r['nr23'] ? 'Apto' : 'Inapto' ?><?= $r['val_nr23'] ? '<br><small style="color:var(--muted)">'.$r['val_nr23'].'</small>' : '' ?></td>
                    <td><?= $r['nr33'] ? 'Apto' : 'Inapto' ?><?= $r['val_nr33'] ? '<br><small style="color:var(--muted)">'.$r['val_nr33'].'</small>' : '' ?></td>
                    <td><?= $r['nr35'] ? 'Apto' : 'Inapto' ?><?= $r['val_nr35'] ? '<br><small style="color:var(--muted)">'.$r['val_nr35'].'</small>' : '' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="post" action="<?= APP_BASE ?>/funcionarios/importar" style="margin-top:16px;">
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
    <form method="post" action="<?= APP_BASE ?>/funcionarios/importar" enctype="multipart/form-data">
        <?= csrf_input() ?>
        <input type="hidden" name="fase" value="upload">
        <div class="campo" style="margin-bottom:16px;">
            <label>Arquivo Excel (.xlsx)</label>
            <input type="file" name="arquivo" accept=".xlsx" required>
            <small style="color:var(--muted);display:block;margin-top:4px;">Use o modelo oficial. Linhas de exemplo (cinza) são ignoradas automaticamente.</small>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-pri">Analisar arquivo</button>
            <a href="<?= APP_BASE ?>/funcionarios" class="btn btn-sec">Cancelar</a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
