-- ============================================================
-- Marco Urbano — Usuários iniciais
-- Banco: u278289683_marco_urbano
-- Senha temporária: Marco@2026  (force_password_change = 1)
-- Execute via phpMyAdmin → aba SQL
-- ============================================================

SET NAMES utf8mb4;

INSERT INTO `usuarios` (`nome`, `email`, `senha`, `tipo_usuario`, `ativo`, `force_password_change`) VALUES
('master_mu',        'master_mu@marcourbano.local',       '$2y$12$TFJEualTEOC7MYLc.ma8H.3lmz8sJtYr48fxiji.gCOe80.4gCjOS', 3, 1, 1),
('planejador_mu',    'planejador_mu@marcourbano.local',   '$2y$12$TFJEualTEOC7MYLc.ma8H.3lmz8sJtYr48fxiji.gCOe80.4gCjOS', 4, 1, 1),
('isaias_mu',        'isaias_mu@marcourbano.local',       '$2y$12$TFJEualTEOC7MYLc.ma8H.3lmz8sJtYr48fxiji.gCOe80.4gCjOS', 4, 1, 1),
('executor_mu',      'executor_mu@marcourbano.local',     '$2y$12$TFJEualTEOC7MYLc.ma8H.3lmz8sJtYr48fxiji.gCOe80.4gCjOS', 5, 1, 1),
('cliente_mu',       'cliente_mu@marcourbano.local',      '$2y$12$TFJEualTEOC7MYLc.ma8H.3lmz8sJtYr48fxiji.gCOe80.4gCjOS', 6, 1, 1),
('executor_rep_mu',  'executor_rep_mu@marcourbano.local', '$2y$12$TFJEualTEOC7MYLc.ma8H.3lmz8sJtYr48fxiji.gCOe80.4gCjOS', 7, 1, 1);
