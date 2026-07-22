<?php
$title     = 'Usuários & Acessos';
$pageTitle = 'Usuários & Acessos';
$pageSubtitle = 'Gerencie contas, perfis e senhas do sistema';

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
.kpis5 { display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:22px; }
@media(max-width:900px){ .kpis5{ grid-template-columns:repeat(2,1fr); } }

.filtros { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; align-items:center; }
.filtros input, .filtros select { font-family:inherit; font-size:13px; padding:9px 12px;
  border:1px solid var(--line); border-radius:9px; background:#fff; color:var(--ink); }
.filtros input { flex:1; min-width:200px; }

.badge-perfil { display:inline-flex; align-items:center; font-size:11px; font-weight:700;
  border-radius:999px; padding:3px 10px; white-space:nowrap; }
.status-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:6px; }
.acoes-td { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }

/* Modal principal */
#modal-bd { display:none; position:fixed; inset:0; background:#00000066; z-index:100;
  align-items:center; justify-content:center; padding:16px; }
#modal-bd.aberto { display:flex; }
#modal { background:#fff; border-radius:16px; padding:28px 30px; width:100%; max-width:520px;
  box-shadow:0 24px 70px #0007; position:relative; max-height:90vh; overflow-y:auto; }
#modal h2 { font-size:18px; font-weight:800; margin-bottom:20px; color:var(--navy); }
#modal .close { position:absolute; top:18px; right:18px; border:0; background:#f2f4f8;
  width:30px; height:30px; border-radius:8px; font-size:16px; color:var(--muted); cursor:pointer; }

/* Modal de senha (reset) */
#modal-senha-bd { display:none; position:fixed; inset:0; background:#00000066; z-index:110;
  align-items:center; justify-content:center; padding:16px; }
#modal-senha-bd.aberto { display:flex; }
#modal-senha { background:#fff; border-radius:16px; padding:28px 30px; width:100%; max-width:440px;
  box-shadow:0 24px 70px #0007; position:relative; }
#modal-senha h2 { font-size:17px; font-weight:800; margin-bottom:6px; color:var(--navy); }
#modal-senha .sub { font-size:13px; color:var(--muted); margin-bottom:18px; }

/* Caixa da senha */
.senha-display { background:#EEF6FF; border:1.5px solid #3e86c966; border-radius:12px;
  padding:16px 18px; margin:14px 0; }
.senha-display .label-s { font-size:11px; font-weight:700; color:var(--muted);
  text-transform:uppercase; letter-spacing:1px; margin-bottom:8px; }
.senha-display .valor { font-family:monospace; font-size:22px; font-weight:800;
  color:var(--navy); letter-spacing:3px; display:block; word-break:break-all; }
.senha-display .aviso { font-size:11.5px; color:var(--aviso); margin-top:8px; font-weight:600; }
.btn-copiar { border:1px solid var(--line); background:#fff; border-radius:8px; padding:8px 14px;
  font-size:12.5px; font-weight:700; color:var(--navy); cursor:pointer; display:flex;
  align-items:center; gap:6px; margin-top:10px; }
.btn-copiar:hover { background:var(--bg); }
.btn-copiar.copiado { color:var(--ok); border-color:var(--ok); }

/* Input senha com toggle mostrar */
.campo-senha { position:relative; }
.campo-senha input { padding-right:70px; }
.campo-senha .tgl-senha { position:absolute; right:10px; top:50%; transform:translateY(-50%);
  border:0; background:0; font-size:12px; font-weight:700; color:var(--navy-500);
  cursor:pointer; font-family:inherit; }

/* Toast */
#toast { position:fixed; bottom:24px; left:50%; transform:translateX(-50%);
  background:var(--navy); color:#fff; padding:11px 20px; border-radius:20px;
  font-size:13px; font-weight:700; z-index:300; opacity:0; transition:opacity .3s;
  pointer-events:none; white-space:nowrap; }
#toast.show { opacity:1; }
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
          <th style="width:200px"></th>
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
                  <span class="chip c-aviso" style="font-size:10px;margin-top:2px">senha provisória ativa</span>
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
                ✏️ Editar
              </button>
              <button class="btn btn-sec btn-sm" title="Resetar senha"
                onclick="abrirResetSenha(<?= $u['id'] ?>, <?= htmlspecialchars(json_encode($u['nome']), ENT_QUOTES) ?>)">
                🔑 Senha
              </button>
              <?php if ($ativo): ?>
                <button class="btn btn-danger btn-sm"
                  onclick="confirmarToggle(<?= $u['id'] ?>, 0, <?= htmlspecialchars(json_encode($u['nome']), ENT_QUOTES) ?>)">
                  Inativar
                </button>
              <?php else: ?>
                <button class="btn btn-sec btn-sm" style="color:var(--ok)"
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

    <div class="form-grid" style="gap:14px">
      <div class="campo">
        <label>Nome completo</label>
        <input type="text" id="m-nome" maxlength="120" placeholder="Nome do usuário">
      </div>
      <div class="campo">
        <label>E-mail</label>
        <input type="email" id="m-email" maxlength="150" placeholder="email@exemplo.com.br">
      </div>
      <div class="campo">
        <label>Perfil de acesso</label>
        <select id="m-perfil" onchange="ajustarCamposModal()">
          <option value="4">Planejador</option>
          <option value="5">Executor de Rede</option>
          <option value="6">Cliente Master</option>
          <option value="7">Executor de Repavimentação</option>
          <option value="3">Master Gravitas</option>
        </select>
      </div>
      <div class="campo" id="campo-equipe" style="display:none">
        <label>Equipe vinculada</label>
        <select id="m-equipe">
          <option value="">— sem equipe —</option>
          <?php foreach ($equipes as $eq): ?>
            <option value="<?= $eq['id'] ?>"><?= htmlspecialchars($eq['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="campo" id="campo-senha">
        <label id="label-senha">Senha <span style="color:var(--muted);font-weight:500">(deixe em branco para gerar automaticamente)</span></label>
        <div class="campo-senha">
          <input type="password" id="m-senha" maxlength="80" placeholder="••••••••" autocomplete="new-password">
          <button type="button" class="tgl-senha" onclick="toggleSenhaVisivel()">Mostrar</button>
        </div>
      </div>
    </div>

    <!-- Exibição da senha após criar -->
    <div id="caixa-senha-gerada" style="display:none">
      <div class="senha-display">
        <div class="label-s">Senha definida para este usuário</div>
        <span class="valor" id="senha-gerada-val">—</span>
        <div class="aviso">⚠️ Anote agora — esta senha não será exibida novamente.</div>
      </div>
      <button class="btn-copiar" id="btn-copiar-criacao" onclick="copiarSenha('senha-gerada-val','btn-copiar-criacao')">
        📋 Copiar senha
      </button>
    </div>

    <div class="form-actions" id="acoes-modal">
      <button class="btn btn-pri" id="btn-salvar-modal" onclick="salvarUsuario()">Criar usuário</button>
      <button class="btn btn-sec" onclick="fecharModal()">Cancelar</button>
    </div>
    <div id="acoes-pos-criar" style="display:none;margin-top:14px">
      <button class="btn btn-pri" onclick="location.reload()">Fechar e atualizar lista</button>
    </div>
  </div>
</div>

<!-- ── Modal reset de senha ── -->
<div id="modal-senha-bd" onclick="fecharSeFundoSenha(event)">
  <div id="modal-senha">
    <h2>Resetar Senha</h2>
    <p class="sub">Usuário: <b id="reset-nome-display">—</b></p>
    <input type="hidden" id="reset-id" value="0">

    <div id="reset-form">
      <div class="campo" style="margin-bottom:14px">
        <label>Nova senha <span style="color:var(--muted);font-weight:500">(deixe em branco para gerar automaticamente)</span></label>
        <div class="campo-senha">
          <input type="password" id="reset-nova-senha" maxlength="80" placeholder="••••••••" autocomplete="new-password">
          <button type="button" class="tgl-senha" onclick="toggleResetSenhaVisivel()">Mostrar</button>
        </div>
      </div>
      <div class="form-actions">
        <button class="btn btn-pri" onclick="executarReset()">Resetar senha</button>
        <button class="btn btn-sec" onclick="fecharModalSenha()">Cancelar</button>
      </div>
    </div>

    <div id="reset-resultado" style="display:none">
      <div class="senha-display">
        <div class="label-s">Nova senha definida</div>
        <span class="valor" id="reset-senha-val">—</span>
        <div class="aviso">⚠️ Anote agora — esta senha não será exibida novamente.</div>
      </div>
      <button class="btn-copiar" id="btn-copiar-reset" onclick="copiarSenha('reset-senha-val','btn-copiar-reset')">
        📋 Copiar senha
      </button>
      <div style="margin-top:14px">
        <button class="btn btn-sec" onclick="fecharModalSenha()">Fechar</button>
      </div>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
const BASE = '<?= APP_BASE ?>';
const CSRF = '<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>';

// ── Filtro ────────────────────────────────────────────────
function filtrar() {
  const busca  = document.getElementById('f-busca').value.toLowerCase();
  const perfil = document.getElementById('f-perfil').value;
  const status = document.getElementById('f-status').value;
  document.querySelectorAll('#tabela-usuarios tbody tr').forEach(tr => {
    const ok = (!busca  || (tr.dataset.nome||'').includes(busca) || (tr.dataset.email||'').includes(busca))
            && (!perfil || tr.dataset.perfil === perfil)
            && (status === '' || tr.dataset.ativo === status);
    tr.style.display = ok ? '' : 'none';
  });
}

// ── Modal criar/editar ────────────────────────────────────
function abrirModalNovo() {
  document.getElementById('m-id').value = '0';
  document.getElementById('m-nome').value = '';
  document.getElementById('m-email').value = '';
  document.getElementById('m-senha').value = '';
  document.getElementById('m-perfil').value = '4';
  document.getElementById('m-equipe').value = '';
  document.getElementById('caixa-senha-gerada').style.display = 'none';
  document.getElementById('acoes-modal').style.display = 'flex';
  document.getElementById('acoes-pos-criar').style.display = 'none';
  document.getElementById('modal-titulo').textContent = 'Novo Usuário';
  document.getElementById('btn-salvar-modal').textContent = 'Criar usuário';
  document.getElementById('label-senha').innerHTML = 'Senha <span style="color:var(--muted);font-weight:500">(deixe em branco para gerar automaticamente)</span>';
  ajustarCamposModal();
  document.getElementById('modal-bd').classList.add('aberto');
}

function abrirModalEditar(id, nome, email, perfil, equipeId) {
  document.getElementById('m-id').value = id;
  document.getElementById('m-nome').value = nome;
  document.getElementById('m-email').value = email;
  document.getElementById('m-senha').value = '';
  document.getElementById('m-perfil').value = String(perfil);
  document.getElementById('m-equipe').value = String(equipeId || '');
  document.getElementById('caixa-senha-gerada').style.display = 'none';
  document.getElementById('acoes-modal').style.display = 'flex';
  document.getElementById('acoes-pos-criar').style.display = 'none';
  document.getElementById('modal-titulo').textContent = 'Editar Usuário';
  document.getElementById('btn-salvar-modal').textContent = 'Salvar alterações';
  document.getElementById('label-senha').innerHTML = 'Nova senha <span style="color:var(--muted);font-weight:500">(deixe em branco para manter a atual)</span>';
  ajustarCamposModal();
  document.getElementById('modal-bd').classList.add('aberto');
}

function fecharModal() { document.getElementById('modal-bd').classList.remove('aberto'); }
function fecharSeFundo(e) { if (e.target.id === 'modal-bd') fecharModal(); }

function ajustarCamposModal() {
  const p = parseInt(document.getElementById('m-perfil').value);
  document.getElementById('campo-equipe').style.display = (p === 5 || p === 7) ? '' : 'none';
}

function toggleSenhaVisivel() {
  const i = document.getElementById('m-senha');
  const b = i.nextElementSibling;
  i.type = i.type === 'password' ? 'text' : 'password';
  b.textContent = i.type === 'password' ? 'Mostrar' : 'Ocultar';
}

// ── Salvar usuário ────────────────────────────────────────
async function salvarUsuario() {
  const nome   = document.getElementById('m-nome').value.trim();
  const email  = document.getElementById('m-email').value.trim();
  const perfil = document.getElementById('m-perfil').value;
  const equipe = document.getElementById('m-equipe').value;
  const senha  = document.getElementById('m-senha').value;
  const id     = document.getElementById('m-id').value;

  if (!nome || !email) { toast('Preencha nome e e-mail.', true); return; }

  const fd = new FormData();
  fd.set('_csrf', CSRF);
  fd.set('id', id);
  fd.set('nome', nome);
  fd.set('email', email);
  fd.set('tipo_usuario', perfil);
  fd.set('equipe_id', equipe);
  fd.set('senha', senha);

  const btn = document.getElementById('btn-salvar-modal');
  btn.disabled = true; btn.textContent = 'Salvando…';

  try {
    const res  = await fetch(BASE + '/admin/salvar-usuario', { method:'POST', body:fd });
    const data = await res.json();
    if (data.ok) {
      if (data.acao === 'criado') {
        document.getElementById('senha-gerada-val').textContent = data.senha || '(definida pelo admin)';
        document.getElementById('caixa-senha-gerada').style.display = data.senha ? 'block' : 'none';
        document.getElementById('acoes-modal').style.display = 'none';
        document.getElementById('acoes-pos-criar').style.display = 'block';
        toast('Usuário criado ✓');
      } else {
        const senhaAlterada = data.senha_alterada;
        toast(senhaAlterada ? 'Salvo ✓ — senha atualizada' : 'Alterações salvas ✓');
        setTimeout(() => location.reload(), 1200);
      }
    } else {
      toast(data.msg || 'Erro ao salvar.', true);
      btn.disabled = false;
      btn.textContent = id === '0' ? 'Criar usuário' : 'Salvar alterações';
    }
  } catch(e) {
    toast('Erro de rede.', true);
    btn.disabled = false;
    btn.textContent = id === '0' ? 'Criar usuário' : 'Salvar alterações';
  }
}

// ── Modal reset de senha ──────────────────────────────────
function abrirResetSenha(id, nome) {
  document.getElementById('reset-id').value = id;
  document.getElementById('reset-nome-display').textContent = nome;
  document.getElementById('reset-nova-senha').value = '';
  document.getElementById('reset-form').style.display = 'block';
  document.getElementById('reset-resultado').style.display = 'none';
  document.getElementById('modal-senha-bd').classList.add('aberto');
}

function fecharModalSenha() { document.getElementById('modal-senha-bd').classList.remove('aberto'); }
function fecharSeFundoSenha(e) { if (e.target.id === 'modal-senha-bd') fecharModalSenha(); }

function toggleResetSenhaVisivel() {
  const i = document.getElementById('reset-nova-senha');
  const b = i.nextElementSibling;
  i.type = i.type === 'password' ? 'text' : 'password';
  b.textContent = i.type === 'password' ? 'Mostrar' : 'Ocultar';
}

async function executarReset() {
  const id    = document.getElementById('reset-id').value;
  const senha = document.getElementById('reset-nova-senha').value;

  const fd = new FormData();
  fd.set('_csrf', CSRF);
  fd.set('id', id);
  fd.set('senha', senha);

  try {
    const res  = await fetch(BASE + '/admin/resetar-senha', { method:'POST', body:fd });
    const data = await res.json();
    if (data.ok) {
      document.getElementById('reset-senha-val').textContent = data.senha || '(senha definida pelo admin)';
      document.getElementById('reset-form').style.display = 'none';
      document.getElementById('reset-resultado').style.display = data.senha ? 'block' : 'none';
      if (!data.senha) {
        toast('Senha atualizada ✓');
        setTimeout(() => fecharModalSenha(), 1000);
      } else {
        toast('Senha resetada ✓');
      }
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
  fd.set('id', id);
  fd.set('ativo', novoAtivo);
  try {
    const res  = await fetch(BASE + '/admin/toggle-ativo', { method:'POST', body:fd });
    const data = await res.json();
    if (data.ok) {
      toast(novoAtivo ? 'Usuário ativado ✓' : 'Usuário inativado ✓');
      setTimeout(() => location.reload(), 900);
    } else {
      toast(data.msg || 'Erro.', true);
    }
  } catch(e) { toast('Erro de rede.', true); }
}

// ── Copiar senha ──────────────────────────────────────────
function copiarSenha(elId, btnId) {
  const val = document.getElementById(elId).textContent;
  navigator.clipboard.writeText(val).then(() => {
    const btn = document.getElementById(btnId);
    btn.textContent = '✓ Copiado!';
    btn.classList.add('copiado');
    setTimeout(() => { btn.textContent = '📋 Copiar senha'; btn.classList.remove('copiado'); }, 2000);
  });
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
