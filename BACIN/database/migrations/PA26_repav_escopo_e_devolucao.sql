-- ============================================================
-- PA26 — Repavimentação com escopo (rede × ramais), conclusão
--        pelo campo e devolução pelo Planejador
-- ------------------------------------------------------------
-- Decisões do Alex (19/09/2026), depois da rodada de testes por perfil:
--
--  1. Quem conclui é quem executou:
--     - a rede é concluída pelo Executor de Rede, ao encerrar o diário;
--     - a repavimentação é concluída pelo Executor de Pavimento, ao
--       encerrar a medição.
--     No Painel existe UM único lugar (ficha do trecho) para o Planejador
--     DEVOLVER a etapa à equipe, com motivo — registrado em
--     `trecho_devolucoes`.
--
--  2. A repavimentação passa a ter DOIS registros independentes por trecho,
--     executados pela MESMA equipe, com áreas individualizadas:
--     - escopo 'rede'   → reposição sobre a vala da rede;
--     - escopo 'ramais' → reposição sobre as valas dos ramais.
--
--  3. Na repavimentação de ramais, cada lançamento é discriminado por
--     LOCAL (via ou calçada), com o seu tipo de pavimento e a sua área, e
--     pode apontar para o ramal (`ramais.id`, do app executor-ramais) ou,
--     na falta dele, para o número do imóvel.
-- ============================================================

-- ── Diário de repavimentação: escopo ────────────────────────
ALTER TABLE `diarios_repav`
  ADD COLUMN `escopo` enum('rede','ramais') NOT NULL DEFAULT 'rede'
      COMMENT 'rede = vala da rede; ramais = valas dos ramais' AFTER `trecho_id`,
  ADD KEY `idx_trecho_escopo_data` (`trecho_id`,`escopo`,`data`);

-- ── Áreas: local, vínculo com o ramal e nº do imóvel ────────
ALTER TABLE `diario_repav_areas`
  ADD COLUMN `local` enum('via','calcada') NOT NULL DEFAULT 'via'
      COMMENT 'onde a área foi reposta' AFTER `tipo_pavimento`,
  ADD COLUMN `ramal_id` int(11) DEFAULT NULL
      COMMENT 'ramais.id (app executor-ramais), quando escopo = ramais' AFTER `local`,
  ADD COLUMN `numero_imovel` varchar(30) DEFAULT NULL
      COMMENT 'nº do imóvel do ramal, quando não há vínculo' AFTER `ramal_id`,
  ADD KEY `idx_ramal` (`ramal_id`);

-- ── Trechos: fila própria da repavimentação de ramais ───────
ALTER TABLE `trechos`
  ADD COLUMN `status_repav_ramais` enum('aguardando','execucao','medido') DEFAULT NULL
      COMMENT 'fila da repavimentação das valas de ramal' AFTER `status_repav`,
  ADD COLUMN `rede_concluida_em` datetime DEFAULT NULL
      COMMENT 'quando o Executor de Rede encerrou — ordena a fila de repavimentação' AFTER `status_repav_ramais`,
  ADD COLUMN `ramais_concluidos_em` datetime DEFAULT NULL
      COMMENT 'quando a última frente de ramais foi enviada' AFTER `rede_concluida_em`;

-- ── Log de devolução (único caminho de volta) ───────────────
CREATE TABLE IF NOT EXISTS `trecho_devolucoes` (
  `id`         int(11) NOT NULL AUTO_INCREMENT,
  `trecho_id`  int(11) NOT NULL,
  `etapa`      enum('rede','repav_rede','repav_ramais') NOT NULL,
  `motivo`     varchar(255) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'planejador que devolveu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_trecho` (`trecho_id`),
  KEY `idx_etapa` (`etapa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trechos com a rede já concluída antes desta migração entram na fila
-- com a data do último diário de rede enviado.
UPDATE `trechos` t
   SET t.`rede_concluida_em` = (
         SELECT MAX(de.`updated_at`) FROM `diarios_execucao` de
          WHERE de.`trecho_id` = t.`id` AND de.`status` = 'enviado'
       )
 WHERE t.`status_rede` = 'concluido' AND t.`rede_concluida_em` IS NULL;

-- ── Unicidade do diário passa a considerar o escopo ─────────
-- Sem isso, a mesma equipe não consegue enviar a repavimentação da rede e a
-- dos ramais do MESMO trecho no MESMO dia (a chave antiga era
-- equipe+trecho+data+versão, e as duas medições disputavam a mesma versão).
ALTER TABLE `diarios_repav`
  DROP INDEX `uk_diario_repav`,
  ADD UNIQUE KEY `uk_diario_repav` (`equipe_id`,`trecho_id`,`escopo`,`data`,`versao`);
