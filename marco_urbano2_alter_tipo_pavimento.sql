-- =====================================================================
-- Atualiza o enum tipo_pavimento da tabela `trechos` (marco_urbano2)
-- para os 7 tipos exatos da legenda "Tipo de Pavimentação" do
-- Resumo PI oficial (1-CBUQ / 2-Calç reg e irreg / 3-Paralelepipedo /
-- 4-Bloco pré-moldado de concreto / 5-Sem pav / 6-CBUQ+Pedra /
-- 7-calçada mod), substituindo o enum antigo (que dividia
-- paralelepípedo em regular/irregular e não tinha "calçada mod").
--
-- Rodar manualmente no phpMyAdmin do banco `u278289683_marco_urbano2`.
--
-- IMPORTANTE: só rode este ALTER se ainda não houver trechos
-- cadastrados usando os valores antigos (paralelepipedo_regular,
-- paralelepipedo_irregular, asfalto, asfalto_paralelepipedo,
-- chao_batido, calcada). Se já houver, esses registros ficariam com
-- um valor de enum inválido e precisariam ser corrigidos manualmente
-- antes ou depois do ALTER.
-- =====================================================================

ALTER TABLE `trechos`
  MODIFY `tipo_pavimento` enum(
    'cbuq',
    'calcada_reg_irreg',
    'paralelepipedo',
    'bloco_concreto',
    'sem_pavimento',
    'cbuq_pedra',
    'calcada_mod'
  ) NOT NULL;
