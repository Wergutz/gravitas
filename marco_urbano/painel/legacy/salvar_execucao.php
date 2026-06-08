<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([5]); // Executor

/* ===============================
   VALIDAR EXECUTOR
================================ */
if (!isset($_SESSION['usuario_id'])) {
    die('Acesso negado.');
}

$executor_id = $_SESSION['usuario_id'];

/* ===============================
   DADOS DO FORMULÁRIO
================================ */
$planejamento_id = $_POST['planejamento_id'] ?? null;
$metragem        = $_POST['metragem'] ?? null;
$ramais          = $_POST['ramais'] ?? null;
$latitude        = $_POST['latitude'] ?? null;
$longitude       = $_POST['longitude'] ?? null;

/* ===============================
   VALIDAÇÕES BÁSICAS
================================ */
if (!$planejamento_id) {
    die('Planejamento não informado.');
}

if ($metragem === null || $ramais === null) {
    die('Metragem e ramais são obrigatórios.');
}

/* ===============================
   INSERIR EXECUÇÃO
   (dia_id e trecho_id ficam NULL)
================================ */
$stmt = $pdo->prepare("
    INSERT INTO execucoes
    (planejamento_id, dia_id, trecho_id, executor_id,
     metragem, ramais, latitude, longitude)
    VALUES (?, NULL, NULL, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $planejamento_id,
    $executor_id,
    $metragem,
    $ramais,
    $latitude,
    $longitude
]);

/* ===============================
   REDIRECIONA
================================ */
header('Location: executor.php?execucao_salva=1');
exit;
