<?php
$empresa    = defined('APP_CLIENT') ? APP_CLIENT : 'GRAVITAS';
$periodoFmt = date('d/m/Y', strtotime($inicio)) . ' a ' . date('d/m/Y', strtotime($fim));
$repavPeriodo = $repavPeriodo ?? null;
$pctBar = min(100, (float)$pctAvanco);
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Resumo Gerencial — <?= htmlspecialchars($periodoFmt) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Inter,-apple-system,Segoe UI,Arial,sans-serif;color:#1E2738;background:#eef1f5;padding:24px}
.no-print{margin-bottom:14px}
.btn-print{display:inline-flex;align-items:center;gap:6px;background:#1A2D4F;color:#fff;border:0;border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;cursor:pointer;margin-right:6px}
.btn-back{display:inline-flex;align-items:center;gap:6px;background:#fff;color:#1A2D4F;border:1px solid #1A2D4F;border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;text-decoration:none;margin-right:6px}
.doc{max-width:1000px;margin:0 auto;background:#fff;border:1px solid #E4E8EF;border-radius:14px;overflow:hidden}
.band{background:#1A2D4F;color:#fff;padding:14px 18px;display:flex;justify-content:space-between;align-items:center}
.band .l{display:flex;align-items:center;gap:12px}
.band b{letter-spacing:3px;font-weight:700;font-size:14px}
.band small{display:block;font-size:11px;color:#9FB4D6;letter-spacing:1px}
.band .num{color:#E0A53D;font-weight:700;font-size:12px;text-align:right}
.wrap{padding:16px 18px}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:10px}
.kpi{background:#F4F6FA;border-radius:8px;padding:12px}
.kpi small{font-size:12px;color:#6B7686}.kpi b{display:block;font-size:22px;font-weight:700;color:#1A2D4F}
.bar{height:14px;background:#E4E8EF;border-radius:99px;overflow:hidden;margin:8px 0}
.bar i{display:block;height:100%;background:#E0A53D;border-radius:99px}
.mini{display:flex;gap:20px;flex-wrap:wrap;font-size:12px;color:#6B7686;margin-bottom:14px}
.mini b{color:#1E2738}
.two{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:16px}
.sec{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#6B7686;font-weight:700;margin:16px 0 6px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{background:#F4F6FA;color:#6B7686;text-align:left;font-size:10px;padding:6px 8px;border-bottom:1px solid #E4E8EF;font-weight:700;letter-spacing:.8px;text-transform:uppercase}
th.r,td.r{text-align:right}
td{padding:7px 8px;border-bottom:1px solid #F4F6FA;font-size:12px}
td:first-child{color:#6B7686}td:last-child{font-weight:700;color:#1A2D4F}
.rodape{padding:10px 18px;border-top:1px solid #E4E8EF;display:flex;justify-content:space-between;font-size:10px;color:#6B7686;margin-top:4px}
@media print{
  .no-print{display:none!important}
  body{background:#fff;padding:8px}
  .doc{border:0;border-radius:0}
  @page{size:A4;margin:12mm}
}
</style>
</head>
<body>

<div class="no-print">
  <button class="btn-print" onclick="window.print()">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    Imprimir / PDF
  </button>
  <a class="btn-back" href="/principal/master/?modo=periodo&inicio=<?= htmlspecialchars($inicio) ?>&fim=<?= htmlspecialchars($fim) ?>">← Voltar</a>
</div>

<div class="doc">
  <div class="band">
    <div class="l">
      <svg viewBox="-4 -4 108 108" width="34" height="34"><ellipse cx="50" cy="50" rx="54" ry="19" fill="none" stroke="#B9C1CC" stroke-width="3.5" transform="rotate(-24 50 50)"/><circle cx="50" cy="50" r="44" fill="#1A2D4F" stroke="#B9C1CC" stroke-width="2.5"/><circle cx="92.8" cy="23.1" r="4.5" fill="#E0A53D"/><path d="M 26.7 73.3 A 33 33 0 1 1 73.3 73.3" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round"/><path d="M 50 50 L 67.4 29.3 L 55.5 53.5 Z" fill="#E0A53D"/><circle cx="50" cy="50" r="6.5" fill="#E0A53D"/><circle cx="50" cy="50" r="3" fill="#1A2D4F"/></svg>
      <div><b><?= htmlspecialchars($empresa) ?></b><small>Resumo gerencial · 1 página</small></div>
    </div>
    <div class="num">Medição nº 01<br><small style="color:#9FB4D6"><?= htmlspecialchars($periodoFmt) ?></small></div>
  </div>
  <div class="wrap">
    <div class="kpis">
      <div class="kpi"><small>Executado no período</small><b><?= number_format($metrosTotal, 1, ',', '.') ?> m</b></div>
      <div class="kpi"><small>Média diária</small><b><?= number_format($mediaDiaria, 1, ',', '.') ?> m</b></div>
      <div class="kpi"><small>Avanço geral</small><b><?= $pctAvanco ?>%</b></div>
      <?php if ($projecao): ?>
      <div class="kpi"><small>Projeção</small><b><?= htmlspecialchars($projecao) ?></b></div>
      <?php endif; ?>
    </div>

    <div class="bar"><i style="width:<?= $pctBar ?>%"></i></div>
    <div class="mini">
      <span>Executado total: <b><?= number_format($executadoTotal, 0, ',', '.') ?> m</b></span>
      <span>Pendente: <b><?= number_format(max(0, $previsto - $executadoTotal), 0, ',', '.') ?> m</b></span>
      <span>Previsto obra: <b><?= number_format($previsto, 0, ',', '.') ?> m</b></span>
    </div>

    <div class="two">
      <div>
        <p class="sec">Indicadores</p>
        <table>
          <tr><td>Dias com produção</td><td class="r"><?= $diasTrabalhados ?></td></tr>
          <tr><td>Ramais</td><td class="r"><?= (int)($ramaisTotal['qtd'] ?? 0) ?></td></tr>
          <tr><td>Interferências</td><td class="r"><?= $totalInterfs ?></td></tr>
          <tr><td>Equipes ativas</td><td class="r"><?= count($produtividade) ?></td></tr>
        </table>

        <?php if (!empty($repavPeriodo) && (float)$repavPeriodo['area_total'] > 0): ?>
        <p class="sec">Repavimentação</p>
        <table>
          <tr><td>Área reposta</td><td class="r"><?= number_format($repavPeriodo['area_total'], 1, ',', '.') ?> m²</td></tr>
          <?php if ((float)$repavPeriodo['volume_total'] > 0): ?>
          <tr><td>Volume asfalto</td><td class="r"><?= number_format($repavPeriodo['volume_total'], 3, ',', '.') ?> m³</td></tr>
          <?php endif; ?>
          <tr><td>Trechos medidos</td><td class="r"><?= (int)$repavPeriodo['trechos_medidos'] ?></td></tr>
        </table>
        <?php endif; ?>
      </div>
      <div>
        <?php if (!empty($produtividade)): ?>
        <p class="sec">Produtividade</p>
        <table>
          <tr><th>Equipe</th><th class="r">Dias</th><th class="r">Total</th><th class="r">m/dia</th></tr>
          <?php foreach ($produtividade as $p): ?>
          <tr>
            <td style="color:#1E2738"><?= htmlspecialchars($p['equipe']) ?></td>
            <td class="r" style="color:#1E2738"><?= (int)$p['dias'] ?></td>
            <td class="r" style="color:#1E2738"><?= number_format($p['metros'], 1, ',', '.') ?></td>
            <td class="r" style="color:#1E2738"><?= number_format($p['m_por_dia'], 1, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>

        <?php if (!empty($interfsTotal)): ?>
        <p class="sec">Interferências</p>
        <table>
          <?php foreach ($interfsTotal as $i): ?>
          <tr>
            <td><?= htmlspecialchars(str_replace('_', ' ', ucfirst($i['tipo']))) ?></td>
            <td class="r"><?= (int)$i['qtd'] ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="rodape">
    <span><?= htmlspecialchars($empresa) ?> — Resumo Gerencial — somente físico — Página 1/1</span>
    <span>Gerado em <?= date('d/m/Y H:i') ?></span>
  </div>
</div>
</body>
</html>
