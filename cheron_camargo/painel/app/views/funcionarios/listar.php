<?php
$title = 'Funcionários';
$pageTitle = 'Funcionários';
$pageSubtitle = 'Cadastro e controle de ASO, NRs e integrações';

ob_start();
?>

<div class="topo" style="margin-bottom:14px;">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <a href="<?= APP_BASE ?>/funcionarios" class="btn btn-sec btn-sm<?= !$filtro_aptos ? ' btn-ativo' : '' ?>">Todos</a>
        <a href="<?= APP_BASE ?>/funcionarios?aptos=1" class="btn btn-sec btn-sm<?= $filtro_aptos ? ' btn-ativo' : '' ?>">✅ Aptos para campo</a>
    </div>
    <div class="acoes">
        <a href="<?= APP_BASE ?>/funcionarios/cadastrar" class="btn btn-pri">+ Novo Funcionário</a>
        <a href="<?= APP_BASE ?>/funcionarios/importar" class="btn btn-sec">Importar Excel</a>
        <a href="<?= APP_BASE ?>/assets/modelos/funcionarios.xlsx" class="btn btn-sec" download>⬇ Modelo</a>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Empresa</th>
                    <th>Função</th>
                    <th>Docs</th>
                    <th>Status</th>
                    <th style="width:140px;">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($funcionarios)): ?>
                <tr><td colspan="6" style="color:var(--muted);">Nenhum funcionário encontrado.</td></tr>
            <?php else: ?>
                <?php foreach ($funcionarios as $f):
                    $sd = $f['status_docs'] ?? 'sem_info';
                    $badge = match($sd) {
                        'vencido'  => ['🔴 Vencido',  '#f8d7da', '#721c24'],
                        'a_vencer' => ['🟡 A vencer', '#fff3cd', '#856404'],
                        'em_dia'   => ['🟢 Em dia',   '#d4edda', '#155724'],
                        default    => ['⚪ Sem dados', '#f8f9fa', '#6c757d'],
                    };
                ?>
                <tr>
                    <td><b><?= htmlspecialchars($f['nome'] ?? '') ?></b></td>
                    <td><?= htmlspecialchars($f['empresa'] ?? '') ?></td>
                    <td><?= htmlspecialchars($f['funcao'] ?? '') ?></td>
                    <td>
                        <span style="background:<?= $badge[1] ?>;color:<?= $badge[2] ?>;padding:2px 10px;border-radius:6px;font-size:12px;font-weight:600;white-space:nowrap;">
                            <?= $badge[0] ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($f['ativo'])): ?>
                            <span class="chip c-ok">Ativo</span>
                        <?php else: ?>
                            <span class="chip c-erro">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= APP_BASE ?>/funcionarios/editar?id=<?= $f['id'] ?>" class="btn btn-sec btn-sm">Editar</a>
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
