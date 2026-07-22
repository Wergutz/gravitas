-- ============================================================
-- Cheron Camargo — Usuários iniciais
-- Banco: u278289683_CHERONCAMARGO
-- Senha temporária: Cheron@2026  (force_password_change = 1)
-- Execute via phpMyAdmin → aba SQL
-- ============================================================

SET NAMES utf8mb4;

INSERT INTO `usuarios` (`nome`, `email`, `senha`, `tipo_usuario`, `ativo`, `force_password_change`) VALUES
('master_cc',        'master_cc@cheroncamargo.local',        '$2y$12$YhjC4ZZpPadPtXGJO1qbAeeczYKLaJg1I3xVuU3aPMsjEttNZjs6K', 3, 1, 1),
('planejador_cc',    'planejador_cc@cheroncamargo.local',    '$2y$12$YhjC4ZZpPadPtXGJO1qbAeeczYKLaJg1I3xVuU3aPMsjEttNZjs6K', 4, 1, 1),
('planejador2_cc',   'planejador2_cc@cheroncamargo.local',   '$2y$12$YhjC4ZZpPadPtXGJO1qbAeeczYKLaJg1I3xVuU3aPMsjEttNZjs6K', 4, 1, 1),
('executor_cc',      'executor_cc@cheroncamargo.local',      '$2y$12$YhjC4ZZpPadPtXGJO1qbAeeczYKLaJg1I3xVuU3aPMsjEttNZjs6K', 5, 1, 1),
('cliente_cc',       'cliente_cc@cheroncamargo.local',       '$2y$12$YhjC4ZZpPadPtXGJO1qbAeeczYKLaJg1I3xVuU3aPMsjEttNZjs6K', 6, 1, 1),
('executor_rep_cc',  'executor_rep_cc@cheroncamargo.local',  '$2y$12$YhjC4ZZpPadPtXGJO1qbAeeczYKLaJg1I3xVuU3aPMsjEttNZjs6K', 7, 1, 1);
