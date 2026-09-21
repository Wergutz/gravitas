<?php
$escopoLabel  = ($diario['escopo'] === 'ramais') ? 'Ramais (valas dos ramais)' : 'Rede (vala da rede)';
$title        = 'Diário de Repavimentação';
$pageTitle    = 'Diário de Repavimentação — ' . $escopoLabel;
$pageSubtitle = date('d/m/Y', strtotime($diario['data'])) . ' · ' . $diario['equipe_nome']
              . ' · PV ' . $diario['pv_montante'] . ' → ' . ($diario['pv_jusante'] ?? '—');

$rotuloPresenca = [
    'presente'  => ['Presente',    'c-ok'],
    'ausente'   => ['Ausente',     'c-erro'],
    'atrasou'   => ['Atrasou',     'c-aviso'],
    'saiu_cedo' => ['Saiu cedo',   'c-aviso'],
];

$thumbs = $repavUploads . '/thumbs';

ob_start();
?>

<!-- Cabeçalho -->
<div class="card mb16">
    <div class="kpis" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:0">
        <div class="kpi">
            <b><?= htmlspecialchars($diario['pv_montante']) ?> &rarr; <?= htmlspecialchars($diario['pv_jusante'] ?? '—') ?></b>
            <span><?= htmlspecialchars($diario['rua'] ?? 'Trecho') ?></span>
        </div>
        <div class="kpi">
            <b><?= number_format((float)$diario['area_total_m2'], 2, ',', '.') ?> m²</b>
            <span>Área total informada</span>
        </div>
        <div class="kpi">
            <b><?= number_format((float)$diario['volume_asf_m3'], 3, ',', '.') ?> m³</b>
            <span>Volume de asfalto</span>
        </div>
        <div class="kpi">
            <b><?= count($cargas) ?></b>
            <span>Cargas recebidas</span>
        </div>
        <div class="kpi">
            <b><?= number_format($massa_total, 2, ',', '.') ?> t</b>
            <span>Massa asfáltica</span>
        </div>
    </div>

    <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <span class="chip <?= $diario['escopo'] === 'ramais' ? 'c-aviso' : 'c-info' ?>">Escopo: <?= htmlspecialchars($escopoLabel) ?></span>
        <span class="chip <?= $diario['status'] === 'aprovado' ? 'c-ok' : 'c-neutro' ?>"><?= $diario['status'] === 'aprovado' ? 'Aprovado' : ucfirst(htmlspecialchars($diario['status'])) ?></span>
        <span class="chip c-neutro">Versão <?= (int)$diario['versao'] ?></span>
        <span style="font-size:12px;color:var(--muted)">Enviado por <?= htmlspecialchars($diario['autor_nome'] ?? '—') ?></span>
        <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
            <a href="<?= APP_BASE ?>/trechos?sel=<?= (int)$diario['trecho_id'] ?>#devolucao" class="btn btn-sec btn-sm">Ficha do trecho</a>
            <a href="<?= APP_BASE ?>/repavimentacao" class="btn btn-sec btn-sm">&larr; Voltar</a>
        </div>
    </div>

    <?php if ((int)$diario['mat_ok'] === 0 || trim((string)$diario['mat_obs']) !== ''): ?>
        <div class="alerta a-aviso" style="margin-top:12px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <div>
                Material apontado pelo campo
                <small><?= nl2br(htmlspecialchars($diario['mat_obs'] ?? 'Sem observação.')) ?></small>
            </div>
        </div>
    <?php endif; ?>

    <?php if (trim((string)$diario['obs_final']) !== ''): ?>
        <div style="margin-top:12px;border-top:1px solid var(--line);padding-top:12px">
            <div class="label">Observação final do campo</div>
            <p style="font-size:13px;line-height:1.5"><?= nl2br(htmlspecialchars($diario['obs_final'])) ?></p>
        </div>
    <?php endif; ?>
</div>

<!-- Somatório por tipo de pavimento -->
<div class="card mb16">
    <div class="label">Medição — somatório por tipo de pavimento</div>
    <?php if (empty($por_tipo)): ?>
        <p style="color:var(--muted);font-size:13px;">Nenhuma área lançada neste diário.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tipo de pavimento</th>
                        <th style="text-align:right">Área via</th>
                        <th style="text-align:right">Área calçada</th>
                        <th style="text-align:right">Área total</th>
                        <th style="text-align:right">Volume</th>
                        <th style="text-align:center">Lançamentos</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($por_tipo as $tipo => $tt): ?>
                    <tr>
                        <td><b><?= htmlspecialchars($tipo) ?></b></td>
                        <td style="text-align:right"><?= number_format($tt['via'], 2, ',', '.') ?> m²</td>
                        <td style="text-align:right"><?= number_format($tt['calcada'], 2, ',', '.') ?> m²</td>
                        <td style="text-align:right"><b><?= number_format($tt['area'], 2, ',', '.') ?> m²</b></td>
                        <td style="text-align:right"><?= $tt['volume'] > 0 ? number_format($tt['volume'], 3, ',', '.') . ' m³' : '—' ?></td>
                        <td style="text-align:center"><?= (int)$tt['linhas'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th style="text-align:right"><?= number_format($por_local['via'], 2, ',', '.') ?> m²</th>
                        <th style="text-align:right"><?= number_format($por_local['calcada'], 2, ',', '.') ?> m²</th>
                        <th style="text-align:right"><?= number_format($area_total, 2, ',', '.') ?> m²</th>
                        <th style="text-align:right"><?= number_format($volume_total, 3, ',', '.') ?> m³</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Áreas discriminadas -->
<div class="card mb16">
    <div class="label">Áreas lançadas (via / calçada, por tipo e imóvel)</div>
    <?php if (empty($areas)): ?>
        <p style="color:var(--muted);font-size:13px;">Sem lançamentos de área.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Local</th>
                        <th>Tipo de pavimento</th>
                        <th>Imóvel (ramal)</th>
                        <th style="text-align:right">Base</th>
                        <th style="text-align:right">Largura</th>
                        <th style="text-align:right">Área</th>
                        <th style="text-align:right">Espessura</th>
                        <th style="text-align:right">Volume</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($areas as $a): ?>
                    <tr>
                        <td>
                            <span class="chip <?= $a['local'] === 'calcada' ? 'c-neutro' : 'c-info' ?>">
                                <?= $a['local'] === 'calcada' ? 'Calçada' : 'Via' ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($a['tipo_pavimento']) ?></td>
                        <td>
                            <?php if (trim((string)$a['numero_imovel']) !== ''): ?>
                                nº <?= htmlspecialchars($a['numero_imovel']) ?>
                            <?php else: ?>
                                <span style="color:var(--muted)">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right"><?= number_format((float)$a['base_m'], 2, ',', '.') ?> m</td>
                        <td style="text-align:right"><?= number_format((float)$a['largura_m'], 2, ',', '.') ?> m</td>
                        <td style="text-align:right"><b><?= number_format((float)$a['area_m2'], 2, ',', '.') ?> m²</b></td>
                        <td style="text-align:right"><?= $a['espessura_m'] !== null ? number_format((float)$a['espessura_m'], 3, ',', '.') . ' m' : '—' ?></td>
                        <td style="text-align:right"><?= $a['volume_m3'] !== null ? number_format((float)$a['volume_m3'], 3, ',', '.') . ' m³' : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="grade2">
    <!-- Presenças -->
    <div class="card">
        <div class="label">Presenças (<?= count($presencas) ?>)</div>
        <?php if (empty($presencas)): ?>
            <p style="color:var(--muted);font-size:13px;">Nenhuma presença registrada.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Funcionário</th><th>Função</th><th>Situação</th></tr></thead>
                    <tbody>
                    <?php foreach ($presencas as $p): ?>
                        <?php $rp = $rotuloPresenca[$p['status']] ?? [$p['status'], 'c-neutro']; ?>
                        <tr>
                            <td><?= htmlspecialchars($p['nome']) ?></td>
                            <td style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($p['funcao'] ?? '—') ?></td>
                            <td><span class="chip <?= $rp[1] ?>"><?= htmlspecialchars($rp[0]) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cargas -->
    <div class="card">
        <div class="label">Cargas de massa asfáltica (<?= count($cargas) ?>)</div>
        <?php if (empty($cargas)): ?>
            <p style="color:var(--muted);font-size:13px;">Nenhuma carga registrada.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>#</th><th>Nº da NF</th><th style="text-align:right">Massa</th><th>Comprovantes</th></tr></thead>
                    <tbody>
                    <?php foreach ($cargas as $c): ?>
                        <tr>
                            <td><?= (int)$c['sequencia'] ?></td>
                            <td><b><?= htmlspecialchars($c['numero_nf'] ?? '—') ?></b></td>
                            <td style="text-align:right"><?= $c['massa_t'] !== null ? number_format((float)$c['massa_t'], 2, ',', '.') . ' t' : '—' ?></td>
                            <td>
                                <?php foreach (['foto_nf' => 'NF', 'foto_carga' => 'Carga'] as $campo => $rot): ?>
                                    <?php if (!empty($c[$campo])): ?>
                                        <a href="<?= $repavUploads ?>/<?= htmlspecialchars($c[$campo]) ?>" target="_blank" rel="noopener">
                                            <img src="<?= $thumbs ?>/<?= htmlspecialchars($c[$campo]) ?>"
                                                 alt="Foto <?= htmlspecialchars($rot) ?>"
                                                 style="width:44px;height:44px;object-fit:cover;border-radius:6px;border:1px solid var(--line)">
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if (empty($c['foto_nf']) && empty($c['foto_carga'])): ?>
                                    <span style="color:var(--muted);font-size:12px">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" style="text-align:right">Total</th>
                            <th style="text-align:right"><?= number_format($massa_total, 2, ',', '.') ?> t</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Fotos -->
<div class="card mt16">
    <div class="label">Fotos do diário (<?= count($fotos) ?>)</div>
    <?php if (empty($fotos)): ?>
        <p style="color:var(--muted);font-size:13px;">Nenhuma foto enviada.</p>
    <?php else: ?>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
            <?php foreach ($fotos as $f): ?>
                <?php $thumbFile = $f['thumb'] ?: $f['filename']; ?>
                <figure style="margin:0;width:120px">
                    <a href="<?= $repavUploads ?>/<?= htmlspecialchars($f['filename']) ?>" target="_blank" rel="noopener">
                        <img src="<?= $thumbs ?>/<?= htmlspecialchars($thumbFile) ?>"
                             alt="Foto passo <?= (int)$f['step_num'] ?>"
                             style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid var(--line);display:block">
                    </a>
                    <figcaption style="font-size:11px;color:var(--muted);margin-top:4px">
                        Passo <?= (int)$f['step_num'] ?>
                        <?php if (!empty($f['captured_at'])): ?>
                            <br><?= htmlspecialchars(date('d/m/Y H:i', strtotime($f['captured_at']))) ?>
                        <?php endif; ?>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
