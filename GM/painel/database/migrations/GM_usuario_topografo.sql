-- ============================================================
-- GM SERVIÇOS — Usuário Topógrafo (nível 8)
-- Banco: u278289683_GM
-- Senha temporária: Gm@2026  (force_password_change = 1)
-- Execute via phpMyAdmin → aba SQL
--
-- Complementa GM_usuarios_iniciais.sql, que não criou o nível 8.
-- Destino no login: /GM/topografo/
-- ============================================================

SET NAMES utf8mb4;

INSERT INTO `usuarios` (`nome`, `email`, `senha`, `tipo_usuario`, `ativo`, `force_password_change`) VALUES
('topografo_gm', 'topografo_gm@gm.local', '$2y$12$LMSRcvbcRR/e1hVLdrPAXe4wnp/Fe3VE1Cc1Uz01g6rWH9gToHq6C', 8, 1, 1);
