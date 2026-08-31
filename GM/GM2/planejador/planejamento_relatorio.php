<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/tipos_pavimento.php';
require_once __DIR__ . '/../app/helpers/midia_url.php';
require_once __DIR__ . '/../app/libs/tcpdf/tcpdf.php';

auth_required([3]);

$planejamentoId = (int) ($_GET['id'] ?? 0);
if (!$planejamentoId) die('Planejamento inválido');

/* PLANEJAMENTO */
$stmt = $pdo->prepare("
    SELECT p.*, u.nome AS usuario
    FROM planejamentos p
    JOIN usuarios u ON u.id = p.usuario_id
    WHERE p.id = ?
");
$stmt->execute([$planejamentoId]);
$planejamento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$planejamento) die('Planejamento não encontrado');

/* TRECHOS */
$stmt = $pdo->prepare("SELECT * FROM trechos WHERE planejamento_id = ?");
$stmt->execute([$planejamentoId]);
$trechos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* PDF */
$pdf = new TCPDF();
$pdf->SetMargins(15, 20, 15);
$pdf->AddPage();

$pdf->SetFont('helvetica','B',16);
$pdf->Cell(0,10,'RELATORIO OFICIAL DE PLANEJAMENTO',0,1,'C');

$pdf->Ln(5);
$pdf->SetFont('helvetica','',11);
$pdf->MultiCell(0,7,
    "Planejamento Nº: {$planejamento['id']}\n".
    "Planejador: {$planejamento['usuario']}\n".
    "Data: ".date('d/m/Y H:i', strtotime($planejamento['created_at']))
);

foreach ($trechos as $i => $t) {

    $pdf->Ln(6);
    $pdf->SetFont('helvetica','B',12);
    $pdf->Cell(0,8,'Trecho '.($i+1),0,1);

    $pdf->SetFont('helvetica','',11);
    $pdf->MultiCell(0,6,
        "Medição: {$t['medicao']}\n".
        "Cidade: {$t['cidade']}\n".
        "Contrato: {$t['contrato']}\n".
        "Bacia: {$t['bacia']}\n".
        "PV Montante: {$t['pv_montante']} | PV Jusante: {$t['pv_jusante']}\n".
        "Tipo PI: {$t['tipo_pi_montante']}\n".
        "Qtd PVs: {$t['quantidade_pvs']}\n".
        "Tipo Pavimento: ".tipo_pavimento_label($t['tipo_pavimento'])."\n".
        "Área Total: {$t['area_total']} m²"
    );

    /* CROQUI */
    $pdf->Ln(3);
    $pdf->SetFont('helvetica','B',11);
    $pdf->Cell(0,6,'Croqui do Trecho:',0,1);

    $croqui = mu_path_midia($t['croqui'], 'croqui');
    if ($croqui !== null) {
        $pdf->Image($croqui, '', '', 160, 0, '', '', 'T', false, 300, 'C');
    } else {
        $pdf->SetFont('helvetica','',10);
        $pdf->Cell(0,6,'Croqui não encontrado.',0,1);
    }

    /* PAVIMENTO */
    $stmt = $pdo->prepare("SELECT * FROM pavimento_produzido WHERE trecho_id = ?");
    $stmt->execute([$t['id']]);

    $pdf->Ln(4);
    $pdf->SetFont('helvetica','B',11);
    $pdf->Cell(60,7,'Comprimento',1);
    $pdf->Cell(60,7,'Largura',1);
    $pdf->Cell(60,7,'Área',1);
    $pdf->Ln();

    $pdf->SetFont('helvetica','',11);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $pdf->Cell(60,7,$p['comprimento'],1);
        $pdf->Cell(60,7,$p['largura'],1);
        $pdf->Cell(60,7,$p['area'],1);
        $pdf->Ln();
    }

    /* FOTOS */
    $stmt = $pdo->prepare("SELECT * FROM trecho_fotos WHERE trecho_id = ?");
    $stmt->execute([$t['id']]);
    $fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($fotos) {
        $pdf->AddPage();
        $pdf->SetFont('helvetica','B',12);
        $pdf->Cell(0,8,'Fotos do Trecho '.($i+1),0,1);

        foreach ($fotos as $f) {
            $img = mu_path_midia($f['arquivo'], 'foto');
            if ($img !== null) {
                $pdf->Image($img, '', '', 80);
                $pdf->Ln(5);
            }
        }
    }
}

if (ob_get_length()) ob_end_clean();
$pdf->Output('relatorio_planejamento_'.$planejamentoId.'.pdf','I');
exit;
