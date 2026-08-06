<?php
$title = 'Equipamentos Leves';
$pageTitle = 'Equipamentos Leves da Equipe';

ob_start();
?>

<a href="<?= APP_BASE ?>/equipes/equipamentos/leves/cadastrar?equipe_id=<?= $equipe_id ?>" class="btn-info">
    + Adicionar Equipamento Leve
</a>

<table class="table" style="margin-top:20px;">
<tr>
    <th>Tipo</th>
    <th>Modelo</th>
    <th>Quantidade</th>
</tr>
<?php foreach ($equipamentos as $e): ?>
<tr>
    <td><?= $e['tipo'] ?></td>
    <td><?= $e['modelo'] ?></td>
    <td><?= $e['quantidade'] ?></td>
</tr>
<?php endforeach; ?>
</table>

<a href="<?= APP_BASE ?>/equipes" class="btn-secondary">Voltar</a>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../layouts/planejador.php';
