<?php
/*
 * GW Shop — configuração do orçamentador.
 * Todos os preços em reais. Depois de alterar, basta salvar: o site lê daqui.
 */
return [
    'loja' => [
        'nome'     => 'GW Shop',
        'whatsapp' => '5500000000000',   // DDI + DDD + número, só dígitos
        'email'    => '',                // se preenchido, recebe aviso a cada orçamento
    ],

    // Preço da camiseta lisa (sem estampa)
    'modelos' => [
        'basica'    => ['nome' => 'Básica algodão',  'descricao' => '100% algodão fio 30.1', 'preco' => 32.00],
        'oversized' => ['nome' => 'Oversized',       'descricao' => 'Malha 200 g/m², caimento amplo', 'preco' => 45.00],
        'babylook'  => ['nome' => 'Baby look',       'descricao' => 'Modelagem feminina acinturada', 'preco' => 32.00],
        'dryfit'    => ['nome' => 'Dry fit',         'descricao' => 'Poliéster, ideal para esporte', 'preco' => 29.00],
    ],

    'cores' => [
        ['nome' => 'Branco',    'hex' => '#fafafa'],
        ['nome' => 'Preto',     'hex' => '#1d1d1f'],
        ['nome' => 'Mescla',    'hex' => '#a9aaad'],
        ['nome' => 'Marinho',   'hex' => '#22304a'],
        ['nome' => 'Vermelho',  'hex' => '#b3261e'],
        ['nome' => 'Royal',     'hex' => '#1f4fa8'],
        ['nome' => 'Verde',     'hex' => '#2f6b3a'],
        ['nome' => 'Off-white', 'hex' => '#f2ede3'],
    ],

    // Acréscimo por peça em cada tamanho
    'tamanhos' => ['PP' => 0, 'P' => 0, 'M' => 0, 'G' => 0, 'GG' => 4.00, 'XG' => 8.00],

    // Área máxima de impressão em cada lado (cm)
    'area' => ['larg' => 30, 'alt' => 40],

    // Preço por lado estampado, pela menor faixa em que a arte cabe (em qualquer orientação)
    'estampa' => [
        ['nome' => 'Pequena', 'larg' => 10, 'alt' => 10, 'preco' => 8.00],
        ['nome' => 'A5',      'larg' => 15, 'alt' => 21, 'preco' => 13.00],
        ['nome' => 'A4',      'larg' => 21, 'alt' => 30, 'preco' => 19.00],
        ['nome' => 'A3',      'larg' => 30, 'alt' => 40, 'preco' => 27.00],
    ],

    // Desconto sobre o total conforme a quantidade de peças
    'descontos' => [
        ['min' => 10,  'pct' => 5],
        ['min' => 25,  'pct' => 10],
        ['min' => 50,  'pct' => 15],
        ['min' => 100, 'pct' => 20],
    ],

    'qtd_maxima'      => 5000,
    'arquivo_max_mb'  => 20,   // tamanho máximo de cada arte enviada
];
