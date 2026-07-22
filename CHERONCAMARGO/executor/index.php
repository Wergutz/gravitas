<?php
// ============================================================
// App do Executor — Roteador principal
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_name('CC_PAINEL');
    session_set_cookie_params(['path' => '/CHERONCAMARGO/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

require_once __DIR__ . '/app/config/app.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/helpers/csrf.php';

header('X-Robots-Tag: noindex, nofollow');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Remove base prefix (/CHERONCAMARGO/executor)
$base = rtrim(EXECUTOR_BASE, '/');
if (str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}
$uri = '/' . trim($uri, '/');
if ($uri === '') $uri = '/';

require_once __DIR__ . '/app/controllers/DiarioController.php';
$ctrl = new DiarioController($pdo);

// Rotas
match (true) {
    // Home — lista de diários / programação do dia
    $uri === '/' || $uri === ''
        => $ctrl->home(),

    // Diário — iniciar/continuar
    $uri === '/diario/novo'
        => $ctrl->novo(),
    $uri === '/diario/salvar' && $_SERVER['REQUEST_METHOD'] === 'POST'
        => $ctrl->salvar(),
    preg_match('#^/diario/(\d+)$#', $uri, $m) === 1
        => $ctrl->ver((int)$m[1]),
    preg_match('#^/diario/(\d+)/encerrar$#', $uri, $m) === 1
        => $ctrl->encerrar((int)$m[1]),

    // Upload de foto (AJAX)
    $uri === '/diario/foto' && $_SERVER['REQUEST_METHOD'] === 'POST'
        => $ctrl->uploadFoto(),

    // Sync offline (recebe JSON da fila localStorage)
    $uri === '/sync' && $_SERVER['REQUEST_METHOD'] === 'POST'
        => $ctrl->sync(),

    default => (function() {
        http_response_code(404);
        echo "Página não encontrada.";
    })()
};
