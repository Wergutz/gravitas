<?php
$empresa = defined('APP_CLIENT') ? APP_CLIENT : 'GRAVITAS';
$uploadsBase = defined('EXECUTOR_UPLOADS') ? EXECUTOR_UPLOADS : '/marco_urbano/executor/uploads';
$dataFmt = date('d/m/Y', strtotime($data));
$stepLabels = [
    1  => 'Situação inicial',
    2  => 'Situação inicial',
    3  => 'Alinhamento e níveis',
    4  => 'Escavação — início',
    5  => 'Escavação — trecho',
    6  => 'Escoramento',
    7  => 'Fundação',
    8  => 'Assentamento',
    9  => 'Detalhe tubo / emenda',
    10 => 'Interligação PV',
    11 => 'Ensaio de estanqueidade',
    12 => 'Reaterro camadas',
    13 => 'Compactação',
    14 => 'Regularização',
    15 => 'PV — situação final',
    16 => 'Sinalização viária',
    17 => 'Interferência',
    18 => 'Situação final geral',
    19 => 'Ramal domiciliar',
    20 => 'Pontão',
];
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Relatório Fotográfico — <?= htmlspecialchars($dataFmt) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Inter",-apple-system,Helvetica,Arial,sans-serif;font-size:12px;color:#1E2738;background:#fff;padding:18px 24px}
.rel-topo{display:flex;align-items:flex-start;justify-content:space-between;border-bottom:2px solid #1A2D4F;padding-bottom:10px;margin-bottom:16px}
.rel-topo h1{font-size:16px;font-weight:800;color:#1A2D4F}
.rel-topo p{font-size:11px;color:#6B7686;margin-top:2px}
.logotipo{font-size:13px;font-weight:900;letter-spacing:3px;color:#1A2D4F;text-align:right}
.sub-logo{font-size:8.5px;letter-spacing:1.5px;color:#6B7686;font-weight:600}
.step-secao{font-size:10px;letter-spacing:1.2px;text-transform:uppercase;font-weight:800;color:#1A2D4F;margin:16px 0 8px;padding-bottom:5px;border-bottom:2px solid #1A2D4F}
.fotos-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px}
.foto-card{border:1px solid #E4E8EF;border-radius:8px;overflow:hidden;break-inside:avoid}
.foto-card img{width:100%;aspect-ratio:4/3;object-fit:cover;display:block}
.foto-info{padding:6px 8px;background:#F4F6FA}
.foto-info .equipe{font-weight:700;font-size:11px;color:#1A2D4F}
.foto-info .coord{font-size:9.5px;color:#6B7686;margin-top:2px}
.sem-fotos{color:#6B7686;font-style:italic;text-align:center;padding:20px 0;font-size:12px;border:1px dashed #E4E8EF;border-radius:8px;margin-bottom:16px}
.rodape{margin-top:20px;border-top:1px solid #E4E8EF;padding-top:8px;display:flex;justify-content:space-between;font-size:9.5px;color:#6B7686}
.btn-print{display:inline-flex;align-items:center;gap:6px;background:#1A2D4F;color:#fff;border:0;border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;cursor:pointer;margin-bottom:14px}
.btn-voltar{display:inline-flex;align-items:center;gap:6px;background:#fff;color:#1A2D4F;border:1px solid #1A2D4F;border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;text-decoration:none;margin-bottom:14px;margin-left:6px}
@media print{
  .no-print{display:none!important}
  body{padding:8px 10px}
  .fotos-grid{grid-template-columns:repeat(3,1fr);gap:8px}
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
  <a class="btn-voltar" href="/marco_urbano/master/?modo=dia&data=<?= htmlspecialchars($data) ?>">← Voltar</a>
</div>

<div class="rel-topo">
  <div>
    <h1>Relatório Fotográfico</h1>
    <p><?= htmlspecialchars($dataFmt) ?> &nbsp;·&nbsp; Gerado em <?= date('d/m/Y H:i') ?></p>
  </div>
  <div>
    <div class="logotipo"><?= htmlspecialchars($empresa) ?></div>
    <div class="sub-logo" style="text-align:right">IMPLANTAÇÃO DE REDE</div>
  </div>
</div>

<?php if (empty($fotos)): ?>
  <div class="sem-fotos">Nenhuma foto registrada para <?= htmlspecialchars($dataFmt) ?>.</div>
<?php else:
  ksort($fotosPorStep);
  foreach ($fotosPorStep as $step => $stepFotos):
    $label = $stepLabels[$step] ?? 'Etapa ' . $step;
?>
  <div class="step-secao">Etapa <?= (int)$step ?> — <?= htmlspecialchars($label) ?></div>
  <div class="fotos-grid">
    <?php foreach ($stepFotos as $f):
        $thumb = !empty($f['thumb']) ? $f['thumb'] : null;
        $imgSrc = $thumb ? htmlspecialchars($uploadsBase . '/thumbs/' . $thumb) : null;
    ?>
    <div class="foto-card">
      <?php if ($imgSrc): ?>
        <img src="<?= $imgSrc ?>" alt="Foto etapa <?= (int)$step ?>" loading="lazy">
      <?php else: ?>
        <div style="aspect-ratio:4/3;background:#F4F6FA;display:grid;place-items:center;color:#6B7686;font-size:11px">Sem imagem</div>
      <?php endif; ?>
      <div class="foto-info">
        <div class="equipe"><?= htmlspecialchars($f['equipe']) ?></div>
        <?php if ($f['lat'] && $f['lng']): ?>
          <div class="coord"><?= number_format($f['lat'],6) ?>, <?= number_format($f['lng'],6) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; endif; ?>

<div class="rodape">
  <span><?= htmlspecialchars($empresa) ?> — Relatório Fotográfico — <?= htmlspecialchars($dataFmt) ?></span>
  <span><?= count($fotos) ?> foto(s) &nbsp;·&nbsp; Gerado em <?= date('d/m/Y H:i') ?></span>
</div>
</body>
</html>
