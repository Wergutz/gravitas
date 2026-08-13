<?php
require_once __DIR__ . '/../../helpers/csrf.php';

$title     = 'Caminhamentos';
$pageTitle = 'Caminhamentos';
$pageSubtitle = 'Programação diária de campo';

ob_start();
?>

<div class="topo" style="margin-bottom:14px;">
    <div class="acoes">
        <a href="<?= APP_BASE ?>/caminhamentos/cadastrar" class="btn btn-pri">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Novo Caminhamento
        </a>
    </div>
</div>

<div class="card">
    <?php if (empty($caminhamentos)): ?>
        <p style="color:var(--muted);font-size:13px;">Nenhum caminhamento cadastrado.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Data</th>
                        <th>Equipe</th>
                        <th>Trechos</th>
                        <th>Status</th>
                        <th>Progresso</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($caminhamentos as $cam): ?>
                        <?php
                        $statusClass = match($cam['status']) {
                            'rascunho'  => 'c-neutro',
                            'publicado' => 'c-info',
                            'execucao'  => 'c-aviso',
                            'concluido' => 'c-ok',
                            default     => 'c-neutro',
                        };
                        $statusLabel = match($cam['status']) {
                            'rascunho'  => 'Rascunho',
                            'publicado' => 'Publicado',
                            'execucao'  => 'Em execução',
                            'concluido' => 'Concluído',
                            default     => $cam['status'],
                        };
                        ?>
                        <tr>
                            <td><?= (int)$cam['id'] ?></td>
                            <td><?= date('d/m/Y', strtotime($cam['data_execucao'])) ?></td>
                            <td><?= htmlspecialchars($cam['equipe_nome']) ?></td>
                            <td><?= (int)$cam['total_trechos'] ?></td>
                            <td><span class="chip <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                            <td>
                                <?php
                                $total = (int)$cam['total_trechos'];
                                $conc  = (int)$cam['trechos_concluidos'];
                                ?>
                                <?php if ($total > 0): ?>
                                    <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);">
                                        <div style="flex:1;height:6px;border-radius:99px;background:var(--line);min-width:50px;overflow:hidden;">
                                            <div style="height:100%;border-radius:99px;background:var(--ok);width:<?= $total > 0 ? round($conc/$total*100) : 0 ?>%;"></div>
                                        </div>
                                        <?= $conc ?>/<?= $total ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <a href="<?= APP_BASE ?>/caminhamentos/detalhe?id=<?= (int)$cam['id'] ?>"
                                       class="btn btn-sec btn-sm">Ver</a>
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
