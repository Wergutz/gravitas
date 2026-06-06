// Executor App — JS principal v2 (PA5 Fase 4)
// Responsável: GPS, offline, draft localStorage, sync queue, GPS-blocking

'use strict';

// ── Base URL ───────────────────────────────────────────────
const EXECUTOR_BASE = (function () {
  const m = window.location.pathname.match(/^(\/[^/]+\/[^/]+)/);
  return m ? m[1] : '';
})();

// ── GPS Status do topo ─────────────────────────────────────
const gpsEl = document.getElementById('gps-status');
if (gpsEl && navigator.geolocation) {
  navigator.geolocation.getCurrentPosition(
    () => { gpsEl.textContent = '📍 GPS ativo'; gpsEl.style.cssText = ''; },
    () => { gpsEl.textContent = '📍 GPS indisponível'; gpsEl.style.background='#c0392b2e'; gpsEl.style.color='#e74c3c'; }
  );
} else if (gpsEl) {
  gpsEl.textContent = '📍 sem GPS';
}

// ── Conectividade ──────────────────────────────────────────
const connBadge = document.getElementById('conn-badge');
let _online = navigator.onLine;

function atualizarConexao() {
  _online = navigator.onLine;
  if (connBadge) {
    connBadge.textContent = _online ? '🟢 Online' : '🔴 Offline';
    connBadge.className   = _online ? '' : 'offline';
  }
  const offlineSec  = document.getElementById('offline-sec');
  const offlineInfo = document.getElementById('offline-queue-info');
  if (offlineSec && offlineInfo) {
    const fila = lerFila();
    if (!_online && fila.length > 0) {
      offlineSec.style.display  = 'flex';
      offlineInfo.style.display = 'block';
      const countEl = document.getElementById('offline-count');
      if (countEl) countEl.textContent = fila.length + ' item(s) para sincronizar';
    } else {
      offlineSec.style.display  = 'none';
      offlineInfo.style.display = 'none';
    }
  }
  if (_online) tentarSync();
}

window.addEventListener('online',  atualizarConexao);
window.addEventListener('offline', atualizarConexao);
atualizarConexao();

// ── Fila Offline (localStorage) ────────────────────────────
const FILA_KEY = 'gravitas_exec_fila';

function lerFila() {
  try { return JSON.parse(localStorage.getItem(FILA_KEY) || '[]'); } catch { return []; }
}
function salvarFila(fila) {
  localStorage.setItem(FILA_KEY, JSON.stringify(fila));
}
function adicionarFila(item) {
  const fila = lerFila();
  fila.push({ ...item, _ts: Date.now() });
  salvarFila(fila);
}

// ── Draft por step (auto-save) ─────────────────────────────
function _draftKey(diarioId, step) {
  return 'draft_' + diarioId + '_' + step;
}
window.salvarRascunho = function (diarioId, step, dados) {
  localStorage.setItem(_draftKey(diarioId, step), JSON.stringify(dados));
};
window.lerRascunho = function (diarioId, step) {
  try { return JSON.parse(localStorage.getItem(_draftKey(diarioId, step)) || 'null'); }
  catch { return null; }
};
function limparRascunho(diarioId, step) {
  localStorage.removeItem(_draftKey(diarioId, step));
}

// Auto-save: escuta change/input em qualquer form dentro de .step-body
function iniciarAutoSave(diarioId) {
  document.addEventListener('change', function (e) {
    const form = e.target.closest('form');
    if (!form) return;
    const step = parseInt(form.closest('[data-step]')?.dataset.step || '0');
    if (!step) return;
    const dados = {};
    new FormData(form).forEach((v, k) => dados[k] = v);
    salvarRascunho(diarioId, step, dados);
  });
  document.addEventListener('input', function (e) {
    const form = e.target.closest('form');
    if (!form) return;
    const step = parseInt(form.closest('[data-step]')?.dataset.step || '0');
    if (!step || e.target.tagName === 'INPUT' && e.target.type === 'file') return;
    const dados = {};
    new FormData(form).forEach((v, k) => dados[k] = v);
    salvarRascunho(diarioId, step, dados);
  });
}

// Restaura rascunho de um step (chamado quando step abre)
function restaurarDraft(diarioId, step) {
  const draft = lerRascunho(diarioId, step);
  if (!draft) return;
  const stepEl = document.querySelector('[data-step="' + step + '"]');
  if (!stepEl) return;
  Object.entries(draft).forEach(([name, value]) => {
    const el = stepEl.querySelector('[name="' + CSS.escape(name) + '"]');
    if (!el || el.type === 'file' || el.type === 'hidden') return;
    if (el.tagName === 'SELECT') el.value = value;
    else if (el.type === 'checkbox' || el.type === 'radio') el.checked = (el.value === value);
    else el.value = value;
  });
}

// ── Acordeão de passos ─────────────────────────────────────
let _diarioIdGlobal = 0; // definido pelo preencher.php

function iniciarAcordeao(diarioId) {
  _diarioIdGlobal = diarioId;
  document.querySelectorAll('.step-h').forEach(h => {
    h.addEventListener('click', () => {
      const step   = h.closest('.step');
      const stepN  = parseInt(step.dataset.step || '0');
      const aberto = step.classList.contains('aberto');
      document.querySelectorAll('.step.aberto').forEach(s => s.classList.remove('aberto'));
      if (!aberto) {
        step.classList.add('aberto');
        if (stepN) restaurarDraft(diarioId, stepN);
        verificarGpsObrigatorio(stepN);
      }
    });
  });
}

// ── GPS obrigatório (steps 12 e 13) ───────────────────────
function verificarGpsObrigatorio(step) {
  if (step !== 12 && step !== 13) return;
  const latId = step === 12 ? 'lat-inicio' : 'lat-fim';
  const btnId = 'btn-gps-step-' + step;
  const latEl = document.getElementById(latId);
  const btn   = document.getElementById(btnId);
  if (!btn || !latEl) return;

  function atualizar() {
    const temGps = latEl.value && latEl.value.trim() !== '';
    btn.disabled  = !temGps;
    btn.title     = temGps ? '' : 'Capture o GPS antes de confirmar';
    btn.style.opacity = temGps ? '1' : '0.4';
  }
  latEl.addEventListener('change', atualizar);
  atualizar();
}

// Rellamado externamente após capturar GPS (capturarGPS chama isso)
window._onGpsCaptured = function (latInputId) {
  const step = latInputId === 'lat-inicio' ? 12 : (latInputId === 'lat-fim' ? 13 : 0);
  if (step) verificarGpsObrigatorio(step);
};

// ── Progresso ──────────────────────────────────────────────
window.marcarStepFeito = function (step) {
  const stepEl = document.querySelector('[data-step="' + step + '"]');
  if (stepEl) {
    stepEl.classList.add('feito');
    stepEl.classList.remove('aberto');
  }
  const feitos = document.querySelectorAll('.step.feito').length;
  const total  = document.querySelectorAll('.step').length || 21;
  const pct    = Math.round(feitos / total * 100);
  ['prog-bar', 'prog-bar-footer'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.width = pct + '%';
  });
  ['prog-pct', 'prog-pct-footer'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = pct + '%';
  });
};

// ── Salvar passo (online / offline) ───────────────────────
window.salvarStep = async function (form, diarioId, step) {
  const fd = new FormData(form);
  fd.set('diario_id', diarioId);
  fd.set('step', step);
  fd.set('csrf_token', document.querySelector('[name=csrf_token]')?.value || '');

  const btnOk = form.querySelector('.btn-step-ok');
  if (btnOk) btnOk.disabled = true;

  if (!_online) {
    const dados = {};
    for (const [k, v] of fd.entries()) dados[k] = v;
    adicionarFila({ tipo: 'step', diario_id: diarioId, step, dados });
    salvarRascunho(diarioId, step, dados);
    marcarStepFeito(step);
    if (btnOk) btnOk.disabled = false;
    mostrarToast('Salvo localmente — será enviado ao conectar.');
    return;
  }

  try {
    const resp = await fetch(EXECUTOR_BASE + '/diario/salvar', { method: 'POST', body: fd });
    const data = await resp.json();
    if (data.ok) {
      marcarStepFeito(step);
      limparRascunho(diarioId, step);
    } else {
      mostrarToast('Erro ao salvar. Verifique a conexão.', true);
    }
  } catch {
    const dados = {};
    for (const [k, v] of fd.entries()) dados[k] = v;
    adicionarFila({ tipo: 'step', diario_id: diarioId, step, dados });
    salvarRascunho(diarioId, step, dados);
    marcarStepFeito(step);
    mostrarToast('Sem conexão — salvo localmente.');
  }
  if (btnOk) btnOk.disabled = false;
};

// ── Upload de foto com GPS ─────────────────────────────────
window.uploadFoto = async function (input, diarioId, step, tipo) {
  if (!input.files[0]) return null;
  const file = input.files[0];

  // Validação client-side rápida
  if (file.size > 15 * 1024 * 1024) {
    mostrarToast('Foto muito grande (máx. 15 MB).', true);
    return null;
  }

  const container = input.closest('.fotos') || input.parentElement;

  // Indicador de carregamento
  const spinner = document.createElement('div');
  spinner.className = 'foto';
  spinner.style.cssText = 'display:grid;place-items:center;font-size:20px;animation:spin 1s linear infinite';
  spinner.textContent = '⏳';
  container.insertBefore(spinner, container.querySelector('.cam'));

  // Captura GPS
  let lat = null, lng = null;
  try {
    const pos = await new Promise((res, rej) =>
      navigator.geolocation.getCurrentPosition(res, rej, { timeout: 6000, enableHighAccuracy: true })
    );
    lat = pos.coords.latitude;
    lng = pos.coords.longitude;
  } catch { /* GPS não disponível */ }

  const fd = new FormData();
  fd.append('csrf_token', document.querySelector('[name=csrf_token]')?.value || '');
  fd.append('diario_id', diarioId);
  fd.append('step', step);
  fd.append('tipo', tipo || '');
  fd.append('foto', file);
  if (lat !== null) fd.append('lat', lat.toFixed(7));
  if (lng !== null) fd.append('lng', lng.toFixed(7));

  spinner.remove();

  if (!_online) {
    const url = URL.createObjectURL(file);
    adicionarFotoPreview(container, url, lat, lng);
    adicionarFila({ tipo: 'foto', diario_id: diarioId, step, tipo_foto: tipo, lat, lng });
    atualizarConexao();
    mostrarToast('Foto salva localmente.');
    return null;
  }

  try {
    const resp = await fetch(EXECUTOR_BASE + '/diario/foto', { method: 'POST', body: fd });
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    const data = await resp.json();
    if (data.ok) {
      adicionarFotoPreview(container, data.thumb, lat, lng);
      return data.foto_id;
    } else {
      mostrarToast(data.msg || 'Erro ao enviar foto.', true);
    }
  } catch (err) {
    const url = URL.createObjectURL(file);
    adicionarFotoPreview(container, url, lat, lng);
    adicionarFila({ tipo: 'foto', diario_id: diarioId, step, tipo_foto: tipo, lat, lng });
    atualizarConexao();
    mostrarToast('Sem conexão — foto salva localmente.');
  }
  return null;
};

function adicionarFotoPreview(container, src, lat, lng) {
  const div = document.createElement('div');
  div.className = 'foto';
  div.innerHTML = '<img src="' + src + '" style="width:100%;height:100%;object-fit:cover" alt="">'
    + (lat ? '<span class="gpsb">GPS</span>' : '');
  container.insertBefore(div, container.querySelector('.cam'));
}

// ── GPS chip helper ────────────────────────────────────────
window.capturarGPS = function (latInput, lngInput, chipEl, onCapturado) {
  if (chipEl) { chipEl.className = 'gps-chip aguardando'; chipEl.textContent = '📍 Capturando GPS…'; }
  if (!navigator.geolocation) {
    if (chipEl) { chipEl.textContent = '❌ GPS não disponível no dispositivo'; }
    return;
  }
  navigator.geolocation.getCurrentPosition(
    pos => {
      const lat = pos.coords.latitude.toFixed(7);
      const lng = pos.coords.longitude.toFixed(7);
      if (latInput) { latInput.value = lat; latInput.dispatchEvent(new Event('change')); }
      if (lngInput) lngInput.value = lng;
      if (chipEl) { chipEl.className = 'gps-chip'; chipEl.textContent = '📍 ' + lat + ', ' + lng + ' (±' + Math.round(pos.coords.accuracy) + 'm)'; }
      if (typeof onCapturado === 'function') onCapturado(lat, lng);
      if (latInput?.id) window._onGpsCaptured(latInput.id);
    },
    err => {
      const msg = err.code === 1 ? 'Permissão negada' : (err.code === 2 ? 'GPS indisponível' : 'Tempo esgotado');
      if (chipEl) { chipEl.className = 'gps-chip aguardando'; chipEl.textContent = '❌ ' + msg + ' — tente novamente'; }
    },
    { enableHighAccuracy: true, timeout: 12000 }
  );
};

// ── Toast de feedback ──────────────────────────────────────
function mostrarToast(msg, erro = false) {
  const t = document.createElement('div');
  t.style.cssText = 'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);'
    + 'background:' + (erro ? '#b23a2c' : '#1F7A6E') + ';color:#fff;border-radius:10px;'
    + 'padding:10px 18px;font-size:13px;font-weight:700;z-index:999;max-width:90vw;text-align:center;'
    + 'animation:fadeInUp .3s ease';
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 3000);
}

// ── Sync offline ───────────────────────────────────────────
let _sincronizando = false;

async function tentarSync() {
  if (_sincronizando || !_online) return;
  const fila = lerFila();
  if (fila.length === 0) return;

  _sincronizando = true;
  const csrf = document.querySelector('meta[name=csrf]')?.content
             || document.querySelector('[name=csrf_token]')?.value || '';
  try {
    const resp = await fetch(EXECUTOR_BASE + '/sync', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ csrf_token: csrf, fila })
    });
    if (resp.ok) {
      const data = await resp.json();
      if (data.ok) {
        salvarFila([]);
        atualizarConexao();
        mostrarToast('✅ ' + fila.length + ' item(s) sincronizado(s) com sucesso!');
      }
    }
  } catch { /* tentará na próxima reconexão */ }
  _sincronizando = false;
}

// ── CSS de animações inline ────────────────────────────────
(function () {
  const style = document.createElement('style');
  style.textContent = '@keyframes fadeInUp{from{opacity:0;transform:translate(-50%,10px)}to{opacity:1;transform:translate(-50%,0)}}';
  document.head.appendChild(style);
})();
