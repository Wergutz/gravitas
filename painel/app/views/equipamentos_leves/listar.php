<?php
$title = 'Equipamentos Leves';
$pageTitle = 'Equipamentos Leves';
$pageSubtitle = 'Cadastro e controle';

ob_start();
?>

<div class="card">

    <div class="form-actions" style="margin-bottom:20px;">
        <a href="<?= APP_BASE ?>/equipamentos-leves/cadastrar" class="btn btn-pri">
            + Novo Equipamento
        </a>

        <a href="<?= APP_BASE ?>/equipamentos-leves/importar" class="btn btn-sec">
            Importar Excel
        </a>
    </div>

    <!-- 🔥 Wrapper para scroll horizontal -->
    <div class="table-wrap">
        <table class="">
            <thead>
                <tr>
                    <th>Referência</th>
                    <th>Fabricante</th>
                    <th>Modelo</th>
                    <th>Ano</th>
                    <th>Proprietário</th>
                    <th>Combustível</th>
                    <th>Status</th>
                    <th style="width:160px;">Ações</th>
                </tr>
            </thead>
            <tbody>

            <?php if (empty($equipamentos)): ?>
                <tr>
                    <td colspan="8">Nenhum equipamento cadastrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($equipamentos as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['referencia'] ?? '') ?></td>
                        <td><?= htmlspecialchars($e['fabricante'] ?? '') ?></td>
                        <td><?= htmlspecialchars($e['modelo'] ?? '') ?></td>
                        <td><?= htmlspecialchars($e['ano'] ?? '') ?></td>
                        <td><?= htmlspecialchars($e['proprietario'] ?? '') ?></td>
                        <td><?= htmlspecialchars($e['combustivel'] ?? '') ?></td>
                        <td>
                            <?php if (!empty($e['ativo'])): ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= APP_BASE ?>/equipamentos-leves/editar?id=<?= $e['id'] ?>"
                               class="btn btn-sec btn-sm">
                                Editar
                            </a>

                            <a href="<?= APP_BASE ?>/equipamentos-leves/inativar?id=<?= $e['id'] ?>"
                               class="btn btn-sec btn-sm"
                               onclick="return confirm('Confirmar alteração de status?')">
                                <?= !empty($e['ativo']) ? 'Inativar' : 'Ativar' ?>
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
