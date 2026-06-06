// Executor App — JS principal
// Responsável: GPS status, offline detection, localStorage draft, sync queue

const EXECUTOR_BASE = (function() {
  const m = window.location.pathname.match(/^(\/[^/]+\/[^/]+)/);
  return m ? m[1] : '';
})();

// ── GPS Status ─────────────────────────────────────────────
const gpsEl = document.getElementById('gps-status');
if (gpsEl && navigator.geolocation) {
  navigator.geolocation.getCurrentPosition(
    () => { gpsEl.textContent = '📍 GPS ativo'; },
    () => { gpsEl.textContent = '📍 GPS indisponível'; gpsEl.style.background = '#c0392b2e'; gpsEl.style.color = '#e74c3c'; }
  );
} else if (gpsEl) {
  gpsEl.textContent = '📍 sem GPS';
}

// ── Conectividade ──────────────────────────────────────────
const connBadge = document.getElementById('conn-badge');

function atualizarConexao() {
  const online = navigator.onLine;
  if (connBadge) {
    connBadge.textContent = online ? '🟢 Online' : '🔴 Offline';
    connBadge.className   = online ? '' : 'offline';
  }
  const offlineSec = document.getElementById('offline-sec');
  const offlineInfo = document.getElementById('offline-queue-info');
  if (offlineSec && offlineInfo) {
    const fila = lerFila();
    if (!online && fila.length > 0) {
      offlineSec.style.display  = 'flex';
      offlineInfo.style.display = 'block';
      const countEl = document.getElementById('offline-count');
      if (countEl) countEl.textContent = fila.length + ' item(s) para sincronizar';
    } else {
      offlineSec.style.display  = 'none';
      offlineInfo.style.display = 'none';
    }
  }
  if (online) tentarSync();
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
  fila.push(item);
  salvarFila(fila);
}

// Salva rascunho de passo no localStorage (offline-first)
window.salvarRascunho = function(diarioId, step, dados) {
  const key = 'rascunho_' + diarioId + '_' + step;
  localStorage.setItem(key, JSON.stringify(dados));
};

window.lerRascunho = function(diarioId, step) {
  try { return JSON.parse(localStorage.getItem('rascunho_' + diarioId + '_' + step) || 'null'); } catch { return null; }
};

// ── Sync ───────────────────────────────────────────────────
let sincronizando = false;

async function tentarSync() {
  if (sincronizando || !navigator.onLine) return;
  const fila = lerFila();
  if (fila.length === 0) return;

  sincronizando = true;
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
      }
    }
  } catch { /* offline — tenta na próxima vez */ }
  sincronizando = false;
}

// ── Passos do diário — accordion ──────────────────────────
document.querySelectorAll('.step-h').forEach(h => {
  h.addEventListener('click', () => {
    const step = h.closest('.step');
    const aberto = step.classList.contains('aberto');
    document.querySelectorAll('.step.aberto').forEach(s => s.classList.remove('aberto'));
    if (!aberto) step.classList.add('aberto');
  });
});

// ── Upload de foto com GPS ─────────────────────────────────
window.uploadFoto = async function(input, diarioId, step, tipo) {
  if (!input.files[0]) return null;
  const file = input.files[0];
  const container = input.closest('.fotos') || input.parentElement;

  // Captura GPS se disponível
  let lat = null, lng = null;
  try {
    const pos = await new Promise((res, rej) =>
      navigator.geolocation.getCurrentPosition(res, rej, { timeout: 5000 })
    );
    lat = pos.coords.latitude;
    lng = pos.coords.longitude;
  } catch { /* GPS indisponível */ }

  const fd = new FormData();
  fd.append('csrf_token', document.querySelector('[name=csrf_token]')?.value || '');
  fd.append('diario_id', diarioId);
  fd.append('step', step);
  fd.append('tipo', tipo || '');
  fd.append('foto', file);
  if (lat !== null) fd.append('lat', lat);
  if (lng !== null) fd.append('lng', lng);

  if (!navigator.onLine) {
    // Offline: mostra preview local, adiciona à fila de sync
    const url = URL.createObjectURL(file);
    adicionarFotoPreview(container, url, lat, lng);
    adicionarFila({ tipo: 'foto', diario_id: diarioId, step, tipo_foto: tipo, lat, lng });
    atualizarConexao();
    return null;
  }

  try {
    const resp = await fetch(EXECUTOR_BASE + '/diario/foto', { method: 'POST', body: fd });
    const data = await resp.json();
    if (data.ok) {
      adicionarFotoPreview(container, data.thumb, lat, lng);
      return data.foto_id;
    }
  } catch {
    const url = URL.createObjectURL(file);
    adicionarFotoPreview(container, url, lat, lng);
    adicionarFila({ tipo: 'foto', diario_id: diarioId, step, tipo_foto: tipo, lat, lng });
    atualizarConexao();
  }
  return null;
};

function adicionarFotoPreview(container, src, lat, lng) {
  const div = document.createElement('div');
  div.className = 'foto';
  div.innerHTML = '<img src="' + src + '" style="width:100%;height:100%;object-fit:cover">'
    + (lat ? '<span class="gpsb">GPS</span>' : '');
  container.insertBefore(div, container.querySelector('.cam'));
}

// ── Salvar passo (online) ──────────────────────────────────
window.salvarStep = async function(form, diarioId, step) {
  const fd = new FormData(form);
  fd.set('diario_id', diarioId);
  fd.set('step', step);

  const btnOk = form.querySelector('.btn-step-ok');
  if (btnOk) btnOk.disabled = true;

  if (!navigator.onLine) {
    // Serializa dados e guarda na fila
    const dados = {};
    for (const [k, v] of fd.entries()) dados[k] = v;
    adicionarFila({ tipo: 'step', diario_id: diarioId, step, dados });
    salvarRascunho(diarioId, step, dados);
    marcarStepFeito(step);
    if (btnOk) btnOk.disabled = false;
    return;
  }

  try {
    const resp = await fetch(EXECUTOR_BASE + '/diario/salvar', { method: 'POST', body: fd });
    const data = await resp.json();
    if (data.ok) {
      marcarStepFeito(step);
      localStorage.removeItem('rascunho_' + diarioId + '_' + step);
    } else {
      alert('Erro ao salvar. Verifique a conexão.');
    }
  } catch {
    const dados = {};
    for (const [k, v] of fd.entries()) dados[k] = v;
    adicionarFila({ tipo: 'step', diario_id: diarioId, step, dados });
    salvarRascunho(diarioId, step, dados);
    marcarStepFeito(step);
  }
  if (btnOk) btnOk.disabled = false;
};

function marcarStepFeito(step) {
  const stepEl = document.querySelector('[data-step="' + step + '"]');
  if (stepEl) {
    stepEl.classList.add('feito');
    stepEl.classList.remove('aberto');
  }
  // Atualiza barra de progresso
  const feitos = document.querySelectorAll('.step.feito').length;
  const total  = document.querySelectorAll('.step').length || 21;
  const pct    = Math.round(feitos / total * 100);
  const bar    = document.getElementById('prog-bar');
  const pctEl  = document.getElementById('prog-pct');
  if (bar)   bar.style.width = pct + '%';
  if (pctEl) pctEl.textContent = pct + '%';
}

// ── GPS chip helper ────────────────────────────────────────
window.capturarGPS = function(latInput, lngInput, chipEl) {
  if (chipEl) { chipEl.className = 'gps-chip aguardando'; chipEl.textContent = '📍 Capturando GPS…'; }
  if (!navigator.geolocation) {
    if (chipEl) { chipEl.className = 'gps-chip aguardando'; chipEl.textContent = '❌ GPS não disponível'; }
    return;
  }
  navigator.geolocation.getCurrentPosition(
    pos => {
      const lat = pos.coords.latitude.toFixed(7);
      const lng = pos.coords.longitude.toFixed(7);
      if (latInput) latInput.value = lat;
      if (lngInput) lngInput.value = lng;
      if (chipEl) { chipEl.className = 'gps-chip'; chipEl.textContent = '📍 ' + lat + ', ' + lng; }
    },
    () => {
      if (chipEl) { chipEl.className = 'gps-chip aguardando'; chipEl.textContent = '❌ GPS falhou — tente novamente'; }
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
};
