-- ============================================================
-- BACIN — Usuários iniciais
-- Banco: u278289683_BACIN
-- Senha temporária: Bacin@2026  (force_password_change = 1)
-- Execute via phpMyAdmin → aba SQL
-- ============================================================

SET NAMES utf8mb4;

INSERT INTO `usuarios` (`nome`, `email`, `senha`, `tipo_usuario`, `ativo`, `force_password_change`) VALUES
('master_bacin',        'master_bacin@bacin.local',      '$2y$12$FvGUexGl8Ceaq1eStzcgiOOv319/1DE5KvpevURMVqUfDsqeuG.Ki', 3, 1, 1),
('planejador_bacin',    'planejador_bacin@bacin.local',  '$2y$12$FvGUexGl8Ceaq1eStzcgiOOv319/1DE5KvpevURMVqUfDsqeuG.Ki', 4, 1, 1),
('planejador2_bacin',   'planejador2_bacin@bacin.local', '$2y$12$FvGUexGl8Ceaq1eStzcgiOOv319/1DE5KvpevURMVqUfDsqeuG.Ki', 4, 1, 1),
('executor_bacin',      'executor_bacin@bacin.local',    '$2y$12$FvGUexGl8Ceaq1eStzcgiOOv319/1DE5KvpevURMVqUfDsqeuG.Ki', 5, 1, 1),
('cliente_bacin',       'cliente_bacin@bacin.local',     '$2y$12$FvGUexGl8Ceaq1eStzcgiOOv319/1DE5KvpevURMVqUfDsqeuG.Ki', 6, 1, 1),
('executor_rep_bacin',  'executor_rep_bacin@bacin.local', '$2y$12$FvGUexGl8Ceaq1eStzcgiOOv319/1DE5KvpevURMVqUfDsqeuG.Ki', 7, 1, 1);
