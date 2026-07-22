<?php
$empresa    = defined('APP_CLIENT') ? APP_CLIENT : 'GRAVITAS';
$periodoFmt = date('d/m/Y', strtotime($inicio)) . ' a ' . date('d/m/Y', strtotime($fim));
$r = 44; $cx = 50; $cy = 50; $circ = 2 * M_PI * $r;
$dashExec = $pctAvanco / 100 * $circ;
$dashResto = $circ - $dashExec;
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Resumo Gerencial — <?= htmlspecialchars($periodoFmt) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Inter",-apple-system,Helvetica,Arial,sans-serif;font-size:12px;color:#1E2738;background:#fff;padding:18px 24px}
.rel-topo{display:flex;align-items:flex-start;justify-content:space-between;border-bottom:3px solid #1A2D4F;padding-bottom:10px;margin-bottom:16px}
.rel-topo h1{font-size:16px;font-weight:900;color:#1A2D4F;letter-spacing:-.3px}
.rel-topo p{font-size:10px;color:#6B7686;margin-top:2px}
.logotipo{font-size:13px;font-weight:900;letter-spacing:3px;color:#1A2D4F;text-align:right}
.sub-logo{font-size:8.5px;letter-spacing:1.5px;color:#6B7686;font-weight:600}
.grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
.kp{border:1.5px solid #E4E8EF;border-radius:10px;padding:10px 12px;text-align:center}
.kp b{font-size:17px;font-weight:800;color:#1A2D4F;display:block;line-height:1.3}
.kp span{font-size:9.5px;color:#6B7686;font-weight:700;text-transform:uppercase;letter-spacing:.8px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px}
.bloco{border:1px solid #E4E8EF;border-radius:10px;padding:12px 14px}
.bloco h3{font-size:10px;color:#6B7686;font-weight:700;letter-spacing:.8px;text-transform:uppercase;margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid #E4E8EF}
.info-row{display:flex;justify-content:space-between;padding:4px 0;font-size:11.5px;border-bottom:1px solid #F4F6FA}
.info-row:last-child{border-bottom:0}
.info-row b{font-weight:700;color:#1A2D4F}
.avanco-wrap{display:flex;align-items:center;gap:14px}
.avanco-wrap svg{flex:0 0 100px}
.avanco-leg{font-size:11px;color:#6B7686}
.avanco-leg .av-row{margin-bottom:6px;display:flex;align-items:center;gap:6px}
.dot{width:10px;height:10px;border-radius:3px}
table{width:100%;border-collapse:collapse;font-size:11px;margin-top:6px}
thead th{background:#F4F6FA;color:#6B7686;font-size:9.5px;letter-spacing:.8px;text-transform:uppercase;padding:5px 8px;text-align:left;border-bottom:1px solid #E4E8EF}
tbody td{padding:5px 8px;border-bottom:1px solid #F4F6FA}
td.n{text-align:right;font-weight:700}
.rodape{margin-top:18px;border-top:1px solid #E4E8EF;padding-top:8px;display:flex;justify-content:space-between;font-size:9.5px;color:#6B7686}
.btn-print{display:inline-flex;align-items:center;gap:6px;background:#1A2D4F;color:#fff;border:0;border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;cursor:pointer;margin-bottom:14px}
.btn-voltar{display:inline-flex;align-items:center;gap:6px;background:#fff;color:#1A2D4F;border:1px solid #1A2D4F;border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;text-decoration:none;margin-bottom:14px;margin-left:6px}
@media print{
  .no-print{display:none!important}
  body{padding:8px 12px}
  @page{size:A4;margin:15mm}
}
</style>
</head>
<body>

<div class="no-print">
  <button class="btn-print" onclick="window.print()">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    Imprimir / PDF
  </button>
  <a class="btn-voltar" href="/cheron_camargo/master/?modo=periodo&inicio=<?= htmlspecialchars($inicio) ?>&fim=<?= htmlspecialchars($fim) ?>">← Voltar</a>
</div>

<div class="rel-topo">
  <div>
    <h1>Resumo Gerencial — Implantação de Rede de Esgoto</h1>
    <p>Período de referência: <?= htmlspecialchars($periodoFmt) ?> &nbsp;·&nbsp; Gerado em <?= date('d/m/Y H:i') ?></p>
  </div>
  <div>
    <div class="logotipo"><?= htmlspecialchars($empresa) ?></div>
    <div class="sub-logo" style="text-align:right">IMPLANTAÇÃO DE REDE</div>
  </div>
</div>

<div class="grid3">
  <div class="kp"><b><?= number_format($metrosTotal, 1, ',', '.') ?> m</b><span>Executado no período</span></div>
  <div class="kp"><b><?= number_format($mediaDiaria, 1, ',', '.') ?> m</b><span>Média diária</span></div>
  <div class="kp"><b><?= $pctAvanco ?>%</b><span>Avanço geral obra</span></div>
</div>

<div class="grid2">
  <div class="bloco">
    <h3>Avanço físico global</h3>
    <div class="avanco-wrap">
      <svg viewBox="0 0 100 100">
        <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none" stroke="#E4E8EF" stroke-width="12"/>
        <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none" stroke="#1F7A6E" stroke-width="12"
                stroke-dasharray="<?= round($dashExec,2) ?> <?= round($dashResto,2) ?>"
                stroke-dashoffset="<?= round($circ/4,2) ?>" stroke-linecap="round"/>
        <text x="<?= $cx ?>" y="<?= $cy-4 ?>" text-anchor="middle" font-size="15" font-weight="800" fill="#1A2D4F"><?= $pctAvanco ?>%</text>
        <text x="<?= $cx ?>" y="<?= $cy+11 ?>" text-anchor="middle" font-size="8" fill="#6B7686">avanço</text>
      </svg>
      <div class="avanco-leg">
        <div class="av-row"><div class="dot" style="background:#1F7A6E"></div><span>Executado: <b style="color:#1E2738"><?= number_format($executadoTotal,0,',','.') ?> m</b></span></div>
        <div class="av-row"><div class="dot" style="background:#E4E8EF"></div><span>Pendente: <b style="color:#1E2738"><?= number_format(max(0,$previsto-$executadoTotal),0,',','.') ?> m</b></span></div>
        <?php if ($projecao): ?><div style="margin-top:8px;font-size:10.5px">Projeção: <b style="color:#1A2D4F"><?= htmlspecialchars($projecao) ?></b></div><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="bloco">
    <h3>Indicadores do período</h3>
    <div class="info-row"><span>Dias com produção</span><b><?= $diasTrabalhados ?></b></div>
    <div class="info-row"><span>Ramais domiciliares</span><b><?= (int)($ramaisTotal['qtd'] ?? 0) ?></b></div>
    <div class="info-row"><span>Interferências</span><b><?= $totalInterfs ?></b></div>
    <div class="info-row"><span>Total previsto (obra)</span><b><?= number_format($previsto,1,',','.') ?> m</b></div>
    <div class="info-row"><span>Executado total (obra)</span><b><?= number_format($executadoTotal,1,',','.') ?> m</b></div>
  </div>
</div>

<?php if (!empty($produtividade)): ?>
<div class="bloco" style="margin-bottom:14px">
  <h3>Produtividade por equipe</h3>
  <table>
    <thead><tr><th>Equipe</th><th style="text-align:right">Dias</th><th style="text-align:right">Total (m)</th><th style="text-align:right">M/dia</th></tr></thead>
    <tbody>
    <?php foreach ($produtividade as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['equipe']) ?></td>
        <td class="n"><?= (int)$p['dias'] ?></td>
        <td class="n"><?= number_format($p['metros'],1,',','.') ?></td>
        <td class="n"><?= number_format($p['m_por_dia'],1,',','.') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php if (!empty($interfsTotal)): ?>
<div class="bloco">
  <h3>Interferências no período</h3>
  <table>
    <thead><tr><th>Tipo</th><th style="text-align:right">Qtd</th></tr></thead>
    <tbody>
    <?php foreach ($interfsTotal as $i): ?>
      <tr><td><?= htmlspecialchars(str_replace('_',' ',ucfirst($i['tipo']))) ?></td><td class="n"><?= (int)$i['qtd'] ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<div class="rodape">
  <span><?= htmlspecialchars($empresa) ?> — Resumo Gerencial — <?= htmlspecialchars($periodoFmt) ?></span>
  <span>Gerado em <?= date('d/m/Y H:i') ?> — Página 1/1</span>
</div>
</body>
</html>
