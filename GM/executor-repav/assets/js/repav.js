'use strict';

// ── GPS ──────────────────────────────────────────────────────
let _gpsCoords = null;
const gpsEl = document.getElementById('gps-status');
if (gpsEl && navigator.geolocation) {
  navigator.geolocation.watchPosition(
    pos => {
      _gpsCoords = { lat: pos.coords.latitude, lng: pos.coords.longitude };
      if (gpsEl) { gpsEl.textContent = '📍 GPS ativo'; gpsEl.style.cssText = ''; }
    },
    () => { if (gpsEl) { gpsEl.textContent = '📍 GPS indisponível'; gpsEl.style.background='#c0392b2e'; gpsEl.style.color='#e74c3c'; } }
  );
} else if (gpsEl) {
  gpsEl.textContent = '📍 sem GPS';
}

// ── Conectividade ─────────────────────────────────────────────
const connBadge = document.getElementById('conn-badge');
function atualizarConexao() {
  if (connBadge) connBadge.textContent = navigator.onLine ? '🟢 Online' : '🔴 Offline';
  const os = document.getElementById('offline-sec');
  const oi = document.getElementById('offline-queue-info');
  if (os && oi) { os.style.display = 'none'; oi.style.display = 'none'; }
}
window.addEventListener('online',  atualizarConexao);
window.addEventListener('offline', atualizarConexao);
atualizarConexao();

// ── Toast ─────────────────────────────────────────────────────
function toast(msg, erro) {
  let el = document.getElementById('_toast');
  if (!el) {
    el = document.createElement('div');
    el.id = '_toast';
    el.className = 'toast';
    document.body.appendChild(el);
  }
  el.textContent = msg;
  el.style.background = erro ? '#B23A2C' : '#1A2D4F';
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), 2800);
}

// ── Toggle step ───────────────────────────────────────────────
window.toggleStep = function(num) {
  const el = document.getElementById('step-' + num);
  if (el) el.classList.toggle('aberto');
};

// ── Salvar passo (AJAX form submit) ──────────────────────────
window.salvarPasso = async function(evt, diarioId, step) {
  evt.preventDefault();
  const form = evt.target;
  const btn  = form.querySelector('.btn-salvar');
  if (btn) { btn.disabled = true; btn.textContent = 'Salvando…'; }

  const fd = new FormData(form);
  fd.set('diario_id', diarioId);
  fd.set('step', step);

  try {
    const res  = await fetch(REPAV_BASE + '/diario/salvar', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      toast('Passo ' + step + ' salvo ✓');
      const stepEl = document.getElementById('step-' + step);
      if (stepEl) { stepEl.classList.add('feito'); stepEl.classList.remove('aberto'); }
      atualizarProgresso();
    } else {
      toast(data.msg || 'Erro ao salvar', true);
    }
  } catch (e) {
    toast('Erro de rede — tente novamente', true);
  } finally {
    if (btn) { btn.disabled = false; btn.textContent = 'Salvar'; }
  }
};

// ── Marcar step (foto confirmada) ─────────────────────────────
window.marcarStep = async function(diarioId, step) {
  const fd = new FormData();
  fd.set('csrf_token', document.querySelector('meta[name=csrf]')?.content || '');
  fd.set('diario_id', diarioId);
  fd.set('step', step);
  try {
    const res  = await fetch(REPAV_BASE + '/diario/salvar', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      const stepEl = document.getElementById('step-' + step);
      if (stepEl) { stepEl.classList.add('feito'); stepEl.classList.remove('aberto'); }
      toast('Passo ' + step + ' marcado ✓');
      atualizarProgresso();
    }
  } catch(e) {}
};

// ── Foto com câmera ───────────────────────────────────────────
let _fotoStep = 0;
let _fotoTarget = null;

document.getElementById('file-foto')?.addEventListener('change', async function() {
  const file = this.files[0];
  if (!file) return;
  const step = _fotoStep;
  const cont = _fotoTarget;

  const fd = new FormData();
  fd.set('csrf_token', document.querySelector('meta[name=csrf]')?.content || '');
  fd.set('diario_id', DIARIO_ID);
  fd.set('step', step);
  fd.set('lat', _gpsCoords ? _gpsCoords.lat : '');
  fd.set('lng', _gpsCoords ? _gpsCoords.lng : '');
  fd.set('ts',  new Date().toISOString());

  const compressed = await comprimirImagem(file);
  fd.set('foto', compressed, file.name);

  toast('Enviando foto…');
  try {
    const res  = await fetch(REPAV_BASE + '/diario/foto', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      toast('Foto salva ✓');
      // Adicionar thumb ao container
      if (cont) {
        const div = document.createElement('div');
        div.className = 'foto';
        div.innerHTML = data.thumb
          ? `<img src="${REPAV_BASE}/uploads/repav/thumbs/${data.thumb}" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0"><span class="gpsb">GPS</span>`
          : `📷<span class="gpsb">GPS</span>`;
        cont.insertBefore(div, cont.querySelector('.cam'));
      }
      const stepEl = document.getElementById('step-' + step);
      if (stepEl) stepEl.classList.add('feito');
      atualizarProgresso();
    } else {
      toast(data.msg || 'Erro ao enviar foto', true);
    }
  } catch(e) {
    toast('Erro de rede ao enviar foto', true);
  }
  this.value = '';
});

window.tirarFoto = function(diarioId, step, camEl) {
  _fotoStep   = step;
  _fotoTarget = camEl?.parentElement;
  document.getElementById('file-foto')?.click();
};

// ── Compressão de imagem ──────────────────────────────────────
async function comprimirImagem(file, maxW = 1280, quality = 0.82) {
  return new Promise(resolve => {
    const reader = new FileReader();
    reader.onload = e => {
      const img = new Image();
      img.onload = () => {
        const w = Math.min(img.width, maxW);
        const h = img.height * (w / img.width);
        const canvas = document.createElement('canvas');
        canvas.width = w; canvas.height = h;
        canvas.getContext('2d').drawImage(img, 0, 0, w, h);
        canvas.toBlob(blob => resolve(blob || file), 'image/jpeg', quality);
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  });
}

// ── Progresso ─────────────────────────────────────────────────
function atualizarProgresso() {
  const feitos  = document.querySelectorAll('.step.feito').length;
  const total   = document.querySelectorAll('.step').length || 19;
  const pct     = Math.round(feitos / total * 100);
  const pctEl   = document.getElementById('prog-pct');
  const barEl   = document.getElementById('prog-bar');
  if (pctEl) pctEl.textContent = pct + '%';
  if (barEl) barEl.style.width = pct + '%';
}

// ── Presença helper ───────────────────────────────────────────
window.setTodos = function(btn, val, diarioId) {
  const lista = document.getElementById('lista-ausentes-' + diarioId);
  const input = document.getElementById('todos-' + diarioId);
  const simBtn = btn.closest('.toggle').querySelector('.sim');
  const naoBtn = btn.closest('.toggle').querySelector('.nao');
  simBtn.classList.toggle('on', val === 's');
  naoBtn.classList.toggle('on', val === 'n');
  if (input) input.value = val;
  if (lista) lista.style.display = val === 'n' ? 'block' : 'none';
};

// ── Material OK helper ────────────────────────────────────────
window.setMatOk = function(btn, val) {
  const toggle = btn.closest('.toggle');
  toggle.querySelectorAll('button').forEach(b => b.classList.remove('on'));
  btn.classList.add('on');
  const diarioId = typeof DIARIO_ID !== 'undefined' ? DIARIO_ID : 0;
  const input = document.getElementById('mat-ok-' + diarioId);
  if (input) input.value = val;
  const obsBox = document.getElementById('mat-obs-box-' + diarioId);
  if (obsBox) obsBox.style.display = val === 0 ? 'block' : 'none';
};

// ── Equipamento status helper ─────────────────────────────────
window.setEquipStatus = function(btn, val) {
  const card = btn.closest('.card-mini');
  card.querySelectorAll('.equip-btn').forEach(b => b.classList.remove('on'));
  btn.classList.add('on');
  const hiddenInput = card.querySelector('input[name="equip_status[]"]');
  if (hiddenInput) hiddenInput.value = val;
};

// ── Adicionar carga (AJAX) ────────────────────────────────────
window.adicionarCarga = async function(diarioId) {
  const fd = new FormData();
  fd.set('csrf_token', document.querySelector('meta[name=csrf]')?.content || '');
  fd.set('diario_id', diarioId);
  try {
    const res  = await fetch(REPAV_BASE + '/diario/carga', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      const lista = document.getElementById('cargas-lista');
      if (!lista) return;
      const div = document.createElement('div');
      div.className = 'card-mini';
      div.id = 'carga-' + data.id;
      div.innerHTML = `<div class="ch">Carga ${data.seq}</div>
        <input type="hidden" name="carga_id[]" value="${data.id}">
        <div class="row2">
          <input type="text" name="carga_nf[]" placeholder="Nº NF">
          <input type="number" name="carga_mass[]" step="0.01" placeholder="Massa (t)">
        </div>
        <div class="lbl">Foto da carga + foto da NF</div>
        <div class="fotos" id="fotos-carga-${data.id}">
          <div class="cam" onclick="tirarFoto(${diarioId},10,this)"><span class="cic">📷</span>foto</div>
        </div>`;
      lista.appendChild(div);
    }
  } catch(e) { toast('Erro ao adicionar carga', true); }
};

// ── Adicionar área asfalto ────────────────────────────────────
window.adicionarAreaAsf = async function(diarioId) {
  const fd = new FormData();
  fd.set('csrf_token', document.querySelector('meta[name=csrf]')?.content || '');
  fd.set('diario_id', diarioId);
  fd.set('tipo', 'Asfalto (CBUQ)');
  try {
    const res  = await fetch(REPAV_BASE + '/diario/area', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      const lista = document.getElementById('areas-asfalto');
      if (!lista) return;
      const espAsf = document.getElementById('esp-asf')?.value || '0.05';
      const div = document.createElement('div');
      div.className = 'dim-row';
      div.id = 'area-row-' + data.id;
      div.innerHTML = `
        <input type="hidden" name="area_id[]" value="${data.id}">
        <div class="row3">
          <div><span class="hint" style="margin:0">Base (m)</span><input type="number" name="area_base[]" step="0.01" value="0" oninput="atualizarCalc()"></div>
          <div><span class="hint" style="margin:0">Largura (m)</span><input type="number" name="area_larg[]" step="0.01" value="0" oninput="atualizarCalc()"></div>
          <button type="button" class="x" onclick="this.closest('.dim-row').remove();atualizarCalc()">✕</button>
        </div>
        <input type="hidden" name="area_esp[]" value="${espAsf}">`;
      lista.appendChild(div);
    }
  } catch(e) { toast('Erro ao adicionar área', true); }
};

// ── Adicionar outro pavimento ─────────────────────────────────
window.adicionarAreaOutro = async function(diarioId) {
  const sel  = document.getElementById('sel-tipo-pav');
  const tipo = sel?.value || 'Calçada';
  const fd = new FormData();
  fd.set('csrf_token', document.querySelector('meta[name=csrf]')?.content || '');
  fd.set('diario_id', diarioId);
  fd.set('tipo', tipo);
  try {
    const res  = await fetch(REPAV_BASE + '/diario/area', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      const lista = document.getElementById('areas-outros');
      if (!lista) return;
      const div = document.createElement('div');
      div.className = 'card-mini dim-row';
      div.id = 'area-row-' + data.id;
      div.innerHTML = `<div class="ch">${tipo}</div>
        <input type="hidden" name="area_id[]" value="${data.id}">
        <input type="hidden" name="area_esp[]" value="">
        <div class="row2">
          <div><span class="hint" style="margin:0">Base (m)</span><input type="number" name="area_base[]" step="0.01" value="0"></div>
          <div><span class="hint" style="margin:0">Largura (m)</span><input type="number" name="area_larg[]" step="0.01" value="0"></div>
        </div>`;
      lista.appendChild(div);
    }
  } catch(e) { toast('Erro ao adicionar pavimento', true); }
};

// ── Cálculo dinâmico de área e volume ────────────────────────
window.atualizarCalc = function() {
  const espEl   = document.getElementById('esp-asf');
  const calcAsf = document.getElementById('calc-asf');
  const calcVol = document.getElementById('calc-vol');
  if (!espEl || !calcAsf) return;

  const esp   = parseFloat(espEl.value) || 0;
  const lista = document.getElementById('areas-asfalto');
  if (!lista) return;

  let areaTotal = 0;
  lista.querySelectorAll('.dim-row').forEach(row => {
    const base = parseFloat(row.querySelector('input[name="area_base[]"]')?.value) || 0;
    const larg = parseFloat(row.querySelector('input[name="area_larg[]"]')?.value) || 0;
    areaTotal += base * larg;
    // Atualizar campo de espessura oculto
    const espHidden = row.querySelector('input[name="area_esp[]"]');
    if (espHidden) espHidden.value = esp;
  });

  const vol = areaTotal * esp;
  calcAsf.innerHTML = `área total: <b>${fmtN(areaTotal)} m²</b>`;
  if (calcVol) calcVol.textContent = `vol: ${fmtN(vol)} m³`;

  // Atualizar rodapé
  const footEl = document.getElementById('foot-resumo');
  if (footEl) footEl.textContent = `${fmtN(areaTotal)} m² · ${fmtN(vol)} m³`;
};

function fmtN(n) {
  return (n || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Inicializar cálculo se estiver no formulário de dimensões
document.addEventListener('DOMContentLoaded', () => {
  const espEl = document.getElementById('esp-asf');
  if (espEl) {
    espEl.addEventListener('input', atualizarCalc);
    document.querySelectorAll('input[name="area_base[]"], input[name="area_larg[]"]')
      .forEach(i => i.addEventListener('input', atualizarCalc));
    atualizarCalc();
  }
  atualizarProgresso();

  // Checkboxes de mini-flag com toggle visual
  document.querySelectorAll('.mini-flag').forEach(label => {
    label.addEventListener('click', function() {
      this.classList.toggle('on');
    });
  });
});
