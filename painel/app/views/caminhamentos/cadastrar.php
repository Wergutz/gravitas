<?php
require_once __DIR__ . '/../../helpers/csrf.php';

$title     = 'Novo Caminhamento';
$pageTitle = 'Novo Caminhamento';
$pageSubtitle = 'Programar jornada de campo';

ob_start();
?>

<div class="grade-eq" style="align-items:start;">

    <!-- Formulário principal -->
    <div class="card">
        <form method="post" action="<?= APP_BASE ?>/caminhamentos/salvar" id="form-caminhamento">
            <?= csrf_input() ?>

            <div class="form-grid col2">
                <div class="campo">
                    <label>Equipe <span style="color:var(--erro)">*</span></label>
                    <select name="equipe_id" required>
                        <option value="">Selecione a equipe</option>
                        <?php foreach ($equipes as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label>Data de execução <span style="color:var(--erro)">*</span></label>
                    <input type="date" name="data_execucao" required
                           value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div class="campo" style="margin-bottom:16px;">
                <label>Observações</label>
                <textarea name="observacoes" rows="3" placeholder="Observações gerais..."></textarea>
            </div>

            <!-- Container para trechos selecionados -->
            <div id="trechos-selecionados" style="margin-bottom:16px;">
                <div class="label">Trechos selecionados <span id="contagem-sel" style="color:var(--navy-500);font-weight:800;">0</span></div>
                <div id="lista-selecionados" style="display:flex;flex-wrap:wrap;gap:8px;">
                    <p id="msg-vazio" style="color:var(--muted);font-size:12px;">Nenhum trecho selecionado. Clique nos trechos ao lado para adicionar.</p>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-pri">Salvar caminhamento</button>
                <a href="<?= APP_BASE ?>/caminhamentos" class="btn btn-sec">Cancelar</a>
            </div>
        </form>
    </div>

    <!-- Lista de trechos disponíveis -->
    <div class="card">
        <div class="label">
            Trechos disponíveis
            <span style="color:var(--muted);font-weight:600;font-size:11px;">(com OS, status livre)</span>
        </div>

        <?php if (empty($trechos_disponiveis)): ?>
            <div class="alerta a-info">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Nenhum trecho disponível. Verifique se há trechos com OS ativa e status "livre".
            </div>
        <?php else: ?>
            <div style="margin-bottom:10px;">
                <input type="text" id="busca-trecho" placeholder="Buscar por PV ou rua..."
                       style="width:100%;font-family:inherit;font-size:13px;padding:8px 10px;border:1px solid var(--line);border-radius:8px;"
                       oninput="filtrarTrechos(this.value)">
            </div>
            <div id="lista-disponiveis" style="max-height:500px;overflow-y:auto;display:flex;flex-direction:column;gap:6px;">
                <?php foreach ($trechos_disponiveis as $t): ?>
                    <div class="item-trecho" data-id="<?= $t['id'] ?>"
                         data-pv="<?= htmlspecialchars(strtolower($t['pv_montante'])) ?>"
                         data-rua="<?= htmlspecialchars(strtolower($t['rua'] ?? '')) ?>"
                         onclick="toggleTrecho(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['pv_montante'])) ?> → <?= htmlspecialchars(addslashes($t['pv_jusante'] ?? '—')) ?>')"
                         style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--line);border-radius:8px;cursor:pointer;font-size:12.5px;background:#fff;transition:background .1s;">
                        <span style="width:18px;height:18px;border:2px solid var(--line);border-radius:4px;flex:0 0 auto;display:grid;place-items:center;" class="check-icon" id="check-<?= $t['id'] ?>"></span>
                        <div>
                            <b style="color:var(--navy);">PV <?= htmlspecialchars($t['pv_montante']) ?> → <?= htmlspecialchars($t['pv_jusante'] ?? '—') ?></b>
                            <span style="color:var(--muted);margin-left:8px;"><?= htmlspecialchars($t['bacia'] ?? '') ?></span>
                            <?php if ($t['rua']): ?>
                                <br><span style="color:var(--muted);font-size:11px;"><?= htmlspecialchars($t['rua']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($t['extensao']): ?>
                            <span style="margin-left:auto;color:var(--muted);font-size:11px;white-space:nowrap;">
                                <?= number_format((float)$t['extensao'], 0, ',', '.') ?> m
                            </span>
                        <?php endif; ?>
                        <span class="chip c-ok" style="margin-left:4px;">OS v<?= (int)$t['os_versao'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
var selecionados = {};

function toggleTrecho(id, label) {
    if (selecionados[id]) {
        delete selecionados[id];
        var el = document.getElementById('check-' + id);
        if (el) { el.innerHTML = ''; el.style.borderColor = 'var(--line)'; el.style.background = ''; }
        var item = document.querySelector('.item-trecho[data-id="' + id + '"]');
        if (item) item.style.background = '#fff';
    } else {
        selecionados[id] = label;
        var el = document.getElementById('check-' + id);
        if (el) {
            el.innerHTML = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
            el.style.background = 'var(--navy)';
            el.style.borderColor = 'var(--navy)';
        }
        var item = document.querySelector('.item-trecho[data-id="' + id + '"]');
        if (item) item.style.background = '#f0f4fb';
    }
    renderSelecionados();
}

function renderSelecionados() {
    var container = document.getElementById('lista-selecionados');
    var msg = document.getElementById('msg-vazio');
    var count = Object.keys(selecionados).length;

    document.getElementById('contagem-sel').textContent = count;

    // Remove inputs antigos
    var old = document.querySelectorAll('input[name="trechos[]"]');
    old.forEach(function(el) { el.remove(); });

    if (count === 0) {
        container.innerHTML = '';
        container.appendChild(msg);
        return;
    }
    container.innerHTML = '';
    Object.keys(selecionados).forEach(function(id) {
        // Chip visual
        var chip = document.createElement('span');
        chip.className = 'chip c-info';
        chip.style.cursor = 'pointer';
        chip.title = 'Clique para remover';
        chip.textContent = selecionados[id] + ' ✕';
        chip.onclick = function() { toggleTrecho(id, selecionados[id]); };
        container.appendChild(chip);

        // Input hidden
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'trechos[]';
        inp.value = id;
        document.getElementById('form-caminhamento').appendChild(inp);
    });
}

function filtrarTrechos(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.item-trecho').forEach(function(el) {
        var pv  = el.getAttribute('data-pv') || '';
        var rua = el.getAttribute('data-rua') || '';
        el.style.display = (pv.includes(q) || rua.includes(q)) ? '' : 'none';
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
