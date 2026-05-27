<?php
$SECRET = 'gravitas2026';

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $payload, $SECRET);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    die('Unauthorized');
}

$output = shell_exec('cd ' . escapeshellarg(__DIR__) . ' && git pull origin main 2>&1');

echo json_encode(['ok' => true, 'output' => $output]);
