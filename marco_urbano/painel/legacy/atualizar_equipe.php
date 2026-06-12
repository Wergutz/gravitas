<?php
session_start();
require_once __DIR__ . '/app/config/database.php';
// auth_required([4]);

$id = $_POST['id'];
$nome = $_POST['nome'];
$responsavel = $_POST['responsavel'];
$tipo = $_POST['tipo'];

$stmt = $pdo->prepare("
    UPDATE equipes
    SET nome = ?, responsavel = ?, tipo = ?
    WHERE id = ?
");
$stmt->execute([$nome, $responsavel, $tipo, $id]);

header('Location: equipes_rede.php');
exit;
