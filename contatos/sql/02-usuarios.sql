-- =====================================================================
--  Gravitas · Contatos com Prefeituras — contas da equipe (MySQL)
--  Rode DEPOIS de 01-schema.sql. Pode rodar de novo: quem já existe
--  é ignorado, e as senhas de quem já está lá não são mexidas.
--
--  SOBRE AS SENHAS
--  Os hashes abaixo são os mesmos que vieram no arquivo de instalação
--  do Supabase, em formato bcrypt $2a$. O password_verify() do PHP
--  aceita esse formato — conferido —, então as senhas que vocês já têm
--  continuam valendo, sem precisar redefinir nada.
--
--  Todos entram com trocar_senha = 1: no primeiro acesso o sistema pede
--  uma senha nova, e a partir daí o hash é regravado no formato $2y$ do
--  PHP. Quem preferir pode mudar para 0 antes de rodar.
--
--  Os identificadores são fixos (UUID escrito à mão) para o script poder
--  ser rodado de novo sem gerar conta duplicada.
-- =====================================================================

SET NAMES utf8mb4;

INSERT INTO `contatos_usuarios` (`id`, `email`, `nome`, `senha`, `cor`, `ativo`, `trocar_senha`) VALUES
  ('a1e0c0de-0001-4000-8000-000000000001', 'alexgenehr@gmail.com',   'Alex',   '$2a$10$5TN67c2Fbm28./GxFZ3Tw.Q3HyDvxl6IypARNIyd5lGulo1/kst96', '#1E304B', 1, 1),
  ('a1e0c0de-0002-4000-8000-000000000002', 'carlo@gravitas.net.br',  'Carlo',  '$2a$10$hkmTD1u7ubkCnXjmqrmeHe82Kk0/tnIYA8Kwjfs01lsgCja9E8rTy', '#E89C2A', 1, 1),
  ('a1e0c0de-0003-4000-8000-000000000003', 'gelson@gravitas.net.br', 'Gelson', '$2a$10$2.46L/0QNkM3ZBrdMJ8HM.qBDZDK6ri7HWCPxnBRFS67fm39PAJl2', '#2E75B6', 1, 1),
  ('a1e0c0de-0004-4000-8000-000000000004', 'rafael@gravitas.net.br', 'Rafael', '$2a$10$kDlnaOblNrc2zCyvgqkVGuVkqgfmX8Ilx49LGZ3XcszyT8vtrxf9O', '#2F9E6B', 1, 1)
ON DUPLICATE KEY UPDATE `id` = `id`;   -- não altera nada de quem já existe

-- Conferência: deve listar os quatro.
SELECT `email`, `nome`, `cor`, `ativo`, `trocar_senha` FROM `contatos_usuarios` ORDER BY `nome`;
