<?php
declare(strict_types=1);
require dirname(__DIR__) . '/api/_lib.php';

const GW_STATUS = [
    'novo'      => 'Novo',
    'contato'   => 'Em contato',
    'aprovado'  => 'Aprovado',
    'producao'  => 'Em produção',
    'entregue'  => 'Entregue',
    'cancelado' => 'Cancelado',
];

session_name('gwadmin');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');

function gw_arquivo_senha(): string
{
    return dirname(__DIR__) . '/dados/admin.json';
}

function gw_hash_senha(): ?string
{
    $f = gw_arquivo_senha();
    if (!is_file($f)) return null;
    $d = json_decode((string) file_get_contents($f), true);
    return $d['hash'] ?? null;
}

function gw_logado(): bool
{
    return !empty($_SESSION['gw_admin']);
}

function gw_csrf(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}

function gw_csrf_ok(): bool
{
    return hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''));
}

function gw_exigir_login(): void
{
    if (!gw_logado()) {
        header('Location: ./');
        exit;
    }
}

function gw_ler_orcamento(string $codigo): ?array
{
    if (!gw_codigo_valido($codigo)) return null;
    $f = gw_dir_orcamentos() . "/{$codigo}/orcamento.json";
    if (!is_file($f)) return null;
    return json_decode((string) file_get_contents($f), true) ?: null;
}

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
