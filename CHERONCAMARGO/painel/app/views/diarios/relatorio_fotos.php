<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Relatório Fotográfico · <?= htmlspecialchars($diario['equipe_nome']) ?> · <?= date('d/m/Y', strtotime($diario['data'])) ?></title>
<style>
  :root{--navy:#1A2D4F;--gold:#E0A53D;--ink:#1E2738;--muted:#6B7686;--line:#E4E8EF;--bg:#F4F6FA;
    --font:"Inter",-apple-system,Segoe UI,Roboto,sans-serif}
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:var(--font);background:#fff;color:var(--ink);font-size:13px}
  .no-print{position:fixed;top:16px;right:16px;z-index:10;display:flex;gap:8px}
  .no-print button,.no-print a{border:0;background:var(--navy);color:#fff;border-radius:8px;
    padding:9px 16px;font-family:var(--font);font-size:13px;font-weight:700;cursor:pointer;text-decoration:none}
  .no-print a{background:var(--bg);color:var(--ink);border:1px solid var(--line)}
  .capa{background:linear-gradient(160deg,var(--navy),#11203B);color:#fff;padding:40px 48px 32px;
    display:flex;align-items:center;gap:20px}
  .capa img{width:56px;height:56px;flex:0 0 auto;object-fit:contain}
  .capa-txt h1{font-size:22px;font-weight:800;letter-spacing:1px}
  .capa-txt .sub{font-size:13px;color:#9FB4D6;margin-top:6px;line-height:1.5}
  .kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:0;border-bottom:2px solid var(--line)}
  .kpi{padding:16px 20px;border-right:1px solid var(--line)}
  .kpi:last-child{border-right:0}
  .kpi b{font-size:20px;display:block;color:var(--navy)}
  .kpi span{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px}
  .sec{padding:28px 40px 0}
  .sec-tit{font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:800;
    color:var(--muted);margin-bottom:16px;display:flex;align-items:center;gap:8px}
  .sec-tit::after{content:'';flex:1;height:1px;background:var(--line)}
  .galeria{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;padding-bottom:24px}
  .foto-card{border:1px solid var(--line);border-radius:10px;overflow:hidden}
  .foto-card img{width:100%;aspect-ratio:4/3;object-fit:cover;display:block}
  .foto-card .meta{padding:6px 8px;font-size:10.5px;color:var(--muted);line-height:1.4}
  .foto-card .meta b{display:block;font-size:11.5px;color:var(--ink)}
  .sem-fotos{color:var(--muted);font-size:12.5px;font-style:italic;padding-bottom:20px}
  @media print{
    .no-print{display:none}
    body{font-size:11px}
    .capa{padding:28px 32px 24px}
    .galeria{grid-template-columns:repeat(3,1fr);gap:8px}
    .sec{padding:20px 32px 0}
    .foto-card img{aspect-ratio:4/3}
    @page{size:A4;margin:10mm}
  }
</style>
</head>
<body>

<div class="no-print">
  <a href="<?= APP_BASE ?>/diarios/ver?id=<?= (int)$diario['id'] ?>">← Diário</a>
  <button onclick="window.print()">🖨️ Imprimir / PDF</button>
</div>

<!-- Capa -->
<div class="capa">
  <img src="/CHERONCAMARGO/painel/assets/img/icon-cheron-camargo.png" alt="Cheron & Camargo">
  <div class="capa-txt">
    <h1>Relatório Fotográfico</h1>
    <div class="sub">
      <?= htmlspecialchars($diario['equipe_nome']) ?> · <?= date('d/m/Y', strtotime($diario['data'])) ?><br>
      Trecho <?= htmlspecialchars($diario['pv_montante']) ?> → <?= htmlspecialchars($diario['pv_jusante']) ?><br>
      <?= htmlspecialchars($diario['rua'] ?? '') ?><?= $diario['bacia'] ? ' · Bacia ' . htmlspecialchars($diario['bacia']) : '' ?>
    </div>
  </div>
</div>

<div class="kpis">
  <div class="kpi"><b><?= array_sum(array_map('count', $fotosPorStep)) ?></b><span>Total de fotos</span></div>
  <div class="kpi"><b><?= count($fotosPorStep) ?></b><span>Passos com foto</span></div>
  <div class="kpi"><b><?= count(array_filter(array_merge(...array_values($fotosPorStep)), fn($f) => $f['lat'])) ?></b><span>Fotos com GPS</span></div>
  <div class="kpi"><b><?= date('d/m/Y') ?></b><span>Gerado em</span></div>
</div>

<?php foreach ($stepNomes as $step => $nome): ?>
<?php $fotosStep = $fotosPorStep[$step] ?? []; ?>
<div class="sec">
  <div class="sec-tit">Passo <?= $step ?> — <?= htmlspecialchars($nome) ?></div>
  <?php if ($fotosStep): ?>
  <div class="galeria">
    <?php foreach ($fotosStep as $foto): ?>
    <div class="foto-card">
      <img src="<?= $executorUploads ?>/<?= htmlspecialchars($foto['arquivo']) ?>"
           alt="<?= htmlspecialchars($foto['tipo'] ?? '') ?>">
      <div class="meta">
        <b><?= htmlspecialchars($foto['tipo'] ?: 'Foto passo ' . $step) ?></b>
        <?php if ($foto['lat']): ?>
        📍 <?= $foto['lat'] ?>, <?= $foto['lng'] ?><br>
        <?php endif; ?>
        🕐 <?= date('d/m/Y H:i', strtotime($foto['timestamp_servidor'])) ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="sem-fotos">Nenhuma foto registrada neste passo.</div>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<div style="padding:32px 40px;color:var(--muted);font-size:11px;border-top:1px solid var(--line);margin-top:20px">
  Cheron &amp; Camargo · Relatório Fotográfico de Execução · Documento gerado automaticamente pelo sistema.
</div>

</body>
</html>
