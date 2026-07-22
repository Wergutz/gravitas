<?php
$empresa    = defined('APP_CLIENT') ? APP_CLIENT : 'Cheron & Camargo';
$periodoFmt = date('d/m/Y', strtotime($inicio)) . ' a ' . date('d/m/Y', strtotime($fim));
$maxMPorDia = count($produtividade) > 0 ? max(array_column($produtividade, 'm_por_dia')) : 1;
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Produtividade — <?= htmlspecialchars($periodoFmt) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Inter",-apple-system,Helvetica,Arial,sans-serif;font-size:12px;color:#1E2738;background:#fff;padding:24px 30px}
.rel-topo{display:flex;align-items:flex-start;justify-content:space-between;border-bottom:2px solid #1A2D4F;padding-bottom:12px;margin-bottom:18px}
.rel-topo h1{font-size:17px;font-weight:800;color:#1A2D4F}
.rel-topo p{font-size:11px;color:#6B7686;margin-top:3px}
.logotipo{font-size:14px;font-weight:900;letter-spacing:3px;color:#1A2D4F;text-align:right}
.sub-logo{font-size:9px;letter-spacing:1.5px;color:#6B7686;font-weight:600}
.kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px}
.kp{border:1px solid #E4E8EF;border-radius:10px;padding:11px 13px;text-align:center}
.kp b{font-size:18px;font-weight:800;color:#1A2D4F;display:block}
.kp span{font-size:10px;color:#6B7686;font-weight:600;text-transform:uppercase;letter-spacing:.8px}
table{width:100%;border-collapse:collapse;margin-bottom:16px}
thead th{background:#1A2D4F;color:#fff;font-size:10px;letter-spacing:.8px;text-transform:uppercase;padding:8px 10px;text-align:left}
tbody tr:nth-child(even){background:#F4F6FA}
tbody td{padding:8px 10px;border-bottom:1px solid #E4E8EF;font-size:12px}
td.n{text-align:right;font-weight:700}
tfoot td{padding:8px 10px;font-weight:800;font-size:12.5px;background:#1A2D4F;color:#fff}
tfoot td.n{text-align:right}
.barra-wrap{height:8px;border-radius:99px;background:#E4E8EF;overflow:hidden;display:inline-block;vertical-align:middle;margin-left:8px}
.barra-fill{height:100%;border-radius:99px;background:#27406A}
.rank{display:inline-flex;width:22px;height:22px;border-radius:99px;font-size:10px;font-weight:800;align-items:center;justify-content:center;margin-right:4px}
.r1{background:#E0A53D22;color:#C68C28} .r2{background:#6B768622;color:#6B7686} .r3{background:#C0392B12;color:#B23A2C}
.rodape{margin-top:24px;border-top:1px solid #E4E8EF;padding-top:10px;display:flex;justify-content:space-between;font-size:10px;color:#6B7686}
.btn-print{display:inline-flex;align-items:center;gap:6px;background:#1A2D4F;color:#fff;border:0;border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;cursor:pointer;margin-bottom:14px}
.btn-csv{display:inline-flex;align-items:center;gap:6px;background:#fff;color:#1A2D4F;border:1px solid #1A2D4F;border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;text-decoration:none;margin-bottom:14px;margin-left:6px}
@media print{.no-print{display:none!important}body{padding:8px 12px}}
</style>
</head>
<body>

<div class="no-print">
  <button class="btn-print" onclick="window.print()">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    Imprimir
  </button>
  <a class="btn-csv" href="?fmt=csv&inicio=<?= htmlspecialchars($inicio) ?>&fim=<?= htmlspecialchars($fim) ?>">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    Exportar CSV
  </a>
  <a class="btn-csv" href="/CHERONCAMARGO/master/">← Voltar</a>
</div>

<div class="rel-topo">
  <div>
    <h1>Relatório de Produtividade</h1>
    <p>Período: <?= htmlspecialchars($periodoFmt) ?> &nbsp;·&nbsp; Gerado em <?= date('d/m/Y H:i') ?></p>
  </div>
  <div>
    <div class="logotipo"><?= htmlspecialchars($empresa) ?></div>
    <div class="sub-logo" style="text-align:right">IMPLANTAÇÃO DE REDE</div>
  </div>
</div>

<div class="kpis">
  <div class="kp"><b><?= number_format($mediaDiaria, 1, ',', '.') ?> m</b><span>Média geral/dia</span></div>
  <div class="kp"><b><?= $diasTrabalhados ?></b><span>Dias com registro</span></div>
  <div class="kp"><b><?= count($produtividade) ?></b><span>Equipes ativas</span></div>
</div>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Equipe</th>
      <th style="text-align:right">Dias</th>
      <th style="text-align:right">Total (m)</th>
      <th style="text-align:right;width:120px">Média m/dia</th>
      <th style="width:160px">Desempenho</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($produtividade as $idx => $p):
      $pct = $maxMPorDia > 0 ? min(100, round((float)$p['m_por_dia'] / $maxMPorDia * 100)) : 0;
      $rankClass = $idx === 0 ? 'r1' : ($idx === 1 ? 'r2' : ($idx === 2 ? 'r3' : ''));
  ?>
    <tr>
      <td><span class="rank <?= $rankClass ?>"><?= $idx+1 ?></span></td>
      <td><?= htmlspecialchars($p['equipe']) ?></td>
      <td class="n"><?= (int)$p['dias'] ?></td>
      <td class="n"><?= number_format($p['metros'], 1, ',', '.') ?></td>
      <td class="n"><?= number_format($p['m_por_dia'], 1, ',', '.') ?></td>
      <td>
        <div class="barra-wrap" style="width:120px">
          <div class="barra-fill" style="width:<?= $pct ?>%"></div>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
  <?php if (!empty($produtividade)): ?>
  <tfoot>
    <tr>
      <td colspan="3">MÉDIA GERAL</td>
      <td class="n"><?= number_format($metrosTotal, 1, ',', '.') ?></td>
      <td class="n"><?= number_format($mediaDiaria, 1, ',', '.') ?></td>
      <td></td>
    </tr>
  </tfoot>
  <?php endif; ?>
</table>

<?php if (!empty($curvaProd)): ?>
<table style="margin-top:10px">
  <thead><tr><th>Data</th><th style="text-align:right">Metros executados</th></tr></thead>
  <tbody>
  <?php foreach ($curvaProd as $c): ?>
    <tr>
      <td><?= date('d/m/Y', strtotime($c['data'])) ?></td>
      <td class="n"><?= number_format($c['metros'], 1, ',', '.') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<div class="rodape">
  <span><?= htmlspecialchars($empresa) ?> — Produtividade — <?= htmlspecialchars($periodoFmt) ?></span>
  <span>Gerado em <?= date('d/m/Y H:i') ?></span>
</div>
</body>
</html>
