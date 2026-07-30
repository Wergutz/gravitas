-- =====================================================================
-- Reverte um trecho já medido (marco_urbano2) de volta para
-- "não medido" — apaga a medição/fotos associadas e zera area_total,
-- fazendo o trecho voltar a aparecer cinza ("Medir trecho") na tela
-- de Incluir Medições.
--
-- Trecho: PI'43-1 -> PI'44-1, planejamento "Contrato 4147-2024"
--
-- Rodar manualmente no phpMyAdmin do banco `u278289683_marco_urbano2`.
-- =====================================================================

START TRANSACTION;

-- 1) Apaga o registro das fotos no banco
--    (os arquivos físicos em uploads/fotos/ ficam órfãos no servidor,
--    isso aqui só limpa a referência no banco)
DELETE tf FROM trecho_fotos tf
JOIN trechos t ON t.id = tf.trecho_id
JOIN planejamentos p ON p.id = t.planejamento_id
WHERE p.nome = 'Contrato 4147-2024'
  AND t.pv_montante = "PI'43-1"
  AND t.pv_jusante = "PI'44-1";

-- 2) Apaga as linhas de medição (comprimento x largura) lançadas
DELETE pp FROM pavimento_produzido pp
JOIN trechos t ON t.id = pp.trecho_id
JOIN planejamentos p ON p.id = t.planejamento_id
WHERE p.nome = 'Contrato 4147-2024'
  AND t.pv_montante = "PI'43-1"
  AND t.pv_jusante = "PI'44-1";

-- 3) Zera area_total e croqui do trecho -> volta a aparecer como
--    "não medido" (cinza) na tela de Incluir Medições
UPDATE trechos t
JOIN planejamentos p ON p.id = t.planejamento_id
SET t.area_total = 0, t.croqui = ''
WHERE p.nome = 'Contrato 4147-2024'
  AND t.pv_montante = "PI'43-1"
  AND t.pv_jusante = "PI'44-1";

COMMIT;
