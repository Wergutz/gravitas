<?php
// app/config/database.php
if (!defined('APP_BASE')) require_once __DIR__ . '/app.php';

// TODO: banco/usuário/senha assumidos por convenção (mesmo padrão do marco_urbano).
// Confirmar o nome real do banco no cPanel e definir a senha real antes do deploy.
$host = "localhost";
$db   = "u278289683_cheron_camargo";
$user = "u278289683_cheron_camargo";
$pass = "TROCAR_SENHA_REAL_DO_BANCO";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );

    // 🔧 AJUSTE DE FUSO HORÁRIO PARA BRASIL (SEM SUPER)
    $pdo->exec("SET time_zone = '-03:00'");

} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
