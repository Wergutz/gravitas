<?php
// Espelha 1:1 os 7 tipos da legenda "Tipo de Pavimentação" do Resumo PI oficial.
const TIPOS_PAVIMENTO = [
    'cbuq'              => 'CBUQ',
    'calcada_reg_irreg' => 'Calçada Regular e Irregular',
    'paralelepipedo'    => 'Paralelepípedo',
    'bloco_concreto'    => 'Bloco Pré-moldado de Concreto',
    'sem_pavimento'     => 'Sem Pavimentação',
    'cbuq_pedra'        => 'CBUQ + Pedra',
    'calcada_mod'       => 'Calçada Modular',
];

function tipo_pavimento_label(string $valor): string
{
    return TIPOS_PAVIMENTO[$valor] ?? $valor;
}

/**
 * Tipos de pavimento que dispensam o croqui.
 *
 * O croqui é o desenho cotado da área reposta. Em trecho sem
 * pavimentação não há pavimento a repor, então não há o que cotar —
 * exigir o desenho travaria o lançamento sem nada para desenhar.
 */
const TIPOS_SEM_CROQUI = [
    'sem_pavimento',
];

/** O croqui é obrigatório para este tipo de pavimento? */
function exige_croqui(?string $tipoPavimento): bool
{
    return !in_array((string) $tipoPavimento, TIPOS_SEM_CROQUI, true);
}
