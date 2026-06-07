<?php
$title = 'Funcionários';
$pageTitle = 'Funcionários';
$pageSubtitle = 'Cadastro e controle de ASO, NRs e integrações';

ob_start();
?>

<?php if (!empty($_SESSION['import_result'])): ?>
    <div class="card">
        <strong>Importação concluída</strong><br>
        Importados: <?= $_SESSION['import_result']['importados'] ?><br>

        <?php if (!empty($_SESSION['import_result']['erros'])): ?>
            <div style="margin-top:10px;color:#ff6b6b;">
                <?php foreach ($_SESSION['import_result']['erros'] as $erro): ?>
                    <?= htmlspecialchars($erro ?? '') ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php unset($_SESSION['import_result']); ?>
<?php endif; ?>

<div class="card">

    <div class="form-actions" style="margin-bottom:20px;">
        <a href="<?= APP_BASE ?>/funcionarios/cadastrar" class="btn btn-pri">
            + Novo Funcionário
        </a>
    
        <a href="<?= APP_BASE ?>/funcionarios/importar" class="btn btn-sec">
            Importar Excel
        </a>
        <a href="<?= APP_BASE ?>/assets/modelos/funcionarios.xlsx" class="btn btn-sec" download>⬇ Modelo</a>
    </div>

    <!-- 🔥 Wrapper para scroll horizontal -->
    <div class="table-wrap">
        <table class="">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Empresa</th>
                    <th>Função</th>
                    <th>ASO</th>
                    <th>NRs</th>
                    <th>Status</th>
                    <th style="width:160px;">Ações</th>
                </tr>
            </thead>
            <tbody>

            <?php if (empty($funcionarios)): ?>
                <tr>
                    <td colspan="7">Nenhum funcionário cadastrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($funcionarios as $f): ?>
                    <tr>
                        <td><?= htmlspecialchars($f['nome'] ?? '') ?></td>
                        <td><?= htmlspecialchars($f['empresa'] ?? '') ?></td>
                        <td><?= htmlspecialchars($f['funcao'] ?? '') ?></td>

                        <td>
                            <?= $f['aso'] == 1 ? 'Apto' : ($f['aso'] == 2 ? 'Inapto' : 'N/A') ?>
                        </td>

                        <td>
                            <?= (
                                $f['nr06']==2 || $f['nr10']==2 || $f['nr11']==2 ||
                                $f['nr12']==2 || $f['nr18']==2 || $f['nr20']==2 ||
                                $f['nr23']==2 || $f['nr33']==2 || $f['nr35']==2
                            ) ? '⚠️ Pendências' : 'OK' ?>
                        </td>

                        <td>
                            <?php if (!empty($f['ativo'])): ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inativo</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <a href="<?= APP_BASE ?>/funcionarios/editar?id=<?= $f['id'] ?>"
                               class="btn btn-sec btn-sm">Editar</a>
                    
                            <a href="<?= APP_BASE ?>/funcionarios/inativar?id=<?= $f['id'] ?>"
                               class="btn btn-sec btn-sm"
                               onclick="return confirm('Deseja alterar o status deste funcionário?')">
                               <?= !empty($f['ativo']) ? 'Inativar' : 'Ativar' ?>
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
