<?php
$title        = 'Topografia';
$pageTitle    = 'OS Topografia';
$pageSubtitle = 'Ordens de Serviço — Gabarito de Rede';

$nivelAtual = (int)($_SESSION['nivel'] ?? 0);

ob_start();
?>

<div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
    <a href="<?= APP_BASE ?>/topografia/importar" class="btn btn-pri">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
             stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Nova Importação
    </a>
</div>

<?php if (empty($lista)): ?>
<div class="card">
    <p style="color:var(--muted);font-size:13px;">Nenhuma OS de topografia encontrada.</p>
</div>
<?php else: ?>

<div class="card">
    <div class="label" style="margin-bottom:12px;">
        <?= count($lista) ?> OS encontrada<?= count($lista) != 1 ? 's' : '' ?>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Trecho</th>
                    <th>Contrato</th>
                    <th>Data OS</th>
                    <th>Revisão</th>
                    <th>Declividade</th>
                    <th>Prof. Média</th>
                    <th>Status</th>
                    <th>Importado por</th>
                    <th style="min-width:200px;">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lista as $os):
                $statusLabel = $os['status'] === 'liberado' ? 'Liberado' : 'Aguardando Liberação';
                $statusClass = $os['status'] === 'liberado' ? 'c-ok' : 'c-aviso';
            ?>
                <tr>
                    <td>
                        <b><?= htmlspecialchars($os['pv_montante']) ?></b>
                        <span style="color:var(--muted);"> → </span>
                        <?= htmlspecialchars($os['pv_jusante'] ?? '—') ?>
                        <?php if (!empty($os['bacia'])): ?>
                            <br><small style="color:var(--muted);">Bacia: <?= htmlspecialchars($os['bacia']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($os['contrato'] ?? '—') ?></td>
                    <td><?= $os['data_os'] ? date('d/m/Y', strtotime($os['data_os'])) : '—' ?></td>
                    <td>
                        <span class="chip c-info">Rev. <?= (int)$os['revisao'] ?></span>
                    </td>
                    <td><?= number_format((float)$os['declividade'], 6, ',', '.') ?></td>
                    <td><?= $os['prof_media'] !== null ? number_format((float)$os['prof_media'], 3, ',', '.') . ' m' : '—' ?></td>
                    <td>
                        <span class="chip <?= $statusClass ?>"><?= $statusLabel ?></span>
                    </td>
                    <td>
                        <?= htmlspecialchars($os['importado_nome']) ?>
                        <br><small style="color:var(--muted);"><?= date('d/m/Y', strtotime($os['importado_em'])) ?></small>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <a href="<?= APP_BASE ?>/topografia/<?= $os['id'] ?>/ver"
                               target="_blank" class="btn btn-sec btn-sm">Ver OS</a>

                            <a href="<?= APP_BASE ?>/topografia/<?= $os['id'] ?>/declividade"
                               class="btn btn-sec btn-sm">Declividade</a>

                            <?php if (in_array($nivelAtual, [3, 4]) && $os['status'] === 'aguardando_liberacao'): ?>
                            <form method="post" action="<?= APP_BASE ?>/topografia/<?= $os['id'] ?>/liberar"
                                  style="display:inline;"
                                  onsubmit="return confirm('Liberar esta OS?')">
                                <?= csrf_input() ?>
                                <button type="submit" class="btn btn-pri btn-sm">Liberar</button>
                            </form>
                            <?php endif; ?>

                            <?php if ($os['status'] === 'liberado' && !empty($os['liberado_nome'])): ?>
                            <small style="color:var(--muted);font-size:10px;align-self:center;">
                                Lib.: <?= htmlspecialchars($os['liberado_nome']) ?>
                            </small>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
