<?php
declare(strict_types=1);
require __DIR__ . '/_lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gw_json(['erro' => 'Método não permitido.'], 405);
}

// Upload maior que post_max_size chega com $_POST vazio.
if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    gw_json(['erro' => 'Arquivos grandes demais para o servidor. Tente uma arte menor.'], 413);
}

// Campo isca: robôs preenchem, pessoas não veem.
if (!empty($_POST['site'])) {
    gw_json(['erro' => 'Envio recusado.'], 400);
}

$cfg = gw_config();

try {
    $pedido = json_decode((string) ($_POST['dados'] ?? ''), true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($pedido)) throw new InvalidArgumentException('Dados inválidos.');

    $nome = trim((string) ($pedido['cliente']['nome'] ?? ''));
    $whats = preg_replace('/\D/', '', (string) ($pedido['cliente']['whatsapp'] ?? ''));
    $email = trim((string) ($pedido['cliente']['email'] ?? ''));
    $obs = trim((string) ($pedido['cliente']['obs'] ?? ''));

    if (mb_strlen($nome) < 2 || mb_strlen($nome) > 80) throw new InvalidArgumentException('Informe seu nome.');
    if (strlen($whats) < 10 || strlen($whats) > 13) throw new InvalidArgumentException('WhatsApp inválido. Use DDD + número.');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('E-mail inválido.');
    if (mb_strlen($obs) > 1000) throw new InvalidArgumentException('Observações muito longas (máx. 1000 caracteres).');

    $calc = gw_calcular($pedido);
} catch (JsonException) {
    gw_json(['erro' => 'Dados inválidos.'], 400);
} catch (InvalidArgumentException $e) {
    gw_json(['erro' => $e->getMessage()], 422);
}

// ---------- Arquivos ----------
$maxBytes = (int) $cfg['arquivo_max_mb'] * 1024 * 1024;
$finfo = new finfo(FILEINFO_MIME_TYPE);

function gw_arquivo(string $campo, array $tipos, int $max, finfo $finfo): ?array
{
    $f = $_FILES[$campo] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        gw_json(['erro' => 'Arquivo grande demais para o servidor.'], 413);
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        gw_json(['erro' => 'Falha ao receber o arquivo. Tente novamente.'], 400);
    }
    if ($f['size'] > $max) {
        gw_json(['erro' => 'Arquivo acima do tamanho permitido.'], 413);
    }
    $mime = $finfo->file($f['tmp_name']) ?: '';
    if ($mime === 'text/xml' || $mime === 'text/plain' || $mime === 'image/svg') $mime = 'image/svg+xml';
    if (!isset($tipos[$mime])) {
        gw_json(['erro' => 'Formato de arquivo não aceito. Use PNG, JPG, WEBP ou SVG.'], 415);
    }
    if ($mime === 'image/svg+xml' && !str_contains((string) file_get_contents($f['tmp_name'], false, null, 0, 4096), '<svg')) {
        gw_json(['erro' => 'Arquivo SVG inválido.'], 415);
    }
    return ['tmp' => $f['tmp_name'], 'ext' => $tipos[$mime], 'nome_original' => mb_substr(basename((string) $f['name']), 0, 120), 'bytes' => (int) $f['size']];
}

$artes = [];
foreach (array_keys($calc['lados']) as $lado) {
    $a = gw_arquivo("arte_{$lado}", GW_TIPOS_ARTE, $maxBytes, $finfo);
    if (!$a) gw_json(['erro' => 'Arte ' . ($lado === 'frente' ? 'da frente' : 'do verso') . ' não recebida.'], 422);
    $artes[$lado] = $a;
}
$mockup = gw_arquivo('mockup', ['image/png' => 'png', 'image/jpeg' => 'jpg'], 10 * 1024 * 1024, $finfo);

// ---------- Gravação ----------
$base = gw_dir_orcamentos();
if (!is_dir($base) && !mkdir($base, 0755, true)) {
    gw_json(['erro' => 'Erro no servidor ao salvar o orçamento.'], 500);
}

do {
    $codigo = 'GW' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
    $dir = "{$base}/{$codigo}";
} while (is_dir($dir));
mkdir($dir, 0755);

$arquivos = [];
foreach ($artes as $lado => $a) {
    $arq = "arte-{$lado}.{$a['ext']}";
    move_uploaded_file($a['tmp'], "{$dir}/{$arq}");
    $arquivos["arte_{$lado}"] = ['arquivo' => $arq, 'nome_original' => $a['nome_original'], 'bytes' => $a['bytes']];
}
if ($mockup) {
    $arq = "mockup.{$mockup['ext']}";
    move_uploaded_file($mockup['tmp'], "{$dir}/{$arq}");
    $arquivos['mockup'] = ['arquivo' => $arq];
}

$registro = [
    'codigo'   => $codigo,
    'criado'   => date('c'),
    'status'   => 'novo',
    'cliente'  => ['nome' => $nome, 'whatsapp' => $whats, 'email' => $email, 'obs' => $obs],
    'calculo'  => $calc,
    'arquivos' => $arquivos,
    'ip'       => $_SERVER['REMOTE_ADDR'] ?? '',
];
file_put_contents("{$dir}/orcamento.json", json_encode($registro, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);

// Aviso por e-mail (opcional)
if (!empty($cfg['loja']['email'])) {
    $assunto = "Novo orçamento {$codigo} — " . gw_brl($calc['total']);
    $corpo = "Cliente: {$nome}\nWhatsApp: {$whats}\nE-mail: {$email}\n\n"
        . "{$calc['qtd']} camisetas {$calc['modelo']['nome']} {$calc['cor']['nome']}\n"
        . "Total: " . gw_brl($calc['total']) . "\n\nObs.: {$obs}\n";
    @mail($cfg['loja']['email'], '=?UTF-8?B?' . base64_encode($assunto) . '?=', $corpo, "Content-Type: text/plain; charset=UTF-8");
}

gw_json(['ok' => true, 'codigo' => $codigo, 'total' => $calc['total'], 'calculo' => $calc]);
