<?php
$title     = 'Trechos & OS';
$pageTitle = 'Trechos & Ordens de Serviço';
$pageSubtitle = 'Gestão da rede e documentos de OS';

ob_start();
?>

<!-- Filtros + botão novo -->
<div class="topo" style="margin-bottom:14px;">
    <form method="get" class="flex-gap">
        <select name="bacia" class="campo" style="min-width:140px;">
            <option value="">Todas as bacias</option>
            <?php foreach ($bacias as $b): ?>
                <option value="<?= htmlspecialchars($b) ?>" <?= $bacia === $b ? 'selected' : '' ?>>
                    <?= htmlspecialchars($b) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="status_rede" style="min-width:140px;font-family:inherit;font-size:14px;color:var(--ink);padding:11px 12px;border:1px solid var(--line);border-radius:10px;background:#fff;">
            <option value="">Todos os status</option>
            <option value="livre"      <?= $status_rede === 'livre'      ? 'selected' : '' ?>>Livre</option>
            <option value="programado" <?= $status_rede === 'programado' ? 'selected' : '' ?>>Programado</option>
            <option value="execucao"   <?= $status_rede === 'execucao'   ? 'selected' : '' ?>>Em execução</option>
            <option value="concluido"  <?= $status_rede === 'concluido'  ? 'selected' : '' ?>>Concluído</option>
        </select>

        <button type="submit" class="btn btn-sec btn-sm">Filtrar</button>
        <a href="<?= APP_BASE ?>/trechos" class="btn btn-sec btn-sm">Limpar</a>
    </form>
    <div class="acoes">
        <a href="<?= APP_BASE ?>/trechos/importar" class="btn btn-sec">Importar Excel</a>
        <a href="<?= APP_BASE ?>/trechos/cadastrar" class="btn btn-pri">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Novo Trecho
        </a>
    </div>
</div>

<div class="grade2" style="grid-template-columns:<?= $trecho_sel ? '1.2fr 1fr' : '1fr' ?>; align-items:start;">

    <!-- Lista de trechos -->
    <div class="card">
        <div class="label">
            <?= count($trechos) ?> trecho<?= count($trechos) != 1 ? 's' : '' ?> encontrado<?= count($trechos) != 1 ? 's' : '' ?>
        </div>

        <?php if (empty($trechos)): ?>
            <p style="color:var(--muted);font-size:13px;">Nenhum trecho encontrado.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>PV Montante</th>
                            <th>PV Jusante</th>
                            <th>Bacia</th>
                            <th>Extensão</th>
                            <th>Status</th>
                            <th>OS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trechos as $t): ?>
                            <?php
                            $statusClass = match($t['status_rede']) {
                                'livre'      => 'c-neutro',
                                'programado' => 'c-info',
                                'execucao'   => 'c-aviso',
                                'concluido'  => 'c-ok',
                                default      => 'c-neutro',
                            };
                            $statusLabel = match($t['status_rede']) {
                                'livre'      => 'Livre',
                                'programado' => 'Programado',
                                'execucao'   => 'Em execução',
                                'concluido'  => 'Concluído',
                                default      => $t['status_rede'],
                            };
                            $isSelected = ($sel_id == $t['id']);
                            $queryStr = http_build_query(array_merge($_GET, ['sel' => $t['id']]));
                            ?>
                            <tr style="<?= $isSelected ? 'background:#f0f4fb;' : '' ?> cursor:pointer;"
                                onclick="location.href='<?= APP_BASE ?>/trechos?<?= htmlspecialchars($queryStr) ?>'">
                                <td><b><?= htmlspecialchars($t['pv_montante']) ?></b></td>
                                <td><?= htmlspecialchars($t['pv_jusante'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($t['bacia'] ?? '—') ?></td>
                                <td><?= $t['extensao'] ? number_format((float)$t['extensao'], 1, ',', '.') . ' m' : '—' ?></td>
                                <td><span class="chip <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                                <td>
                                    <?php if ($t['os_id']): ?>
                                        <span class="chip c-ok">v<?= (int)$t['os_versao'] ?></span>
                                    <?php else: ?>
                                        <span class="chip c-aviso">Sem OS</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Painel lateral: ficha do trecho selecionado -->
    <?php if ($trecho_sel): ?>
    <div>
        <div class="card">
            <div class="label flex-between">
                Ficha do Trecho #<?= $trecho_sel['id'] ?>
                <div class="flex-gap">
                    <a href="<?= APP_BASE ?>/trechos/editar?id=<?= $trecho_sel['id'] ?>" class="btn btn-sec btn-sm">Editar</a>
                    <a href="<?= APP_BASE ?>/trechos" class="btn btn-sec btn-sm">✕</a>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:13px;margin-bottom:16px;">
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">PV Montante</span><br><b><?= htmlspecialchars($trecho_sel['pv_montante']) ?></b></div>
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">PV Jusante</span><br><?= htmlspecialchars($trecho_sel['pv_jusante'] ?? '—') ?></div>
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Bacia</span><br><?= htmlspecialchars($trecho_sel['bacia'] ?? '—') ?></div>
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">DN</span><br><?= htmlspecialchars($trecho_sel['dn'] ?? '—') ?></div>
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Extensão</span><br><?= $trecho_sel['extensao'] ? number_format((float)$trecho_sel['extensao'], 2, ',', '.') . ' m' : '—' ?></div>
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Prof. Média</span><br><?= $trecho_sel['profundidade_media'] ? number_format((float)$trecho_sel['profundidade_media'], 2, ',', '.') . ' m' : '—' ?></div>
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Rua</span><br><?= htmlspecialchars($trecho_sel['rua'] ?? '—') ?></div>
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Cidade</span><br><?= htmlspecialchars($trecho_sel['cidade'] ?? '—') ?></div>
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Contrato</span><br><?= htmlspecialchars($trecho_sel['contrato'] ?? '—') ?></div>
                <div><span style="color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Ramais</span><br><?= (int)$trecho_sel['ramais'] ?></div>
            </div>

            <!-- Upload de OS -->
            <div style="border-top:1px solid var(--line);padding-top:16px;margin-top:4px;">
                <div class="label">Upload de OS (PDF)</div>
                <form method="post" action="<?= APP_BASE ?>/trechos/upload-os" enctype="multipart/form-data">
                    <?= csrf_input() ?>
                    <input type="hidden" name="trecho_id" value="<?= $trecho_sel['id'] ?>">
                    <div class="form-grid col2" style="margin-bottom:10px;">
                        <div class="campo">
                            <label>Data da OS</label>
                            <input type="date" name="data_os">
                        </div>
                        <div class="campo">
                            <label>Topógrafo</label>
                            <input type="text" name="topografo" placeholder="Nome do topógrafo">
                        </div>
                    </div>
                    <div class="campo" style="margin-bottom:10px;">
                        <input type="file" name="arquivo_os" accept=".pdf,application/pdf" required
                               style="font-family:inherit;font-size:13px;padding:8px;border:1px solid var(--line);border-radius:8px;width:100%;">
                        <small style="color:var(--muted);font-size:11px;">PDF, máx. 10MB</small>
                    </div>
                    <button type="submit" class="btn btn-pri btn-sm">Enviar OS</button>
                </form>
            </div>

            <!-- Histórico de OS -->
            <?php if (!empty($os_historico)): ?>
            <div style="border-top:1px solid var(--line);padding-top:16px;margin-top:16px;">
                <div class="label">Histórico de OS</div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Versão</th>
                                <th>Data OS</th>
                                <th>Topógrafo</th>
                                <th>Ativa</th>
                                <th>Arquivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($os_historico as $os): ?>
                                <tr>
                                    <td>v<?= (int)$os['versao'] ?></td>
                                    <td><?= $os['data_os'] ? date('d/m/Y', strtotime($os['data_os'])) : '—' ?></td>
                                    <td><?= htmlspecialchars($os['topografo'] ?? '—') ?></td>
                                    <td><?= $os['ativa'] ? '<span class="chip c-ok">Ativa</span>' : '<span class="chip c-neutro">Hist.</span>' ?></td>
                                    <td>
                                        <a href="<?= APP_BASE ?>/uploads/os/<?= htmlspecialchars($os['arquivo_pdf']) ?>"
                                           target="_blank" class="btn btn-sec btn-sm">PDF</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
