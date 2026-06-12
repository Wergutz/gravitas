<?php
session_start();

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([4]); // Planejador

$id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$acao  = $_GET['acao'] ?? '';

if ($id <= 0 || !in_array($acao, ['ativar', 'desativar'])) {
    header("Location: equipes_todas.php");
    exit;
}

$novoStatus = ($acao === 'ativar') ? 1 : 0;

$stmt = $pdo->prepare("
    UPDATE equipes
    SET ativo = ?
    WHERE id = ?
");
$stmt->execute([$novoStatus, $id]);

$_SESSION['sucesso'] = ($novoStatus)
    ? 'Equipe ativada com sucesso.'
    : 'Equipe desativada com sucesso.';

/* 🔴 VOLTA PARA A MESMA TELA */
header("Location: equipes_todas.php");
exit;
