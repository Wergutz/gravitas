// Configuração do banco (Supabase). Pegue os dois valores em:
//   Supabase → seu projeto → Project Settings → API
//   • Project URL        → SUPABASE_URL
//   • anon public (key)  → SUPABASE_ANON_KEY
// A chave "anon" é pública por desenho: o que protege os dados são as regras do banco (RLS) e o login.
// Com os dois campos vazios, o sistema abre em MODO DEMONSTRAÇÃO (dados locais, nada é compartilhado).
window.CONTATOS_CONFIG = {
  SUPABASE_URL: "",
  SUPABASE_ANON_KEY: ""
};
