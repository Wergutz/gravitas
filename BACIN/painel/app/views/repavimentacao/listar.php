<?php
$title        = 'Repavimentação';
$pageTitle    = 'Repavimentação';
$pageSubtitle = 'Diários enviados pela equipe de pavimentação e fila de trechos';

/* Blocos de diários por escopo */
$blocos = [
    'rede' => [
        'titulo'  => 'Diários de repavimentação — REDE (vala da rede)',
        'lista'   => $diarios_rede,
        'totais'  => $totais['rede'],
        'chip'    => 'c-info',
    ],
    'ramais' => [
        'titulo'  => 'Diários de repavimentação — RAMAIS (valas dos ramais)',
        'lista'   => $diarios_ramais,
        'totais'  => $totais['ramais'],
        'chip'    => 'c-aviso',
    ],
];

ob_start();
?>

<?php foreach ($blocos as $escopo => $bloco): ?>
<div class="card mb16">
    <div class="label">
        <?= htmlspecialchars($bloco['titulo']) ?>
        <span class="ver" style="color:var(--muted);font-weight:700;">
            <?= count($bloco['lista']) ?> diário<?= count($bloco['lista']) != 1 ? 's' : '' ?>
        </span>
    </div>

    <?php if (empty($bloco['lista'])): ?>
        <div class="alerta a-info">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Nenhum diário de repavimentação de <?= $escopo === 'ramais' ? 'ramais' : 'rede' ?> recebido do campo.
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Equipe</th>
                        <th>Trecho / Rua</th>
                        <th style="text-align:right">Área total</th>
                        <th style="text-align:right">Volume asfalto</th>
                        <th style="text-align:center">Cargas</th>
                        <th>NFs</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bloco['lista'] as $d): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($d['data'])) ?></td>
                        <td><?= htmlspecialchars($d['equipe_nome']) ?></td>
                        <td>
                            <b><?= htmlspecialchars($d['pv_montante']) ?> &rarr; <?= htmlspecialchars($d['pv_jusante'] ?? '—') ?></b>
                            <?php if (!empty($d['rua'])): ?>
                                <br><span style="color:var(--muted);font-size:12px;"><?= htmlspecialchars($d['rua']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right"><?= number_format((float)$d['area_total_m2'], 2, ',', '.') ?> m²</td>
                        <td style="text-align:right"><?= number_format((float)$d['volume_asf_m3'], 3, ',', '.') ?> m³</td>
                        <td style="text-align:center"><?= (int)$d['n_cargas'] ?></td>
                        <td style="font-size:12px"><?= htmlspecialchars($d['nfs'] ?? '') !== '' ? htmlspecialchars($d['nfs']) : '—' ?></td>
                        <td>
                            <span class="chip <?= $d['status'] === 'aprovado' ? 'c-ok' : $bloco['chip'] ?>">
                                <?= $d['status'] === 'aprovado' ? 'Aprovado' : 'Enviado' ?>
                            </span>
                            <?php if ((int)$d['mat_ok'] === 0): ?>
                                <span class="chip c-aviso" title="Material com pendência apontada pelo campo">Material</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= APP_BASE ?>/repavimentacao/diario?id=<?= (int)$d['id'] ?>" class="btn btn-sec btn-sm">Abrir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" style="text-align:right">Total <?= $escopo === 'ramais' ? 'ramais' : 'rede' ?></th>
                        <th style="text-align:right"><?= number_format($bloco['totais']['area'], 2, ',', '.') ?> m²</th>
                        <th style="text-align:right"><?= number_format($bloco['totais']['volume'], 3, ',', '.') ?> m³</th>
                        <th style="text-align:center"><?= (int)$bloco['totais']['cargas'] ?></th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<!-- Fila de trechos -->
<div class="card">
    <div class="label">Fila de trechos</div>

    <?php if (empty($trechos)): ?>
        <div class="alerta a-info">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Nenhum trecho na fila de repavimentação.
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>PV Montante</th>
                        <th>PV Jusante</th>
                        <th>Bacia</th>
                        <th>Rua</th>
                        <th>Extensão</th>
                        <th>Repav. rede</th>
                        <th>Repav. ramais</th>
                        <th>Medição</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $classeStatus = function ($st) {
                        return match ($st) {
                            'aguardando' => 'c-aviso',
                            'execucao'   => 'c-info',
                            'medido'     => 'c-ok',
                            default      => 'c-neutro',
                        };
                    };
                    $rotuloStatus = function ($st) {
                        return match ($st) {
                            'aguardando' => 'Aguardando',
                            'execucao'   => 'Em execução',
                            'medido'     => 'Medido',
                            default      => '—',
                        };
                    };
                    ?>
                    <?php foreach ($trechos as $t): ?>
                        <?php $medicaoStatus = $t['medicao_status'] ?? null; ?>
                        <tr>
                            <td><b><?= htmlspecialchars($t['pv_montante']) ?></b></td>
                            <td><?= htmlspecialchars($t['pv_jusante'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($t['bacia'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($t['rua'] ?? '—') ?></td>
                            <td><?= $t['extensao'] ? number_format((float)$t['extensao'], 1, ',', '.') . ' m' : '—' ?></td>
                            <td><span class="chip <?= $classeStatus($t['status_repav']) ?>"><?= $rotuloStatus($t['status_repav']) ?></span></td>
                            <td><span class="chip <?= $classeStatus($t['status_repav_ramais']) ?>"><?= $rotuloStatus($t['status_repav_ramais']) ?></span></td>
                            <td>
                                <?php if ($medicaoStatus): ?>
                                    <span class="chip <?= $medicaoStatus === 'concluida' ? 'c-ok' : 'c-neutro' ?>">
                                        <?= $medicaoStatus === 'concluida' ? 'Concluída' : 'Rascunho' ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:var(--muted);font-size:12px;">Sem medição</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <a href="<?= APP_BASE ?>/repavimentacao/medicao?trecho_id=<?= (int)$t['id'] ?>"
                                       class="btn btn-sec btn-sm">
                                        <?= $t['medicao_id'] ? 'Ver medição' : 'Iniciar medição' ?>
                                    </a>
                                    <?php if ($medicaoStatus === 'concluida'): ?>
                                        <a href="<?= APP_BASE ?>/repavimentacao/relatorio?medicao_id=<?= (int)$t['medicao_id'] ?>"
                                           target="_blank" class="btn btn-sec btn-sm">PDF</a>
                                    <?php endif; ?>
                                    <a href="<?= APP_BASE ?>/trechos?sel=<?= (int)$t['id'] ?>#devolucao" class="btn btn-sec btn-sm">Ficha do trecho</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
