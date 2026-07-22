<?php
$title = 'Funcionários da Equipe';
$pageTitle = 'Funcionários';
$pageSubtitle = 'Funcionários vinculados à equipe';

ob_start();
?>

<a href="<?= APP_BASE ?>/equipes/funcionarios/cadastrar?equipe_id=<?= $equipe_id ?>" class="btn-info">
    + Adicionar Funcionário
</a>

<table class="table" style="margin-top:20px;">
    <thead>
        <tr>
            <th>Nome</th>
            <th>Função</th>
            <th>ASO</th>
            <th>SERTRAS</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($funcionarios as $f): ?>
        <tr>
            <td><?= htmlspecialchars($f['nome']) ?></td>
            <td><?= htmlspecialchars($f['funcao']) ?></td>
            <td>
                <?= $f['aso'] == 1 ? '<span class="badge badge-success">APTO</span>' : '<span class="badge badge-danger">INAPTO</span>' ?>
            </td>
            <td>
                <?= $f['sertras'] == 1 ? '<span class="badge badge-success">APTO</span>' : '<span class="badge badge-danger">INAPTO</span>' ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<a href="<?= APP_BASE ?>/equipes" class="btn-secondary">Voltar</a>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/planejador.php';
