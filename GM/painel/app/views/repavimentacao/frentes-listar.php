<?php
require_once __DIR__ . '/../../helpers/csrf.php';

$title        = 'Frentes de Repavimentação';
$pageTitle    = 'Frentes de Repavimentação';
$pageSubtitle = 'Programação das equipes de pavimento';

ob_start();
?>

<div class="topo" style="margin-bottom:14px;">
    <div class="acoes">
        <a href="<?= APP_BASE ?>/repavimentacao/frentes/cadastrar" class="btn btn-pri">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nova Frente
        </a>
        <a href="<?= APP_BASE ?>/repavimentacao" class="btn btn-sec btn-sm">← Fila de repavimentação</a>
    </div>
</div>

<div class="card">
    <?php if (empty($frentes)): ?>
        <p style="color:var(--muted);font-size:13px;">Nenhuma frente cadastrada. Use o botão acima para programar a equipe de pavimento.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Data</th>
                        <th>Equipe</th>
                        <th>Trechos</th>
                        <th>Status</th>
                        <th>Progresso</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($frentes as $f):
                    $statusClass = match($f['status']) {
                        'rascunho'  => 'c-neutro',
                        'publicado' => 'c-info',
                        'execucao'  => 'c-aviso',
                        'concluido' => 'c-ok',
                        default     => 'c-neutro',
                    };
                    $statusLabel = match($f['status']) {
                        'rascunho'  => 'Rascunho',
                        'publicado' => 'Publicado',
                        'execucao'  => 'Em execução',
                        'concluido' => 'Concluído',
                        default     => ucfirst($f['status']),
                    };
                    $total = (int)$f['total_trechos'];
                    $conc  = (int)$f['trechos_concluidos'];
                    $pct   = $total > 0 ? round($conc / $total * 100) : 0;
                ?>
                <tr>
                    <td style="color:var(--muted);font-size:12px;">#<?= $f['id'] ?></td>
                    <td><b><?= date('d/m/Y', strtotime($f['data_execucao'])) ?></b></td>
                    <td><?= htmlspecialchars($f['equipe_nome']) ?></td>
                    <td><?= $total ?> trecho(s)</td>
                    <td><span class="chip <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                    <td>
                        <?php if ($total > 0): ?>
                        <div style="display:flex;align-items:center;gap:6px;font-size:12px;">
                            <div style="flex:1;background:var(--line);border-radius:4px;height:6px;">
                                <div style="width:<?= $pct ?>%;background:var(--verde);border-radius:4px;height:6px;"></div>
                            </div>
                            <span style="color:var(--muted);white-space:nowrap;"><?= $conc ?>/<?= $total ?></span>
                        </div>
                        <?php else: ?>
                        <span style="color:var(--muted);font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="acoes-cell">
                        <?php if (in_array($f['status'], ['rascunho', 'publicado'])): ?>
                        <form method="post" action="<?= APP_BASE ?>/repavimentacao/frentes/publicar" style="display:inline;">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                            <button type="submit" class="btn btn-sm <?= $f['status'] === 'rascunho' ? 'btn-pri' : 'btn-sec' ?>">
                                <?= $f['status'] === 'rascunho' ? 'Publicar' : 'Despublicar' ?>
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php if (!in_array($f['status'], ['execucao', 'concluido'])): ?>
                        <form method="post" action="<?= APP_BASE ?>/repavimentacao/frentes/excluir"
                              style="display:inline;" data-confirmar="Excluir esta frente?" data-cor="#ef4444">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="modal-confirm" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:14px;padding:28px 28px 20px;max-width:380px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.25)">
    <div id="modal-msg" style="font-size:15px;font-weight:600;color:#1E2738;margin-bottom:20px"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button onclick="modalResponder(false)" style="padding:9px 18px;border:1px solid #E4E8EF;border-radius:8px;background:#fff;font-size:13px;font-weight:600;cursor:pointer;">Cancelar</button>
      <button id="modal-btn-ok" onclick="modalResponder(true)" style="padding:9px 18px;border:0;border-radius:8px;color:#fff;font-size:13px;font-weight:700;cursor:pointer;">Confirmar</button>
    </div>
  </div>
</div>
<script>
let _modalForm = null, _modalResolve = null;
function abrirModal(msg, cor) {
  document.getElementById('modal-msg').textContent = msg;
  document.getElementById('modal-btn-ok').style.background = cor || '#1A2D4F';
  const el = document.getElementById('modal-confirm');
  el.style.display = 'flex';
  return new Promise(r => { _modalResolve = r; });
}
function modalResponder(ok) {
  document.getElementById('modal-confirm').style.display = 'none';
  if (_modalResolve) _modalResolve(ok);
}
document.querySelectorAll('form[data-confirmar]').forEach(form => {
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const ok = await abrirModal(this.dataset.confirmar, this.dataset.cor);
    if (ok) this.submit();
  });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
