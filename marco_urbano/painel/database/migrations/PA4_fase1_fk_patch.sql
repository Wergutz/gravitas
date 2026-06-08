-- ============================================================
-- PA4 Fase 1 — PATCH: apenas as Foreign Keys
-- Execute SOMENTE se as tabelas já foram criadas (script principal rodou)
-- e o erro ocorreu na seção de Foreign Keys.
-- ============================================================

SET foreign_key_checks = 0;

ALTER TABLE `trechos`
  ADD CONSTRAINT `fk_trechos_criado_por`
    FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `ordens_servico`
  ADD CONSTRAINT `fk_os_trecho`
    FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_os_criado_por`
    FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `caminhamentos`
  ADD CONSTRAINT `fk_caminhamento_equipe`
    FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`),
  ADD CONSTRAINT `fk_caminhamento_planejador`
    FOREIGN KEY (`planejador_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `caminhamento_trechos`
  ADD CONSTRAINT `fk_camt_caminhamento`
    FOREIGN KEY (`caminhamento_id`) REFERENCES `caminhamentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_camt_trecho`
    FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`);

ALTER TABLE `funcionario_documentos`
  ADD CONSTRAINT `fk_fundoc_funcionario`
    FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE;

ALTER TABLE `equipamento_manutencoes`
  ADD CONSTRAINT `fk_manut_criado_por`
    FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `materiais_estoque`
  ADD CONSTRAINT `fk_estoque_material`
    FOREIGN KEY (`material_id`) REFERENCES `materiais_catalogo` (`id`) ON DELETE CASCADE;

ALTER TABLE `materiais_movimentos`
  ADD CONSTRAINT `fk_mov_material`
    FOREIGN KEY (`material_id`) REFERENCES `materiais_catalogo` (`id`),
  ADD CONSTRAINT `fk_mov_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `trecho_materiais`
  ADD CONSTRAINT `fk_tmat_trecho`
    FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tmat_material`
    FOREIGN KEY (`material_id`) REFERENCES `materiais_catalogo` (`id`),
  ADD CONSTRAINT `fk_tmat_criado_por`
    FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `medicoes_repavimentacao`
  ADD CONSTRAINT `fk_medicao_trecho`
    FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_medicao_criado_por`
    FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `medicao_pavimentos`
  ADD CONSTRAINT `fk_pavimento_medicao`
    FOREIGN KEY (`medicao_id`) REFERENCES `medicoes_repavimentacao` (`id`) ON DELETE CASCADE;

ALTER TABLE `medicao_pavimento_linhas`
  ADD CONSTRAINT `fk_linha_pavimento`
    FOREIGN KEY (`pavimento_id`) REFERENCES `medicao_pavimentos` (`id`) ON DELETE CASCADE;

ALTER TABLE `medicao_fotos`
  ADD CONSTRAINT `fk_foto_medicao`
    FOREIGN KEY (`medicao_id`) REFERENCES `medicoes_repavimentacao` (`id`) ON DELETE CASCADE;

SET foreign_key_checks = 1;
