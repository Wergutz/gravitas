(() => {
  'use strict';

  const $ = (s, el = document) => el.querySelector(s);
  const brl = v => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const cm = v => v.toLocaleString('pt-BR', { maximumFractionDigits: 1 });
  const r1 = v => Math.round(v * 10) / 10;
  const LADOS = ['frente', 'verso'];
  const DO_LADO = { frente: 'da frente', verso: 'do verso' };
  const TIPOS = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'];

  /* ---------- Geometria da camiseta ----------
   * Desenho em um "viewBox" de 200×200 unidades.
   * O corpo da camiseta (x 46→154 = 108 un.) representa ~53 cm (tamanho M).
   */
  const U = 108 / 53;              // unidades por centímetro
  const DESLOC_Y = -8;             // centraliza a camiseta no quadro
  const CORPO = 'L170 52 L186 92 L158 102 L154 86 L154 176 Q100 182 46 176 L46 86 L42 102 L14 92 L30 52 Z';
  const CAMISA = {
    frente: new Path2D('M70 34 Q100 50 130 34 ' + CORPO),
    verso: new Path2D('M70 34 Q100 40 130 34 ' + CORPO),
  };
  const GOLA_INTERNA = new Path2D('M70 34 Q100 24 130 34 Q100 50 70 34 Z');
  const GOLA = { frente: new Path2D('M73 36 Q100 54 127 36'), verso: new Path2D('M73 37 Q100 44 127 37') };
  const CAVAS = new Path2D('M46 86 Q47 66 38 55 M154 86 Q153 66 162 55');
  const TOPO_AREA = { frente: 60, verso: 50 };

  let CFG = null;
  const est = {
    modelo: null, cor: null, lado: 'frente',
    qtd: {},
    artes: { frente: null, verso: null }, // { file, img, url, pxW, pxH, vetor, aspect, x, y, w, h } (cm)
  };

  const tela = $('#tela');
  const ctx = tela.getContext('2d');

  function area(lado) {
    const w = CFG.area.larg * U, h = CFG.area.alt * U;
    return { x0: 100 - w / 2, y0: TOPO_AREA[lado], w, h };
  }

  function escurecer(hex, f) {
    const n = parseInt(hex.slice(1), 16);
    const c = [(n >> 16) & 255, (n >> 8) & 255, n & 255].map(v => Math.round(v * f));
    return `rgb(${c.join(',')})`;
  }
  function luminancia(hex) {
    const n = parseInt(hex.slice(1), 16);
    return (0.299 * ((n >> 16) & 255) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255)) / 255;
  }

  /** Desenha uma camiseta no contexto já transformado para o viewBox 200×200. */
  function desenharCamiseta(c, lado, { editar = false } = {}) {
    const cor = est.cor.hex;
    const camisa = CAMISA[lado];
    c.save();
    c.translate(0, DESLOC_Y);

    if (lado === 'frente') { c.fillStyle = escurecer(cor, 0.78); c.fill(GOLA_INTERNA); }
    c.fillStyle = cor;
    c.fill(camisa);

    const a = est.artes[lado];
    const A = area(lado);
    if (a) {
      c.save();
      c.clip(camisa);
      c.imageSmoothingQuality = 'high';
      c.drawImage(a.img, A.x0 + a.x * U, A.y0 + a.y * U, a.w * U, a.h * U);
      c.restore();
    }

    // Sombreado de tecido
    c.save();
    c.clip(camisa);
    const g = c.createLinearGradient(14, 0, 186, 0);
    g.addColorStop(0, 'rgba(0,0,0,.14)');
    g.addColorStop(0.28, 'rgba(0,0,0,0)');
    g.addColorStop(0.5, 'rgba(255,255,255,.05)');
    g.addColorStop(0.72, 'rgba(0,0,0,0)');
    g.addColorStop(1, 'rgba(0,0,0,.14)');
    c.fillStyle = g;
    c.fillRect(0, 0, 200, 200);
    const gv = c.createLinearGradient(0, 30, 0, 182);
    gv.addColorStop(0, 'rgba(255,255,255,.06)');
    gv.addColorStop(1, 'rgba(0,0,0,.08)');
    c.fillStyle = gv;
    c.fillRect(0, 0, 200, 200);
    c.strokeStyle = 'rgba(0,0,0,.12)';
    c.lineWidth = 0.8;
    c.stroke(CAVAS);
    c.restore();

    const borda = luminancia(cor) > 0.8 ? 'rgba(0,0,0,.22)' : 'rgba(0,0,0,.35)';
    c.strokeStyle = borda;
    c.lineWidth = 1;
    c.lineJoin = 'round';
    c.stroke(camisa);
    c.lineWidth = 1.6;
    c.strokeStyle = escurecer(cor, 0.82);
    c.stroke(GOLA[lado]);

    if (editar) {
      const claro = luminancia(cor) < 0.45;
      c.setLineDash([2, 2]);
      c.lineWidth = 0.5;
      c.strokeStyle = claro ? 'rgba(255,255,255,.55)' : 'rgba(0,0,0,.35)';
      c.strokeRect(A.x0, A.y0, A.w, A.h);
      c.setLineDash([]);
      if (!a) {
        c.fillStyle = claro ? 'rgba(255,255,255,.7)' : 'rgba(0,0,0,.45)';
        c.font = '600 5px Archivo, sans-serif';
        c.textAlign = 'center';
        c.fillText(`Área de impressão ${CFG.area.larg} × ${CFG.area.alt} cm`, 100, A.y0 + A.h / 2);
      } else {
        const x = A.x0 + a.x * U, y = A.y0 + a.y * U, w = a.w * U, h = a.h * U;
        c.strokeStyle = '#e2572b';
        c.lineWidth = 0.7;
        c.strokeRect(x, y, w, h);
        c.fillStyle = '#e2572b';
        c.beginPath(); c.arc(x + w, y + h, 3, 0, Math.PI * 2); c.fill();
        c.fillStyle = '#fff';
        c.beginPath(); c.arc(x + w, y + h, 1.3, 0, Math.PI * 2); c.fill();
        // etiqueta de medida
        const txt = `${cm(a.w)} × ${cm(a.h)} cm`;
        c.font = '700 5px Archivo, sans-serif';
        const tw = c.measureText(txt).width + 6;
        let ty = y - 9;
        if (ty < A.y0 - 9) ty = y + 2;
        const tx = Math.min(Math.max(x + w / 2 - tw / 2, 2), 198 - tw);
        c.fillStyle = 'rgba(29,29,31,.88)';
        c.beginPath(); c.roundRect(tx, ty, tw, 7.5, 2); c.fill();
        c.fillStyle = '#fff';
        c.textAlign = 'center';
        c.fillText(txt, tx + tw / 2, ty + 5.4);
      }
    }
    c.restore();
  }

  /* ---------- Render do editor ---------- */
  let pedidoRender = false;
  function render() {
    if (pedidoRender || !CFG) return;
    pedidoRender = true;
    requestAnimationFrame(() => {
      pedidoRender = false;
      const dpr = window.devicePixelRatio || 1;
      const lado = tela.clientWidth;
      if (tela.width !== Math.round(lado * dpr)) { tela.width = tela.height = Math.round(lado * dpr); }
      const s = tela.width / 200;
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      ctx.clearRect(0, 0, tela.width, tela.height);
      ctx.setTransform(s, 0, 0, s, 0, 0);
      desenharCamiseta(ctx, est.lado, { editar: true });
    });
  }

  /* ---------- Interação: arrastar e redimensionar ---------- */
  function pontoCamisa(e) {
    const r = tela.getBoundingClientRect();
    return { x: (e.clientX - r.left) / r.width * 200, y: (e.clientY - r.top) / r.height * 200 - DESLOC_Y };
  }

  function limitar(a) {
    const W = CFG.area.larg, H = CFG.area.alt;
    const maxW = Math.min(W, H * a.aspect);
    a.w = Math.min(Math.max(a.w, 2), maxW);
    a.h = a.w / a.aspect;
    if (a.h < 2) { a.h = 2; a.w = a.h * a.aspect; }
    a.x = Math.min(Math.max(a.x, 0), W - a.w);
    a.y = Math.min(Math.max(a.y, 0), H - a.h);
  }

  function alvo(p) {
    const a = est.artes[est.lado];
    if (!a) return null;
    const A = area(est.lado);
    const x = A.x0 + a.x * U, y = A.y0 + a.y * U, w = a.w * U, h = a.h * U;
    const raio = tela.clientWidth < 400 ? 9 : 6;
    if (Math.hypot(p.x - (x + w), p.y - (y + h)) <= raio) return 'redim';
    if (p.x >= x && p.x <= x + w && p.y >= y && p.y <= y + h) return 'mover';
    return null;
  }

  let arraste = null;
  tela.addEventListener('pointerdown', e => {
    const p = pontoCamisa(e);
    const modo = alvo(p);
    if (!modo) return;
    e.preventDefault();
    tela.setPointerCapture(e.pointerId);
    const a = est.artes[est.lado];
    const A = area(est.lado);
    arraste = { modo, dx: (p.x - A.x0) / U - a.x, dy: (p.y - A.y0) / U - a.y };
  });
  tela.addEventListener('pointermove', e => {
    const p = pontoCamisa(e);
    if (!arraste) {
      const m = alvo(p);
      tela.className = m === 'redim' ? 'redim' : m === 'mover' ? 'mover' : '';
      return;
    }
    const a = est.artes[est.lado];
    const A = area(est.lado);
    const px = (p.x - A.x0) / U, py = (p.y - A.y0) / U;
    if (arraste.modo === 'mover') {
      a.x = px - arraste.dx;
      a.y = py - arraste.dy;
    } else {
      // mantém proporção; usa o eixo que o usuário mais puxou
      const w1 = px - a.x, w2 = (py - a.y) * a.aspect;
      a.w = Math.max(w1, w2);
    }
    limitar(a);
    render();
    atualizarAjustes();
    atualizarPreco();
  });
  const soltarArraste = () => { arraste = null; };
  tela.addEventListener('pointerup', soltarArraste);
  tela.addEventListener('pointercancel', soltarArraste);

  tela.addEventListener('keydown', e => {
    const a = est.artes[est.lado];
    if (!a) return;
    const passo = e.shiftKey ? 2 : 0.5;
    const mov = { ArrowLeft: [-passo, 0], ArrowRight: [passo, 0], ArrowUp: [0, -passo], ArrowDown: [0, passo] }[e.key];
    if (!mov) return;
    e.preventDefault();
    a.x += mov[0]; a.y += mov[1];
    limitar(a); render(); atualizarAjustes();
  });

  /* ---------- Upload ---------- */
  function carregarArte(file, lado) {
    if (!file) return;
    if (!TIPOS.includes(file.type)) { toast('Formato não aceito. Use PNG, JPG, WEBP ou SVG.'); return; }
    if (file.size > CFG.arquivo_max_mb * 1024 * 1024) { toast(`Arquivo acima de ${CFG.arquivo_max_mb} MB.`); return; }
    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload = () => {
      const vetor = file.type === 'image/svg+xml';
      const pxW = img.naturalWidth || 1000, pxH = img.naturalHeight || 1000;
      const anterior = est.artes[lado];
      if (anterior) URL.revokeObjectURL(anterior.url);
      const aspect = pxW / pxH;
      const a = { file, img, url, pxW, pxH, vetor, aspect, x: 0, y: 0, w: 25, h: 25 / aspect };
      // Começa centralizada, com 25 cm de largura (ou menor, se for alta)
      a.w = Math.min(25, (CFG.area.alt - 4) * aspect);
      limitar(a);
      a.x = (CFG.area.larg - a.w) / 2;
      a.y = 2;
      limitar(a);
      est.artes[lado] = a;
      trocarLado(lado);
      atualizarTudo();
      toast(`Arte ${DO_LADO[lado]} carregada. Arraste para posicionar.`);
    };
    img.onerror = () => { URL.revokeObjectURL(url); toast('Não foi possível abrir essa imagem.'); };
    img.src = url;
  }

  $('#arquivo').addEventListener('change', e => { carregarArte(e.target.files[0], est.lado); e.target.value = ''; });

  const palco = $('#palco');
  ['dragenter', 'dragover'].forEach(ev => palco.addEventListener(ev, e => { e.preventDefault(); palco.classList.add('arrastando'); }));
  ['dragleave', 'drop'].forEach(ev => palco.addEventListener(ev, e => { e.preventDefault(); palco.classList.remove('arrastando'); }));
  palco.addEventListener('drop', e => carregarArte(e.dataTransfer.files[0], est.lado));

  /* ---------- Ajustes (medidas e atalhos) ---------- */
  function atualizarAjustes() {
    const a = est.artes[est.lado];
    $('#ajustes').hidden = !a;
    $('#rotuloUpload').textContent = `${a ? 'Trocar' : 'Enviar'} arte ${DO_LADO[est.lado]}`;
    for (const l of LADOS) {
      const el = $(l === 'frente' ? '#statusFrente' : '#statusVerso');
      const x = est.artes[l];
      el.textContent = x ? `${cm(r1(x.w))} × ${cm(r1(x.h))} cm` : 'sem arte';
      el.classList.toggle('tem', !!x);
    }
    if (!a) return;
    if (document.activeElement !== $('#largura')) $('#largura').value = r1(a.w);
    if (document.activeElement !== $('#altura')) $('#altura').value = r1(a.h);
    const f = faixa(r1(a.w), r1(a.h));
    $('#faixaAtual').innerHTML = f ? `Estampa <strong>${esc(f.nome)} · ${brl(f.preco)}</strong>por peça` : '';

    const alerta = $('#alertaDpi');
    if (a.vetor) { alerta.hidden = true; return; }
    const dpi = Math.round(a.pxW / (a.w / 2.54));
    alerta.hidden = dpi >= 150;
    alerta.classList.toggle('forte', dpi < 100);
    alerta.textContent = dpi < 100
      ? `Resolução baixa (${dpi} DPI nesse tamanho): a estampa deve sair pixelada. Envie uma imagem maior ou diminua a arte.`
      : `Resolução razoável (${dpi} DPI). Para melhor nitidez, o ideal é 200 DPI ou mais.`;
  }

  function medidaDigitada(campo) {
    const a = est.artes[est.lado];
    const v = parseFloat(String($(campo).value).replace(',', '.'));
    if (!a || !(v > 0)) return;
    const cx = a.x + a.w / 2;
    a.w = campo === '#largura' ? v : v * a.aspect;
    limitar(a);
    a.x = cx - a.w / 2;
    limitar(a);
    render(); atualizarAjustes(); atualizarPreco();
  }
  $('#largura').addEventListener('input', () => medidaDigitada('#largura'));
  $('#altura').addEventListener('input', () => medidaDigitada('#altura'));
  ['#largura', '#altura'].forEach(s => $(s).addEventListener('blur', atualizarAjustes));

  document.querySelector('.presets').addEventListener('click', e => {
    const b = e.target.closest('[data-acao]');
    const a = est.artes[est.lado];
    if (!b || !a) return;
    const W = CFG.area.larg;
    switch (b.dataset.acao) {
      case 'centro': a.x = (W - a.w) / 2; break;
      case 'peito':
        a.w = Math.min(9, 9 * a.aspect); limitar(a);
        a.x = est.lado === 'frente' ? W - a.w - 3.5 : (W - a.w) / 2;
        a.y = est.lado === 'frente' ? 3 : 1;
        break;
      case 'max': a.w = 999; limitar(a); a.x = (W - a.w) / 2; a.y = 0; break;
      case 'remover':
        URL.revokeObjectURL(a.url);
        est.artes[est.lado] = null;
        atualizarTudo();
        return;
    }
    limitar(a); render(); atualizarAjustes(); atualizarPreco();
  });

  /* ---------- Abas ---------- */
  function trocarLado(lado) {
    est.lado = lado;
    document.querySelectorAll('.aba').forEach(b => {
      const ativo = b.dataset.lado === lado;
      b.classList.toggle('ativo', ativo);
      b.setAttribute('aria-selected', ativo);
    });
    render(); atualizarAjustes();
  }
  document.querySelector('.abas').addEventListener('click', e => {
    const b = e.target.closest('[data-lado]');
    if (b) trocarLado(b.dataset.lado);
  });

  /* ---------- Modelo, cor e quantidades ---------- */
  function renderOpcoes() {
    $('#modelos').innerHTML = Object.entries(CFG.modelos).map(([id, m]) =>
      `<button type="button" class="modelo${id === est.modelo ? ' ativo' : ''}" data-modelo="${esc(id)}" aria-pressed="${id === est.modelo}">
        <strong>${esc(m.nome)}</strong><span>${esc(m.descricao || '')}</span><em>${brl(m.preco)}</em>
      </button>`).join('');
    $('#cores').innerHTML = CFG.cores.map((c, i) =>
      `<button type="button" class="cor${c === est.cor ? ' ativo' : ''}" style="--c:${c.hex}" data-cor="${i}" aria-label="${esc(c.nome)}" aria-pressed="${c === est.cor}" title="${esc(c.nome)}"></button>`).join('');
    $('#corNome').textContent = est.cor.nome;
  }
  $('#modelos').addEventListener('click', e => {
    const b = e.target.closest('[data-modelo]');
    if (b) { est.modelo = b.dataset.modelo; renderOpcoes(); atualizarPreco(); }
  });
  $('#cores').addEventListener('click', e => {
    const b = e.target.closest('[data-cor]');
    if (b) { est.cor = CFG.cores[+b.dataset.cor]; renderOpcoes(); render(); }
  });

  function renderTamanhos() {
    $('#tamanhos').innerHTML = Object.entries(CFG.tamanhos).map(([t, extra]) =>
      `<div class="tam${est.qtd[t] ? ' tem' : ''}">
        <label for="q-${t}">${t}${extra ? `<small>+${brl(extra)}</small>` : ''}</label>
        <input id="q-${t}" type="number" min="0" max="${CFG.qtd_maxima}" inputmode="numeric" data-tam="${t}" value="${est.qtd[t] || ''}" placeholder="0">
      </div>`).join('');
  }
  $('#tamanhos').addEventListener('input', e => {
    const t = e.target.dataset.tam;
    if (!t) return;
    est.qtd[t] = Math.max(0, Math.min(CFG.qtd_maxima, parseInt(e.target.value, 10) || 0));
    e.target.closest('.tam').classList.toggle('tem', est.qtd[t] > 0);
    atualizarPreco();
  });

  /* ---------- Cálculo (espelha api/_lib.php) ---------- */
  function faixa(w, h) {
    const e = 1e-6;
    return CFG.estampa.find(f => (w <= f.larg + e && h <= f.alt + e) || (w <= f.alt + e && h <= f.larg + e)) || null;
  }

  function calcular() {
    const m = CFG.modelos[est.modelo];
    let porPeca = m.preco;
    const lados = [];
    for (const l of LADOS) {
      const a = est.artes[l];
      if (!a) continue;
      const w = r1(a.w), h = r1(a.h);
      const f = faixa(w, h);
      lados.push({ lado: l, w, h, f });
      porPeca += f.preco;
    }
    let qtd = 0, subtotal = 0;
    const itens = [];
    for (const [t, extra] of Object.entries(CFG.tamanhos)) {
      const n = est.qtd[t] || 0;
      if (!n) continue;
      qtd += n;
      subtotal += n * (porPeca + extra);
      itens.push({ t, n, extra });
    }
    let pct = 0;
    for (const d of CFG.descontos) if (qtd >= d.min) pct = Math.max(pct, d.pct);
    const desconto = Math.round(subtotal * pct) / 100;
    return { m, lados, porPeca, qtd, itens, subtotal, pct, desconto, total: Math.round((subtotal - desconto) * 100) / 100 };
  }

  function atualizarPreco() {
    const c = calcular();
    const linhas = [`<div class="linha"><span>Camiseta ${esc(c.m.nome)}</span><span>${brl(c.m.preco)}</span></div>`];
    for (const l of c.lados) {
      linhas.push(`<div class="linha"><span>Estampa ${l.lado} · ${esc(l.f.nome)}<small>${cm(l.w)} × ${cm(l.h)} cm</small></span><span>${brl(l.f.preco)}</span></div>`);
    }
    if (!c.lados.length) linhas.push('<p class="vazio">Envie a arte para calcular a estampa.</p>');
    linhas.push(`<div class="linha"><strong>Por peça</strong><strong>${brl(c.porPeca)}</strong></div>`);
    linhas.push('<div class="sep"></div>');
    if (c.qtd) {
      for (const i of c.itens) {
        linhas.push(`<div class="linha"><span>${i.n}× ${i.t}${i.extra ? ` <small>(+${brl(i.extra)} cada)</small>` : ''}</span><span>${brl(i.n * (c.porPeca + i.extra))}</span></div>`);
      }
      if (c.pct) linhas.push(`<div class="linha desconto"><span>Desconto ${c.pct}% (${c.qtd} peças)</span><span>− ${brl(c.desconto)}</span></div>`);
      linhas.push(`<div class="linha total"><span>Total</span><span>${brl(c.total)}</span></div>`);
      linhas.push(`<div class="unit">${c.qtd} ${c.qtd === 1 ? 'peça' : 'peças'} · ${brl(c.total / c.qtd)} cada</div>`);
    } else {
      linhas.push('<p class="vazio">Informe as quantidades por tamanho.</p>');
    }
    $('#resumo').innerHTML = linhas.join('');

    const prox = CFG.descontos.find(d => c.qtd < d.min);
    $('#notaDesconto').textContent = prox
      ? `${c.qtd ? `Faltam ${prox.min - c.qtd} peças` : `A partir de ${prox.min} peças`} para ${prox.pct}% de desconto.`
      : `Desconto máximo de ${c.pct}% aplicado.`;

    const atuais = new Set(c.lados.map(l => l.f.nome));
    $('#tabelaPrecos').innerHTML = `
      <table><tr><th>Estampa (por lado)</th><th>Até</th><th>Preço</th></tr>
      ${CFG.estampa.map(f => `<tr class="${atuais.has(f.nome) ? 'atual' : ''}"><td>${esc(f.nome)}</td><td>${f.larg} × ${f.alt} cm</td><td>${brl(f.preco)}</td></tr>`).join('')}
      </table>
      <p>Preço por peça = camiseta + estampa de cada lado + adicional do tamanho. A faixa é a menor em que a arte cabe, em pé ou deitada.</p>
      <table><tr><th>Quantidade</th><th></th><th>Desconto</th></tr>
      ${CFG.descontos.map(d => `<tr class="${c.pct === d.pct && c.pct ? 'atual' : ''}"><td>${d.min}+ peças</td><td></td><td>${d.pct}%</td></tr>`).join('')}
      </table>`;

    $('#btnEnviar').disabled = !(c.lados.length && c.qtd);
    $('#btnBaixar').disabled = !c.lados.length;
    return c;
  }

  function atualizarTudo() { render(); atualizarAjustes(); atualizarPreco(); }

  /* ---------- Imagem frente e verso ---------- */
  function gerarMockup() {
    const W = 2400, H = 1400, K = 5.6;
    const cv = document.createElement('canvas');
    cv.width = W; cv.height = H;
    const c = cv.getContext('2d');
    c.fillStyle = '#f7f4ee';
    c.fillRect(0, 0, W, H);
    LADOS.forEach((lado, i) => {
      const ox = 40 + i * (W / 2) + (W / 2 - 200 * K) / 2 - 20;
      c.save();
      c.setTransform(K, 0, 0, K, ox, 80);
      desenharCamiseta(c, lado);
      c.restore();
      c.fillStyle = '#1d1d1f';
      c.font = '800 40px Archivo, sans-serif';
      c.textAlign = 'center';
      c.fillText(lado === 'frente' ? 'FRENTE' : 'VERSO', ox + 100 * K, 1210);
      const a = est.artes[lado];
      c.fillStyle = '#6b6760';
      c.font = '400 30px Archivo, sans-serif';
      c.fillText(a ? `Estampa ${cm(r1(a.w))} × ${cm(r1(a.h))} cm` : 'Sem estampa', ox + 100 * K, 1256);
    });
    const m = CFG.modelos[est.modelo];
    c.textAlign = 'left';
    c.fillStyle = '#1d1d1f';
    c.font = '900 44px "Archivo Black", Archivo, sans-serif';
    c.fillText('GW', 60, 90);
    c.fillStyle = '#e2572b';
    c.font = '800 26px Archivo, sans-serif';
    c.fillText('SHOP', 60 + c.measureText('GW').width + 70, 80);
    c.fillStyle = '#6b6760';
    c.font = '400 28px Archivo, sans-serif';
    c.textAlign = 'right';
    c.fillText(`${m.nome} · ${est.cor.nome}`, W - 60, 86);
    c.textAlign = 'center';
    c.font = '400 24px Archivo, sans-serif';
    c.fillText('Simulação ilustrativa · gwshop.com.br', W / 2, H - 50);
    return new Promise(res => cv.toBlob(res, 'image/png'));
  }

  let urlMockup = null;
  async function urlDoMockup() {
    const blob = await gerarMockup();
    if (urlMockup) URL.revokeObjectURL(urlMockup);
    urlMockup = URL.createObjectURL(blob);
    return { blob, url: urlMockup };
  }

  $('#btnBaixar').addEventListener('click', async () => {
    const { url } = await urlDoMockup();
    const a = document.createElement('a');
    a.href = url; a.download = 'camiseta-gwshop.png';
    document.body.appendChild(a); a.click(); a.remove();
  });

  /* ---------- Envio ---------- */
  const form = $('#form');
  form.whatsapp.addEventListener('input', e => {
    const d = e.target.value.replace(/\D/g, '').slice(0, 11);
    e.target.value = d.length > 6 ? `(${d.slice(0, 2)}) ${d.slice(2, d.length - 4)}-${d.slice(-4)}` : d.length > 2 ? `(${d.slice(0, 2)}) ${d.slice(2)}` : d;
  });

  function erro(msg, campo) {
    const el = $('#erroForm');
    el.textContent = msg; el.hidden = !msg;
    form.querySelectorAll('[aria-invalid]').forEach(i => i.removeAttribute('aria-invalid'));
    if (campo) { campo.setAttribute('aria-invalid', 'true'); campo.focus(); }
  }

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const c = calcular();
    if (!c.lados.length) return erro('Envie a arte de pelo menos um lado.');
    if (!c.qtd) return erro('Informe a quantidade de camisetas.');
    if (form.nome.value.trim().length < 2) return erro('Informe seu nome.', form.nome);
    const whats = form.whatsapp.value.replace(/\D/g, '');
    if (whats.length < 10) return erro('Informe o WhatsApp com DDD.', form.whatsapp);
    if (form.email.value && !form.email.checkValidity()) return erro('E-mail inválido.', form.email);
    erro('');

    const btn = $('#btnEnviar');
    btn.disabled = true;
    btn.textContent = 'Enviando…';
    try {
      const { blob, url } = await urlDoMockup();
      const dados = {
        modelo: est.modelo,
        cor: est.cor.nome,
        quantidades: est.qtd,
        lados: Object.fromEntries(LADOS.map(l => {
          const a = est.artes[l];
          return [l, a ? { w: r1(a.w), h: r1(a.h), x: r1(a.x), y: r1(a.y) } : null];
        })),
        cliente: { nome: form.nome.value.trim(), whatsapp: whats, email: form.email.value.trim(), obs: form.obs.value.trim() },
      };
      const fd = new FormData();
      fd.append('dados', JSON.stringify(dados));
      fd.append('site', form.site.value);
      for (const l of LADOS) if (est.artes[l]) fd.append(`arte_${l}`, est.artes[l].file, est.artes[l].file.name);
      fd.append('mockup', blob, 'mockup.png');

      const resp = await fetch('api/orcamento.php', { method: 'POST', body: fd });
      let json = {};
      try { json = await resp.json(); } catch { /* resposta não-JSON */ }
      if (!resp.ok || !json.ok) throw new Error(json.erro || 'Não foi possível enviar agora. Tente novamente.');
      sucesso(json, c, url);
    } catch (err) {
      erro(err.message || 'Falha de conexão. Tente novamente.');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Enviar orçamento';
      atualizarPreco();
    }
  });

  function sucesso(json, c, urlImg) {
    const calc = json.calculo;
    $('#sucessoCodigo').textContent = json.codigo;
    $('#sucessoImagem').src = urlImg;
    $('#sucessoBaixar').href = urlImg;
    $('#sucessoTotal').textContent = `Total: ${brl(calc.total)} · ${calc.qtd} peças`;
    const lados = Object.entries(calc.lados).map(([l, v]) => `${l} ${cm(v.w)}×${cm(v.h)} cm`).join(', ');
    const tams = calc.itens.map(i => `${i.qtd} ${i.tamanho}`).join(', ');
    const msg = [
      `Olá! Fiz o orçamento *${json.codigo}* no site.`,
      `${calc.qtd}× ${calc.modelo.nome} ${calc.cor.nome} (${tams})`,
      `Estampa: ${lados}`,
      `Total: *${brl(calc.total)}*`,
    ].join('\n');
    $('#sucessoWhats').href = `https://wa.me/${CFG.loja.whatsapp}?text=${encodeURIComponent(msg)}`;
    const m = $('#sucesso');
    m.hidden = false;
    document.body.classList.add('travado');
    $('.fechar', m).focus();
  }
  document.addEventListener('click', e => { if (e.target.closest('[data-fechar]')) fecharModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && !$('#sucesso').hidden) fecharModal(); });
  function fecharModal() { $('#sucesso').hidden = true; document.body.classList.remove('travado'); }

  let toastT;
  function toast(t) {
    const el = $('#toast');
    el.textContent = t; el.classList.add('visivel');
    clearTimeout(toastT);
    toastT = setTimeout(() => el.classList.remove('visivel'), 3000);
  }

  /* ---------- Início ---------- */
  new ResizeObserver(() => render()).observe(tela);
  fetch('api/precos.php')
    .then(r => { if (!r.ok) throw new Error(); return r.json(); })
    .then(cfg => {
      CFG = cfg;
      est.modelo = Object.keys(CFG.modelos)[0];
      est.cor = CFG.cores[0];
      $('#maxMb').textContent = CFG.arquivo_max_mb;
      $('#linkWhats').href = `https://wa.me/${CFG.loja.whatsapp}`;
      $('#carregando').remove();
      renderOpcoes();
      renderTamanhos();
      atualizarTudo();
      if (document.fonts) document.fonts.ready.then(render);
    })
    .catch(() => {
      $('#carregando').textContent = 'Não foi possível carregar os preços. Recarregue a página.';
    });
})();
