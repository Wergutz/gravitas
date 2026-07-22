<?php
if (!defined('APP_BASE')) require_once __DIR__ . '/../../../config/app.php';
auth_required([4]);

$tipos_legenda = [
    'paralelepipedo_regular'   => 'Paralelepípedo Regular',
    'paralelepipedo_irregular' => 'Paralelepípedo Irregular',
    'bloco_concreto'           => 'Bloco de Concreto',
    'asfalto'                  => 'Asfalto',
    'asfalto_paralelepipedo'   => 'Asfalto + Paralelepípedo',
    'chao_batido'              => 'Chão Batido',
    'calcada'                  => 'Calçada',
];

// Calcular áreas e volumes por pavimento
$totais_por_tipo = [];
$area_total = 0;
foreach ($pavimentos as &$pav) {
    $area = 0;
    $linhas_calc = [];
    if ($pav['linhas_raw']) {
        foreach (explode('|', $pav['linhas_raw']) as $linha) {
            [$c, $l] = explode('x', $linha . 'x0');
            $c = (float)$c; $l = (float)$l;
            $sub = $c * $l;
            $area += $sub;
            $linhas_calc[] = ['c' => $c, 'l' => $l, 'sub' => $sub];
        }
    }
    $pav['_area']   = $area;
    $pav['_linhas'] = $linhas_calc;
    $esp = (float)($pav['espessura_cm'] ?? 0);
    $pav['_volume'] = ($pav['tipo_pavimento'] === 'asfalto' && $esp > 0) ? round($area * $esp / 100, 4) : null;

    $tipo = $pav['tipo_pavimento'];
    if (!isset($totais_por_tipo[$tipo])) $totais_por_tipo[$tipo] = ['area' => 0, 'volume' => 0, 'tem_vol' => false];
    $totais_por_tipo[$tipo]['area'] += $area;
    if ($pav['_volume'] !== null) { $totais_por_tipo[$tipo]['volume'] += $pav['_volume']; $totais_por_tipo[$tipo]['tem_vol'] = true; }
    $area_total += $area;
}
unset($pav);

// Agrupar fotos por tipo
$fotos_por_tipo = [];
foreach ($fotos as $f) {
    $fotos_por_tipo[$f['tipo']][] = $f;
}

$num_doc = 'REPAV-' . date('Y') . '-' . sprintf('%04d', $dados['id']);
$data_ger = date('d/m/Y H:i');
$usuario  = $_SESSION['user'] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Relatório de Repavimentação · <?= htmlspecialchars($dados['pv_montante']) ?> → <?= htmlspecialchars($dados['pv_jusante'] ?? '') ?></title>
<meta name="robots" content="noindex,nofollow">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,'Helvetica Neue',Arial,sans-serif;font-size:11px;color:#1a1a1a;background:#ddd;line-height:1.5}
.toolbar{background:#1A2D4F;color:#fff;padding:10px 20px;display:flex;align-items:center;gap:12px;font-size:12px}
.toolbar a{color:#E0A53D;text-decoration:none;font-weight:700}
.toolbar button{background:#E0A53D;color:#1A2D4F;border:none;padding:7px 18px;border-radius:6px;font-weight:800;font-size:12px;cursor:pointer}
.pagina{width:210mm;min-height:297mm;margin:20px auto;background:#fff;padding:16mm 15mm 20mm;box-shadow:0 2px 20px rgba(0,0,0,.18)}
/* Cabeçalho */
.rpt-header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2.5px solid #1A2D4F;padding-bottom:10px;margin-bottom:12px}
.marca-area{display:flex;align-items:center;gap:10px}
.marca-area svg{width:36px;height:36px;flex:0 0 auto}
.marca-txt b{display:block;font-size:14px;letter-spacing:2px;color:#1A2D4F;font-weight:800}
.marca-txt small{font-size:8.5px;color:#666;letter-spacing:0.5px;text-transform:uppercase}
.doc-meta{text-align:right;font-size:9.5px;color:#555}
.doc-meta strong{display:block;font-size:11px;color:#1A2D4F;font-weight:800;margin-bottom:2px}
/* Título */
.rpt-titulo{text-align:center;margin:10px 0 14px;padding:8px 0;background:#f5f7fa;border-radius:4px}
.rpt-titulo h1{font-size:14px;color:#1A2D4F;text-transform:uppercase;letter-spacing:1px;font-weight:800}
.rpt-titulo p{font-size:11px;color:#555;margin-top:3px}
/* Seções */
.secao{margin-bottom:14px}
.secao-titulo{font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:1.5px;color:#fff;background:#1A2D4F;padding:4px 8px;margin-bottom:8px}
/* Grade de dados */
.grade-dados{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:4px}
.dado label{display:block;font-size:8.5px;text-transform:uppercase;letter-spacing:0.8px;color:#888;font-weight:700;margin-bottom:2px}
.dado span{font-size:11.5px;font-weight:700;color:#1a1a1a}
/* Pavimentos */
.pav-bloco{border:1px solid #dde;border-radius:4px;margin-bottom:8px;overflow:hidden;page-break-inside:avoid}
.pav-head{background:#f0f3f8;padding:6px 10px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #dde}
.pav-num{width:20px;height:20px;background:#1A2D4F;color:#fff;border-radius:50%;display:grid;place-items:center;font-size:10px;font-weight:800;flex:0 0 auto}
.pav-tipo{font-weight:700;font-size:11.5px;color:#1A2D4F}
.pav-esp{margin-left:auto;font-size:10px;color:#777}
.pav-linhas{padding:6px 10px}
table.linhas{width:100%;border-collapse:collapse;font-size:10.5px}
table.linhas td{padding:3px 6px}
table.linhas .sub{text-align:right;color:#555}
table.linhas tr:nth-child(even) td{background:#fafbfd}
.pav-total{background:#eef2f8;padding:5px 10px;border-top:1px solid #dde;display:flex;justify-content:flex-end;gap:20px;font-size:11px}
.pav-total b{color:#1A2D4F;font-weight:800}
/* Totais */
table.totais{width:100%;border-collapse:collapse;font-size:11px}
table.totais th{background:#1A2D4F;color:#fff;padding:5px 8px;text-align:left;font-size:9.5px;text-transform:uppercase;letter-spacing:0.5px}
table.totais td{padding:5px 8px;border-bottom:1px solid #eee}
table.totais tr:nth-child(even) td{background:#f8f9fb}
table.totais tfoot td{font-weight:800;background:#f0f3f8;border-top:2px solid #1A2D4F;font-size:12px}
/* Fotos */
.fotos-secao{margin-bottom:14px}
.fotos-titulo-tipo{font-size:9px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:1px;margin:8px 0 4px}
.fotos-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-bottom:6px}
.foto-item{text-align:center}
.foto-item img{width:100%;border-radius:3px;border:1px solid #ddd;display:block}
.foto-item span{font-size:8.5px;color:#666;margin-top:2px;display:block}
/* Assinatura */
.assinaturas{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:28px;page-break-inside:avoid}
.assinatura{border-top:1.5px solid #888;padding-top:6px;text-align:center;font-size:10px;color:#555}
.assinatura b{display:block;font-size:10.5px;color:#1a1a1a;margin-bottom:2px}
.linha-data{text-align:center;margin-top:16px;font-size:10px;color:#555;page-break-inside:avoid}
/* Rodapé */
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
<div class="toolbar no-print">
    <a href="javascript:history.back()">← Voltar</a>
    <span style="flex:1"></span>
    <span>Documento: <?= htmlspecialchars($num_doc) ?></span>
    <button onclick="window.print()">Imprimir / Salvar PDF</button>
</div>

<div class="pagina">

    <!-- Cabeçalho -->
    <div class="rpt-header">
        <div class="marca-area">
            <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Cheron Camargo">
  <rect x="4" y="4" width="92" height="92" rx="26" fill="none" stroke="#3CB86A" stroke-width="3"/>
  <rect x="14" y="14" width="72" height="72" rx="19" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="50" cy="33" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="50" cy="67" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="33" cy="50" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="67" cy="50" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="50" cy="50" r="5" fill="#3CB86A"/>
</svg>
            <div class="marca-txt">
                <b>CHERON CAMARGO</b>
                <small>Saneamento Básico</small>
            </div>
        </div>
        <div class="doc-meta">
            <strong><?= htmlspecialchars($num_doc) ?></strong>
            Gerado em: <?= $data_ger ?><br>
            Por: <?= htmlspecialchars($usuario['nome'] ?? '—') ?><br>
            Medição #<?= (int)$dados['id'] ?>
            <?php if ($dados['status'] === 'concluida'): ?>
                · <span style="color:#2a9d5c;font-weight:700;">Concluída</span>
            <?php else: ?>
                · <span style="color:#e0a53d;font-weight:700;">Rascunho</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Título -->
    <div class="rpt-titulo">
        <h1>Relatório de Repavimentação</h1>
        <p>PV <?= htmlspecialchars($dados['pv_montante']) ?> → <?= htmlspecialchars($dados['pv_jusante'] ?? '—') ?>
        <?php if ($dados['rua']): ?> · <?= htmlspecialchars($dados['rua']) ?><?php endif; ?></p>
    </div>

    <!-- Dados do Trecho -->
    <div class="secao">
        <div class="secao-titulo">Dados do Trecho</div>
        <div class="grade-dados">
            <div class="dado"><label>PV Montante</label><span><?= htmlspecialchars($dados['pv_montante']) ?></span></div>
            <div class="dado"><label>PV Jusante</label><span><?= htmlspecialchars($dados['pv_jusante'] ?? '—') ?></span></div>
            <div class="dado"><label>Bacia</label><span><?= htmlspecialchars($dados['bacia'] ?? '—') ?></span></div>
            <div class="dado"><label>Extensão</label><span><?= $dados['extensao'] ? number_format((float)$dados['extensao'], 2, ',', '.') . ' m' : '—' ?></span></div>
        </div>
        <div class="grade-dados" style="grid-template-columns:2fr 1fr 1fr;">
            <div class="dado"><label>Rua / Logradouro</label><span><?= htmlspecialchars($dados['rua'] ?? '—') ?></span></div>
            <div class="dado"><label>Data da Medição</label><span><?= $dados['criado_em'] ? date('d/m/Y', strtotime($dados['criado_em'])) : '—' ?></span></div>
            <div class="dado"><label>Status</label><span><?= $dados['status'] === 'concluida' ? 'Concluída' : 'Rascunho' ?></span></div>
        </div>
    </div>

    <!-- Pavimentos -->
    <div class="secao">
        <div class="secao-titulo">Pavimentos Medidos (<?= count($pavimentos) ?>)</div>
        <?php if (empty($pavimentos)): ?>
            <p style="color:#888;font-size:11px;padding:8px 0;">Nenhum pavimento cadastrado.</p>
        <?php else: ?>
            <?php foreach ($pavimentos as $i => $pav): ?>
                <?php $tipo_leg = $tipos_legenda[$pav['tipo_pavimento']] ?? $pav['tipo_pavimento']; ?>
                <div class="pav-bloco">
                    <div class="pav-head">
                        <span class="pav-num"><?= ($i + 1) ?></span>
                        <span class="pav-tipo"><?= htmlspecialchars($tipo_leg) ?></span>
                        <?php if ($pav['espessura_cm']): ?>
                            <span class="pav-esp">Espessura: <?= number_format((float)$pav['espessura_cm'], 1, ',', '.') ?> cm</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($pav['_linhas'])): ?>
                    <div class="pav-linhas">
                        <table class="linhas">
                            <?php foreach ($pav['_linhas'] as $li => $ln): ?>
                            <tr>
                                <td style="color:#888;width:50px;">Linha <?= ($li+1) ?></td>
                                <td><?= number_format($ln['c'], 2, ',', '.') ?> m × <?= number_format($ln['l'], 2, ',', '.') ?> m</td>
                                <td class="sub"><?= number_format($ln['sub'], 2, ',', '.') ?> m²</td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <?php endif; ?>
                    <div class="pav-total">
                        <span>Área: <b><?= number_format($pav['_area'], 2, ',', '.') ?> m²</b></span>
                        <?php if ($pav['_volume'] !== null): ?>
                            <span>Volume: <b><?= number_format($pav['_volume'], 3, ',', '.') ?> m³</b></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Totais -->
    <?php if (!empty($totais_por_tipo)): ?>
    <div class="secao">
        <div class="secao-titulo">Resumo por Tipo de Pavimento</div>
        <table class="totais">
            <thead>
                <tr>
                    <th>Tipo de Pavimento</th>
                    <th style="text-align:right;">Área Total (m²)</th>
                    <th style="text-align:right;">Volume (m³)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($totais_por_tipo as $tipo => $tot): ?>
                <tr>
                    <td><?= htmlspecialchars($tipos_legenda[$tipo] ?? $tipo) ?></td>
                    <td style="text-align:right;"><?= number_format($tot['area'], 2, ',', '.') ?></td>
                    <td style="text-align:right;"><?= $tot['tem_vol'] ? number_format($tot['volume'], 3, ',', '.') : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td><b>ÁREA TOTAL GERAL</b></td>
                    <td style="text-align:right;"><b><?= number_format($area_total, 2, ',', '.') ?> m²</b></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

    <!-- Fotos -->
    <?php if (!empty($fotos_por_tipo)): ?>
    <div class="fotos-secao">
        <div class="secao-titulo">Registro Fotográfico</div>
        <?php
        $tipo_labels = ['antes' => 'Antes', 'durante' => 'Durante', 'depois' => 'Depois', 'croqui' => 'Croqui'];
        foreach (['antes','durante','depois','croqui'] as $tipo_f):
            if (empty($fotos_por_tipo[$tipo_f])) continue;
        ?>
            <div class="fotos-titulo-tipo"><?= $tipo_labels[$tipo_f] ?> (<?= count($fotos_por_tipo[$tipo_f]) ?>)</div>
            <div class="fotos-grid">
                <?php foreach ($fotos_por_tipo[$tipo_f] as $foto): ?>
                    <div class="foto-item">
                        <img src="<?= APP_BASE ?>/uploads/repavimentacao/<?= htmlspecialchars($foto['arquivo']) ?>"
                             alt="<?= htmlspecialchars($tipo_labels[$tipo_f]) ?>"
                             onerror="this.style.display='none'">
                        <span><?= htmlspecialchars($tipo_labels[$tipo_f]) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Assinaturas -->
    <div class="linha-data">
        _______________, _____ de __________________ de ________
    </div>
    <div class="assinaturas">
        <div class="assinatura">
            <b>Responsável Técnico</b>
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
        <span>CHERON CAMARGO · Relatório de Repavimentação · <?= htmlspecialchars($num_doc) ?></span>
        <span>Gerado em <?= $data_ger ?></span>
    </div>

</div>
</body>
</html>
