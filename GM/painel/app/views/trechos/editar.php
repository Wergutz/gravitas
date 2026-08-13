<?php
require_once __DIR__ . '/../../helpers/csrf.php';

$title     = 'Editar Trecho';
$pageTitle = 'Editar Trecho';
$pageSubtitle = 'PV ' . htmlspecialchars($trecho['pv_montante'] ?? '');

ob_start();
?>

<div class="card" style="max-width:860px;">
    <form method="post" action="<?= APP_BASE ?>/trechos/atualizar">
        <?= csrf_input() ?>
        <input type="hidden" name="id" value="<?= (int)$trecho['id'] ?>">

        <div class="form-grid col2">
            <div class="campo">
                <label>PV Montante <span style="color:var(--erro)">*</span></label>
                <input type="text" name="pv_montante" required
                       value="<?= htmlspecialchars($trecho['pv_montante']) ?>">
            </div>
            <div class="campo">
                <label>PV Jusante</label>
                <input type="text" name="pv_jusante"
                       value="<?= htmlspecialchars($trecho['pv_jusante'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Bacia</label>
                <input type="text" name="bacia"
                       value="<?= htmlspecialchars($trecho['bacia'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Tipo PI Montante</label>
                <input type="text" name="tipo_pi_montante"
                       value="<?= htmlspecialchars($trecho['tipo_pi_montante'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Extensão (m)</label>
                <input type="text" name="extensao"
                       value="<?= htmlspecialchars($trecho['extensao'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Profundidade Média (m)</label>
                <input type="text" name="profundidade_media"
                       value="<?= htmlspecialchars($trecho['profundidade_media'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>DN</label>
                <input type="text" name="dn"
                       value="<?= htmlspecialchars($trecho['dn'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Ramais</label>
                <input type="number" name="ramais" min="0"
                       value="<?= (int)($trecho['ramais'] ?? 0) ?>">
            </div>
        </div>

        <div class="form-grid col3">
            <div class="campo">
                <label>Rua</label>
                <input type="text" name="rua"
                       value="<?= htmlspecialchars($trecho['rua'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Cidade</label>
                <input type="text" name="cidade"
                       value="<?= htmlspecialchars($trecho['cidade'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Contrato</label>
                <input type="text" name="contrato"
                       value="<?= htmlspecialchars($trecho['contrato'] ?? '') ?>">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-pri">Salvar alterações</button>
            <a href="<?= APP_BASE ?>/trechos?sel=<?= (int)$trecho['id'] ?>" class="btn btn-sec">Cancelar</a>
        </div>
    </form>
</div>

<!-- C1: Materiais do trecho -->
<div class="card" style="max-width:860px;margin-top:20px;">
    <div style="margin-bottom:14px;">
        <h3 style="font-size:15px;font-weight:700;color:var(--navy);">Materiais do Trecho</h3>
        <p style="font-size:12.5px;color:var(--muted);margin-top:2px;">Materiais reservados ao publicar e baixados ao concluir o trecho.</p>
    </div>

    <form id="form-add-mat" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--line);">
        <div class="campo" style="flex:2;min-width:200px;margin-bottom:0;">
            <label>Material</label>
            <select name="material_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($catalogo as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nome']) ?> (<?= htmlspecialchars($c['unidade']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo" style="width:130px;margin-bottom:0;">
            <label>Quantidade</label>
            <input type="text" name="quantidade" placeholder="0,000" required style="width:100%;">
        </div>
        <div style="padding-bottom:0;">
            <button type="submit" class="btn btn-pri" id="btn-add-mat">+ Adicionar</button>
        </div>
    </form>

    <div class="table-wrap">
        <table id="tbl-materiais">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Und.</th>
                    <th style="text-align:right;">Quantidade</th>
                    <th style="width:90px;"></th>
                </tr>
            </thead>
            <tbody id="mat-tbody">
                <?php if (empty($materiais_trecho)): ?>
                <tr id="mat-vazio"><td colspan="4" style="color:var(--muted);text-align:center;padding:16px;font-size:13px;">Nenhum material cadastrado para este trecho.</td></tr>
                <?php else: ?>
                <?php foreach ($materiais_trecho as $m): ?>
                <tr data-id="<?= (int)$m['id'] ?>">
                    <td style="font-weight:600;"><?= htmlspecialchars($m['nome']) ?></td>
                    <td style="color:var(--muted);"><?= htmlspecialchars($m['unidade']) ?></td>
                    <td style="text-align:right;font-weight:700;"><?= number_format((float)$m['quantidade'], 3, ',', '.') ?></td>
                    <td><button class="btn btn-danger btn-sm btn-remover" data-id="<?= (int)$m['id'] ?>">Remover</button></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function() {
    const CSRF = <?= json_encode(csrf_token()) ?>;
    const TRECHO_ID = <?= (int)$trecho['id'] ?>;
    const BASE = <?= json_encode(APP_BASE) ?>;

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function fmtQtd(v) {
        return parseFloat(v).toLocaleString('pt-BR', {minimumFractionDigits:3, maximumFractionDigits:3});
    }

    document.getElementById('form-add-mat').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-add-mat');
        btn.disabled = true;
        const fd = new FormData(this);
        fd.set('_csrf', CSRF);
        fd.set('trecho_id', TRECHO_ID);
        try {
            const res = await fetch(BASE + '/trechos/material-add', {method:'POST', body:fd});
            const data = await res.json();
            if (!data.ok) { alert(data.erro); return; }

            const tbody = document.getElementById('mat-tbody');
            const vazio = document.getElementById('mat-vazio');
            if (vazio) vazio.remove();

            const m = data.item;
            const existing = tbody.querySelector('tr[data-id="' + m.id + '"]');
            if (existing) {
                existing.cells[2].textContent = fmtQtd(m.quantidade);
            } else {
                const tr = document.createElement('tr');
                tr.dataset.id = m.id;
                tr.innerHTML = '<td style="font-weight:600;">' + escHtml(m.nome) + '</td>'
                    + '<td style="color:var(--muted);">' + escHtml(m.unidade) + '</td>'
                    + '<td style="text-align:right;font-weight:700;">' + fmtQtd(m.quantidade) + '</td>'
                    + '<td><button class="btn btn-danger btn-sm btn-remover" data-id="' + m.id + '">Remover</button></td>';
                tbody.appendChild(tr);
            }
            this.reset();
        } finally {
            btn.disabled = false;
        }
    });

    document.getElementById('mat-tbody').addEventListener('click', async function(e) {
        const btn = e.target.closest('.btn-remover');
        if (!btn) return;
        if (!confirm('Remover este material do trecho?')) return;
        btn.disabled = true;
        const id = btn.dataset.id;
        const fd = new FormData();
        fd.set('_csrf', CSRF);
        fd.set('id', id);
        fd.set('trecho_id', TRECHO_ID);
        try {
            const res = await fetch(BASE + '/trechos/material-remove', {method:'POST', body:fd});
            const data = await res.json();
            if (!data.ok) { alert(data.erro); btn.disabled = false; return; }
            const row = document.querySelector('#mat-tbody tr[data-id="' + id + '"]');
            if (row) row.remove();
            if (!document.getElementById('mat-tbody').querySelector('tr')) {
                const tr = document.createElement('tr');
                tr.id = 'mat-vazio';
                tr.innerHTML = '<td colspan="4" style="color:var(--muted);text-align:center;padding:16px;font-size:13px;">Nenhum material cadastrado para este trecho.</td>';
                document.getElementById('mat-tbody').appendChild(tr);
            }
        } catch(err) { btn.disabled = false; }
    });
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
