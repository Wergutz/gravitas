-- ============================================================
-- PA24 — Executor de Ramais (perfil nível 9)
-- ------------------------------------------------------------
-- Cria a frente de ramais (uma por dia, por equipe) — tabela `frentes_ramais`,
-- os ramais lançados nessa frente e as fotos de cada ramal.
--
-- Regras adotadas:
--  * NÃO confundir com `diario_ramais`, que é o lançamento de ramais
--    dentro do diário do executor de REDE (sem foto e sem pavimento).
--  * A frente pode estar ligada a um trecho do caminhamento
--    (trecho_id) OU ter só o logradouro digitado pelo executor.
--  * Comprimentos são POR RAMAL: via e calçada, cada um com o
--    seu tipo de pavimento.
--  * numero_imovel é o número da casa/economia atendida (texto,
--    aceita "1234-A"); sequencia é o contador dentro da frente.
--  * Cada ramal tem 3 fotos: 'ramal', 'lancamento_via' e 'acabado'.
--
-- Migração incremental — pressupõe o esquema base já criado.
-- ============================================================

CREATE TABLE IF NOT EXISTS `frentes_ramais` (
  `id`          int(11) NOT NULL AUTO_INCREMENT,
  `equipe_id`   int(11) NOT NULL,
  `autor_id`    int(11) NOT NULL COMMENT 'usuário executor de ramais (nível 9)',
  `trecho_id`   int(11) DEFAULT NULL COMMENT 'opcional — frente ligada a um trecho',
  `logradouro`  varchar(160) NOT NULL COMMENT 'rua/avenida da frente',
  `data`        date NOT NULL,
  `status`      enum('rascunho','enviado') NOT NULL DEFAULT 'rascunho',
  `versao`      tinyint(4) NOT NULL DEFAULT 1,
  `obs`         text DEFAULT NULL,
  `qtd_ramais`  int(11) NOT NULL DEFAULT 0 COMMENT 'calculado ao encerrar',
  `total_via_m`     decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_calcada_m` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at`  timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at`  timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_equipe_data` (`equipe_id`,`data`),
  KEY `idx_trecho` (`trecho_id`),
  KEY `idx_autor` (`autor_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ramais` (
  `id`                int(11) NOT NULL AUTO_INCREMENT,
  `frente_id`   int(11) NOT NULL,
  `sequencia`         int(11) NOT NULL DEFAULT 1 COMMENT 'ordem do ramal dentro da frente',
  `numero_imovel`     varchar(30) NOT NULL COMMENT 'número da casa/economia (ex: 1234, 1234-A)',
  `pavimento_via`     enum('asfalto','asfalto_paralelepipedo','paralelepipedo_regular','paralelepipedo_irregular','bloco_concreto','chao_batido') DEFAULT NULL,
  `comprimento_via_m` decimal(8,2) NOT NULL DEFAULT 0.00,
  `pavimento_calcada` enum('concreto','ladrilho_hidraulico','petit_pave','bloco_concreto','basalto','grama_terra','sem_calcada') DEFAULT NULL,
  `comprimento_calcada_m` decimal(8,2) NOT NULL DEFAULT 0.00,
  `observacao`        varchar(255) DEFAULT NULL,
  `lat`               decimal(10,7) DEFAULT NULL,
  `lng`               decimal(10,7) DEFAULT NULL,
  `created_at`        timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at`        timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_diario` (`frente_id`),
  CONSTRAINT `fk_ramal_frente` FOREIGN KEY (`frente_id`)
      REFERENCES `frentes_ramais` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ramal_fotos` (
  `id`          int(11) NOT NULL AUTO_INCREMENT,
  `ramal_id`    int(11) NOT NULL,
  `tipo`        enum('ramal','lancamento_via','acabado') NOT NULL,
  `filename`    varchar(255) NOT NULL,
  `thumb`       varchar(255) DEFAULT NULL,
  `lat`         decimal(10,7) DEFAULT NULL,
  `lng`         decimal(10,7) DEFAULT NULL,
  `captured_at` varchar(30) DEFAULT NULL COMMENT 'horário do aparelho, enviado pelo app',
  `created_at`  timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ramal_tipo` (`ramal_id`,`tipo`),
  CONSTRAINT `fk_ramal_foto` FOREIGN KEY (`ramal_id`)
      REFERENCES `ramais` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
