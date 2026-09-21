<?php
/**
 * ============================================================
 * Devoluções do Planejador (PA26) — atalho do app de Pavimento
 * ------------------------------------------------------------
 * A consulta mora em painel/app/helpers/devolucoes.php (um único lugar,
 * usado também por executor/ e executor-ramais/). Este arquivo só
 * empurra o require para dentro do app, para o controller poder fazer:
 *
 *     require_once __DIR__ . '/../helpers/devolucoes.php';
 *
 * Funções disponíveis:
 *   devolucao_pendente(PDO $pdo, int $trechoId, string $etapa): ?array
 *       etapa = 'repav_rede' (escopo rede) | 'repav_ramais' (escopo ramais)
 *       devolve ['etapa','etapa_rotulo','motivo','created_at','usuario_nome'] ou null
 *       (null = nada pendente: o campo já refez e enviou depois da devolução)
 *
 *   devolucao_aviso_html(array $dev, string $chamada, string $rodape = ''): string
 *       bloco de aviso pronto, com estilos embutidos — basta ecoar na view.
 * ============================================================
 */
require_once dirname(__DIR__, 3) . '/painel/app/helpers/devolucoes.php';
