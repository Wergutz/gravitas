<?php
require_once __DIR__ . '/../../helpers/csrf.php';
$title        = 'Editar Declividade';
$pageTitle    = 'Editar Declividade';
$pageSubtitle = 'OS #' . $os['id'] . ' — ' . htmlspecialchars($os['pv_montante']) . ' → ' . htmlspecialchars($os['pv_jusante'] ?? '—');

ob_start();
?>

<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
    <a href="<?= APP_BASE ?>/topografia" class="btn btn-sec btn-sm">← Voltar</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start;" class="grade2">

    <!-- Painel esquerdo: dados atuais + formulário -->
    <div>
        <div class="card" style="margin-bottom:16px;">
            <div class="label" style="margin-bottom:12px;">Dados Atuais da OS</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:13px;">
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">PV Montante</span><br><b><?= htmlspecialchars($os['pv_montante']) ?></b></div>
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">PV Jusante</span><br><?= htmlspecialchars($os['pv_jusante'] ?? '—') ?></div>
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Contrato</span><br><?= htmlspecialchars($os['contrato'] ?? '—') ?></div>
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Revisão Atual</span><br><b>Rev. <?= (int)$os['revisao'] ?></b></div>
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Declividade Atual (m/m)</span><br><b><?= number_format((float)$os['declividade'], 6, ',', '.') ?></b></div>
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Cota Fundo Jusante</span><br><?= number_format((float)$os['cota_fundo_jusante'], 3, ',', '.') ?></div>
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Prof. Média (m)</span><br><?= $os['prof_media'] !== null ? number_format((float)$os['prof_media'], 3, ',', '.') : '—' ?></div>
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Status</span><br>
                    <span class="chip <?= $os['status'] === 'liberado' ? 'c-ok' : 'c-aviso' ?>">
                        <?= $os['status'] === 'liberado' ? 'Liberado' : 'Aguardando Liberação' ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="label" style="margin-bottom:12px;">Nova Declividade</div>
            <p style="font-size:13px;color:var(--muted);margin-bottom:14px;">
                Ao salvar, a revisão será incrementada (Rev. <?= (int)$os['revisao'] + 1 ?>), todas as estacas serão
                recalculadas e o status voltará para <b>Aguardando Liberação</b>.
            </p>
            <form method="post" action="<?= APP_BASE ?>/topografia/<?= $os['id'] ?>/declividade"
                  onsubmit="return confirm('Confirmar alteração de declividade? A OS voltará para aguardando liberação.')">
                <?= csrf_input() ?>
                <div class="campo" style="margin-bottom:16px;">
                    <label>Nova Declividade (m/m) <small style="color:var(--muted);">0.0001 – 0.2</small></label>
                    <input type="number" name="declividade" id="inp_decl"
                           value="<?= htmlspecialchars($_GET['prev_decl'] ?? number_format((float)$os['declividade'], 6, '.', '')) ?>"
                           step="0.000001" min="0.0001" max="0.2" required
                           style="font-family:inherit;font-size:14px;padding:10px 12px;border:1px solid var(--line);border-radius:8px;width:100%;">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-pri">Salvar Nova Declividade</button>
                    <a href="<?= APP_BASE ?>/topografia" class="btn btn-sec">Cancelar</a>
                </div>
            </form>

            <div style="margin-top:14px;border-top:1px solid var(--line);padding-top:14px;">
                <form method="get" action="<?= APP_BASE ?>/topografia/<?= $os['id'] ?>/declividade">
                    <div style="display:flex;gap:10px;align-items:flex-end;">
                        <div class="campo" style="flex:1;margin-bottom:0;">
                            <label style="font-size:12px;">Simular declividade (prévia)</label>
                            <input type="number" name="prev_decl"
                                   value="<?= htmlspecialchars($_GET['prev_decl'] ?? '') ?>"
                                   step="0.000001" min="0.0001" max="0.2"
                                   placeholder="ex: 0.005000"
                                   style="font-family:inherit;font-size:13px;padding:9px 12px;border:1px solid var(--line);border-radius:8px;width:100%;">
                        </div>
                        <button type="submit" class="btn btn-sec btn-sm" style="margin-bottom:0;">Simular</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Painel direito: tabela de estacas (atual ou simulada) -->
    <div class="card">
        <?php if ($prevDecl !== null): ?>
        <div class="label" style="margin-bottom:8px;">
            Prévia com Declividade <?= number_format($prevDecl, 6, ',', '.') ?> m/m
        </div>
        <div style="background:#fff3cd;border-radius:6px;padding:8px 12px;font-size:12px;margin-bottom:12px;">
            <b>Nova Cota Fundo Jusante:</b> <?= number_format((float)$prevFJ, 3, ',', '.') ?> &nbsp;|&nbsp;
            <b>Prof. Média calc.:</b> <?= $prevProfMedia !== null ? number_format($prevProfMedia, 3, ',', '.') . ' m' : '—' ?>
        </div>
        <div class="table-wrap">
            <table style="font-size:11px;">
                <thead>
                    <tr>
                        <th>Estaca</th><th>Comp.</th><th>GI atual</th><th>GI novo</th>
                        <th>Prof. Vala atual</th><th>Prof. Vala nova</th><th>Alt. Gab. nova</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($prevEstacas as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['estaca']) ?></td>
                        <td><?= number_format((float)$e['comp_acumulado'], 3, ',', '.') ?></td>
                        <td><?= number_format((float)$e['cota_rede_gi'], 3, ',', '.') ?></td>
                        <td style="font-weight:700;color:#0d6efd;"><?= number_format($e['new_gi'], 3, ',', '.') ?></td>
                        <td><?= number_format((float)$e['prof_vala'], 3, ',', '.') ?></td>
                        <td style="font-weight:700;color:#0d6efd;"><?= number_format($e['new_pv'], 3, ',', '.') ?></td>
                        <td style="color:#856404;"><?= number_format($e['new_altg'], 3, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="label" style="margin-bottom:8px;">Estacas Atuais (<?= count($estacas) ?>)</div>
        <div class="table-wrap">
            <table style="font-size:11px;">
                <thead>
                    <tr>
                        <th>Estaca</th><th>Comp. (m)</th><th>GI</th><th>GS</th>
                        <th>Gabarito</th><th>Alt. Gab.</th><th>Prof. Vala</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($estacas as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['estaca']) ?></td>
                        <td><?= number_format((float)$e['comp_acumulado'], 3, ',', '.') ?></td>
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
        <p style="font-size:12px;color:var(--muted);margin-top:8px;">
            Use o formulário de simulação para pré-visualizar o impacto de uma nova declividade.
        </p>
        <?php endif; ?>
    </div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/topografo.php';
