-- =====================================================================
--  Gravitas · Contatos com Prefeituras — esquema (MySQL / MariaDB)
--
--  Banco próprio do site gravitas.net.br. Rode UMA vez, no phpMyAdmin,
--  com o banco já selecionado. Depois rode 02-usuarios.sql e 03-seed.sql.
--
--  Traduzido do esquema que a versão Supabase usava. O que mudou e por quê:
--    uuid              -> char(36)   (MySQL não tem tipo uuid nativo)
--    timestamptz       -> datetime   (o fuso é fixado na conexão, -03:00)
--    text[]            -> json       (MySQL não tem array)
--    datetime          -> datetime(3), com milissegundos. A tela pergunta
--                         de 15 em 15 s se algo mudou, comparando contagem
--                         e data da última alteração; com precisão de 1 s,
--                         duas alterações no mesmo segundo ficariam
--                         invisíveis para sempre.
--    políticas de RLS  -> regras no PHP; aqui o banco não filtra por
--                         usuário, porque quem fala com ele é só o
--                         servidor, nunca o navegador.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Equipe. Login próprio do site, separado do painel de obras.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contatos_usuarios` (
  `id`            char(36)     NOT NULL,
  `email`         varchar(190) NOT NULL,
  `nome`          varchar(120) NOT NULL,
  `senha`         varchar(255) NOT NULL,              -- hash do password_hash() do PHP
  `cor`           varchar(9)   NOT NULL DEFAULT '#2E75B6',
  `ativo`         tinyint(1)   NOT NULL DEFAULT 1,
  `trocar_senha`  tinyint(1)   NOT NULL DEFAULT 1,    -- força troca no 1º acesso
  `ultimo_acesso` datetime         NULL DEFAULT NULL,
  `visto_em`      datetime         NULL DEFAULT NULL, -- batida de ponto do "quem está online"
  `criado_em`     datetime(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `contatos_usuarios_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Cidades
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contatos_cidades` (
  `id`                varchar(80)  NOT NULL,          -- slug, ex.: santa-cruz-do-sul
  `nome`              varchar(160) NOT NULL,
  `uf`                char(2)      NOT NULL DEFAULT 'RS',
  `populacao`         int              NULL,
  `prefeito`          varchar(160)     NULL,
  `vice`              varchar(160)     NULL,
  `presidente_camara` varchar(160)     NULL,
  `cadeiras_camara`   int              NULL,
  `status`            varchar(20)  NOT NULL DEFAULT 'prospeccao',
  `resumo`            text             NULL,
  `ordem`             int          NOT NULL DEFAULT 100,
  `atualizado_em`     datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `contatos_cidades_ordem` (`ordem`),
  CONSTRAINT `contatos_cidades_status`
    CHECK (`status` IN ('prospeccao','em_contato','proposta','contratada','pausada'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Pessoas e canais de cada cidade
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contatos_pessoas` (
  `id`             varchar(80)  NOT NULL,
  `cidade_id`      varchar(80)  NOT NULL,
  `grupo`          varchar(20)  NOT NULL,
  `orgao`          varchar(190)     NULL,
  `cargo`          varchar(190)     NULL,
  `nome`           varchar(190) NOT NULL,
  `partido`        varchar(80)      NULL,
  `telefone`       varchar(190)     NULL,
  `whatsapp`       varchar(190)     NULL,
  `email`          varchar(190)     NULL,
  `endereco`       varchar(255)     NULL,
  `rede_social`    varchar(255)     NULL,
  `obs_fonte`      text             NULL,
  `foto`           varchar(255)     NULL,
  `ordem`          int          NOT NULL DEFAULT 100,
  `prioritario`    tinyint(1)   NOT NULL DEFAULT 0,
  `vinculado_a`    varchar(80)      NULL,
  `status`         varchar(24)  NOT NULL DEFAULT 'nao_contatado',
  `responsavel_id` char(36)         NULL,
  `notas`          text             NULL,
  `atualizado_em`  datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `atualizado_por` char(36)         NULL,
  PRIMARY KEY (`id`),
  KEY `contatos_pessoas_cidade` (`cidade_id`,`ordem`),
  CONSTRAINT `contatos_pessoas_cidade_fk`  FOREIGN KEY (`cidade_id`)      REFERENCES `contatos_cidades`(`id`)  ON DELETE CASCADE,
  CONSTRAINT `contatos_pessoas_chefe_fk`   FOREIGN KEY (`vinculado_a`)    REFERENCES `contatos_pessoas`(`id`)  ON DELETE SET NULL,
  CONSTRAINT `contatos_pessoas_resp_fk`    FOREIGN KEY (`responsavel_id`) REFERENCES `contatos_usuarios`(`id`) ON DELETE SET NULL,
  CONSTRAINT `contatos_pessoas_autor_fk`   FOREIGN KEY (`atualizado_por`) REFERENCES `contatos_usuarios`(`id`) ON DELETE SET NULL,
  CONSTRAINT `contatos_pessoas_grupo`  CHECK (`grupo`  IN ('gabinete','nucleo','secretaria','subprefeitura','camara','canal','outro')),
  CONSTRAINT `contatos_pessoas_status` CHECK (`status` IN ('nao_contatado','contato_iniciado','reuniao_realizada','aliado','resistente','sem_resposta'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Interações: cada contato feito
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contatos_interacoes` (
  `id`            char(36)     NOT NULL,
  `cidade_id`     varchar(80)  NOT NULL,
  `pessoa_id`     varchar(80)      NULL,
  `usuario_id`    char(36)     NOT NULL,
  `tipo`          varchar(16)  NOT NULL,
  `data`          datetime(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `resumo`        text         NOT NULL,
  `resultado`     varchar(16)      NULL,
  `proximo_passo` text             NULL,
  `criado_em`     datetime(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `contatos_interacoes_cidade` (`cidade_id`,`data`),
  KEY `contatos_interacoes_pessoa` (`pessoa_id`,`data`),
  CONSTRAINT `contatos_interacoes_cidade_fk`  FOREIGN KEY (`cidade_id`)  REFERENCES `contatos_cidades`(`id`)  ON DELETE CASCADE,
  CONSTRAINT `contatos_interacoes_pessoa_fk`  FOREIGN KEY (`pessoa_id`)  REFERENCES `contatos_pessoas`(`id`)  ON DELETE SET NULL,
  CONSTRAINT `contatos_interacoes_usuario_fk` FOREIGN KEY (`usuario_id`) REFERENCES `contatos_usuarios`(`id`),
  CONSTRAINT `contatos_interacoes_tipo`      CHECK (`tipo`      IN ('reuniao','ligacao','whatsapp','email','visita','evento','nota')),
  CONSTRAINT `contatos_interacoes_resultado` CHECK (`resultado` IS NULL OR `resultado` IN ('positivo','neutro','negativo','sem_resposta'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Agendas: compromissos marcados
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contatos_agendas` (
  `id`            char(36)     NOT NULL,
  `cidade_id`     varchar(80)  NOT NULL,
  `usuario_id`    char(36)     NOT NULL,
  `pessoa_ids`    json             NULL,              -- era text[] no Postgres
  `titulo`        varchar(255) NOT NULL,
  `inicio`        datetime     NOT NULL,
  `fim`           datetime         NULL,
  `local`         varchar(255)     NULL,
  `status`        varchar(16)  NOT NULL DEFAULT 'agendado',
  `obs`           text             NULL,
  `criado_em`     datetime(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `atualizado_em` datetime(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `contatos_agendas_inicio` (`inicio`),
  CONSTRAINT `contatos_agendas_cidade_fk`  FOREIGN KEY (`cidade_id`)  REFERENCES `contatos_cidades`(`id`) ON DELETE CASCADE,
  CONSTRAINT `contatos_agendas_usuario_fk` FOREIGN KEY (`usuario_id`) REFERENCES `contatos_usuarios`(`id`),
  CONSTRAINT `contatos_agendas_status` CHECK (`status` IN ('agendado','realizado','cancelado'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
