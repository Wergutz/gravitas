-- PA11: Contagem de estoque para importação de posição de estoque
CREATE TABLE IF NOT EXISTS `contagens_estoque` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `data_contagem` DATE NOT NULL,
  `responsavel` VARCHAR(100) NULL,
  `usuario_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `contagens_estoque_itens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contagem_id` INT UNSIGNED NOT NULL,
  `material_id` INT UNSIGNED NOT NULL,
  `estoque_encontrado` DECIMAL(10,3) NOT NULL DEFAULT 0,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_contagem_material` (`contagem_id`, `material_id`),
  INDEX `idx_contagem` (`contagem_id`),
  INDEX `idx_material` (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
