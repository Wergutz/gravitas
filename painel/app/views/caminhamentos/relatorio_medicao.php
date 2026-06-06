<?php
if (!defined('APP_BASE')) require_once __DIR__ . '/../../../config/app.php';
auth_required([4]);

$num_doc  = 'MERED-' . date('Y') . '-' . sprintf('%04d', $caminhamento['id']);
$data_ger = date('d/m/Y H:i');
$usuario  = $_SESSION['user'] ?? [];

$status_cam_labels = [
    'rascunho'  => ['Rascunho',     '#888'],
    'publicado' => ['Publicado',    '#2563eb'],
    'execucao'  => ['Em Execução',  '#d97706'],
    'concluido' => ['Concluído',    '#2a9d5c'],
];

$total_trechos   = count($trechos);
$conc_trechos    = count(array_filter($trechos, fn($t) => $t['ct_status'] === 'concluido'));
$extensao_total  = array_sum(array_column($trechos, 'extensao'));
$extensao_conc   = array_sum(array_map(
    fn($t) => $t['ct_status'] === 'concluido' ? (float)$t['extensao'] : 0,
    $trechos
));

[$cam_label, $cam_color] = $status_cam_labels[$caminhamento['status']] ?? [$caminhamento['status'], '#888'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Relatório de Medição da Rede · Caminhamento #<?= (int)$caminhamento['id'] ?></title>
<meta name="robots" content="noindex,nofollow">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,'Helvetica Neue',Arial,sans-serif;font-size:11px;color:#1a1a1a;background:#ddd;line-height:1.5}
.toolbar{background:#1A2D4F;color:#fff;padding:10px 20px;display:flex;align-items:center;gap:12px;font-size:12px}
.toolbar a{color:#E0A53D;text-decoration:none;font-weight:700}
.toolbar button{background:#E0A53D;color:#1A2D4F;border:none;padding:7px 18px;border-radius:6px;font-weight:800;font-size:12px;cursor:pointer}
.pagina{width:210mm;min-height:297mm;margin:20px auto;background:#fff;padding:16mm 15mm 20mm;box-shadow:0 2px 20px rgba(0,0,0,.18)}
.rpt-header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2.5px solid #1A2D4F;padding-bottom:10px;margin-bottom:12px}
.marca-area{display:flex;align-items:center;gap:10px}
.marca-area svg{width:36px;height:36px;flex:0 0 auto}
.marca-txt b{display:block;font-size:14px;letter-spacing:2px;color:#1A2D4F;font-weight:800}
.marca-txt small{font-size:8.5px;color:#666;letter-spacing:0.5px;text-transform:uppercase}
.doc-meta{text-align:right;font-size:9.5px;color:#555}
.doc-meta strong{display:block;font-size:11px;color:#1A2D4F;font-weight:800;margin-bottom:2px}
.rpt-titulo{text-align:center;margin:10px 0 14px;padding:10px 0;background:#f5f7fa;border-radius:4px}
.rpt-titulo h1{font-size:15px;color:#1A2D4F;text-transform:uppercase;letter-spacing:1.5px;font-weight:800}
.rpt-titulo p{font-size:11px;color:#555;margin-top:3px}
.secao{margin-bottom:14px}
.secao-titulo{font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:1.5px;color:#fff;background:#1A2D4F;padding:4px 8px;margin-bottom:8px}
.grade-dados{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:4px}
.dado label{display:block;font-size:8.5px;text-transform:uppercase;letter-spacing:0.8px;color:#888;font-weight:700;margin-bottom:2px}
.dado span{font-size:11.5px;font-weight:700;color:#1a1a1a}
/* KPIs */
.kpis-row{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px}
.kpi-box{background:#f5f7fa;border:1px solid #dde;border-radius:4px;padding:10px 8px;text-align:center}
.kpi-box .num{font-size:22px;font-weight:800;color:#1A2D4F;line-height:1.1}
.kpi-box .lab{font-size:8.5px;text-transform:uppercase;letter-spacing:0.8px;color:#888;font-weight:700;margin-top:3px}
.prog-bar{background:#dde;border-radius:99px;height:6px;margin-top:5px;overflow:hidden}
.prog-bar-fill{background:#2a9d5c;height:100%;border-radius:99px;transition:width .3s}
/* Trechos table */
table.trch{width:100%;border-collapse:collapse;font-size:11px}
table.trch th{background:#1A2D4F;color:#fff;padding:6px 8px;font-size:9.5px;text-transform:uppercase;letter-spacing:0.4px;text-align:left}
table.trch td{padding:6px 8px;border-bottom:1px solid #eee;vertical-align:middle}
table.trch tr:nth-child(even) td{background:#f8f9fb}
table.trch tfoot td{font-weight:800;background:#f0f3f8;border-top:2px solid #1A2D4F}
.chip-ok{background:#dcf5e7;color:#1a6e40;border-radius:99px;padding:2px 8px;font-size:9px;font-weight:700;white-space:nowrap}
.chip-pend{background:#f0f0f0;color:#666;border-radius:99px;padding:2px 8px;font-size:9px;font-weight:700;white-space:nowrap}
.chip-exec{background:#fff3dc;color:#7a5200;border-radius:99px;padding:2px 8px;font-size:9px;font-weight:700;white-space:nowrap}
/* Materiais */
table.mat{width:100%;border-collapse:collapse;font-size:11px}
table.mat th{background:#1A2D4F;color:#fff;padding:6px 8px;font-size:9.5px;text-transform:uppercase;letter-spacing:0.4px;text-align:left}
table.mat td{padding:6px 8px;border-bottom:1px solid #eee}
table.mat tr:nth-child(even) td{background:#f8f9fb}
table.mat tfoot td{font-weight:800;background:#f0f3f8;border-top:2px solid #1A2D4F}
.obs-box{background:#fafbfd;border:1px solid #dde;border-radius:4px;padding:8px 12px;font-size:11px;color:#333;font-style:italic}
.assinaturas{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:28px;page-break-inside:avoid}
.assinatura{border-top:1.5px solid #888;padding-top:6px;text-align:center;font-size:10px;color:#555}
.assinatura b{display:block;font-size:10.5px;color:#1a1a1a;margin-bottom:2px}
.linha-data{text-align:center;margin-top:16px;font-size:10px;color:#555;page-break-inside:avoid}
.rpt-footer{margin-top:20px;border-top:1px solid #ccc;padding-top:6px;display:flex;justify-content:space-between;font-size:8.5px;color:#888}
@media print{
    body{background:#fff}
    .toolbar{display:none!important}
    .pagina{margin:0;box-shadow:none;padding:12mm 12mm 16mm}
    @page{size:A4 portrait;margin:0}
}
</style>
</head>
<body>
<div class="toolbar">
    <a href="javascript:history.back()">← Voltar</a>
    <span style="flex:1"></span>
    <span>Documento: <?= htmlspecialchars($num_doc) ?></span>
    <button onclick="window.print()">Imprimir / Salvar PDF</button>
</div>

<div class="pagina">

    <!-- Cabeçalho -->
    <div class="rpt-header">
        <div class="marca-area">
            <svg viewBox="-4 -4 108 108" xmlns="http://www.w3.org/2000/svg">
                <ellipse cx="50" cy="50" rx="54" ry="19" fill="none" stroke="#B9C1CC" stroke-width="3.5" transform="rotate(-24 50 50)"/>
                <circle cx="50" cy="50" r="44" fill="#1A2D4F" stroke="#B9C1CC" stroke-width="2.5"/>
                <circle cx="92.8" cy="23.1" r="4.5" fill="#C9A227"/>
                <path d="M 26.7 73.3 A 33 33 0 1 1 73.3 73.3" fill="none" stroke="#FFFFFF" stroke-width="6" stroke-linecap="round"/>
                <path d="M 50 50 L 67.4 29.3 L 55.5 53.5 Z" fill="#C9A227"/>
                <circle cx="50" cy="50" r="6.5" fill="#C9A227"/>
            </svg>
            <div class="marca-txt">
                <b>GRAVITAS</b>
                <small>Saneamento Básico</small>
            </div>
        </div>
        <div class="doc-meta">
            <strong><?= htmlspecialchars($num_doc) ?></strong>
            Caminhamento #<?= (int)$caminhamento['id'] ?><br>
            Gerado em: <?= $data_ger ?><br>
            Por: <?= htmlspecialchars($usuario['nome'] ?? '—') ?>
        </div>
    </div>

    <!-- Título -->
    <div class="rpt-titulo">
        <h1>Relatório de Medição da Rede</h1>
        <p><?= htmlspecialchars($caminhamento['equipe_nome']) ?> · <?= date('d/m/Y', strtotime($caminhamento['data_execucao'])) ?>
        · <span style="color:<?= $cam_color ?>;font-weight:700;"><?= $cam_label ?></span></p>
    </div>

    <!-- Dados do Caminhamento -->
    <div class="secao">
        <div class="secao-titulo">Dados do Caminhamento</div>
        <div class="grade-dados">
            <div class="dado"><label>Caminhamento Nº</label><span>#<?= (int)$caminhamento['id'] ?></span></div>
            <div class="dado"><label>Equipe</label><span><?= htmlspecialchars($caminhamento['equipe_nome']) ?></span></div>
            <div class="dado"><label>Data de Execução</label><span><?= date('d/m/Y', strtotime($caminhamento['data_execucao'])) ?></span></div>
            <div class="dado"><label>Status</label><span style="color:<?= $cam_color ?>;"><?= $cam_label ?></span></div>
        </div>
        <?php if (!empty($caminhamento['observacoes'])): ?>
        <div class="obs-box" style="margin-top:6px;"><?= htmlspecialchars($caminhamento['observacoes']) ?></div>
        <?php endif; ?>
    </div>

    <!-- KPIs -->
    <div class="kpis-row">
        <div class="kpi-box">
            <div class="num"><?= $conc_trechos ?>/<?= $total_trechos ?></div>
            <div class="lab">Trechos concluídos</div>
            <div class="prog-bar"><div class="prog-bar-fill" style="width:<?= $total_trechos > 0 ? round($conc_trechos/$total_trechos*100) : 0 ?>%"></div></div>
        </div>
        <div class="kpi-box">
            <div class="num"><?= $extensao_total > 0 ? number_format($extensao_total, 0, ',', '.') : '—' ?></div>
            <div class="lab">Extensão total (m)</div>
        </div>
        <div class="kpi-box">
            <div class="num"><?= $extensao_conc > 0 ? number_format($extensao_conc, 0, ',', '.') : '—' ?></div>
            <div class="lab">Extensão concluída (m)</div>
        </div>
        <div class="kpi-box">
            <div class="num"><?= count($materiais_baixados) ?></div>
            <div class="lab">Tipos de material baixados</div>
        </div>
    </div>

    <!-- Trechos -->
    <div class="secao">
        <div class="secao-titulo">Trechos do Caminhamento (<?= $total_trechos ?>)</div>
        <?php if (empty($trechos)): ?>
            <p style="color:#888;font-size:11px;">Nenhum trecho.</p>
        <?php else: ?>
        <table class="trch">
            <thead>
                <tr>
                    <th style="width:28px;">#</th>
                    <th>PV Montante</th>
                    <th>PV Jusante</th>
                    <th>Rua / Bacia</th>
                    <th style="text-align:right;">Extensão</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trechos as $t):
                    $chip = match($t['ct_status']) {
                        'concluido' => ['Concluído', 'chip-ok'],
                        'execucao'  => ['Em exec.',  'chip-exec'],
                        default     => ['Pendente',  'chip-pend'],
                    };
                ?>
                <tr>
                    <td style="color:#888;"><?= (int)$t['sequencia'] ?></td>
                    <td><b><?= htmlspecialchars($t['pv_montante']) ?></b></td>
                    <td><?= htmlspecialchars($t['pv_jusante'] ?? '—') ?></td>
                    <td style="font-size:10.5px;"><?= htmlspecialchars($t['rua'] ?? ($t['bacia'] ?? '—')) ?></td>
                    <td style="text-align:right;"><?= $t['extensao'] ? number_format((float)$t['extensao'], 1, ',', '.') . ' m' : '—' ?></td>
                    <td><span class="<?= $chip[1] ?>"><?= $chip[0] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4"><b>Extensão Total</b></td>
                    <td style="text-align:right;"><b><?= $extensao_total > 0 ? number_format($extensao_total, 1, ',', '.') . ' m' : '—' ?></b></td>
                    <td><b><?= $conc_trechos ?>/<?= $total_trechos ?> concl.</b></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>

    <!-- Materiais Baixados -->
    <?php if (!empty($materiais_baixados)): ?>
    <div class="secao">
        <div class="secao-titulo">Materiais Baixados do Estoque</div>
        <table class="mat">
            <thead>
                <tr>
                    <th>Material</th>
                    <th style="text-align:right;width:100px;">Qtd. Baixada</th>
                    <th style="width:70px;">Unidade</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materiais_baixados as $mb): ?>
                <tr>
                    <td><?= htmlspecialchars($mb['material_nome']) ?></td>
                    <td style="text-align:right;font-weight:700;color:#1A2D4F;"><?= number_format((float)$mb['total_baixado'], 2, ',', '.') ?></td>
                    <td style="color:#666;"><?= htmlspecialchars($mb['unidade']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Assinaturas -->
    <div class="linha-data">
        _______________, _____ de __________________ de ________
    </div>
    <div class="assinaturas">
        <div class="assinatura">
            <b>Responsável pelo Planejamento</b>
            <?= htmlspecialchars($usuario['nome'] ?? '______________________') ?><br>
            <span style="font-size:9px;">Gravitas — Planejamento</span>
        </div>
        <div class="assinatura">
            <b>Visto / Aprovação</b>
            <br>
            <span style="font-size:9px;">______________________________</span>
        </div>
    </div>

    <!-- Rodapé -->
    <div class="rpt-footer">
        <span>GRAVITAS · Relatório de Medição da Rede · <?= htmlspecialchars($num_doc) ?></span>
        <span>Gerado em <?= $data_ger ?></span>
    </div>

</div>
</body>
</html>
