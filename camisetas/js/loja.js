(() => {
  'use strict';

  const $ = (s, el = document) => el.querySelector(s);
  const brl = v => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const CHAVE = 'trama_carrinho_v1';

  /* ---------- Desenho das camisetas (SVG) ---------- */
  function luminancia(hex) {
    const n = parseInt(hex.slice(1), 16);
    const r = (n >> 16) & 255, g = (n >> 8) & 255, b = n & 255;
    return (0.299 * r + 0.587 * g + 0.114 * b) / 255;
  }

  function desenhoEstampa(e, corFundo) {
    if (!e) return '';
    const escuro = luminancia(corFundo) < 0.45;
    let cor = e.cor;
    if (escuro && e.corEscura) cor = e.corEscura;
    if (!escuro && e.corClara) cor = e.corClara;
    if (escuro && !e.corEscura && luminancia(cor) < 0.3) cor = '#f2ede3';

    switch (e.tipo) {
      case 'sol': {
        let raios = '';
        for (let i = 0; i < 12; i++) {
          const a = (i * Math.PI) / 6;
          raios += `<line x1="${100 + Math.cos(a) * 30}" y1="${112 + Math.sin(a) * 30}" x2="${100 + Math.cos(a) * 42}" y2="${112 + Math.sin(a) * 42}" stroke="${cor}" stroke-width="5" stroke-linecap="round"/>`;
        }
        return `<circle cx="100" cy="112" r="22" fill="${cor}"/>${raios}`;
      }
      case 'ondas':
        return [0, 1, 2].map(i => `<path d="M62 ${100 + i * 14} q9.5 -10 19 0 t19 0 t19 0 t19 0" fill="none" stroke="${cor}" stroke-width="5" stroke-linecap="round" opacity="${1 - i * 0.25}"/>`).join('');
      case 'montanha':
        return `<path d="M58 140 L82 100 L94 118 L110 88 L142 140" fill="none" stroke="${cor}" stroke-width="5" stroke-linejoin="round" stroke-linecap="round"/><circle cx="128" cy="92" r="7" fill="none" stroke="${cor}" stroke-width="4"/>`;
      case 'texto': {
        const linhas = e.texto.split('\n');
        return linhas.map((l, i) => `<text x="100" y="${112 + (i - (linhas.length - 1) / 2) * 26}" text-anchor="middle" dominant-baseline="middle" font-family="Archivo Black, Arial Black, sans-serif" font-size="26" fill="${cor}">${esc(l)}</text>`).join('');
      }
      case 'peito':
        return `<text x="122" y="84" text-anchor="middle" font-family="Archivo Black, Arial Black, sans-serif" font-size="10" letter-spacing="1" fill="${cor}">${esc(e.texto)}</text>`;
      default:
        return '';
    }
  }

  function camisetaSVG(produto, cor) {
    const borda = luminancia(cor.hex) > 0.85 ? 'rgba(0,0,0,.14)' : 'rgba(0,0,0,.18)';
    return `<svg viewBox="0 0 200 200" role="img" aria-label="${esc(produto.nome)} na cor ${esc(cor.nome)}">
      <path d="M70 34 Q100 50 130 34 L170 52 L186 92 L158 102 L154 86 L154 176 Q100 182 46 176 L46 86 L42 102 L14 92 L30 52 Z"
        fill="${cor.hex}" stroke="${borda}" stroke-width="1.5" stroke-linejoin="round"/>
      <path d="M70 34 Q100 58 130 34" fill="none" stroke="${borda}" stroke-width="2"/>
      ${desenhoEstampa(produto.estampa, cor.hex)}
    </svg>`;
  }

  function imagemProduto(p, cor) {
    return p.imagem ? `<img src="${esc(p.imagem)}" alt="${esc(p.nome)}" loading="lazy">` : camisetaSVG(p, cor);
  }

  /* ---------- Estado ---------- */
  let filtro = 'Todas';
  let ordem = 'destaque';
  let carrinho = carregar();
  const sel = { produto: null, cor: 0, tamanho: null, qtd: 1 };

  function carregar() {
    try { return JSON.parse(localStorage.getItem(CHAVE)) || []; } catch { return []; }
  }
  function salvar() {
    try { localStorage.setItem(CHAVE, JSON.stringify(carrinho)); } catch { /* sem storage */ }
  }

  /* ---------- Catálogo ---------- */
  function renderFiltros() {
    const cats = ['Todas', ...new Set(PRODUTOS.map(p => p.categoria))];
    $('#filtros').innerHTML = cats.map(c =>
      `<button role="tab" aria-selected="${c === filtro}" class="chip${c === filtro ? ' ativo' : ''}" data-cat="${esc(c)}">${esc(c)}</button>`).join('');
  }

  function listaVisivel() {
    let lista = PRODUTOS.filter(p => filtro === 'Todas' || p.categoria === filtro);
    const cmp = {
      destaque: (a, b) => a.destaque - b.destaque,
      menor: (a, b) => a.preco - b.preco,
      maior: (a, b) => b.preco - a.preco,
      nome: (a, b) => a.nome.localeCompare(b.nome, 'pt-BR'),
    }[ordem];
    return lista.slice().sort(cmp);
  }

  function renderGrade() {
    $('#grade').innerHTML = listaVisivel().map(p => {
      const off = p.precoAntigo ? Math.round((1 - p.preco / p.precoAntigo) * 100) : 0;
      return `<article class="card" data-id="${p.id}" tabindex="0">
        <div class="card__imagem">
          ${off ? `<span class="selo">-${off}%</span>` : ''}
          ${imagemProduto(p, p.cores[0])}
        </div>
        <div class="card__info">
          <h3>${esc(p.nome)}</h3>
          <div class="card__precos">
            <span class="preco">${brl(p.preco)}</span>
            ${p.precoAntigo ? `<s>${brl(p.precoAntigo)}</s>` : ''}
          </div>
          <div class="bolinhas">${p.cores.map(c => `<i style="background:${c.hex}" title="${esc(c.nome)}"></i>`).join('')}</div>
        </div>
      </article>`;
    }).join('');
  }

  /* ---------- Modal do produto ---------- */
  function abrirProduto(id) {
    const p = PRODUTOS.find(x => x.id === id);
    if (!p) return;
    Object.assign(sel, { produto: p, cor: 0, tamanho: null, qtd: 1 });
    $('#modalCategoria').textContent = p.categoria;
    $('#modalTitulo').textContent = p.nome;
    $('#modalPreco').innerHTML = brl(p.preco) + (p.precoAntigo ? ` <s>${brl(p.precoAntigo)}</s>` : '');
    $('#modalDescricao').textContent = p.descricao;
    $('#avisoTamanho').hidden = true;
    renderModal();
    abrir($('#modal'));
  }

  function renderModal() {
    const p = sel.produto, cor = p.cores[sel.cor];
    $('#modalImagem').innerHTML = imagemProduto(p, cor);
    $('#modalCorNome').textContent = cor.nome;
    $('#modalCores').innerHTML = p.cores.map((c, i) =>
      `<button class="cor${i === sel.cor ? ' ativo' : ''}" style="--c:${c.hex}" data-cor="${i}" aria-label="${esc(c.nome)}" aria-pressed="${i === sel.cor}"></button>`).join('');
    $('#modalTamanhos').innerHTML = TAMANHOS.map(t =>
      `<button class="tam${t === sel.tamanho ? ' ativo' : ''}" data-tam="${t}" aria-pressed="${t === sel.tamanho}">${t}</button>`).join('');
    $('#qtdValor').textContent = sel.qtd;
  }

  function adicionar() {
    if (!sel.tamanho) { $('#avisoTamanho').hidden = false; return; }
    const p = sel.produto, cor = p.cores[sel.cor];
    const chave = `${p.id}|${cor.nome}|${sel.tamanho}`;
    const item = carrinho.find(i => i.chave === chave);
    if (item) item.qtd += sel.qtd;
    else carrinho.push({ chave, id: p.id, cor: sel.cor, tamanho: sel.tamanho, qtd: sel.qtd });
    salvar();
    renderCarrinho();
    fechar($('#modal'));
    toast(`${p.nome} (${sel.tamanho}) adicionada ao carrinho`);
    pulsar();
  }

  /* ---------- Carrinho ---------- */
  function totais() {
    const subtotal = carrinho.reduce((s, i) => {
      const p = PRODUTOS.find(x => x.id === i.id);
      return p ? s + p.preco * i.qtd : s;
    }, 0);
    const frete = subtotal === 0 || subtotal >= CONFIG.freteGratisAcima ? 0 : CONFIG.freteFixo;
    return { subtotal, frete, total: subtotal + frete };
  }

  function renderCarrinho() {
    carrinho = carrinho.filter(i => PRODUTOS.some(p => p.id === i.id));
    const qtd = carrinho.reduce((s, i) => s + i.qtd, 0);
    $('#contador').textContent = qtd;
    $('#contador').classList.toggle('visivel', qtd > 0);

    const { subtotal, frete, total } = totais();
    const falta = CONFIG.freteGratisAcima - subtotal;
    const pct = Math.min(100, (subtotal / CONFIG.freteGratisAcima) * 100);
    $('#freteBarra').innerHTML = subtotal === 0 ? '' :
      `<p>${falta > 0 ? `Faltam <strong>${brl(falta)}</strong> para frete grátis` : '<strong>Você ganhou frete grátis!</strong>'}</p>
       <div class="barra"><span style="width:${pct}%"></span></div>`;

    if (!carrinho.length) {
      $('#itens').innerHTML = `<li class="vazio"><p>Seu carrinho está vazio.</p><button class="btn" data-fechar-carrinho>Continuar comprando</button></li>`;
      $('#carrinhoRodape').hidden = true;
      return;
    }
    $('#carrinhoRodape').hidden = false;
    $('#itens').innerHTML = carrinho.map(i => {
      const p = PRODUTOS.find(x => x.id === i.id), cor = p.cores[i.cor] || p.cores[0];
      return `<li class="item" data-chave="${esc(i.chave)}">
        <div class="item__img">${imagemProduto(p, cor)}</div>
        <div class="item__info">
          <strong>${esc(p.nome)}</strong>
          <span>${esc(cor.nome)} · ${i.tamanho}</span>
          <div class="qtd qtd--peq">
            <button data-acao="menos" aria-label="Diminuir">−</button>
            <span>${i.qtd}</span>
            <button data-acao="mais" aria-label="Aumentar">+</button>
          </div>
        </div>
        <div class="item__dir">
          <span>${brl(p.preco * i.qtd)}</span>
          <button class="remover" data-acao="remover">Remover</button>
        </div>
      </li>`;
    }).join('');
    $('#subtotal').textContent = brl(subtotal);
    $('#frete').textContent = frete === 0 ? 'Grátis' : brl(frete);
    $('#total').textContent = brl(total);
    $('#pix').textContent = `ou ${brl(total * (1 - CONFIG.descontoPix))} no Pix · 3x de ${brl(total / 3)}`;
  }

  function alterarItem(chave, acao) {
    const item = carrinho.find(i => i.chave === chave);
    if (!item) return;
    if (acao === 'mais') item.qtd++;
    if (acao === 'menos') item.qtd--;
    if (acao === 'remover' || item.qtd <= 0) carrinho = carrinho.filter(i => i !== item);
    salvar();
    renderCarrinho();
  }

  /* ---------- Checkout ---------- */
  function totalCheckout() {
    const { total } = totais();
    const pix = $('#formCheckout').pagamento.value === 'Pix';
    return pix ? total * (1 - CONFIG.descontoPix) : total;
  }

  function enviarPedido(ev) {
    ev.preventDefault();
    const f = ev.target;
    const { subtotal, frete } = totais();
    const linhas = carrinho.map(i => {
      const p = PRODUTOS.find(x => x.id === i.id), cor = p.cores[i.cor] || p.cores[0];
      return `• ${i.qtd}x ${p.nome} — ${cor.nome} / ${i.tamanho} — ${brl(p.preco * i.qtd)}`;
    });
    const msg = [
      '*Novo pedido — Trama Camisetas*', '',
      ...linhas, '',
      `Subtotal: ${brl(subtotal)}`,
      `Frete: ${frete === 0 ? 'Grátis' : brl(frete)}`,
      `Pagamento: ${f.pagamento.value}`,
      `*Total: ${brl(totalCheckout())}*`, '',
      `Nome: ${f.nome.value}`,
      `WhatsApp: ${f.telefone.value}`,
      `Endereço: ${f.endereco.value}, ${f.numero.value} — ${f.cidade.value}/${f.uf.value.toUpperCase()} — CEP ${f.cep.value}`,
    ].join('\n');
    window.open(`https://wa.me/${CONFIG.whatsapp}?text=${encodeURIComponent(msg)}`, '_blank', 'noopener');
    carrinho = [];
    salvar();
    renderCarrinho();
    fechar($('#checkout'));
    toast('Pedido enviado! Confirme no WhatsApp.');
  }

  async function buscarCep(cep) {
    const d = cep.replace(/\D/g, '');
    if (d.length !== 8) return;
    try {
      const r = await fetch(`https://viacep.com.br/ws/${d}/json/`);
      const j = await r.json();
      if (j.erro) return;
      const f = $('#formCheckout');
      if (!f.endereco.value) f.endereco.value = [j.logradouro, j.bairro].filter(Boolean).join(', ');
      f.cidade.value = j.localidade || f.cidade.value;
      f.uf.value = j.uf || f.uf.value;
      f.numero.focus();
    } catch { /* offline: usuário preenche manualmente */ }
  }

  /* ---------- Utilidades de UI ---------- */
  let ultimoFoco = null;
  function abrir(el) { ultimoFoco = document.activeElement; el.hidden = false; document.body.classList.add('travado'); requestAnimationFrame(() => el.classList.add('aberto')); $('.fechar', el)?.focus(); }
  function fechar(el) { el.classList.remove('aberto'); el.hidden = true; liberar(); ultimoFoco?.focus?.(); }
  function abrirCarrinho() { ultimoFoco = document.activeElement; const c = $('#carrinho'); c.classList.add('aberto'); c.setAttribute('aria-hidden', 'false'); document.body.classList.add('travado'); $('.fechar', c).focus(); }
  function fecharCarrinho() { const c = $('#carrinho'); c.classList.remove('aberto'); c.setAttribute('aria-hidden', 'true'); liberar(); }
  function liberar() {
    if ($('#modal').hidden && $('#checkout').hidden && !$('#carrinho').classList.contains('aberto')) document.body.classList.remove('travado');
  }
  let toastT;
  function toast(t) { const el = $('#toast'); el.textContent = t; el.classList.add('visivel'); clearTimeout(toastT); toastT = setTimeout(() => el.classList.remove('visivel'), 2600); }
  function pulsar() { const b = $('#abrirCarrinho'); b.classList.remove('pulso'); void b.offsetWidth; b.classList.add('pulso'); }

  /* ---------- Eventos ---------- */
  $('#filtros').addEventListener('click', e => {
    const b = e.target.closest('[data-cat]'); if (!b) return;
    filtro = b.dataset.cat; renderFiltros(); renderGrade();
  });
  $('#ordenar').addEventListener('change', e => { ordem = e.target.value; renderGrade(); });
  $('#grade').addEventListener('click', e => { const c = e.target.closest('.card'); if (c) abrirProduto(c.dataset.id); });
  $('#grade').addEventListener('keydown', e => { if (e.key === 'Enter' && e.target.matches('.card')) abrirProduto(e.target.dataset.id); });

  $('#modalCores').addEventListener('click', e => { const b = e.target.closest('[data-cor]'); if (b) { sel.cor = +b.dataset.cor; renderModal(); } });
  $('#modalTamanhos').addEventListener('click', e => { const b = e.target.closest('[data-tam]'); if (b) { sel.tamanho = b.dataset.tam; $('#avisoTamanho').hidden = true; renderModal(); } });
  $('#qtdMenos').addEventListener('click', () => { sel.qtd = Math.max(1, sel.qtd - 1); renderModal(); });
  $('#qtdMais').addEventListener('click', () => { sel.qtd = Math.min(20, sel.qtd + 1); renderModal(); });
  $('#adicionar').addEventListener('click', adicionar);

  $('#abrirCarrinho').addEventListener('click', abrirCarrinho);
  $('#itens').addEventListener('click', e => {
    const b = e.target.closest('[data-acao]'); if (!b) return;
    alterarItem(b.closest('.item').dataset.chave, b.dataset.acao);
  });
  $('#irCheckout').addEventListener('click', () => {
    fecharCarrinho();
    $('#checkoutTotal').textContent = brl(totalCheckout());
    abrir($('#checkout'));
  });
  $('#formCheckout').addEventListener('change', e => { if (e.target.name === 'pagamento') $('#checkoutTotal').textContent = brl(totalCheckout()); });
  $('#formCheckout').addEventListener('submit', enviarPedido);
  $('#formCheckout').cep.addEventListener('input', e => {
    let v = e.target.value.replace(/\D/g, '').slice(0, 8);
    if (v.length > 5) v = v.slice(0, 5) + '-' + v.slice(5);
    e.target.value = v;
    if (v.length === 9) buscarCep(v);
  });

  document.addEventListener('click', e => {
    if (e.target.closest('[data-fechar]')) fechar($('#modal'));
    if (e.target.closest('[data-fechar-checkout]')) fechar($('#checkout'));
    if (e.target.closest('[data-fechar-carrinho]')) fecharCarrinho();
  });
  document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    if (!$('#modal').hidden) fechar($('#modal'));
    else if (!$('#checkout').hidden) fechar($('#checkout'));
    else fecharCarrinho();
  });

  /* ---------- Início ---------- */
  const heroProd = PRODUTOS.find(p => p.id === 'sol-de-quinta') || PRODUTOS[0];
  $('#heroArte').innerHTML = camisetaSVG(heroProd, heroProd.cores[0]);
  renderFiltros();
  renderGrade();
  renderCarrinho();
})();
