-- =====================================================================
--  Gravitas · Contatos com Prefeituras — esquema do banco (Supabase)
--  Rode este arquivo UMA vez no SQL Editor do projeto. Depois rode seed.sql.
-- =====================================================================

create extension if not exists pgcrypto;

-- ---------------------------------------------------------------------
-- Usuários da equipe (perfil público; a senha fica em auth.users)
-- ---------------------------------------------------------------------
create table if not exists public.usuarios (
  id         uuid primary key references auth.users(id) on delete cascade,
  nome       text not null,
  cor        text not null default '#2E75B6',
  criado_em  timestamptz not null default now()
);

-- ---------------------------------------------------------------------
-- Cidades
-- ---------------------------------------------------------------------
create table if not exists public.cidades (
  id                text primary key,                       -- slug, ex.: santa-cruz-do-sul
  nome              text not null,
  uf                text not null default 'RS',
  populacao         integer,
  prefeito          text,
  vice              text,
  presidente_camara text,
  cadeiras_camara   integer,
  status            text not null default 'prospeccao'
                    check (status in ('prospeccao','em_contato','proposta','contratada','pausada')),
  resumo            text,
  ordem             integer not null default 100,
  atualizado_em     timestamptz not null default now()
);

-- ---------------------------------------------------------------------
-- Pessoas e canais de cada cidade (a estrutura da apresentação)
-- ---------------------------------------------------------------------
create table if not exists public.pessoas (
  id             text primary key,
  cidade_id      text not null references public.cidades(id) on delete cascade,
  grupo          text not null
                 check (grupo in ('gabinete','nucleo','secretaria','subprefeitura','camara','canal','outro')),
  orgao          text,
  cargo          text,
  nome           text not null,
  partido        text,
  telefone       text,
  whatsapp       text,
  email          text,
  endereco       text,
  rede_social    text,
  obs_fonte      text,                                       -- observação vinda do levantamento
  foto           text,                                       -- caminho relativo (fotos/<cidade>/<arquivo>.jpg)
  ordem          integer not null default 100,
  prioritario    boolean not null default false,
  vinculado_a    text references public.pessoas(id) on delete set null,  -- adjunto -> titular
  -- campos de trabalho da equipe
  status         text not null default 'nao_contatado'
                 check (status in ('nao_contatado','contato_iniciado','reuniao_realizada','aliado','resistente','sem_resposta')),
  responsavel_id uuid references public.usuarios(id) on delete set null,
  notas          text,
  atualizado_em  timestamptz not null default now(),
  atualizado_por uuid references public.usuarios(id) on delete set null
);
create index if not exists pessoas_cidade_idx on public.pessoas(cidade_id, ordem);

-- ---------------------------------------------------------------------
-- Interações: cada contato feito (ligação, reunião, WhatsApp...)
-- ---------------------------------------------------------------------
create table if not exists public.interacoes (
  id            uuid primary key default gen_random_uuid(),
  cidade_id     text not null references public.cidades(id) on delete cascade,
  pessoa_id     text references public.pessoas(id) on delete set null,
  usuario_id    uuid not null references public.usuarios(id),
  tipo          text not null check (tipo in ('reuniao','ligacao','whatsapp','email','visita','evento','nota')),
  data          timestamptz not null default now(),
  resumo        text not null,
  resultado     text check (resultado in ('positivo','neutro','negativo','sem_resposta')),
  proximo_passo text,
  criado_em     timestamptz not null default now()
);
create index if not exists interacoes_cidade_idx on public.interacoes(cidade_id, data desc);
create index if not exists interacoes_pessoa_idx on public.interacoes(pessoa_id, data desc);

-- ---------------------------------------------------------------------
-- Agendas: compromissos marcados por cada um nas cidades
-- ---------------------------------------------------------------------
create table if not exists public.agendas (
  id            uuid primary key default gen_random_uuid(),
  cidade_id     text not null references public.cidades(id) on delete cascade,
  usuario_id    uuid not null references public.usuarios(id),
  pessoa_ids    text[] not null default '{}',
  titulo        text not null,
  inicio        timestamptz not null,
  fim           timestamptz,
  local         text,
  status        text not null default 'agendado' check (status in ('agendado','realizado','cancelado')),
  obs           text,
  criado_em     timestamptz not null default now(),
  atualizado_em timestamptz not null default now()
);
create index if not exists agendas_inicio_idx on public.agendas(inicio);

-- ---------------------------------------------------------------------
-- Carimbo de atualização
-- ---------------------------------------------------------------------
create or replace function public.set_atualizado_em() returns trigger
language plpgsql as $$
begin
  new.atualizado_em := now();
  return new;
end $$;

drop trigger if exists trg_cidades_atualizado on public.cidades;
create trigger trg_cidades_atualizado before update on public.cidades for each row execute function public.set_atualizado_em();
drop trigger if exists trg_pessoas_atualizado on public.pessoas;
create trigger trg_pessoas_atualizado before update on public.pessoas for each row execute function public.set_atualizado_em();
drop trigger if exists trg_agendas_atualizado on public.agendas;
create trigger trg_agendas_atualizado before update on public.agendas for each row execute function public.set_atualizado_em();

-- ---------------------------------------------------------------------
-- Quando um usuário é criado no Auth, cria o perfil em public.usuarios.
-- O nome vem do campo "nome" dos metadados ou do início do e-mail;
-- a pessoa pode trocar o nome depois, dentro do sistema.
-- ---------------------------------------------------------------------
create or replace function public.handle_new_user() returns trigger
language plpgsql security definer set search_path = public as $$
declare
  n int;
  cores text[] := array['#1E304B','#E89C2A','#2E75B6','#2F9E6B','#8E44AD','#C0392B'];
begin
  select count(*) into n from public.usuarios;
  insert into public.usuarios (id, nome, cor)
  values (new.id,
          coalesce(nullif(new.raw_user_meta_data->>'nome',''), initcap(split_part(new.email,'@',1))),
          cores[(n % array_length(cores,1)) + 1])
  on conflict (id) do nothing;
  return new;
end $$;

drop trigger if exists on_auth_user_created on auth.users;
create trigger on_auth_user_created after insert on auth.users for each row execute function public.handle_new_user();

-- Perfis para usuários que já existiam antes deste script
insert into public.usuarios (id, nome)
select u.id, coalesce(nullif(u.raw_user_meta_data->>'nome',''), initcap(split_part(u.email,'@',1)))
from auth.users u left join public.usuarios p on p.id = u.id where p.id is null;

-- ---------------------------------------------------------------------
-- Segurança: só usuários autenticados (a equipe) leem e escrevem.
-- Sem login, ninguém vê nada.
-- ---------------------------------------------------------------------
alter table public.usuarios   enable row level security;
alter table public.cidades    enable row level security;
alter table public.pessoas    enable row level security;
alter table public.interacoes enable row level security;
alter table public.agendas    enable row level security;

drop policy if exists "equipe le usuarios" on public.usuarios;
create policy "equipe le usuarios" on public.usuarios for select to authenticated using (true);
drop policy if exists "usuario edita proprio perfil" on public.usuarios;
create policy "usuario edita proprio perfil" on public.usuarios for update to authenticated using (auth.uid() = id) with check (auth.uid() = id);
drop policy if exists "usuario cria proprio perfil" on public.usuarios;
create policy "usuario cria proprio perfil" on public.usuarios for insert to authenticated with check (auth.uid() = id);

drop policy if exists "equipe acessa cidades" on public.cidades;
create policy "equipe acessa cidades" on public.cidades for all to authenticated using (true) with check (true);
drop policy if exists "equipe acessa pessoas" on public.pessoas;
create policy "equipe acessa pessoas" on public.pessoas for all to authenticated using (true) with check (true);
drop policy if exists "equipe acessa interacoes" on public.interacoes;
create policy "equipe acessa interacoes" on public.interacoes for all to authenticated using (true) with check (true);
drop policy if exists "equipe acessa agendas" on public.agendas;
create policy "equipe acessa agendas" on public.agendas for all to authenticated using (true) with check (true);

-- ---------------------------------------------------------------------
-- Tempo real: publica as mudanças para quem estiver com o site aberto
-- ---------------------------------------------------------------------
do $$
begin
  if not exists (select 1 from pg_publication where pubname = 'supabase_realtime') then
    create publication supabase_realtime;
  end if;
end $$;
do $$
declare t text;
begin
  foreach t in array array['cidades','pessoas','interacoes','agendas','usuarios'] loop
    if not exists (select 1 from pg_publication_tables where pubname = 'supabase_realtime' and schemaname = 'public' and tablename = t) then
      execute format('alter publication supabase_realtime add table public.%I', t);
    end if;
  end loop;
end $$;
