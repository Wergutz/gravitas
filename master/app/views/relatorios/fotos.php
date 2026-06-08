<?php
$empresa     = defined('APP_CLIENT') ? APP_CLIENT : 'GRAVITAS';
$uploadsBase = defined('EXECUTOR_UPLOADS') ? EXECUTOR_UPLOADS : '/principal/executor/uploads';
$dataFmt     = date('d/m/Y', strtotime($data));
$stepLabels  = [
    1=>'Situação inicial (frente)',2=>'Situação inicial (fundo)',3=>'Alinhamento e níveis',
    4=>'Escavação — início',5=>'Escavação — trecho',6=>'Escoramento',7=>'Fundação',
    8=>'Assentamento',9=>'Detalhe tubo / emenda',10=>'Interligação PV',
    11=>'Ensaio de estanqueidade',12=>'Reaterro camadas',13=>'Compactação',
    14=>'Regularização',15=>'PV — situação final',16=>'Sinalização viária',
    17=>'Interferência',18=>'Situação final geral',19=>'Ramal domiciliar',
    20=>'Pontão',21=>'Equipe — encerramento',
];
$stepsComFoto = array_keys($fotosPorStep);
$cobertura    = count($stepsComFoto);
// Info from first diary of the day
$equipeNome   = !empty($fotos) ? $fotos[0]['equipe'] : '—';
$trechoInfo   = !empty($fotos) ? (($fotos[0]['pv_montante'] ?? '') . '→' . ($fotos[0]['pv_jusante'] ?? '')) : '—';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Relatório Fotográfico — <?= htmlspecialchars($dataFmt) ?></title>
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
.mini{display:flex;gap:20px;flex-wrap:wrap;font-size:12px;color:#6B7686;margin-bottom:14px}
.mini b{color:#1E2738}
.sec{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#6B7686;font-weight:700;margin:16px 0 6px}
.fotos{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:18px}
.foto{border:1px solid #E4E8EF;border-radius:8px;overflow:hidden}
.foto .ph{position:relative;height:150px;background:#F4F6FA;display:grid;place-items:center;overflow:hidden}
.foto .ph img{width:100%;height:100%;object-fit:cover;display:block}
.foto .ph .noimg{color:#aab1bd;font-size:32px}
.foto .stamp{position:absolute;left:0;right:0;bottom:0;background:rgba(17,32,59,.82);color:#fff;font-size:9.5px;padding:3px 6px;line-height:1.4}
.foto .cap{padding:9px 11px}
.foto .cap b{font-size:12px;display:block}
.foto .cap span{font-size:11px;color:#6B7686}
.step-sec{font-size:10px;letter-spacing:1px;text-transform:uppercase;font-weight:800;color:#1A2D4F;margin:18px 0 8px;padding-bottom:4px;border-bottom:2px solid #1A2D4F}
.cov{display:grid;grid-template-columns:repeat(auto-fill,minmax(38px,1fr));gap:6px;margin-bottom:8px}
.cov span{aspect-ratio:1;border-radius:6px;background:#F1EFE8;display:grid;place-items:center;font-size:11px;color:#888;font-weight:600}
.cov span.f{background:#1D9E75;color:#fff;font-weight:700}
.leg{display:flex;gap:16px;font-size:11px;color:#6B7686;margin-top:4px}
.leg span{display:flex;align-items:center;gap:5px}
.dot{width:11px;height:11px;border-radius:3px;display:inline-block}
.rodape{padding:10px 18px;border-top:1px solid #E4E8EF;display:flex;justify-content:space-between;font-size:10px;color:#6B7686;margin-top:4px}
@media print{
  .no-print{display:none!important}
  body{background:#fff;padding:8px}
  .doc{border:0;border-radius:0}
  .fotos{grid-template-columns:repeat(3,1fr)}
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
  <a class="btn-back" href="/principal/master/?modo=dia&data=<?= htmlspecialchars($data) ?>">← Voltar</a>
</div>

<div class="doc">
  <div class="band">
    <div class="l">
      <svg viewBox="-4 -4 108 108" width="34" height="34"><ellipse cx="50" cy="50" rx="54" ry="19" fill="none" stroke="#B9C1CC" stroke-width="3.5" transform="rotate(-24 50 50)"/><circle cx="50" cy="50" r="44" fill="#1A2D4F" stroke="#B9C1CC" stroke-width="2.5"/><circle cx="92.8" cy="23.1" r="4.5" fill="#E0A53D"/><path d="M 26.7 73.3 A 33 33 0 1 1 73.3 73.3" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round"/><path d="M 50 50 L 67.4 29.3 L 55.5 53.5 Z" fill="#E0A53D"/><circle cx="50" cy="50" r="6.5" fill="#E0A53D"/><circle cx="50" cy="50" r="3" fill="#1A2D4F"/></svg>
      <div><b><?= htmlspecialchars($empresa) ?></b><small>Relatório fotográfico · diário</small></div>
    </div>
    <div class="num"><?= htmlspecialchars($dataFmt) ?><br><small style="color:#9FB4D6"><?= htmlspecialchars($equipeNome) ?></small></div>
  </div>
  <div class="wrap">
    <div class="mini">
      <span>Trecho: <b><?= htmlspecialchars($trechoInfo) ?></b></span>
      <span>Fotos: <b><?= count($fotos) ?></b></span>
      <span>Cobertura: <b><?= $cobertura ?> / 21 passos</b></span>
    </div>

    <?php if (empty($fotos)): ?>
      <p style="color:#6B7686;font-style:italic;text-align:center;padding:30px 0">Nenhuma foto registrada para <?= htmlspecialchars($dataFmt) ?>.</p>
    <?php else:
      ksort($fotosPorStep);
      foreach ($fotosPorStep as $step => $stepFotos):
        $label = $stepLabels[$step] ?? 'Etapa ' . $step;
    ?>
    <div class="step-sec">Passo <?= (int)$step ?> — <?= htmlspecialchars($label) ?></div>
    <div class="fotos">
      <?php foreach ($stepFotos as $f):
          $thumb  = !empty($f['thumb']) ? $f['thumb'] : null;
          $imgSrc = $thumb ? htmlspecialchars($uploadsBase . '/thumbs/' . $thumb) : null;
          $stamp  = '';
          if (!empty($f['created_at'])) {
              $stamp .= date('d/m H:i', strtotime($f['created_at']));
          } elseif (!empty($f['updated_at'])) {
              $stamp .= date('d/m H:i', strtotime($f['updated_at']));
          }
          if (!empty($f['lat']) && !empty($f['lng'])) {
              $stamp .= ($stamp ? ' · ' : '') . number_format($f['lat'], 4) . ', ' . number_format($f['lng'], 4);
          }
      ?>
      <div class="foto">
        <div class="ph">
          <?php if ($imgSrc): ?>
            <img src="<?= $imgSrc ?>" alt="Passo <?= (int)$step ?>" loading="lazy">
          <?php else: ?>
            <span class="noimg">📷</span>
          <?php endif; ?>
          <?php if ($stamp): ?>
          <div class="stamp"><?= htmlspecialchars($stamp) ?></div>
          <?php endif; ?>
        </div>
        <div class="cap">
          <b>Passo <?= (int)$step ?> — <?= htmlspecialchars($label) ?></b>
          <span><?= htmlspecialchars($f['equipe']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; endif; ?>

    <p class="sec">Cobertura de fotos por passo (21 passos)</p>
    <div class="cov">
      <?php for ($s = 1; $s <= 21; $s++): ?>
      <span class="<?= isset($fotosPorStep[$s]) ? 'f' : '' ?>" title="Passo <?= $s ?>: <?= htmlspecialchars($stepLabels[$s] ?? 'Passo '.$s) ?>"><?= $s ?></span>
      <?php endfor; ?>
    </div>
    <div class="leg">
      <span><span class="dot" style="background:#1D9E75"></span> com foto</span>
      <span><span class="dot" style="background:#F1EFE8;border:1px solid #ddd"></span> sem foto</span>
    </div>
  </div>
  <div class="rodape">
    <span><?= htmlspecialchars($empresa) ?> — Relatório Fotográfico — <?= htmlspecialchars($dataFmt) ?></span>
    <span><?= count($fotos) ?> foto(s) · <?= $cobertura ?>/21 passos · Gerado em <?= date('d/m/Y H:i') ?></span>
  </div>
</div>
</body>
</html>
