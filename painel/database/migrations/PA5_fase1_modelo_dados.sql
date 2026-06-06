-- ============================================================
-- PA5 — Fase 1: Modelo de Dados — App do Executor
-- Banco: u278289683_vh_planeja  |  Data: 2026-06-06
-- ============================================================
-- SEGURO: apenas cria tabelas novas (CREATE TABLE IF NOT EXISTS).
-- NÃO modifica nem apaga dados existentes.
-- Execute via phpMyAdmin → aba SQL.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- 1. diarios_execucao — cabeçalho do RDO (1 por equipe/trecho/dia)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `diarios_execucao` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `equipe_id`   int(11)      NOT NULL,
  `trecho_id`   int(11)      NOT NULL,
  `data`        date         NOT NULL,
  `autor_id`    int(11)      NOT NULL COMMENT 'usuario executor responsável',
  `status`      enum('rascunho','enviado','aprovado') NOT NULL DEFAULT 'rascunho',
  `versao`      int(11)      NOT NULL DEFAULT 1,
  `step_atual`  tinyint(3)   NOT NULL DEFAULT 1 COMMENT 'último passo salvo (1-21)',
  `created_at`  timestamp    NULL DEFAULT current_timestamp(),
  `updated_at`  timestamp    NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_equipe_trecho_data_versao` (`equipe_id`, `trecho_id`, `data`, `versao`),
  KEY `idx_equipe_data`  (`equipe_id`, `data`),
  KEY `idx_trecho`       (`trecho_id`),
  KEY `idx_autor`        (`autor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. diario_presencas — por funcionário no dia
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `diario_presencas` (
  `id`             int(11)    NOT NULL AUTO_INCREMENT,
  `diario_id`      int(11)    NOT NULL,
  `funcionario_id` int(11)    NOT NULL,
  `status`         enum('presente','ausente','atrasou','saiu_cedo') NOT NULL DEFAULT 'presente',
  `obs`            varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_diario_func` (`diario_id`, `funcionario_id`),
  KEY `idx_diario` (`diario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. diario_fotos — fotos georreferenciadas com timestamp servidor
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `diario_fotos` (
  `id`                int(11)      NOT NULL AUTO_INCREMENT,
  `diario_id`         int(11)      NOT NULL,
  `step_num`          tinyint(3)   NOT NULL COMMENT 'passo do diário (1-21)',
  `arquivo`           varchar(255) NOT NULL,
  `thumb`             varchar(255) DEFAULT NULL,
  `lat`               decimal(10,7) DEFAULT NULL,
  `lng`               decimal(10,7) DEFAULT NULL,
  `timestamp_servidor` timestamp   NOT NULL DEFAULT current_timestamp(),
  `tipo`              varchar(50)  DEFAULT NULL COMMENT 'ex: sinalização, equipamento, reaterro...',
  PRIMARY KEY (`id`),
  KEY `idx_diario_step` (`diario_id`, `step_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. diario_interferencias — por trecho (várias por diário)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `diario_interferencias` (
  `id`             int(11)    NOT NULL AUTO_INCREMENT,
  `diario_id`      int(11)    NOT NULL,
  `tipo`           enum(
    'pedra',
    'agua_na_vala',
    'ramal_de_agua',
    'rede_de_agua',
    'rede_pluvial',
    'rompimento_de_rede',
    'rede_cloacal_existente',
    'rede_logica',
    'rede_eletrica',
    'outros'
  ) NOT NULL,
  `especificacao`  varchar(255) DEFAULT NULL,
  `lat`            decimal(10,7) DEFAULT NULL,
  `lng`            decimal(10,7) DEFAULT NULL,
  `foto_id`        int(11)      DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_diario` (`diario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. diario_reaterros — camadas de reaterro (várias por diário)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `diario_reaterros` (
  `id`           int(11)    NOT NULL AUTO_INCREMENT,
  `diario_id`    int(11)    NOT NULL,
  `tipo`         enum(
    'lastro_brita',
    'colchao_areia_po_brita',
    'reaterro_importado',
    'compactacao_importado',
    'reaterro_local',
    'compactacao_local',
    'base_brita_graduada',
    'compactacao_base'
  ) NOT NULL,
  `espessura_cm` decimal(6,2) DEFAULT NULL,
  `foto_id`      int(11)      DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_diario` (`diario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. diario_ramais — ramais executados no dia
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `diario_ramais` (
  `id`              int(11)    NOT NULL AUTO_INCREMENT,
  `diario_id`       int(11)    NOT NULL,
  `nro_residencia`  varchar(50)  DEFAULT NULL,
  `dimensao_pontao` varchar(50)  DEFAULT NULL,
  `ext_pista`       decimal(7,2) DEFAULT NULL COMMENT 'metros',
  `ext_calcada`     decimal(7,2) DEFAULT NULL COMMENT 'metros',
  PRIMARY KEY (`id`),
  KEY `idx_diario` (`diario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. diario_cargas — bota-fora, bota-espera e importado
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `diario_cargas` (
  `id`        int(11)    NOT NULL AUTO_INCREMENT,
  `diario_id` int(11)    NOT NULL,
  `tipo`      enum('bota_fora','bota_espera','importado') NOT NULL,
  `numero`    int(11)    NOT NULL DEFAULT 1 COMMENT 'número sequencial da carga no dia',
  `foto_id`   int(11)    DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_diario` (`diario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. diario_pontoes — pontões de espera de ramal
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `diario_pontoes` (
  `id`             int(11)    NOT NULL AUTO_INCREMENT,
  `diario_id`      int(11)    NOT NULL,
  `nro_residencia` varchar(50) DEFAULT NULL,
  `foto_id`        int(11)    DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_diario` (`diario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. diario_equipamentos — estado dos equipamentos no dia
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `diario_equipamentos` (
  `id`              int(11)    NOT NULL AUTO_INCREMENT,
  `diario_id`       int(11)    NOT NULL,
  `equipamento_id`  int(11)    NOT NULL,
  `tipo`            enum('leve','pesado') NOT NULL,
  `funcionando`     tinyint(1) NOT NULL DEFAULT 1,
  `obs`             varchar(255) DEFAULT NULL,
  `foto_id`         int(11)    DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_diario`      (`diario_id`),
  KEY `idx_equipamento` (`equipamento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10. diario_gps — posições início/fim e extensão calculada
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `diario_gps` (
  `id`                    int(11)       NOT NULL AUTO_INCREMENT,
  `diario_id`             int(11)       NOT NULL,
  `lat_inicio`            decimal(10,7) DEFAULT NULL,
  `lng_inicio`            decimal(10,7) DEFAULT NULL,
  `foto_inicio_id`        int(11)       DEFAULT NULL,
  `lat_fim`               decimal(10,7) DEFAULT NULL,
  `lng_fim`               decimal(10,7) DEFAULT NULL,
  `foto_fim_id`           int(11)       DEFAULT NULL,
  `extensao_calculada_m`  decimal(8,2)  DEFAULT NULL COMMENT 'haversine início→fim',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_diario` (`diario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Foreign keys
-- ------------------------------------------------------------
ALTER TABLE `diario_presencas`
  ADD CONSTRAINT `fk_dp_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao`(`id`) ON DELETE CASCADE;

ALTER TABLE `diario_fotos`
  ADD CONSTRAINT `fk_df_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao`(`id`) ON DELETE CASCADE;

ALTER TABLE `diario_interferencias`
  ADD CONSTRAINT `fk_di_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao`(`id`) ON DELETE CASCADE;

ALTER TABLE `diario_reaterros`
  ADD CONSTRAINT `fk_dr_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao`(`id`) ON DELETE CASCADE;

ALTER TABLE `diario_ramais`
  ADD CONSTRAINT `fk_dra_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao`(`id`) ON DELETE CASCADE;

ALTER TABLE `diario_cargas`
  ADD CONSTRAINT `fk_dc_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao`(`id`) ON DELETE CASCADE;

ALTER TABLE `diario_pontoes`
  ADD CONSTRAINT `fk_dpt_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao`(`id`) ON DELETE CASCADE;

ALTER TABLE `diario_equipamentos`
  ADD CONSTRAINT `fk_deq_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao`(`id`) ON DELETE CASCADE;

ALTER TABLE `diario_gps`
  ADD CONSTRAINT `fk_dgps_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao`(`id`) ON DELETE CASCADE;

SET foreign_key_checks = 1;
