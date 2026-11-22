<?php
declare(strict_types=1);
require __DIR__ . '/_lib.php';

$cfg = gw_config();

// Só o que o navegador precisa — nada de e-mail ou dados internos.
gw_json([
    'loja'           => ['nome' => $cfg['loja']['nome'], 'whatsapp' => $cfg['loja']['whatsapp']],
    'modelos'        => $cfg['modelos'],
    'cores'          => $cfg['cores'],
    'tamanhos'       => $cfg['tamanhos'],
    'area'           => $cfg['area'],
    'estampa'        => $cfg['estampa'],
    'descontos'      => $cfg['descontos'],
    'qtd_maxima'     => $cfg['qtd_maxima'],
    'arquivo_max_mb' => $cfg['arquivo_max_mb'],
]);
