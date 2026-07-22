-- PA13: Corrige estrutura de funcionario_documentos
-- Problema: faltavam colunas `status` e `atualizado_em`, e constraint UNIQUE(funcionario_id, tipo)
-- Sem UNIQUE, ON DUPLICATE KEY UPDATE nunca disparava → validades nunca persistiam

-- 1. Adicionar colunas ausentes
ALTER TABLE `funcionario_documentos`
  ADD COLUMN IF NOT EXISTS `status`       TINYINT(1)  NOT NULL DEFAULT 0
      COMMENT '0=não informado 1=apto 2=inapto 3=não se aplica'
      AFTER `tipo`,
  ADD COLUMN IF NOT EXISTS `atualizado_em` TIMESTAMP  NULL DEFAULT CURRENT_TIMESTAMP
      ON UPDATE CURRENT_TIMESTAMP
      AFTER `criado_em`;

-- 2. Remover duplicatas (mantém o registro com data_validade mais recente; em empate, o de maior id)
DELETE d1 FROM `funcionario_documentos` d1
INNER JOIN `funcionario_documentos` d2
  ON  d1.funcionario_id = d2.funcionario_id
  AND d1.tipo           = d2.tipo
  AND (
    d1.data_validade < d2.data_validade
    OR (d1.data_validade = d2.data_validade AND d1.id < d2.id)
    OR (d1.data_validade IS NULL AND d2.data_validade IS NOT NULL)
  );

-- 3. Adicionar constraint UNIQUE para que ON DUPLICATE KEY UPDATE funcione
ALTER TABLE `funcionario_documentos`
  ADD UNIQUE KEY IF NOT EXISTS `uq_func_tipo` (`funcionario_id`, `tipo`);
