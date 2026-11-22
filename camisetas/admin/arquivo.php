<?php
declare(strict_types=1);
require __DIR__ . '/_auth.php';

gw_exigir_login();

$codigo = (string) ($_GET['c'] ?? '');
$chave = (string) ($_GET['f'] ?? '');
$orc = gw_ler_orcamento($codigo);
$info = $orc['arquivos'][$chave] ?? null;
if (!$orc || !$info) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$caminho = gw_dir_orcamentos() . "/{$codigo}/" . basename($info['arquivo']);
if (!is_file($caminho)) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$ext = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
$tipos = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'webp' => 'image/webp', 'svg' => 'image/svg+xml'];
$baixar = isset($_GET['baixar']) || $ext === 'svg'; // SVG sempre como download (segurança)

header('Content-Type: ' . ($baixar ? 'application/octet-stream' : ($tipos[$ext] ?? 'application/octet-stream')));
header('Content-Length: ' . filesize($caminho));
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'");
$nomeDownload = "{$codigo}-" . str_replace('_', '-', $chave) . ".{$ext}";
header('Content-Disposition: ' . ($baixar ? 'attachment' : 'inline') . '; filename="' . $nomeDownload . '"');
readfile($caminho);
