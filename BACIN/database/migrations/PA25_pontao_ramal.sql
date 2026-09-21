-- ============================================================
-- PA25 — O ramal do executor de REDE vira só o PONTÃO
-- ------------------------------------------------------------
-- Decisão de processo (18/09/2026): a equipe de rede executa apenas o
-- pontão — o lançamento da rede até a cota do ramal, normalmente 0,80 m —
-- e registra número do imóvel, profundidade, foto e localização.
-- O ramal completo (via + calçada, com as três fotos) é executado depois,
-- pela equipe de ramais, no app /BACIN/executor-ramais (PA24).
--
-- Efeitos:
--  * `diario_pontoes` ganha profundidade e coordenadas.
--  * `diario_ramais` (passo 18 do diário de rede) fica como HISTÓRICO:
--    não recebe mais lançamento novo. Nada é apagado.
-- ============================================================

ALTER TABLE `diario_pontoes`
  ADD COLUMN `profundidade_m` decimal(5,2) DEFAULT NULL
      COMMENT 'cota de lançamento do ramal, normalmente 0,80 m' AFTER `nro_residencia`,
  ADD COLUMN `lat` decimal(10,7) DEFAULT NULL AFTER `foto_id`,
  ADD COLUMN `lng` decimal(10,7) DEFAULT NULL AFTER `lat`,
  ADD COLUMN `observacao` varchar(255) DEFAULT NULL AFTER `lng`,
  ADD COLUMN `created_at` timestamp NOT NULL DEFAULT current_timestamp() AFTER `observacao`;
-- (o índice em diario_id já existe no esquema base)

ALTER TABLE `diario_ramais`
  COMMENT = 'HISTÓRICO — ramal completo lançado no diário de rede até PA25 (18/09/2026). A partir de PA24/PA25 o ramal é do app executor-ramais.';
