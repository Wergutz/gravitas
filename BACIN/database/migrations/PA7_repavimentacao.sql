-- ============================================================
-- PA7 — App do Executor de Repavimentação
-- Execute no banco u278289683_vh_planeja (Hostinger MariaDB)
-- NOTA: ADD COLUMN IF NOT EXISTS funciona; ADD CONSTRAINT não aceita IF NOT EXISTS
-- ============================================================

-- Caminhamentos de repavimentação (similar a caminhamentos de rede)
CREATE TABLE IF NOT EXISTS `caminhamentos_repav` (
  `id`             INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `equipe_id`      INT          NOT NULL,
  `data_execucao`  DATE         NOT NULL,
  `status`         ENUM('rascunho','publicado','execucao','concluido') NOT NULL DEFAULT 'rascunho',
  `criado_por`     INT          NULL,
  `obs`            TEXT         NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_cam_repav_equipe_data` (`equipe_id`, `data_execucao`),
  INDEX `idx_cam_repav_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trechos do caminhamento de repavimentação
CREATE TABLE IF NOT EXISTS `caminhamentos_repav_trechos` (
  `id`               INT       NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `caminhamento_id`  INT       NOT NULL,
  `trecho_id`        INT       NOT NULL,
  `sequencia`        TINYINT   NOT NULL DEFAULT 1,
  `status`           ENUM('pendente','execucao','concluido') NOT NULL DEFAULT 'pendente',
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_crt_cam` (`caminhamento_id`),
  INDEX `idx_crt_trecho` (`trecho_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tipos de pavimento por trecho de caminhamento
CREATE TABLE IF NOT EXISTS `caminhamentos_repav_pavimentos` (
  `id`                      INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `caminhamento_trecho_id`  INT           NOT NULL,
  `tipo_pavimento`          VARCHAR(80)   NOT NULL,
  `espessura_cm`            DECIMAL(5,2)  NULL COMMENT 'Apenas para asfalto',
  INDEX `idx_crp_trecho` (`caminhamento_trecho_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Diário principal de repavimentação
CREATE TABLE IF NOT EXISTS `diarios_repav` (
  `id`              INT            NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `equipe_id`       INT            NOT NULL,
  `trecho_id`       INT            NOT NULL,
  `data`            DATE           NOT NULL,
  `autor_id`        INT            NOT NULL,
  `status`          ENUM('rascunho','enviado','aprovado') NOT NULL DEFAULT 'rascunho',
  `step_atual`      TINYINT        NOT NULL DEFAULT 0,
  `versao`          TINYINT        NOT NULL DEFAULT 1,
  `mat_ok`          TINYINT        NULL COMMENT '1=ok, 0=falta material',
  `mat_obs`         TEXT           NULL,
  `obs_final`       TEXT           NULL,
  `area_total_m2`   DECIMAL(10,2)  NOT NULL DEFAULT 0,
  `volume_asf_m3`   DECIMAL(10,3)  NOT NULL DEFAULT 0,
  `created_at`      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_diario_repav` (`equipe_id`, `trecho_id`, `data`, `versao`),
  INDEX `idx_dr_data` (`data`),
  INDEX `idx_dr_equipe` (`equipe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Presenças do diário de repavimentação
CREATE TABLE IF NOT EXISTS `diario_repav_presencas` (
  `id`             INT  NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `diario_id`      INT  NOT NULL,
  `funcionario_id` INT  NOT NULL,
  `status`         ENUM('presente','ausente','atrasou','saiu_cedo') NOT NULL DEFAULT 'presente',
  UNIQUE KEY `uk_drp_func` (`diario_id`, `funcionario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Equipamentos verificados no diário
CREATE TABLE IF NOT EXISTS `diario_repav_equipamentos` (
  `id`             INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `diario_id`      INT          NOT NULL,
  `equipamento_id` INT          NULL COMMENT 'FK para equipamentos_pesados ou leves',
  `tipo`           VARCHAR(10)  NOT NULL DEFAULT 'pesado',
  `nome`           VARCHAR(100) NOT NULL DEFAULT '',
  `status`         ENUM('ok','problema') NOT NULL DEFAULT 'ok',
  `foto`           VARCHAR(255) NULL,
  INDEX `idx_dre_diario` (`diario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cargas de asfalto com NF
CREATE TABLE IF NOT EXISTS `diario_repav_cargas` (
  `id`          INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `diario_id`   INT           NOT NULL,
  `sequencia`   TINYINT       NOT NULL DEFAULT 1,
  `numero_nf`   VARCHAR(50)   NULL,
  `massa_t`     DECIMAL(8,2)  NULL,
  `foto_carga`  VARCHAR(255)  NULL,
  `foto_nf`     VARCHAR(255)  NULL,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_drc_diario` (`diario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Áreas aplicadas por tipo de pavimento e trecho
CREATE TABLE IF NOT EXISTS `diario_repav_areas` (
  `id`             INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `diario_id`      INT           NOT NULL,
  `tipo_pavimento` VARCHAR(80)   NOT NULL,
  `sequencia`      TINYINT       NOT NULL DEFAULT 1,
  `base_m`         DECIMAL(8,2)  NOT NULL DEFAULT 0,
  `largura_m`      DECIMAL(8,2)  NOT NULL DEFAULT 0,
  `area_m2`        DECIMAL(10,2) NOT NULL DEFAULT 0,
  `espessura_m`    DECIMAL(5,3)  NULL COMMENT 'Apenas para asfalto',
  `volume_m3`      DECIMAL(10,3) NULL,
  INDEX `idx_dra_diario` (`diario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fotos por passo do diário
CREATE TABLE IF NOT EXISTS `diario_repav_fotos` (
  `id`          INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `diario_id`   INT          NOT NULL,
  `step_num`    TINYINT      NOT NULL,
  `filename`    VARCHAR(255) NOT NULL,
  `thumb`       VARCHAR(255) NULL,
  `lat`         DECIMAL(10,7) NULL,
  `lng`         DECIMAL(10,7) NULL,
  `captured_at` VARCHAR(30)  NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_drf_diario` (`diario_id`),
  INDEX `idx_drf_step` (`step_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar tipo_usuario 7 ao enum de usuarios (só se ainda não existir)
-- Não é necessário alterar o enum pois o campo tipo_usuario já suporta INT ou TINYINT
-- Verificar: a coluna tipo_usuario da tabela usuarios deve aceitar valor 7
-- Se for ENUM, execute o ALTER abaixo:
-- ALTER TABLE `usuarios` MODIFY COLUMN `tipo_usuario`
--   ENUM('1','2','3','4','5','6','7') NOT NULL DEFAULT '4';
-- Se for TINYINT ou INT, nenhuma alteração necessária.
