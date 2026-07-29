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
-- ATUALIZAÇÃO: a senha foi padronizada para "Marco@2026", a mesma
-- senha temporária padrão já usada nos usuários do marco_urbano
-- (ver marco_urbano/painel/database/migrations/MU_usuarios_iniciais.sql).
-- Ver marco_urbano2_atualizar_senhas.sql para o UPDATE aplicado em produção.
--
-- Usuário: planejador_isaias
--   Senha: Marco@2026
--   tipo_usuario = 3 (PLANEJADOR)
--
-- Usuário: executor2_mu
--   Senha: Marco@2026
--   tipo_usuario = 4 (EXECUTOR) — perfil ainda sem tela implementada
--   (pasta executor/ vazia), usuário criado mesmo assim a pedido.
-- =====================================================================

INSERT INTO `usuarios` (`nome`, `usuario`, `senha`, `tipo_usuario`, `ativo`)
VALUES
(
    'Isaías',
    'planejador_isaias',
    '$2y$12$ajTNZxO8rdvfrrdqxA7UeuNPUWn1l/SR5wdToMsC7fojqACajODb.',
    3,
    1
),
(
    'Executor 2',
    'executor2_mu',
    '$2y$12$ajTNZxO8rdvfrrdqxA7UeuNPUWn1l/SR5wdToMsC7fojqACajODb.',
    4,
    1
);
