<?php
$empresa      = defined('APP_CLIENT') ? APP_CLIENT : 'GRAVITAS';
$periodoFmt   = date('d/m/Y', strtotime($inicio)) . ' a ' . date('d/m/Y', strtotime($fim));
$meta_m_dia   = $meta_m_dia ?? 40.0;
$mHomemDia    = $mHomemDia ?? null;
$maxMPorDia   = count($produtividade) > 0 ? max(array_column($produtividade, 'm_por_dia')) : 1;
$refPct       = max($meta_m_dia, (float)$maxMPorDia, 0.1);
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Produtividade — <?= htmlspecialchars($periodoFmt) ?></title>
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
.bar{height:10px;background:#E4E8EF;border-radius:99px;overflow:hidden;display:inline-block;vertical-align:middle}
.bar i{display:block;height:100%;border-radius:99px}
.ok{color:#1F7A6E;font-weight:700}.dn{color:#B23A2C;font-weight:700}.warn{color:#A96C2A;font-weight:700}
.rank{display:inline-flex;width:22px;height:22px;border-radius:99px;font-size:10px;font-weight:800;align-items:center;justify-content:center;margin-right:4px}
.r1{background:#E0A53D22;color:#C68C28}.r2{background:#6B768622;color:#6B7686}.r3{background:#C0392B12;color:#B23A2C}
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
      <div><b><?= htmlspecialchars($empresa) ?></b><small>Produtividade · desempenho</small></div>
    </div>
    <div class="num">Período<br><small style="color:#9FB4D6"><?= htmlspecialchars($periodoFmt) ?></small></div>
  </div>
  <div class="wrap">
    <div class="kpis">
      <div class="kpi"><small>Média geral/dia</small><b><?= number_format($mediaDiaria, 1, ',', '.') ?> m</b></div>
      <div class="kpi"><small>Meta m/dia</small><b><?= number_format($meta_m_dia, 1, ',', '.') ?> m</b></div>
      <div class="kpi"><small>m / homem-dia</small><b><?= $mHomemDia !== null ? number_format($mHomemDia, 1, ',', '.') : '—' ?></b></div>
      <div class="kpi"><small>Equipes ativas</small><b><?= count($produtividade) ?></b></div>
    </div>

    <p class="sec">Ranking por equipe</p>
    <?php if (empty($produtividade)): ?>
      <p style="color:#6B7686;font-style:italic;padding:12px 0">Nenhum dado no período.</p>
    <?php else: ?>
    <table>
      <tr>
        <th style="width:36px">#</th>
        <th>Equipe</th>
        <th class="r">Dias</th>
        <th class="r">m/dia</th>
        <th class="r">Meta</th>
        <th class="r">m/homem</th>
        <th style="width:200px">Desempenho</th>
      </tr>
      <?php foreach ($produtividade as $idx => $p):
          $mDia  = (float)$p['m_por_dia'];
          $pct   = $meta_m_dia > 0 ? round($mDia / $meta_m_dia * 100) : 0;
          $barW  = min(100, round($mDia / $refPct * 100));
          $rankClass = $idx === 0 ? 'r1' : ($idx === 1 ? 'r2' : ($idx === 2 ? 'r3' : ''));
          $color = $pct >= 90 ? '#1D9E75' : ($pct >= 60 ? '#E0A53D' : '#B23A2C');
          $cls   = $pct >= 90 ? 'ok' : ($pct >= 60 ? 'warn' : 'dn');
      ?>
      <tr>
        <td><span class="rank <?= $rankClass ?>"><?= $idx+1 ?></span></td>
        <td><?= htmlspecialchars($p['equipe']) ?></td>
        <td class="r"><?= (int)$p['dias'] ?></td>
        <td class="r"><b><?= number_format($mDia, 1, ',', '.') ?></b></td>
        <td class="r gps"><?= number_format($meta_m_dia, 1, ',', '.') ?></td>
        <td class="r"><?= $p['m_homem'] ? number_format($p['m_homem'], 1, ',', '.') : '—' ?></td>
        <td>
          <div style="display:flex;align-items:center;gap:8px">
            <div class="bar" style="flex:1"><i style="width:<?= $barW ?>%;background:<?= $color ?>"></i></div>
            <span class="<?= $cls ?>" style="min-width:40px;text-align:right"><?= $pct ?>%</span>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <tr class="tot">
        <td colspan="3">MÉDIA GERAL</td>
        <td class="r"><?= number_format($mediaDiaria, 1, ',', '.') ?></td>
        <td class="r"><?= number_format($meta_m_dia, 1, ',', '.') ?></td>
        <td class="r"><?= $mHomemDia !== null ? number_format($mHomemDia, 1, ',', '.') : '—' ?></td>
        <td></td>
      </tr>
    </table>
    <?php endif; ?>
  </div>
  <div class="rodape">
    <span><?= htmlspecialchars($empresa) ?> — Relatório de Produtividade</span>
    <span>Gerado em <?= date('d/m/Y H:i') ?></span>
  </div>
</div>
</body>
</html>
