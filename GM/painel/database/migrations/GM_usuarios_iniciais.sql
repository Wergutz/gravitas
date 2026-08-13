-- ============================================================
-- GM SERVIÇOS — Usuários iniciais
-- Banco: u278289683_GM
-- Senha temporária: Gm@2026  (force_password_change = 1)
-- Execute via phpMyAdmin → aba SQL
-- ============================================================

SET NAMES utf8mb4;

INSERT INTO `usuarios` (`nome`, `email`, `senha`, `tipo_usuario`, `ativo`, `force_password_change`) VALUES
('master_gm',        'master_gm@gm.local',        '$2y$12$zs3DugbUJIJB1Ket7lPWIOlnLNLv9Msmn0tgoyoERIXKMponigfDm', 3, 1, 1),
('planejador_gm',    'planejador_gm@gm.local',    '$2y$12$zs3DugbUJIJB1Ket7lPWIOlnLNLv9Msmn0tgoyoERIXKMponigfDm', 4, 1, 1),
('planejador2_gm',   'planejador2_gm@gm.local',   '$2y$12$zs3DugbUJIJB1Ket7lPWIOlnLNLv9Msmn0tgoyoERIXKMponigfDm', 4, 1, 1),
('executor_gm',      'executor_gm@gm.local',      '$2y$12$zs3DugbUJIJB1Ket7lPWIOlnLNLv9Msmn0tgoyoERIXKMponigfDm', 5, 1, 1),
('cliente_gm',       'cliente_gm@gm.local',       '$2y$12$zs3DugbUJIJB1Ket7lPWIOlnLNLv9Msmn0tgoyoERIXKMponigfDm', 6, 1, 1),
('executor_rep_gm',  'executor_rep_gm@gm.local',  '$2y$12$zs3DugbUJIJB1Ket7lPWIOlnLNLv9Msmn0tgoyoERIXKMponigfDm', 7, 1, 1);
