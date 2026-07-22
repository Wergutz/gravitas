-- PA9: Gerenciamento de Usuários (Master Gravitas — tipo_usuario = 3)
-- Executar via PhpMyAdmin antes de ativar PA9

-- Log de auditoria
CREATE TABLE IF NOT EXISTS `log_auditoria` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`            INT UNSIGNED NOT NULL,
  `acao`                VARCHAR(30)  NOT NULL,
  `usuario_afetado_id`  INT UNSIGNED NULL,
  `detalhes`            TEXT NULL,
  `criado_em`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_admin`    (`admin_id`),
  INDEX `idx_afetado`  (`usuario_afetado_id`),
  INDEX `idx_criado`   (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rastrear último acesso
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `ultimo_acesso` DATETIME NULL AFTER `ativo`;

-- Forçar troca de senha após reset
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `force_password_change` TINYINT(1) NOT NULL DEFAULT 0 AFTER `ultimo_acesso`;
