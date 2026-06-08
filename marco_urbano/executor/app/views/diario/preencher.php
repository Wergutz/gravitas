<?php
header('X-Robots-Tag: noindex, nofollow');
$stepAtual = (int)$diario['step_atual'];
$totalSteps = 21;
$pct = (int)round($stepAtual / $totalSteps * 100);
$diarioId = (int)$diario['id'];
$bloqueado = $diario['status'] === 'enviado';

// Helpers
function isFeito(int $step, int $stepAtual): bool { return $step <= $stepAtual; }
function stepClass(int $step, int $stepAtual): string {
    $c = 'step';
    if (isFeito($step, $stepAtual)) $c .= ' feito';
    if ($step === $stepAtual + 1)   $c .= ' aberto';
    return $c;
}

$fotosStep = [];
foreach ($fotos as $f) {
    $fotosStep[$f['step_num']][] = $f;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1A2D4F">
<meta name="robots" content="noindex,nofollow">
<meta name="csrf" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
<title>Diário <?= date('d/m/Y', strtotime($diario['data'])) ?> · MARCO URBANO</title>
<link rel="stylesheet" href="<?= EXECUTOR_BASE ?>/assets/css/executor.css">
</head>
<body>
<div class="phone">

<!-- Topo -->
<div class="top">
  <div class="top-row">
    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Marco Urbano Urbanizadora">
  <rect x="4" y="4" width="92" height="92" rx="26" fill="none" stroke="#3CB86A" stroke-width="3"/>
  <rect x="14" y="14" width="72" height="72" rx="19" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="50" cy="33" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="50" cy="67" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="33" cy="50" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="67" cy="50" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="50" cy="50" r="5" fill="#3CB86A"/>
</svg>
    <div class="nm">MARCO URBANO<small>DIÁRIO <?= date('d/m', strtotime($diario['data'])) ?></small></div>
    <a href="<?= EXECUTOR_BASE ?>/" style="margin-left:auto;color:#9FB4D6;font-size:11px;text-decoration:none">← Início</a>
  </div>
  <div class="hoje">
    <span>📅 <?= date('d/m/Y', strtotime($diario['data'])) ?> · <?= htmlspecialchars($trecho['pv_montante'] ?? '') ?> → <?= htmlspecialchars($trecho['pv_jusante'] ?? '') ?></span>
    <span class="gps" id="gps-status">📍…</span>
  </div>
</div>

<div class="scroll">

<!-- Progresso -->
<div class="prog-card">
  <div class="t">Progresso do diário <b id="prog-pct"><?= $pct ?>%</b></div>
  <div class="barra"><i id="prog-bar" style="width:<?= $pct ?>%"></i></div>
</div>

<?php if ($bloqueado): ?>
<div class="info" style="border-color:var(--ok)">
  <div class="info-h">
    <span class="ic i-ok">✅</span>
    <div><b>Diário enviado</b><span>Este diário já foi enviado ao Planejador e não pode ser editado.</span></div>
  </div>
</div>
<?php endif; ?>

<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

<!-- ================================================================
     PASSOS DO DIÁRIO
     ================================================================ -->

<!-- Passo 1: Equipe na obra + ausentes -->
<div class="<?= stepClass(1, $stepAtual) ?>" data-step="1">
  <div class="step-h">
    <span class="n">1</span>
    <div class="tt"><b>Equipe na obra</b><span>Quem está presente / ausente</span></div>
    <span class="ck"><?= isFeito(1, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <?php if ($bloqueado): ?>
      <?php foreach ($presencas as $p): ?>
      <div class="pres">
        <span class="av"><?= strtoupper(substr($p['nome'], 0, 2)) ?></span>
        <span class="nome"><?= htmlspecialchars($p['nome']) ?></span>
        <span class="badge <?= $p['status'] === 'presente' ? 'b-ok' : 'b-erro' ?>"><?= htmlspecialchars($p['status']) ?></span>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
    <form id="form-step-1" onsubmit="return false">
      <div class="lbl">Marque quem está ausente hoje:</div>
      <?php
      $presencaMap = [];
      foreach ($presencas as $p) $presencaMap[$p['funcionario_id']] = $p;
      foreach ($funcionariosEquipe as $f):
        $pres = $presencaMap[$f['id']] ?? null;
        $status = $pres['status'] ?? 'presente';
      ?>
      <div class="pres">
        <span class="av"><?= strtoupper(substr(htmlspecialchars($f['nome']), 0, 2)) ?></span>
        <span class="nome"><?= htmlspecialchars($f['nome']) ?> <small style="color:var(--muted)"><?= htmlspecialchars($f['funcao'] ?? '') ?></small></span>
        <select name="presenca[<?= (int)$f['id'] ?>]">
          <option value="presente"   <?= $status === 'presente'   ? 'selected' : '' ?>>Presente</option>
          <option value="ausente"    <?= $status === 'ausente'    ? 'selected' : '' ?>>Ausente</option>
          <option value="atrasou"    <?= $status === 'atrasou'    ? 'selected' : '' ?>>Atrasou</option>
          <option value="saiu_cedo"  <?= $status === 'saiu_cedo'  ? 'selected' : '' ?>>Saiu cedo</option>
        </select>
      </div>
      <?php endforeach; ?>
      <button type="button" class="btn-step-ok" onclick="salvarPresencasStep1(<?= $diarioId ?>)">✔ Confirmar presença</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<!-- Passo 2: Atrasos / saídas antecipadas -->
<div class="<?= stepClass(2, $stepAtual) ?>" data-step="2">
  <div class="step-h">
    <span class="n">2</span>
    <div class="tt"><b>Atrasos / saídas</b><span>Registrar horários e observações</span></div>
    <span class="ck"><?= isFeito(2, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <?php if (!$bloqueado): ?>
    <form id="form-step-2" onsubmit="return false">
      <?php foreach ($funcionariosEquipe as $f):
        $pres = $presencaMap[$f['id']] ?? null;
        if ($pres && in_array($pres['status'], ['atrasou','saiu_cedo'])): ?>
      <div class="card-mini">
        <b><?= htmlspecialchars($f['nome']) ?></b>
        <div style="margin-top:6px">
          <textarea name="obs[<?= (int)$f['id'] ?>]" placeholder="Observação / horário…" rows="2"><?= htmlspecialchars($pres['obs'] ?? '') ?></textarea>
        </div>
      </div>
      <?php endif; endforeach; ?>
      <div class="hint">Funcionários marcados como "atrasou" ou "saiu cedo" no passo 1 aparecem aqui.</div>
      <button type="button" class="btn-step-ok" onclick="marcarStepFeito(2)">✔ Confirmar</button>
    </form>
    <?php else: ?>
    <div class="hint">Passo registrado.</div>
    <?php endif; ?>
  </div>
</div>

<!-- Passo 3: Estoque na frente -->
<div class="<?= stepClass(3, $stepAtual) ?>" data-step="3">
  <div class="step-h">
    <span class="n">3</span>
    <div class="tt"><b>Estoque na frente</b><span>Tem tudo? Se não, quais faltam</span></div>
    <span class="ck"><?= isFeito(3, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <?php if (!$bloqueado): ?>
    <form id="form-step-3" onsubmit="return false">
      <div class="lbl">Situação do material:</div>
      <select name="estoque_ok" id="estoque-ok-sel" onchange="toggleFaltasMat()">
        <option value="1">Sim — tem tudo</option>
        <option value="0">Não — falta material</option>
      </select>
      <div id="faltas-mat" style="display:none;margin-top:10px">
        <div class="lbl">Quais materiais estão faltando?</div>
        <textarea name="materiais_faltando" rows="3" placeholder="Ex: tubo PVC 200mm, anel de borracha…"></textarea>
      </div>
      <button type="button" class="btn-step-ok" onclick="salvarStepSimples(<?= $diarioId ?>, 3, 'form-step-3')">✔ Confirmar</button>
    </form>
    <?php else: ?><div class="hint">Passo registrado.</div><?php endif; ?>
  </div>
</div>

<!-- Passos 4–5: Fotos de carga e sinalização -->
<?php
$fotoSteps = [
  4  => ['Foto: carregando material', 'Foto da carga sendo levada para a frente de serviço'],
  5  => ['Fotos: sinalização + EPIs', 'Sinalização viária e equipe com EPIs'],
];
foreach ($fotoSteps as $sn => [$titulo, $desc]):
?>
<div class="<?= stepClass($sn, $stepAtual) ?>" data-step="<?= $sn ?>">
  <div class="step-h">
    <span class="n"><?= $sn ?></span>
    <div class="tt"><b><?= $titulo ?></b><span><?= $desc ?></span></div>
    <span class="ck"><?= isFeito($sn, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <div class="fotos" id="fotos-<?= $sn ?>">
      <?php foreach ($fotosStep[$sn] ?? [] as $foto): ?>
      <div class="foto">
        <img src="<?= EXECUTOR_BASE ?>/uploads/<?= htmlspecialchars($foto['arquivo']) ?>" alt="">
        <?php if ($foto['lat']): ?><span class="gpsb">GPS</span><?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php if (!$bloqueado): ?>
      <label class="cam" title="Adicionar foto">
        <span class="cic">📷</span>Foto
        <input type="file" accept="image/*" capture="environment" style="display:none"
               onchange="handleFotoUpload(this, <?= $diarioId ?>, <?= $sn ?>, '', <?= $sn ?>)">
      </label>
      <?php endif; ?>
    </div>
    <?php if (!$bloqueado): ?>
    <button type="button" class="btn-step-ok" onclick="marcarStepFeito(<?= $sn ?>)">✔ Fotos ok</button>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<!-- Passo 6: Equipamentos -->
<div class="<?= stepClass(6, $stepAtual) ?>" data-step="6">
  <div class="step-h">
    <span class="n">6</span>
    <div class="tt"><b>Equipamentos</b><span>Funcionando? — foto de cada</span></div>
    <span class="ck"><?= isFeito(6, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <?php if (!$bloqueado): ?>
    <form id="form-step-6" onsubmit="return false">
      <?php
      $idxEq = 0;
      foreach ($equipsPesados as $eq):
      ?>
      <div class="card-mini">
        <div class="ch">
          <span>🚛 <?= htmlspecialchars($eq['tipo']) ?> <?= htmlspecialchars($eq['modelo']) ?> (<?= htmlspecialchars($eq['placa']) ?>)</span>
        </div>
        <input type="hidden" name="equip_id[<?= $idxEq ?>]"   value="<?= (int)$eq['id'] ?>">
        <input type="hidden" name="equip_tipo[<?= $idxEq ?>]" value="pesado">
        <div class="row2" style="margin-top:8px">
          <select name="equip_func[<?= $idxEq ?>]">
            <option value="1">✅ Funcionando</option>
            <option value="0">❌ Com problema</option>
          </select>
          <div class="fotos" id="fotos-eq-<?= $idxEq ?>">
            <label class="cam" title="Foto">
              <span class="cic">📷</span>
              <input type="file" accept="image/*" capture="environment" style="display:none"
                     onchange="handleFotoEq(this, <?= $diarioId ?>, <?= $idxEq ?>)">
              <input type="hidden" name="equip_foto[<?= $idxEq ?>]" id="foto-eq-<?= $idxEq ?>">
            </label>
          </div>
        </div>
        <input type="text" name="equip_obs[<?= $idxEq ?>]" placeholder="Observação (opcional)" style="margin-top:6px">
      </div>
      <?php $idxEq++; endforeach;
      foreach ($equipsLeves as $eq):
      ?>
      <div class="card-mini">
        <div class="ch"><span>🚗 <?= htmlspecialchars($eq['tipo']) ?> <?= htmlspecialchars($eq['modelo']) ?></span></div>
        <input type="hidden" name="equip_id[<?= $idxEq ?>]"   value="<?= (int)$eq['id'] ?>">
        <input type="hidden" name="equip_tipo[<?= $idxEq ?>]" value="leve">
        <div class="row2" style="margin-top:8px">
          <select name="equip_func[<?= $idxEq ?>]">
            <option value="1">✅ Funcionando</option>
            <option value="0">❌ Com problema</option>
          </select>
          <div class="fotos" id="fotos-eq-<?= $idxEq ?>">
            <label class="cam" title="Foto">
              <span class="cic">📷</span>
              <input type="file" accept="image/*" capture="environment" style="display:none"
                     onchange="handleFotoEq(this, <?= $diarioId ?>, <?= $idxEq ?>)">
              <input type="hidden" name="equip_foto[<?= $idxEq ?>]" id="foto-eq-<?= $idxEq ?>">
            </label>
          </div>
        </div>
      </div>
      <?php $idxEq++; endforeach; ?>
      <button type="button" class="btn-step-ok" onclick="salvarStep(document.getElementById('form-step-6'), <?= $diarioId ?>, 6)">✔ Confirmar equipamentos</button>
    </form>
    <?php else: ?><div class="hint">Passo registrado.</div><?php endif; ?>
  </div>
</div>

<!-- Passos 7–10: Fotos de obras -->
<?php
$obraSteps = [
  7  => ['Corte de asfalto',    'Fotos do corte (só se houver asfalto)'],
  8  => ['Retirada de pavimento','Fotos da remoção do pavimento'],
  9  => ['Escavação',            'Fotos da escavação'],
  10 => ['Escoramento',          'Fotos do escoramento da vala'],
];
foreach ($obraSteps as $sn => [$titulo, $desc]):
?>
<div class="<?= stepClass($sn, $stepAtual) ?>" data-step="<?= $sn ?>">
  <div class="step-h">
    <span class="n"><?= $sn ?></span>
    <div class="tt"><b><?= $titulo ?></b><span><?= $desc ?></span></div>
    <span class="ck"><?= isFeito($sn, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <div class="fotos" id="fotos-<?= $sn ?>">
      <?php foreach ($fotosStep[$sn] ?? [] as $foto): ?>
      <div class="foto"><img src="<?= EXECUTOR_BASE ?>/uploads/<?= htmlspecialchars($foto['arquivo']) ?>" alt=""><?php if ($foto['lat']): ?><span class="gpsb">GPS</span><?php endif; ?></div>
      <?php endforeach; ?>
      <?php if (!$bloqueado): ?>
      <label class="cam">
        <span class="cic">📷</span>Foto
        <input type="file" accept="image/*" capture="environment" style="display:none"
               onchange="handleFotoUpload(this, <?= $diarioId ?>, <?= $sn ?>, '<?= $titulo ?>', <?= $sn ?>)">
      </label>
      <?php endif; ?>
    </div>
    <?php if (!$bloqueado): ?>
    <button type="button" class="btn-step-ok" onclick="marcarStepFeito(<?= $sn ?>)">✔ Fotos ok</button>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<!-- Passo 11: Interferências -->
<div class="<?= stepClass(11, $stepAtual) ?>" data-step="11">
  <div class="step-h">
    <span class="n">11</span>
    <div class="tt"><b>Interferências</b><span>Foto + tipo + GPS por interferência</span></div>
    <span class="ck"><?= isFeito(11, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <?php if (!$bloqueado): ?>
    <form id="form-step-11" onsubmit="return false">
      <div id="lista-interf">
        <?php foreach ($interferencias as $idx => $interf): ?>
        <div class="card-mini">
          <select name="interf_tipo[<?= $idx ?>]">
            <?php foreach (['pedra','agua_na_vala','ramal_de_agua','rede_de_agua','rede_pluvial','rompimento_de_rede','rede_cloacal_existente','rede_logica','rede_eletrica','outros'] as $opt): ?>
            <option value="<?= $opt ?>" <?= $interf['tipo'] === $opt ? 'selected' : '' ?>><?= str_replace('_', ' ', ucfirst($opt)) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="text" name="interf_esp[<?= $idx ?>]" value="<?= htmlspecialchars($interf['especificacao'] ?? '') ?>" placeholder="Especificação" style="margin-top:6px">
          <input type="hidden" name="interf_lat[<?= $idx ?>]" id="lat-interf-<?= $idx ?>" value="<?= htmlspecialchars($interf['lat'] ?? '') ?>">
          <input type="hidden" name="interf_lng[<?= $idx ?>]" id="lng-interf-<?= $idx ?>" value="<?= htmlspecialchars($interf['lng'] ?? '') ?>">
          <div class="gps-chip <?= $interf['lat'] ? 'ok' : 'aguardando' ?>" id="gps-interf-<?= $idx ?>">
            <?= $interf['lat'] ? '📍 ' . round((float)$interf['lat'], 4) . ', ' . round((float)$interf['lng'], 4) : '📍 GPS não capturado' ?>
          </div>
          <?php if (!$interf['lat']): ?>
          <button type="button" onclick="capturarGPS(document.getElementById('lat-interf-<?= $idx ?>'),document.getElementById('lng-interf-<?= $idx ?>'),document.getElementById('gps-interf-<?= $idx ?>'))" style="margin-top:4px;width:100%;border:1px solid var(--line);background:var(--bg);border-radius:8px;padding:7px;font-size:12px;font-weight:700">📍 Capturar GPS</button>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="add-item" onclick="adicionarInterferencia()">+ Adicionar interferência</button>
      <button type="button" class="btn-step-ok" onclick="salvarStep(document.getElementById('form-step-11'), <?= $diarioId ?>, 11)">✔ Salvar interferências</button>
    </form>
    <?php else: ?>
    <div class="hint"><?= count($interferencias) ?> interferência(s) registrada(s).</div>
    <?php endif; ?>
  </div>
</div>

<!-- Passo 12: GPS início -->
<div class="<?= stepClass(12, $stepAtual) ?>" data-step="12">
  <div class="step-h">
    <span class="n">12</span>
    <div class="tt"><b>Posição de início (GPS)</b><span>Foto georreferenciada na direção do trecho</span></div>
    <span class="ck"><?= isFeito(12, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <?php if (!$bloqueado): ?>
    <form id="form-step-12" onsubmit="return false">
      <div class="gps-chip aguardando" id="gps-inicio-chip">📍 Aguardando GPS…</div>
      <input type="hidden" name="lat" id="lat-inicio" value="<?= htmlspecialchars($gps['lat_inicio'] ?? '') ?>">
      <input type="hidden" name="lng" id="lng-inicio" value="<?= htmlspecialchars($gps['lng_inicio'] ?? '') ?>">
      <button type="button" onclick="capturarGPS(document.getElementById('lat-inicio'), document.getElementById('lng-inicio'), document.getElementById('gps-inicio-chip'))" style="margin:8px 0;width:100%;border:1px solid var(--line);background:var(--bg);border-radius:8px;padding:10px;font-size:13px;font-weight:700">📍 Capturar posição de início</button>
      <div class="fotos" id="fotos-12">
        <?php foreach ($fotosStep[12] ?? [] as $foto): ?>
        <div class="foto"><img src="<?= EXECUTOR_BASE ?>/uploads/<?= htmlspecialchars($foto['arquivo']) ?>" alt=""><?php if ($foto['lat']): ?><span class="gpsb">GPS</span><?php endif; ?></div>
        <?php endforeach; ?>
        <label class="cam">
          <span class="cic">📷</span>Foto início
          <input type="file" accept="image/*" capture="environment" style="display:none"
                 onchange="handleFotoUpload(this, <?= $diarioId ?>, 12, 'inicio', 12)">
        </label>
      </div>
      <button type="button" class="btn-step-ok" id="btn-gps-step-12" onclick="salvarStep(document.getElementById('form-step-12'), <?= $diarioId ?>, 12)">✔ Confirmar início</button>
    </form>
    <?php else: ?>
    <div class="gps-chip"><?= $gps ? '📍 ' . $gps['lat_inicio'] . ', ' . $gps['lng_inicio'] : 'Não capturado' ?></div>
    <?php endif; ?>
  </div>
</div>

<!-- Passo 13: GPS fim + extensão -->
<div class="<?= stepClass(13, $stepAtual) ?>" data-step="13">
  <div class="step-h">
    <span class="n">13</span>
    <div class="tt"><b>Posição final (GPS)</b><span>Foto sobre o último tubo → calcula extensão</span></div>
    <span class="ck"><?= isFeito(13, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <?php if (!$bloqueado): ?>
    <form id="form-step-13" onsubmit="return false">
      <div class="gps-chip aguardando" id="gps-fim-chip">📍 Aguardando GPS…</div>
      <input type="hidden" name="lat" id="lat-fim" value="<?= htmlspecialchars($gps['lat_fim'] ?? '') ?>">
      <input type="hidden" name="lng" id="lng-fim" value="<?= htmlspecialchars($gps['lng_fim'] ?? '') ?>">
      <button type="button" onclick="capturarGPS(document.getElementById('lat-fim'), document.getElementById('lng-fim'), document.getElementById('gps-fim-chip'))" style="margin:8px 0;width:100%;border:1px solid var(--line);background:var(--bg);border-radius:8px;padding:10px;font-size:13px;font-weight:700">📍 Capturar posição final</button>
      <?php if ($gps && $gps['extensao_calculada_m']): ?>
      <div class="calc">Extensão executada hoje: <b><?= number_format($gps['extensao_calculada_m'], 1, ',', '.') ?> m</b></div>
      <?php else: ?>
      <div class="calc" id="calc-extensao" style="display:none">Extensão calculada: <b id="val-extensao">—</b></div>
      <?php endif; ?>
      <div class="fotos" id="fotos-13">
        <?php foreach ($fotosStep[13] ?? [] as $foto): ?>
        <div class="foto"><img src="<?= EXECUTOR_BASE ?>/uploads/<?= htmlspecialchars($foto['arquivo']) ?>" alt=""><?php if ($foto['lat']): ?><span class="gpsb">GPS</span><?php endif; ?></div>
        <?php endforeach; ?>
        <label class="cam">
          <span class="cic">📷</span>Foto fim
          <input type="file" accept="image/*" capture="environment" style="display:none"
                 onchange="handleFotoUpload(this, <?= $diarioId ?>, 13, 'fim', 13)">
        </label>
      </div>
      <button type="button" class="btn-step-ok" id="btn-gps-step-13" onclick="salvarStep(document.getElementById('form-step-13'), <?= $diarioId ?>, 13)">✔ Confirmar posição final</button>
    </form>
    <?php else: ?>
    <?php if ($gps && $gps['extensao_calculada_m']): ?>
    <div class="calc">Extensão executada: <b><?= number_format($gps['extensao_calculada_m'], 1, ',', '.') ?> m</b></div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Passo 14: Pontões de espera -->
<div class="<?= stepClass(14, $stepAtual) ?>" data-step="14">
  <div class="step-h">
    <span class="n">14</span>
    <div class="tt"><b>Pontões de espera</b><span>Foto + nº residência</span></div>
    <span class="ck"><?= isFeito(14, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <?php if (!$bloqueado): ?>
    <form id="form-step-14" onsubmit="return false">
      <div id="lista-pontoes">
        <?php foreach ($pontoes as $pi => $p): ?>
        <div class="card-mini">
          <input type="text" name="pontao_res[<?= $pi ?>]" value="<?= htmlspecialchars($p['nro_residencia'] ?? '') ?>" placeholder="Nº da residência">
          <div class="fotos" id="fotos-pontao-<?= $pi ?>" style="margin-top:6px">
            <label class="cam"><span class="cic">📷</span><input type="file" accept="image/*" capture="environment" style="display:none" onchange="handleFotoUpload(this, <?= $diarioId ?>, 14, 'pontao', 14)"></label>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="add-item" onclick="adicionarPontao()">+ Adicionar pontão</button>
      <button type="button" class="btn-step-ok" onclick="salvarStep(document.getElementById('form-step-14'), <?= $diarioId ?>, 14)">✔ Salvar pontões</button>
    </form>
    <?php else: ?>
    <div class="hint"><?= count($pontoes) ?> pontão(ões) registrado(s).</div>
    <?php endif; ?>
  </div>
</div>

<!-- Passos 15–16: Cargas -->
<?php
$cargaSteps = [
  15 => ['Cargas bota-fora / bota-espera', 'bota_fora', 'Foto de cada carga — numeradas'],
  16 => ['Cargas de material importado',   'importado', 'Foto de cada carga importada'],
];
foreach ($cargaSteps as $sn => [$titulo, $tipo, $desc]):
  $cargasFiltradas = array_filter($cargas, fn($c) => $c['tipo'] === $tipo);
?>
<div class="<?= stepClass($sn, $stepAtual) ?>" data-step="<?= $sn ?>">
  <div class="step-h">
    <span class="n"><?= $sn ?></span>
    <div class="tt"><b><?= $titulo ?></b><span><?= $desc ?></span></div>
    <span class="ck"><?= isFeito($sn, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <?php if (!$bloqueado): ?>
    <form id="form-step-<?= $sn ?>" onsubmit="return false">
      <div class="fotos" id="fotos-carga-<?= $sn ?>">
        <?php foreach ($cargasFiltradas as $c): ?>
        <div class="foto">
          <?php if ($c['foto_id'] && !empty($fotosStep[14])): ?>
          <img src="<?= EXECUTOR_BASE ?>/uploads/<?= htmlspecialchars($c['foto_id']) ?>" alt="">
          <?php else: ?><span style="font-size:20px">📦</span><?php endif; ?>
          <div style="position:absolute;bottom:3px;left:0;right:0;text-align:center;font-size:8px;font-weight:800;color:#fff;background:#00000066;padding:1px">Carga <?= (int)$c['numero'] ?></div>
        </div>
        <?php endforeach; ?>
        <label class="cam">
          <span class="cic">📷</span>+ Carga
          <input type="file" accept="image/*" capture="environment" style="display:none"
                 onchange="adicionarCargaFoto(this, <?= $diarioId ?>, <?= $sn ?>)">
        </label>
      </div>
      <button type="button" class="btn-step-ok" onclick="salvarStep(document.getElementById('form-step-<?= $sn ?>'), <?= $diarioId ?>, <?= $sn ?>)">✔ Confirmar cargas</button>
    </form>
    <?php else: ?>
    <div class="hint"><?= count($cargasFiltradas) ?> carga(s) registrada(s).</div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<!-- Passo 17: Reaterros -->
<div class="<?= stepClass(17, $stepAtual) ?>" data-step="17">
  <div class="step-h">
    <span class="n">17</span>
    <div class="tt"><b>Camadas de reaterro</b><span>Tipo + espessura + foto</span></div>
    <span class="ck"><?= isFeito(17, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <?php if (!$bloqueado): ?>
    <form id="form-step-17" onsubmit="return false">
      <div id="lista-reaterros">
        <?php foreach ($reaterros as $ri => $r): ?>
        <div class="card-mini">
          <select name="reat_tipo[<?= $ri ?>]">
            <?php foreach (['lastro_brita'=>'Lastro de brita','colchao_areia_po_brita'=>'Colchão areia/pó de brita','reaterro_importado'=>'Reaterro importado','compactacao_importado'=>'Compactação importado','reaterro_local'=>'Reaterro local','compactacao_local'=>'Compactação local','base_brita_graduada'=>'Base brita graduada','compactacao_base'=>'Compactação base'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= $r['tipo'] === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
          <div class="row2">
            <input type="number" step="0.5" name="reat_esp[<?= $ri ?>]" value="<?= htmlspecialchars($r['espessura_cm'] ?? '') ?>" placeholder="Espessura (cm)">
            <div class="fotos" id="fotos-reat-<?= $ri ?>">
              <label class="cam"><span class="cic">📷</span><input type="file" accept="image/*" capture="environment" style="display:none" onchange="handleFotoUpload(this, <?= $diarioId ?>, 17, 'reaterro', 17)"></label>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="add-item" onclick="adicionarReaterro()">+ Adicionar camada</button>
      <button type="button" class="btn-step-ok" onclick="salvarStep(document.getElementById('form-step-17'), <?= $diarioId ?>, 17)">✔ Salvar reaterros</button>
    </form>
    <?php else: ?><div class="hint"><?= count($reaterros) ?> camada(s) registrada(s).</div><?php endif; ?>
  </div>
</div>

<!-- Passo 18: Ramais -->
<div class="<?= stepClass(18, $stepAtual) ?>" data-step="18">
  <div class="step-h">
    <span class="n">18</span>
    <div class="tt"><b>Ramais executados</b><span>Dimensão pontão, extensões, nº residência</span></div>
    <span class="ck"><?= isFeito(18, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <?php if (!$bloqueado): ?>
    <form id="form-step-18" onsubmit="return false">
      <div id="lista-ramais">
        <?php foreach ($ramais as $ri => $r): ?>
        <div class="card-mini">
          <input type="text" name="ramal_nro[<?= $ri ?>]" value="<?= htmlspecialchars($r['nro_residencia'] ?? '') ?>" placeholder="Nº residência">
          <div class="row2" style="margin-top:6px">
            <input type="text" name="ramal_pontao[<?= $ri ?>]" value="<?= htmlspecialchars($r['dimensao_pontao'] ?? '') ?>" placeholder="Dim. pontão">
            <input type="number" step="0.1" name="ramal_pista[<?= $ri ?>]" value="<?= htmlspecialchars($r['ext_pista'] ?? '') ?>" placeholder="Ext. pista (m)">
          </div>
          <input type="number" step="0.1" name="ramal_calcada[<?= $ri ?>]" value="<?= htmlspecialchars($r['ext_calcada'] ?? '') ?>" placeholder="Ext. calçada (m)" style="margin-top:6px">
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="add-item" onclick="adicionarRamal()">+ Adicionar ramal</button>
      <button type="button" class="btn-step-ok" onclick="salvarStep(document.getElementById('form-step-18'), <?= $diarioId ?>, 18)">✔ Salvar ramais</button>
    </form>
    <?php else: ?><div class="hint"><?= count($ramais) ?> ramal(is) registrado(s).</div><?php endif; ?>
  </div>
</div>

<!-- Passos 19–20: Fotos finais -->
<?php
$finSteps = [
  19 => ['Rua limpa', 'Foto da rua após execução'],
  20 => ['Equipe final + equipamentos', 'Foto da equipe no encerramento'],
];
foreach ($finSteps as $sn => [$titulo, $desc]):
?>
<div class="<?= stepClass($sn, $stepAtual) ?>" data-step="<?= $sn ?>">
  <div class="step-h">
    <span class="n"><?= $sn ?></span>
    <div class="tt"><b><?= $titulo ?></b><span><?= $desc ?></span></div>
    <span class="ck"><?= isFeito($sn, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <div class="fotos" id="fotos-<?= $sn ?>">
      <?php foreach ($fotosStep[$sn] ?? [] as $foto): ?>
      <div class="foto"><img src="<?= EXECUTOR_BASE ?>/uploads/<?= htmlspecialchars($foto['arquivo']) ?>" alt=""><?php if ($foto['lat']): ?><span class="gpsb">GPS</span><?php endif; ?></div>
      <?php endforeach; ?>
      <?php if (!$bloqueado): ?>
      <label class="cam"><span class="cic">📷</span>Foto<input type="file" accept="image/*" capture="environment" style="display:none" onchange="handleFotoUpload(this, <?= $diarioId ?>, <?= $sn ?>, '<?= $titulo ?>', <?= $sn ?>)"></label>
      <?php endif; ?>
    </div>
    <?php if (!$bloqueado): ?>
    <button type="button" class="btn-step-ok" onclick="marcarStepFeito(<?= $sn ?>)">✔ Fotos ok</button>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<!-- Passo 21: Finalização -->
<div class="<?= stepClass(21, $stepAtual) ?>" data-step="21">
  <div class="step-h">
    <span class="n">21</span>
    <div class="tt"><b>Finalização</b><span>Confirmar e habilitar envio</span></div>
    <span class="ck"><?= isFeito(21, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <?php if (!$bloqueado): ?>
    <div class="hint">Revise os passos acima. Ao encerrar, o diário é enviado ao Planejador e não poderá ser editado (reabertura gera nova versão).</div>
    <button type="button" class="btn-step-ok" onclick="marcarStepFeito(21)">✔ Tudo certo — pronto para enviar</button>
    <?php else: ?>
    <div class="info" style="border-color:var(--ok)">
      <div class="info-h"><span class="ic i-ok">✅</span><div><b>Diário enviado com sucesso</b></div></div>
    </div>
    <?php endif; ?>
  </div>
</div>

</div><!-- /scroll -->

<!-- Rodapé -->
<div class="footer">
  <div class="resumo">
    <b id="prog-pct-footer"><?= $pct ?>%</b> preenchido<br>
    <span id="conn-badge">🟢 Online</span>
  </div>
  <?php if (!$bloqueado): ?>
  <div style="margin-left:auto;display:flex;flex-direction:column;align-items:flex-end;gap:4px">
    <form method="post" action="<?= EXECUTOR_BASE ?>/diario/<?= $diarioId ?>/encerrar" id="form-encerrar" onsubmit="return confirmarEnvio()">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
      <button type="submit" class="btn-encerrar" id="btn-encerrar" disabled>Encerrar & enviar 🚀</button>
    </form>
    <span id="hint-encerrar" style="font-size:10.5px;color:#9FB4D6;text-align:right">Confirme o passo 21 para habilitar o envio</span>
  </div>
  <?php else: ?>
  <a href="<?= EXECUTOR_BASE ?>/" class="btn-sair" style="margin-left:auto">← Início</a>
  <?php endif; ?>
</div>

</div><!-- /phone -->

<script src="<?= EXECUTOR_BASE ?>/assets/js/executor.js"></script>
<script>
const DIARIO_ID = <?= $diarioId ?>;

function verificarEncerramento() {
  const ok = document.querySelector('[data-step="21"].feito');
  const btn  = document.getElementById('btn-encerrar');
  const hint = document.getElementById('hint-encerrar');
  if (!btn) return;
  btn.disabled = !ok;
  if (hint) hint.style.display = ok ? 'none' : 'block';
}
document.addEventListener('DOMContentLoaded', verificarEncerramento);

function confirmarEnvio() {
  const total = 21;
  const feitos = document.querySelectorAll('[data-step].feito').length;
  if (feitos >= total) return confirm('Encerrar e enviar o diário? Esta ação não pode ser desfeita.');
  const faltam = total - feitos;
  const passosFaltando = [];
  for (let s = 1; s <= total; s++) {
    if (!document.querySelector('[data-step="'+s+'"].feito')) passosFaltando.push(s);
  }
  return confirm(
    'Atenção: ' + faltam + ' passo(s) não foram confirmados: ' + passosFaltando.join(', ') + '.\n\n' +
    'Enviar mesmo assim? O Planejador verá o diário como incompleto.'
  );
}

// Override de marcarStepFeito para verificar encerramento
const _marcarOrig = window.marcarStepFeito;
window.marcarStepFeito = function(step) {
  _marcarOrig(step);
  verificarEncerramento();
};

// Salvar presença do passo 1 — envia presenca[ID]=status direto (backend lê $_POST['presenca'])
async function salvarPresencasStep1(diarioId) {
  await salvarStep(document.getElementById('form-step-1'), diarioId, 1);
}

async function salvarStepSimples(diarioId, step, formId) {
  await salvarStep(document.getElementById(formId), diarioId, step);
}

function toggleFaltasMat() {
  const v = document.getElementById('estoque-ok-sel').value;
  document.getElementById('faltas-mat').style.display = v === '0' ? 'block' : 'none';
}

// Upload helper
async function handleFotoUpload(input, diarioId, step, tipo, stepNum) {
  const fotoId = await uploadFoto(input, diarioId, step, tipo);
  if (fotoId) {
    const hidInput = document.createElement('input');
    hidInput.type = 'hidden'; hidInput.name = 'foto_id'; hidInput.value = fotoId;
    input.closest('.step-body').appendChild(hidInput);
  }
}

async function handleFotoEq(input, diarioId, idx) {
  const fotoId = await uploadFoto(input, diarioId, 6, 'equipamento');
  if (fotoId) { document.getElementById('foto-eq-' + idx).value = fotoId; }
}

async function adicionarCargaFoto(input, diarioId, step) {
  const fotoId = await uploadFoto(input, diarioId, step, 'carga');
  const container = input.closest('.fotos');
  const hidInput = document.createElement('input');
  hidInput.type = 'hidden'; hidInput.name = 'carga_foto[]'; hidInput.value = fotoId || '';
  container.appendChild(hidInput);
}

// Adicionar interferência dinâmica
let interfIdx = <?= count($interferencias) ?>;
function adicionarInterferencia() {
  const li = document.getElementById('lista-interf');
  const div = document.createElement('div'); div.className = 'card-mini';
  div.innerHTML = `
    <select name="interf_tipo[${interfIdx}]">
      <option value="pedra">Pedra</option><option value="agua_na_vala">Água na vala</option>
      <option value="ramal_de_agua">Ramal de água</option><option value="rede_de_agua">Rede de água</option>
      <option value="rede_pluvial">Rede pluvial</option><option value="rompimento_de_rede">Rompimento de rede</option>
      <option value="rede_cloacal_existente">Rede cloacal existente</option>
      <option value="rede_logica">Rede lógica</option><option value="rede_eletrica">Rede elétrica</option>
      <option value="outros">Outros</option>
    </select>
    <input type="text" name="interf_esp[${interfIdx}]" placeholder="Especificação" style="margin-top:6px">
    <div class="gps-chip aguardando" id="gps-interf-${interfIdx}">📍 GPS não capturado</div>
    <input type="hidden" name="interf_lat[${interfIdx}]" id="lat-interf-${interfIdx}">
    <input type="hidden" name="interf_lng[${interfIdx}]" id="lng-interf-${interfIdx}">
    <button type="button" onclick="capturarGPS(document.getElementById('lat-interf-${interfIdx}'),document.getElementById('lng-interf-${interfIdx}'),document.getElementById('gps-interf-${interfIdx}'))" style="margin-top:6px;width:100%;border:1px solid var(--line);background:var(--bg);border-radius:8px;padding:8px;font-size:12px;font-weight:700">📍 Capturar GPS</button>
  `;
  li.appendChild(div);
  interfIdx++;
}

let pontaoIdx = <?= count($pontoes) ?>;
function adicionarPontao() {
  const li = document.getElementById('lista-pontoes');
  const div = document.createElement('div'); div.className = 'card-mini';
  div.innerHTML = `<input type="text" name="pontao_res[${pontaoIdx}]" placeholder="Nº da residência">
    <div class="fotos" id="fotos-pontao-${pontaoIdx}" style="margin-top:6px">
      <label class="cam"><span class="cic">📷</span><input type="file" accept="image/*" capture="environment" style="display:none" onchange="handleFotoUpload(this, ${DIARIO_ID}, 14, 'pontao', 14)"></label>
    </div>`;
  li.appendChild(div); pontaoIdx++;
}

let reaterroIdx = <?= count($reaterros) ?>;
function adicionarReaterro() {
  const li = document.getElementById('lista-reaterros');
  const div = document.createElement('div'); div.className = 'card-mini';
  div.innerHTML = `
    <select name="reat_tipo[${reaterroIdx}]">
      <option value="lastro_brita">Lastro de brita</option><option value="colchao_areia_po_brita">Colchão areia/pó de brita</option>
      <option value="reaterro_importado">Reaterro importado</option><option value="compactacao_importado">Compactação importado</option>
      <option value="reaterro_local">Reaterro local</option><option value="compactacao_local">Compactação local</option>
      <option value="base_brita_graduada">Base brita graduada</option><option value="compactacao_base">Compactação base</option>
    </select>
    <div class="row2">
      <input type="number" step="0.5" name="reat_esp[${reaterroIdx}]" placeholder="Espessura (cm)">
      <div class="fotos" id="fotos-reat-${reaterroIdx}">
        <label class="cam"><span class="cic">📷</span><input type="file" accept="image/*" capture="environment" style="display:none" onchange="handleFotoUpload(this, ${DIARIO_ID}, 17, 'reaterro', 17)"></label>
      </div>
    </div>
  `;
  li.appendChild(div); reaterroIdx++;
}

let ramalIdx = <?= count($ramais) ?>;
function adicionarRamal() {
  const li = document.getElementById('lista-ramais');
  const div = document.createElement('div'); div.className = 'card-mini';
  div.innerHTML = `
    <input type="text" name="ramal_nro[${ramalIdx}]" placeholder="Nº residência">
    <div class="row2" style="margin-top:6px">
      <input type="text" name="ramal_pontao[${ramalIdx}]" placeholder="Dim. pontão">
      <input type="number" step="0.1" name="ramal_pista[${ramalIdx}]" placeholder="Ext. pista (m)">
    </div>
    <input type="number" step="0.1" name="ramal_calcada[${ramalIdx}]" placeholder="Ext. calçada (m)" style="margin-top:6px">
  `;
  li.appendChild(div); ramalIdx++;
}

iniciarAcordeao(DIARIO_ID);
iniciarAutoSave(DIARIO_ID);
</script>
</body>
</html>
