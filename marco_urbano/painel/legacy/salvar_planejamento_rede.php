<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([4]); // Planejador

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: planejamento_rede.php");
    exit;
}

try {
    $pdo->beginTransaction();

    /* ===============================
       PLANEJAMENTO
       =============================== */
    $stmt = $pdo->prepare("
        INSERT INTO planejamentos (equipe_id, planejador_id)
        VALUES (?, ?)
    ");
    $stmt->execute([
        $_POST['equipe_id'],
        $_SESSION['usuario_id']
    ]);

    $planejamentoId = $pdo->lastInsertId();

    /* ===============================
       DIAS
       =============================== */
    foreach ($_POST['planejamento'] as $diaIndex => $dia) {

        $data = $dia['data'];
        $diaSemana = strtoupper(date('l', strtotime($data)));

        $stmt = $pdo->prepare("
            INSERT INTO planejamento_dias
            (planejamento_id, data, dia_semana)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $planejamentoId,
            $data,
            $diaSemana
        ]);

        $diaId = $pdo->lastInsertId();

        /* ===============================
           TRECHOS
           =============================== */
        foreach ($dia['trechos'] as $trechoIndex => $trecho) {

            $osPdf = null;

            /* ===== UPLOAD DA OS ===== */
            if (
                isset($_FILES['planejamento']['name'][$diaIndex]['trechos'][$trechoIndex]['os_pdf']) &&
                $_FILES['planejamento']['error'][$diaIndex]['trechos'][$trechoIndex]['os_pdf'] === UPLOAD_ERR_OK
            ) {
                $tmpName = $_FILES['planejamento']['tmp_name'][$diaIndex]['trechos'][$trechoIndex]['os_pdf'];
                $originalName = $_FILES['planejamento']['name'][$diaIndex]['trechos'][$trechoIndex]['os_pdf'];

                $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                $osPdf = 'os_' . uniqid() . '.' . $ext;

                $destino = __DIR__ . '/uploads/os_trechos/' . $osPdf;

                if (!move_uploaded_file($tmpName, $destino)) {
                    throw new Exception("Erro ao enviar arquivo da OS.");
                }
            }

            /* ===== SALVAR TRECHO ===== */
            $stmt = $pdo->prepare("
                INSERT INTO planejamento_trechos
                (dia_id, pv_montante, pv_juzante, comprimento, ramais, os_pdf)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $diaId,
                $trecho['pv_montante'],
                $trecho['pv_juzante'],
                $trecho['comprimento'],
                $trecho['ramais'],
                $osPdf
            ]);
        }
    }

    $pdo->commit();
    header("Location: planejador.php?salvo=1");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Erro ao salvar planejamento: " . $e->getMessage());
}
