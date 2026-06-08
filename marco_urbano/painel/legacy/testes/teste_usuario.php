<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/Usuario.php';

$u = new Usuario($pdo);
$user = $u->buscarPorEmail('master@visionhub.local');

var_dump($user);
echo "PHP: " . date('Y-m-d H:i:s') . "<br>";

$stmt = $pdo->query("SELECT NOW()");
echo "MYSQL: " . $stmt->fetchColumn();
