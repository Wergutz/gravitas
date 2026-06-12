<?php
$empresa = defined('APP_CLIENT') ? APP_CLIENT : 'GRAVITAS';
$criticos = array_filter($materiais, fn($m) => (float)$m['estoque_atual'] <= (float)$m['minimo'] && (float)$m['minimo'] > 0);
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Materiais — <?= date('d/m/Y') ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Inter",-apple-system,Helvetica,Arial,sans-serif;font-size:12px;color:#1E2738;background:#fff;padding:24px 30px}
.rel-topo{display:flex;align-items:flex-start;justify-content:space-between;border-bottom:2px solid #1A2D4F;padding-bottom:12px;margin-bottom:18px}
.rel-topo h1{font-size:17px;font-weight:800;color:#1A2D4F}
.rel-topo p{font-size:11px;color:#6B7686;margin-top:3px}
.logotipo{font-size:14px;font-weight:900;letter-spacing:3px;color:#1A2D4F;text-align:right}
.sub-logo{font-size:9px;letter-spacing:1.5px;color:#6B7686;font-weight:600}
table{width:100%;border-collapse:collapse;margin-bottom:16px}
thead th{background:#1A2D4F;color:#fff;font-size:10px;letter-spacing:.8px;text-transform:uppercase;padding:8px 10px;text-align:left}
tbody tr:nth-child(even){background:#F4F6FA}
tbody td{padding:8px 10px;border-bottom:1px solid #E4E8EF;font-size:12px}
td.n{text-align:right;font-weight:700}
.badge-critico{display:inline-block;background:#C0392B12;color:#B23A2C;font-size:9px;font-weight:800;letter-spacing:.5px;padding:2px 7px;border-radius:99px;text-transform:uppercase}
.badge-ok{display:inline-block;background:#2E9E8F14;color:#1F7A6E;font-size:9px;font-weight:800;letter-spacing:.5px;padding:2px 7px;border-radius:99px;text-transform:uppercase}
.alerta-bloco{background:#C0392B12;color:#B23A2C;border-radius:10px;padding:12px 14px;margin-bottom:14px;font-size:12px;font-weight:600}
.secao{font-size:10.5px;letter-spacing:1.2px;text-transform:uppercase;font-weight:800;color:#6B7686;margin:14px 0 8px}
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
  <a class="btn-csv" href="?fmt=csv">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    Exportar CSV
  </a>
  <a class="btn-csv" href="/marco_urbano/master/">← Voltar</a>
</div>

<div class="rel-topo">
  <div>
    <h1>Posição de Materiais</h1>
    <p>Posição em <?= date('d/m/Y H:i') ?></p>
  </div>
  <div>
    <div class="logotipo"><?= htmlspecialchars($empresa) ?></div>
    <div class="sub-logo" style="text-align:right">IMPLANTAÇÃO DE REDE</div>
  </div>
</div>

<?php if (!empty($criticos)): ?>
<div class="alerta-bloco">
  ⚠ <?= count($criticos) ?> material(is) abaixo do estoque mínimo — reposição necessária.
</div>
<?php endif; ?>

<?php if (empty($materiais)): ?>
  <p style="color:#6B7686;font-style:italic;text-align:center;padding:30px 0">Nenhum material cadastrado no catálogo.</p>
<?php else: ?>
<table>
  <thead>
    <tr>
      <th>Material</th>
      <th>Un.</th>
      <th style="text-align:right">Estoque atual</th>
      <th style="text-align:right">Reservado</th>
      <th style="text-align:right">Mínimo</th>
      <th style="text-align:center">Status</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($materiais as $m):
      $critico = (float)$m['minimo'] > 0 && (float)$m['estoque_atual'] <= (float)$m['minimo'];
  ?>
    <tr <?= $critico ? 'style="background:#C0392B08"' : '' ?>>
      <td><?= htmlspecialchars($m['nome']) ?></td>
      <td><?= htmlspecialchars($m['unidade']) ?></td>
      <td class="n"><?= number_format($m['estoque_atual'], 2, ',', '.') ?></td>
      <td class="n" style="color:#A96C2A"><?= number_format($m['reservado'], 2, ',', '.') ?></td>
      <td class="n" style="color:#6B7686"><?= number_format($m['minimo'], 2, ',', '.') ?></td>
      <td style="text-align:center">
        <?php if ($critico): ?>
          <span class="badge-critico">Crítico</span>
        <?php else: ?>
          <span class="badge-ok">OK</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<div class="rodape">
  <span><?= htmlspecialchars($empresa) ?> — Posição de Materiais</span>
  <span>Gerado em <?= date('d/m/Y H:i') ?></span>
</div>
</body>
</html>
