/* Camada de dados. Duas implementações com a mesma interface:
   - MysqlStore: o banco do site, atrás da API em PHP (uso normal)
   - DemoStore:  dados locais no navegador, para mostrar o sistema sem tocar em dado real

   A versão anterior falava direto com o Supabase. Trocada a pedido: nada
   de serviço externo guardando ou validando dado nosso. O app.js não
   soube da diferença, porque a interface é a mesma.
*/
(function () {
  "use strict";

  const DEMO_USERS = [
    { id: "u-alex",   nome: "Alex",   cor: "#1E304B" },
    { id: "u-carlo",  nome: "Carlo",  cor: "#E89C2A" },
    { id: "u-gelson", nome: "Gelson", cor: "#2E75B6" },
    { id: "u-rafael", nome: "Rafael", cor: "#2F9E6B" },
  ];

  function uid() {
    return (crypto.randomUUID ? crypto.randomUUID() : "id-" + Date.now() + "-" + Math.random().toString(16).slice(2));
  }

  /* ---------------------------------------------------------------- */
  class DemoStore {
    constructor() {
      this.mode = "demo";
      this._cbs = [];
      this._user = null;
      const seed = window.DEMO_DATA || { cidades: [], pessoas: [] };
      this.db = {
        usuarios: DEMO_USERS.map((u) => ({ ...u })),
        cidades: seed.cidades.map((c) => ({ ...c, atualizado_em: new Date().toISOString() })),
        pessoas: seed.pessoas.map((p) => ({ ...p, status: "nao_contatado", responsavel_id: null, notas: null, atualizado_em: new Date().toISOString() })),
        interacoes: [], agendas: [],
      };
      try {
        const saved = JSON.parse(localStorage.getItem("contatos-demo") || "null");
        if (saved && saved.v === 1) {
          // mantém o trabalho feito na demonstração, mas atualiza o cadastro vindo do seed
          const work = Object.fromEntries(saved.pessoas.map((p) => [p.id, p]));
          this.db.pessoas = this.db.pessoas.map((p) => ({ ...p, ...(work[p.id] ? { status: work[p.id].status, responsavel_id: work[p.id].responsavel_id, notas: work[p.id].notas } : {}) }))
            .concat(saved.pessoas.filter((p) => !seed.pessoas.some((s) => s.id === p.id)));
          const seedC = new Set(seed.cidades.map((c) => c.id));
          this.db.cidades = this.db.cidades.map((c) => { const s = saved.cidades.find((x) => x.id === c.id); return s ? { ...c, status: s.status, resumo: s.resumo } : c; })
            .concat(saved.cidades.filter((c) => !seedC.has(c.id)));
          this.db.interacoes = saved.interacoes || []; this.db.agendas = saved.agendas || [];
          this.db.usuarios = saved.usuarios || this.db.usuarios;
          this._user = saved.user || null;
        }
      } catch (e) { /* sem armazenamento: segue só em memória */ }
    }
    _save() {
      try { localStorage.setItem("contatos-demo", JSON.stringify({ v: 1, ...this.db, user: this._user })); } catch (e) { /* ignora */ }
    }
    _emit(table, type, row, old) { this._cbs.forEach((f) => f({ table, type, row, old })); }
    async getSession() { return this._user; }
    onAuth(cb) { this._authCb = cb; }
    async signIn(email) {
      const nome = (email.split("@")[0] || "").toLowerCase();
      const u = this.db.usuarios.find((x) => x.nome.toLowerCase() === nome) || this.db.usuarios[0];
      this._user = { id: u.id, email }; this._save();
      if (this._authCb) this._authCb(this._user);
    }
    async signOut() { this._user = null; this._save(); if (this._authCb) this._authCb(null); }
    async resetPassword() { throw new Error("No modo demonstração não há senha."); }
    async updatePassword() { throw new Error("No modo demonstração não há senha."); }
    async senhaProvisoria() { return false; }
    async ensureProfile(user) { return this.db.usuarios.find((u) => u.id === user.id); }
    async loadAll() { return JSON.parse(JSON.stringify(this.db)); }
    async updateMe(id, patch) { const u = this.db.usuarios.find((x) => x.id === id); Object.assign(u, patch); this._save(); this._emit("usuarios", "UPDATE", { ...u }); return { ...u }; }
    async addCidade(obj) { const c = { status: "prospeccao", ordem: 100, atualizado_em: new Date().toISOString(), ...obj }; this.db.cidades.push(c); this._save(); this._emit("cidades", "INSERT", { ...c }); return { ...c }; }
    async updateCidade(id, patch) { const c = this.db.cidades.find((x) => x.id === id); Object.assign(c, patch, { atualizado_em: new Date().toISOString() }); this._save(); this._emit("cidades", "UPDATE", { ...c }); return { ...c }; }
    async addPessoa(obj) { const p = { status: "nao_contatado", responsavel_id: null, notas: null, prioritario: false, ordem: 999, foto: null, vinculado_a: null, ...obj, atualizado_em: new Date().toISOString() }; this.db.pessoas.push(p); this._save(); this._emit("pessoas", "INSERT", { ...p }); return { ...p }; }
    async updatePessoa(id, patch) { const p = this.db.pessoas.find((x) => x.id === id); Object.assign(p, patch, { atualizado_em: new Date().toISOString() }); this._save(); this._emit("pessoas", "UPDATE", { ...p }); return { ...p }; }
    async addInteracao(obj) { const i = { id: uid(), criado_em: new Date().toISOString(), data: new Date().toISOString(), ...obj }; this.db.interacoes.unshift(i); this._save(); this._emit("interacoes", "INSERT", { ...i }); return { ...i }; }
    async deleteInteracao(id) { const i = this.db.interacoes.find((x) => x.id === id); this.db.interacoes = this.db.interacoes.filter((x) => x.id !== id); this._save(); this._emit("interacoes", "DELETE", null, { id }); return i; }
    async addAgenda(obj) { const a = { id: uid(), status: "agendado", pessoa_ids: [], criado_em: new Date().toISOString(), atualizado_em: new Date().toISOString(), ...obj }; this.db.agendas.push(a); this._save(); this._emit("agendas", "INSERT", { ...a }); return { ...a }; }
    async updateAgenda(id, patch) { const a = this.db.agendas.find((x) => x.id === id); Object.assign(a, patch, { atualizado_em: new Date().toISOString() }); this._save(); this._emit("agendas", "UPDATE", { ...a }); return { ...a }; }
    async deleteAgenda(id) { this.db.agendas = this.db.agendas.filter((x) => x.id !== id); this._save(); this._emit("agendas", "DELETE", null, { id }); }
    onChange(cb) { this._cbs.push(cb); }
    presence(me, cb) { setTimeout(() => cb([{ id: me.id, nome: me.nome, cor: me.cor }]), 0); }
  }

  /* ---------------------------------------------------------------- */
  /* Banco próprio do site: MySQL atrás de /api, em PHP.
     Mesma interface das outras duas, então o app.js não muda.

     Duas coisas que o Supabase dava de graça e aqui são construídas:

     - TEMPO REAL. Lá o banco empurrava cada mudança. Aqui o navegador
       pergunta de 15 em 15 segundos por uma impressão digital barata
       (contagem + data da última alteração). Só quando ela muda é que
       recarrega os dados e compara com o que tinha, emitindo os mesmos
       eventos {table, type, row, old} que o app.js já sabe aplicar.

     - QUEM ESTÁ ONLINE. Lá era presença do próprio servidor. Aqui cada
       um bate ponto na mesma volta do relógio, e online é quem bateu
       nos últimos 90 segundos. */
  const CT_INTERVALO = 15000;

  class MysqlStore {
    constructor() {
      this.mode = "mysql";
      this._changeCbs = [];
      this._user = null;
      this._versao = null;
      this._snap = null;
      this._relogio = null;
    }

    async _api(acao, corpo) {
      let r;
      try {
        r = await fetch("api/index.php?a=" + encodeURIComponent(acao), {
          method: corpo === undefined ? "GET" : "POST",
          headers: corpo === undefined ? {} : { "Content-Type": "application/json" },
          body: corpo === undefined ? undefined : JSON.stringify(corpo),
          credentials: "same-origin",
        });
      } catch (e) {
        throw new Error("Sem conexão com o servidor.");
      }
      let j = null;
      try { j = await r.json(); } catch (e) { /* resposta sem JSON */ }
      if (!r.ok) {
        if (r.status === 401 && this._user) { this._user = null; if (this._authCb) this._authCb(null); }
        throw new Error((j && j.mensagem) || "Erro ao falar com o servidor.");
      }
      return j || {};
    }

    async getSession() {
      const { usuario } = await this._api("sessao");
      this._user = usuario || null;
      return this._user;
    }
    onAuth(cb) { this._authCb = cb; }
    async signIn(email, senha) {
      const { usuario } = await this._api("entrar", { email, senha });
      this._user = usuario;
      if (this._authCb) this._authCb(usuario);
    }
    async signOut() {
      await this._api("sair", {});
      this._user = null;
      if (this._relogio) { clearInterval(this._relogio); this._relogio = null; }
      if (this._authCb) this._authCb(null);
    }
    async resetPassword() {
      // Não há envio de e-mail no site; quem repõe senha é o administrador.
      throw new Error("Para repor a senha, peça ao administrador do sistema.");
    }
    async updatePassword(senha) {
      const { usuario } = await this._api("senha", { senha });
      this._user = usuario;
    }
    async senhaProvisoria() {
      const u = this._user || (await this.getSession());
      return !!(u && u.trocar_senha);
    }
    async ensureProfile(user) {
      // O perfil nasce junto com a conta, no banco. Nada a criar aqui.
      return user;
    }

    async loadAll() {
      const d = await this._api("tudo");
      this._snap = JSON.parse(JSON.stringify(d));
      return d;
    }

    updateMe(id, patch)     { return this._api("perfil", patch).then((r) => r.usuario); }
    addCidade(obj)          { return this._api("cidade.criar", obj).then((r) => r.linha); }
    updateCidade(id, patch) { return this._api("cidade.editar", { ...patch, id }).then((r) => r.linha); }
    addPessoa(obj)          { return this._api("pessoa.criar", obj).then((r) => r.linha); }
    updatePessoa(id, patch) { return this._api("pessoa.editar", { ...patch, id }).then((r) => r.linha); }
    addInteracao(obj)       { return this._api("interacao.criar", obj).then((r) => r.linha); }
    deleteInteracao(id)     { return this._api("interacao.apagar", { id }); }
    addAgenda(obj)          { return this._api("agenda.criar", obj).then((r) => r.linha); }
    updateAgenda(id, patch) { return this._api("agenda.editar", { ...patch, id }).then((r) => r.linha); }
    deleteAgenda(id)        { return this._api("agenda.apagar", { id }); }

    onChange(cb) { this._changeCbs.push(cb); this._ligarRelogio(); }

    presence(me, cb) { this._presCb = cb; this._ligarRelogio(); }

    _ligarRelogio() {
      if (this._relogio) return;
      const volta = () => this._volta().catch(() => { /* rede caiu; tenta de novo no próximo */ });
      this._relogio = setInterval(volta, CT_INTERVALO);
      volta();
    }

    async _volta() {
      if (!this._user) return;
      if (this._presCb) {
        const { online } = await this._api("presenca", {});
        this._presCb(online || []);
      }
      if (!this._changeCbs.length) return;
      const { versao } = await this._api("versao");
      if (versao === this._versao) return;      // nada mudou: não baixa nada
      const primeira = this._versao === null;
      this._versao = versao;
      if (primeira) return;                     // a 1ª volta só registra onde estamos
      const novo = await this._api("tudo");
      this._comparar(novo);
      this._snap = JSON.parse(JSON.stringify(novo));
    }

    /* Compara o que chegou com o que havia e emite os mesmos eventos que
       o Supabase emitia, para o app.js aplicar sem saber a diferença. */
    _comparar(novo) {
      if (!this._snap) { this._snap = JSON.parse(JSON.stringify(novo)); return; }
      for (const tabela of ["usuarios", "cidades", "pessoas", "interacoes", "agendas"]) {
        const antes = new Map((this._snap[tabela] || []).map((r) => [r.id, r]));
        const agora = new Map((novo[tabela] || []).map((r) => [r.id, r]));
        for (const [id, linha] of agora) {
          const velha = antes.get(id);
          if (!velha) this._emitir({ table: tabela, type: "INSERT", row: linha });
          else if (JSON.stringify(velha) !== JSON.stringify(linha)) this._emitir({ table: tabela, type: "UPDATE", row: linha });
        }
        for (const [id] of antes) {
          if (!agora.has(id)) this._emitir({ table: tabela, type: "DELETE", row: null, old: { id } });
        }
      }
    }
    _emitir(ev) { this._changeCbs.forEach((f) => f(ev)); }
  }

  window.createStore = function () {
    const cfg = window.CONTATOS_CONFIG || {};
    // O banco do site é o caminho normal. O modo demonstração só entra
    // quando se pede, para mostrar o sistema sem tocar em dado real.
    if (cfg.MODO === "demo") return new DemoStore();
    return new MysqlStore();
  };
  window.DEMO_USERS = DEMO_USERS;
})();
