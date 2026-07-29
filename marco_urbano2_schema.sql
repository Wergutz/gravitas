-- =====================================================================
-- Schema do banco marco_urbano2 (VisionHub Locar)
-- =====================================================================
--
-- Este arquivo é um artefato de referência para criação do banco NOVO
-- `u278289683_marco_urbano2` (ainda não existe em produção). NÃO faz
-- parte do código do app e não é copiado pelo deploy — é só para
-- rodar manualmente via phpMyAdmin (ou outro cliente MySQL).
--
-- Origem: baseado no dump real exportado via phpMyAdmin do banco
-- `u278289683_locar` (estrutura, sem dados), gerado em 29/07/2026.
-- As definições de CREATE TABLE / índices / AUTO_INCREMENT / FOREIGN
-- KEYS abaixo são as mesmas do dump original — nenhum tipo de coluna
-- foi adivinhado a partir do código-fonte. A única alteração em
-- relação ao dump é este cabeçalho (o dump original não tinha
-- CREATE DATABASE/USE explícito, só um comentário citando o banco
-- antigo `u278289683_locar`; aqui o comentário foi atualizado para
-- citar o banco novo `u278289683_marco_urbano2`) e o acréscimo de
-- "IF NOT EXISTS" nos CREATE TABLE, para permitir rodar o script mais
-- de uma vez sem erro.
--
-- Tabelas: equipamentos_leves, equipamentos_pesados, funcionarios,
-- historico_medicao, pavimento_produzido, planejamentos, trechos,
-- trecho_fotos, usuarios.
--
-- Login (tabela usuarios): campos usuario/senha (não e-mail).
-- tipo_usuario: 1 = MASTER, 2 = PROPRIETÁRIO, 3 = PLANEJADOR, 4 = EXECUTOR.
-- Não existe coluna de "forçar troca de senha" nesta tabela (confirmado
-- no dump real) — ver marco_urbano2_usuarios.sql para os usuários novos
-- e as senhas temporárias geradas.
-- =====================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u278289683_marco_urbano2`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipamentos_leves`
--

CREATE TABLE IF NOT EXISTS `equipamentos_leves` (
  `id` int(10) UNSIGNED NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `fabricante` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) NOT NULL,
  `ano` year(4) DEFAULT NULL,
  `proprietario` varchar(150) DEFAULT NULL,
  `valor_contratado` decimal(12,2) DEFAULT NULL,
  `unidade_contrato` varchar(50) DEFAULT NULL,
  `combustivel` varchar(50) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipamentos_pesados`
--

CREATE TABLE IF NOT EXISTS `equipamentos_pesados` (
  `id` int(10) UNSIGNED NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `ano` year(4) DEFAULT NULL,
  `proprietario` varchar(150) DEFAULT NULL,
  `valor_contratado` decimal(12,2) DEFAULT NULL,
  `unidade_contrato` varchar(50) DEFAULT NULL,
  `combustivel` varchar(50) DEFAULT NULL,
  `placa` varchar(20) DEFAULT NULL,
  `fabricante` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionarios`
--

CREATE TABLE IF NOT EXISTS `funcionarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `inscricao` varchar(50) DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `empresa` varchar(150) DEFAULT NULL,
  `funcao` varchar(80) NOT NULL,
  `salario` decimal(10,2) DEFAULT NULL,
  `aso` tinyint(1) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nr06` tinyint(1) DEFAULT NULL,
  `nr10` tinyint(1) DEFAULT NULL,
  `nr11` tinyint(1) DEFAULT NULL,
  `nr12` tinyint(1) DEFAULT NULL,
  `nr18` tinyint(1) DEFAULT NULL,
  `nr20` tinyint(1) DEFAULT NULL,
  `nr23` tinyint(1) DEFAULT NULL,
  `nr33` tinyint(1) DEFAULT NULL,
  `nr35` tinyint(1) DEFAULT NULL,
  `integracao_corsan` tinyint(1) DEFAULT NULL,
  `sertras` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_medicao`
--

CREATE TABLE IF NOT EXISTS `historico_medicao` (
  `id` int(10) UNSIGNED NOT NULL,
  `trecho_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `data_alteracao` datetime NOT NULL DEFAULT current_timestamp(),
  `area_anterior` decimal(10,2) DEFAULT NULL,
  `area_nova` decimal(10,2) DEFAULT NULL,
  `tipo_pavimento_anterior` varchar(50) DEFAULT NULL,
  `tipo_pavimento_novo` varchar(50) DEFAULT NULL,
  `observacao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pavimento_produzido`
--

CREATE TABLE IF NOT EXISTS `pavimento_produzido` (
  `id` int(10) UNSIGNED NOT NULL,
  `trecho_id` int(10) UNSIGNED NOT NULL,
  `comprimento` decimal(10,2) NOT NULL,
  `largura` decimal(10,2) NOT NULL,
  `area` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `planejamentos`
--

CREATE TABLE IF NOT EXISTS `planejamentos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(150) DEFAULT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `trechos`
--

CREATE TABLE IF NOT EXISTS `trechos` (
  `id` int(10) UNSIGNED NOT NULL,
  `planejamento_id` int(10) UNSIGNED NOT NULL,
  `medicao` varchar(50) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `contrato` varchar(100) NOT NULL,
  `bacia` varchar(100) NOT NULL,
  `pv_montante` varchar(50) NOT NULL,
  `pv_jusante` varchar(50) NOT NULL,
  `tipo_pi_montante` enum('PV','PI','CA','Outro') NOT NULL,
  `quantidade_pvs` int(11) NOT NULL,
  `tipo_pavimento` enum('paralelepipedo_regular','paralelepipedo_irregular','bloco_concreto','asfalto','asfalto_paralelepipedo','chao_batido','calcada') NOT NULL,
  `area_total` decimal(10,2) NOT NULL,
  `croqui` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `trecho_fotos`
--

CREATE TABLE IF NOT EXISTS `trecho_fotos` (
  `id` int(10) UNSIGNED NOT NULL,
  `trecho_id` int(10) UNSIGNED NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo_usuario` tinyint(3) UNSIGNED NOT NULL COMMENT '\r\n1 = MASTER\r\n2 = PROPRIETÁRIO\r\n3 = PLANEJADOR\r\n4 = EXECUTOR\r\n',
  `ativo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = ativo, 0 = desativado',
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `equipamentos_leves`
--
ALTER TABLE `equipamentos_leves`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `equipamentos_pesados`
--
ALTER TABLE `equipamentos_pesados`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_funcionario_cpf` (`cpf`);

--
-- Índices de tabela `historico_medicao`
--
ALTER TABLE `historico_medicao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trecho` (`trecho_id`),
  ADD KEY `idx_usuario` (`usuario_id`);

--
-- Índices de tabela `pavimento_produzido`
--
ALTER TABLE `pavimento_produzido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pavimento_trecho` (`trecho_id`);

--
-- Índices de tabela `planejamentos`
--
ALTER TABLE `planejamentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `trechos`
--
ALTER TABLE `trechos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_trechos_planejamento` (`planejamento_id`);

--
-- Índices de tabela `trecho_fotos`
--
ALTER TABLE `trecho_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_fotos_trecho` (`trecho_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `equipamentos_leves`
--
ALTER TABLE `equipamentos_leves`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipamentos_pesados`
--
ALTER TABLE `equipamentos_pesados`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `historico_medicao`
--
ALTER TABLE `historico_medicao`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pavimento_produzido`
--
ALTER TABLE `pavimento_produzido`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `planejamentos`
--
ALTER TABLE `planejamentos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `trechos`
--
ALTER TABLE `trechos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `trecho_fotos`
--
ALTER TABLE `trecho_fotos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `historico_medicao`
--
ALTER TABLE `historico_medicao`
  ADD CONSTRAINT `fk_hist_trecho` FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_hist_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `pavimento_produzido`
--
ALTER TABLE `pavimento_produzido`
  ADD CONSTRAINT `fk_pavimento_trecho` FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `trechos`
--
ALTER TABLE `trechos`
  ADD CONSTRAINT `fk_trechos_planejamento` FOREIGN KEY (`planejamento_id`) REFERENCES `planejamentos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `trecho_fotos`
--
ALTER TABLE `trecho_fotos`
  ADD CONSTRAINT `fk_fotos_trecho` FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
