<?php
$title = 'Planejamentos';
$pageTitle = 'Planejamentos';
$pageSubtitle = 'Gestão de planejamentos';

ob_start();
?>

<div class="card">

    <div class="form-actions">
        <a href="<?= APP_BASE ?>/planejamentos/cadastrar" class="btn btn-pri btn-sm">
            + Novo Planejamento
        </a>
    </div>

    <div class="table-wrap">
        <table class="">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Equipe</th>
                    <th>Macro</th>
                    <th>Cidade</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($planejamentos as $p): ?>
                <tr>
                    <td>
                        <?= !empty($p['data_execucao'])
                            ? date('d/m/Y', strtotime($p['data_execucao']))
                            : '-' ?>
                    </td>
            
                    <td><?= htmlspecialchars($p['equipe_nome'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['macro'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['cidade'] ?? '-') ?></td>
            
                    <td>
                        <a href="<?= APP_BASE ?>/planejamentos/editar?id=<?= $p['id'] ?>" class="btn btn-sec btn-sm">
                            Editar
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
