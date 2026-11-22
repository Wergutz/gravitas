<?php
declare(strict_types=1);

const GW_LADOS = ['frente', 'verso'];
const GW_TIPOS_ARTE = [
    'image/png'     => 'png',
    'image/jpeg'    => 'jpg',
    'image/webp'    => 'webp',
    'image/svg+xml' => 'svg',
];

function gw_config(): array
{
    static $cfg = null;
    return $cfg ??= require __DIR__ . '/config.php';
}

function gw_dir_orcamentos(): string
{
    return dirname(__DIR__) . '/dados/orcamentos';
}

function gw_codigo_valido(string $c): bool
{
    return (bool) preg_match('/^GW\d{6}-[A-F0-9]{4}$/', $c);
}

function gw_json(mixed $dados, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Menor faixa de estampa em que a arte cabe, em qualquer orientação. */
function gw_faixa(float $w, float $h): ?array
{
    foreach (gw_config()['estampa'] as $f) {
        if (($w <= $f['larg'] + 1e-6 && $h <= $f['alt'] + 1e-6) ||
            ($w <= $f['alt'] + 1e-6 && $h <= $f['larg'] + 1e-6)) {
            return $f;
        }
    }
    return null;
}

/**
 * Calcula o orçamento a partir do pedido enviado pelo navegador.
 * Mesma regra de js/orcamento.js — o servidor sempre recalcula, nunca confia no valor do cliente.
 *
 * @throws InvalidArgumentException
 */
function gw_calcular(array $pedido): array
{
    $cfg = gw_config();

    $modeloId = (string) ($pedido['modelo'] ?? '');
    if (!isset($cfg['modelos'][$modeloId])) {
        throw new InvalidArgumentException('Modelo inválido.');
    }
    $modelo = $cfg['modelos'][$modeloId];

    $corNome = (string) ($pedido['cor'] ?? '');
    $cor = null;
    foreach ($cfg['cores'] as $c) {
        if ($c['nome'] === $corNome) { $cor = $c; break; }
    }
    if (!$cor) {
        throw new InvalidArgumentException('Cor inválida.');
    }

    $porPeca = (float) $modelo['preco'];
    $lados = [];
    foreach (GW_LADOS as $lado) {
        $l = $pedido['lados'][$lado] ?? null;
        if (!$l) continue;
        $w = round((float) ($l['w'] ?? 0), 1);
        $h = round((float) ($l['h'] ?? 0), 1);
        $x = round((float) ($l['x'] ?? 0), 1);
        $y = round((float) ($l['y'] ?? 0), 1);
        if ($w < 1 || $h < 1 || $w > $cfg['area']['larg'] + 0.05 || $h > $cfg['area']['alt'] + 0.05) {
            throw new InvalidArgumentException("Tamanho da arte ({$lado}) fora da área de impressão.");
        }
        $faixa = gw_faixa($w, $h);
        if (!$faixa) {
            throw new InvalidArgumentException("Arte ({$lado}) maior que a área de impressão.");
        }
        $porPeca += $faixa['preco'];
        $lados[$lado] = ['w' => $w, 'h' => $h, 'x' => $x, 'y' => $y, 'faixa' => $faixa['nome'], 'preco' => $faixa['preco']];
    }
    if (!$lados) {
        throw new InvalidArgumentException('Envie a arte de pelo menos um lado.');
    }

    $qtd = 0;
    $subtotal = 0.0;
    $itens = [];
    foreach ($cfg['tamanhos'] as $tam => $extra) {
        $n = (int) ($pedido['quantidades'][$tam] ?? 0);
        if ($n < 0) throw new InvalidArgumentException('Quantidade inválida.');
        if ($n === 0) continue;
        $unit = $porPeca + (float) $extra;
        $itens[] = ['tamanho' => $tam, 'qtd' => $n, 'unitario' => $unit, 'total' => $unit * $n];
        $qtd += $n;
        $subtotal += $unit * $n;
    }
    if ($qtd < 1) {
        throw new InvalidArgumentException('Informe a quantidade de camisetas.');
    }
    if ($qtd > $cfg['qtd_maxima']) {
        throw new InvalidArgumentException('Quantidade acima do limite. Fale com a gente pelo WhatsApp.');
    }

    $pct = 0;
    foreach ($cfg['descontos'] as $d) {
        if ($qtd >= $d['min']) $pct = max($pct, $d['pct']);
    }
    $desconto = round($subtotal * $pct / 100, 2);
    $total = round($subtotal - $desconto, 2);

    return [
        'modelo'       => ['id' => $modeloId, 'nome' => $modelo['nome'], 'preco' => $modelo['preco']],
        'cor'          => $cor,
        'lados'        => $lados,
        'por_peca'     => $porPeca,
        'itens'        => $itens,
        'qtd'          => $qtd,
        'subtotal'     => round($subtotal, 2),
        'desconto_pct' => $pct,
        'desconto'     => $desconto,
        'total'        => $total,
    ];
}

function gw_brl(float $v): string
{
    return 'R$ ' . number_format($v, 2, ',', '.');
}
