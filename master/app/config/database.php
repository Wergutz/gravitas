<?php
if (!defined('MASTER_BASE')) require_once __DIR__ . '/app.php';

$host = 'localhost';
$db   = 'u278289683_vh_planeja';
$user = 'u278289683_visionhub_2';
$pass = 'geb91/RS';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    die('Erro de conexão: ' . $e->getMessage());
}
