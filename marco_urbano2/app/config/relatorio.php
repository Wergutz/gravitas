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

    /* --- partes ------------------------------------------------------- */
    'contratante'  => 'Companhia Riograndense de Saneamento — CORSAN',
    'contratada'   => 'Marco Urbano Empreendimentos Imobiliários LTDA',
    'cnpj'         => '32.999.383/0001-40',
    'crea'         => 'CREA/RS: 271512',

    /* --- obra --------------------------------------------------------- */
    'contrato'     => '4147-2024 (TC 0147/2024)',
    'municipio'    => 'Barra do Quaraí / RS',

    /* --- documento ---------------------------------------------------- */
    'titulo'       => 'Relatório de Medição de Pavimentação',
    'subtitulo'    => 'Reposição de pavimento sobre vala de rede coletora de esgoto',

    /* --- PENDENTES: preencher conforme o contrato ---------------------- */
    // Ex.: '01/07/2026 a 31/07/2026'
    'periodo'      => null,
    // Ex.: '01/08/2026'. Null usa a data de hoje.
    'emissao'      => null,
    // Ex.: 'Eng. Fulano de Tal'
    'resp_tecnico' => null,
    // Ex.: 'ART 1234567890'
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
