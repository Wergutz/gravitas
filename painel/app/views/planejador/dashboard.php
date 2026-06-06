<?php
$title = 'Dashboard';
$pageTitle = 'Dashboard';
$pageSubtitle = 'Visão geral da produção e gestão';

ob_start();
?>

<!-- KPIs -->
<div class="kpis">

    <div class="kpi">
        <div class="ic ic-info">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
        </div>
        <b><?= $caminhamentos_hoje ?></b>
        <span>Caminhamentos hoje</span>
    </div>

    <div class="kpi">
        <div class="ic ic-navy">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"/>
                <polyline points="8 7 3 12 8 17"/>
                <polyline points="16 7 21 12 16 17"/>
            </svg>
        </div>
        <b><?= number_format($metros_programados, 0, ',', '.') ?> m</b>
        <span>Metros programados</span>
    </div>

    <div class="kpi">
        <div class="ic ic-ok">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <b><?= $equipes_ativas ?></b>
        <span>Equipes ativas</span>
    </div>

    <div class="kpi">
        <div class="ic ic-aviso">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        <b><?= $trechos_sem_os ?></b>
        <span>Trechos sem OS</span>
        <?php if ($trechos_sem_os > 0): ?>
            <span class="delta d-aviso">Atenção necessária</span>
        <?php endif; ?>
    </div>

    <div class="kpi">
        <div class="ic ic-erro">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="12" y1="18" x2="12" y2="12"/>
                <line x1="9" y1="15" x2="15" y2="15"/>
            </svg>
        </div>
        <b><?= $docs_vencer ?></b>
        <span>Docs a vencer (15d)</span>
        <?php if ($docs_vencer > 0): ?>
            <span class="delta d-erro">Renovar em breve</span>
        <?php endif; ?>
    </div>

</div>

<!-- Grade principal -->
<div class="grade2">

    <!-- Caminhamentos do dia -->
    <div class="card">
        <div class="label">
            Caminhamentos do dia
            <a href="<?= APP_BASE ?>/caminhamentos/cadastrar" class="ver">+ Novo</a>
        </div>

        <?php if (empty($caminhamentos_dia)): ?>
            <div class="alerta a-info">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Nenhum caminhamento programado para hoje.
                <a href="<?= APP_BASE ?>/caminhamentos/cadastrar" class="go">Criar</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Equipe</th>
                            <th>Trechos</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($caminhamentos_dia as $cam): ?>
                            <?php
                            $statusLabel = match($cam['status']) {
                                'rascunho'  => ['Rascunho',  'c-neutro'],
                                'publicado' => ['Publicado', 'c-info'],
                                'execucao'  => ['Em execução','c-aviso'],
                                'concluido' => ['Concluído', 'c-ok'],
                                default     => [$cam['status'], 'c-neutro'],
                            };
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($cam['equipe_nome']) ?></td>
                                <td><?= (int)$cam['total_trechos'] ?></td>
                                <td><span class="chip <?= $statusLabel[1] ?>"><?= $statusLabel[0] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pendências -->
    <div>

        <!-- Trechos sem OS -->
        <?php if (!empty($pendencias_sem_os)): ?>
        <div class="card mb16">
            <div class="label">
                Trechos sem OS
                <a href="<?= APP_BASE ?>/trechos" class="ver">Ver todos</a>
            </div>
            <?php foreach ($pendencias_sem_os as $t): ?>
                <div class="alerta a-aviso">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <div>
                        PV <?= htmlspecialchars($t['pv_montante']) ?> → <?= htmlspecialchars($t['pv_jusante'] ?? '—') ?>
                        <small><?= htmlspecialchars($t['rua'] ?? '') ?><?= !empty($t['bacia']) ? ' | Bacia: ' . htmlspecialchars($t['bacia']) : '' ?></small>
                    </div>
                    <a href="<?= APP_BASE ?>/trechos/editar?id=<?= $t['id'] ?>" class="go">Upload OS</a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Documentos a vencer -->
        <?php if (!empty($pendencias_docs)): ?>
        <div class="card mb16">
            <div class="label">
                Documentos a vencer
                <a href="<?= APP_BASE ?>/funcionarios" class="ver">Funcionários</a>
            </div>
            <?php foreach ($pendencias_docs as $d): ?>
                <?php
                $diasRestantes = (int)((strtotime($d['data_validade']) - strtotime(date('Y-m-d'))) / 86400);
                $classe = $diasRestantes <= 5 ? 'a-erro' : 'a-aviso';
                ?>
                <div class="alerta <?= $classe ?>">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <div>
                        <?= htmlspecialchars($d['tipo']) ?> — <?= htmlspecialchars($d['funcionario_nome']) ?>
                        <small>Vence em <?= $diasRestantes ?> dia<?= $diasRestantes != 1 ? 's' : '' ?> (<?= date('d/m/Y', strtotime($d['data_validade'])) ?>)</small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Repavimentação pendente -->
        <?php if ($repav_pendentes > 0): ?>
        <div class="card">
            <div class="label">Repavimentação</div>
            <div class="alerta a-info">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <div>
                    <b><?= $repav_pendentes ?></b> trecho<?= $repav_pendentes != 1 ? 's' : '' ?> aguardando repavimentação
                </div>
                <a href="<?= APP_BASE ?>/repavimentacao" class="go">Ver fila</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($pendencias_sem_os) && empty($pendencias_docs) && $repav_pendentes == 0): ?>
        <div class="card">
            <div class="alerta a-ok">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Sem pendências críticas no momento.
            </div>
        </div>
        <?php endif; ?>

    </div>

</div>

<!-- Ações rápidas -->
<div class="card mt16">
    <div class="label">Ações rápidas</div>
    <div class="flex-gap">
        <a href="<?= APP_BASE ?>/caminhamentos/cadastrar" class="btn btn-pri">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Novo caminhamento
        </a>
        <a href="<?= APP_BASE ?>/trechos/cadastrar" class="btn btn-sec">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Novo trecho
        </a>
        <a href="<?= APP_BASE ?>/equipes" class="btn btn-sec">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Equipes
        </a>
        <a href="<?= APP_BASE ?>/funcionarios" class="btn btn-sec">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Funcionários
        </a>
        <a href="<?= APP_BASE ?>/materiais" class="btn btn-sec">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            Materiais
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
