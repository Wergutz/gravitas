<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('CC_PAINEL');
    session_set_cookie_params(['path' => '/cheron_camargo/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

require_once __DIR__ . '/app/config/app.php';
require_once __DIR__ . '/app/helpers/auth.php';
auth_required_topografo();

header('X-Robots-Tag: noindex, nofollow');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = APP_BASE;
if (strpos($uri, $base) === 0) {
    $uri = substr($uri, strlen($base));
}
if ($uri === '' || $uri === null) $uri = '/';
$currentRoute = $uri;

if ($uri === '/' || $uri === '') {
    header('Location: ' . APP_BASE . '/topografia');
    exit;
}

if ($uri === '/topografia') {
    require_once __DIR__ . '/app/controllers/TopografiaController.php';
    (new TopografiaController())->index();
    exit;
}
if ($uri === '/topografia/importar') {
    require_once __DIR__ . '/app/controllers/TopografiaController.php';
    (new TopografiaController())->importar();
    exit;
}
if (preg_match('#^/topografia/(\d+)/declividade$#', $uri, $m)) {
    require_once __DIR__ . '/app/controllers/TopografiaController.php';
    (new TopografiaController())->editarDeclividade((int)$m[1]);
    exit;
}
if (preg_match('#^/topografia/(\d+)/ver$#', $uri, $m)) {
    require_once __DIR__ . '/app/controllers/TopografiaController.php';
    (new TopografiaController())->verOS((int)$m[1]);
    exit;
}

http_response_code(404);
echo "Página não encontrada";
