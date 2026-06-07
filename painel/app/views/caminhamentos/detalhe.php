<?php
require_once __DIR__ . '/../../helpers/csrf.php';

$statusLabel = match($caminhamento['status']) {
    'rascunho'  => ['Rascunho',     'c-neutro'],
    'publicado' => ['Publicado',    'c-info'],
    'execucao'  => ['Em execução',  'c-aviso'],
    'concluido' => ['Concluído',    'c-ok'],
    default     => [$caminhamento['status'], 'c-neutro'],
};

$title     = 'Caminhamento #' . $caminhamento['id'];
$pageTitle = 'Caminhamento #' . $caminhamento['id'];
$pageSubtitle = $caminhamento['equipe_nome'] . ' · ' . date('d/m/Y', strtotime($caminhamento['data_execucao']));

ob_start();
?>

<!-- Barra de ação -->
<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <span class="chip <?= $statusLabel[1] ?>" style="font-size:12px;padding:4px 12px;"><?= $statusLabel[0] ?></span>

    <?php if ($caminhamento['status'] === 'rascunho'): ?>
        <form method="post" action="<?= APP_BASE ?>/caminhamentos/publicar"
              style="display:inline;"
              onsubmit="return confirm('Publicar este caminhamento? Os materiais dos trechos serão reservados no estoque.')">
            <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= (int)$caminhamento['id'] ?>">
            <button type="submit" class="btn btn-pri btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="12" x2="20" y2="12"/><polyline points="13 5 20 12 13 19"/></svg>
                Publicar (reserva materiais)
            </button>
        </form>
    <?php endif; ?>

    <a href="<?= APP_BASE ?>/caminhamentos/relatorio-materiais?id=<?= (int)$caminhamento['id'] ?>"
       target="_blank" class="btn btn-sec btn-sm">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        Solicitação de Materiais
    </a>

    <a href="<?= APP_BASE ?>/caminhamentos/relatorio-medicao?id=<?= (int)$caminhamento['id'] ?>"
       target="_blank" class="btn btn-sec btn-sm">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Relatório de Medição
    </a>

    <?php if (in_array($caminhamento['status'], ['rascunho', 'publicado'])): ?>
        <form method="post" action="<?= APP_BASE ?>/caminhamentos/excluir"
              style="display:inline;"
              onsubmit="return confirm('Excluir este caminhamento? Os trechos voltarão para \'livre\'.')">
            <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= (int)$caminhamento['id'] ?>">
            <button type="submit" class="btn btn-sm" style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;">
                🗑 Excluir
            </button>
        </form>
    <?php endif; ?>

    <a href="<?= APP_BASE ?>/caminhamentos" class="btn btn-sec btn-sm">← Voltar</a>
</div>

<?php if (!empty($caminhamento['observacoes'])): ?>
    <div class="alerta a-info" style="margin-bottom:14px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($caminhamento['observacoes']) ?>
    </div>
<?php endif; ?>

<!-- Regra 18: aviso de documentos vencidos na equipe -->
<?php if (!empty($docs_vencidos)): ?>
    <div class="alerta a-erro" style="margin-bottom:14px;flex-direction:column;align-items:flex-start;">
        <div style="display:flex;align-items:center;gap:8px;font-weight:800;margin-bottom:6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <?= count($docs_vencidos) ?> documento(s) vencido(s) na equipe
        </div>
        <?php foreach ($docs_vencidos as $d): ?>
            <div style="font-size:12px;font-weight:600;margin-bottom:2px;">
                <?= htmlspecialchars($d['nome']) ?> — <?= htmlspecialchars($d['tipo']) ?>
                <span style="opacity:.8;">(venceu <?= date('d/m/Y', strtotime($d['data_validade'])) ?>)</span>
            </div>
        <?php endforeach; ?>
        <a href="<?= APP_BASE ?>/funcionarios" style="font-size:11px;font-weight:800;margin-top:6px;border:1.5px solid currentColor;border-radius:8px;padding:4px 10px;background:transparent;color:inherit;">
            Renovar documentos →
        </a>
    </div>
<?php endif; ?>

<!-- Trechos -->
<div class="card">
    <div class="label">
        Trechos do caminhamento
        <span style="color:var(--muted);font-size:11px;font-weight:600;text-transform:none;">
            <?= count(array_filter($trechos_cam, fn($t) => $t['ct_status'] === 'concluido')) ?>/<?= count($trechos_cam) ?> concluídos
        </span>
    </div>

    <?php if (empty($trechos_cam)): ?>
        <p style="color:var(--muted);font-size:13px;">Nenhum trecho neste caminhamento.</p>
    <?php else: ?>
        <?php foreach ($trechos_cam as $tc):
            $tcStatus = match($tc['ct_status']) {
                'pendente'  => ['Pendente',    'c-neutro'],
                'execucao'  => ['Em execução', 'c-aviso'],
                'concluido' => ['Concluído',   'c-ok'],
                default     => [$tc['ct_status'], 'c-neutro'],
            };
            $mats = $materiais_por_trecho[$tc['trecho_id']] ?? [];
        ?>
            <div style="border:1px solid var(--line);border-radius:10px;padding:13px 15px;margin-bottom:10px;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <span style="width:26px;height:26px;border-radius:99px;background:var(--navy);color:#fff;display:grid;place-items:center;font-size:12px;font-weight:800;flex:0 0 auto;">
                        <?= (int)$tc['sequencia'] ?>
                    </span>
                    <div>
                        <b style="color:var(--navy);">PV <?= htmlspecialchars($tc['pv_montante']) ?> → <?= htmlspecialchars($tc['pv_jusante'] ?? '—') ?></b>
                        <span style="color:var(--muted);font-size:12px;margin-left:8px;"><?= htmlspecialchars($tc['bacia'] ?? '') ?></span>
                        <?php if ($tc['rua']): ?>
                            <br><span style="color:var(--muted);font-size:12px;"><?= htmlspecialchars($tc['rua']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="margin-left:auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <?php if ($tc['extensao']): ?>
                            <span style="font-size:12px;color:var(--muted);"><?= number_format((float)$tc['extensao'], 0, ',', '.') ?> m</span>
                        <?php endif; ?>
                        <?php if ($tc['os_arquivo']): ?>
                            <span class="chip c-ok">OS v<?= (int)$tc['os_versao'] ?></span>
                        <?php else: ?>
                            <span class="chip c-erro">Sem OS</span>
                        <?php endif; ?>
                        <span class="chip <?= $tcStatus[1] ?>"><?= $tcStatus[0] ?></span>

                        <?php if ($tc['ct_status'] !== 'concluido' && in_array($caminhamento['status'], ['publicado', 'execucao'])): ?>
                            <form method="post" action="<?= APP_BASE ?>/caminhamentos/concluir-trecho"
                                  style="display:inline;"
                                  onsubmit="return confirm('Marcar trecho como concluído? Materiais serão baixados do estoque e o trecho entrará na fila de repavimentação.')">
                                <?= csrf_input() ?>
                                <input type="hidden" name="caminhamento_id" value="<?= (int)$caminhamento['id'] ?>">
                                <input type="hidden" name="trecho_id" value="<?= (int)$tc['trecho_id'] ?>">
                                <button type="submit" class="btn btn-pri btn-sm">Concluir trecho</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Materiais do trecho -->
                <?php if (!empty($mats)): ?>
                    <div style="margin-top:10px;padding-top:10px;border-top:1px dashed var(--line);">
                        <div style="font-size:10.5px;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);font-weight:700;margin-bottom:6px;">Materiais</div>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;">
                            <?php foreach ($mats as $mat):
                                $disp = (float)$mat['qtd_fisica'] - (float)$mat['qtd_reservada'];
                                $falta = $disp < (float)$mat['quantidade'];
                            ?>
                                <span class="chip <?= $falta ? 'c-erro' : 'c-neutro' ?>" title="Disponível: <?= number_format($disp, 2) ?> <?= htmlspecialchars($mat['unidade']) ?>">
                                    <?= htmlspecialchars($mat['material_nome']) ?>
                                    <?= number_format((float)$mat['quantidade'], 2) ?> <?= htmlspecialchars($mat['unidade']) ?>
                                    <?= $falta ? '⚠' : '' ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (!empty($trechos_disponiveis_det) && in_array($caminhamento['status'], ['rascunho', 'publicado'])): ?>
<div class="card" style="margin-top:18px;">
    <div class="label">➕ Adicionar trechos a este caminhamento</div>
    <form method="post" action="<?= APP_BASE ?>/caminhamentos/adicionar-trechos">
        <?= csrf_input() ?>
        <input type="hidden" name="id" value="<?= (int)$caminhamento['id'] ?>">
        <div class="table-wrap" style="margin-bottom:12px;">
            <table>
                <thead><tr><th></th><th>PV Montante</th><th>PV Jusante</th><th>Rua</th><th>Bacia</th><th>Extensão</th></tr></thead>
                <tbody>
                <?php foreach ($trechos_disponiveis_det as $td): ?>
                <tr>
                    <td style="width:36px;text-align:center;">
                        <input type="checkbox" name="trechos[]" value="<?= (int)$td['id'] ?>">
                    </td>
                    <td><?= htmlspecialchars($td['pv_montante']) ?></td>
                    <td><?= htmlspecialchars($td['pv_jusante']) ?></td>
                    <td style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($td['rua'] ?? '') ?></td>
                    <td><?= htmlspecialchars($td['bacia'] ?? '') ?></td>
                    <td><?= $td['extensao'] ? number_format($td['extensao'], 0, ',', '.') . ' m' : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="submit" class="btn btn-pri btn-sm">Adicionar selecionados</button>
    </form>
</div>
<?php elseif (in_array($caminhamento['status'], ['rascunho', 'publicado'])): ?>
<div class="alerta a-info" style="margin-top:18px;">Todos os trechos disponíveis já estão neste caminhamento.</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
