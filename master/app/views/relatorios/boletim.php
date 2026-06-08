<?php
$empresa    = defined('APP_CLIENT') ? APP_CLIENT : 'GRAVITAS';
$periodoFmt = date('d/m/Y', strtotime($inicio)) . ' a ' . date('d/m/Y', strtotime($fim));
$repavPeriodo = $repavPeriodo ?? null;
$trechosBoletim = $trechosBoletim ?? [];
$metrosExec = array_sum(array_column($trechosBoletim, 'extensao_executada'));
$contrato   = !empty($trechosBoletim) ? ($trechosBoletim[0]['contrato'] ?? '—') : '—';
$baciaLabel = count(array_unique(array_column($trechosBoletim, 'bacia'))) === 1
    ? ($trechosBoletim[0]['bacia'] ?? '—')
    : implode(', ', array_unique(array_filter(array_column($trechosBoletim, 'bacia')))) ?: '—';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Boletim de Medição — <?= htmlspecialchars($periodoFmt) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Inter,-apple-system,Segoe UI,Arial,sans-serif;color:#1E2738;background:#eef1f5;padding:24px}
.no-print{margin-bottom:14px}
.btn-print{display:inline-flex;align-items:center;gap:6px;background:#1A2D4F;color:#fff;border:0;border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;cursor:pointer;margin-right:6px}
.btn-csv{display:inline-flex;align-items:center;gap:6px;background:#fff;color:#1A2D4F;border:1px solid #1A2D4F;border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;text-decoration:none;margin-right:6px}
.doc{max-width:1000px;margin:0 auto;background:#fff;border:1px solid #E4E8EF;border-radius:14px;overflow:hidden}
.band{background:#1A2D4F;color:#fff;padding:14px 18px;display:flex;justify-content:space-between;align-items:center}
.band .l{display:flex;align-items:center;gap:12px}
.band b{letter-spacing:3px;font-weight:700;font-size:14px}
.band small{display:block;font-size:11px;color:#9FB4D6;letter-spacing:1px}
.band .num{color:#E0A53D;font-weight:700;font-size:12px;text-align:right}
.meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));border-bottom:1px solid #E4E8EF}
.meta div{padding:10px 18px;border-right:1px solid #E4E8EF}
.meta div:last-child{border-right:0}
.meta small{font-size:11px;color:#6B7686;display:block}.meta b{font-size:13px}
.wrap{padding:16px 18px}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:16px}
.kpi{background:#F4F6FA;border-radius:8px;padding:12px}
.kpi small{font-size:12px;color:#6B7686}.kpi b{display:block;font-size:22px;font-weight:700;color:#1A2D4F}
.kpi .ref{font-size:11px;color:#9AA3B2}
.sec{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#6B7686;font-weight:700;margin:16px 0 6px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{background:#1A2D4F;color:#fff;text-align:left;font-size:11px;padding:8px 9px}
th.r,td.r{text-align:right}
td{padding:8px 9px;border-bottom:1px solid #EEF1F5}
tr.tot td{background:#1A2D4F;color:#fff;font-weight:700}
.gps{color:#6B7686;font-size:11px}
.two{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.rodape{padding:10px 18px;border-top:1px solid #E4E8EF;display:flex;justify-content:space-between;font-size:10px;color:#6B7686;margin-top:4px}
@media print{
  .no-print{display:none!important}
  body{background:#fff;padding:8px}
  .doc{border:0;border-radius:0}
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
  <a class="btn-csv" href="/principal/master/?modo=periodo&inicio=<?= htmlspecialchars($inicio) ?>&fim=<?= htmlspecialchars($fim) ?>">← Voltar</a>
</div>

<div class="doc">
  <div class="band">
    <div class="l">
      <svg viewBox="-4 -4 108 108" width="34" height="34"><ellipse cx="50" cy="50" rx="54" ry="19" fill="none" stroke="#B9C1CC" stroke-width="3.5" transform="rotate(-24 50 50)"/><circle cx="50" cy="50" r="44" fill="#1A2D4F" stroke="#B9C1CC" stroke-width="2.5"/><circle cx="92.8" cy="23.1" r="4.5" fill="#E0A53D"/><path d="M 26.7 73.3 A 33 33 0 1 1 73.3 73.3" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round"/><path d="M 50 50 L 67.4 29.3 L 55.5 53.5 Z" fill="#E0A53D"/><circle cx="50" cy="50" r="6.5" fill="#E0A53D"/><circle cx="50" cy="50" r="3" fill="#1A2D4F"/></svg>
      <div><b><?= htmlspecialchars($empresa) ?></b><small>Boletim de medição · físico</small></div>
    </div>
    <div class="num">BMED-<?= date('Y') ?>-0001<br><small style="color:#9FB4D6">Medição nº 01</small></div>
  </div>
  <div class="meta">
    <div><small>Contrato</small><b><?= htmlspecialchars($contrato) ?></b></div>
    <div><small>Competência</small><b><?= htmlspecialchars($periodoFmt) ?></b></div>
    <div><small>Bacia/equipe</small><b><?= htmlspecialchars($baciaLabel) ?></b></div>
  </div>
  <div class="wrap">
    <div class="kpis">
      <div class="kpi">
        <small>Rede executada</small>
        <b><?= number_format($metrosExec, 1, ',', '.') ?> m</b>
        <div class="ref">planejado <?= number_format(array_sum(array_column($trechosBoletim, 'extensao_planejada')), 1, ',', '.') ?></div>
      </div>
      <div class="kpi">
        <small>Ramais</small>
        <b><?= (int)($ramaisTotal['qtd'] ?? 0) ?></b>
        <?php if ((float)($ramaisTotal['m_pista'] ?? 0) > 0): ?>
        <div class="ref"><?= number_format($ramaisTotal['m_pista'], 1, ',', '.') ?>m pista</div>
        <?php endif; ?>
      </div>
      <div class="kpi">
        <small>Repavimentação</small>
        <b><?= number_format((float)($repavPeriodo['area_total'] ?? 0), 1, ',', '.') ?> m²</b>
        <?php if ((float)($repavPeriodo['volume_total'] ?? 0) > 0): ?>
        <div class="ref">asfalto <?= number_format($repavPeriodo['volume_total'], 2, ',', '.') ?> m³</div>
        <?php endif; ?>
      </div>
      <div class="kpi">
        <small>Média diária</small>
        <b><?= number_format($mediaDiaria, 1, ',', '.') ?> m</b>
        <div class="ref"><?= $diasTrabalhados ?> dia<?= $diasTrabalhados !== 1 ? 's' : '' ?></div>
      </div>
    </div>

    <p class="sec">Rede executada por trecho</p>
    <?php if (empty($trechosBoletim)): ?>
      <p style="color:#6B7686;font-style:italic;padding:12px 0">Nenhum trecho executado no período.</p>
    <?php else: ?>
    <table>
      <tr>
        <th>PV mont.</th><th>PV jus.</th><th>DN</th><th>Prof.</th>
        <th class="r">Planejado</th><th class="r">Executado</th><th class="r">GPS (evid.)</th>
      </tr>
      <?php $totPlan = 0; $totExec = 0; $totGps = 0;
            foreach ($trechosBoletim as $t):
                $totPlan += (float)$t['extensao_planejada'];
                $totExec += (float)$t['extensao_executada'];
                $totGps  += (float)$t['extensao_gps'];
      ?>
      <tr>
        <td><?= htmlspecialchars($t['pv_montante'] ?? '—') ?></td>
        <td><?= htmlspecialchars($t['pv_jusante'] ?? '—') ?></td>
        <td><?= htmlspecialchars($t['dn'] ?? '—') ?></td>
        <td><?= $t['profundidade_media'] ? number_format($t['profundidade_media'], 2, ',', '.') . ' m' : '—' ?></td>
        <td class="r"><?= number_format($t['extensao_planejada'], 1, ',', '.') ?></td>
        <td class="r"><b><?= number_format($t['extensao_executada'], 1, ',', '.') ?></b></td>
        <td class="r gps"><?= number_format($t['extensao_gps'], 1, ',', '.') ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="tot">
        <td colspan="4">Total geral</td>
        <td class="r"><?= number_format($totPlan, 1, ',', '.') ?></td>
        <td class="r"><?= number_format($totExec, 1, ',', '.') ?> m</td>
        <td class="r"><?= number_format($totGps, 1, ',', '.') ?></td>
      </tr>
    </table>
    <?php endif; ?>

    <div class="two" style="margin-top:16px">
      <div>
        <p class="sec">Repavimentação</p>
        <?php if (!empty($repavPeriodo) && (float)$repavPeriodo['area_total'] > 0): ?>
        <table>
          <tr><td>Área reposta</td><td class="r"><?= number_format($repavPeriodo['area_total'], 1, ',', '.') ?> m²</td></tr>
          <?php if ((float)$repavPeriodo['volume_total'] > 0): ?>
          <tr><td>Volume de asfalto</td><td class="r"><?= number_format($repavPeriodo['volume_total'], 3, ',', '.') ?> m³</td></tr>
          <?php endif; ?>
          <tr><td>Trechos medidos</td><td class="r"><?= (int)$repavPeriodo['trechos_medidos'] ?></td></tr>
        </table>
        <?php else: ?>
          <p style="color:#6B7686;font-size:12px;padding:4px 0">Sem registro no período.</p>
        <?php endif; ?>
      </div>
      <div>
        <p class="sec">Interferências</p>
        <?php if (!empty($interfsTotal)): ?>
        <table>
          <?php foreach ($interfsTotal as $i): ?>
          <tr>
            <td><?= htmlspecialchars(str_replace('_', ' ', ucfirst($i['tipo']))) ?></td>
            <td class="r"><?= (int)$i['qtd'] ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="tot"><td>Total</td><td class="r"><?= $totalInterfs ?></td></tr>
        </table>
        <?php else: ?>
          <p style="color:#6B7686;font-size:12px;padding:4px 0">Sem interferências no período.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="rodape">
    <span><?= htmlspecialchars($empresa) ?> — Boletim de Medição — somente físico</span>
    <span>Gerado em <?= date('d/m/Y H:i') ?></span>
  </div>
</div>
</body>
</html>
