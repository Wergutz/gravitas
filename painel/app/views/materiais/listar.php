<?php
require_once __DIR__ . '/../../helpers/csrf.php';

$title     = 'Materiais';
$pageTitle = 'Materiais';
$pageSubtitle = 'Catálogo e controle de estoque';

ob_start();
?>

<div class="topo" style="margin-bottom:14px;">
    <div class="acoes">
        <a href="<?= APP_BASE ?>/materiais/importar" class="btn btn-sec">Importar Catálogo</a>
        <a href="<?= APP_BASE ?>/materiais/importar-estoque" class="btn btn-sec">Importar Contagem</a>
        <a href="<?= APP_BASE ?>/materiais/cadastrar" class="btn btn-pri">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Novo Material
        </a>
    </div>
</div>

<div class="card">
    <?php if (empty($materiais)): ?>
        <p style="color:var(--muted);font-size:13px;">Nenhum material cadastrado.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nome</th>
                        <th>Unid.</th>
                        <th>Físico</th>
                        <th>Reservado</th>
                        <th>Disponível</th>
                        <th>Mín.</th>
                        <th>Estoque</th>
                        <th>Movimento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materiais as $m): ?>
                        <?php
                        $disponivel = (float)$m['qtd_disponivel'];
                        $minimo     = (float)$m['estoque_minimo'];
                        $alerta     = ($minimo > 0 && $disponivel < $minimo);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($m['codigo'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($m['nome']) ?></td>
                            <td><?= htmlspecialchars($m['unidade']) ?></td>
                            <td><?= number_format((float)$m['qtd_fisica'], 3, ',', '.') ?></td>
                            <td><?= number_format((float)$m['qtd_reservada'], 3, ',', '.') ?></td>
                            <td>
                                <b><?= number_format($disponivel, 3, ',', '.') ?></b>
                            </td>
                            <td><?= number_format($minimo, 3, ',', '.') ?></td>
                            <td>
                                <?php if ($alerta): ?>
                                    <span class="chip c-erro">Baixo</span>
                                <?php else: ?>
                                    <span class="chip c-ok">OK</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sec btn-sm"
                                        onclick="abrirMovimento(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['nome'])) ?>')">
                                    Lançar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de movimento -->
<div id="modal-movimento" style="display:none;position:fixed;inset:0;background:#0006;z-index:999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:24px;width:400px;max-width:94vw;">
        <h3 style="margin-bottom:16px;font-size:16px;color:var(--navy);">Lançar Movimento</h3>
        <p id="mov-nome" style="margin-bottom:14px;font-size:13px;color:var(--muted);"></p>
        <form method="post" action="<?= APP_BASE ?>/materiais/movimento">
            <?= csrf_input() ?>
            <input type="hidden" name="material_id" id="mov-id">
            <div class="form-grid col2">
                <div class="campo">
                    <label>Tipo</label>
                    <select name="tipo" required>
                        <option value="entrada">Entrada</option>
                        <option value="ajuste">Ajuste (substituir)</option>
                    </select>
                </div>
                <div class="campo">
                    <label>Quantidade</label>
                    <input type="text" name="quantidade" required placeholder="0.000">
                </div>
            </div>
            <div class="campo" style="margin-bottom:16px;">
                <label>Observação</label>
                <input type="text" name="observacao" placeholder="Opcional">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-pri">Confirmar</button>
                <button type="button" class="btn btn-sec" onclick="fecharMovimento()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirMovimento(id, nome) {
    document.getElementById('mov-id').value = id;
    document.getElementById('mov-nome').textContent = nome;
    var modal = document.getElementById('modal-movimento');
    modal.style.display = 'flex';
}
function fecharMovimento() {
    document.getElementById('modal-movimento').style.display = 'none';
}
document.getElementById('modal-movimento').addEventListener('click', function(e) {
    if (e.target === this) fecharMovimento();
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
