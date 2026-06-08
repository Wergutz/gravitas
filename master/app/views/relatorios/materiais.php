<?php
$empresa      = defined('APP_CLIENT') ? APP_CLIENT : 'GRAVITAS';
$materiaisErro = $materiaisErro ?? false;
$emFalta  = array_filter($materiais, fn($m) => (float)$m['minimo'] > 0 && (float)$m['estoque_atual'] == 0);
$baixo    = array_filter($materiais, fn($m) => (float)$m['minimo'] > 0 && (float)$m['estoque_atual'] > 0 && (float)$m['estoque_atual'] <= (float)$m['minimo']);
$alertas  = count($emFalta) + count($baixo);
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Materiais — <?= date('d/m/Y') ?></title>
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
.mini{display:flex;gap:20px;flex-wrap:wrap;font-size:12px;color:#6B7686;margin-bottom:14px}
.mini b{color:#1E2738}
.warn-box{display:flex;gap:8px;background:#F6E7CF;color:#A96C2A;border-radius:8px;padding:10px 14px;font-size:12px;font-weight:600;margin-bottom:14px}
.err-box{display:flex;gap:8px;background:#FBEDEA;color:#B23A2C;border-radius:8px;padding:10px 14px;font-size:12px;font-weight:600;margin-bottom:14px}
.sec{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#6B7686;font-weight:700;margin:16px 0 6px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{background:#1A2D4F;color:#fff;text-align:left;font-size:11px;padding:8px 9px}
th.r,td.r{text-align:right}
td{padding:8px 9px;border-bottom:1px solid #EEF1F5}
.ok{color:#1F7A6E;font-weight:700}.dn{color:#B23A2C;font-weight:700}.low{color:#A96C2A;font-weight:700}
.tr-em-falta td{background:#FBEDEA}
.tr-baixo td{background:#FEF8EC}
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
  <a class="btn-csv" href="?fmt=csv">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    Exportar CSV
  </a>
  <a class="btn-csv" href="/principal/master/">← Voltar</a>
</div>

<div class="doc">
  <div class="band">
    <div class="l">
      <svg viewBox="-4 -4 108 108" width="34" height="34"><ellipse cx="50" cy="50" rx="54" ry="19" fill="none" stroke="#B9C1CC" stroke-width="3.5" transform="rotate(-24 50 50)"/><circle cx="50" cy="50" r="44" fill="#1A2D4F" stroke="#B9C1CC" stroke-width="2.5"/><circle cx="92.8" cy="23.1" r="4.5" fill="#E0A53D"/><path d="M 26.7 73.3 A 33 33 0 1 1 73.3 73.3" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round"/><path d="M 50 50 L 67.4 29.3 L 55.5 53.5 Z" fill="#E0A53D"/><circle cx="50" cy="50" r="6.5" fill="#E0A53D"/><circle cx="50" cy="50" r="3" fill="#1A2D4F"/></svg>
      <div><b><?= htmlspecialchars($empresa) ?></b><small>Materiais · posição de estoque</small></div>
    </div>
    <div class="num">Itens <?= count($materiais) ?><br><small style="color:#9FB4D6">posição <?= date('d/m') ?></small></div>
  </div>
  <div class="wrap">
    <?php if ($materiaisErro): ?>
    <div class="err-box">⚠ Erro ao carregar os dados. Verifique o catálogo de materiais no banco de dados.</div>
    <?php elseif ($alertas > 0): ?>
    <div class="warn-box">⚠ <?= $alertas ?> item(ns) abaixo do estoque mínimo — reposição necessária.</div>
    <?php endif; ?>

    <div class="mini">
      <span>Posição em: <b><?= date('d/m/Y H:i') ?></b></span>
      <?php if ($alertas > 0): ?>
      <span>Em falta/baixo: <b class="dn"><?= $alertas ?></b></span>
      <?php endif; ?>
    </div>

    <?php if (empty($materiais) && !$materiaisErro): ?>
      <p style="color:#6B7686;font-style:italic;text-align:center;padding:30px 0">Nenhum material cadastrado no catálogo.</p>
    <?php elseif (!empty($materiais)): ?>
    <table>
      <tr>
        <th>Código</th><th>Material</th><th>Un.</th>
        <th class="r">Físico</th><th class="r">Reservado</th><th class="r">Disponível</th><th class="r">Mínimo</th>
        <th>Status</th>
      </tr>
      <?php foreach ($materiais as $m):
          $fis = (float)$m['estoque_atual'];
          $res = (float)$m['reservado'];
          $dis = (float)$m['disponivel'];
          $min = (float)$m['minimo'];
          $emFaltaRow = $min > 0 && $fis == 0;
          $baixoRow   = $min > 0 && $fis > 0 && $fis <= $min;
          $rowClass   = $emFaltaRow ? 'tr-em-falta' : ($baixoRow ? 'tr-baixo' : '');
      ?>
      <tr class="<?= $rowClass ?>">
        <td><?= htmlspecialchars($m['codigo'] ?? '—') ?></td>
        <td><?= htmlspecialchars($m['nome']) ?></td>
        <td><?= htmlspecialchars($m['unidade']) ?></td>
        <td class="r <?= $emFaltaRow ? 'dn' : '' ?>"><?= number_format($fis, 0, ',', '.') ?></td>
        <td class="r" style="color:#A96C2A"><?= number_format($res, 0, ',', '.') ?></td>
        <td class="r"><?= number_format($dis, 0, ',', '.') ?></td>
        <td class="r" style="color:#6B7686"><?= number_format($min, 0, ',', '.') ?></td>
        <td>
          <?php if ($emFaltaRow): ?>
            <span class="dn">Em falta</span>
          <?php elseif ($baixoRow): ?>
            <span class="low">Baixo</span>
          <?php else: ?>
            <span class="ok">OK</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
  <div class="rodape">
    <span><?= htmlspecialchars($empresa) ?> — Posição de Materiais</span>
    <span>Gerado em <?= date('d/m/Y H:i') ?></span>
  </div>
</div>
</body>
</html>
