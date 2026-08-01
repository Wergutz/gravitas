-- =====================================================================
-- Cadeia de custódia de mídia — marco_urbano2
-- =====================================================================
--
-- Registra, para cada foto/croqui recebido, o SHA-256 do arquivo no
-- instante do recebimento, os metadados EXIF lidos do original, e quem
-- enviou, quando e de qual IP. É o que sustenta a imagem como prova:
-- mesmo com EXIF vazio, prova-se que o arquivo apresentado é bit a bit
-- o mesmo que foi recebido.
--
-- O registro é append-only: correção vira registro novo, nunca UPDATE.
--
-- Rodar manualmente no phpMyAdmin do banco `u278289683_marco_urbano2`.
--
-- Notas de adaptação ao schema real deste sistema:
--   * `trecho_id` referencia `trechos.id` (int unsigned), com FK em
--     CASCADE igual às demais tabelas filhas de `trechos`.
--   * `medicao_id` fica NULL: neste sistema a medição não é uma tabela,
--     é o campo varchar `trechos.medicao`. A coluna é mantida para não
--     divergir do pacote original e para uso futuro.
--   * `validade_pericial` distingue a mídia recebida sob estas regras
--     (1) do acervo anterior carimbado depois (0).
-- =====================================================================

CREATE TABLE IF NOT EXISTS `midia_custodia` (
  `id`               bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `trecho_id`        int(10) UNSIGNED NOT NULL,
  `medicao_id`       int(10) UNSIGNED DEFAULT NULL,
  `tipo`             enum('foto','croqui') NOT NULL,
  `nome_original`    varchar(255) NOT NULL,
  `caminho_original` varchar(255) NOT NULL,
  `mime_real`        varchar(60) NOT NULL,
  `bytes`            bigint(20) UNSIGNED NOT NULL,
  `largura`          int(10) UNSIGNED DEFAULT NULL,
  `altura`           int(10) UNSIGNED DEFAULT NULL,
  `sha256`           char(64) NOT NULL,
  `tem_exif`         tinyint(1) NOT NULL DEFAULT 0,
  `exif_datahora`    datetime DEFAULT NULL,
  `exif_gps_lat`     decimal(10,7) DEFAULT NULL,
  `exif_gps_lon`     decimal(10,7) DEFAULT NULL,
  `exif_dispositivo` varchar(120) DEFAULT NULL,
  `exif_bruto`       mediumtext DEFAULT NULL,
  `enviado_por`      int(10) UNSIGNED DEFAULT NULL,
  `enviado_em`       datetime NOT NULL DEFAULT current_timestamp(),
  `ip_envio`         varchar(45) DEFAULT NULL,
  `validade_pericial` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sha_trecho` (`sha256`, `trecho_id`),
  KEY `idx_trecho` (`trecho_id`),
  KEY `idx_sha` (`sha256`),
  CONSTRAINT `fk_midia_custodia_trecho`
    FOREIGN KEY (`trecho_id`) REFERENCES `trechos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
