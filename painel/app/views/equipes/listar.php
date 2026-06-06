<?php
$title = 'Equipes';
$pageTitle = 'Equipes';
$pageSubtitle = 'Gestão de equipes';

ob_start();
?>

<div class="form-card">

    <div class="form-actions">
        <a href="<?= APP_BASE ?>/equipes/cadastrar" class="btn-primary">
            + Nova Equipe
        </a>
    </div>

    <div class="table-wrapper">
        <table class="table table-equipes">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Responsável</th>
                    <th style="width:140px;">Ações</th>
                </tr>
            </thead>
            <tbody>

            <?php if (empty($equipes)): ?>
                <tr>
                    <td colspan="3">Nenhuma equipe cadastrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($equipes as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['nome']) ?></td>
                        <td><?= htmlspecialchars($e['responsavel_nome'] ?? '') ?></td>
                        <td>
                            <a href="<?= APP_BASE ?>/equipes/editar?id=<?= $e['id'] ?>"
                               class="btn-info btn-sm">
                                Editar
                            </a>

                            <a href="<?= APP_BASE ?>/equipes/apagar?id=<?= $e['id'] ?>"
                               class="btn-secondary btn-sm"
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
