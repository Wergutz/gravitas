<?php
require_once __DIR__ . '/../app/config/database.php';

$trechos = $pdo->query("
    SELECT * FROM trechos
    WHERE area_total = 0
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($trechos as $t) {

    $pdo->prepare("
        UPDATE trechos SET
            tipo_pavimento = ?,
            quantidade_pvs = ?,
            tipo_pi_montante = ?,
            pv_jusante = ?,
            pv_montante = ?,
            bacia = ?,
            contrato = ?,
            cidade = ?
        WHERE id = ?
    ")->execute([
        $t['quantidade_pvs'],
        $t['tipo_pi_montante'],
        $t['pv_jusante'],
        $t['pv_montante'],
        $t['bacia'],
        $t['contrato'],
        $t['cidade'],
        $t['medicao'], // ← ajuste conforme erro real
        $t['id']
    ]);
}

echo "Correção concluída.";
