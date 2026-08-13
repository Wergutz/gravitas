-- PA19: Topógrafo profile + OS Topografia tables
-- Safe to run multiple times (IF NOT EXISTS)

CREATE TABLE IF NOT EXISTS `os_topografia` (
  `id`                   int(11) NOT NULL AUTO_INCREMENT,
  `trecho_id`            int(11) NOT NULL,
  `data_os`              date NOT NULL,
  `cota_tampa_montante`  decimal(10,3) NOT NULL,
  `cota_fundo_montante`  decimal(10,3) NOT NULL,
  `cota_tampa_jusante`   decimal(10,3) NOT NULL,
  `cota_fundo_jusante`   decimal(10,3) NOT NULL COMMENT 'calculado: fundo_mont - extensao * declividade',
  `declividade`          decimal(8,6) NOT NULL COMMENT 'm/m',
  `regua`                decimal(5,2) NOT NULL,
  `diam_externo_esp`     int(11) NOT NULL COMMENT 'mm',
  `prof_media`           decimal(6,3) DEFAULT NULL COMMENT 'media de prof_vala de todas estacas',
  `observacoes`          text DEFAULT NULL,
  `revisao`              int(11) NOT NULL DEFAULT 1,
  `status`               enum('aguardando_liberacao','liberado') NOT NULL DEFAULT 'aguardando_liberacao',
  `arquivo_os`           varchar(255) DEFAULT NULL,
  `importado_por`        int(11) NOT NULL,
  `importado_em`         timestamp NULL DEFAULT current_timestamp(),
  `liberado_por`         int(11) DEFAULT NULL,
  `liberado_em`          timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `idx_trecho` (`trecho_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `os_topografia_estacas` (
  `id`             int(11) NOT NULL AUTO_INCREMENT,
  `os_id`          int(11) NOT NULL,
  `estaca`         varchar(30) NOT NULL,
  `comp_acumulado` decimal(8,3) NOT NULL,
  `cota_auxiliar`  decimal(10,3) DEFAULT NULL,
  `cota_eixo`      decimal(10,3) DEFAULT NULL,
  `cota_rede_gi`   decimal(10,3) NOT NULL,
  `cota_rede_gs`   decimal(10,3) NOT NULL,
  `cota_gabarito`  decimal(10,3) NOT NULL,
  `altura_gabarito` decimal(8,3) NOT NULL,
  `prof_vala`      decimal(8,3) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_os_id` (`os_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `os_topografia_revisoes` (
  `id`                 int(11) NOT NULL AUTO_INCREMENT,
  `os_id`              int(11) NOT NULL,
  `revisao`            int(11) NOT NULL,
  `declividade_de`     decimal(8,6) NOT NULL,
  `declividade_para`   decimal(8,6) NOT NULL,
  `cota_fj_de`         decimal(10,3) NOT NULL,
  `cota_fj_para`       decimal(10,3) NOT NULL,
  `alterado_por`       int(11) NOT NULL,
  `alterado_em`        timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_os_id` (`os_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FKs (only add if not exists)
ALTER TABLE `os_topografia`
  ADD CONSTRAINT `fk_ostopo_trecho`   FOREIGN KEY IF NOT EXISTS (`trecho_id`)    REFERENCES `trechos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ostopo_import`   FOREIGN KEY IF NOT EXISTS (`importado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_ostopo_liberado` FOREIGN KEY IF NOT EXISTS (`liberado_por`)  REFERENCES `usuarios` (`id`);

ALTER TABLE `os_topografia_estacas`
  ADD CONSTRAINT `fk_ostopo_est_os` FOREIGN KEY IF NOT EXISTS (`os_id`) REFERENCES `os_topografia` (`id`) ON DELETE CASCADE;

ALTER TABLE `os_topografia_revisoes`
  ADD CONSTRAINT `fk_ostopo_rev_os`   FOREIGN KEY IF NOT EXISTS (`os_id`)        REFERENCES `os_topografia` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ostopo_rev_user` FOREIGN KEY IF NOT EXISTS (`alterado_por`) REFERENCES `usuarios` (`id`);
