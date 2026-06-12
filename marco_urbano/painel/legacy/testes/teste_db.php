<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fuso do PHP
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../app/config/database.php';


echo "<h2>Teste de Fuso Horário - Vision Hub</h2>";

echo "<strong>PHP</strong><br>";
echo "date(): " . date('Y-m-d H:i:s') . "<br><br>";

echo "<strong>MySQL</strong><br>";
$stmt = $pdo->query("SELECT NOW()");
echo "NOW(): " . $stmt->fetchColumn() . "<br><br>";

echo "<strong>Diferença esperada:</strong> 0 segundos<br>";

