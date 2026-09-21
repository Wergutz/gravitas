<?php
/**
 * ============================================================
 * Devoluções de etapa (PA26) — consulta compartilhada
 * ------------------------------------------------------------
 * O Planejador devolve uma etapa do trecho pela ficha do trecho
 * (POST /BACIN/painel/trechos/devolver), gravando em `trecho_devolucoes`.
 * Este helper responde a pergunta que os apps de campo precisam fazer:
 *
 *   "este trecho está devolvido AGORA nesta etapa?"
 *
 * Pendente = existe devolução daquela etapa MAIS RECENTE que a última
 * conclusão da mesma etapa. Assim que o campo refaz e envia de novo, o
 * aviso some sozinho — ninguém precisa "baixar" a devolução na mão.
 *
 * Usado pelo Painel e pelos apps executor/, executor-ramais/ e
 * executor-repav/ (todos moram no mesmo BACIN/, mesmo banco).
 * ============================================================
 */

if (!function_exists('devolucao_etapas')) {

    /** Rótulos das etapas em linguagem de obra. */
    function devolucao_etapas(): array
    {
        return [
            'rede'         => 'rede (escavação e assentamento)',
            'repav_rede'   => 'repavimentação da vala da rede',
            'repav_ramais' => 'repavimentação das valas dos ramais',
        ];
    }

    /**
     * Devolução ainda NÃO resolvida de uma etapa do trecho.
     *
     * @return array|null ['etapa','etapa_rotulo','motivo','created_at','usuario_nome'] ou null
     */
    function devolucao_pendente(PDO $pdo, int $trechoId, string $etapa): ?array
    {
        $rotulos = devolucao_etapas();
        if ($trechoId <= 0 || !isset($rotulos[$etapa])) {
            return null;
        }

        // 1) última devolução registrada para a etapa
        $st = $pdo->prepare("
            SELECT td.etapa, td.motivo, td.created_at, u.nome AS usuario_nome
              FROM trecho_devolucoes td
              LEFT JOIN usuarios u ON u.id = td.usuario_id
             WHERE td.trecho_id = ? AND td.etapa = ?
             ORDER BY td.created_at DESC, td.id DESC
             LIMIT 1
        ");
        $st->execute([$trechoId, $etapa]);
        $dev = $st->fetch(PDO::FETCH_ASSOC);
        if (!$dev) {
            return null;
        }

        // 2) última conclusão da MESMA etapa
        if ($etapa === 'rede') {
            // a rede é concluída pelo Executor de Rede, ao encerrar o diário
            $st = $pdo->prepare("SELECT rede_concluida_em FROM trechos WHERE id = ?");
            $st->execute([$trechoId]);
            $concluida = $st->fetchColumn();
        } else {
            // a repavimentação é concluída pelo Executor de Pavimento, ao enviar a medição
            $escopo = ($etapa === 'repav_rede') ? 'rede' : 'ramais';
            $st = $pdo->prepare("
                SELECT MAX(updated_at)
                  FROM diarios_repav
                 WHERE trecho_id = ? AND escopo = ? AND status IN ('enviado','aprovado')
            ");
            $st->execute([$trechoId, $escopo]);
            $concluida = $st->fetchColumn();
        }

        if ($concluida && strtotime((string)$concluida) >= strtotime((string)$dev['created_at'])) {
            return null; // já foi refeita depois da devolução
        }

        $dev['etapa_rotulo'] = $rotulos[$etapa] ?? $etapa;
        return $dev;
    }

    /**
     * Bloco de aviso pronto para o celular. Estilos embutidos de propósito:
     * cada app de campo tem o seu CSS, e o aviso precisa aparecer igual em todos.
     *
     * @param array  $dev      retorno de devolucao_pendente()
     * @param string $chamada  primeira linha, em linguagem simples
     * @param string $rodape   linha extra opcional (o que a equipe deve fazer)
     */
    function devolucao_aviso_html(
        array $dev,
        string $chamada = 'O escritório devolveu este trecho:',
        string $rodape  = ''
    ): string {
        $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        $quando = !empty($dev['created_at'])
            ? date('d/m/Y \à\s H:i', strtotime((string)$dev['created_at']))
            : '';
        $quem = trim((string)($dev['usuario_nome'] ?? '')) !== ''
            ? (string)$dev['usuario_nome']
            : 'escritório';

        $html  = '<div style="border:2px solid #B23A2C;background:#FDF2F0;border-radius:12px;'
               . 'padding:12px 13px;margin:10px 0;color:#3A1B16">';
        $html .= '<div style="font-size:15px;font-weight:700;line-height:1.3">'
               . '⚠️ ' . $h($chamada) . '</div>';
        $html .= '<div style="font-size:15px;font-weight:700;margin-top:6px;'
               . 'background:#fff;border-radius:8px;padding:9px 10px;border:1px solid #E7C3BC">'
               . '“' . $h($dev['motivo'] ?? '') . '”</div>';
        $html .= '<div style="font-size:12px;margin-top:7px;opacity:.85">'
               . 'Etapa devolvida: <b>' . $h($dev['etapa_rotulo'] ?? $dev['etapa'] ?? '') . '</b>'
               . ' · por <b>' . $h($quem) . '</b>'
               . ($quando !== '' ? ' em ' . $h($quando) : '')
               . '</div>';
        if ($rodape !== '') {
            $html .= '<div style="font-size:12.5px;margin-top:7px">' . $h($rodape) . '</div>';
        }
        $html .= '</div>';

        return $html;
    }
}
