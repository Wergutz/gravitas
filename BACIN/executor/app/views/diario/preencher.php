<?php
header('X-Robots-Tag: noindex, nofollow');
$stepAtual = (int)$diario['step_atual'];
$totalSteps = 21;
$pct = (int)round($stepAtual / $totalSteps * 100);
$diarioId = (int)$diario['id'];
$bloqueado = $diario['status'] === 'enviado';
$redeConcluida = (($trecho['status_rede'] ?? '') === 'concluido');
$nomeTrecho = trim(($trecho['pv_montante'] ?? '') . ' → ' . ($trecho['pv_jusante'] ?? ''));

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
<meta name="csrf" content="<?= htmlspecialchars(csrf_token_executor()) ?>">
<title>Diário <?= date('d/m/Y', strtotime($diario['data'])) ?> · BACIN</title>
<link rel="stylesheet" href="<?= EXECUTOR_BASE ?>/assets/css/executor.css">
</head>
<body>
<div class="phone">

<!-- Topo -->
<div class="top">
  <div class="top-row">
    <img class="logo" src="/BACIN/painel/assets/img/icon-bacin-white.svg?v=2" alt="BACIN">
    <div class="nm">BACIN<small>DIÁRIO <?= date('d/m', strtotime($diario['data'])) ?></small></div>
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

<?php if (!empty($devolucao)): ?>
<!-- Devolução do Planejador (PA26) — aparece no topo do diário -->
<?= devolucao_aviso_html(
        $devolucao,
        'O escritório devolveu este trecho: a rede tem que ser refeita.',
        'Lance aqui o serviço refeito. Só marque "rede concluída" quando estiver resolvido.'
    ) ?>
<?php endif; ?>

<?php if ($bloqueado): ?>
<div class="info" style="border-color:<?= $redeConcluida ? 'var(--ok)' : 'var(--aviso)' ?>">
  <div class="info-h">
    <span class="ic <?= $redeConcluida ? 'i-ok' : 'i-aviso' ?>"><?= $redeConcluida ? '✅' : '🔧' ?></span>
    <div>
      <b>Diário enviado</b>
      <span>Este diário já foi enviado ao Planejador e não pode ser editado.</span>
    </div>
  </div>
  <div style="margin-top:9px">
    <?php if ($redeConcluida): ?>
    <span class="badge b-ok">✅ Rede concluída<?= !empty($trecho['rede_concluida_em']) ? ' em ' . date('d/m/Y', strtotime($trecho['rede_concluida_em'])) : '' ?></span>
    <div class="hint">Ramais e pavimento já foram liberados neste trecho. Não há nada a concluir de novo.</div>
    <?php else: ?>
    <span class="badge b-aviso">🔧 Trecho continua — rede não concluída</span>
    <div class="hint">Se a rede de <?= htmlspecialchars($nomeTrecho) ?> terminou, marque abaixo: isso libera a equipe de ramais e a de pavimento.</div>
    <form method="post" action="<?= EXECUTOR_BASE ?>/diario/<?= $diarioId ?>/encerrar"
          onsubmit="return confirm('Confirmar que a REDE do trecho <?= htmlspecialchars($nomeTrecho, ENT_QUOTES) ?> está CONCLUÍDA?\n\nIsso libera a equipe de ramais e a de pavimento.')">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token_executor()) ?>">
      <input type="hidden" name="rede_concluida" value="1">
      <button type="submit" class="btn-step-ok">✅ Terminei o trecho — rede concluída</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token_executor()) ?>">

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
          <input type="hidden" name="interf_foto[<?= $idx ?>]" id="foto-interf-<?= $idx ?>" value="<?= !empty($interf['foto_id']) ? (int)$interf['foto_id'] : '' ?>">
          <input type="hidden" name="interf_remover[<?= $idx ?>]" id="rm-interf-<?= $idx ?>" value="">
          <input type="hidden" name="interf_lat[<?= $idx ?>]" id="lat-interf-<?= $idx ?>" value="<?= htmlspecialchars($interf['lat'] ?? '') ?>">
          <input type="hidden" name="interf_lng[<?= $idx ?>]" id="lng-interf-<?= $idx ?>" value="<?= htmlspecialchars($interf['lng'] ?? '') ?>">
          <div class="gps-chip <?= $interf['lat'] ? 'ok' : 'aguardando' ?>" id="gps-interf-<?= $idx ?>">
            <?= $interf['lat'] ? '📍 ' . round((float)$interf['lat'], 4) . ', ' . round((float)$interf['lng'], 4) : '📍 GPS não capturado' ?>
          </div>
          <?php if (!$interf['lat']): ?>
          <button type="button" onclick="capturarGPS(document.getElementById('lat-interf-<?= $idx ?>'),document.getElementById('lng-interf-<?= $idx ?>'),document.getElementById('gps-interf-<?= $idx ?>'))" style="margin-top:4px;width:100%;border:1px solid var(--line);background:var(--bg);border-radius:8px;padding:7px;font-size:12px;font-weight:700">📍 Capturar GPS</button>
          <?php endif; ?>
          <button type="button" class="btn-remover" onclick="removerItem(this, 'rm-interf-<?= $idx ?>')">🗑 Remover esta interferência</button>
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

<!-- Passo 14: Pontões de ramal (espera) — registro oficial do pontão -->
<div class="<?= stepClass(14, $stepAtual) ?>" data-step="14">
  <div class="step-h">
    <span class="n">14</span>
    <div class="tt"><b>Pontões de ramal (espera)</b><span>Lançamento da rede até a cota do ramal (padrão 0,80 m)</span></div>
    <span class="ck"><?= isFeito(14, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <?php if (!$bloqueado): ?>
    <div class="hint">Registre o pontão de espera: nº do imóvel (obrigatório), profundidade da cota de lançamento (entre 0,40 m e 4,00 m), foto e GPS. A extensão do ramal em pista e calçada é lançada depois pela equipe de ramais. Salvar com a tela vazia NÃO apaga os pontões já lançados — para tirar um, use o 🗑 Remover dele.</div>
    <form id="form-step-14" onsubmit="return false">
      <div id="lista-pontoes">
        <?php foreach ($pontoes as $pi => $p):
          // Profundidade NULL aparece VAZIA (antes voltava como 0,80 e virava 0,80 de verdade)
          $pProf = ($p['profundidade_m'] !== null && $p['profundidade_m'] !== '') ? (string)$p['profundidade_m'] : '';
          $pLat  = $p['lat'] ?? '';
          $pLng  = $p['lng'] ?? '';
        ?>
        <div class="card-mini">
          <input type="text" name="pontao_res[<?= $pi ?>]" value="<?= htmlspecialchars($p['nro_residencia'] ?? '') ?>" placeholder="Nº do imóvel">
          <div class="row2">
            <input type="number" inputmode="decimal" step="0.01" min="0.40" max="4.00"
                   name="pontao_prof[<?= $pi ?>]" value="<?= htmlspecialchars($pProf) ?>"
                   placeholder="Profundidade 0,40 a 4,00 m">
            <div class="fotos" id="fotos-pontao-<?= $pi ?>">
              <?php if (!empty($p['foto_thumb'])): ?>
              <div class="foto"><img src="<?= EXECUTOR_BASE ?>/uploads/<?= htmlspecialchars($p['foto_thumb']) ?>" alt=""><?php if ($pLat): ?><span class="gpsb">GPS</span><?php endif; ?></div>
              <?php endif; ?>
              <label class="cam"><span class="cic">📷</span><input type="file" accept="image/*" capture="environment" style="display:none" onchange="handleFotoUpload(this, <?= $diarioId ?>, 14, 'pontao', 14, 'foto-pontao-<?= $pi ?>')"></label>
            </div>
          </div>
          <input type="hidden" name="pontao_foto[<?= $pi ?>]" id="foto-pontao-<?= $pi ?>" value="<?= !empty($p['foto_id']) ? (int)$p['foto_id'] : '' ?>">
          <input type="text" name="pontao_obs[<?= $pi ?>]" maxlength="255" value="<?= htmlspecialchars($p['observacao'] ?? '') ?>" placeholder="Observação (opcional)" style="margin-top:6px">
          <input type="hidden" name="pontao_lat[<?= $pi ?>]" id="lat-pontao-<?= $pi ?>" value="<?= htmlspecialchars((string)$pLat) ?>">
          <input type="hidden" name="pontao_lng[<?= $pi ?>]" id="lng-pontao-<?= $pi ?>" value="<?= htmlspecialchars((string)$pLng) ?>">
          <div class="gps-chip <?= $pLat ? '' : 'aguardando' ?>" id="gps-pontao-<?= $pi ?>" style="margin-top:6px">
            <?= $pLat ? '📍 ' . htmlspecialchars(round((float)$pLat, 5) . ', ' . round((float)$pLng, 5)) : '📍 GPS não capturado' ?>
          </div>
          <button type="button" onclick="capturarGPS(document.getElementById('lat-pontao-<?= $pi ?>'),document.getElementById('lng-pontao-<?= $pi ?>'),document.getElementById('gps-pontao-<?= $pi ?>'))" style="margin-top:6px;width:100%;border:1px solid var(--line);background:var(--bg);border-radius:8px;padding:8px;font-size:12px;font-weight:700">📍 Capturar GPS do pontão</button>
          <input type="hidden" name="pontao_remover[<?= $pi ?>]" id="rm-pontao-<?= $pi ?>" value="">
          <button type="button" class="btn-remover" onclick="removerItem(this, 'rm-pontao-<?= $pi ?>')">🗑 Remover este pontão</button>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="add-item" onclick="adicionarPontao()">+ Adicionar pontão</button>
      <button type="button" class="btn-step-ok" onclick="salvarStep(document.getElementById('form-step-14'), <?= $diarioId ?>, 14)">✔ Salvar pontões</button>
    </form>
    <?php else: ?>
      <?php if (!$pontoes): ?>
      <div class="hint">Nenhum pontão registrado.</div>
      <?php else: ?>
        <?php foreach ($pontoes as $p): ?>
        <div class="mat-li">
          <span>🏠 <?= htmlspecialchars($p['nro_residencia'] ?? '—') ?><?= !empty($p['observacao']) ? ' · ' . htmlspecialchars($p['observacao']) : '' ?></span>
          <span class="q"><?= $p['profundidade_m'] !== null ? number_format((float)$p['profundidade_m'], 2, ',', '.') . ' m' : '—' ?></span>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
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
          <?php if (!empty($c['foto_thumb'])): ?>
          <img src="<?= EXECUTOR_BASE ?>/uploads/<?= htmlspecialchars($c['foto_thumb']) ?>" alt="">
          <?php else: ?><span style="font-size:20px">📦</span><?php endif; ?>
          <div style="position:absolute;bottom:3px;left:0;right:0;text-align:center;font-size:8px;font-weight:800;color:#fff;background:#00000066;padding:1px">Carga <?= (int)$c['numero'] ?></div>
          <?php if (!empty($c['foto_id'])): ?>
          <!-- devolve a carga já gravada para o POST: substituir o conjunto não perde nada -->
          <input type="hidden" name="carga_foto[]" value="<?= (int)$c['foto_id'] ?>">
          <?php endif; ?>
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
              <label class="cam"><span class="cic">📷</span><input type="file" accept="image/*" capture="environment" style="display:none" onchange="handleFotoUpload(this, <?= $diarioId ?>, 17, 'reaterro', 17, 'foto-reat-<?= $ri ?>')"></label>
            </div>
          </div>
          <input type="hidden" name="reat_foto[<?= $ri ?>]" id="foto-reat-<?= $ri ?>" value="<?= !empty($r['foto_id']) ? (int)$r['foto_id'] : '' ?>">
          <input type="hidden" name="reat_remover[<?= $ri ?>]" id="rm-reat-<?= $ri ?>" value="">
          <button type="button" class="btn-remover" onclick="removerItem(this, 'rm-reat-<?= $ri ?>')">🗑 Remover esta camada</button>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="add-item" onclick="adicionarReaterro()">+ Adicionar camada</button>
      <button type="button" class="btn-step-ok" onclick="salvarStep(document.getElementById('form-step-17'), <?= $diarioId ?>, 17)">✔ Salvar reaterros</button>
    </form>
    <?php else: ?><div class="hint"><?= count($reaterros) ?> camada(s) registrada(s).</div><?php endif; ?>
  </div>
</div>

<!-- Passo 18: ramal completo saiu do diário de rede (PA25) -->
<div class="<?= stepClass(18, $stepAtual) ?>" data-step="18">
  <div class="step-h">
    <span class="n">18</span>
    <div class="tt"><b>Ramais executados</b><span>Agora lançados pela equipe de ramais</span></div>
    <span class="ck"><?= isFeito(18, $stepAtual) ? '✅' : '○' ?></span>
    <span class="chev">▼</span>
  </div>
  <div class="step-body">
    <div class="info" style="margin-top:12px;margin-bottom:0;border-color:var(--aviso)">
      <div class="info-h">
        <span class="ic i-aviso">⚠️</span>
        <div><b>Ramal não é mais lançado aqui</b><span>Extensão em pista e calçada, tipo de pavimento e fotos do ramal são registrados depois pela equipe de ramais, no app próprio.</span></div>
      </div>
      <div class="corpo">Na rede você registra apenas o <b>pontão de espera</b>, no passo 14: nº do imóvel, profundidade da cota de lançamento, foto e GPS.</div>
    </div>
    <?php if ($ramais): ?>
    <div class="hint"><?= count($ramais) ?> ramal(is) lançado(s) neste diário antes da mudança — mantidos como histórico.</div>
    <?php endif; ?>
    <?php if (!$bloqueado): ?>
    <form id="form-step-18" onsubmit="return false">
      <button type="button" class="btn-step-ok" onclick="salvarStep(document.getElementById('form-step-18'), <?= $diarioId ?>, 18)">✔ Ciente, seguir</button>
    </form>
    <?php endif; ?>
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

<?php if (!$bloqueado): ?>
<!-- ================================================================
     ENCERRAMENTO — quem conclui a rede é quem executou (19/09/2026)
     ================================================================ -->
<div class="info" id="bloco-encerrar" style="border-color:var(--gold);border-width:2px">
  <div class="info-h">
    <span class="ic i-gold" style="font-size:18px">🏁</span>
    <div><b>Como ficou o trecho <?= htmlspecialchars($nomeTrecho) ?></b><span>Responda antes de enviar o diário</span></div>
  </div>
  <div>
    <div class="hint">Só quem está na obra sabe se a rede deste trecho terminou. Marcar <b>rede concluída</b> libera a equipe de ramais e a de pavimento para entrar aqui — por isso a pergunta vem no envio.</div>
    <form method="post" action="<?= EXECUTOR_BASE ?>/diario/<?= $diarioId ?>/encerrar" id="form-encerrar" onsubmit="return confirmarEnvio()">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token_executor()) ?>">

      <label class="card-mini" style="display:flex;gap:10px;align-items:flex-start">
        <input type="radio" name="rede_concluida" value="1" style="width:22px;height:22px;margin-top:2px" onchange="verificarEncerramento()">
        <span>
          <b style="font-size:14px">✅ Terminei o trecho — rede concluída</b>
          <span class="hint" style="display:block;margin-top:2px">A rede de <?= htmlspecialchars($nomeTrecho) ?> está pronta. Libera a equipe de ramais e a fila de repavimentação.</span>
        </span>
      </label>

      <label class="card-mini" style="display:flex;gap:10px;align-items:flex-start">
        <input type="radio" name="rede_concluida" value="0" style="width:22px;height:22px;margin-top:2px" onchange="verificarEncerramento()">
        <span>
          <b style="font-size:14px">🔧 Continua amanhã</b>
          <span class="hint" style="display:block;margin-top:2px">O trecho não terminou hoje. Ninguém entra atrás ainda; amanhã a equipe segue nele.</span>
        </span>
      </label>

      <button type="submit" class="btn-step-ok" id="btn-encerrar" disabled>Encerrar &amp; enviar 🚀</button>
      <div class="hint" id="hint-encerrar">Confirme o passo 21 e marque como o trecho ficou para liberar o envio.</div>
    </form>
  </div>
</div>
<?php endif; ?>

</div><!-- /scroll -->

<!-- Rodapé -->
<div class="footer">
  <div class="resumo">
    <b id="prog-pct-footer"><?= $pct ?>%</b> preenchido<br>
    <span id="conn-badge">🟢 Online</span>
  </div>
  <?php if (!$bloqueado): ?>
  <div style="margin-left:auto;display:flex;flex-direction:column;align-items:flex-end;gap:4px">
    <button type="button" class="btn-encerrar" onclick="irParaEncerramento()">Encerrar &amp; enviar 🚀</button>
    <span style="font-size:10.5px;color:var(--muted);text-align:right">Diga como o trecho ficou no fim da tela</span>
  </div>
  <?php else: ?>
  <a href="<?= EXECUTOR_BASE ?>/" class="btn-sair" style="margin-left:auto">← Início</a>
  <?php endif; ?>
</div>

</div><!-- /phone -->

<script src="<?= EXECUTOR_BASE ?>/assets/js/executor.js"></script>
<script>
const DIARIO_ID = <?= $diarioId ?>;

const NOME_TRECHO = <?= json_encode($nomeTrecho, JSON_UNESCAPED_UNICODE) ?>;

function escolhaRede() {
  return document.querySelector('input[name="rede_concluida"]:checked');
}

function verificarEncerramento() {
  const ok   = document.querySelector('[data-step="21"].feito');
  const btn  = document.getElementById('btn-encerrar');
  const hint = document.getElementById('hint-encerrar');
  if (!btn) return;
  const esc = escolhaRede();
  btn.disabled = !(ok && esc);
  if (esc) {
    btn.textContent = esc.value === '1'
      ? 'Encerrar — rede concluída ✅'
      : 'Encerrar — continua amanhã 🔧';
  }
  if (hint) {
    if (!ok)       hint.textContent = 'Confirme o passo 21 para liberar o envio.';
    else if (!esc) hint.textContent = 'Marque como o trecho ficou: rede concluída ou continua amanhã.';
    else           hint.textContent = esc.value === '1'
      ? 'Ao enviar, o trecho fica CONCLUÍDO e libera ramais e pavimento.'
      : 'Ao enviar, o trecho continua em execução com a sua equipe.';
  }
}
document.addEventListener('DOMContentLoaded', verificarEncerramento);

function irParaEncerramento() {
  const bloco = document.getElementById('bloco-encerrar');
  if (bloco) bloco.scrollIntoView({behavior: 'smooth', block: 'center'});
}

function removerItem(btn, hiddenId) {
  const hid = document.getElementById(hiddenId);
  const card = btn.closest('.card-mini');
  if (!hid || !card) return;
  if (!confirm('Tirar este item do diário? Ele sai quando você salvar o passo.')) return;
  hid.value = '1';
  card.style.display = 'none';
}

function confirmarEnvio() {
  const esc = escolhaRede();
  if (!esc) {
    alert('Antes de enviar, marque como o trecho ficou: "Terminei o trecho — rede concluída" ou "Continua amanhã".');
    return false;
  }
  const total = 21;
  const feitos = document.querySelectorAll('[data-step].feito').length;
  let aviso = '';
  if (feitos < total) {
    const passosFaltando = [];
    for (let s = 1; s <= total; s++) {
      if (!document.querySelector('[data-step="'+s+'"].feito')) passosFaltando.push(s);
    }
    aviso = 'Atenção: ' + (total - feitos) + ' passo(s) não confirmados: ' + passosFaltando.join(', ') +
            '. O Planejador verá o diário como incompleto.\n\n';
  }
  if (esc.value === '1') {
    return confirm(aviso +
      'Confirmar que a REDE do trecho ' + NOME_TRECHO + ' está CONCLUÍDA?\n\n' +
      'Isso envia o diário e libera a equipe de ramais e a de pavimento para entrar neste trecho.');
  }
  return confirm(aviso +
    'Enviar o diário? O trecho ' + NOME_TRECHO + ' CONTINUA — a rede não será marcada como concluída.');
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
async function handleFotoUpload(input, diarioId, step, tipo, stepNum, alvoId) {
  const fotoId = await uploadFoto(input, diarioId, step, tipo);
  if (fotoId) {
    // alvoId: hidden específico do item (ex.: foto do pontão do passo 14)
    if (alvoId) {
      const alvo = document.getElementById(alvoId);
      if (alvo) { alvo.value = fotoId; return; }
    }
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
    <input type="hidden" name="interf_foto[${interfIdx}]" id="foto-interf-${interfIdx}">
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
  const i  = pontaoIdx;
  const div = document.createElement('div'); div.className = 'card-mini';
  div.innerHTML = `<input type="text" name="pontao_res[${i}]" placeholder="Nº do imóvel">
    <div class="row2">
      <input type="number" inputmode="decimal" step="0.01" min="0" max="99.99" name="pontao_prof[${i}]" value="0.80" placeholder="Profundidade (m)">
      <div class="fotos" id="fotos-pontao-${i}">
        <label class="cam"><span class="cic">📷</span><input type="file" accept="image/*" capture="environment" style="display:none" onchange="handleFotoUpload(this, ${DIARIO_ID}, 14, 'pontao', 14, 'foto-pontao-${i}')"></label>
      </div>
    </div>
    <input type="hidden" name="pontao_foto[${i}]" id="foto-pontao-${i}">
    <input type="text" name="pontao_obs[${i}]" maxlength="255" placeholder="Observação (opcional)" style="margin-top:6px">
    <input type="hidden" name="pontao_lat[${i}]" id="lat-pontao-${i}">
    <input type="hidden" name="pontao_lng[${i}]" id="lng-pontao-${i}">
    <div class="gps-chip aguardando" id="gps-pontao-${i}" style="margin-top:6px">📍 GPS não capturado</div>
    <button type="button" onclick="capturarGPS(document.getElementById('lat-pontao-${i}'),document.getElementById('lng-pontao-${i}'),document.getElementById('gps-pontao-${i}'))" style="margin-top:6px;width:100%;border:1px solid var(--line);background:var(--bg);border-radius:8px;padding:8px;font-size:12px;font-weight:700">📍 Capturar GPS do pontão</button>`;
  li.appendChild(div);
  pontaoIdx++;
  // GPS já vai sendo capturado assim que o pontão é criado
  capturarGPS(document.getElementById('lat-pontao-' + i), document.getElementById('lng-pontao-' + i), document.getElementById('gps-pontao-' + i));
}

let reaterroIdx = <?= count($reaterros) ?>;
function adicionarReaterro() {
  const li = document.getElementById('lista-reaterros');
  const i  = reaterroIdx;
  const div = document.createElement('div'); div.className = 'card-mini';
  div.innerHTML = `
    <select name="reat_tipo[${i}]">
      <option value="lastro_brita">Lastro de brita</option><option value="colchao_areia_po_brita">Colchão areia/pó de brita</option>
      <option value="reaterro_importado">Reaterro importado</option><option value="compactacao_importado">Compactação importado</option>
      <option value="reaterro_local">Reaterro local</option><option value="compactacao_local">Compactação local</option>
      <option value="base_brita_graduada">Base brita graduada</option><option value="compactacao_base">Compactação base</option>
    </select>
    <div class="row2">
      <input type="number" step="0.5" min="1" max="200" name="reat_esp[${i}]" placeholder="Espessura (cm)">
      <div class="fotos" id="fotos-reat-${i}">
        <label class="cam"><span class="cic">📷</span><input type="file" accept="image/*" capture="environment" style="display:none" onchange="handleFotoUpload(this, ${DIARIO_ID}, 17, 'reaterro', 17, 'foto-reat-${i}')"></label>
      </div>
    </div>
    <input type="hidden" name="reat_foto[${i}]" id="foto-reat-${i}">
  `;
  li.appendChild(div); reaterroIdx++;
}


iniciarAcordeao(DIARIO_ID);
iniciarAutoSave(DIARIO_ID);
</script>
</body>
</html>
