<?php
$title = 'Equipe';
$pageTitle = $equipe['nome'];
$pageSubtitle = 'Detalhes da equipe';

ob_start();
?>

<div class="form-card">
    <strong>Responsável:</strong>
    <?= htmlspecialchars($equipe['responsavel']) ?>
</div>

<?php if (!empty($pendencias)): ?>
<div class="form-card" style="border-left:4px solid #ffc107;">
    <h3 style="margin-bottom:10px;">⚠️ Pendências de Conformidade</h3>

    <?php foreach ($pendencias as $p): ?>
        <div style="margin-bottom:12px;">
            <strong><?= htmlspecialchars($p['nome']) ?></strong>
            <span style="opacity:.7;">(<?= htmlspecialchars($p['funcao']) ?>)</span><br>

            <?php foreach ($p['itens'] as $item): ?>
                <span class="badge badge-danger"><?= $item ?></span>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <p style="font-size:13px;opacity:.7;">
        ⚠️ Estas pendências não impedem o planejamento ou a execução.
    </p>
</div>
<?php endif; ?>

<a href="<?= APP_BASE ?>/equipes" class="btn-secondary">Voltar</a>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
