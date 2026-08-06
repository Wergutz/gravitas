<?php
$title = 'Equipes';
$pageTitle = 'Equipes';
$pageSubtitle = 'Gestão de equipes';

ob_start();
?>

<div class="topo" style="margin-bottom:14px;">
    <div class="acoes">
        <a href="<?= APP_BASE ?>/equipes/cadastrar" class="btn btn-pri">
            + Nova Equipe
        </a>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Responsável</th>
                    <th>Composição</th>
                    <th style="width:140px;">Ações</th>
                </tr>
            </thead>
            <tbody>

            <?php if (empty($equipes)): ?>
                <tr>
                    <td colspan="4">Nenhuma equipe cadastrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($equipes as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['nome']) ?></td>
                        <td><?= htmlspecialchars($e['responsavel_nome'] ?? '—') ?></td>
                        <td style="font-size:12.5px;color:var(--muted);">
                            <?= (int)$e['num_funcionarios'] ?> func.
                            · <?= (int)($e['num_leves'] + $e['num_pesados']) ?> equip.
                        </td>
                        <td>
                            <a href="<?= APP_BASE ?>/equipes/editar?id=<?= $e['id'] ?>"
                               class="btn btn-sec btn-sm">
                                Editar
                            </a>

                            <a href="<?= APP_BASE ?>/equipes/apagar?id=<?= $e['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Deseja apagar esta equipe?')">
                                Apagar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
