<?php
require_once __DIR__ . '/../../helpers/csrf.php';
$title        = 'Importar OS Topografia';
$pageTitle    = 'Importar OS Topografia';
$pageSubtitle = 'Importação em lote via planilha Excel (.xlsx)';

ob_start();
?>

<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
    <a href="<?= APP_BASE ?>/topografia" class="btn btn-sec btn-sm">← Voltar</a>
</div>

<?php if ($preview): ?>
<?php
$groups = $preview['groups'] ?? [];
$totals = $preview['totals'] ?? ['novo' => 0, 'atualizar' => 0, 'erro' => 0];
$temErros = ($totals['erro'] ?? 0) > 0;
?>

<!-- Barra de totais -->
<div style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;font-size:13px;">
    <span style="background:#d4edda;color:#155724;padding:4px 14px;border-radius:6px;font-weight:600;">
        ✅ <?= $totals['novo'] ?> nov<?= $totals['novo'] != 1 ? 'os' : 'o' ?>
    </span>
    <span style="background:#cce5ff;color:#004085;padding:4px 14px;border-radius:6px;font-weight:600;">
        🔄 <?= $totals['atualizar'] ?> atualizar
    </span>
    <span style="background:#f8d7da;color:#721c24;padding:4px 14px;border-radius:6px;font-weight:600;">
        ❌ <?= $totals['erro'] ?> erro<?= $totals['erro'] != 1 ? 's' : '' ?>
    </span>
    <span style="color:var(--muted);align-self:center;font-size:12px;">
        <?= count($groups) ?> trecho<?= count($groups) != 1 ? 's' : '' ?> detectado<?= count($groups) != 1 ? 's' : '' ?>
    </span>
</div>

<!-- Cards por grupo/trecho -->
<?php foreach ($groups as $g):
    $cardBorder = match($g['_status']) {
        'novo'      => '3px solid #28a745',
        'atualizar' => '3px solid #0d6efd',
        'erro'      => '3px solid #dc3545',
        default     => '3px solid #aaa',
    };
    $cardClass = import_row_class($g['_status']);
?>
<div class="card <?= $cardClass ?>" style="margin-bottom:14px;border-left:<?= $cardBorder ?>;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
        <div>
            <div style="font-weight:700;font-size:14px;">
                <?= htmlspecialchars($g['trecho_info']) ?>
                <?php if (!empty($g['contrato'])): ?>
                    <span style="color:var(--muted);font-size:12px;"> · Contrato: <?= htmlspecialchars($g['contrato']) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($g['_status'] === 'erro'): ?>
                <div style="color:#dc3545;font-size:12px;margin-top:4px;">
                    ❌ <?= htmlspecialchars($g['_msg']) ?>
                </div>
            <?php else: ?>
                <div style="display:flex;gap:16px;margin-top:8px;flex-wrap:wrap;font-size:12px;color:var(--muted);">
                    <span>📍 <b><?= (int)$g['n_estacas'] ?></b> estacas</span>
                    <span>📏 Ext. planilha: <b><?= number_format((float)($g['extensao_planilha'] ?? 0), 3, ',', '.') ?> m</b></span>
                    <span>📏 Ext. cadastro: <b><?= number_format((float)($g['extensao_cadastro'] ?? 0), 3, ',', '.') ?> m</b></span>
                    <span>↗ Declividade: <b><?= number_format((float)$g['declividade'], 6, ',', '.') ?> m/m</b></span>
                    <span>🪣 Prof. média calc.: <b><?= $g['prof_media_calc'] !== null ? number_format((float)$g['prof_media_calc'], 3, ',', '.') . ' m' : '—' ?></b></span>
                    <span>📅 Data OS: <b><?= $g['data_os'] ? date('d/m/Y', strtotime($g['data_os'])) : '—' ?></b></span>
                    <span>⬇ Cota FJ calc.: <b><?= number_format((float)($g['cota_fundo_jus_calc'] ?? 0), 3, ',', '.') ?></b></span>
                </div>
            <?php endif; ?>
        </div>
        <div>
            <span style="font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px;
                background:<?= $g['_status'] === 'novo' ? '#d4edda' : ($g['_status'] === 'atualizar' ? '#cce5ff' : '#f8d7da') ?>;
                color:<?= $g['_status'] === 'novo' ? '#155724' : ($g['_status'] === 'atualizar' ? '#004085' : '#721c24') ?>;">
                <?= import_status_label($g['_status']) ?>
            </span>
        </div>
    </div>

    <?php if ($g['_status'] !== 'erro' && !empty($g['estacas'])): ?>
    <details style="margin-top:12px;">
        <summary style="cursor:pointer;font-size:12px;color:var(--muted);font-weight:600;">
            Ver detalhes das <?= count($g['estacas']) ?> estacas
        </summary>
        <div class="table-wrap" style="margin-top:10px;">
            <table style="font-size:11px;">
                <thead>
                    <tr>
                        <th>Estaca</th><th>Comp. (m)</th><th>Cota Aux.</th><th>Cota Eixo</th>
                        <th>GI</th><th>GS</th><th>Gabarito</th><th>Alt. Gab.</th><th>Prof. Vala</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($g['estacas'] as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['estaca']) ?></td>
                        <td><?= number_format((float)$e['comp_acumulado'], 3, ',', '.') ?></td>
                        <td><?= $e['cota_auxiliar'] !== null ? number_format((float)$e['cota_auxiliar'], 3, ',', '.') : '—' ?></td>
                        <td><?= $e['cota_eixo'] !== null ? number_format((float)$e['cota_eixo'], 3, ',', '.') : '—' ?></td>
                        <td><?= number_format((float)$e['cota_rede_gi'], 3, ',', '.') ?></td>
                        <td><?= number_format((float)$e['cota_rede_gs'], 3, ',', '.') ?></td>
                        <td><?= number_format((float)$e['cota_gabarito'], 3, ',', '.') ?></td>
                        <td><?= number_format((float)$e['altura_gabarito'], 3, ',', '.') ?></td>
                        <td><?= number_format((float)$e['prof_vala'], 3, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </details>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<!-- Botões confirmar / cancelar -->
<form method="post" action="<?= APP_BASE ?>/topografia/importar" style="margin-top:8px;">
    <?= csrf_input() ?>
    <div class="form-actions">
        <button type="submit" name="fase" value="confirmar" class="btn btn-pri"
            <?= $temErros ? 'disabled title="Corrija os erros antes de confirmar"' : '' ?>>
            Confirmar importação
        </button>
        <?php if ($temErros): ?>
            <span style="color:#721c24;font-size:12px;">
                ❌ <?= $totals['erro'] ?> erro<?= $totals['erro'] != 1 ? 's' : '' ?> — corrija na planilha e reimporte
            </span>
        <?php endif; ?>
        <button type="submit" name="fase" value="cancelar" class="btn btn-sec">Cancelar</button>
    </div>
</form>

<?php else: ?>

<!-- Formulário de upload -->
<div class="card">
    <div style="background:#f0f4fb;border-radius:8px;padding:14px;margin-bottom:18px;font-size:13px;color:#1A2D4F;">
        <b>Estrutura esperada da planilha (a partir da linha 7):</b><br>
        A=Contrato &bull; B=PV Montante &bull; C=PV Jusante &bull; D=Data OS &bull;
        E=Cota Tampa Mont. &bull; F=Cota Fundo Mont. &bull; G=Cota Tampa Jus. &bull;
        H=Declividade &bull; I=Régua &bull; J=Diâm. Externo+Esp. (mm) &bull;
        K=Estaca &bull; L=Comp. Acumulado &bull; M=Cota Auxiliar &bull; N=Cota Eixo &bull; O=Observações
    </div>
    <form method="post" action="<?= APP_BASE ?>/topografia/importar" enctype="multipart/form-data">
        <?= csrf_input() ?>
        <input type="hidden" name="fase" value="upload">
        <div class="campo" style="margin-bottom:16px;">
            <label>Arquivo Excel (.xlsx)</label>
            <input type="file" name="arquivo" accept=".xlsx" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-pri">Analisar arquivo</button>
            <a href="<?= APP_BASE ?>/topografia" class="btn btn-sec">Cancelar</a>
        </div>
    </form>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
