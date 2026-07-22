-- PA10 C1: Relação trecho ↔ material
CREATE TABLE IF NOT EXISTS `trecho_materiais` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `trecho_id` INT UNSIGNED NOT NULL,
  `material_id` INT UNSIGNED NOT NULL,
  `quantidade` DECIMAL(10,3) NOT NULL DEFAULT 0,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trecho_material` (`trecho_id`, `material_id`),
  INDEX `idx_trecho` (`trecho_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PA10 C3: Documentos de funcionário com validade
CREATE TABLE IF NOT EXISTS `funcionario_documentos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `funcionario_id` INT UNSIGNED NOT NULL,
  `tipo` VARCHAR(30) NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `data_validade` DATE NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_func_tipo` (`funcionario_id`, `tipo`),
  INDEX `idx_func` (`funcionario_id`),
  INDEX `idx_validade` (`data_validade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
