<?php
$empresa  = defined('APP_CLIENT') ? APP_CLIENT : 'GRAVITAS';
$dataFmt  = date('d/m/Y', strtotime($data));
$diaSem   = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'][date('w', strtotime($data))];
$totalPres = $presentes + $ausentes;
$equipamentosDia     = $equipamentosDia ?? [];
$materiaisAplicadosDia = $materiaisAplicadosDia ?? [];
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>RDO Executivo — <?= htmlspecialchars($dataFmt) ?></title>
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
.meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));border-bottom:1px solid #E4E8EF}
.meta div{padding:10px 18px;border-right:1px solid #E4E8EF}
.meta div:last-child{border-right:0}
.meta small{font-size:11px;color:#6B7686;display:block}.meta b{font-size:13px}
.wrap{padding:16px 18px}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:16px}
.kpi{background:#F4F6FA;border-radius:8px;padding:12px}
.kpi small{font-size:12px;color:#6B7686}.kpi b{display:block;font-size:22px;font-weight:700;color:#1A2D4F}
.sec{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#6B7686;font-weight:700;margin:16px 0 6px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{background:#1A2D4F;color:#fff;text-align:left;font-size:11px;padding:8px 9px}
th.r,td.r{text-align:right}
td{padding:8px 9px;border-bottom:1px solid #EEF1F5}
tr.tot td{background:#1A2D4F;color:#fff;font-weight:700}
.two{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:16px}
.assin{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:34px;font-size:12px;color:#6B7686;text-align:center}
.assin div{border-top:1px solid #9aa3b2;padding-top:6px}
.sem{color:#6B7686;font-style:italic;font-size:12px;padding:8px 0}
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
  <a class="btn-back" href="/principal/master/?modo=dia&data=<?= htmlspecialchars($data) ?>">← Voltar</a>
</div>

<div class="doc">
  <div class="band">
    <div class="l">
      <svg viewBox="-4 -4 108 108" width="34" height="34"><ellipse cx="50" cy="50" rx="54" ry="19" fill="none" stroke="#B9C1CC" stroke-width="3.5" transform="rotate(-24 50 50)"/><circle cx="50" cy="50" r="44" fill="#1A2D4F" stroke="#B9C1CC" stroke-width="2.5"/><circle cx="92.8" cy="23.1" r="4.5" fill="#E0A53D"/><path d="M 26.7 73.3 A 33 33 0 1 1 73.3 73.3" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round"/><path d="M 50 50 L 67.4 29.3 L 55.5 53.5 Z" fill="#E0A53D"/><circle cx="50" cy="50" r="6.5" fill="#E0A53D"/><circle cx="50" cy="50" r="3" fill="#1A2D4F"/></svg>
      <div><b><?= htmlspecialchars($empresa) ?></b><small>Relatório diário de obra · RDO</small></div>
    </div>
    <div class="num">RDO nº —<br><small style="color:#9FB4D6"><?= $diaSem ?>, <?= htmlspecialchars($dataFmt) ?></small></div>
  </div>
  <div class="meta">
    <div><small>Tempo</small><b>—</b></div>
    <div><small>Temperatura</small><b>— °C</b></div>
    <div><small>Terreno</small><b>—</b></div>
    <div><small>Contrato</small><b>—</b></div>
  </div>
  <div class="wrap">
    <div class="kpis">
      <div class="kpi"><small>Rede executada</small><b><?= number_format($metrosDia, 1, ',', '.') ?> m</b></div>
      <div class="kpi"><small>Presentes</small><b><?= $presentes ?> / <?= $totalPres ?></b></div>
      <div class="kpi"><small>Ramais</small><b><?= (int)($ramais['qtd'] ?? 0) ?></b></div>
      <div class="kpi"><small>Interferências</small><b><?= count($interfs) > 0 ? array_sum(array_column($interfs,'qtd')) : 0 ?></b></div>
    </div>

    <?php if (!empty($producaoPorEquipe)): ?>
    <p class="sec">Produção por equipe</p>
    <table>
      <tr><th>Equipe</th><th>Bacia</th><th>PV mont.</th><th>PV jus.</th><th class="r">Metros</th></tr>
      <?php $totalM = 0; foreach ($producaoPorEquipe as $r): $totalM += (float)$r['extensao_planejada']; ?>
      <tr>
        <td><?= htmlspecialchars($r['equipe']) ?></td>
        <td><?= htmlspecialchars($r['bacia'] ?: '—') ?></td>
        <td><?= htmlspecialchars($r['pv_montante'] ?: '—') ?></td>
        <td><?= htmlspecialchars($r['pv_jusante'] ?: '—') ?></td>
        <td class="r"><b><?= number_format($r['extensao_planejada'], 1, ',', '.') ?></b></td>
      </tr>
      <?php endforeach; ?>
      <tr class="tot"><td colspan="4">Total</td><td class="r"><?= number_format($totalM, 1, ',', '.') ?> m</td></tr>
    </table>
    <?php endif; ?>

    <div class="two">
      <div>
        <p class="sec">Equipamentos + horas</p>
        <?php if (!empty($equipamentosDia)): ?>
        <table>
          <?php foreach ($equipamentosDia as $eq): ?>
          <tr><td><?= htmlspecialchars($eq['nome']) ?></td><td class="r"><?= number_format($eq['horas'], 1, ',', '.') ?> h</td></tr>
          <?php endforeach; ?>
        </table>
        <?php else: ?>
        <p class="sem">Sem registro de equipamentos neste dia.</p>
        <?php endif; ?>

        <p class="sec" style="margin-top:14px">Presença</p>
        <table>
          <tr><td>Presentes</td><td class="r"><?= $presentes ?></td></tr>
          <tr><td>Ausentes / irregulares</td><td class="r"><?= $ausentes ?></td></tr>
          <tr><td>Total</td><td class="r"><?= $totalPres ?></td></tr>
        </table>

        <?php if (!empty($interfs)): ?>
        <p class="sec" style="margin-top:14px">Interferências</p>
        <table>
          <?php foreach ($interfs as $i): ?>
          <tr>
            <td><?= htmlspecialchars(str_replace('_', ' ', ucfirst($i['tipo']))) ?></td>
            <td class="r"><?= (int)$i['qtd'] ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
      </div>
      <div>
        <p class="sec">Materiais aplicados (qtd.)</p>
        <?php if (!empty($materiaisAplicadosDia)): ?>
        <table>
          <?php foreach ($materiaisAplicadosDia as $mat): ?>
          <tr>
            <td><?= htmlspecialchars($mat['nome']) ?></td>
            <td class="r"><?= number_format($mat['qtd'], 0, ',', '.') ?> <?= htmlspecialchars($mat['unidade']) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php else: ?>
        <p class="sem">Sem registro de materiais aplicados neste dia.</p>
        <?php endif; ?>

        <p class="sec" style="margin-top:14px">Serviços complementares</p>
        <table>
          <tr><td>Ramais domiciliares</td><td class="r"><?= (int)($ramais['qtd'] ?? 0) ?></td></tr>
          <tr><td>Ext. pista (m)</td><td class="r"><?= number_format($ramais['m_pista'] ?? 0, 1, ',', '.') ?></td></tr>
          <tr><td>Ext. calçada (m)</td><td class="r"><?= number_format($ramais['m_calcada'] ?? 0, 1, ',', '.') ?></td></tr>
          <tr><td>Pontões</td><td class="r"><?= $pontoes ?></td></tr>
        </table>
      </div>
    </div>

    <div class="assin">
      <div>Encarregado de frente</div>
      <div>Fiscalização</div>
    </div>
  </div>
  <div class="rodape">
    <span><?= htmlspecialchars($empresa) ?> — RDO Executivo — <?= htmlspecialchars($dataFmt) ?></span>
    <span>Gerado em <?= date('d/m/Y H:i') ?></span>
  </div>
</div>
</body>
</html>
