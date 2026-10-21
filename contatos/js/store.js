/* Camada de dados. Duas implementações com a mesma interface:
   - SupabaseStore: banco compartilhado em tempo real (produção)
   - DemoStore: dados locais no navegador, para ver o sistema antes do Supabase existir
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
  class SupabaseStore {
    constructor(url, key) {
      this.mode = "supabase";
      this.sb = window.supabase.createClient(url, key, { auth: { persistSession: true, autoRefreshToken: true } });
      this._changeCbs = [];
      this._presenceCh = null;
    }
    async getSession() {
      const { data } = await this.sb.auth.getSession();
      return data.session ? data.session.user : null;
    }
    onAuth(cb) { this.sb.auth.onAuthStateChange((ev, session) => cb(session ? session.user : null, ev)); }
    async signIn(email, senha) {
      const { error } = await this.sb.auth.signInWithPassword({ email, password: senha });
      if (error) throw new Error(this._msg(error));
    }
    async signOut() { await this.sb.auth.signOut(); }
    async resetPassword(email) {
      const { error } = await this.sb.auth.resetPasswordForEmail(email, { redirectTo: location.origin + location.pathname });
      if (error) throw new Error(this._msg(error));
    }
    async updatePassword(senha) {
      // troca a senha e apaga a marca de "senha provisória" (usada no primeiro acesso)
      const { error } = await this.sb.auth.updateUser({ password: senha, data: { senha_provisoria: false } });
      if (error) throw new Error(this._msg(error));
    }
    async senhaProvisoria() {
      const { data } = await this.sb.auth.getUser();
      return !!(data && data.user && data.user.user_metadata && data.user.user_metadata.senha_provisoria);
    }
    _msg(error) {
      const m = (error && error.message) || "";
      if (/invalid login/i.test(m)) return "E-mail ou senha incorretos.";
      if (/email not confirmed/i.test(m)) return "E-mail ainda não confirmado.";
      if (/rate limit/i.test(m)) return "Muitas tentativas. Aguarde um minuto.";
      return m || "Erro ao falar com o servidor.";
    }
    async ensureProfile(user) {
      let { data } = await this.sb.from("usuarios").select("*").eq("id", user.id).maybeSingle();
      if (!data) {
        const nome = (user.user_metadata && user.user_metadata.nome) || (user.email || "").split("@")[0];
        const ins = await this.sb.from("usuarios").insert({ id: user.id, nome: nome.charAt(0).toUpperCase() + nome.slice(1) }).select().single();
        if (ins.error) throw new Error(ins.error.message);
        data = ins.data;
      }
      return data;
    }
    async loadAll() {
      const q = (t, order) => this.sb.from(t).select("*").order(order.col, { ascending: order.asc !== false }).limit(5000);
      const [u, c, p, i, a] = await Promise.all([
        q("usuarios", { col: "nome" }), q("cidades", { col: "ordem" }), q("pessoas", { col: "ordem" }),
        q("interacoes", { col: "data", asc: false }), q("agendas", { col: "inicio" }),
      ]);
      for (const r of [u, c, p, i, a]) if (r.error) throw new Error(r.error.message);
      return { usuarios: u.data, cidades: c.data, pessoas: p.data, interacoes: i.data, agendas: a.data };
    }
    async _one(promise) { const { data, error } = await promise; if (error) throw new Error(error.message); return data; }
    updateMe(id, patch)      { return this._one(this.sb.from("usuarios").update(patch).eq("id", id).select().single()); }
    addCidade(obj)           { return this._one(this.sb.from("cidades").insert(obj).select().single()); }
    updateCidade(id, patch)  { return this._one(this.sb.from("cidades").update(patch).eq("id", id).select().single()); }
    addPessoa(obj)           { return this._one(this.sb.from("pessoas").insert(obj).select().single()); }
    updatePessoa(id, patch)  { return this._one(this.sb.from("pessoas").update(patch).eq("id", id).select().single()); }
    addInteracao(obj)        { return this._one(this.sb.from("interacoes").insert(obj).select().single()); }
    deleteInteracao(id)      { return this._one(this.sb.from("interacoes").delete().eq("id", id)); }
    addAgenda(obj)           { return this._one(this.sb.from("agendas").insert(obj).select().single()); }
    updateAgenda(id, patch)  { return this._one(this.sb.from("agendas").update(patch).eq("id", id).select().single()); }
    deleteAgenda(id)         { return this._one(this.sb.from("agendas").delete().eq("id", id)); }
    onChange(cb) {
      this._changeCbs.push(cb);
      if (this._changeCh) return;
      this._changeCh = this.sb.channel("mudancas")
        .on("postgres_changes", { event: "*", schema: "public" }, (payload) => {
          const ev = { table: payload.table, type: payload.eventType, row: payload.new, old: payload.old };
          this._changeCbs.forEach((f) => f(ev));
        })
        .subscribe();
    }
    presence(me, cb) {
      if (this._presenceCh) this.sb.removeChannel(this._presenceCh);
      const ch = this.sb.channel("online", { config: { presence: { key: me.id } } });
      ch.on("presence", { event: "sync" }, () => {
        const state = ch.presenceState();
        const list = Object.keys(state).map((k) => state[k][0]).filter(Boolean);
        cb(list);
      });
      ch.subscribe(async (status) => {
        if (status === "SUBSCRIBED") await ch.track({ id: me.id, nome: me.nome, cor: me.cor, desde: Date.now() });
      });
      this._presenceCh = ch;
    }
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

  window.createStore = function () {
    const cfg = window.CONTATOS_CONFIG || {};
    if (cfg.SUPABASE_URL && cfg.SUPABASE_ANON_KEY && window.supabase) return new SupabaseStore(cfg.SUPABASE_URL, cfg.SUPABASE_ANON_KEY);
    return new DemoStore();
  };
  window.DEMO_USERS = DEMO_USERS;
})();
