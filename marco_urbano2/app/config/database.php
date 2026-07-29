<?php
$host = 'localhost';
$db   = 'u278289683_marco_urbano2';
$user = 'u278289683_marco_urbano2';
$pass = 'geb91/RS';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $user,
        $pass,
        $options
    );
} catch (PDOException $e) {
    die('Erro de conexão com banco de dados');
}
