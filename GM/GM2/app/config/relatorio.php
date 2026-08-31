<?php
/**
 * Dados fixos do cabeçalho e da assinatura do Relatório Oficial de Medição.
 *
 * Os campos marcados como PENDENTE saem impressos como "a confirmar" no PDF,
 * em destaque, até serem preenchidos aqui. São exatamente os itens que o
 * relatório de alterações listou como "a confirmar no contrato":
 * período da medição, data de emissão, responsável técnico e número da ART.
 *
 * Preencher e reenviar — não exige mexer em código.
 */

const MU_RELATORIO = [

    /* --- partes -------------------------------------------------------
       PENDENTES. Os dados abaixo são da GM e ainda não foram informados;
       enquanto estiverem null, saem no PDF como "a confirmar". Não foram
       herdados da instância de origem de propósito: CNPJ, CREA e
       contratante de outro cliente em relatório da GM seria documento
       falso. */
    'contratante'  => null,
    'contratada'   => 'GM SERVIÇOS',
    'cnpj'         => null,
    'crea'         => null,

    /* --- obra ---------------------------------------------------------
       Contrato e município NÃO precisam ser mantidos aqui: o relatório
       lê os dois dos trechos que entram no documento (campos
       `trechos.contrato` e `trechos.cidade`). Antes eram fixos e todo
       relatório saía com a mesma cidade, mesmo em planejamento de outro
       município.

       O que estiver abaixo só é usado quando os trechos não trazem a
       informação — serve de reserva, não de fonte. */
    'contrato'     => null,
    'municipio'    => null,

    /* --- documento ---------------------------------------------------- */
    'titulo'       => 'Relatório de Medição de Pavimentação',
    'subtitulo'    => 'Reposição de pavimento sobre vala de rede coletora de esgoto',

    /* =================================================================
       PENDENTES — PREENCHER AQUI
       -----------------------------------------------------------------
       Enquanto estiverem como null, saem impressos no PDF como
       "a confirmar", em destaque. Troque o null pelo texto entre aspas.

       ANTES:  'resp_tecnico' => null,
       DEPOIS: 'resp_tecnico' => 'Eng. João da Silva',

       Cuidado só com isto: mantenha as aspas e a vírgula no fim.
       ================================================================= */

    // PERÍODO — normalmente NÃO precisa preencher.
    //
    // O sistema apura sozinho, a partir dos trechos que entram no
    // relatório: usa a data em que as fotos foram TIRADAS (EXIF), que é
    // a evidência de quando o serviço aconteceu em campo. Sem EXIF, cai
    // para a data em que a medição foi lançada.
    //
    // Preencha aqui SÓ se o contrato fixar um período diferente do que
    // os dados mostram — neste caso, o valor daqui vence.
    // Exemplo: '01/07/2026 a 31/07/2026'
    'periodo'      => null,

    // Data de emissão do relatório. Exemplo: '01/08/2026'
    // Deixando null, o sistema usa a data em que o PDF for gerado.
    'emissao'      => null,

    // Nome de quem assina o documento, como deve aparecer sobre a linha
    // de assinatura. Exemplo: 'Eng. João da Silva'
    'resp_tecnico' => null,

    // Número da ART do responsável técnico.
    // Exemplo: 'ART 1234567890'
    'art'          => null,
];

/** Devolve o valor, ou a marcação de pendência para o PDF. */
function mu_rel(string $chave, string $sePendente = 'a confirmar'): string
{
    $v = MU_RELATORIO[$chave] ?? null;
    return ($v === null || $v === '') ? $sePendente : (string) $v;
}

/** true quando o campo ainda não foi preenchido (para destacar no PDF). */
function mu_rel_pendente(string $chave): bool
{
    $v = MU_RELATORIO[$chave] ?? null;
    return $v === null || $v === '';
}
