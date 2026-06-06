<?php
$empresa  = defined('APP_CLIENT') ? APP_CLIENT : 'GRAVITAS';
$dataFmt  = date('d/m/Y', strtotime($data));
$diaSem   = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'][date('w', strtotime($data))];
$totalPres = $presentes + $ausentes;
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>RDO Executivo — <?= htmlspecialchars($dataFmt) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Inter",-apple-system,Helvetica,Arial,sans-serif;font-size:12px;color:#1E2738;background:#fff;padding:24px 30px}
.rel-topo{display:flex;align-items:flex-start;justify-content:space-between;border-bottom:2px solid #1A2D4F;padding-bottom:12px;margin-bottom:18px}
.rel-topo h1{font-size:17px;font-weight:800;color:#1A2D4F}
.rel-topo p{font-size:11px;color:#6B7686;margin-top:3px}
.logotipo{font-size:14px;font-weight:900;letter-spacing:3px;color:#1A2D4F;text-align:right}
.sub-logo{font-size:9px;letter-spacing:1.5px;color:#6B7686;font-weight:600}
.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px}
.kp{border:1px solid #E4E8EF;border-radius:10px;padding:11px 13px;text-align:center}
.kp b{font-size:19px;font-weight:800;color:#1A2D4F;display:block}
.kp span{font-size:10px;color:#6B7686;font-weight:600;text-transform:uppercase;letter-spacing:.8px}
.secao{font-size:10.5px;letter-spacing:1.2px;text-transform:uppercase;font-weight:800;color:#6B7686;margin:14px 0 7px;border-top:1px solid #E4E8EF;padding-top:10px}
table{width:100%;border-collapse:collapse;margin-bottom:16px}
thead th{background:#1A2D4F;color:#fff;font-size:10px;letter-spacing:.8px;text-transform:uppercase;padding:7px 9px;text-align:left}
tbody tr:nth-child(even){background:#F4F6FA}
tbody td{padding:7px 9px;border-bottom:1px solid #E4E8EF;font-size:12px}
td.n{text-align:right;font-weight:700}
tfoot td{padding:7px 9px;font-weight:800;font-size:12.5px;background:#1A2D4F;color:#fff}
tfoot td.n{text-align:right}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px}
.info-bloco{border:1px solid #E4E8EF;border-radius:10px;padding:12px 14px}
.info-bloco h3{font-size:11px;color:#6B7686;font-weight:700;letter-spacing:.8px;text-transform:uppercase;margin-bottom:8px}
.info-row{display:flex;justify-content:space-between;border-bottom:1px solid #F4F6FA;padding:5px 0;font-size:12px}
.info-row:last-child{border-bottom:0}
.info-row b{font-weight:700}
.rodape{margin-top:20px;border-top:1px solid #E4E8EF;padding-top:10px;display:flex;justify-content:space-between;font-size:10px;color:#6B7686}
.btn-print{display:inline-flex;align-items:center;gap:6px;background:#1A2D4F;color:#fff;border:0;
           border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;cursor:pointer;margin-bottom:14px}
.btn-voltar{display:inline-flex;align-items:center;gap:6px;background:#fff;color:#1A2D4F;border:1px solid #1A2D4F;
           border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;text-decoration:none;margin-bottom:14px;margin-left:6px}
@media print{
  .no-print{display:none!important}
  body{padding:8px 12px}
}
</style>
</head>
<body>

<div class="no-print">
  <button class="btn-print" onclick="window.print()">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    Imprimir
  </button>
  <a class="btn-voltar" href="/principal/master/?modo=dia&data=<?= htmlspecialchars($data) ?>">← Voltar</a>
</div>

<div class="rel-topo">
  <div>
    <h1>Relatório Diário de Obra — RDO</h1>
    <p><?= $diaSem ?>, <?= htmlspecialchars($dataFmt) ?> &nbsp;·&nbsp; Gerado em <?= date('d/m/Y H:i') ?></p>
  </div>
  <div>
    <div class="logotipo"><?= htmlspecialchars($empresa) ?></div>
    <div class="sub-logo" style="text-align:right">IMPLANTAÇÃO DE REDE</div>
  </div>
</div>

<div class="kpis">
  <div class="kp"><b><?= number_format($metrosDia, 1, ',', '.') ?> m</b><span>Rede executada</span></div>
  <div class="kp"><b><?= $presentes ?> / <?= $totalPres ?></b><span>Presentes</span></div>
  <div class="kp"><b><?= (int)($ramais['qtd'] ?? 0) ?></b><span>Ramais</span></div>
  <div class="kp"><b><?= count($interfs) > 0 ? array_sum(array_column($interfs,'qtd')) : 0 ?></b><span>Interferências</span></div>
</div>

<?php if (!empty($producaoPorEquipe)): ?>
<p class="secao">Produção por Equipe</p>
<table>
  <thead>
    <tr><th>Equipe</th><th>Bacia</th><th>PV Montante</th><th>PV Jusante</th><th style="text-align:right">Metros</th></tr>
  </thead>
  <tbody>
  <?php $totalM = 0; foreach ($producaoPorEquipe as $r): $totalM += (float)$r['extensao_gps_m']; ?>
    <tr>
      <td><?= htmlspecialchars($r['equipe']) ?></td>
      <td><?= htmlspecialchars($r['bacia'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['pv_montante'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['pv_jusante'] ?: '—') ?></td>
      <td class="n"><?= number_format($r['extensao_gps_m'], 1, ',', '.') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot><tr><td colspan="4">TOTAL</td><td class="n"><?= number_format($totalM, 1, ',', '.') ?> m</td></tr></tfoot>
</table>
<?php endif; ?>

<div class="grid2">
  <div class="info-bloco">
    <h3>Equipe — Presença</h3>
    <div class="info-row"><span>Presentes</span><b><?= $presentes ?></b></div>
    <div class="info-row"><span>Ausentes / Irregulares</span><b><?= $ausentes ?></b></div>
    <div class="info-row"><span>Total equipe</span><b><?= $totalPres ?></b></div>
  </div>
  <div class="info-bloco">
    <h3>Serviços complementares</h3>
    <div class="info-row"><span>Ramais domiciliares</span><b><?= (int)($ramais['qtd'] ?? 0) ?></b></div>
    <div class="info-row"><span>Ext. pista (m)</span><b><?= number_format($ramais['m_pista'] ?? 0, 1, ',', '.') ?></b></div>
    <div class="info-row"><span>Ext. calçada (m)</span><b><?= number_format($ramais['m_calcada'] ?? 0, 1, ',', '.') ?></b></div>
    <div class="info-row"><span>Pontões</span><b><?= $pontoes ?></b></div>
  </div>
</div>

<?php if (!empty($cargas)): ?>
<div class="info-bloco" style="margin-bottom:14px">
  <h3>Cargas / Viagens</h3>
  <?php foreach ($cargas as $tipo => $qtd): ?>
  <div class="info-row">
    <span><?= htmlspecialchars(str_replace('_',' ', ucfirst($tipo))) ?></span>
    <b><?= (int)$qtd ?></b>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($interfs)): ?>
<p class="secao">Interferências</p>
<table>
  <thead><tr><th>Tipo</th><th style="text-align:right">Qtd</th></tr></thead>
  <tbody>
  <?php foreach ($interfs as $i): ?>
    <tr>
      <td><?= htmlspecialchars(str_replace('_',' ', ucfirst($i['tipo']))) ?></td>
      <td class="n"><?= (int)$i['qtd'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<div class="rodape">
  <span><?= htmlspecialchars($empresa) ?> — RDO — <?= htmlspecialchars($dataFmt) ?></span>
  <span>Gerado em <?= date('d/m/Y H:i') ?></span>
</div>
</body>
</html>
