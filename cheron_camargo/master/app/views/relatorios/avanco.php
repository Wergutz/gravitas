<?php
$empresa    = defined('APP_CLIENT') ? APP_CLIENT : 'GRAVITAS';
$periodoFmt = date('d/m/Y', strtotime($inicio)) . ' a ' . date('d/m/Y', strtotime($fim));
$resto      = max(0, $previsto - $executadoTotal);
$naoInic    = max(0, $previsto - $executadoTotal);
// Donut SVG values
$r = 54; $cx = 65; $cy = 65; $circ = 2 * M_PI * $r;
$dashExec   = $pctAvanco / 100 * $circ;
$dashResto  = $circ - $dashExec;
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Avanço Físico — <?= htmlspecialchars($periodoFmt) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Inter",-apple-system,Helvetica,Arial,sans-serif;font-size:12px;color:#1E2738;background:#fff;padding:24px 30px}
.rel-topo{display:flex;align-items:flex-start;justify-content:space-between;border-bottom:2px solid #1A2D4F;padding-bottom:12px;margin-bottom:18px}
.rel-topo h1{font-size:17px;font-weight:800;color:#1A2D4F}
.rel-topo p{font-size:11px;color:#6B7686;margin-top:3px}
.logotipo{font-size:14px;font-weight:900;letter-spacing:3px;color:#1A2D4F;text-align:right}
.sub-logo{font-size:9px;letter-spacing:1.5px;color:#6B7686;font-weight:600}
.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:18px}
.kp{border:1px solid #E4E8EF;border-radius:10px;padding:11px 13px;text-align:center}
.kp b{font-size:18px;font-weight:800;color:#1A2D4F;display:block}
.kp span{font-size:10px;color:#6B7686;font-weight:600;text-transform:uppercase;letter-spacing:.8px}
.avanco-bloco{display:flex;align-items:center;gap:30px;border:1px solid #E4E8EF;border-radius:12px;padding:18px 22px;margin-bottom:18px}
.donut-svg{flex:0 0 130px}
.leg div{font-size:12.5px;color:#6B7686;margin-bottom:10px;display:flex;align-items:center;gap:8px}
.leg b{color:#1E2738}
.dot{width:12px;height:12px;border-radius:3px;flex:0 0 auto}
.secao{font-size:10.5px;letter-spacing:1.2px;text-transform:uppercase;font-weight:800;color:#6B7686;margin:14px 0 8px;border-top:1px solid #E4E8EF;padding-top:10px}
table{width:100%;border-collapse:collapse;margin-bottom:16px}
thead th{background:#1A2D4F;color:#fff;font-size:10px;letter-spacing:.8px;text-transform:uppercase;padding:7px 9px;text-align:left}
tbody tr:nth-child(even){background:#F4F6FA}
tbody td{padding:7px 9px;border-bottom:1px solid #E4E8EF;font-size:12px}
td.n{text-align:right;font-weight:700}
tfoot td{padding:7px 9px;font-weight:800;font-size:12.5px;background:#1A2D4F;color:#fff}
tfoot td.n{text-align:right}
.barra-wrap{height:10px;border-radius:99px;background:#E4E8EF;overflow:hidden}
.barra-fill{height:100%;border-radius:99px;background:#1F7A6E}
.rodape{margin-top:24px;border-top:1px solid #E4E8EF;padding-top:10px;display:flex;justify-content:space-between;font-size:10px;color:#6B7686}
.btn-print{display:inline-flex;align-items:center;gap:6px;background:#1A2D4F;color:#fff;border:0;border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;cursor:pointer;margin-bottom:14px}
.btn-voltar{display:inline-flex;align-items:center;gap:6px;background:#fff;color:#1A2D4F;border:1px solid #1A2D4F;border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;text-decoration:none;margin-bottom:14px;margin-left:6px}
@media print{.no-print{display:none!important}body{padding:8px 12px}}
</style>
</head>
<body>

<div class="no-print">
  <button class="btn-print" onclick="window.print()">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    Imprimir
  </button>
  <a class="btn-voltar" href="/cheron_camargo/master/?modo=periodo&inicio=<?= htmlspecialchars($inicio) ?>&fim=<?= htmlspecialchars($fim) ?>">← Voltar</a>
</div>

<div class="rel-topo">
  <div>
    <h1>Avanço Físico da Obra</h1>
    <p>Referência: <?= htmlspecialchars($periodoFmt) ?> &nbsp;·&nbsp; Gerado em <?= date('d/m/Y H:i') ?></p>
  </div>
  <div>
    <div class="logotipo"><?= htmlspecialchars($empresa) ?></div>
    <div class="sub-logo" style="text-align:right">IMPLANTAÇÃO DE REDE</div>
  </div>
</div>

<div class="kpis">
  <div class="kp"><b><?= number_format($previsto, 1, ',', '.') ?> m</b><span>Total previsto</span></div>
  <div class="kp"><b><?= number_format($executadoTotal, 1, ',', '.') ?> m</b><span>Executado geral</span></div>
  <div class="kp"><b><?= $pctAvanco ?>%</b><span>% de avanço</span></div>
  <div class="kp"><b><?= $projecao ?? '—' ?></b><span>Projeção conclusão</span></div>
</div>

<div class="avanco-bloco">
  <svg class="donut-svg" viewBox="0 0 130 130">
    <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none" stroke="#E4E8EF" stroke-width="14"/>
    <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none" stroke="#1F7A6E" stroke-width="14"
            stroke-dasharray="<?= round($dashExec,2) ?> <?= round($dashResto,2) ?>"
            stroke-dashoffset="<?= round($circ/4,2) ?>" stroke-linecap="round"/>
    <text x="<?= $cx ?>" y="<?= $cy - 6 ?>" text-anchor="middle" font-size="18" font-weight="800" fill="#1A2D4F"><?= $pctAvanco ?>%</text>
    <text x="<?= $cx ?>" y="<?= $cy + 12 ?>" text-anchor="middle" font-size="10" fill="#6B7686">avanço</text>
  </svg>
  <div class="leg">
    <div><div class="dot" style="background:#1F7A6E"></div><span>Executado: <b><?= number_format($executadoTotal,1,',','.') ?> m</b></span></div>
    <div><div class="dot" style="background:#E4E8EF"></div><span>Pendente: <b><?= number_format($resto,1,',','.') ?> m</b></span></div>
    <div style="margin-top:10px;font-size:12.5px;color:#6B7686">
      Média no período: <b style="color:#1E2738"><?= number_format($mediaDiaria,1,',','.') ?> m/dia</b><br>
      Dias trabalhados: <b style="color:#1E2738"><?= $diasTrabalhados ?></b>
    </div>
  </div>
</div>

<?php if (!empty($porBaciaEquipe)): ?>
<p class="secao">Produção por Bacia</p>
<table>
  <thead>
    <tr><th>Bacia</th><th>Equipe</th><th style="text-align:right">Metros (período)</th><th style="width:160px">Barra</th></tr>
  </thead>
  <tbody>
  <?php
  $maxM = max(array_column($porBaciaEquipe, 'metros') ?: [1]);
  foreach ($porBaciaEquipe as $r):
      $pct2 = $maxM > 0 ? min(100, round($r['metros']/$maxM*100)) : 0;
  ?>
    <tr>
      <td><?= htmlspecialchars($r['bacia'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['equipe']) ?></td>
      <td class="n"><?= number_format($r['metros'],1,',','.') ?></td>
      <td><div class="barra-wrap"><div class="barra-fill" style="width:<?= $pct2 ?>%"></div></div></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr><td colspan="2">TOTAL NO PERÍODO</td><td class="n"><?= number_format($metrosTotal,1,',','.') ?> m</td><td></td></tr>
  </tfoot>
</table>
<?php endif; ?>

<div class="rodape">
  <span><?= htmlspecialchars($empresa) ?> — Avanço Físico — <?= htmlspecialchars($periodoFmt) ?></span>
  <span>Gerado em <?= date('d/m/Y H:i') ?></span>
</div>
</body>
</html>
