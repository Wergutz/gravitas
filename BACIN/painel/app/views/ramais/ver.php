<?php
/** @var array $frente @var array $ramais @var array $fotosPorRamal @var array $totais */
$title        = 'Frente de Ramais #' . (int)$frente['id'];
$pageTitle    = 'Frente de Ramais #' . (int)$frente['id'];
$pageSubtitle = RamaisController::dataBr($frente['data']) . ' · ' . (string)$frente['equipe_nome'];

$currentRoute = $currentRoute ?? '/ramais';

ob_start();
?>

<!-- Cabeçalho da frente -->
<div class="card mb16">
    <div class="kpis" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:0">
        <div class="kpi">
            <b><?= (int)$totais['ramais'] ?></b>
            <span>Ramais lançados</span>
        </div>
        <div class="kpi">
            <b><?= RamaisController::num($totais['via_m'], 2) ?> m</b>
            <span>Total em via</span>
        </div>
        <div class="kpi">
            <b><?= RamaisController::num($totais['calcada_m'], 2) ?> m</b>
            <span>Total em calçada</span>
        </div>
        <div class="kpi">
            <b><?= (int)$totais['fotos'] ?></b>
            <span>Fotos anexadas</span>
        </div>
    </div>
</div>

<div class="card mb16">
    <div class="label">Dados da frente</div>
    <div class="form-grid col4" style="margin-bottom:0">
        <div>
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);font-weight:700">Data</div>
            <div style="font-size:14px;font-weight:600"><?= htmlspecialchars(RamaisController::dataBr($frente['data'])) ?></div>
        </div>
        <div>
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);font-weight:700">Equipe</div>
            <div style="font-size:14px;font-weight:600"><?= htmlspecialchars((string)$frente['equipe_nome']) ?></div>
        </div>
        <div>
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);font-weight:700">Autor</div>
            <div style="font-size:14px;font-weight:600"><?= htmlspecialchars((string)($frente['autor_nome'] ?? '—')) ?></div>
        </div>
        <div>
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);font-weight:700">Status</div>
            <div style="font-size:14px;font-weight:600">
                <span class="chip <?= $frente['status'] === 'enviado' ? 'c-ok' : 'c-neutro' ?>">
                    <?= htmlspecialchars(RamaisController::labelStatus($frente['status'])) ?>
                </span>
                <small style="color:var(--muted)">v<?= (int)$frente['versao'] ?></small>
            </div>
        </div>
        <div>
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);font-weight:700">Logradouro</div>
            <div style="font-size:14px;font-weight:600"><?= htmlspecialchars((string)($frente['logradouro'] ?: '—')) ?></div>
        </div>
        <div style="grid-column:span 2">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);font-weight:700">Trecho</div>
            <div style="font-size:14px;font-weight:600">
                <?php if (!empty($frente['pv_montante'])): ?>
                    <?= htmlspecialchars((string)$frente['pv_montante']) ?> →
                    <?= htmlspecialchars((string)($frente['pv_jusante'] ?: '?')) ?>
                    <?php if (!empty($frente['trecho_rua'])): ?>
                        · <?= htmlspecialchars((string)$frente['trecho_rua']) ?>
                    <?php endif; ?>
                <?php else: ?>
                    <span style="color:var(--muted)">Sem trecho vinculado</span>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);font-weight:700">Enviada em</div>
            <div style="font-size:14px;font-weight:600"><?= htmlspecialchars(RamaisController::dataBr($frente['updated_at'])) ?></div>
        </div>
    </div>

    <?php if (!empty($frente['obs'])): ?>
        <div class="alerta a-info mt16" style="margin-bottom:0">
            <div><b>Observações da frente</b><small><?= nl2br(htmlspecialchars((string)$frente['obs'])) ?></small></div>
        </div>
    <?php endif; ?>

    <div class="mt16" style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="<?= APP_BASE ?>/ramais" class="btn btn-sec btn-sm">← Voltar para a lista</a>
        <a href="<?= APP_BASE ?>/ramais/relatorio?inicio=<?= urlencode((string)$frente['data']) ?>&fim=<?= urlencode((string)$frente['data']) ?>"
           class="btn btn-sec btn-sm">Relatório físico do dia</a>
    </div>
</div>

<!-- Ramais -->
<div class="card">
    <div class="label">
        Ramais da frente
        <span class="ver"><?= count($ramais) ?> ramal(is)</span>
    </div>

    <?php if (empty($ramais)): ?>
        <div class="alerta a-info">
            Esta frente ainda não tem nenhum ramal lançado.
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:40px">Seq.</th>
                        <th>Nº do imóvel</th>
                        <th>Pavimento da via</th>
                        <th style="text-align:right">Via (m)</th>
                        <th>Pavimento da calçada</th>
                        <th style="text-align:right">Calçada (m)</th>
                        <th>Observação</th>
                        <th style="min-width:230px">Fotos</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ramais as $r): ?>
                    <?php $fotos = $fotosPorRamal[(int)$r['id']] ?? []; ?>
                    <tr>
                        <td style="color:var(--muted)"><?= (int)$r['sequencia'] ?></td>
                        <td style="font-weight:700"><?= htmlspecialchars((string)$r['numero_imovel']) ?></td>
                        <td style="font-size:12.5px"><?= htmlspecialchars(RamaisController::labelPavVia($r['pavimento_via'])) ?></td>
                        <td style="text-align:right"><?= RamaisController::num($r['comprimento_via_m'], 2) ?></td>
                        <td style="font-size:12.5px"><?= htmlspecialchars(RamaisController::labelPavCalcada($r['pavimento_calcada'])) ?></td>
                        <td style="text-align:right"><?= RamaisController::num($r['comprimento_calcada_m'], 2) ?></td>
                        <td style="font-size:12.5px;color:var(--muted)">
                            <?= htmlspecialchars((string)($r['observacao'] ?: '—')) ?>
                        </td>
                        <td>
                            <?php if (empty($fotos)): ?>
                                <small style="color:var(--muted)">Sem fotos</small>
                            <?php else: ?>
                                <div style="display:flex;gap:6px;flex-wrap:wrap">
                                <?php foreach ($fotos as $foto): ?>
                                    <?php
                                    $grande = $fotosBase . '/' . rawurlencode((string)$foto['filename']);
                                    $mini   = !empty($foto['thumb'])
                                        ? $thumbsBase . '/' . rawurlencode((string)$foto['thumb'])
                                        : $grande;
                                    $rot = RamaisController::labelTipoFoto($foto['tipo']);
                                    ?>
                                    <a href="<?= htmlspecialchars($grande) ?>" target="_blank" rel="noopener"
                                       title="<?= htmlspecialchars($rot) ?>"
                                       style="display:block;text-align:center;text-decoration:none">
                                        <img src="<?= htmlspecialchars($mini) ?>"
                                             alt="<?= htmlspecialchars($rot) ?>"
                                             style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid var(--line);display:block">
                                        <small style="display:block;font-size:10px;color:var(--muted);margin-top:3px;max-width:64px">
                                            <?= htmlspecialchars($rot) ?>
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="font-weight:700">Totais</td>
                        <td style="text-align:right;font-weight:700"><?= RamaisController::num($totais['via_m'], 2) ?></td>
                        <td></td>
                        <td style="text-align:right;font-weight:700"><?= RamaisController::num($totais['calcada_m'], 2) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
