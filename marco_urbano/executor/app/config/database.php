<?php
if (!defined('EXECUTOR_BASE')) require_once __DIR__ . '/app.php';

$host = "localhost";
$db   = "u278289683_marco_urbano";
$user = "u278289683_marco_urbano";
$pass = "geb91/RS";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
