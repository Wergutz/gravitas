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
