<?php
$empresa  = defined('APP_CLIENT') ? APP_CLIENT : 'GRAVITAS';
$periodoFmt = date('d/m/Y', strtotime($inicio)) . ' a ' . date('d/m/Y', strtotime($fim));
$total = 0; foreach ($porBaciaEquipe as $r) $total += (float)$r['metros'];
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Boletim de Medição — <?= htmlspecialchars($periodoFmt) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Inter",-apple-system,Helvetica,Arial,sans-serif;font-size:12px;color:#1E2738;background:#fff;padding:24px 30px}
.rel-topo{display:flex;align-items:flex-start;justify-content:space-between;border-bottom:2px solid #1A2D4F;padding-bottom:12px;margin-bottom:18px}
.rel-topo h1{font-size:17px;font-weight:800;color:#1A2D4F;letter-spacing:-.3px}
.rel-topo p{font-size:11px;color:#6B7686;margin-top:3px}
.rel-topo .logotipo{font-size:14px;font-weight:900;letter-spacing:3px;color:#1A2D4F}
.rel-topo .sub-logo{font-size:9px;letter-spacing:1.5px;color:#6B7686;font-weight:600}
.resumo-kpi{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}
.kp{border:1px solid #E4E8EF;border-radius:10px;padding:12px 14px;text-align:center}
.kp b{font-size:20px;font-weight:800;color:#1A2D4F;display:block;line-height:1.2}
.kp span{font-size:10px;color:#6B7686;font-weight:600;text-transform:uppercase;letter-spacing:.8px}
table{width:100%;border-collapse:collapse;margin-bottom:18px}
thead th{background:#1A2D4F;color:#fff;font-size:10px;letter-spacing:.8px;text-transform:uppercase;padding:8px 10px;text-align:left}
tbody tr:nth-child(even){background:#F4F6FA}
tbody td{padding:8px 10px;border-bottom:1px solid #E4E8EF;font-size:12px}
tbody td.n{text-align:right;font-weight:700;font-size:12.5px}
tfoot td{padding:8px 10px;font-weight:800;font-size:13px;background:#1A2D4F;color:#fff}
tfoot td.n{text-align:right}
.secao{font-size:11px;letter-spacing:1.2px;text-transform:uppercase;font-weight:800;color:#6B7686;margin:14px 0 8px;border-top:1px solid #E4E8EF;padding-top:10px}
.rodape{margin-top:24px;border-top:1px solid #E4E8EF;padding-top:10px;display:flex;justify-content:space-between;font-size:10px;color:#6B7686}
.btn-print{display:inline-flex;align-items:center;gap:6px;background:#1A2D4F;color:#fff;border:0;
           border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;cursor:pointer;margin-bottom:14px}
.btn-csv{display:inline-flex;align-items:center;gap:6px;background:#fff;color:#1A2D4F;border:1px solid #1A2D4F;
         border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;text-decoration:none;margin-bottom:14px;margin-left:6px}
.no-print{margin-bottom:14px}
@media print{
  .no-print{display:none!important}
  body{padding:8px 12px}
  .rel-topo{padding-bottom:8px;margin-bottom:12px}
}
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
  <a class="btn-csv" href="/principal/master/">← Voltar</a>
</div>

<div class="rel-topo">
  <div>
    <h1>Boletim de Medição</h1>
    <p>Período: <?= htmlspecialchars($periodoFmt) ?> &nbsp;·&nbsp; Gerado em <?= date('d/m/Y H:i') ?></p>
  </div>
  <div style="text-align:right">
    <div class="logotipo"><?= htmlspecialchars($empresa) ?></div>
    <div class="sub-logo">IMPLANTAÇÃO DE REDE</div>
  </div>
</div>

<div class="resumo-kpi">
  <div class="kp"><b><?= number_format($total, 1, ',', '.') ?> m</b><span>Rede executada</span></div>
  <div class="kp"><b><?= (int)($ramaisTotal['qtd'] ?? 0) ?></b><span>Ramais domiciliares</span></div>
  <div class="kp"><b><?= number_format(($ramaisTotal['m_pista'] ?? 0) + ($ramaisTotal['m_calcada'] ?? 0), 1, ',', '.') ?> m</b><span>Extensão repav.</span></div>
  <div class="kp"><b><?= number_format($mediaDiaria, 1, ',', '.') ?> m</b><span>Média diária</span></div>
</div>

<p class="secao">Produção por Bacia e Equipe</p>
<?php
$baciaAtual = null;
$baciaTot   = 0;
?>
<table>
  <thead>
    <tr>
      <th>Bacia</th>
      <th>Equipe</th>
      <th style="text-align:right">Rede executada (m)</th>
    </tr>
  </thead>
  <tbody>
<?php foreach ($porBaciaEquipe as $r):
    if ($baciaAtual !== null && $baciaAtual !== $r['bacia']): ?>
    <tr style="background:#EEF0F4">
      <td colspan="2" style="font-weight:800;font-size:11px;color:#1A2D4F">Subtotal <?= htmlspecialchars($baciaAtual) ?></td>
      <td class="n"><?= number_format($baciaTot, 1, ',', '.') ?></td>
    </tr>
<?php   $baciaTot = 0; endif;
    $baciaAtual = $r['bacia'];
    $baciaTot += (float)$r['metros'];
?>
    <tr>
      <td><?= htmlspecialchars($r['bacia'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['equipe']) ?></td>
      <td class="n"><?= number_format($r['metros'], 1, ',', '.') ?></td>
    </tr>
<?php endforeach;
if ($baciaAtual !== null): ?>
    <tr style="background:#EEF0F4">
      <td colspan="2" style="font-weight:800;font-size:11px;color:#1A2D4F">Subtotal <?= htmlspecialchars($baciaAtual) ?></td>
      <td class="n"><?= number_format($baciaTot, 1, ',', '.') ?></td>
    </tr>
<?php endif; ?>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="2">TOTAL GERAL</td>
      <td class="n"><?= number_format($total, 1, ',', '.') ?> m</td>
    </tr>
  </tfoot>
</table>

<?php if (!empty($interfsTotal)): ?>
<p class="secao">Interferências no Período</p>
<table>
  <thead><tr><th>Tipo</th><th style="text-align:right">Qtd</th></tr></thead>
  <tbody>
  <?php foreach ($interfsTotal as $i): ?>
    <tr>
      <td><?= htmlspecialchars(str_replace('_',' ', ucfirst($i['tipo']))) ?></td>
      <td class="n"><?= (int)$i['qtd'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot><tr><td>TOTAL</td><td class="n"><?= $totalInterfs ?></td></tr></tfoot>
</table>
<?php endif; ?>

<div class="rodape">
  <span><?= htmlspecialchars($empresa) ?> — Gestão de Implantação de Rede de Esgoto</span>
  <span>Página 1 — <?= date('d/m/Y H:i') ?></span>
</div>
</body>
</html>
