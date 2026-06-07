-- PA12: Adiciona colunas de manutenção nas tabelas de equipamentos
-- Necessário para a tela /diarios do painel do planejador

ALTER TABLE `equipamentos_pesados`
  ADD COLUMN IF NOT EXISTS `status_manutencao` ENUM('ok','manutencao') NOT NULL DEFAULT 'ok',
  ADD COLUMN IF NOT EXISTS `obs_manutencao`    VARCHAR(500) DEFAULT NULL;

ALTER TABLE `equipamentos_leves`
  ADD COLUMN IF NOT EXISTS `status_manutencao` ENUM('ok','manutencao') NOT NULL DEFAULT 'ok',
  ADD COLUMN IF NOT EXISTS `obs_manutencao`    VARCHAR(500) DEFAULT NULL;
