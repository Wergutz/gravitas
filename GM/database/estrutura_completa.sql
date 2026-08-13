-- ============================================================
-- GM SERVIÇOS — estrutura completa do banco
--
-- Banco de destino: u278289683_GM
--
-- 61 tabelas com índices, AUTO_INCREMENT e chaves estrangeiras.
-- Somente estrutura: nenhuma linha de dados.
--
-- POR QUE ESTE ARQUIVO EXISTE: as migrações PA* de database/migrations
-- e painel/database/migrations são INCREMENTAIS — partem de um esquema
-- base que nunca foi versionado. Sozinhas elas criam 41 das 61 tabelas
-- e quebram em ALTER TABLE de tabelas inexistentes (equipamentos_pesados,
-- equipamentos_leves, usuarios, funcionarios, equipes, planejamentos...).
-- Para um banco NOVO, use este arquivo e ignore as PA*.
--
-- Execução: phpMyAdmin -> selecione o banco -> aba SQL -> cole -> Executar.
-- Depois rode painel/database/migrations/GM_usuarios_iniciais.sql.
-- ============================================================

-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 06/08/2026 às 17:45
-- Versão do servidor: 11.8.8-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u278289683_GM`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `alertas_falta_material`
--

CREATE TABLE `alertas_falta_material` (
  `id` int(11) NOT NULL,
  `diario_id` int(11) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `trecho_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `materiais_faltando` text NOT NULL,
  `resolvido` tinyint(1) NOT NULL DEFAULT 0,
  `resolvido_em` timestamp NULL DEFAULT NULL,
  `resolvido_por` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `caminhamentos`
--

CREATE TABLE `caminhamentos` (
  `id` int(11) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `planejador_id` int(11) NOT NULL,
  `data_execucao` date NOT NULL,
  `status` enum('rascunho','publicado','execucao','concluido') NOT NULL DEFAULT 'rascunho',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `caminhamentos_repav`
--

CREATE TABLE `caminhamentos_repav` (
  `id` int(11) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `data_execucao` date NOT NULL,
  `status` enum('rascunho','publicado','execucao','concluido') NOT NULL DEFAULT 'rascunho',
  `criado_por` int(11) DEFAULT NULL,
  `obs` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `caminhamentos_repav_pavimentos`
--

CREATE TABLE `caminhamentos_repav_pavimentos` (
  `id` int(11) NOT NULL,
  `caminhamento_trecho_id` int(11) NOT NULL,
  `tipo_pavimento` varchar(80) NOT NULL,
  `espessura_cm` decimal(5,2) DEFAULT NULL COMMENT 'Apenas para asfalto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `caminhamentos_repav_trechos`
--

CREATE TABLE `caminhamentos_repav_trechos` (
  `id` int(11) NOT NULL,
  `caminhamento_id` int(11) NOT NULL,
  `trecho_id` int(11) NOT NULL,
  `sequencia` tinyint(4) NOT NULL DEFAULT 1,
  `status` enum('pendente','execucao','concluido') NOT NULL DEFAULT 'pendente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `caminhamento_trechos`
--

CREATE TABLE `caminhamento_trechos` (
  `id` int(11) NOT NULL,
  `caminhamento_id` int(11) NOT NULL,
  `trecho_id` int(11) NOT NULL,
  `sequencia` int(11) NOT NULL DEFAULT 1,
  `status` enum('pendente','execucao','concluido') NOT NULL DEFAULT 'pendente',
  `extensao_executada_m` decimal(8,2) DEFAULT NULL COMMENT 'extensão medida por GPS pelo Executor'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contagens_estoque`
--

CREATE TABLE `contagens_estoque` (
  `id` int(10) UNSIGNED NOT NULL,
  `data_contagem` date NOT NULL,
  `responsavel` varchar(100) DEFAULT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contagens_estoque_itens`
--

CREATE TABLE `contagens_estoque_itens` (
  `id` int(10) UNSIGNED NOT NULL,
  `contagem_id` int(10) UNSIGNED NOT NULL,
  `material_id` int(10) UNSIGNED NOT NULL,
  `estoque_encontrado` decimal(10,3) NOT NULL DEFAULT 0.000,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `diarios_execucao`
--

CREATE TABLE `diarios_execucao` (
  `id` int(11) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `trecho_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `autor_id` int(11) NOT NULL COMMENT 'usuario executor responsável',
  `status` enum('rascunho','enviado','aprovado') NOT NULL DEFAULT 'rascunho',
  `versao` int(11) NOT NULL DEFAULT 1,
  `step_atual` tinyint(3) NOT NULL DEFAULT 1 COMMENT 'último passo salvo (1-21)',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `step3_estoque_ok` tinyint(1) DEFAULT NULL COMMENT '1=tem tudo, 0=falta',
  `step3_materiais_faltando` text DEFAULT NULL,
  `extensao_gps_m` decimal(8,2) DEFAULT NULL COMMENT 'cópia de diario_gps.extensao_calculada_m ao encerrar'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `diarios_repav`
--

CREATE TABLE `diarios_repav` (
  `id` int(11) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `trecho_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `autor_id` int(11) NOT NULL,
  `status` enum('rascunho','enviado','aprovado') NOT NULL DEFAULT 'rascunho',
  `step_atual` tinyint(4) NOT NULL DEFAULT 0,
  `versao` tinyint(4) NOT NULL DEFAULT 1,
  `mat_ok` tinyint(4) DEFAULT NULL COMMENT '1=ok, 0=falta material',
  `mat_obs` text DEFAULT NULL,
  `obs_final` text DEFAULT NULL,
  `area_total_m2` decimal(10,2) NOT NULL DEFAULT 0.00,
  `volume_asf_m3` decimal(10,3) NOT NULL DEFAULT 0.000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `diario_cargas`
--

CREATE TABLE `diario_cargas` (
  `id` int(11) NOT NULL,
  `diario_id` int(11) NOT NULL,
  `tipo` enum('bota_fora','bota_espera','importado') NOT NULL,
  `numero` int(11) NOT NULL DEFAULT 1 COMMENT 'número sequencial da carga no dia',
  `foto_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `diario_equipamentos`
--

CREATE TABLE `diario_equipamentos` (
  `id` int(11) NOT NULL,
  `diario_id` int(11) NOT NULL,
  `equipamento_id` int(11) NOT NULL,
  `tipo` enum('leve','pesado') NOT NULL,
  `funcionando` tinyint(1) NOT NULL DEFAULT 1,
  `obs` varchar(255) DEFAULT NULL,
  `foto_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `diario_fotos`
--

CREATE TABLE `diario_fotos` (
  `id` int(11) NOT NULL,
  `diario_id` int(11) NOT NULL,
  `step_num` tinyint(3) NOT NULL COMMENT 'passo do diário (1-21)',
  `arquivo` varchar(255) NOT NULL,
  `thumb` varchar(255) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `timestamp_servidor` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo` varchar(50) DEFAULT NULL COMMENT 'ex: sinalização, equipamento, reaterro...'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `diario_gps`
--

CREATE TABLE `diario_gps` (
  `id` int(11) NOT NULL,
  `diario_id` int(11) NOT NULL,
  `lat_inicio` decimal(10,7) DEFAULT NULL,
  `lng_inicio` decimal(10,7) DEFAULT NULL,
  `foto_inicio_id` int(11) DEFAULT NULL,
  `lat_fim` decimal(10,7) DEFAULT NULL,
  `lng_fim` decimal(10,7) DEFAULT NULL,
  `foto_fim_id` int(11) DEFAULT NULL,
  `extensao_calculada_m` decimal(8,2) DEFAULT NULL COMMENT 'haversine início→fim'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `diario_interferencias`
--

CREATE TABLE `diario_interferencias` (
  `id` int(11) NOT NULL,
  `diario_id` int(11) NOT NULL,
  `tipo` enum('pedra','agua_na_vala','ramal_de_agua','rede_de_agua','rede_pluvial','rompimento_de_rede','rede_cloacal_existente','rede_logica','rede_eletrica','outros') NOT NULL,
  `especificacao` varchar(255) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `foto_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `diario_pontoes`
--

CREATE TABLE `diario_pontoes` (
  `id` int(11) NOT NULL,
  `diario_id` int(11) NOT NULL,
  `nro_residencia` varchar(50) DEFAULT NULL,
  `foto_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `diario_presencas`
--

CREATE TABLE `diario_presencas` (
  `id` int(11) NOT NULL,
  `diario_id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `status` enum('presente','ausente','atrasou','saiu_cedo') NOT NULL DEFAULT 'presente',
  `obs` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `diario_ramais`
--

CREATE TABLE `diario_ramais` (
  `id` int(11) NOT NULL,
  `diario_id` int(11) NOT NULL,
  `nro_residencia` varchar(50) DEFAULT NULL,
  `dimensao_pontao` varchar(50) DEFAULT NULL,
  `ext_pista` decimal(7,2) DEFAULT NULL COMMENT 'metros',
  `ext_calcada` decimal(7,2) DEFAULT NULL COMMENT 'metros'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `diario_reaterros`
--

CREATE TABLE `diario_reaterros` (
  `id` int(11) NOT NULL,
  `diario_id` int(11) NOT NULL,
  `tipo` enum('lastro_brita','colchao_areia_po_brita','reaterro_importado','compactacao_importado','reaterro_local','compactacao_local','base_brita_graduada','compactacao_base') NOT NULL,
  `espessura_cm` decimal(6,2) DEFAULT NULL,
  `foto_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `diario_repav_areas`
--

CREATE TABLE `diario_repav_areas` (
  `id` int(11) NOT NULL,
  `diario_id` int(11) NOT NULL,
  `tipo_pavimento` varchar(80) NOT NULL,
  `sequencia` tinyint(4) NOT NULL DEFAULT 1,
  `base_m` decimal(8,2) NOT NULL DEFAULT 0.00,
  `largura_m` decimal(8,2) NOT NULL DEFAULT 0.00,
  `area_m2` decimal(10,2) NOT NULL DEFAULT 0.00,
  `espessura_m` decimal(5,3) DEFAULT NULL COMMENT 'Apenas para asfalto',
  `volume_m3` decimal(10,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `diario_repav_cargas`
--

CREATE TABLE `diario_repav_cargas` (
  `id` int(11) NOT NULL,
  `diario_id` int(11) NOT NULL,
  `sequencia` tinyint(4) NOT NULL DEFAULT 1,
  `numero_nf` varchar(50) DEFAULT NULL,
  `massa_t` decimal(8,2) DEFAULT NULL,
  `foto_carga` varchar(255) DEFAULT NULL,
  `foto_nf` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `diario_repav_equipamentos`
--

CREATE TABLE `diario_repav_equipamentos` (
  `id` int(11) NOT NULL,
  `diario_id` int(11) NOT NULL,
  `equipamento_id` int(11) DEFAULT NULL COMMENT 'FK para equipamentos_pesados ou leves',
  `tipo` varchar(10) NOT NULL DEFAULT 'pesado',
  `nome` varchar(100) NOT NULL DEFAULT '',
  `status` enum('ok','problema') NOT NULL DEFAULT 'ok',
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `diario_repav_fotos`
--

CREATE TABLE `diario_repav_fotos` (
  `id` int(11) NOT NULL,
  `diario_id` int(11) NOT NULL,
  `step_num` tinyint(4) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `thumb` varchar(255) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `captured_at` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `diario_repav_presencas`
--

CREATE TABLE `diario_repav_presencas` (
  `id` int(11) NOT NULL,
  `diario_id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `status` enum('presente','ausente','atrasou','saiu_cedo') NOT NULL DEFAULT 'presente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipamentos_leves`
--

CREATE TABLE `equipamentos_leves` (
  `id` int(11) NOT NULL,
  `referencia` varchar(50) NOT NULL,
  `fabricante` varchar(100) NOT NULL,
  `tipo` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `ano` int(4) DEFAULT NULL,
  `proprietario` varchar(100) DEFAULT NULL,
  `combustivel` enum('GASOLINA','DIESEL','ELETRICO','OUTRO') DEFAULT 'OUTRO',
  `numero_serie` varchar(50) DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `km_atual` decimal(10,2) DEFAULT NULL,
  `proxima_manutencao_km` decimal(10,2) DEFAULT NULL,
  `proxima_manutencao_data` date DEFAULT NULL,
  `status_manutencao` enum('ok','manutencao') NOT NULL DEFAULT 'ok',
  `obs_manutencao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipamentos_pesados`
--

CREATE TABLE `equipamentos_pesados` (
  `id` int(11) NOT NULL,
  `tipo` varchar(100) NOT NULL,
  `fabricante` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) NOT NULL,
  `ano` int(4) DEFAULT NULL,
  `proprietario` varchar(100) DEFAULT NULL,
  `combustivel` enum('DIESEL','GASOLINA','OUTRO') DEFAULT 'DIESEL',
  `placa` varchar(20) DEFAULT NULL,
  `descricao` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `horimetro_atual` decimal(10,2) DEFAULT NULL,
  `proxima_manutencao_horimetro` decimal(10,2) DEFAULT NULL,
  `proxima_manutencao_data` date DEFAULT NULL,
  `status_manutencao` enum('ok','manutencao') NOT NULL DEFAULT 'ok',
  `obs_manutencao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipamento_documentos`
--

CREATE TABLE `equipamento_documentos` (
  `id` int(11) NOT NULL,
  `tipo_equipamento` enum('leve','pesado') NOT NULL,
  `equipamento_id` int(11) NOT NULL,
  `tipo_documento` varchar(80) NOT NULL COMMENT 'ex: CRLV, Seguro, Inspecao, Laudo',
  `data_emissao` date DEFAULT NULL,
  `data_validade` date DEFAULT NULL,
  `arquivo_pdf` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipamento_manutencoes`
--

CREATE TABLE `equipamento_manutencoes` (
  `id` int(11) NOT NULL,
  `tipo_equipamento` enum('leve','pesado') NOT NULL,
  `equipamento_id` int(11) NOT NULL,
  `tipo` enum('preventiva','corretiva') NOT NULL DEFAULT 'preventiva',
  `descricao` text DEFAULT NULL,
  `data_manutencao` date NOT NULL,
  `horimetro_km_na_data` decimal(10,2) DEFAULT NULL,
  `proxima_previsao_km` decimal(10,2) DEFAULT NULL,
  `proxima_previsao_data` date DEFAULT NULL,
  `custo` decimal(10,2) DEFAULT NULL,
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipes`
--

CREATE TABLE `equipes` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `responsavel` varchar(100) DEFAULT NULL,
  `usuario_sistema` varchar(100) DEFAULT NULL,
  `divide_frente` enum('SIM','NAO') DEFAULT 'NAO',
  `frente_nome` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `responsavel_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipes_equipamentos`
--

CREATE TABLE `equipes_equipamentos` (
  `id` int(11) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `compactador` int(11) DEFAULT 0,
  `placa` int(11) DEFAULT 0,
  `motobomba` int(11) DEFAULT 0,
  `cortadora` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipes_equipamentos_leves`
--

CREATE TABLE `equipes_equipamentos_leves` (
  `id` int(11) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `equipamento_id` int(11) NOT NULL,
  `quantidade` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipes_equipamentos_pesados`
--

CREATE TABLE `equipes_equipamentos_pesados` (
  `id` int(11) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `equipamento_id` int(11) NOT NULL,
  `operador` varchar(100) DEFAULT NULL,
  `compartilhado` tinyint(4) DEFAULT 0,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipes_funcionarios`
--

CREATE TABLE `equipes_funcionarios` (
  `id` int(11) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `numero` int(11) DEFAULT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `funcao` varchar(100) DEFAULT NULL,
  `sertras` enum('APTO','INAPTO') DEFAULT 'APTO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipes_maquinas`
--

CREATE TABLE `equipes_maquinas` (
  `id` int(11) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `retro_descricao` varchar(150) DEFAULT NULL,
  `retro_operador` varchar(100) DEFAULT NULL,
  `cacamba_descricao` varchar(150) DEFAULT NULL,
  `cacamba_motorista` varchar(100) DEFAULT NULL,
  `divide_cacamba` enum('SIM','NAO') DEFAULT 'NAO',
  `frente_nome` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipes_usuarios`
--

CREATE TABLE `equipes_usuarios` (
  `id` int(11) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipes_veiculos`
--

CREATE TABLE `equipes_veiculos` (
  `id` int(11) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `retro_modelo_placa` varchar(100) DEFAULT NULL,
  `retro_operador` varchar(100) DEFAULT NULL,
  `cacamba_modelo_placa` varchar(100) DEFAULT NULL,
  `cacamba_motorista` varchar(100) DEFAULT NULL,
  `divide_cacamba` enum('SIM','NAO') DEFAULT 'NAO',
  `frente_nome` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipe_funcionarios`
--

CREATE TABLE `equipe_funcionarios` (
  `id` int(11) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date DEFAULT NULL,
  `ativo` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipe_membros`
--

CREATE TABLE `equipe_membros` (
  `id` int(11) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `funcao` varchar(50) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `execucao_fotos`
--

CREATE TABLE `execucao_fotos` (
  `id` int(11) NOT NULL,
  `execucao_id` int(11) NOT NULL,
  `tipo` enum('REDE','RAMAL') NOT NULL,
  `arquivo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `execucoes`
--

CREATE TABLE `execucoes` (
  `id` int(11) NOT NULL,
  `planejamento_id` int(11) DEFAULT NULL,
  `dia_id` int(11) DEFAULT NULL,
  `trecho_id` int(11) DEFAULT NULL,
  `executor_id` int(11) NOT NULL,
  `metragem` decimal(10,2) DEFAULT NULL,
  `ramais` int(11) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `data_execucao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionarios`
--

CREATE TABLE `funcionarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` char(11) NOT NULL,
  `empresa` varchar(100) NOT NULL,
  `funcao` varchar(100) NOT NULL,
  `salario` decimal(10,2) NOT NULL,
  `aso` tinyint(4) NOT NULL,
  `nr06` tinyint(4) NOT NULL,
  `nr10` tinyint(4) NOT NULL,
  `nr11` tinyint(4) NOT NULL,
  `nr12` tinyint(4) NOT NULL,
  `nr18` tinyint(4) NOT NULL,
  `nr20` tinyint(4) NOT NULL,
  `nr23` tinyint(4) NOT NULL,
  `nr33` tinyint(4) NOT NULL,
  `nr35` tinyint(4) NOT NULL,
  `integracao_corsan` tinyint(4) NOT NULL,
  `sertras` tinyint(4) NOT NULL,
  `ativo` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionario_documentos`
--

CREATE TABLE `funcionario_documentos` (
  `id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL COMMENT 'ASO, NR06, NR10, NR18, NR33, NR35, INTEGRACAO_CORSAN, SERTRAS...',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=não informado 1=apto 2=inapto 3=não se aplica',
  `data_emissao` date DEFAULT NULL,
  `data_validade` date DEFAULT NULL,
  `arquivo_pdf` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `log_auditoria`
--

CREATE TABLE `log_auditoria` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `acao` varchar(30) NOT NULL,
  `usuario_afetado_id` int(10) UNSIGNED DEFAULT NULL,
  `detalhes` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `materiais_catalogo`
--

CREATE TABLE `materiais_catalogo` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `unidade` varchar(20) NOT NULL DEFAULT 'un' COMMENT 'un, m, m², m³, kg, l, cj',
  `estoque_minimo` decimal(10,3) NOT NULL DEFAULT 0.000,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `materiais_estoque`
--

CREATE TABLE `materiais_estoque` (
  `id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `quantidade_fisica` decimal(10,3) NOT NULL DEFAULT 0.000,
  `quantidade_reservada` decimal(10,3) NOT NULL DEFAULT 0.000,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `materiais_movimentos`
--

CREATE TABLE `materiais_movimentos` (
  `id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `tipo` enum('entrada','reserva','baixa','ajuste') NOT NULL,
  `quantidade` decimal(10,3) NOT NULL,
  `referencia_tipo` varchar(50) DEFAULT NULL COMMENT 'caminhamento, trecho, ajuste_manual',
  `referencia_id` int(11) DEFAULT NULL,
  `observacao` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `medicao_fotos`
--

CREATE TABLE `medicao_fotos` (
  `id` int(11) NOT NULL,
  `medicao_id` int(11) NOT NULL,
  `tipo` enum('antes','durante','depois','croqui') NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `thumb` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `medicao_pavimentos`
--

CREATE TABLE `medicao_pavimentos` (
  `id` int(11) NOT NULL,
  `medicao_id` int(11) NOT NULL,
  `tipo_pavimento` enum('paralelepipedo_regular','paralelepipedo_irregular','bloco_concreto','asfalto','asfalto_paralelepipedo','chao_batido','calcada') NOT NULL,
  `espessura_cm` decimal(6,2) DEFAULT NULL COMMENT 'obrigatório para asfalto',
  `ordem` int(11) NOT NULL DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `medicao_pavimento_linhas`
--

CREATE TABLE `medicao_pavimento_linhas` (
  `id` int(11) NOT NULL,
  `pavimento_id` int(11) NOT NULL,
  `comprimento` decimal(8,2) NOT NULL,
  `largura` decimal(8,2) NOT NULL,
  `sequencia` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `medicoes_repavimentacao`
--

CREATE TABLE `medicoes_repavimentacao` (
  `id` int(11) NOT NULL,
  `trecho_id` int(11) NOT NULL,
  `status` enum('rascunho','concluida') NOT NULL DEFAULT 'rascunho',
  `observacoes` text DEFAULT NULL,
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ordens_servico`
--

CREATE TABLE `ordens_servico` (
  `id` int(11) NOT NULL,
  `trecho_id` int(11) NOT NULL,
  `topografo` varchar(100) DEFAULT NULL,
  `data_os` date DEFAULT NULL,
  `arquivo_pdf` varchar(255) NOT NULL,
  `versao` int(11) NOT NULL DEFAULT 1,
  `ativa` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=versão atual, 0=histórico',
  `criado_por` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `os_topografia`
--

CREATE TABLE `os_topografia` (
  `id` int(11) NOT NULL,
  `trecho_id` int(11) NOT NULL,
  `data_os` date NOT NULL,
  `cota_tampa_montante` decimal(10,3) NOT NULL,
  `cota_fundo_montante` decimal(10,3) NOT NULL,
  `cota_tampa_jusante` decimal(10,3) NOT NULL,
  `cota_fundo_jusante` decimal(10,3) NOT NULL,
  `declividade` decimal(8,6) NOT NULL,
  `regua` decimal(5,2) NOT NULL,
  `diam_externo_esp` int(11) NOT NULL,
  `prof_media` decimal(6,3) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `revisao` int(11) NOT NULL DEFAULT 1,
  `status` enum('aguardando_liberacao','liberado') NOT NULL DEFAULT 'aguardando_liberacao',
  `arquivo_os` varchar(255) DEFAULT NULL,
  `importado_por` int(11) NOT NULL,
  `importado_em` timestamp NULL DEFAULT current_timestamp(),
  `liberado_por` int(11) DEFAULT NULL,
  `liberado_em` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `os_topografia_estacas`
--

CREATE TABLE `os_topografia_estacas` (
  `id` int(11) NOT NULL,
  `os_id` int(11) NOT NULL,
  `estaca` varchar(30) NOT NULL,
  `comp_acumulado` decimal(8,3) NOT NULL,
  `cota_auxiliar` decimal(10,3) DEFAULT NULL,
  `cota_eixo` decimal(10,3) DEFAULT NULL,
  `cota_rede_gi` decimal(10,3) NOT NULL,
  `cota_rede_gs` decimal(10,3) NOT NULL,
  `cota_gabarito` decimal(10,3) NOT NULL,
  `altura_gabarito` decimal(8,3) NOT NULL,
  `prof_vala` decimal(8,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `os_topografia_revisoes`
--

CREATE TABLE `os_topografia_revisoes` (
  `id` int(11) NOT NULL,
  `os_id` int(11) NOT NULL,
  `revisao` int(11) NOT NULL,
  `declividade_de` decimal(8,6) NOT NULL,
  `declividade_para` decimal(8,6) NOT NULL,
  `cota_fj_de` decimal(10,3) NOT NULL,
  `cota_fj_para` decimal(10,3) NOT NULL,
  `alterado_por` int(11) NOT NULL,
  `alterado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `planejamentos`
--

CREATE TABLE `planejamentos` (
  `id` int(11) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `planejador_id` int(11) NOT NULL,
  `data_execucao` date DEFAULT NULL,
  `macro` varchar(100) DEFAULT NULL,
  `medicao` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `contrato` varchar(100) DEFAULT NULL,
  `bacia` varchar(100) DEFAULT NULL,
  `trecho` varchar(100) DEFAULT NULL,
  `pv_montante` varchar(50) DEFAULT NULL,
  `tipo_pi_montante` varchar(50) DEFAULT NULL,
  `quantidade_pvs` int(11) DEFAULT NULL,
  `tipo_pavimento` enum('paralelepipedo_regular','paralelepipedo_irregular','bloco_concreto','asfalto','asfalto_paralelepipedo','chao_batido','calcada') DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('ATIVO','FINALIZADO') DEFAULT 'ATIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `planejamento_dias`
--

CREATE TABLE `planejamento_dias` (
  `id` int(11) NOT NULL,
  `planejamento_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `dia_semana` varchar(20) DEFAULT NULL,
  `producao` tinyint(1) DEFAULT 1,
  `motivo_sem_producao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `planejamento_trechos`
--

CREATE TABLE `planejamento_trechos` (
  `id` int(11) NOT NULL,
  `dia_id` int(11) NOT NULL,
  `pv_montante` varchar(50) DEFAULT NULL,
  `pv_juzante` varchar(50) DEFAULT NULL,
  `comprimento` decimal(10,2) DEFAULT NULL,
  `ramais` int(11) DEFAULT NULL,
  `os_pdf` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_usuario`
--

CREATE TABLE `tipos_usuario` (
  `id` tinyint(4) NOT NULL,
  `nome` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `trechos`
--

CREATE TABLE `trechos` (
  `id` int(11) NOT NULL,
  `bacia` varchar(100) DEFAULT NULL,
  `pv_montante` varchar(50) NOT NULL,
  `pv_jusante` varchar(50) DEFAULT NULL,
  `tipo_pi_montante` varchar(50) DEFAULT NULL,
  `extensao` decimal(8,2) DEFAULT NULL COMMENT 'metros',
  `profundidade_media` decimal(6,2) DEFAULT NULL COMMENT 'metros',
  `dn` varchar(30) DEFAULT NULL COMMENT 'ex: 200 PVC',
  `rua` varchar(150) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `contrato` varchar(100) DEFAULT NULL,
  `ramais` int(11) DEFAULT 0,
  `status_rede` enum('livre','programado','execucao','concluido') NOT NULL DEFAULT 'livre',
  `status_repav` enum('aguardando','execucao','medido') DEFAULT NULL,
  `criado_por` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `trecho_materiais`
--

CREATE TABLE `trecho_materiais` (
  `id` int(11) NOT NULL,
  `trecho_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `quantidade` decimal(10,3) NOT NULL,
  `observacao` varchar(255) DEFAULT NULL,
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo_usuario` tinyint(4) NOT NULL COMMENT '1=Master,2=Admin,3=Proprietario,4=Planejador,5=Executor',
  `ativo` tinyint(4) DEFAULT 1,
  `ultimo_acesso` datetime DEFAULT NULL,
  `force_password_change` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `alertas_falta_material`
--
ALTER TABLE `alertas_falta_material`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diario` (`diario_id`),
  ADD KEY `idx_equipe` (`equipe_id`),
  ADD KEY `idx_resolvido` (`resolvido`);

--
-- Índices de tabela `caminhamentos`
--
ALTER TABLE `caminhamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_equipe` (`equipe_id`),
  ADD KEY `idx_planejador` (`planejador_id`),
  ADD KEY `idx_data_status` (`data_execucao`,`status`);

--
-- Índices de tabela `caminhamentos_repav`
--
ALTER TABLE `caminhamentos_repav`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cam_repav_equipe_data` (`equipe_id`,`data_execucao`),
  ADD KEY `idx_cam_repav_status` (`status`);

--
-- Índices de tabela `caminhamentos_repav_pavimentos`
--
ALTER TABLE `caminhamentos_repav_pavimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_crp_trecho` (`caminhamento_trecho_id`);

--
-- Índices de tabela `caminhamentos_repav_trechos`
--
ALTER TABLE `caminhamentos_repav_trechos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_crt_cam` (`caminhamento_id`),
  ADD KEY `idx_crt_trecho` (`trecho_id`);

--
-- Índices de tabela `caminhamento_trechos`
--
ALTER TABLE `caminhamento_trechos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_caminhamento_trecho` (`caminhamento_id`,`trecho_id`),
  ADD KEY `idx_trecho` (`trecho_id`);

--
-- Índices de tabela `contagens_estoque`
--
ALTER TABLE `contagens_estoque`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `contagens_estoque_itens`
--
ALTER TABLE `contagens_estoque_itens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_contagem_material` (`contagem_id`,`material_id`),
  ADD KEY `idx_contagem` (`contagem_id`),
  ADD KEY `idx_material` (`material_id`);

--
-- Índices de tabela `diarios_execucao`
--
ALTER TABLE `diarios_execucao`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_equipe_trecho_data_versao` (`equipe_id`,`trecho_id`,`data`,`versao`),
  ADD KEY `idx_equipe_data` (`equipe_id`,`data`),
  ADD KEY `idx_trecho` (`trecho_id`),
  ADD KEY `idx_autor` (`autor_id`);

--
-- Índices de tabela `diarios_repav`
--
ALTER TABLE `diarios_repav`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_diario_repav` (`equipe_id`,`trecho_id`,`data`,`versao`),
  ADD KEY `idx_dr_data` (`data`),
  ADD KEY `idx_dr_equipe` (`equipe_id`);

--
-- Índices de tabela `diario_cargas`
--
ALTER TABLE `diario_cargas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diario` (`diario_id`);

--
-- Índices de tabela `diario_equipamentos`
--
ALTER TABLE `diario_equipamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diario` (`diario_id`),
  ADD KEY `idx_equipamento` (`equipamento_id`);

--
-- Índices de tabela `diario_fotos`
--
ALTER TABLE `diario_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diario_step` (`diario_id`,`step_num`);

--
-- Índices de tabela `diario_gps`
--
ALTER TABLE `diario_gps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_diario` (`diario_id`);

--
-- Índices de tabela `diario_interferencias`
--
ALTER TABLE `diario_interferencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diario` (`diario_id`);

--
-- Índices de tabela `diario_pontoes`
--
ALTER TABLE `diario_pontoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diario` (`diario_id`);

--
-- Índices de tabela `diario_presencas`
--
ALTER TABLE `diario_presencas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_diario_func` (`diario_id`,`funcionario_id`),
  ADD KEY `idx_diario` (`diario_id`);

--
-- Índices de tabela `diario_ramais`
--
ALTER TABLE `diario_ramais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diario` (`diario_id`);

--
-- Índices de tabela `diario_reaterros`
--
ALTER TABLE `diario_reaterros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diario` (`diario_id`);

--
-- Índices de tabela `diario_repav_areas`
--
ALTER TABLE `diario_repav_areas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dra_diario` (`diario_id`);

--
-- Índices de tabela `diario_repav_cargas`
--
ALTER TABLE `diario_repav_cargas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_drc_diario` (`diario_id`);

--
-- Índices de tabela `diario_repav_equipamentos`
--
ALTER TABLE `diario_repav_equipamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dre_diario` (`diario_id`);

--
-- Índices de tabela `diario_repav_fotos`
--
ALTER TABLE `diario_repav_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_drf_diario` (`diario_id`),
  ADD KEY `idx_drf_step` (`step_num`);

--
-- Índices de tabela `diario_repav_presencas`
--
ALTER TABLE `diario_repav_presencas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_drp_func` (`diario_id`,`funcionario_id`);

--
-- Índices de tabela `equipamentos_leves`
--
ALTER TABLE `equipamentos_leves`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_referencia` (`referencia`);

--
-- Índices de tabela `equipamentos_pesados`
--
ALTER TABLE `equipamentos_pesados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_placa` (`placa`);

--
-- Índices de tabela `equipamento_documentos`
--
ALTER TABLE `equipamento_documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_equipamento` (`tipo_equipamento`,`equipamento_id`),
  ADD KEY `idx_validade` (`data_validade`);

--
-- Índices de tabela `equipamento_manutencoes`
--
ALTER TABLE `equipamento_manutencoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_equipamento` (`tipo_equipamento`,`equipamento_id`),
  ADD KEY `idx_data` (`data_manutencao`),
  ADD KEY `fk_manut_criado_por` (`criado_por`);

--
-- Índices de tabela `equipes`
--
ALTER TABLE `equipes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `equipes_equipamentos`
--
ALTER TABLE `equipes_equipamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_eq_equip` (`equipe_id`);

--
-- Índices de tabela `equipes_equipamentos_leves`
--
ALTER TABLE `equipes_equipamentos_leves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipe_id` (`equipe_id`),
  ADD KEY `equipamento_id` (`equipamento_id`);

--
-- Índices de tabela `equipes_equipamentos_pesados`
--
ALTER TABLE `equipes_equipamentos_pesados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipe_id` (`equipe_id`),
  ADD KEY `equipamento_id` (`equipamento_id`);

--
-- Índices de tabela `equipes_funcionarios`
--
ALTER TABLE `equipes_funcionarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_eq_func` (`equipe_id`);

--
-- Índices de tabela `equipes_maquinas`
--
ALTER TABLE `equipes_maquinas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipe_id` (`equipe_id`);

--
-- Índices de tabela `equipes_usuarios`
--
ALTER TABLE `equipes_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_equipe_usuario` (`equipe_id`,`usuario_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `equipes_veiculos`
--
ALTER TABLE `equipes_veiculos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_veiculos_equipe` (`equipe_id`);

--
-- Índices de tabela `equipe_funcionarios`
--
ALTER TABLE `equipe_funcionarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `equipe_id` (`equipe_id`,`funcionario_id`);

--
-- Índices de tabela `equipe_membros`
--
ALTER TABLE `equipe_membros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipe_id` (`equipe_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `execucao_fotos`
--
ALTER TABLE `execucao_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `execucao_id` (`execucao_id`);

--
-- Índices de tabela `execucoes`
--
ALTER TABLE `execucoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `executor_id` (`executor_id`),
  ADD KEY `planejamento_id` (`planejamento_id`),
  ADD KEY `dia_id` (`dia_id`),
  ADD KEY `trecho_id` (`trecho_id`);

--
-- Índices de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- Índices de tabela `funcionario_documentos`
--
ALTER TABLE `funcionario_documentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_func_tipo` (`funcionario_id`,`tipo`),
  ADD KEY `idx_funcionario` (`funcionario_id`),
  ADD KEY `idx_validade` (`data_validade`);

--
-- Índices de tabela `log_auditoria`
--
ALTER TABLE `log_auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin` (`admin_id`),
  ADD KEY `idx_afetado` (`usuario_afetado_id`),
  ADD KEY `idx_criado` (`criado_em`);

--
-- Índices de tabela `materiais_catalogo`
--
ALTER TABLE `materiais_catalogo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_codigo` (`codigo`);

--
-- Índices de tabela `materiais_estoque`
--
ALTER TABLE `materiais_estoque`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_material` (`material_id`);

--
-- Índices de tabela `materiais_movimentos`
--
ALTER TABLE `materiais_movimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_material` (`material_id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_referencia` (`referencia_tipo`,`referencia_id`);

--
-- Índices de tabela `medicao_fotos`
--
ALTER TABLE `medicao_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_medicao` (`medicao_id`),
  ADD KEY `idx_tipo` (`tipo`);

--
-- Índices de tabela `medicao_pavimentos`
--
ALTER TABLE `medicao_pavimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_medicao` (`medicao_id`);

--
-- Índices de tabela `medicao_pavimento_linhas`
--
ALTER TABLE `medicao_pavimento_linhas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pavimento` (`pavimento_id`);

--
-- Índices de tabela `medicoes_repavimentacao`
--
ALTER TABLE `medicoes_repavimentacao`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_trecho` (`trecho_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_medicao_criado_por` (`criado_por`);

--
-- Índices de tabela `ordens_servico`
--
ALTER TABLE `ordens_servico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trecho_ativa` (`trecho_id`,`ativa`),
  ADD KEY `idx_criado_por` (`criado_por`);

--
-- Índices de tabela `os_topografia`
--
ALTER TABLE `os_topografia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trecho` (`trecho_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_ostopo_import` (`importado_por`),
  ADD KEY `fk_ostopo_liberado` (`liberado_por`);

--
-- Índices de tabela `os_topografia_estacas`
--
ALTER TABLE `os_topografia_estacas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_os_id` (`os_id`);

--
-- Índices de tabela `os_topografia_revisoes`
--
ALTER TABLE `os_topografia_revisoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_os_id` (`os_id`),
  ADD KEY `fk_ostopo_rev_user` (`alterado_por`);

--
-- Índices de tabela `planejamentos`
--
ALTER TABLE `planejamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipe_id` (`equipe_id`),
  ADD KEY `planejador_id` (`planejador_id`);

--
-- Índices de tabela `planejamento_dias`
--
ALTER TABLE `planejamento_dias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `planejamento_id` (`planejamento_id`);

--
-- Índices de tabela `planejamento_trechos`
--
ALTER TABLE `planejamento_trechos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dia_id` (`dia_id`);

--
-- Índices de tabela `tipos_usuario`
--
ALTER TABLE `tipos_usuario`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `trechos`
--
ALTER TABLE `trechos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_rede` (`status_rede`),
  ADD KEY `idx_status_repav` (`status_repav`),
  ADD KEY `idx_criado_por` (`criado_por`);

--
-- Índices de tabela `trecho_materiais`
--
ALTER TABLE `trecho_materiais`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_trecho_material` (`trecho_id`,`material_id`),
  ADD KEY `idx_material` (`material_id`),
  ADD KEY `fk_tmat_criado_por` (`criado_por`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alertas_falta_material`
--
ALTER TABLE `alertas_falta_material`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `caminhamentos`
--
ALTER TABLE `caminhamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `caminhamentos_repav`
--
ALTER TABLE `caminhamentos_repav`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `caminhamentos_repav_pavimentos`
--
ALTER TABLE `caminhamentos_repav_pavimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `caminhamentos_repav_trechos`
--
ALTER TABLE `caminhamentos_repav_trechos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `caminhamento_trechos`
--
ALTER TABLE `caminhamento_trechos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `contagens_estoque`
--
ALTER TABLE `contagens_estoque`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `contagens_estoque_itens`
--
ALTER TABLE `contagens_estoque_itens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `diarios_execucao`
--
ALTER TABLE `diarios_execucao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `diarios_repav`
--
ALTER TABLE `diarios_repav`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `diario_cargas`
--
ALTER TABLE `diario_cargas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `diario_equipamentos`
--
ALTER TABLE `diario_equipamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `diario_fotos`
--
ALTER TABLE `diario_fotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `diario_gps`
--
ALTER TABLE `diario_gps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `diario_interferencias`
--
ALTER TABLE `diario_interferencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `diario_pontoes`
--
ALTER TABLE `diario_pontoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `diario_presencas`
--
ALTER TABLE `diario_presencas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `diario_ramais`
--
ALTER TABLE `diario_ramais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `diario_reaterros`
--
ALTER TABLE `diario_reaterros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `diario_repav_areas`
--
ALTER TABLE `diario_repav_areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `diario_repav_cargas`
--
ALTER TABLE `diario_repav_cargas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `diario_repav_equipamentos`
--
ALTER TABLE `diario_repav_equipamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `diario_repav_fotos`
--
ALTER TABLE `diario_repav_fotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `diario_repav_presencas`
--
ALTER TABLE `diario_repav_presencas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipamentos_leves`
--
ALTER TABLE `equipamentos_leves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipamentos_pesados`
--
ALTER TABLE `equipamentos_pesados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipamento_documentos`
--
ALTER TABLE `equipamento_documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipamento_manutencoes`
--
ALTER TABLE `equipamento_manutencoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipes`
--
ALTER TABLE `equipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipes_equipamentos`
--
ALTER TABLE `equipes_equipamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipes_equipamentos_leves`
--
ALTER TABLE `equipes_equipamentos_leves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipes_equipamentos_pesados`
--
ALTER TABLE `equipes_equipamentos_pesados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipes_funcionarios`
--
ALTER TABLE `equipes_funcionarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipes_maquinas`
--
ALTER TABLE `equipes_maquinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipes_usuarios`
--
ALTER TABLE `equipes_usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipes_veiculos`
--
ALTER TABLE `equipes_veiculos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipe_funcionarios`
--
ALTER TABLE `equipe_funcionarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipe_membros`
--
ALTER TABLE `equipe_membros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `execucao_fotos`
--
ALTER TABLE `execucao_fotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `execucoes`
--
ALTER TABLE `execucoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `funcionario_documentos`
--
ALTER TABLE `funcionario_documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `log_auditoria`
--
ALTER TABLE `log_auditoria`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `materiais_catalogo`
--
ALTER TABLE `materiais_catalogo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `materiais_estoque`
--
ALTER TABLE `materiais_estoque`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `materiais_movimentos`
--
ALTER TABLE `materiais_movimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `medicao_fotos`
--
ALTER TABLE `medicao_fotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `medicao_pavimentos`
--
ALTER TABLE `medicao_pavimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `medicao_pavimento_linhas`
--
ALTER TABLE `medicao_pavimento_linhas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `medicoes_repavimentacao`
--
ALTER TABLE `medicoes_repavimentacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ordens_servico`
--
ALTER TABLE `ordens_servico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `os_topografia`
--
ALTER TABLE `os_topografia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `os_topografia_estacas`
--
ALTER TABLE `os_topografia_estacas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `os_topografia_revisoes`
--
ALTER TABLE `os_topografia_revisoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `planejamentos`
--
ALTER TABLE `planejamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `planejamento_dias`
--
ALTER TABLE `planejamento_dias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `planejamento_trechos`
--
ALTER TABLE `planejamento_trechos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `trechos`
--
ALTER TABLE `trechos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `trecho_materiais`
--
ALTER TABLE `trecho_materiais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `caminhamentos`
--
ALTER TABLE `caminhamentos`
  ADD CONSTRAINT `fk_caminhamento_equipe` FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`),
  ADD CONSTRAINT `fk_caminhamento_planejador` FOREIGN KEY (`planejador_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `caminhamento_trechos`
--
ALTER TABLE `caminhamento_trechos`
  ADD CONSTRAINT `fk_camt_caminhamento` FOREIGN KEY (`caminhamento_id`) REFERENCES `caminhamentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_camt_trecho` FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`);

--
-- Restrições para tabelas `diario_cargas`
--
ALTER TABLE `diario_cargas`
  ADD CONSTRAINT `fk_dc_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `diario_equipamentos`
--
ALTER TABLE `diario_equipamentos`
  ADD CONSTRAINT `fk_deq_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `diario_fotos`
--
ALTER TABLE `diario_fotos`
  ADD CONSTRAINT `fk_df_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `diario_gps`
--
ALTER TABLE `diario_gps`
  ADD CONSTRAINT `fk_dgps_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `diario_interferencias`
--
ALTER TABLE `diario_interferencias`
  ADD CONSTRAINT `fk_di_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `diario_pontoes`
--
ALTER TABLE `diario_pontoes`
  ADD CONSTRAINT `fk_dpt_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `diario_presencas`
--
ALTER TABLE `diario_presencas`
  ADD CONSTRAINT `fk_dp_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `diario_ramais`
--
ALTER TABLE `diario_ramais`
  ADD CONSTRAINT `fk_dra_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `diario_reaterros`
--
ALTER TABLE `diario_reaterros`
  ADD CONSTRAINT `fk_dr_diario` FOREIGN KEY (`diario_id`) REFERENCES `diarios_execucao` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `equipamento_manutencoes`
--
ALTER TABLE `equipamento_manutencoes`
  ADD CONSTRAINT `fk_manut_criado_por` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `equipes_equipamentos`
--
ALTER TABLE `equipes_equipamentos`
  ADD CONSTRAINT `fk_eq_equip` FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `equipes_equipamentos_leves`
--
ALTER TABLE `equipes_equipamentos_leves`
  ADD CONSTRAINT `equipes_equipamentos_leves_ibfk_1` FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`),
  ADD CONSTRAINT `equipes_equipamentos_leves_ibfk_2` FOREIGN KEY (`equipamento_id`) REFERENCES `equipamentos_leves` (`id`);

--
-- Restrições para tabelas `equipes_equipamentos_pesados`
--
ALTER TABLE `equipes_equipamentos_pesados`
  ADD CONSTRAINT `equipes_equipamentos_pesados_ibfk_1` FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`),
  ADD CONSTRAINT `equipes_equipamentos_pesados_ibfk_2` FOREIGN KEY (`equipamento_id`) REFERENCES `equipamentos_pesados` (`id`);

--
-- Restrições para tabelas `equipes_funcionarios`
--
ALTER TABLE `equipes_funcionarios`
  ADD CONSTRAINT `fk_eq_func` FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `equipes_maquinas`
--
ALTER TABLE `equipes_maquinas`
  ADD CONSTRAINT `equipes_maquinas_ibfk_1` FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `equipes_usuarios`
--
ALTER TABLE `equipes_usuarios`
  ADD CONSTRAINT `equipes_usuarios_ibfk_1` FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`),
  ADD CONSTRAINT `equipes_usuarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `equipes_veiculos`
--
ALTER TABLE `equipes_veiculos`
  ADD CONSTRAINT `fk_veiculos_equipe` FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `equipe_membros`
--
ALTER TABLE `equipe_membros`
  ADD CONSTRAINT `equipe_membros_ibfk_1` FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`),
  ADD CONSTRAINT `equipe_membros_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `execucao_fotos`
--
ALTER TABLE `execucao_fotos`
  ADD CONSTRAINT `execucao_fotos_ibfk_1` FOREIGN KEY (`execucao_id`) REFERENCES `execucoes` (`id`);

--
-- Restrições para tabelas `execucoes`
--
ALTER TABLE `execucoes`
  ADD CONSTRAINT `execucoes_ibfk_1` FOREIGN KEY (`executor_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `execucoes_ibfk_2` FOREIGN KEY (`planejamento_id`) REFERENCES `planejamentos` (`id`),
  ADD CONSTRAINT `execucoes_ibfk_3` FOREIGN KEY (`dia_id`) REFERENCES `planejamento_dias` (`id`),
  ADD CONSTRAINT `execucoes_ibfk_4` FOREIGN KEY (`trecho_id`) REFERENCES `planejamento_trechos` (`id`);

--
-- Restrições para tabelas `funcionario_documentos`
--
ALTER TABLE `funcionario_documentos`
  ADD CONSTRAINT `fk_fundoc_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `materiais_estoque`
--
ALTER TABLE `materiais_estoque`
  ADD CONSTRAINT `fk_estoque_material` FOREIGN KEY (`material_id`) REFERENCES `materiais_catalogo` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `materiais_movimentos`
--
ALTER TABLE `materiais_movimentos`
  ADD CONSTRAINT `fk_mov_material` FOREIGN KEY (`material_id`) REFERENCES `materiais_catalogo` (`id`),
  ADD CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `medicao_fotos`
--
ALTER TABLE `medicao_fotos`
  ADD CONSTRAINT `fk_foto_medicao` FOREIGN KEY (`medicao_id`) REFERENCES `medicoes_repavimentacao` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `medicao_pavimentos`
--
ALTER TABLE `medicao_pavimentos`
  ADD CONSTRAINT `fk_pavimento_medicao` FOREIGN KEY (`medicao_id`) REFERENCES `medicoes_repavimentacao` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `medicao_pavimento_linhas`
--
ALTER TABLE `medicao_pavimento_linhas`
  ADD CONSTRAINT `fk_linha_pavimento` FOREIGN KEY (`pavimento_id`) REFERENCES `medicao_pavimentos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `medicoes_repavimentacao`
--
ALTER TABLE `medicoes_repavimentacao`
  ADD CONSTRAINT `fk_medicao_criado_por` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_medicao_trecho` FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ordens_servico`
--
ALTER TABLE `ordens_servico`
  ADD CONSTRAINT `fk_os_criado_por` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_os_trecho` FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `os_topografia`
--
ALTER TABLE `os_topografia`
  ADD CONSTRAINT `fk_ostopo_import` FOREIGN KEY (`importado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_ostopo_liberado` FOREIGN KEY (`liberado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_ostopo_trecho` FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `os_topografia_estacas`
--
ALTER TABLE `os_topografia_estacas`
  ADD CONSTRAINT `fk_ostopo_est_os` FOREIGN KEY (`os_id`) REFERENCES `os_topografia` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `os_topografia_revisoes`
--
ALTER TABLE `os_topografia_revisoes`
  ADD CONSTRAINT `fk_ostopo_rev_os` FOREIGN KEY (`os_id`) REFERENCES `os_topografia` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ostopo_rev_user` FOREIGN KEY (`alterado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `planejamentos`
--
ALTER TABLE `planejamentos`
  ADD CONSTRAINT `planejamentos_ibfk_1` FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`),
  ADD CONSTRAINT `planejamentos_ibfk_2` FOREIGN KEY (`planejador_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `planejamento_dias`
--
ALTER TABLE `planejamento_dias`
  ADD CONSTRAINT `planejamento_dias_ibfk_1` FOREIGN KEY (`planejamento_id`) REFERENCES `planejamentos` (`id`);

--
-- Restrições para tabelas `planejamento_trechos`
--
ALTER TABLE `planejamento_trechos`
  ADD CONSTRAINT `planejamento_trechos_ibfk_1` FOREIGN KEY (`dia_id`) REFERENCES `planejamento_dias` (`id`);

--
-- Restrições para tabelas `trechos`
--
ALTER TABLE `trechos`
  ADD CONSTRAINT `fk_trechos_criado_por` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `trecho_materiais`
--
ALTER TABLE `trecho_materiais`
  ADD CONSTRAINT `fk_tmat_criado_por` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tmat_material` FOREIGN KEY (`material_id`) REFERENCES `materiais_catalogo` (`id`),
  ADD CONSTRAINT `fk_tmat_trecho` FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
