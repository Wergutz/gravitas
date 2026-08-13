-- ============================================================
-- PA4 — Fase 1: Modelo de Dados
-- Banco: u278289683_vh_planeja  |  Data: 2026-06-06
-- ============================================================
-- SEGURO: apenas cria tabelas novas e adiciona colunas.
-- NÃO apaga nem modifica dados existentes.
-- Execute via phpMyAdmin → aba SQL.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- 1. Colunas novas em equipamentos existentes
-- ------------------------------------------------------------

ALTER TABLE `equipamentos_pesados`
  ADD COLUMN IF NOT EXISTS `horimetro_atual`              decimal(10,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `proxima_manutencao_horimetro` decimal(10,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `proxima_manutencao_data`      date          DEFAULT NULL;

ALTER TABLE `equipamentos_leves`
  ADD COLUMN IF NOT EXISTS `km_atual`               decimal(10,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `proxima_manutencao_km`  decimal(10,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `proxima_manutencao_data` date         DEFAULT NULL;

-- ------------------------------------------------------------
-- 2. trechos — estoque de serviço (substitui planejamento_trechos p/ PA4)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trechos` (
  `id`               int(11)       NOT NULL AUTO_INCREMENT,
  `bacia`            varchar(100)  DEFAULT NULL,
  `pv_montante`      varchar(50)   NOT NULL,
  `pv_jusante`       varchar(50)   DEFAULT NULL,
  `tipo_pi_montante` varchar(50)   DEFAULT NULL,
  `extensao`         decimal(8,2)  DEFAULT NULL  COMMENT 'metros',
  `profundidade_media` decimal(6,2) DEFAULT NULL COMMENT 'metros',
  `dn`               varchar(30)   DEFAULT NULL  COMMENT 'ex: 200 PVC',
  `rua`              varchar(150)  DEFAULT NULL,
  `cidade`           varchar(100)  DEFAULT NULL,
  `contrato`         varchar(100)  DEFAULT NULL,
  `ramais`           int(11)       DEFAULT 0,
  `status_rede`      enum('livre','programado','execucao','concluido') NOT NULL DEFAULT 'livre',
  `status_repav`     enum('aguardando','execucao','medido')            DEFAULT NULL,
  `criado_por`       int(11)       DEFAULT NULL,
  `created_at`       timestamp     NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status_rede`  (`status_rede`),
  KEY `idx_status_repav` (`status_repav`),
  KEY `idx_criado_por`   (`criado_por`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. ordens_servico — PDF de OS vinculado a trecho (com histórico de versões)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ordens_servico` (
  `id`           int(11)       NOT NULL AUTO_INCREMENT,
  `trecho_id`    int(11)       NOT NULL,
  `topografo`    varchar(100)  DEFAULT NULL,
  `data_os`      date          DEFAULT NULL,
  `arquivo_pdf`  varchar(255)  NOT NULL,
  `versao`       int(11)       NOT NULL DEFAULT 1,
  `ativa`        tinyint(1)    NOT NULL DEFAULT 1 COMMENT '1=versão atual, 0=histórico',
  `criado_por`   int(11)       DEFAULT NULL,
  `created_at`   timestamp     NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_trecho_ativa` (`trecho_id`, `ativa`),
  KEY `idx_criado_por`   (`criado_por`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. caminhamentos — programação diária por equipe
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `caminhamentos` (
  `id`             int(11)      NOT NULL AUTO_INCREMENT,
  `equipe_id`      int(11)      NOT NULL,
  `planejador_id`  int(11)      NOT NULL,
  `data_execucao`  date         NOT NULL,
  `status`         enum('rascunho','publicado','execucao','concluido') NOT NULL DEFAULT 'rascunho',
  `observacoes`    text         DEFAULT NULL,
  `criado_em`      timestamp    NULL DEFAULT current_timestamp(),
  `atualizado_em`  timestamp    NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_equipe`      (`equipe_id`),
  KEY `idx_planejador`  (`planejador_id`),
  KEY `idx_data_status` (`data_execucao`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. caminhamento_trechos — trechos ordenados dentro de um caminhamento
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `caminhamento_trechos` (
  `id`              int(11)   NOT NULL AUTO_INCREMENT,
  `caminhamento_id` int(11)   NOT NULL,
  `trecho_id`       int(11)   NOT NULL,
  `sequencia`       int(11)   NOT NULL DEFAULT 1,
  `status`          enum('pendente','execucao','concluido') NOT NULL DEFAULT 'pendente',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_caminhamento_trecho` (`caminhamento_id`, `trecho_id`),
  KEY `idx_trecho` (`trecho_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. funcionario_documentos — ASO, NRs, integrações com PDF e validade
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `funcionario_documentos` (
  `id`              int(11)       NOT NULL AUTO_INCREMENT,
  `funcionario_id`  int(11)       NOT NULL,
  `tipo`            varchar(50)   NOT NULL COMMENT 'ASO, NR06, NR10, NR18, NR33, NR35, INTEGRACAO_CORSAN, SERTRAS...',
  `data_emissao`    date          DEFAULT NULL,
  `data_validade`   date          DEFAULT NULL,
  `arquivo_pdf`     varchar(255)  DEFAULT NULL,
  `criado_em`       timestamp     NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_funcionario` (`funcionario_id`),
  KEY `idx_validade`    (`data_validade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. equipamento_documentos — documentos de equipamentos leves e pesados
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `equipamento_documentos` (
  `id`                int(11)      NOT NULL AUTO_INCREMENT,
  `tipo_equipamento`  enum('leve','pesado') NOT NULL,
  `equipamento_id`    int(11)      NOT NULL,
  `tipo_documento`    varchar(80)  NOT NULL COMMENT 'ex: CRLV, Seguro, Inspecao, Laudo',
  `data_emissao`      date         DEFAULT NULL,
  `data_validade`     date         DEFAULT NULL,
  `arquivo_pdf`       varchar(255) DEFAULT NULL,
  `criado_em`         timestamp    NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_equipamento` (`tipo_equipamento`, `equipamento_id`),
  KEY `idx_validade`    (`data_validade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. equipamento_manutencoes — histórico de manutenções
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `equipamento_manutencoes` (
  `id`                        int(11)       NOT NULL AUTO_INCREMENT,
  `tipo_equipamento`          enum('leve','pesado') NOT NULL,
  `equipamento_id`            int(11)       NOT NULL,
  `tipo`                      enum('preventiva','corretiva') NOT NULL DEFAULT 'preventiva',
  `descricao`                 text          DEFAULT NULL,
  `data_manutencao`           date          NOT NULL,
  `horimetro_km_na_data`      decimal(10,2) DEFAULT NULL,
  `proxima_previsao_km`       decimal(10,2) DEFAULT NULL,
  `proxima_previsao_data`     date          DEFAULT NULL,
  `custo`                     decimal(10,2) DEFAULT NULL,
  `criado_por`                int(11)       DEFAULT NULL,
  `criado_em`                 timestamp     NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_equipamento` (`tipo_equipamento`, `equipamento_id`),
  KEY `idx_data`        (`data_manutencao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. materiais_catalogo — catálogo de materiais
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `materiais_catalogo` (
  `id`               int(11)       NOT NULL AUTO_INCREMENT,
  `codigo`           varchar(50)   DEFAULT NULL,
  `nome`             varchar(150)  NOT NULL,
  `unidade`          varchar(20)   NOT NULL DEFAULT 'un' COMMENT 'un, m, m², m³, kg, l, cj',
  `estoque_minimo`   decimal(10,3) NOT NULL DEFAULT 0,
  `ativo`            tinyint(1)    NOT NULL DEFAULT 1,
  `created_at`       timestamp     NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10. materiais_estoque — posição atual do estoque
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `materiais_estoque` (
  `id`                   int(11)       NOT NULL AUTO_INCREMENT,
  `material_id`          int(11)       NOT NULL,
  `quantidade_fisica`    decimal(10,3) NOT NULL DEFAULT 0,
  `quantidade_reservada` decimal(10,3) NOT NULL DEFAULT 0,
  `updated_at`           timestamp     NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_material` (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 11. materiais_movimentos — entradas, reservas, baixas e ajustes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `materiais_movimentos` (
  `id`               int(11)       NOT NULL AUTO_INCREMENT,
  `material_id`      int(11)       NOT NULL,
  `tipo`             enum('entrada','reserva','baixa','ajuste') NOT NULL,
  `quantidade`       decimal(10,3) NOT NULL,
  `referencia_tipo`  varchar(50)   DEFAULT NULL COMMENT 'caminhamento, trecho, ajuste_manual',
  `referencia_id`    int(11)       DEFAULT NULL,
  `observacao`       varchar(255)  DEFAULT NULL,
  `usuario_id`       int(11)       NOT NULL,
  `criado_em`        timestamp     NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_material`   (`material_id`),
  KEY `idx_tipo`       (`tipo`),
  KEY `idx_usuario`    (`usuario_id`),
  KEY `idx_referencia` (`referencia_tipo`, `referencia_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 12. trecho_materiais — materiais alocados por trecho (lançamento manual)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trecho_materiais` (
  `id`           int(11)       NOT NULL AUTO_INCREMENT,
  `trecho_id`    int(11)       NOT NULL,
  `material_id`  int(11)       NOT NULL,
  `quantidade`   decimal(10,3) NOT NULL,
  `observacao`   varchar(255)  DEFAULT NULL,
  `criado_por`   int(11)       DEFAULT NULL,
  `criado_em`    timestamp     NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_trecho_material` (`trecho_id`, `material_id`),
  KEY `idx_material` (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 13. medicoes_repavimentacao — medição por trecho
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `medicoes_repavimentacao` (
  `id`          int(11)   NOT NULL AUTO_INCREMENT,
  `trecho_id`   int(11)   NOT NULL,
  `status`      enum('rascunho','concluida') NOT NULL DEFAULT 'rascunho',
  `observacoes` text      DEFAULT NULL,
  `criado_por`  int(11)   DEFAULT NULL,
  `criado_em`   timestamp NULL DEFAULT current_timestamp(),
  `updated_at`  timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_trecho` (`trecho_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 14. medicao_pavimentos — N pavimentos por medição
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `medicao_pavimentos` (
  `id`              int(11)      NOT NULL AUTO_INCREMENT,
  `medicao_id`      int(11)      NOT NULL,
  `tipo_pavimento`  enum(
    'paralelepipedo_regular','paralelepipedo_irregular',
    'bloco_concreto','asfalto','asfalto_paralelepipedo',
    'chao_batido','calcada'
  ) NOT NULL,
  `espessura_cm`    decimal(6,2) DEFAULT NULL COMMENT 'obrigatório para asfalto',
  `ordem`           int(11)      NOT NULL DEFAULT 1,
  `criado_em`       timestamp    NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_medicao` (`medicao_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 15. medicao_pavimento_linhas — linhas de dimensão (comprimento × largura)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `medicao_pavimento_linhas` (
  `id`            int(11)       NOT NULL AUTO_INCREMENT,
  `pavimento_id`  int(11)       NOT NULL,
  `comprimento`   decimal(8,2)  NOT NULL,
  `largura`       decimal(8,2)  NOT NULL,
  `sequencia`     int(11)       NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_pavimento` (`pavimento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 16. medicao_fotos — fotos antes/durante/depois + croqui
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `medicao_fotos` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `medicao_id`  int(11)      NOT NULL,
  `tipo`        enum('antes','durante','depois','croqui') NOT NULL,
  `arquivo`     varchar(255) NOT NULL,
  `thumb`       varchar(255) DEFAULT NULL,
  `criado_em`   timestamp    NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_medicao` (`medicao_id`),
  KEY `idx_tipo`    (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Foreign Keys
-- (adicionadas após criação das tabelas para evitar ordem de dependência)
-- ------------------------------------------------------------

ALTER TABLE `trechos`
  ADD CONSTRAINT `fk_trechos_criado_por`
    FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `ordens_servico`
  ADD CONSTRAINT `fk_os_trecho`
    FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_os_criado_por`
    FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `caminhamentos`
  ADD CONSTRAINT `fk_caminhamento_equipe`
    FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`),
  ADD CONSTRAINT `fk_caminhamento_planejador`
    FOREIGN KEY (`planejador_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `caminhamento_trechos`
  ADD CONSTRAINT `fk_camt_caminhamento`
    FOREIGN KEY (`caminhamento_id`) REFERENCES `caminhamentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_camt_trecho`
    FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`);

ALTER TABLE `funcionario_documentos`
  ADD CONSTRAINT `fk_fundoc_funcionario`
    FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE;

ALTER TABLE `equipamento_manutencoes`
  ADD CONSTRAINT `fk_manut_criado_por`
    FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `materiais_estoque`
  ADD CONSTRAINT `fk_estoque_material`
    FOREIGN KEY (`material_id`) REFERENCES `materiais_catalogo` (`id`) ON DELETE CASCADE;

ALTER TABLE `materiais_movimentos`
  ADD CONSTRAINT `fk_mov_material`
    FOREIGN KEY (`material_id`) REFERENCES `materiais_catalogo` (`id`),
  ADD CONSTRAINT `fk_mov_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `trecho_materiais`
  ADD CONSTRAINT `fk_tmat_trecho`
    FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tmat_material`
    FOREIGN KEY (`material_id`) REFERENCES `materiais_catalogo` (`id`),
  ADD CONSTRAINT `fk_tmat_criado_por`
    FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `medicoes_repavimentacao`
  ADD CONSTRAINT `fk_medicao_trecho`
    FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_medicao_criado_por`
    FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `medicao_pavimentos`
  ADD CONSTRAINT `fk_pavimento_medicao`
    FOREIGN KEY (`medicao_id`) REFERENCES `medicoes_repavimentacao` (`id`) ON DELETE CASCADE;

ALTER TABLE `medicao_pavimento_linhas`
  ADD CONSTRAINT `fk_linha_pavimento`
    FOREIGN KEY (`pavimento_id`) REFERENCES `medicao_pavimentos` (`id`) ON DELETE CASCADE;

ALTER TABLE `medicao_fotos`
  ADD CONSTRAINT `fk_foto_medicao`
    FOREIGN KEY (`medicao_id`) REFERENCES `medicoes_repavimentacao` (`id`) ON DELETE CASCADE;

SET foreign_key_checks = 1;

-- ============================================================
-- FIM DA MIGRAÇÃO PA4 — Fase 1
-- Tabelas criadas (se não existiam):
--   trechos, ordens_servico, caminhamentos, caminhamento_trechos,
--   funcionario_documentos, equipamento_documentos, equipamento_manutencoes,
--   materiais_catalogo, materiais_estoque, materiais_movimentos, trecho_materiais,
--   medicoes_repavimentacao, medicao_pavimentos, medicao_pavimento_linhas, medicao_fotos
-- Colunas adicionadas (se não existiam):
--   equipamentos_pesados: horimetro_atual, proxima_manutencao_horimetro, proxima_manutencao_data
--   equipamentos_leves: km_atual, proxima_manutencao_km, proxima_manutencao_data
-- ============================================================
