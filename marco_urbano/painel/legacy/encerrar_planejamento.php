<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([4]); // Planejador

if (empty($_GET['id'])) {
    $_SESSION['erro'] = 'Planejamento inválido.';
    header("Location: planejamentos_ativos.php");
    exit;
}

$planejamento_id = (int) $_GET['id'];

$stmt = $pdo->prepare("
    UPDATE planejamentos
    SET status = 'FINALIZADO'
    WHERE id = ?
");
$stmt->execute([$planejamento_id]);

$_SESSION['sucesso'] = 'Planejamento encerrado com sucesso.';
header("Location: planejamentos_ativos.php");
exit;
