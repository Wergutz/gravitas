-- ============================================================
-- PA5 — Fase 3: Integrações Executor → Planejador
-- Banco: u278289683_vh_planeja  |  Data: 2026-06-06
-- ============================================================
-- SEGURO: ADD COLUMN IF NOT EXISTS + CREATE TABLE IF NOT EXISTS.
-- Execute via phpMyAdmin → aba SQL.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- 1. diarios_execucao — campos do passo 3 (estoque na frente)
-- ------------------------------------------------------------
ALTER TABLE `diarios_execucao`
  ADD COLUMN IF NOT EXISTS `step3_estoque_ok`          tinyint(1)    DEFAULT NULL COMMENT '1=tem tudo, 0=falta',
  ADD COLUMN IF NOT EXISTS `step3_materiais_faltando`  text          DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `extensao_gps_m`            decimal(8,2)  DEFAULT NULL COMMENT 'cópia de diario_gps.extensao_calculada_m ao encerrar';

-- ------------------------------------------------------------
-- 2. caminhamento_trechos — extensão real executada (do GPS do Executor)
-- ------------------------------------------------------------
ALTER TABLE `caminhamento_trechos`
  ADD COLUMN IF NOT EXISTS `extensao_executada_m` decimal(8,2) DEFAULT NULL COMMENT 'extensão medida por GPS pelo Executor';

-- ------------------------------------------------------------
-- 3. equipamentos_pesados — status de manutenção
-- ------------------------------------------------------------
ALTER TABLE `equipamentos_pesados`
  ADD COLUMN IF NOT EXISTS `status_manutencao` enum('ok','manutencao') NOT NULL DEFAULT 'ok',
  ADD COLUMN IF NOT EXISTS `obs_manutencao`    varchar(255) DEFAULT NULL;

-- ------------------------------------------------------------
-- 4. equipamentos_leves — status de manutenção
-- ------------------------------------------------------------
ALTER TABLE `equipamentos_leves`
  ADD COLUMN IF NOT EXISTS `status_manutencao` enum('ok','manutencao') NOT NULL DEFAULT 'ok',
  ADD COLUMN IF NOT EXISTS `obs_manutencao`    varchar(255) DEFAULT NULL;

-- ------------------------------------------------------------
-- 5. alertas_falta_material — fila de alertas gerados pelo Executor
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `alertas_falta_material` (
  `id`                  int(11)      NOT NULL AUTO_INCREMENT,
  `diario_id`           int(11)      NOT NULL,
  `equipe_id`           int(11)      NOT NULL,
  `trecho_id`           int(11)      NOT NULL,
  `data`                date         NOT NULL,
  `materiais_faltando`  text         NOT NULL,
  `resolvido`           tinyint(1)   NOT NULL DEFAULT 0,
  `resolvido_em`        timestamp    NULL DEFAULT NULL,
  `resolvido_por`       int(11)      DEFAULT NULL,
  `created_at`          timestamp    NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_diario`   (`diario_id`),
  KEY `idx_equipe`   (`equipe_id`),
  KEY `idx_resolvido`(`resolvido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
