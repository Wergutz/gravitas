<?php
session_start();
require_once __DIR__ . '/app/config/app.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/helpers/csrf.php';
require_once __DIR__ . '/app/controllers/MasterController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = MASTER_BASE;
if (strpos($uri, $base) === 0) $uri = substr($uri, strlen($base));
$uri = rtrim($uri, '/') ?: '/';

$ctrl = new MasterController($pdo);

// /relatorio/{tipo}
if (preg_match('#^/relatorio/([a-z_]+)$#', $uri, $m)) {
    $ctrl->relatorio($m[1]);
    exit;
}

// / (dashboard — todos os modos via ?modo=)
if ($uri === '/' || $uri === '') {
    $ctrl->dashboard();
    exit;
}

http_response_code(404);
echo 'Página não encontrada.';
