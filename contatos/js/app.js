/* Gravitas · Contatos com Prefeituras — interface */
(function () {
  "use strict";

  /* ============================ constantes ============================ */
  const STATUS = {
    nao_contatado:     { label: "Não contatado",     cor: "#9AA3AE" },
    contato_iniciado:  { label: "Contato iniciado",  cor: "#2E75B6" },
    reuniao_realizada: { label: "Reunião realizada", cor: "#E89C2A" },
    aliado:            { label: "Aliado",            cor: "#2F9E6B" },
    resistente:        { label: "Resistente",        cor: "#C0392B" },
    sem_resposta:      { label: "Sem resposta",      cor: "#B58A00" },
  };
  const TIPOS = { reuniao: ["Reunião", "🤝"], ligacao: ["Ligação", "📞"], whatsapp: ["WhatsApp", "💬"], email: ["E-mail", "✉️"], visita: ["Visita", "📍"], evento: ["Evento", "🎤"], nota: ["Observação", "📝"] };
  const RESULTADOS = { positivo: "Positivo", neutro: "Neutro", negativo: "Negativo", sem_resposta: "Sem resposta" };
  const GRUPOS = [["gabinete", "Gabinete e controle"], ["nucleo", "Núcleo prioritário"], ["secretaria", "Demais secretarias"], ["subprefeitura", "Subprefeituras"], ["camara", "Câmara de Vereadores"], ["canal", "Canais institucionais"], ["outro", "Outros"]];
  const CIDADE_STATUS = { prospeccao: "Prospecção", em_contato: "Em contato", proposta: "Proposta apresentada", contratada: "Contratada", pausada: "Pausada" };
  const AGENDA_STATUS = { agendado: "Agendado", realizado: "Realizado", cancelado: "Cancelado" };

  /* ============================ estado ============================ */
  const S = { store: null, me: null, usuarios: [], cidades: [], pessoas: [], interacoes: [], agendas: [], online: [], filtro: { busca: "", status: "", resp: "", prio: false }, agFiltro: { user: "", cidade: "", qual: "proximos" }, atFiltro: { user: "", cidade: "" }, drawer: null, modal: null, menu: false };
  const app = document.getElementById("app");
  const esc = (s) => String(s == null ? "" : s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const byId = (arr, id) => arr.find((x) => x.id === id);
  const user = (id) => byId(S.usuarios, id);
  const cidade = (id) => byId(S.cidades, id);
  const pessoa = (id) => byId(S.pessoas, id);

  /* ============================ formatação ============================ */
  const fmtData = (d) => d ? new Date(d).toLocaleDateString("pt-BR", { day: "2-digit", month: "short" }).replace(".", "") : "";
  const fmtDataHora = (d) => d ? new Date(d).toLocaleString("pt-BR", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" }).replace(".", "") : "";
  const fmtHora = (d) => new Date(d).toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" });
  function relTime(d) {
    if (!d) return "";
    const diff = (Date.now() - new Date(d).getTime()) / 1000;
    if (diff < 60) return "agora";
    if (diff < 3600) return `há ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `há ${Math.floor(diff / 3600)} h`;
    const days = Math.floor(diff / 86400);
    if (days === 1) return "ontem";
    if (days < 30) return `há ${days} dias`;
    return fmtData(d);
  }
  function diaLabel(d) {
    const x = new Date(d); const hoje = new Date(); hoje.setHours(0, 0, 0, 0);
    const dd = new Date(x); dd.setHours(0, 0, 0, 0);
    const diff = Math.round((dd - hoje) / 86400000);
    const base = x.toLocaleDateString("pt-BR", { weekday: "short", day: "2-digit", month: "short" }).replace(/\./g, "");
    if (diff === 0) return "Hoje · " + base;
    if (diff === 1) return "Amanhã · " + base;
    if (diff === -1) return "Ontem · " + base;
    return base;
  }
  const iniciais = (nome) => (nome || "?").split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]).join("").toUpperCase();
  const toLocalInput = (d) => { const x = d ? new Date(d) : new Date(); x.setMinutes(x.getMinutes() - x.getTimezoneOffset()); return x.toISOString().slice(0, 16); };
  const slug = (s) => s.normalize("NFKD").replace(/[̀-ͯ]/g, "").toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");

  /* ============================ pedaços de HTML ============================ */
  const av = (u, cls = "") => u ? `<span class="av ${cls}" style="background:${esc(u.cor || "#5B6470")}" title="${esc(u.nome)}">${esc(iniciais(u.nome))}</span>` : "";
  const pill = (st) => { const m = STATUS[st] || STATUS.nao_contatado; return `<span class="pill st" style="background:${m.cor}">${m.label}</span>`; };
  const fotoOuIni = (p) => p.foto ? `<img class="foto" src="${esc(p.foto)}" alt="" loading="lazy" onerror="this.outerHTML='<span class=&quot;ini&quot;>${esc(iniciais(p.nome))}</span>'">` : `<span class="ini">${esc(iniciais(p.nome))}</span>`;
  const opt = (obj, sel, vazio) => (vazio != null ? `<option value="">${esc(vazio)}</option>` : "") + Object.entries(obj).map(([k, v]) => `<option value="${k}" ${k === sel ? "selected" : ""}>${esc(Array.isArray(v) ? v[0] : (v.label || v))}</option>`).join("");
  const optUsers = (sel, vazio) => (vazio != null ? `<option value="">${esc(vazio)}</option>` : "") + S.usuarios.map((u) => `<option value="${u.id}" ${u.id === sel ? "selected" : ""}>${esc(u.nome)}</option>`).join("");
  const optCidades = (sel, vazio) => (vazio != null ? `<option value="">${esc(vazio)}</option>` : "") + S.cidades.map((c) => `<option value="${c.id}" ${c.id === sel ? "selected" : ""}>${esc(c.nome)}</option>`).join("");
  function optPessoas(cid, sel) {
    const ps = S.pessoas.filter((p) => p.cidade_id === cid);
    return `<option value="">— nenhuma pessoa específica —</option>` + GRUPOS.map(([g, lab]) => {
      const items = ps.filter((p) => p.grupo === g);
      return items.length ? `<optgroup label="${esc(lab)}">` + items.map((p) => `<option value="${p.id}" ${p.id === sel ? "selected" : ""}>${esc(p.nome)}${p.cargo ? " · " + esc(p.cargo) : ""}</option>`).join("") + `</optgroup>` : "";
    }).join("");
  }

  /* ============================ consultas ============================ */
  const interacoesDe = (pid) => S.interacoes.filter((i) => i.pessoa_id === pid).sort((a, b) => new Date(b.data) - new Date(a.data));
  const ultimaInteracao = (pid) => interacoesDe(pid)[0];
  const quemFalou = (pid) => { const ids = [...new Set(interacoesDe(pid).map((i) => i.usuario_id))]; return ids.map(user).filter(Boolean); };
  const agendasDe = (pid) => S.agendas.filter((a) => (a.pessoa_ids || []).includes(pid) && a.status === "agendado" && new Date(a.inicio) > Date.now() - 3600000).sort((a, b) => new Date(a.inicio) - new Date(b.inicio));
  const agendasCidade = (cid) => S.agendas.filter((a) => a.cidade_id === cid && a.status === "agendado" && new Date(a.inicio) > Date.now() - 3600000).sort((a, b) => new Date(a.inicio) - new Date(b.inicio));
  function resumoCidade(cid) {
    const ps = S.pessoas.filter((p) => p.cidade_id === cid && p.grupo !== "canal");
    const cont = {}; Object.keys(STATUS).forEach((k) => (cont[k] = 0));
    ps.forEach((p) => (cont[p.status || "nao_contatado"] = (cont[p.status || "nao_contatado"] || 0) + 1));
    const ult = S.interacoes.filter((i) => i.cidade_id === cid).sort((a, b) => new Date(b.data) - new Date(a.data))[0];
    const ativos = [...new Set(S.interacoes.filter((i) => i.cidade_id === cid).map((i) => i.usuario_id))].map(user).filter(Boolean);
    return { total: ps.length, cont, contatados: ps.length - cont.nao_contatado, ult, ativos, proximas: agendasCidade(cid) };
  }

  /* ============================ topo ============================ */
  function topbar(route) {
    const on = (r) => (route === r ? "on" : "");
    const outros = S.online.filter((o) => o.id !== S.me.id);
    return `
    ${S.store.mode === "demo" ? `<div class="demo-banner"><b>Modo demonstração.</b> Os dados ficam só neste navegador e não são compartilhados. Para o uso real, configure o Supabase em <code>js/config.js</code>.</div>` : ""}
    <header class="topbar"><div class="topbar-in">
      <a class="brand" href="#/"><span class="brand-mark">G</span><span><div class="brand-name">GRAVITAS</div><div class="brand-sub">Contatos com Prefeituras</div></span></a>
      <nav class="nav"><a href="#/" class="${on("home")}">Cidades</a><a href="#/agenda" class="${on("agenda")}">Agenda</a><a href="#/atividade" class="${on("atividade")}">Atividade</a></nav>
      <div class="top-right">
        <div class="online" title="${esc(outros.map((o) => o.nome).join(", ") || "Só você online")}">${outros.slice(0, 5).map((o) => av(o, "sm")).join("")}<span class="online-label">${S.online.length <= 1 ? "só você online" : outros.length === 1 ? `${esc(outros[0].nome)} online` : `${S.online.length} online`}</span></div>
        <button class="me-btn" data-action="menu">${av(S.me)}<span class="small">${esc(S.me.nome)}</span></button>
        ${S.menu ? `<div class="menu"><div class="menu-head"><b>${esc(S.me.nome)}</b><div class="small muted">${esc(S.me.email || "")}</div></div><button data-action="perfil">Trocar meu nome</button>${S.store.mode === "supabase" ? `<button data-action="senha">Trocar minha senha</button>` : ""}<button data-action="sair">Sair</button></div>` : ""}
      </div>
    </div></header>`;
  }

  /* ============================ visão geral ============================ */
  function viewHome() {
    const pessoas = S.pessoas.filter((p) => p.grupo !== "canal");
    const contatadas = pessoas.filter((p) => p.status && p.status !== "nao_contatado").length;
    const reunioes = pessoas.filter((p) => ["reuniao_realizada", "aliado"].includes(p.status)).length;
    const sete = S.agendas.filter((a) => a.status === "agendado" && new Date(a.inicio) >= Date.now() - 3600000 && new Date(a.inicio) < Date.now() + 7 * 86400000);
    const proximas = S.agendas.filter((a) => a.status === "agendado" && new Date(a.inicio) >= Date.now() - 3600000).sort((a, b) => new Date(a.inicio) - new Date(b.inicio)).slice(0, 6);
    const recentes = S.interacoes.slice().sort((a, b) => new Date(b.data) - new Date(a.data)).slice(0, 6);
    return `<div class="wrap">
      <div class="page-head"><div><h1>Cidades</h1><div class="lead">Com quem já falamos, quem cuida de cada uma e o que vem pela frente.</div></div>
        <div class="actions"><button class="btn" data-action="nova-cidade">+ Nova cidade</button><button class="btn" data-action="agendar">+ Agendar</button><button class="btn btn-primary" data-action="registrar">+ Registrar contato</button></div></div>
      <div class="stats">
        <div class="stat"><div class="n">${S.cidades.length}</div><div class="t">cidades em trabalho</div></div>
        <div class="stat"><div class="n">${contatadas}<span class="muted" style="font-size:16px;font-weight:500"> / ${pessoas.length}</span></div><div class="t">pessoas já contatadas</div></div>
        <div class="stat"><div class="n">${reunioes}</div><div class="t">com reunião feita ou aliadas</div></div>
        <div class="stat"><div class="n">${sete.length}</div><div class="t">compromissos nos próximos 7 dias</div></div>
      </div>
      <div class="layout">
        <div class="grid grid-2">
          ${S.cidades.slice().sort((a, b) => (a.ordem || 0) - (b.ordem || 0) || a.nome.localeCompare(b.nome)).map((c) => {
            const r = resumoCidade(c.id);
            const pct = r.total ? Math.round(100 * r.contatados / r.total) : 0;
            return `<a class="card city-card" href="#/cidade/${esc(c.id)}">
              <div class="row" style="justify-content:space-between"><h2>${esc(c.nome)}<span class="muted" style="font-size:14px;font-weight:500"> · ${esc(c.uf || "RS")}</span></h2><span class="pill">${esc(CIDADE_STATUS[c.status] || c.status)}</span></div>
              <div class="city-meta">${c.prefeito ? "Prefeito: " + esc(c.prefeito) : ""}${c.populacao ? " · " + c.populacao.toLocaleString("pt-BR") + " hab." : ""}</div>
              <div class="city-prog"><div class="row" style="justify-content:space-between"><span class="small"><b>${r.contatados}</b> de ${r.total} pessoas contatadas</span><span class="small muted">${pct}%</span></div>
                <div class="progress" style="margin-top:6px">${Object.entries(r.cont).filter(([k, n]) => k !== "nao_contatado" && n).map(([k, n]) => `<span style="width:${100 * n / r.total}%;background:${STATUS[k].cor}" title="${STATUS[k].label}: ${n}"></span>`).join("")}</div>
                <div class="city-legend" style="margin-top:6px">${Object.entries(r.cont).filter(([k, n]) => k !== "nao_contatado" && n).map(([k, n]) => `<span><i style="background:${STATUS[k].cor}"></i>${STATUS[k].label} ${n}</span>`).join("") || `<span>Ninguém contatado ainda</span>`}</div></div>
              <div class="city-foot"><span class="muted">${r.proximas[0] ? `Próximo: <b>${esc(fmtDataHora(r.proximas[0].inicio))}</b> · ${esc(r.proximas[0].titulo)}` : "Sem compromisso marcado"}</span><span class="avs">${r.ativos.map((u) => av(u, "sm")).join("")}</span></div>
              <div class="small muted" style="margin-top:6px">${r.ult ? `Última atividade ${relTime(r.ult.data)} por ${esc((user(r.ult.usuario_id) || {}).nome || "?")}` : "Sem atividade registrada"}</div>
            </a>`;
          }).join("") || `<div class="empty">Nenhuma cidade ainda. Clique em “Nova cidade”.</div>`}
        </div>
        <div>
          <div class="card"><div class="section-head"><h3>Próximos compromissos</h3><a href="#/agenda" class="small">ver agenda</a></div>
            <ul class="mini-list">${proximas.map((a) => `<li><div class="when">${esc(fmtDataHora(a.inicio))} · ${esc((cidade(a.cidade_id) || {}).nome || "")}</div><div><b>${esc(a.titulo)}</b></div><div class="row" style="gap:6px;margin-top:4px">${av(user(a.usuario_id), "sm")}<span class="small muted">${esc((user(a.usuario_id) || {}).nome || "")}${a.local ? " · " + esc(a.local) : ""}</span></div></li>`).join("") || `<li class="muted">Nada marcado.</li>`}</ul></div>
          <div class="card" style="margin-top:16px"><div class="section-head"><h3>Atividade recente</h3><a href="#/atividade" class="small">ver tudo</a></div>
            <ul class="mini-list">${recentes.map((i) => { const p = pessoa(i.pessoa_id); return `<li><div class="when">${relTime(i.data)} · ${esc((user(i.usuario_id) || {}).nome || "")} · ${esc((cidade(i.cidade_id) || {}).nome || "")}</div><div>${TIPOS[i.tipo] ? TIPOS[i.tipo][1] + " " : ""}${p ? `<a href="#/cidade/${esc(i.cidade_id)}/pessoa/${esc(p.id)}"><b>${esc(p.nome)}</b></a>: ` : ""}${esc(i.resumo)}</div></li>`; }).join("") || `<li class="muted">Nenhum contato registrado.</li>`}</ul></div>
        </div>
      </div>
    </div>`;
  }

  /* ============================ cidade ============================ */
  function viewCidade(cid) {
    const c = cidade(cid);
    if (!c) return `<div class="wrap"><div class="empty">Cidade não encontrada. <a href="#/">Voltar</a></div></div>`;
    const f = S.filtro;
    const busca = f.busca.trim().toLowerCase();
    let ps = S.pessoas.filter((p) => p.cidade_id === cid);
    const filtrada = ps.filter((p) => (!busca || [p.nome, p.cargo, p.orgao, p.partido].join(" ").toLowerCase().includes(busca)) && (!f.status || (p.status || "nao_contatado") === f.status) && (!f.resp || p.responsavel_id === f.resp) && (!f.prio || p.prioritario));
    const ids = new Set(filtrada.map((p) => p.id));
    const titulares = filtrada.filter((p) => !p.vinculado_a || !ids.has(p.vinculado_a));
    const r = resumoCidade(cid);
    const prox = r.proximas.slice(0, 4);
    const card = (p) => {
      const adj = ps.filter((x) => x.vinculado_a === p.id && ids.has(x.id));
      const ult = ultimaInteracao(p.id); const resp = user(p.responsavel_id); const falou = quemFalou(p.id);
      return `<div class="person ${p.prioritario ? "prio" : ""}" data-action="abrir" data-id="${esc(p.id)}">
        ${p.prioritario ? `<span class="star" title="Prioritário">★</span>` : ""}
        <div class="top">${fotoOuIni(p)}<div><div class="nm">${esc(p.nome)}</div><div class="cg">${esc(p.cargo || "")}${p.cargo && p.orgao && p.grupo !== "camara" ? " · " : ""}${p.grupo !== "camara" ? esc(p.orgao || "") : ""}${p.partido ? ` <span class="chip party">${esc(p.partido)}</span>` : ""}</div></div></div>
        <div class="bottom">${pill(p.status)}<span class="avs">${falou.map((u) => av(u, "sm")).join("")}${resp && !falou.some((u) => u.id === resp.id) ? av(resp, "sm") : ""}</span></div>
        <div class="last">${ult ? `${TIPOS[ult.tipo] ? TIPOS[ult.tipo][1] + " " : ""}${relTime(ult.data)} por ${esc((user(ult.usuario_id) || {}).nome || "?")}` : resp ? `Responsável: ${esc(resp.nome)}` : "Sem contato registrado"}</div>
        ${adj.length ? `<div class="adj">${adj.map((a) => `<span><span class="st-dot" style="background:${(STATUS[a.status] || STATUS.nao_contatado).cor}"></span><a href="#" data-action="abrir" data-id="${esc(a.id)}">${esc(a.nome)}</a> · ${esc(a.cargo || "")}</span>`).join("")}</div>` : ""}
      </div>`;
    };
    const canais = titulares.filter((p) => p.grupo === "canal");
    return `<div class="wrap">
      <a class="back" href="#/">← Cidades</a>
      <div class="card city-head">
        <div class="info"><div class="row" style="gap:12px"><h1>${esc(c.nome)}<span class="muted" style="font-size:16px;font-weight:500"> · ${esc(c.uf || "RS")}</span></h1>
          <select class="input" style="width:auto" data-action="cidade-status" data-id="${esc(c.id)}">${opt(CIDADE_STATUS, c.status)}</select></div>
          <div class="facts">${c.prefeito ? `<span>Prefeito <b>${esc(c.prefeito)}</b></span>` : ""}${c.vice ? `<span>Vice <b>${esc(c.vice)}</b></span>` : ""}${c.presidente_camara ? `<span>Câmara <b>${esc(c.presidente_camara)}</b>${c.cadeiras_camara ? ` · ${c.cadeiras_camara} cadeiras` : ""}</span>` : ""}${c.populacao ? `<span><b>${c.populacao.toLocaleString("pt-BR")}</b> hab.</span>` : ""}</div>
          <div class="resumo"><span class="label">Resumo e observações da cidade</span><div class="edit" data-action="editar-resumo" data-id="${esc(c.id)}" title="Clique para editar">${c.resumo ? esc(c.resumo) : `<span class="muted">Clique para escrever um resumo da situação nesta cidade…</span>`}</div></div>
        </div>
        <div class="actions"><button class="btn" data-action="nova-pessoa" data-cidade="${esc(c.id)}">+ Pessoa</button><button class="btn" data-action="agendar" data-cidade="${esc(c.id)}">+ Agendar</button><button class="btn btn-primary" data-action="registrar" data-cidade="${esc(c.id)}">+ Registrar contato</button></div>
      </div>
      <div class="city-side">
        <div class="card"><div class="section-head"><h3>Próximos compromissos aqui</h3><a href="#/agenda" class="small">agenda</a></div>
          <ul class="mini-list">${prox.map((a) => `<li><div class="when">${esc(fmtDataHora(a.inicio))}${a.local ? " · " + esc(a.local) : ""}</div><div><b>${esc(a.titulo)}</b></div><div class="row" style="gap:6px;margin-top:4px">${av(user(a.usuario_id), "sm")}<span class="small muted">${esc((user(a.usuario_id) || {}).nome || "")}${(a.pessoa_ids || []).length ? " com " + esc((a.pessoa_ids || []).map((id) => (pessoa(id) || {}).nome).filter(Boolean).join(", ")) : ""}</span></div></li>`).join("") || `<li class="muted">Nada marcado nesta cidade.</li>`}</ul></div>
        <div class="card"><div class="section-head"><h3>Últimos contatos aqui</h3><a href="#/atividade" class="small">atividade</a></div>
          <ul class="mini-list">${S.interacoes.filter((i) => i.cidade_id === cid).sort((a, b) => new Date(b.data) - new Date(a.data)).slice(0, 4).map((i) => { const p = pessoa(i.pessoa_id); return `<li><div class="when">${relTime(i.data)} · ${esc((user(i.usuario_id) || {}).nome || "")}</div><div>${TIPOS[i.tipo] ? TIPOS[i.tipo][1] + " " : ""}${p ? `<a href="#" data-action="abrir" data-id="${esc(p.id)}"><b>${esc(p.nome)}</b></a>: ` : ""}${esc(i.resumo)}</div></li>`; }).join("") || `<li class="muted">Nenhum contato registrado.</li>`}</ul></div>
      </div>
      <div style="margin-top:20px">
        <div class="row" style="justify-content:space-between"><div class="small"><b>${r.contatados}</b> de ${r.total} pessoas contatadas</div><div class="city-legend">${Object.entries(r.cont).filter(([k, n]) => n).map(([k, n]) => `<span><i style="background:${STATUS[k].cor}"></i>${STATUS[k].label} ${n}</span>`).join("")}</div></div>
        <div class="progress" style="margin-top:6px">${Object.entries(r.cont).filter(([k, n]) => k !== "nao_contatado" && n).map(([k, n]) => `<span style="width:${100 * n / r.total}%;background:${STATUS[k].cor}"></span>`).join("")}</div>
        <div class="filters">
          <input class="input busca" placeholder="Buscar nome, cargo, órgão…" value="${esc(f.busca)}" data-filtro="busca">
          <select class="input" data-filtro="status">${opt(STATUS, f.status, "Todas as situações")}</select>
          <select class="input" data-filtro="resp">${optUsers(f.resp, "Qualquer responsável")}</select>
          <label class="check"><input type="checkbox" data-filtro="prio" ${f.prio ? "checked" : ""}> Só prioritários</label>
        </div>
        ${GRUPOS.filter(([g]) => g !== "canal").map(([g, lab]) => { const items = titulares.filter((p) => p.grupo === g); return items.length ? `<div class="section"><div class="section-head"><h2>${esc(lab)}</h2><span class="count">${items.length}</span></div><div class="people">${items.map(card).join("")}</div></div>` : ""; }).join("")}
        ${canais.length ? `<div class="section"><div class="section-head"><h2>Canais institucionais</h2><span class="count">${canais.length}</span></div><div class="canais">${canais.map((p) => `<div class="canal" data-action="abrir" data-id="${esc(p.id)}"><b>${esc(p.nome)}</b><span class="nowrap">${esc(p.telefone || "")}</span><span class="c">${esc(p.cargo || "")}</span><span class="c">${esc(p.email || "")}</span></div>`).join("")}</div></div>` : ""}
        ${!filtrada.length ? `<div class="empty" style="margin-top:20px">Nenhuma pessoa com esses filtros.</div>` : ""}
      </div>
    </div>`;
  }

  /* ============================ drawer da pessoa ============================ */
  function drawerPessoa(pid) {
    const p = pessoa(pid); if (!p) return "";
    const c = cidade(p.cidade_id); const chefe = pessoa(p.vinculado_a);
    const adj = S.pessoas.filter((x) => x.vinculado_a === p.id);
    const hist = interacoesDe(p.id); const falou = quemFalou(p.id); const ag = agendasDe(p.id);
    const link = (v, href) => v ? `<a href="${href}" target="_blank" rel="noopener">${esc(v)}</a>` : "";
    const tel = (v) => v ? v.split(/[·,;]|\be\b/).map((t) => t.trim()).filter((t) => /\d{4}/.test(t)).map((t) => `<a href="tel:${t.replace(/[^\d+]/g, "")}">${esc(t)}</a>`).join(" · ") || esc(v) : "";
    const wa = (v) => { const d = (v || "").replace(/\D/g, ""); return v ? (d.length >= 10 ? `<a href="https://wa.me/55${d.replace(/^55/, "")}" target="_blank" rel="noopener">${esc(v)}</a>` : esc(v)) : ""; };
    const redes = (v) => v ? v.split(/\s·\s|\s+\|\s+/).map((r) => { const m = r.match(/((?:www\.)?(?:instagram|facebook|linkedin|x|twitter|tiktok)\.com\/[^\s(]+)/i); return m ? `<a href="https://${m[1].replace(/^www\./, "")}" target="_blank" rel="noopener">${esc(r)}</a>` : esc(r); }).join(" · ") : "";
    return `<div class="overlay" data-action="fechar-drawer"></div><aside class="drawer" role="dialog" aria-label="${esc(p.nome)}">
      <div class="drawer-head">${fotoOuIni(p)}<div><h2>${esc(p.nome)}</h2><div class="small muted">${esc(p.cargo || "")}${p.cargo && p.orgao ? " · " : ""}${esc(p.orgao || "")}${p.partido ? ` <span class="chip party">${esc(p.partido)}</span>` : ""}${p.prioritario ? ` <span class="chip" style="background:#FFF1DD;color:#7A5200">★ prioritário</span>` : ""}</div><div class="tiny muted">${esc((c || {}).nome || "")}${chefe ? ` · vinculado a <a href="#" data-action="abrir" data-id="${esc(chefe.id)}">${esc(chefe.nome)}</a>` : ""}</div></div><button class="close" data-action="fechar-drawer" aria-label="Fechar">×</button></div>
      <div class="drawer-body">
        <div class="row"><button class="btn btn-primary" data-action="registrar" data-cidade="${esc(p.cidade_id)}" data-pessoa="${esc(p.id)}">+ Registrar contato</button><button class="btn" data-action="agendar" data-cidade="${esc(p.cidade_id)}" data-pessoa="${esc(p.id)}">+ Agendar</button><button class="btn btn-ghost btn-sm" data-action="editar-pessoa" data-id="${esc(p.id)}">Editar cadastro</button></div>
        <div class="card pad-s"><div class="form-grid">
          <div class="field"><label>Situação</label><select class="input" data-action="pessoa-status" data-id="${esc(p.id)}">${opt(STATUS, p.status || "nao_contatado")}</select></div>
          <div class="field"><label>Responsável na Gravitas</label><select class="input" data-action="pessoa-resp" data-id="${esc(p.id)}">${optUsers(p.responsavel_id, "— ninguém ainda —")}</select></div>
          <div class="field full"><label>Observações sobre a pessoa <span class="saved" id="notas-saved"></span></label><textarea class="input" data-action="pessoa-notas" data-id="${esc(p.id)}" placeholder="Como chegar, o que já sabemos, quem indicou, temas sensíveis…">${esc(p.notas || "")}</textarea><div class="tiny muted">Salva ao sair do campo. Fica visível para toda a equipe.</div></div>
        </div></div>
        <div class="card pad-s"><h3 style="margin-bottom:8px">Contatos institucionais</h3><div class="kv">
          ${p.telefone ? `<span class="k">Telefone</span><span>${tel(p.telefone)}</span>` : ""}${p.whatsapp ? `<span class="k">WhatsApp</span><span>${wa(p.whatsapp)}</span>` : ""}${p.email ? `<span class="k">E-mail</span><span>${p.email.split(/\s·\s/).map((e) => link(e.trim(), "mailto:" + e.trim())).join(" · ")}</span>` : ""}${p.rede_social ? `<span class="k">Redes</span><span>${redes(p.rede_social)}</span>` : ""}${p.endereco ? `<span class="k">Endereço</span><span>${esc(p.endereco)}</span>` : ""}${p.obs_fonte ? `<span class="k">Do levantamento</span><span class="small">${esc(p.obs_fonte)}</span>` : ""}
          ${!p.telefone && !p.email && !p.whatsapp ? `<span class="k">—</span><span class="muted">Sem contato publicado.</span>` : ""}
        </div>${adj.length ? `<div class="small" style="margin-top:10px"><span class="k muted">Equipe: </span>${adj.map((a) => `<a href="#" data-action="abrir" data-id="${esc(a.id)}">${esc(a.nome)}</a> (${esc(a.cargo || "")})`).join(", ")}</div>` : ""}</div>
        ${ag.length ? `<div class="card pad-s"><h3 style="margin-bottom:8px">Compromissos marcados</h3><ul class="mini-list">${ag.map((a) => `<li><div class="when">${esc(fmtDataHora(a.inicio))}${a.local ? " · " + esc(a.local) : ""}</div><b>${esc(a.titulo)}</b> <span class="small muted">· ${esc((user(a.usuario_id) || {}).nome || "")}</span></li>`).join("")}</ul></div>` : ""}
        <div class="card pad-s"><div class="section-head"><h3>Histórico</h3><div class="who">${falou.length ? falou.map((u) => `<span class="w">${av(u, "sm")}${esc(u.nome)}</span>`).join("") : `<span class="small muted">ninguém falou ainda</span>`}</div></div>
          <ul class="hist">${hist.map((i) => `<li><span class="ico">${TIPOS[i.tipo] ? TIPOS[i.tipo][1] : "•"}</span><div><div class="meta"><b style="color:var(--navy)">${esc(TIPOS[i.tipo] ? TIPOS[i.tipo][0] : i.tipo)}</b><span>${esc(fmtDataHora(i.data))}</span><span>${av(user(i.usuario_id), "sm")} ${esc((user(i.usuario_id) || {}).nome || "")}</span>${i.resultado ? `<span class="chip">${esc(RESULTADOS[i.resultado] || i.resultado)}</span>` : ""}${i.usuario_id === S.me.id ? `<button class="del" data-action="apagar-interacao" data-id="${esc(i.id)}">apagar</button>` : ""}</div><div class="txt">${esc(i.resumo)}</div>${i.proximo_passo ? `<div class="next">→ ${esc(i.proximo_passo)}</div>` : ""}</div></li>`).join("") || `<li class="muted small" style="display:block">Nenhum contato registrado com esta pessoa.</li>`}</ul></div>
      </div></aside>`;
  }

  /* ============================ agenda ============================ */
  function viewAgenda() {
    const f = S.agFiltro; const agora = Date.now() - 3600000;
    let ag = S.agendas.filter((a) => (!f.user || a.usuario_id === f.user) && (!f.cidade || a.cidade_id === f.cidade));
    if (f.qual === "proximos") ag = ag.filter((a) => a.status === "agendado" && new Date(a.inicio) >= agora);
    else if (f.qual === "passados") ag = ag.filter((a) => a.status === "realizado" || (a.status === "agendado" && new Date(a.inicio) < agora));
    else if (f.qual === "cancelados") ag = ag.filter((a) => a.status === "cancelado");
    ag.sort((a, b) => f.qual === "passados" ? new Date(b.inicio) - new Date(a.inicio) : new Date(a.inicio) - new Date(b.inicio));
    const dias = []; ag.forEach((a) => { const k = new Date(a.inicio).toDateString(); let d = dias.find((x) => x.k === k); if (!d) dias.push((d = { k, inicio: a.inicio, items: [] })); d.items.push(a); });
    const hojeK = new Date().toDateString();
    return `<div class="wrap">
      <div class="page-head"><div><h1>Agenda</h1><div class="lead">Quem vai onde, com quem, quando.</div></div><div class="actions"><button class="btn btn-primary" data-action="agendar">+ Agendar</button></div></div>
      <div class="filters" style="margin-top:0">
        <select class="input" data-agfiltro="qual">${opt({ proximos: "Próximos", passados: "Passados", cancelados: "Cancelados", todos: "Todos" }, f.qual)}</select>
        <select class="input" data-agfiltro="user">${optUsers(f.user, "Toda a equipe")}</select>
        <select class="input" data-agfiltro="cidade">${optCidades(f.cidade, "Todas as cidades")}</select>
      </div>
      ${dias.map((d) => `<div class="day"><h3 class="${d.k === hojeK ? "today" : ""}">${esc(diaLabel(d.inicio))}</h3>${d.items.map(evt).join("")}</div>`).join("") || `<div class="empty" style="margin-top:20px">Nenhum compromisso ${f.qual === "proximos" ? "marcado" : "aqui"}.</div>`}
    </div>`;
  }
  function evt(a) {
    const u = user(a.usuario_id); const c = cidade(a.cidade_id); const ps = (a.pessoa_ids || []).map(pessoa).filter(Boolean);
    return `<div class="evt ${a.status}">
      <div class="time">${esc(fmtHora(a.inicio))}${a.fim ? `<small>até ${esc(fmtHora(a.fim))}</small>` : ""}</div>
      <div><div class="t">${esc(a.titulo)}</div><div class="d">${av(u, "sm")} ${esc((u || {}).nome || "")}<span class="chip city">${esc((c || {}).nome || "")}</span>${a.local ? `<span>📍 ${esc(a.local)}</span>` : ""}${a.status !== "agendado" ? `<span class="chip">${esc(AGENDA_STATUS[a.status])}</span>` : ""}</div>
        ${ps.length ? `<div class="with">${ps.map((p) => `<a class="chip" href="#/cidade/${esc(p.cidade_id)}/pessoa/${esc(p.id)}" title="${esc(p.cargo || "")}">${esc(p.nome)}</a>`).join("")}</div>` : ""}${a.obs ? `<div class="obs">${esc(a.obs)}</div>` : ""}</div>
      <div class="ops">${a.status === "agendado" ? `<button class="btn btn-sm" data-action="agenda-realizado" data-id="${esc(a.id)}">✓ Realizado</button>` : ""}<button class="btn btn-sm btn-ghost" data-action="editar-agenda" data-id="${esc(a.id)}">Editar</button>${a.status === "agendado" ? `<button class="btn btn-sm btn-ghost btn-danger" data-action="agenda-cancelar" data-id="${esc(a.id)}">Cancelar</button>` : ""}</div>
    </div>`;
  }

  /* ============================ atividade ============================ */
  function viewAtividade() {
    const f = S.atFiltro;
    const items = S.interacoes.filter((i) => (!f.user || i.usuario_id === f.user) && (!f.cidade || i.cidade_id === f.cidade)).map((i) => ({ t: new Date(i.data), k: "i", i }))
      .concat(S.agendas.filter((a) => (!f.user || a.usuario_id === f.user) && (!f.cidade || a.cidade_id === f.cidade)).map((a) => ({ t: new Date(a.criado_em || a.inicio), k: "a", a })))
      .sort((x, y) => y.t - x.t).slice(0, 150);
    return `<div class="wrap">
      <div class="page-head"><div><h1>Atividade</h1><div class="lead">Tudo o que a equipe registrou, do mais recente ao mais antigo.</div></div></div>
      <div class="filters" style="margin-top:0"><select class="input" data-atfiltro="user">${optUsers(f.user, "Toda a equipe")}</select><select class="input" data-atfiltro="cidade">${optCidades(f.cidade, "Todas as cidades")}</select></div>
      <ul class="feed" style="margin-top:14px">${items.map((x) => {
        if (x.k === "i") { const i = x.i; const p = pessoa(i.pessoa_id); const u = user(i.usuario_id); return `<li>${av(u, "lg")}<div><div class="h"><b>${esc((u || {}).nome || "")}</b><span>${relTime(i.data)}</span><span class="chip city">${esc((cidade(i.cidade_id) || {}).nome || "")}</span><span>${TIPOS[i.tipo] ? TIPOS[i.tipo][1] + " " + TIPOS[i.tipo][0] : esc(i.tipo)}</span>${i.resultado ? `<span class="chip">${esc(RESULTADOS[i.resultado])}</span>` : ""}</div><div class="b">${p ? `<a href="#/cidade/${esc(i.cidade_id)}/pessoa/${esc(p.id)}"><b>${esc(p.nome)}</b></a>${p.cargo ? ` <span class="small muted">(${esc(p.cargo)})</span>` : ""}: ` : ""}${esc(i.resumo)}${i.proximo_passo ? `<div class="small" style="color:var(--blue)">→ ${esc(i.proximo_passo)}</div>` : ""}</div></div></li>`; }
        const a = x.a; const u = user(a.usuario_id); const ps = (a.pessoa_ids || []).map(pessoa).filter(Boolean);
        return `<li>${av(u, "lg")}<div><div class="h"><b>${esc((u || {}).nome || "")}</b><span>${relTime(a.criado_em || a.inicio)}</span><span class="chip city">${esc((cidade(a.cidade_id) || {}).nome || "")}</span><span>📅 ${a.status === "cancelado" ? "cancelou" : a.status === "realizado" ? "realizou" : "agendou"}</span></div><div class="b"><b>${esc(a.titulo)}</b> · ${esc(fmtDataHora(a.inicio))}${ps.length ? " com " + ps.map((p) => `<a href="#/cidade/${esc(p.cidade_id)}/pessoa/${esc(p.id)}">${esc(p.nome)}</a>`).join(", ") : ""}${a.local ? ` · ${esc(a.local)}` : ""}</div></div></li>`;
      }).join("") || `<li class="empty" style="display:block">Nenhuma atividade ainda.</li>`}</ul></div>`;
  }

  /* ============================ modais ============================ */
  function modal(titulo, corpo, botoes) {
    return `<div class="modal-wrap" data-action="fechar-modal"><div class="modal" data-stop="1"><div class="modal-head"><h2>${esc(titulo)}</h2><button class="close btn btn-ghost btn-sm" data-action="fechar-modal">✕</button></div><form id="mform" data-stop="1"><div class="modal-body">${corpo}<div class="err" id="merr"></div></div><div class="modal-foot">${botoes}</div></form></div></div>`;
  }
  function modalRegistrar(m) {
    const cid = m.cidade || (S.cidades[0] || {}).id; const p = pessoa(m.pessoa);
    const sugerido = p ? (p.status === "nao_contatado" ? "contato_iniciado" : p.status) : "";
    return modal("Registrar contato", `
      <div class="form-grid">
        <div class="field"><label>Cidade</label><select class="input" name="cidade_id" data-action="modal-cidade">${optCidades(cid)}</select></div>
        <div class="field"><label>Com quem</label><select class="input" name="pessoa_id">${optPessoas(cid, m.pessoa)}</select></div>
        <div class="field"><label>Tipo</label><select class="input" name="tipo">${opt(TIPOS, m.tipo || "ligacao")}</select></div>
        <div class="field"><label>Quando</label><input class="input" type="datetime-local" name="data" value="${toLocalInput()}" required></div>
        <div class="field full"><label>O que aconteceu</label><textarea class="input" name="resumo" required placeholder="Resumo da conversa, o que foi combinado, impressões…" autofocus></textarea></div>
        <div class="field"><label>Resultado</label><select class="input" name="resultado">${opt(RESULTADOS, "", "— sem avaliar —")}</select></div>
        <div class="field"><label>Próximo passo</label><input class="input" name="proximo_passo" placeholder="Ex.: mandar a proposta até sexta"></div>
        <div class="field full"><label>Atualizar a situação da pessoa para</label><select class="input" name="novo_status">${opt(STATUS, sugerido, "— manter como está —")}</select></div>
      </div>`, `<button type="button" class="btn" data-action="fechar-modal">Cancelar</button><button type="submit" class="btn btn-primary" data-submit="registrar">Salvar contato</button>`);
  }
  function modalAgendar(m) {
    const a = m.agenda ? byId(S.agendas, m.agenda) : null;
    const cid = (a && a.cidade_id) || m.cidade || (S.cidades[0] || {}).id;
    const sel = new Set(a ? a.pessoa_ids || [] : m.pessoa ? [m.pessoa] : []);
    const ps = S.pessoas.filter((p) => p.cidade_id === cid && p.grupo !== "canal");
    return modal(a ? "Editar compromisso" : "Agendar compromisso", `
      <div class="form-grid">
        <div class="field"><label>Cidade</label><select class="input" name="cidade_id" data-action="modal-cidade" ${a ? "disabled" : ""}>${optCidades(cid)}</select></div>
        <div class="field"><label>Quem vai</label><select class="input" name="usuario_id">${optUsers(a ? a.usuario_id : S.me.id)}</select></div>
        <div class="field full"><label>Título</label><input class="input" name="titulo" required value="${esc(a ? a.titulo : "")}" placeholder="Ex.: Reunião com o secretário de Obras" autofocus></div>
        <div class="field"><label>Início</label><input class="input" type="datetime-local" name="inicio" required value="${a ? toLocalInput(a.inicio) : ""}"></div>
        <div class="field"><label>Fim (opcional)</label><input class="input" type="datetime-local" name="fim" value="${a && a.fim ? toLocalInput(a.fim) : ""}"></div>
        <div class="field full"><label>Com quem (marque)</label><input class="input" placeholder="Filtrar pessoas…" data-action="filtrar-pick" style="margin-bottom:6px"><div class="pick-list" id="pick">${ps.map((p) => `<label data-txt="${esc((p.nome + " " + (p.cargo || "") + " " + (p.orgao || "")).toLowerCase())}"><input type="checkbox" name="pessoa_ids" value="${esc(p.id)}" ${sel.has(p.id) ? "checked" : ""}><span>${esc(p.nome)}</span><span class="sub">${esc(p.cargo || "")}${p.orgao && p.grupo !== "camara" ? " · " + esc(p.orgao) : ""}</span></label>`).join("") || `<span class="muted small">Sem pessoas cadastradas nesta cidade.</span>`}</div></div>
        <div class="field"><label>Local</label><input class="input" name="local" value="${esc(a ? a.local || "" : "")}" placeholder="Ex.: Prefeitura, sala do secretário"></div>
        <div class="field"><label>Situação</label><select class="input" name="status">${opt(AGENDA_STATUS, a ? a.status : "agendado")}</select></div>
        <div class="field full"><label>Observações</label><textarea class="input" name="obs" placeholder="Pauta, quem mais vai, o que levar…">${esc(a ? a.obs || "" : "")}</textarea></div>
      </div>`, `${a ? `<button type="button" class="btn btn-ghost btn-danger" data-action="apagar-agenda" data-id="${esc(a.id)}" style="margin-right:auto">Apagar</button>` : ""}<button type="button" class="btn" data-action="fechar-modal">Cancelar</button><button type="submit" class="btn btn-primary" data-submit="agendar" data-id="${esc(a ? a.id : "")}">${a ? "Salvar" : "Agendar"}</button>`);
  }
  function modalCidade() {
    return modal("Nova cidade", `
      <div class="form-grid">
        <div class="field"><label>Nome</label><input class="input" name="nome" required autofocus></div>
        <div class="field"><label>UF</label><input class="input" name="uf" value="RS" maxlength="2"></div>
        <div class="field"><label>Prefeito (nome e partido)</label><input class="input" name="prefeito" placeholder="Ex.: Fulano (PP)"></div>
        <div class="field"><label>Vice-prefeito</label><input class="input" name="vice"></div>
        <div class="field"><label>Presidente da Câmara</label><input class="input" name="presidente_camara"></div>
        <div class="field"><label>Cadeiras na Câmara</label><input class="input" type="number" name="cadeiras_camara" min="9"></div>
        <div class="field"><label>População</label><input class="input" type="number" name="populacao"></div>
        <div class="field"><label>Situação</label><select class="input" name="status">${opt(CIDADE_STATUS, "prospeccao")}</select></div>
        <div class="field full"><label>Resumo</label><textarea class="input" name="resumo"></textarea></div>
      </div><div class="small muted">Depois de criar, use “+ Pessoa” na página da cidade para montar a estrutura.</div>`, `<button type="button" class="btn" data-action="fechar-modal">Cancelar</button><button type="submit" class="btn btn-primary" data-submit="cidade">Criar cidade</button>`);
  }
  function modalPessoa(m) {
    const p = m.id ? pessoa(m.id) : null; const cid = p ? p.cidade_id : m.cidade;
    const titulares = S.pessoas.filter((x) => x.cidade_id === cid && !x.vinculado_a && x.grupo !== "canal" && (!p || x.id !== p.id));
    return modal(p ? "Editar cadastro" : "Nova pessoa", `
      <div class="form-grid">
        <div class="field full"><label>Nome</label><input class="input" name="nome" required value="${esc(p ? p.nome : "")}" autofocus></div>
        <div class="field"><label>Grupo</label><select class="input" name="grupo">${GRUPOS.map(([g, l]) => `<option value="${g}" ${(p ? p.grupo : m.grupo || "secretaria") === g ? "selected" : ""}>${esc(l)}</option>`).join("")}</select></div>
        <div class="field"><label>Cargo</label><input class="input" name="cargo" value="${esc(p ? p.cargo || "" : "")}" placeholder="Secretário, Vereador, Diretor…"></div>
        <div class="field"><label>Órgão / secretaria</label><input class="input" name="orgao" value="${esc(p ? p.orgao || "" : "")}"></div>
        <div class="field"><label>Partido / perfil</label><input class="input" name="partido" value="${esc(p ? p.partido || "" : "")}"></div>
        <div class="field"><label>Telefone</label><input class="input" name="telefone" value="${esc(p ? p.telefone || "" : "")}"></div>
        <div class="field"><label>WhatsApp</label><input class="input" name="whatsapp" value="${esc(p ? p.whatsapp || "" : "")}"></div>
        <div class="field"><label>E-mail</label><input class="input" name="email" value="${esc(p ? p.email || "" : "")}"></div>
        <div class="field"><label>Rede social</label><input class="input" name="rede_social" value="${esc(p ? p.rede_social || "" : "")}" placeholder="instagram.com/…"></div>
        <div class="field full"><label>Endereço</label><input class="input" name="endereco" value="${esc(p ? p.endereco || "" : "")}"></div>
        <div class="field"><label>Vinculado a (titular)</label><select class="input" name="vinculado_a"><option value="">— ninguém —</option>${titulares.map((t) => `<option value="${esc(t.id)}" ${p && p.vinculado_a === t.id ? "selected" : ""}>${esc(t.nome)} · ${esc(t.cargo || "")}</option>`).join("")}</select></div>
        <div class="field" style="justify-content:flex-end"><label class="check"><input type="checkbox" name="prioritario" ${p && p.prioritario ? "checked" : ""}> Prioritário para a Gravitas</label></div>
      </div>`, `<button type="button" class="btn" data-action="fechar-modal">Cancelar</button><button type="submit" class="btn btn-primary" data-submit="pessoa" data-id="${esc(p ? p.id : "")}" data-cidade="${esc(cid)}">${p ? "Salvar" : "Adicionar"}</button>`);
  }
  const modalPerfil = () => modal("Meu nome na equipe", `<div class="field"><label>Como você quer aparecer</label><input class="input" name="nome" required value="${esc(S.me.nome)}" autofocus></div>`, `<button type="button" class="btn" data-action="fechar-modal">Cancelar</button><button type="submit" class="btn btn-primary" data-submit="perfil">Salvar</button>`);
  const modalSenha = (primeira) => modal(primeira ? "Crie sua senha definitiva" : "Trocar minha senha", `${primeira ? `<p class="small muted">Você entrou com uma senha provisória. Escolha agora a sua senha; só você vai conhecê-la.</p>` : ""}<div class="field"><label>Nova senha (mínimo 8 caracteres)</label><input class="input" type="password" name="senha" minlength="8" required autocomplete="new-password" autofocus></div><div class="field"><label>Repita a nova senha</label><input class="input" type="password" name="senha2" minlength="8" required autocomplete="new-password"></div>`, `${primeira ? "" : `<button type="button" class="btn" data-action="fechar-modal">Cancelar</button>`}<button type="submit" class="btn btn-primary" data-submit="senha">Salvar senha</button>`);
  const modalResumo = (cid) => modal("Resumo da cidade", `<div class="field"><label>${esc((cidade(cid) || {}).nome || "")}</label><textarea class="input" name="resumo" style="min-height:160px" autofocus>${esc((cidade(cid) || {}).resumo || "")}</textarea></div>`, `<button type="button" class="btn" data-action="fechar-modal">Cancelar</button><button type="submit" class="btn btn-primary" data-submit="resumo" data-id="${esc(cid)}">Salvar</button>`);

  /* ============================ login ============================ */
  function viewLogin(err) {
    const demo = S.store.mode === "demo";
    return `<div class="login-wrap"><div class="login">
      <div class="brand"><span class="brand-mark">G</span><span><div class="brand-name">GRAVITAS</div><div class="brand-sub">Contatos com Prefeituras</div></span></div>
      <h1>Entrar</h1><div class="lead">${demo ? "Modo demonstração: escolha quem você é. Nada do que fizer aqui é compartilhado." : "Use o e-mail e a senha cadastrados pela Gravitas."}</div>
      ${demo ? `<div class="demo-pick">${window.DEMO_USERS.map((u) => `<button type="button" data-action="demo-login" data-email="${esc(u.nome.toLowerCase())}@gravitas.net.br">${av(u)}${esc(u.nome)}</button>`).join("")}</div>` : `
      <form id="login-form"><div class="field"><label>E-mail</label><input class="input" type="email" name="email" required autocomplete="username"></div>
        <div class="field"><label>Senha</label><input class="input" type="password" name="senha" required autocomplete="current-password"></div>
        <div class="err">${esc(err || "")}</div>
        <button class="btn btn-primary" type="submit" style="justify-content:center">Entrar</button>
        <button class="btn btn-ghost btn-sm" type="button" data-action="esqueci" style="justify-content:center">Esqueci minha senha</button></form>`}
    </div></div>`;
  }

  /* ============================ render ============================ */
  function route() {
    const h = location.hash.replace(/^#\/?/, "");
    const parts = h.split("/").filter(Boolean);
    if (parts[0] === "cidade" && parts[1]) return { name: "cidade", id: decodeURIComponent(parts[1]), pessoa: parts[2] === "pessoa" && parts[3] ? decodeURIComponent(parts[3]) : null };
    if (parts[0] === "agenda") return { name: "agenda" };
    if (parts[0] === "atividade") return { name: "atividade" };
    return { name: "home" };
  }
  function render() {
    if (!S.me) { app.innerHTML = viewLogin(S.loginErr); return; }
    const r = route();
    if (r.name === "cidade" && r.pessoa) S.drawer = r.pessoa;
    let body = r.name === "cidade" ? viewCidade(r.id) : r.name === "agenda" ? viewAgenda() : r.name === "atividade" ? viewAtividade() : viewHome();
    let extra = S.drawer ? drawerPessoa(S.drawer) : "";
    if (S.modal) {
      const m = S.modal;
      extra += m.kind === "registrar" ? modalRegistrar(m) : m.kind === "agendar" ? modalAgendar(m) : m.kind === "cidade" ? modalCidade() : m.kind === "pessoa" ? modalPessoa(m) : m.kind === "perfil" ? modalPerfil() : m.kind === "senha" ? modalSenha(m.primeira) : m.kind === "resumo" ? modalResumo(m.id) : "";
    }
    const scrollY = window.scrollY;
    app.innerHTML = topbar(r.name) + body + extra;
    document.body.classList.toggle("has-drawer", !!S.drawer || !!S.modal);
    window.scrollTo(0, scrollY);
    const foco = app.querySelector("#mform [autofocus]"); if (foco && S.modal && !S.modal._focused) { foco.focus(); S.modal._focused = true; }
    const busca = app.querySelector('[data-filtro="busca"]'); if (busca && S.filtroFoco) { busca.focus(); busca.setSelectionRange(busca.value.length, busca.value.length); }
  }

  /* ============================ ações ============================ */
  let toastT;
  function toast(msg, err) { let t = document.getElementById("toast"); if (!t) { t = document.createElement("div"); t.id = "toast"; document.body.appendChild(t); } t.className = "toast" + (err ? " err" : ""); t.textContent = msg; clearTimeout(toastT); toastT = setTimeout(() => t.remove(), err ? 5000 : 2200); }
  async function tenta(fn, ok) { try { await fn(); if (ok) toast(ok); } catch (e) { console.error(e); toast(e.message || "Erro", true); } }

  function applyChange(ev) {
    const map = { usuarios: S.usuarios, cidades: S.cidades, pessoas: S.pessoas, interacoes: S.interacoes, agendas: S.agendas };
    const arr = map[ev.table]; if (!arr) return;
    if (ev.type === "DELETE") { const id = (ev.old || {}).id; const i = arr.findIndex((x) => x.id === id); if (i >= 0) arr.splice(i, 1); }
    else if (ev.row) { const i = arr.findIndex((x) => x.id === ev.row.id); if (i >= 0) arr[i] = { ...arr[i], ...ev.row }; else arr.push(ev.row); if (ev.table === "usuarios" && ev.row.id === S.me.id) S.me = { ...S.me, ...ev.row }; }
    if (ev.table === "pessoas") S.pessoas.sort((a, b) => (a.ordem || 0) - (b.ordem || 0));
    if (ev.table === "usuarios") S.usuarios.sort((a, b) => a.nome.localeCompare(b.nome));
    // não redesenha enquanto um formulário ou o campo de notas está sendo editado
    if (document.activeElement && (document.activeElement.closest("#mform") || document.activeElement.matches('[data-action="pessoa-notas"]'))) { S.pendingRender = true; return; }
    render();
  }

  async function boot() {
    S.store = window.createStore();
    app.innerHTML = `<div class="login-wrap"><div class="login"><div class="brand"><span class="brand-mark">G</span><span><div class="brand-name">GRAVITAS</div><div class="brand-sub">Contatos com Prefeituras</div></span></div><div class="muted">Carregando…</div></div></div>`;
    S.store.onAuth((u, ev) => { if (!u && S.me) { S.me = null; render(); } if (ev === "PASSWORD_RECOVERY" && u) { S.recovery = true; if (S.me) { S.modal = { kind: "senha" }; render(); } } });
    if (/access_token=|type=recovery/.test(location.hash)) history.replaceState(null, "", "#/");
    const u = await S.store.getSession();
    if (u) await entrar(u); else render();
  }
  async function entrar(u) {
    try {
      const perfil = await S.store.ensureProfile(u);
      S.me = { ...perfil, email: u.email };
      const d = await S.store.loadAll();
      Object.assign(S, d);
      S.store.onChange(applyChange);
      S.store.presence(S.me, (list) => { S.online = list; if (!(document.activeElement && document.activeElement.closest("#mform"))) render(); });
      S.loginErr = "";
      const provisoria = await S.store.senhaProvisoria();
      if (S.recovery || provisoria) { S.recovery = false; S.modal = { kind: "senha", primeira: provisoria }; }
      render();
    } catch (e) { console.error(e); S.loginErr = "Não foi possível carregar os dados: " + (e.message || e); S.me = null; render(); }
  }
  async function salvarNotas(el) {
    const id = el.dataset.id; const p = pessoa(id); if (!p || (p.notas || "") === el.value) return;
    await tenta(async () => { Object.assign(p, await S.store.updatePessoa(id, { notas: el.value || null, atualizado_por: S.me.id })); const s = document.getElementById("notas-saved"); if (s) { s.textContent = "salvo ✓"; setTimeout(() => (s.textContent = ""), 2000); } });
  }
  function fechar() { if (S.modal && S.modal.primeira) return; S.modal = null; render(); }
  function fecharDrawer() { S.drawer = null; const r = route(); if (r.name === "cidade" && r.pessoa) { history.replaceState(null, "", "#/cidade/" + encodeURIComponent(r.id)); } render(); }

  document.addEventListener("click", async (e) => {
    const t = e.target.closest("[data-action],[data-submit]"); const sub = e.target.closest("[data-submit]");
    if (sub) return; // o submit do formulário trata
    if (!t) { if (S.menu && !e.target.closest(".menu,.me-btn")) { S.menu = false; render(); } return; }
    const a = t.dataset.action; const id = t.dataset.id;
    if (a === "fechar-modal" && e.target !== t && e.target.closest("[data-stop]")) return; // clique dentro do modal
    if (t.tagName === "A" && t.getAttribute("href") === "#") e.preventDefault();
    switch (a) {
      case "menu": S.menu = !S.menu; render(); break;
      case "sair": S.menu = false; await S.store.signOut(); S.me = null; S.drawer = null; location.hash = "#/"; render(); break;
      case "perfil": S.menu = false; S.modal = { kind: "perfil" }; render(); break;
      case "senha": S.menu = false; S.modal = { kind: "senha" }; render(); break;
      case "demo-login": await S.store.signIn(t.dataset.email, ""); await entrar({ id: null, email: t.dataset.email, ...(await S.store.getSession()) }); break;
      case "esqueci": { const email = (document.querySelector('#login-form [name=email]') || {}).value; if (!email) { toast("Digite seu e-mail primeiro.", true); return; } await tenta(() => S.store.resetPassword(email), "Enviamos um link para redefinir a senha."); break; }
      case "abrir": e.preventDefault(); e.stopPropagation(); S.drawer = id; { const r = route(); if (r.name === "cidade") history.replaceState(null, "", "#/cidade/" + encodeURIComponent(r.id) + "/pessoa/" + encodeURIComponent(id)); } render(); break;
      case "fechar-drawer": fecharDrawer(); break;
      case "fechar-modal": fechar(); break;
      case "registrar": S.modal = { kind: "registrar", cidade: t.dataset.cidade || (S.drawer ? (pessoa(S.drawer) || {}).cidade_id : (route().id)), pessoa: t.dataset.pessoa || null }; render(); break;
      case "agendar": S.modal = { kind: "agendar", cidade: t.dataset.cidade || (route().id), pessoa: t.dataset.pessoa || null }; render(); break;
      case "editar-agenda": S.modal = { kind: "agendar", agenda: id }; render(); break;
      case "nova-cidade": S.modal = { kind: "cidade" }; render(); break;
      case "nova-pessoa": S.modal = { kind: "pessoa", cidade: t.dataset.cidade }; render(); break;
      case "editar-pessoa": S.modal = { kind: "pessoa", id }; render(); break;
      case "editar-resumo": S.modal = { kind: "resumo", id }; render(); break;
      case "agenda-realizado": await tenta(() => S.store.updateAgenda(id, { status: "realizado" }).then((r) => Object.assign(byId(S.agendas, id), r)), "Marcado como realizado."); render(); break;
      case "agenda-cancelar": if (!confirm("Cancelar este compromisso?")) return; await tenta(() => S.store.updateAgenda(id, { status: "cancelado" }).then((r) => Object.assign(byId(S.agendas, id), r)), "Compromisso cancelado."); render(); break;
      case "apagar-agenda": if (!confirm("Apagar este compromisso de vez?")) return; await tenta(async () => { await S.store.deleteAgenda(id); S.agendas = S.agendas.filter((x) => x.id !== id); }, "Apagado."); S.modal = null; render(); break;
      case "apagar-interacao": if (!confirm("Apagar este registro?")) return; await tenta(async () => { await S.store.deleteInteracao(id); S.interacoes = S.interacoes.filter((x) => x.id !== id); }, "Apagado."); render(); break;
    }
  });

  document.addEventListener("change", async (e) => {
    const t = e.target; const a = t.dataset.action; const id = t.dataset.id;
    if (t.dataset.filtro) { S.filtro[t.dataset.filtro] = t.type === "checkbox" ? t.checked : t.value; S.filtroFoco = false; render(); return; }
    if (t.dataset.agfiltro) { S.agFiltro[t.dataset.agfiltro] = t.value; render(); return; }
    if (t.dataset.atfiltro) { S.atFiltro[t.dataset.atfiltro] = t.value; render(); return; }
    if (a === "pessoa-status") await tenta(async () => Object.assign(pessoa(id), await S.store.updatePessoa(id, { status: t.value, atualizado_por: S.me.id })), "Situação atualizada.").then(render);
    if (a === "pessoa-resp") await tenta(async () => Object.assign(pessoa(id), await S.store.updatePessoa(id, { responsavel_id: t.value || null, atualizado_por: S.me.id })), "Responsável atualizado.").then(render);
    if (a === "cidade-status") await tenta(async () => Object.assign(cidade(id), await S.store.updateCidade(id, { status: t.value })), "Situação da cidade atualizada.").then(render);
    if (a === "modal-cidade") { if (S.modal) { S.modal.cidade = t.value; S.modal.pessoa = null; S.modal._focused = true; render(); } }
  });
  document.addEventListener("input", (e) => {
    const t = e.target;
    if (t.dataset.filtro === "busca") { S.filtro.busca = t.value; S.filtroFoco = true; render(); }
    if (t.dataset.action === "filtrar-pick") { const q = t.value.toLowerCase(); document.querySelectorAll("#pick label").forEach((l) => (l.style.display = l.dataset.txt.includes(q) ? "" : "none")); }
  });
  document.addEventListener("focusout", (e) => { if (e.target.dataset && e.target.dataset.action === "pessoa-notas") salvarNotas(e.target).then(() => { if (S.pendingRender) { S.pendingRender = false; render(); } }); });
  document.addEventListener("keydown", (e) => { if (e.key === "Escape") { if (S.modal) fechar(); else if (S.drawer) fecharDrawer(); } });

  document.addEventListener("submit", async (e) => {
    const form = e.target; e.preventDefault();
    if (form.id === "login-form") { const fd = new FormData(form); try { await S.store.signIn(fd.get("email").trim(), fd.get("senha")); await entrar(await S.store.getSession()); } catch (err) { S.loginErr = err.message; render(); } return; }
    if (form.id !== "mform") return;
    const btn = form.querySelector("[data-submit]"); const kind = btn.dataset.submit; const fd = new FormData(form);
    const v = (k) => (fd.get(k) || "").toString().trim() || null;
    const err = form.querySelector("#merr"); btn.disabled = true;
    try {
      if (kind === "registrar") {
        const pid = v("pessoa_id"); const novo = v("novo_status");
        const row = await S.store.addInteracao({ cidade_id: v("cidade_id"), pessoa_id: pid, usuario_id: S.me.id, tipo: v("tipo"), data: new Date(v("data")).toISOString(), resumo: v("resumo"), resultado: v("resultado"), proximo_passo: v("proximo_passo") });
        if (!byId(S.interacoes, row.id)) S.interacoes.unshift(row);
        if (pid && novo && pessoa(pid) && pessoa(pid).status !== novo) Object.assign(pessoa(pid), await S.store.updatePessoa(pid, { status: novo, atualizado_por: S.me.id, ...(pessoa(pid).responsavel_id ? {} : { responsavel_id: S.me.id }) }));
        else if (pid && pessoa(pid) && !pessoa(pid).responsavel_id) Object.assign(pessoa(pid), await S.store.updatePessoa(pid, { responsavel_id: S.me.id, atualizado_por: S.me.id }));
        toast("Contato registrado.");
      } else if (kind === "agendar") {
        const obj = { usuario_id: v("usuario_id") || S.me.id, titulo: v("titulo"), inicio: new Date(v("inicio")).toISOString(), fim: v("fim") ? new Date(v("fim")).toISOString() : null, pessoa_ids: fd.getAll("pessoa_ids"), local: v("local"), status: v("status") || "agendado", obs: v("obs") };
        if (btn.dataset.id) Object.assign(byId(S.agendas, btn.dataset.id), await S.store.updateAgenda(btn.dataset.id, obj));
        else { const row = await S.store.addAgenda({ ...obj, cidade_id: v("cidade_id") }); if (!byId(S.agendas, row.id)) S.agendas.push(row); }
        toast(btn.dataset.id ? "Compromisso atualizado." : "Compromisso agendado.");
      } else if (kind === "cidade") {
        const nome = v("nome"); const id = slug(nome) + (cidade(slug(nome)) ? "-" + Date.now().toString(36) : "");
        const row = await S.store.addCidade({ id, nome, uf: (v("uf") || "RS").toUpperCase(), prefeito: v("prefeito"), vice: v("vice"), presidente_camara: v("presidente_camara"), cadeiras_camara: v("cadeiras_camara") ? +v("cadeiras_camara") : null, populacao: v("populacao") ? +v("populacao") : null, status: v("status") || "prospeccao", resumo: v("resumo"), ordem: 100 + S.cidades.length });
        if (!cidade(row.id)) S.cidades.push(row); S.modal = null; location.hash = "#/cidade/" + encodeURIComponent(row.id); toast("Cidade criada."); return;
      } else if (kind === "pessoa") {
        const obj = { nome: v("nome"), grupo: v("grupo"), cargo: v("cargo"), orgao: v("orgao"), partido: v("partido"), telefone: v("telefone"), whatsapp: v("whatsapp"), email: v("email"), rede_social: v("rede_social"), endereco: v("endereco"), vinculado_a: v("vinculado_a"), prioritario: fd.get("prioritario") === "on" };
        if (btn.dataset.id) Object.assign(pessoa(btn.dataset.id), await S.store.updatePessoa(btn.dataset.id, { ...obj, atualizado_por: S.me.id }));
        else { const cid = btn.dataset.cidade; const id = cid.slice(0, 3) + "-" + slug(obj.nome) + "-" + Date.now().toString(36); const row = await S.store.addPessoa({ id, cidade_id: cid, ...obj, ordem: 500 + S.pessoas.filter((p) => p.cidade_id === cid).length }); if (!pessoa(row.id)) S.pessoas.push(row); S.drawer = row.id; }
        toast("Cadastro salvo.");
      } else if (kind === "perfil") {
        Object.assign(S.me, await S.store.updateMe(S.me.id, { nome: v("nome") })); const u = user(S.me.id); if (u) u.nome = S.me.nome; toast("Nome atualizado.");
      } else if (kind === "senha") {
        if (v("senha") !== v("senha2")) throw new Error("As duas senhas não são iguais.");
        await S.store.updatePassword(v("senha")); toast("Senha alterada.");
      } else if (kind === "resumo") {
        Object.assign(cidade(btn.dataset.id), await S.store.updateCidade(btn.dataset.id, { resumo: v("resumo") })); toast("Resumo salvo.");
      }
      S.modal = null; render();
    } catch (ex) { console.error(ex); err.textContent = ex.message || "Não foi possível salvar."; btn.disabled = false; }
  });

  window.addEventListener("hashchange", () => { S.menu = false; if (!(S.modal && S.modal.primeira)) S.modal = null; const r = route(); if (!(r.name === "cidade" && r.pessoa)) S.drawer = null; S.filtroFoco = false; render(); });
  boot();
})();
