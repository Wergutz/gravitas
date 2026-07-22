<?php
$empresa    = defined('APP_CLIENT') ? APP_CLIENT : 'Cheron & Camargo';
$periodoFmt = date('d/m/Y', strtotime($inicio)) . ' a ' . date('d/m/Y', strtotime($fim));
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Interferências — <?= htmlspecialchars($periodoFmt) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Inter",-apple-system,Helvetica,Arial,sans-serif;font-size:12px;color:#1E2738;background:#fff;padding:24px 30px}
.rel-topo{display:flex;align-items:flex-start;justify-content:space-between;border-bottom:2px solid #1A2D4F;padding-bottom:12px;margin-bottom:18px}
.rel-topo h1{font-size:17px;font-weight:800;color:#1A2D4F}
.rel-topo p{font-size:11px;color:#6B7686;margin-top:3px}
.logotipo{font-size:14px;font-weight:900;letter-spacing:3px;color:#1A2D4F;text-align:right}
.sub-logo{font-size:9px;letter-spacing:1.5px;color:#6B7686;font-weight:600}
table{width:100%;border-collapse:collapse;margin-bottom:18px}
thead th{background:#1A2D4F;color:#fff;font-size:10px;letter-spacing:.8px;text-transform:uppercase;padding:8px 10px;text-align:left}
tbody tr:nth-child(even){background:#F4F6FA}
tbody td{padding:8px 10px;border-bottom:1px solid #E4E8EF;font-size:12px}
td.n{text-align:right;font-weight:700}
td.pct{text-align:right}
tfoot td{padding:8px 10px;font-weight:800;font-size:12.5px;background:#1A2D4F;color:#fff}
tfoot td.n{text-align:right}
.barra-wrap{display:flex;align-items:center;gap:8px}
.barra{height:8px;border-radius:99px;background:#1A2D4F;min-width:4px}
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
    <h1>Registro de Interferências</h1>
    <p>Período: <?= htmlspecialchars($periodoFmt) ?> &nbsp;·&nbsp; Gerado em <?= date('d/m/Y H:i') ?></p>
  </div>
  <div>
    <div class="logotipo"><?= htmlspecialchars($empresa) ?></div>
    <div class="sub-logo" style="text-align:right">IMPLANTAÇÃO DE REDE</div>
  </div>
</div>

<?php if (empty($interfsTotal)): ?>
  <p style="color:#6B7686;font-style:italic;text-align:center;padding:30px 0">Nenhuma interferência registrada no período.</p>
<?php else:
  $max = max(array_column($interfsTotal, 'qtd'));
?>
<table>
  <thead>
    <tr>
      <th>Tipo de interferência</th>
      <th style="width:40%;text-align:right">Distribuição</th>
      <th style="text-align:right">Qtd</th>
      <th style="text-align:right">%</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($interfsTotal as $i):
      $pct  = $totalInterfs > 0 ? round($i['qtd'] / $totalInterfs * 100, 1) : 0;
      $largura = $max > 0 ? round($i['qtd'] / $max * 180) : 0;
  ?>
    <tr>
      <td><?= htmlspecialchars(str_replace('_',' ', ucfirst($i['tipo']))) ?></td>
      <td class="pct">
        <div class="barra-wrap" style="justify-content:flex-end">
          <div class="barra" style="width:<?= $largura ?>px"></div>
        </div>
      </td>
      <td class="n"><?= (int)$i['qtd'] ?></td>
      <td style="text-align:right;color:#6B7686;font-size:11px"><?= $pct ?>%</td>
    </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr><td colspan="2">TOTAL</td><td class="n"><?= $totalInterfs ?></td><td style="text-align:right">100%</td></tr>
  </tfoot>
</table>
<?php endif; ?>

<div class="rodape">
  <span><?= htmlspecialchars($empresa) ?> — Interferências — <?= htmlspecialchars($periodoFmt) ?></span>
  <span>Gerado em <?= date('d/m/Y H:i') ?></span>
</div>
</body>
</html>
