<?php
$title     = 'Repavimentação';
$pageTitle = 'Repavimentação';
$pageSubtitle = 'Fila de medição e acompanhamento';

ob_start();
?>

<div class="card">
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
                        <th>Status Repav.</th>
                        <th>Medição</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trechos as $t): ?>
                        <?php
                        $statusClass = match($t['status_repav']) {
                            'aguardando' => 'c-aviso',
                            'execucao'   => 'c-info',
                            'medido'     => 'c-ok',
                            default      => 'c-neutro',
                        };
                        $statusLabel = match($t['status_repav']) {
                            'aguardando' => 'Aguardando',
                            'execucao'   => 'Em execução',
                            'medido'     => 'Medido',
                            default      => $t['status_repav'],
                        };
                        $medicaoStatus = $t['medicao_status'] ?? null;
                        ?>
                        <tr>
                            <td><b><?= htmlspecialchars($t['pv_montante']) ?></b></td>
                            <td><?= htmlspecialchars($t['pv_jusante'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($t['bacia'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($t['rua'] ?? '—') ?></td>
                            <td><?= $t['extensao'] ? number_format((float)$t['extensao'], 1, ',', '.') . ' m' : '—' ?></td>
                            <td><span class="chip <?= $statusClass ?>"><?= $statusLabel ?></span></td>
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
                                <a href="<?= APP_BASE ?>/repavimentacao/medicao?trecho_id=<?= $t['id'] ?>"
                                   class="btn btn-sec btn-sm">
                                    <?= $t['medicao_id'] ? 'Ver medição' : 'Iniciar medição' ?>
                                </a>
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
