<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('CC_PAINEL');
    session_set_cookie_params(['path' => '/cheron_camargo/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

require_once __DIR__ . '/app/config/app.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/helpers/csrf.php';

header('X-Robots-Tag: noindex, nofollow');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = rtrim(REPAV_BASE, '/');
if (str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}
$uri = '/' . trim($uri, '/');
if ($uri === '') $uri = '/';

require_once __DIR__ . '/app/controllers/RepavController.php';
$ctrl = new RepavController($pdo);

match (true) {
    $uri === '/' || $uri === ''
        => $ctrl->home(),

    $uri === '/diario/novo'
        => $ctrl->novo(),

    $uri === '/diario/salvar' && $_SERVER['REQUEST_METHOD'] === 'POST'
        => $ctrl->salvar(),

    preg_match('#^/diario/(\d+)$#', $uri, $m) === 1
        => $ctrl->ver((int)$m[1]),

    preg_match('#^/diario/(\d+)/encerrar$#', $uri, $m) === 1
        => $ctrl->encerrar((int)$m[1]),

    $uri === '/diario/foto' && $_SERVER['REQUEST_METHOD'] === 'POST'
        => $ctrl->uploadFoto(),

    $uri === '/diario/carga' && $_SERVER['REQUEST_METHOD'] === 'POST'
        => $ctrl->addCarga(),

    $uri === '/diario/area' && $_SERVER['REQUEST_METHOD'] === 'POST'
        => $ctrl->addArea(),

    $uri === '/sync' && $_SERVER['REQUEST_METHOD'] === 'POST'
        => $ctrl->sync(),

    default => (function() {
        http_response_code(404);
        echo "Página não encontrada.";
    })()
};
