<?php
$title = 'Equipamentos Pesados';
$pageTitle = 'Equipamentos Pesados da Equipe';

ob_start();
?>

<a href="<?= APP_BASE ?>/equipes/equipamentos/pesados/cadastrar?equipe_id=<?= $equipe_id ?>" class="btn-info">
    + Adicionar Equipamento Pesado
</a>

<table class="table" style="margin-top:20px;">
<tr>
    <th>Tipo</th>
    <th>Modelo</th>
    <th>Placa</th>
    <th>Operador</th>
</tr>
<?php foreach ($equipamentos as $e): ?>
<tr>
    <td><?= $e['tipo'] ?></td>
    <td><?= $e['modelo'] ?></td>
    <td><?= $e['placa'] ?></td>
    <td><?= $e['operador'] ?></td>
</tr>
<?php endforeach; ?>
</table>

<a href="<?= APP_BASE ?>/equipes" class="btn-secondary">Voltar</a>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../layouts/planejador.php';
