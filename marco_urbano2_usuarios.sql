-- =====================================================================
-- Usuários iniciais do banco marco_urbano2 (VisionHub Locar)
-- =====================================================================
--
-- Artefato de referência, fora do deploy — rodar manualmente via
-- phpMyAdmin depois de aplicar marco_urbano2_schema.sql no banco novo
-- `u278289683_marco_urbano2`.
--
-- Tabela `usuarios`: id, nome, usuario, senha, tipo_usuario, ativo,
-- criado_em, atualizado_em (login por usuário/senha, não e-mail).
-- Não existe coluna de "forçar troca de senha" nesta tabela — repasse
-- as senhas temporárias abaixo para os usuários trocarem manualmente
-- assim que acessarem pela primeira vez.
--
-- Hashes gerados com password_hash(..., PASSWORD_DEFAULT) via PHP CLI
-- (PHP 8.4, algoritmo bcrypt $2y$).
--
-- Usuário: planejador_isaias
--   Senha temporária: Locar@2026Pj
--   tipo_usuario = 3 (PLANEJADOR)
--
-- Usuário: executor2_mu
--   Senha temporária: Locar@2026Ex
--   tipo_usuario = 4 (EXECUTOR) — perfil ainda sem tela implementada
--   (pasta executor/ vazia), usuário criado mesmo assim a pedido.
-- =====================================================================

INSERT INTO `usuarios` (`nome`, `usuario`, `senha`, `tipo_usuario`, `ativo`)
VALUES
(
    'Isaías',
    'planejador_isaias',
    '$2y$12$NiBaT/CMbFjNhhA61BfTMODbcx94K5tFi46.ywjATlRO16ImGXo7W',
    3,
    1
),
(
    'Executor 2',
    'executor2_mu',
    '$2y$12$1eqJqfYUoUqv31M5Egxr2eC6M41Ik/yOl0DuCjACvvJgWP2LZ7z/2',
    4,
    1
);
