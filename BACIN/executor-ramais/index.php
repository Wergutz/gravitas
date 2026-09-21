<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('BC_PAINEL');
    session_set_cookie_params(['path' => '/BACIN/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

require_once __DIR__ . '/app/config/app.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/helpers/csrf.php';

header('X-Robots-Tag: noindex, nofollow');

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = rtrim(RAMAIS_BASE, '/');
if (str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}
$uri = '/' . trim($uri, '/');

require_once __DIR__ . '/app/controllers/RamalController.php';
$ctrl = new RamalController($pdo);
$post = $_SERVER['REQUEST_METHOD'] === 'POST';

match (true) {
    $uri === '/' || $uri === ''
        => $ctrl->home(),

    $uri === '/frente/nova' && $post
        => $ctrl->novaFrente(),

    preg_match('#^/frente/(\d+)$#', $uri, $m) === 1
        => $ctrl->abrirFrente((int)$m[1]),

    preg_match('#^/frente/(\d+)/ramal/(\d+)$#', $uri, $m) === 1
        => $ctrl->abrirFrente((int)$m[1], (int)$m[2]),

    preg_match('#^/frente/(\d+)/encerrar$#', $uri, $m) === 1 && $post
        => $ctrl->encerrarFrente((int)$m[1]),

    $uri === '/ramal/salvar' && $post
        => $ctrl->salvarRamal(),

    $uri === '/ramal/excluir' && $post
        => $ctrl->excluirRamal(),

    default => (function () {
        http_response_code(404);
        echo 'Página não encontrada.';
    })()
};
