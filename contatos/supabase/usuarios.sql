-- =====================================================================
--  Gravitas · Contatos com Prefeituras — usuários da equipe
--  Cria as quatro contas com senha provisória (já criptografada; a senha
--  em texto só o Alex tem). Rode UMA vez no SQL Editor, DEPOIS de schema.sql.
--  Se um e-mail já existir, ele é ignorado. Pode editar os e-mails abaixo
--  antes de rodar; as senhas continuam valendo.
-- =====================================================================
create extension if not exists pgcrypto;

do $$
declare
  u record;
  uid uuid;
begin
  for u in
    select * from (values
    ('alexgenehr@gmail.com', 'Alex', '$2a$10$5TN67c2Fbm28./GxFZ3Tw.Q3HyDvxl6IypARNIyd5lGulo1/kst96'),
    ('carlo@gravitas.net.br', 'Carlo', '$2a$10$hkmTD1u7ubkCnXjmqrmeHe82Kk0/tnIYA8Kwjfs01lsgCja9E8rTy'),
    ('gelson@gravitas.net.br', 'Gelson', '$2a$10$2.46L/0QNkM3ZBrdMJ8HM.qBDZDK6ri7HWCPxnBRFS67fm39PAJl2'),
    ('rafael@gravitas.net.br', 'Rafael', '$2a$10$kDlnaOblNrc2zCyvgqkVGuVkqgfmX8Ilx49LGZ3XcszyT8vtrxf9O')
    ) as t(email, nome, hash)
  loop
    if exists (select 1 from auth.users where lower(email) = lower(u.email)) then
      raise notice 'já existe: %', u.email;
      continue;
    end if;
    uid := gen_random_uuid();
    insert into auth.users (instance_id, id, aud, role, email, encrypted_password, email_confirmed_at,
                            raw_app_meta_data, raw_user_meta_data, created_at, updated_at,
                            confirmation_token, recovery_token, email_change, email_change_token_new, email_change_token_current)
    values ('00000000-0000-0000-0000-000000000000', uid, 'authenticated', 'authenticated', u.email, u.hash, now(),
            '{"provider":"email","providers":["email"]}'::jsonb, jsonb_build_object('nome', u.nome, 'senha_provisoria', true), now(), now(),
            '', '', '', '', '');
    insert into auth.identities (id, user_id, provider_id, identity_data, provider, last_sign_in_at, created_at, updated_at)
    values (gen_random_uuid(), uid, uid::text,
            jsonb_build_object('sub', uid::text, 'email', u.email, 'email_verified', true, 'phone_verified', false),
            'email', now(), now(), now());
    raise notice 'criado: % (%)', u.email, u.nome;
  end loop;
end $$;

-- Conferência: deve listar os quatro, com perfil criado e confirmados
select a.email, p.nome, a.email_confirmed_at is not null as confirmado, p.cor
from auth.users a left join public.usuarios p on p.id = a.id
order by a.created_at;
