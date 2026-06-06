<?php
$title     = 'Usuários & Acessos';
$pageTitle = 'Usuários & Acessos';
$pageSubtitle = 'Gerencie contas, perfis e senhas do sistema';

// Perfil → cor + rótulo
$perfilCor = [
    3 => ['#1a2d4f12', '#1A2D4F', 'Master Gravitas'],
    4 => ['#2e9e8f14', '#1F7A6E', 'Planejador'],
    5 => ['#3e86c91a', '#2F6BA6', 'Executor de Rede'],
    6 => ['#7a4fb018', '#7A4FB0', 'Cliente Master'],
    7 => ['#c9853d1c', '#A96C2A', 'Executor de Repavimentação'],
];

ob_start();
?>

<style>
/* ── KPI strip ── */
.kpis5 { display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:22px; }
@media(max-width:900px){ .kpis5{ grid-template-columns:repeat(2,1fr); } }

/* ── Filtros ── */
.filtros { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; align-items:center; }
.filtros input, .filtros select { font-family:inherit; font-size:13px; padding:9px 12px;
  border:1px solid var(--line); border-radius:9px; background:#fff; color:var(--ink); }
.filtros input { flex:1; min-width:200px; }

/* ── Tabela ── */
.badge-perfil { display:inline-flex; align-items:center; font-size:11px; font-weight:700;
  border-radius:999px; padding:3px 10px; white-space:nowrap; }
.status-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:6px; }
.acoes-td { display:flex; gap:6px; align-items:center; }

/* ── Modal ── */
#modal-bd { display:none; position:fixed; inset:0; background:#00000066; z-index:100;
  align-items:center; justify-content:center; }
#modal-bd.aberto { display:flex; }
#modal { background:#fff; border-radius:16px; padding:28px 30px; width:100%; max-width:500px;
  box-shadow:0 24px 70px #0007; position:relative; max-height:90vh; overflow-y:auto; }
#modal h2 { font-size:18px; font-weight:800; margin-bottom:20px; color:var(--navy); }
#modal .close { position:absolute; top:18px; right:18px; border:0; background:#f2f4f8;
  width:30px; height:30px; border-radius:8px; font-size:16px; color:var(--muted); cursor:pointer; }

/* ── Toast ── */
#toast { position:fixed; bottom:24px; left:50%; transform:translateX(-50%);
  background:var(--navy); color:#fff; padding:11px 20px; border-radius:20px;
  font-size:13px; font-weight:700; z-index:200; opacity:0; transition:opacity .3s;
  pointer-events:none; white-space:nowrap; }
#toast.show { opacity:1; }

/* ── Senha provisória ── */
#senha-box { background:#f0f9ff; border:1px solid #3e86c955; border-radius:10px;
  padding:14px 16px; margin-top:16px; display:none; }
#senha-box b { font-family:monospace; font-size:17px; color:var(--navy); letter-spacing:2px; }
#senha-box p { font-size:12px; color:var(--muted); margin-top:6px; }
</style>

<!-- KPIs -->
<div class="kpis5">
  <div class="kpi">
    <div class="ic ic-navy">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <b><?= (int)($kpis['total'] ?? 0) ?></b>
    <span>Total de usuários</span>
  </div>
  <div class="kpi">
    <div class="ic ic-ok">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </div>
    <b><?= (int)($kpis['ativos'] ?? 0) ?></b>
    <span>Ativos</span>
  </div>
  <div class="kpi">
    <div class="ic ic-info">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2" width="10" height="20" rx="2.5"/><line x1="10.5" y1="18.5" x2="13.5" y2="18.5"/></svg>
    </div>
    <b><?= (int)($kpis['executores'] ?? 0) ?></b>
    <span>Executores (campo)</span>
  </div>
  <div class="kpi">
    <div class="ic" style="background:#7a4fb018;color:#7A4FB0">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </div>
    <b><?= (int)($kpis['clientes_master'] ?? 0) ?></b>
    <span>Clientes Master</span>
  </div>
  <div class="kpi">
    <div class="ic" style="background:#1a2d4f12;color:#1A2D4F">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M2 20s1.5-4 10-4 10 4 10 4"/><path d="M18 14l2 2 4-4"/></svg>
    </div>
    <b><?= (int)($kpis['masters'] ?? 0) ?></b>
    <span>Master Gravitas</span>
  </div>
</div>

<!-- Lista -->
<div class="card">
  <div class="filtros">
    <input type="text" id="f-busca" placeholder="Buscar nome ou e-mail…" oninput="filtrar()">
    <select id="f-perfil" onchange="filtrar()">
      <option value="">Todos os perfis</option>
      <option value="3">Master Gravitas</option>
      <option value="4">Planejador</option>
      <option value="5">Executor de Rede</option>
      <option value="6">Cliente Master</option>
      <option value="7">Executor de Repavimentação</option>
    </select>
    <select id="f-status" onchange="filtrar()">
      <option value="">Todos</option>
      <option value="1">Ativo</option>
      <option value="0">Inativo</option>
    </select>
    <button class="btn btn-pri" onclick="abrirModalNovo()">+ Novo Usuário</button>
  </div>

  <div class="table-wrap">
    <table id="tabela-usuarios">
      <thead>
        <tr>
          <th>Usuário</th>
          <th>Perfil</th>
          <th>Equipe</th>
          <th>Último acesso</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u):
          $tipo = (int)$u['tipo_usuario'];
          [$bg, $cor, $label] = $perfilCor[$tipo] ?? ['#6b768614', '#6B7686', 'Desconhecido'];
          $ativo = (int)$u['ativo'];
          $acesso = $u['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acesso'])) : '—';
          $iniciais = strtoupper(mb_substr($u['nome'], 0, 1));
        ?>
        <tr data-nome="<?= htmlspecialchars(strtolower($u['nome'])) ?>"
            data-email="<?= htmlspecialchars(strtolower($u['email'])) ?>"
            data-perfil="<?= $tipo ?>"
            data-ativo="<?= $ativo ?>">
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div style="width:34px;height:34px;border-radius:10px;background:<?= $bg ?>;color:<?= $cor ?>;
                          display:grid;place-items:center;font-size:13px;font-weight:800;flex:0 0 auto">
                <?= htmlspecialchars($iniciais) ?>
              </div>
              <div>
                <b style="font-size:13.5px"><?= htmlspecialchars($u['nome']) ?></b>
                <span style="display:block;font-size:11.5px;color:var(--muted)"><?= htmlspecialchars($u['email']) ?></span>
                <?php if ($u['force_password_change']): ?>
                  <span class="chip c-aviso" style="font-size:10px;margin-top:2px">troca de senha pendente</span>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td>
            <span class="badge-perfil" style="background:<?= $bg ?>;color:<?= $cor ?>">
              <?= htmlspecialchars($label) ?>
            </span>
          </td>
          <td style="font-size:12.5px;color:var(--muted)"><?= htmlspecialchars($u['equipe_nome'] ?? '—') ?></td>
          <td style="font-size:12.5px;color:var(--muted)"><?= $acesso ?></td>
          <td>
            <?php if ($ativo): ?>
              <span><span class="status-dot" style="background:var(--ok)"></span><span style="font-size:12.5px;font-weight:600;color:var(--ok)">Ativo</span></span>
            <?php else: ?>
              <span><span class="status-dot" style="background:var(--muted)"></span><span style="font-size:12.5px;font-weight:600;color:var(--muted)">Inativo</span></span>
            <?php endif; ?>
          </td>
          <td>
            <div class="acoes-td">
              <button class="btn btn-sec btn-sm" title="Editar"
                onclick="abrirModalEditar(<?= $u['id'] ?>, <?= htmlspecialchars(json_encode($u['nome']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($u['email']), ENT_QUOTES) ?>, <?= $tipo ?>, <?= (int)($u['equipe_id'] ?? 0) ?>)">
                ✏️
              </button>
              <button class="btn btn-sec btn-sm" title="Resetar senha"
                onclick="confirmarReset(<?= $u['id'] ?>, <?= htmlspecialchars(json_encode($u['nome']), ENT_QUOTES) ?>)">
                🔑
              </button>
              <?php if ($ativo): ?>
                <button class="btn btn-danger btn-sm" title="Inativar"
                  onclick="confirmarToggle(<?= $u['id'] ?>, 0, <?= htmlspecialchars(json_encode($u['nome']), ENT_QUOTES) ?>)">
                  Inativar
                </button>
              <?php else: ?>
                <button class="btn btn-sec btn-sm" title="Ativar" style="color:var(--ok)"
                  onclick="confirmarToggle(<?= $u['id'] ?>, 1, <?= htmlspecialchars(json_encode($u['nome']), ENT_QUOTES) ?>)">
                  Ativar
                </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Modal criar/editar ── -->
<div id="modal-bd" onclick="fecharSeFundo(event)">
  <div id="modal">
    <button class="close" onclick="fecharModal()">✕</button>
    <h2 id="modal-titulo">Novo Usuário</h2>
    <input type="hidden" id="m-id" value="0">

    <div class="form-grid col2">
      <div class="campo" style="grid-column:1/-1">
        <label>Nome completo</label>
        <input type="text" id="m-nome" maxlength="120" placeholder="Nome do usuário">
      </div>
      <div class="campo" style="grid-column:1/-1">
        <label>E-mail</label>
        <input type="email" id="m-email" maxlength="150" placeholder="email@exemplo.com.br">
      </div>
      <div class="campo" style="grid-column:1/-1">
        <label>Perfil de acesso</label>
        <select id="m-perfil" onchange="ajustarCamposModal()">
          <option value="4">Planejador</option>
          <option value="5">Executor de Rede</option>
          <option value="6">Cliente Master</option>
          <option value="7">Executor de Repavimentação</option>
          <option value="3">Master Gravitas</option>
        </select>
      </div>
      <div class="campo" id="campo-equipe" style="grid-column:1/-1;display:none">
        <label>Equipe vinculada</label>
        <select id="m-equipe">
          <option value="">— sem equipe —</option>
          <?php foreach ($equipes as $eq): ?>
            <option value="<?= $eq['id'] ?>"><?= htmlspecialchars($eq['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div id="senha-box">
      <p style="font-size:12px;color:var(--muted);margin-bottom:6px">Senha provisória gerada (copie antes de fechar):</p>
      <b id="senha-valor">—</b>
      <p>O usuário precisará alterar a senha no próximo acesso.</p>
    </div>

    <div class="form-actions">
      <button class="btn btn-pri" id="btn-salvar-modal" onclick="salvarUsuario()">Salvar</button>
      <button class="btn btn-sec" onclick="fecharModal()">Cancelar</button>
    </div>
  </div>
</div>

<!-- ── Toast ── -->
<div id="toast"></div>

<script>
const BASE = '<?= APP_BASE ?>';
const CSRF = '<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>';

// ── Filtro local ──────────────────────────────────────────
function filtrar() {
  const busca  = document.getElementById('f-busca').value.toLowerCase();
  const perfil = document.getElementById('f-perfil').value;
  const status = document.getElementById('f-status').value;
  document.querySelectorAll('#tabela-usuarios tbody tr').forEach(tr => {
    const nome  = tr.dataset.nome  || '';
    const email = tr.dataset.email || '';
    const p     = tr.dataset.perfil || '';
    const a     = tr.dataset.ativo  || '';
    const ok = (!busca  || nome.includes(busca) || email.includes(busca))
            && (!perfil || p === perfil)
            && (status === '' || a === status);
    tr.style.display = ok ? '' : 'none';
  });
}

// ── Modal abrir/fechar ────────────────────────────────────
function abrirModalNovo() {
  document.getElementById('m-id').value = '0';
  document.getElementById('m-nome').value = '';
  document.getElementById('m-email').value = '';
  document.getElementById('m-perfil').value = '4';
  document.getElementById('m-equipe').value = '';
  document.getElementById('senha-box').style.display = 'none';
  document.getElementById('modal-titulo').textContent = 'Novo Usuário';
  document.getElementById('btn-salvar-modal').textContent = 'Criar usuário';
  ajustarCamposModal();
  document.getElementById('modal-bd').classList.add('aberto');
}

function abrirModalEditar(id, nome, email, perfil, equipeId) {
  document.getElementById('m-id').value = id;
  document.getElementById('m-nome').value = nome;
  document.getElementById('m-email').value = email;
  document.getElementById('m-perfil').value = String(perfil);
  document.getElementById('m-equipe').value = String(equipeId || '');
  document.getElementById('senha-box').style.display = 'none';
  document.getElementById('modal-titulo').textContent = 'Editar Usuário';
  document.getElementById('btn-salvar-modal').textContent = 'Salvar alterações';
  ajustarCamposModal();
  document.getElementById('modal-bd').classList.add('aberto');
}

function fecharModal() {
  document.getElementById('modal-bd').classList.remove('aberto');
}

function fecharSeFundo(e) {
  if (e.target === document.getElementById('modal-bd')) fecharModal();
}

function ajustarCamposModal() {
  const p = parseInt(document.getElementById('m-perfil').value);
  document.getElementById('campo-equipe').style.display = (p === 5 || p === 7) ? '' : 'none';
}

// ── Salvar usuário (AJAX) ─────────────────────────────────
async function salvarUsuario() {
  const nome   = document.getElementById('m-nome').value.trim();
  const email  = document.getElementById('m-email').value.trim();
  const perfil = document.getElementById('m-perfil').value;
  const equipe = document.getElementById('m-equipe').value;
  const id     = document.getElementById('m-id').value;

  if (!nome || !email) { toast('Preencha nome e e-mail.', true); return; }

  const fd = new FormData();
  fd.set('_csrf', CSRF);
  fd.set('id',          id);
  fd.set('nome',        nome);
  fd.set('email',       email);
  fd.set('tipo_usuario', perfil);
  fd.set('equipe_id',   equipe);

  const btn = document.getElementById('btn-salvar-modal');
  btn.disabled = true;

  try {
    const res  = await fetch(BASE + '/admin/salvar-usuario', { method:'POST', body:fd });
    const data = await res.json();
    if (data.ok) {
      if (data.acao === 'criado' && data.senha) {
        document.getElementById('senha-valor').textContent = data.senha;
        document.getElementById('senha-box').style.display = 'block';
        document.getElementById('btn-salvar-modal').style.display = 'none';
        toast('Usuário criado ✓');
        setTimeout(() => location.reload(), 4000);
      } else {
        toast('Salvo com sucesso ✓');
        setTimeout(() => location.reload(), 1200);
      }
    } else {
      toast(data.msg || 'Erro ao salvar.', true);
    }
  } catch(e) {
    toast('Erro de rede.', true);
  } finally {
    btn.disabled = false;
  }
}

// ── Resetar senha ─────────────────────────────────────────
async function confirmarReset(id, nome) {
  if (!confirm(`Resetar a senha de "${nome}"?\nUma senha provisória será gerada.`)) return;

  const fd = new FormData();
  fd.set('_csrf', CSRF);
  fd.set('id', id);
  try {
    const res  = await fetch(BASE + '/admin/resetar-senha', { method:'POST', body:fd });
    const data = await res.json();
    if (data.ok) {
      alert(`Nova senha provisória de "${nome}":\n\n${data.senha}\n\nCopie agora — não será exibida novamente.`);
      toast('Senha resetada ✓');
    } else {
      toast(data.msg || 'Erro ao resetar.', true);
    }
  } catch(e) {
    toast('Erro de rede.', true);
  }
}

// ── Toggle ativo ──────────────────────────────────────────
async function confirmarToggle(id, novoAtivo, nome) {
  const verb = novoAtivo ? 'ativar' : 'inativar';
  if (!confirm(`Deseja ${verb} o usuário "${nome}"?`)) return;

  const fd = new FormData();
  fd.set('_csrf', CSRF);
  fd.set('id',    id);
  fd.set('ativo', novoAtivo);
  try {
    const res  = await fetch(BASE + '/admin/toggle-ativo', { method:'POST', body:fd });
    const data = await res.json();
    if (data.ok) {
      toast(novoAtivo ? 'Usuário ativado ✓' : 'Usuário inativado ✓');
      setTimeout(() => location.reload(), 1000);
    } else {
      toast(data.msg || 'Erro.', true);
    }
  } catch(e) {
    toast('Erro de rede.', true);
  }
}

// ── Toast ─────────────────────────────────────────────────
function toast(msg, erro) {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.style.background = erro ? '#B23A2C' : '#1A2D4F';
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), 2800);
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
