<?php
header('X-Robots-Tag: noindex, nofollow');
$totalSteps = 19;
$stepAtual  = (int)$diario['step_atual'];
$pct        = (int)round($stepAtual / $totalSteps * 100);
$bloqueado  = $diario['status'] === 'enviado';
$diarioId   = (int)$diario['id'];

// Calcular totais atuais
$areaAsf  = 0; $volAsf = 0; $areaOtrs = 0;
foreach ($areas as $a) {
    $area = (float)$a['base_m'] * (float)$a['largura_m'];
    if (str_contains(strtolower($a['tipo_pavimento']),'asfalto') || str_contains(strtolower($a['tipo_pavimento']),'cbuq')) {
        $areaAsf += $area;
        $volAsf  += $area * (float)($a['espessura_m'] ?? 0);
    } else {
        $areaOtrs += $area;
    }
}
$areaTotal = $areaAsf + $areaOtrs;

// Presença map
$presMap = [];
foreach ($presencas as $p) $presMap[$p['funcionario_id']] = $p['status'];

// Fotos por step
$fotoStep = [];
foreach ($fotos as $f) $fotoStep[(int)$f['step_num']][] = $f;

// Tipos de pavimento do caminhamento
$tiposPav = array_column($pavimentos, 'tipo_pavimento');
$temAsfalto = !empty(array_filter($tiposPav, fn($t) => str_contains(strtolower($t),'asfalto') || str_contains(strtolower($t),'cbuq')));
$espAsf = 0.05;
foreach ($pavimentos as $pav) {
    if (str_contains(strtolower($pav['tipo_pavimento']),'asfalto') || str_contains(strtolower($pav['tipo_pavimento']),'cbuq')) {
        $espAsf = $pav['espessura_cm'] ? (float)$pav['espessura_cm'] / 100 : 0.05;
    }
}

// Equipamentos map (diario_id → lista já salva)
$equipsMap = [];
foreach ($equipamentos as $eq) $equipsMap[$eq['equipamento_id'] . '_' . $eq['tipo']] = $eq['status'];

// Helper steps
$PASSOS = [
    1  => ['t'=>'Equipe na obra',                'desc'=>'Presença e quem não está'],
    2  => ['t'=>'Atrasos / saídas antecipadas',  'desc'=>'Marcar por funcionário'],
    3  => ['t'=>'Materiais em estoque na frente', 'desc'=>'Tem tudo? Quais faltam'],
    4  => ['t'=>'Foto: carregando material',      'desc'=>'Transporte para a frente de serviço', 'foto'=>true, 'min'=>1],
    5  => ['t'=>'Sinalização + EPIs',             'desc'=>'Segurança antes de iniciar', 'foto'=>true, 'min'=>2],
    6  => ['t'=>'Equipamentos funcionando',       'desc'=>'Vibroacabadora, rolo, espargidor…'],
    7  => ['t'=>'Fotos: corte / regularização',  'desc'=>'Bordas — somente quando houver asfalto', 'foto'=>true, 'cond'=>'asfalto'],
    8  => ['t'=>'Foto: rebaixo da base',          'desc'=>'Na espessura do asfalto a aplicar', 'foto'=>true, 'cond'=>'asfalto'],
    9  => ['t'=>'Foto: imprimação',               'desc'=>'Somente asfalto', 'foto'=>true, 'cond'=>'asfalto'],
    10 => ['t'=>'Cargas de asfalto + NF',         'desc'=>'Foto da carga e da nota fiscal'],
    11 => ['t'=>'Foto: aplicação do asfalto',     'desc'=>'', 'foto'=>true, 'cond'=>'asfalto'],
    12 => ['t'=>'Foto: compactação',              'desc'=>'', 'foto'=>true],
    13 => ['t'=>'Foto: selagem da junta',         'desc'=>'', 'foto'=>true, 'cond'=>'asfalto'],
    14 => ['t'=>'Croqui com dimensões',           'desc'=>'Desenho cotado da área aplicada', 'foto'=>true],
    15 => ['t'=>'Dimensões — Asfalto',            'desc'=>'Áreas (base × largura) + espessura → m³'],
    16 => ['t'=>'Dimensões — outros pavimentos',  'desc'=>'Calçada, paralelepípedo, etc.'],
    17 => ['t'=>'Foto: rua limpa',               'desc'=>'Após a execução', 'foto'=>true, 'min'=>1],
    18 => ['t'=>'Foto: equipe no final',          'desc'=>'Integrantes + equipamentos', 'foto'=>true, 'min'=>1],
    19 => ['t'=>'Finalização do serviço',         'desc'=>'Confirmar fechamento do dia'],
];

function stepFeito(int $s, array $foStep, array $pres, array $cargas, array $areas, array $equips, array $diario): bool {
    if ($s <= (int)$diario['step_atual']) return true;
    if (in_array($s,[4,5,7,8,9,11,12,13,14,17,18])) return !empty($foStep[$s]);
    if ($s === 1) return !empty($pres);
    if ($s === 2) return count($pres) >= 0; // always considered done if step 1 done
    if ($s === 3) return $diario['mat_ok'] !== null;
    if ($s === 6) return !empty($equips);
    if ($s === 10) return !empty($cargas);
    if ($s === 15 || $s === 16) return !empty($areas);
    if ($s === 19) return !empty($diario['obs_final']);
    return false;
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
<title>Diário de Repavimentação · Cheron &amp; Camargo</title>
<link rel="stylesheet" href="<?= REPAV_BASE ?>/assets/css/repav.css">
</head>
<body>
<div class="phone">

  <!-- Topo -->
  <div class="top">
    <div class="top-row">
      <a href="<?= REPAV_BASE ?>/" style="color:#fff;display:flex;align-items:center">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      </a>
      <div class="nm">Cheron &amp; Camargo<small>DIÁRIO DE REPAVIMENTAÇÃO</small></div>
      <div class="eq">
        <b><?= htmlspecialchars($trecho['pv_montante'] ?? '?') ?> → <?= htmlspecialchars($trecho['pv_jusante'] ?? '?') ?></b>
        <span style="font-size:9.5px;color:#C7D2E5"><?= htmlspecialchars($trecho['rua'] ?? '') ?></span>
      </div>
    </div>
    <div class="hoje">
      <span>📅 <?= date('d/m/Y', strtotime($diario['data'])) ?></span>
      <span class="gps" id="gps-status">📍 verificando…</span>
    </div>
  </div>

  <div class="scroll">

    <!-- Progresso -->
    <div class="prog-card">
      <div class="t">Diário de repavimentação <b id="prog-pct"><?= $pct ?>%</b></div>
      <div class="barra"><i id="prog-bar" style="width:<?= $pct ?>%"></i></div>
    </div>

    <?php if ($bloqueado): ?>
    <div class="info" style="border-color:var(--ok)">
      <div class="info-h">
        <span style="font-size:20px">✅</span>
        <div><b>Diário enviado ao Planejador</b><span>Somente leitura.</span></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- ════════════════════════════════════════════ -->
    <!-- 19 PASSOS                                    -->
    <!-- ════════════════════════════════════════════ -->
    <?php foreach ($PASSOS as $num => $passo):
      $feito = stepFeito($num, $fotoStep, $presencas, $cargas, $areas, $equipamentos, $diario);
    ?>
    <div class="step <?= $feito ? 'feito' : '' ?>" id="step-<?= $num ?>">
      <div class="step-h" onclick="toggleStep(<?= $num ?>)">
        <span class="n"><?= $num ?></span>
        <div class="tt">
          <b><?= htmlspecialchars($passo['t']) ?></b>
          <span><?= htmlspecialchars($passo['desc']) ?><?= !empty($passo['cond']) ? ' · condicional' : '' ?></span>
        </div>
        <svg class="ck" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="9"/>
          <?= $feito ? '<path d="M8 12l3 3 5-6"/>' : '' ?>
        </svg>
        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
      </div>

      <div class="step-body">
      <?php if ($bloqueado): ?>
        <p class="hint">Diário enviado — modo somente leitura.</p>
      <?php else: ?>

      <!-- ─── Conteúdo por passo ─── -->
      <?php if ($num === 1): // Presença ?>
        <form onsubmit="salvarPasso(event,<?= $diarioId ?>,1)">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <input type="hidden" name="diario_id" value="<?= $diarioId ?>">
          <input type="hidden" name="step" value="1">
          <div class="lbl">Toda a equipe está na obra?</div>
          <div class="toggle">
            <button type="button" class="sim <?= empty($presencas)||!in_array('ausente',array_column($presencas,'status'))&&!empty($presencas)?'on':'' ?>" onclick="setTodos(this,'s',<?= $diarioId ?>)">Sim, completa</button>
            <button type="button" class="nao" onclick="setTodos(this,'n',<?= $diarioId ?>)">Falta alguém</button>
          </div>
          <div id="lista-ausentes-<?= $diarioId ?>" style="display:none">
            <div class="lbl">Quem não está no início:</div>
            <?php foreach ($funcionarios as $f): ?>
            <label class="pres">
              <span class="av"><?= strtoupper(substr($f['nome'],0,1)) ?></span>
              <span class="nome"><?= htmlspecialchars($f['nome']) ?><br><span style="font-size:10.5px;color:var(--muted)"><?= htmlspecialchars($f['funcao'] ?? '') ?></span></span>
              <input type="checkbox" name="ausentes[]" value="<?= (int)$f['id'] ?>" style="width:18px;height:18px" <?= ($presMap[$f['id']] ?? '') === 'ausente' ? 'checked' : '' ?>>
            </label>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="todos" id="todos-<?= $diarioId ?>" value="<?= !empty($presencas) && !in_array('ausente', array_column($presencas,'status')) ? 's' : 'n' ?>">
          <button type="submit" class="btn-salvar">Salvar presença</button>
        </form>

      <?php elseif ($num === 2): // Atrasos ?>
        <form onsubmit="salvarPasso(event,<?= $diarioId ?>,2)">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <input type="hidden" name="diario_id" value="<?= $diarioId ?>">
          <input type="hidden" name="step" value="2">
          <div class="hint">Marque quem chegou atrasado ou saiu mais cedo.</div>
          <?php foreach ($funcionarios as $f):
            $sts = $presMap[$f['id']] ?? 'presente';
          ?>
          <div class="pres">
            <span class="av"><?= strtoupper(substr($f['nome'],0,1)) ?></span>
            <span class="nome"><?= htmlspecialchars($f['nome']) ?></span>
            <label class="mini-flag <?= $sts === 'atrasou' ? 'on' : '' ?>">
              <input type="checkbox" name="atrasou[]" value="<?= (int)$f['id'] ?>" <?= $sts === 'atrasou' ? 'checked' : '' ?> style="display:none">atrasou</label>
            <label class="mini-flag <?= $sts === 'saiu_cedo' ? 'on' : '' ?>">
              <input type="checkbox" name="saiu_cedo[]" value="<?= (int)$f['id'] ?>" <?= $sts === 'saiu_cedo' ? 'checked' : '' ?> style="display:none">saiu cedo</label>
          </div>
          <?php endforeach; ?>
          <button type="submit" class="btn-salvar">Salvar</button>
        </form>

      <?php elseif ($num === 3): // Material check ?>
        <form onsubmit="salvarPasso(event,<?= $diarioId ?>,3)">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <input type="hidden" name="diario_id" value="<?= $diarioId ?>">
          <input type="hidden" name="step" value="3">
          <div class="lbl">Estoque na frente tem todo o material necessário?</div>
          <div class="toggle">
            <button type="button" class="sim <?= $diario['mat_ok'] === '1' ? 'on' : '' ?>" onclick="setMatOk(this,1)">Sim, tudo</button>
            <button type="button" class="nao <?= $diario['mat_ok'] === '0' ? 'on' : '' ?>" onclick="setMatOk(this,0)">Falta material</button>
          </div>
          <input type="hidden" name="mat_ok" id="mat-ok-<?= $diarioId ?>" value="<?= htmlspecialchars($diario['mat_ok'] ?? '') ?>">
          <div id="mat-obs-box-<?= $diarioId ?>" style="<?= $diario['mat_ok'] === '0' ? '' : 'display:none' ?>">
            <div class="lbl">Quais estão faltando:</div>
            <textarea name="mat_obs" rows="2" placeholder="CBUQ, emulsão…"><?= htmlspecialchars($diario['mat_obs'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn-salvar">Salvar</button>
        </form>

      <?php elseif ($num === 6): // Equipamentos ?>
        <form onsubmit="salvarPasso(event,<?= $diarioId ?>,6)">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <input type="hidden" name="diario_id" value="<?= $diarioId ?>">
          <input type="hidden" name="step" value="6">
          <?php foreach (array_merge($equipsPesados, $equipsLeves) as $eq):
            $tipo  = isset($eq['placa']) ? 'pesado' : 'leve';
            $label = $eq['modelo'] . (isset($eq['placa']) && $eq['placa'] ? ' · ' . $eq['placa'] : '');
            $key   = $eq['id'] . '_' . $tipo;
            $st    = $equipsMap[$key] ?? 'ok';
          ?>
          <div class="card-mini">
            <div class="ch"><?= htmlspecialchars($label) ?>
              <span class="badge <?= $tipo === 'pesado' ? 'b-info' : 'b-neutro' ?>" style="margin-left:8px"><?= $tipo ?></span>
            </div>
            <input type="hidden" name="equip_id[]" value="<?= (int)$eq['id'] ?>">
            <input type="hidden" name="equip_tipo[]" value="<?= $tipo ?>">
            <div class="toggle" style="margin-top:8px">
              <button type="button" class="sim equip-btn <?= $st === 'ok' ? 'on' : '' ?>" onclick="setEquipStatus(this,'ok')">Funcionando</button>
              <button type="button" class="nao equip-btn <?= $st === 'problema' ? 'on' : '' ?>" onclick="setEquipStatus(this,'problema')">Com problema</button>
            </div>
            <input type="hidden" name="equip_status[]" value="<?= $st ?>">
            <div class="lbl">Foto do equipamento</div>
            <div class="fotos" id="fotos-step6-<?= $eq['id'] ?>">
              <?php foreach ($fotoStep[6] ?? [] as $foto): ?>
              <div class="foto"><span class="gpsb">GPS</span><span class="tag"><?= htmlspecialchars(substr($foto['filename'],0,12)) ?>…</span></div>
              <?php endforeach; ?>
              <div class="cam" onclick="tirarFoto(<?= $diarioId ?>,6,this)"><span class="cic">📷</span>foto</div>
            </div>
          </div>
          <?php endforeach; ?>
          <button type="submit" class="btn-salvar">Salvar equipamentos</button>
        </form>

      <?php elseif ($num === 10): // Cargas de asfalto ?>
        <div class="hint">Uma foto por carga de asfalto + foto da NF.</div>
        <form id="form-cargas-<?= $diarioId ?>" onsubmit="salvarPasso(event,<?= $diarioId ?>,10)">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <input type="hidden" name="diario_id" value="<?= $diarioId ?>">
          <input type="hidden" name="step" value="10">
          <div id="cargas-lista">
          <?php foreach ($cargas as $carga): ?>
          <div class="card-mini" id="carga-<?= (int)$carga['id'] ?>">
            <div class="ch">Carga <?= (int)$carga['sequencia'] ?></div>
            <input type="hidden" name="carga_id[]" value="<?= (int)$carga['id'] ?>">
            <div class="row2">
              <input type="text" name="carga_nf[]" placeholder="Nº NF" value="<?= htmlspecialchars($carga['numero_nf'] ?? '') ?>">
              <input type="number" name="carga_mass[]" step="0.01" placeholder="Massa (t)" value="<?= htmlspecialchars($carga['massa_t'] ?? '') ?>">
            </div>
            <div class="lbl">Foto da carga + foto da NF</div>
            <div class="fotos" id="fotos-carga-<?= (int)$carga['id'] ?>">
              <?php foreach ($fotoStep[10] ?? [] as $foto): ?>
              <div class="foto"><span class="gpsb">GPS</span><span class="tag">carga/nf</span></div>
              <?php endforeach; ?>
              <div class="cam" onclick="tirarFoto(<?= $diarioId ?>,10,this)"><span class="cic">📷</span>foto</div>
            </div>
          </div>
          <?php endforeach; ?>
          </div>
          <button type="button" class="add-item" onclick="adicionarCarga(<?= $diarioId ?>)">+ Adicionar carga de asfalto</button>
          <button type="submit" class="btn-salvar">Salvar cargas</button>
        </form>

      <?php elseif ($num === 14): // Croqui ?>
        <div class="hint">Fotografe o croqui cotado da área aplicada (com todas as dimensões).</div>
        <div class="fotos" style="grid-template-columns:1fr 1fr" id="fotos-step14">
          <?php foreach ($fotoStep[14] ?? [] as $foto): ?>
          <div class="foto croqui">✏️<span class="tag">croqui</span></div>
          <?php endforeach; ?>
          <div class="cam" onclick="tirarFoto(<?= $diarioId ?>,14,this)"><span class="cic">✏️</span>foto do croqui</div>
        </div>

      <?php elseif ($num === 15): // Dimensões asfalto ?>
        <form onsubmit="salvarPasso(event,<?= $diarioId ?>,15)">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <input type="hidden" name="diario_id" value="<?= $diarioId ?>">
          <input type="hidden" name="step" value="15">
          <div class="lbl">Asfalto — áreas aplicadas (base × largura)</div>
          <div id="areas-asfalto">
          <?php $areasAsf = array_filter($areas, fn($a) => str_contains(strtolower($a['tipo_pavimento']),'asfalto')||str_contains(strtolower($a['tipo_pavimento']),'cbuq')); ?>
          <?php foreach ($areasAsf as $a): ?>
          <div class="dim-row" id="area-row-<?= (int)$a['id'] ?>">
            <input type="hidden" name="area_id[]" value="<?= (int)$a['id'] ?>">
            <div class="row3">
              <div><span class="hint" style="margin:0">Base (m)</span><input type="number" name="area_base[]" step="0.01" value="<?= htmlspecialchars($a['base_m']) ?>"></div>
              <div><span class="hint" style="margin:0">Largura (m)</span><input type="number" name="area_larg[]" step="0.01" value="<?= htmlspecialchars($a['largura_m']) ?>"></div>
              <button type="button" class="x" onclick="this.closest('.dim-row').remove()">✕</button>
            </div>
            <input type="hidden" name="area_esp[]" value="<?= htmlspecialchars($a['espessura_m'] ?? $espAsf) ?>">
          </div>
          <?php endforeach; ?>
          </div>
          <div class="row2" style="margin-top:8px">
            <div><span class="hint" style="margin:0">Espessura asfalto (m)</span>
              <input type="number" id="esp-asf" step="0.001" value="<?= $espAsf ?>" onchange="atualizarCalc()"></div>
            <div style="align-self:flex-end;text-align:right" class="area-tot" id="calc-vol">vol: — m³</div>
          </div>
          <div class="calc" id="calc-asf">área total: — m²</div>
          <button type="button" class="add-item" onclick="adicionarAreaAsf(<?= $diarioId ?>)">+ Área</button>
          <button type="submit" class="btn-salvar">Salvar dimensões asfalto</button>
        </form>

      <?php elseif ($num === 16): // Outros pavimentos ?>
        <form onsubmit="salvarPasso(event,<?= $diarioId ?>,16)">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <input type="hidden" name="diario_id" value="<?= $diarioId ?>">
          <input type="hidden" name="step" value="16">
          <div class="hint">Adicione os demais pavimentos (calçada, paralelepípedo, etc.).</div>
          <div id="areas-outros">
          <?php $areasOtrs = array_filter($areas, fn($a) => !str_contains(strtolower($a['tipo_pavimento']),'asfalto')&&!str_contains(strtolower($a['tipo_pavimento']),'cbuq')); ?>
          <?php foreach ($areasOtrs as $a): ?>
          <div class="card-mini dim-row" id="area-row-<?= (int)$a['id'] ?>">
            <div class="ch"><?= htmlspecialchars($a['tipo_pavimento']) ?></div>
            <input type="hidden" name="area_id[]" value="<?= (int)$a['id'] ?>">
            <input type="hidden" name="area_esp[]" value="">
            <div class="row2">
              <div><span class="hint" style="margin:0">Base (m)</span><input type="number" name="area_base[]" step="0.01" value="<?= htmlspecialchars($a['base_m']) ?>"></div>
              <div><span class="hint" style="margin:0">Largura (m)</span><input type="number" name="area_larg[]" step="0.01" value="<?= htmlspecialchars($a['largura_m']) ?>"></div>
            </div>
          </div>
          <?php endforeach; ?>
          </div>
          <div style="margin-top:8px">
            <select id="sel-tipo-pav" style="width:100%;font-family:inherit;font-size:13.5px;padding:11px 12px;border:1px solid var(--line);border-radius:10px;background:#fff">
              <option value="Calçada">Calçada</option>
              <option value="Paralelepípedo Regular">Paralelepípedo Regular</option>
              <option value="Paralelepípedo Irregular">Paralelepípedo Irregular</option>
              <option value="Bloco de Concreto">Bloco de Concreto</option>
              <option value="Chão Batido">Chão Batido</option>
            </select>
            <button type="button" class="add-item" style="margin-top:8px" onclick="adicionarAreaOutro(<?= $diarioId ?>)">+ Adicionar pavimento</button>
          </div>
          <button type="submit" class="btn-salvar">Salvar outros pavimentos</button>
        </form>

      <?php elseif ($num === 19): // Finalização ?>
        <div class="calc">
          Resumo: área total <b id="resumo-area"><?= number_format($areaTotal,2,',','.') ?> m²</b>
          · asfalto <b id="resumo-vol"><?= number_format($volAsf,2,',','.') ?> m³</b>
          · <?= count($cargas) ?> carga(s)
        </div>
        <form onsubmit="salvarPasso(event,<?= $diarioId ?>,19)">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <input type="hidden" name="diario_id" value="<?= $diarioId ?>">
          <input type="hidden" name="step" value="19">
          <div class="lbl" style="margin-top:12px">Observações finais (opcional)</div>
          <textarea name="obs_final" rows="3" placeholder="Ocorrências, pendências…"><?= htmlspecialchars($diario['obs_final'] ?? '') ?></textarea>
          <button type="submit" class="btn-salvar">Confirmar finalização</button>
        </form>

      <?php elseif (!empty($passo['foto'])): // Steps só de foto ?>
        <div class="lbl">Fotos<?= !empty($passo['min']) ? ' (mínimo ' . $passo['min'] . ')' : '' ?></div>
        <?php if (!empty($passo['cond'])): ?>
        <div class="hint">Etapa condicional: preencher só quando houver <?= htmlspecialchars($passo['cond']) ?>.</div>
        <?php endif; ?>
        <div class="fotos" id="fotos-step<?= $num ?>">
          <?php foreach ($fotoStep[$num] ?? [] as $foto): ?>
          <div class="foto">
            <?php if ($foto['thumb']): ?>
            <img src="<?= REPAV_BASE ?>/uploads/repav/thumbs/<?= htmlspecialchars($foto['thumb']) ?>" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0">
            <?php else: ?>📷<?php endif; ?>
            <span class="gpsb">GPS</span>
            <span class="tag"><?= htmlspecialchars(substr($foto['filename'],0,12)) ?>…</span>
          </div>
          <?php endforeach; ?>
          <div class="cam" onclick="tirarFoto(<?= $diarioId ?>,<?= $num ?>,this)"><span class="cic">📷</span>tirar foto</div>
        </div>
        <?php if (!empty($fotoStep[$num])): ?>
        <button class="btn-salvar" style="margin-top:8px" onclick="marcarStep(<?= $diarioId ?>,<?= $num ?>)">✓ Marcar como feito</button>
        <?php endif; ?>

      <?php else: ?>
        <div class="hint">Preencha este passo.</div>
      <?php endif; ?>

      <?php endif; // bloqueado ?>
      </div><!-- /step-body -->
    </div><!-- /step -->
    <?php endforeach; ?>

  </div><!-- /scroll -->

  <!-- Rodapé com m² e m³ + botão encerrar -->
  <div class="footer">
    <div class="resumo">
      aplicado hoje<br>
      <b id="foot-resumo"><?= number_format($areaTotal,2,',','.') ?> m² · <?= number_format($volAsf,2,',','.') ?> m³</b>
    </div>
    <?php if (!$bloqueado && $stepAtual >= 17): ?>
    <form method="post" action="<?= REPAV_BASE ?>/diario/<?= $diarioId ?>/encerrar" style="margin:0">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
      <button type="submit" class="btn-encerrar" onclick="return confirm('Encerrar e enviar o diário ao Painel?')">
        Encerrar &amp; enviar
      </button>
    </form>
    <?php elseif (!$bloqueado): ?>
    <span class="hint" style="font-size:11px;text-align:right;color:var(--muted)">Complete até o passo 17 para encerrar</span>
    <?php endif; ?>
  </div>

</div><!-- /phone -->

<input type="file" id="file-foto" accept="image/*" capture="environment" style="display:none">

<script>
const REPAV_BASE = '<?= REPAV_BASE ?>';
const DIARIO_ID  = <?= $diarioId ?>;
</script>
<script src="<?= REPAV_BASE ?>/assets/js/repav.js"></script>
</body>
</html>
