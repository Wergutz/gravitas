<?php
header('X-Robots-Tag: noindex, nofollow');

$escopo     = ($diario['escopo'] ?? 'rede') === 'ramais' ? 'ramais' : 'rede';
$ehRamais   = $escopo === 'ramais';
$totalSteps = 19;
$stepAtual  = (int)$diario['step_atual'];
$bloqueado  = $diario['status'] !== 'rascunho';
$diarioId   = (int)$diario['id'];

/** número no padrão brasileiro (vírgula decimal) */
function nbrv($v, int $dec = 2): string {
    return number_format((float)$v, $dec, ',', '.');
}
/** valor para campo de texto: vírgula decimal, sem zeros à toa */
function campoNum($v, int $dec = 2): string {
    if ($v === null || $v === '') return '';
    $s = number_format((float)$v, $dec, ',', '');
    return $s;
}
function ehAsfalto(string $t): bool {
    $t = mb_strtolower($t);
    return str_contains($t, 'asfalto') || str_contains($t, 'cbuq');
}

// ── Totais atuais ──
$areaTotal = 0.0; $volAsf = 0.0; $areaVia = 0.0; $areaCalcada = 0.0;
foreach ($areas as $a) {
    $ar = (float)$a['base_m'] * (float)$a['largura_m'];
    $areaTotal += $ar;
    if ($a['espessura_m'] !== null) $volAsf += $ar * (float)$a['espessura_m'];
    if (($a['local'] ?? 'via') === 'calcada') $areaCalcada += $ar; else $areaVia += $ar;
}

// Presença map
$presMap = [];
foreach ($presencas as $p) $presMap[$p['funcionario_id']] = $p['status'];

// Fotos por step
$fotoStep = $fotosPorStep ?? [];

// Espessura padrão do asfalto (do caminhamento, quando houver)
$espAsf = 0.05;
foreach ($pavimentos as $pav) {
    if (ehAsfalto($pav['tipo_pavimento'])) {
        $espAsf = $pav['espessura_cm'] ? (float)$pav['espessura_cm'] / 100 : 0.05;
    }
}

// Equipamentos já salvos
$equipsMap = [];
foreach ($equipamentos as $eq) $equipsMap[$eq['equipamento_id'] . '_' . $eq['tipo']] = $eq['status'];

// ── Passos ──
$PASSOS_REDE = [
    1  => ['t'=>'Equipe na obra',                 'desc'=>'Presença e quem não está'],
    2  => ['t'=>'Atrasos / saídas antecipadas',   'desc'=>'Marcar por funcionário'],
    3  => ['t'=>'Materiais em estoque na frente', 'desc'=>'Tem tudo? Quais faltam'],
    4  => ['t'=>'Foto: carregando material',      'desc'=>'Transporte para a frente de serviço', 'foto'=>true, 'min'=>1],
    5  => ['t'=>'Sinalização + EPIs',             'desc'=>'Segurança antes de iniciar', 'foto'=>true, 'min'=>2],
    6  => ['t'=>'Equipamentos funcionando',       'desc'=>'Vibroacabadora, rolo, espargidor…'],
    7  => ['t'=>'Fotos: corte / regularização',   'desc'=>'Bordas — somente quando houver asfalto', 'foto'=>true, 'cond'=>'asfalto'],
    8  => ['t'=>'Foto: rebaixo da base',          'desc'=>'Na espessura do asfalto a aplicar', 'foto'=>true, 'cond'=>'asfalto'],
    9  => ['t'=>'Foto: imprimação',               'desc'=>'Somente asfalto', 'foto'=>true, 'cond'=>'asfalto'],
    10 => ['t'=>'Cargas de asfalto + NF',         'desc'=>'Foto da carga e da nota fiscal'],
    11 => ['t'=>'Foto: aplicação do asfalto',     'desc'=>'', 'foto'=>true, 'cond'=>'asfalto'],
    12 => ['t'=>'Foto: compactação',              'desc'=>'', 'foto'=>true],
    13 => ['t'=>'Foto: selagem da junta',         'desc'=>'', 'foto'=>true, 'cond'=>'asfalto'],
    14 => ['t'=>'Croqui com dimensões',           'desc'=>'Desenho cotado da área aplicada', 'foto'=>true],
    15 => ['t'=>'Dimensões — Asfalto',            'desc'=>'Áreas (base × largura) + espessura → m³'],
    16 => ['t'=>'Dimensões — outros pavimentos',  'desc'=>'Calçada, paralelepípedo, etc.'],
    17 => ['t'=>'Foto: rua limpa',                'desc'=>'Após a execução', 'foto'=>true, 'min'=>1],
    18 => ['t'=>'Foto: equipe no final',          'desc'=>'Integrantes + equipamentos', 'foto'=>true, 'min'=>1],
    19 => ['t'=>'Finalização do serviço',         'desc'=>'Confirmar fechamento do dia'],
];

$PASSOS_RAMAIS = [
    1  => $PASSOS_REDE[1],
    2  => $PASSOS_REDE[2],
    3  => $PASSOS_REDE[3],
    5  => $PASSOS_REDE[5],
    6  => $PASSOS_REDE[6],
    10 => ['t'=>'Cargas de asfalto + NF',      'desc'=>'Quando houver ramal com reposição em asfalto'],
    12 => ['t'=>'Foto: compactação das valas', 'desc'=>'Reposição sobre a vala do ramal', 'foto'=>true],
    14 => ['t'=>'Croqui com dimensões',        'desc'=>'Desenho cotado das valas dos ramais', 'foto'=>true],
    15 => ['t'=>'Medição dos ramais',          'desc'=>'Via e calçada de cada ramal — confira e informe a largura'],
    17 => $PASSOS_REDE[17],
    18 => $PASSOS_REDE[18],
    19 => $PASSOS_REDE[19],
];

$PASSOS = $ehRamais ? $PASSOS_RAMAIS : $PASSOS_REDE;
$pct    = (int)round($stepAtual / $totalSteps * 100);

function stepFeito(int $s, array $foStep, array $pres, array $cargas, array $areas, array $equips, array $diario): bool {
    if ($s <= (int)$diario['step_atual']) return true;
    if (!empty($foStep[$s])) return true;
    if ($s === 1)  return !empty($pres);
    if ($s === 3)  return $diario['mat_ok'] !== null;
    if ($s === 6)  return !empty($equips);
    if ($s === 10) return !empty($cargas);
    if ($s === 19) return !empty($diario['obs_final']);
    return false;
}

$labelEscopo = $ehRamais ? 'RAMAIS' : 'REDE';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1A2D4F">
<meta name="robots" content="noindex,nofollow">
<meta name="csrf" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
<title>Diário de Repavimentação · BACIN</title>
<link rel="stylesheet" href="<?= REPAV_BASE ?>/assets/css/repav.css">
</head>
<body>
<div class="phone">

  <div class="top">
    <div class="top-row">
      <a href="<?= REPAV_BASE ?>/" style="color:#fff;display:flex;align-items:center" aria-label="Voltar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      </a>
      <div class="nm">BACIN<small>DIÁRIO · REPAV. <?= htmlspecialchars($labelEscopo) ?></small></div>
      <div class="eq">
        <b><?= htmlspecialchars($trecho['pv_montante'] ?? '?') ?> → <?= htmlspecialchars($trecho['pv_jusante'] ?? '?') ?></b>
        <span style="font-size:9.5px;color:#C7D2E5"><?= htmlspecialchars($trecho['rua'] ?? '') ?></span>
      </div>
    </div>
    <div class="hoje">
      <span>📅 <?= date('d/m/Y', strtotime($diario['data'])) ?></span>
      <span class="esc-badge"><?= $ehRamais ? '🏠 ramais' : '🛣 rede' ?></span>
      <span class="gps" id="gps-status">📍 verificando…</span>
    </div>
  </div>

  <div class="scroll">

    <div class="prog-card">
      <div class="t">Diário de repavimentação — <?= htmlspecialchars($labelEscopo) ?> <b id="prog-pct"><?= $pct ?>%</b></div>
      <div class="barra"><i id="prog-bar" style="width:<?= $pct ?>%"></i></div>
    </div>

    <?php $devolucao = $devolucao ?? null; if ($devolucao): ?>
    <div class="devol devol-topo">
      <span class="ic">🔁</span>
      <div>
        <b>Serviço devolvido pelo escritório — refazer <?= $ehRamais ? 'a reposição dos ramais' : 'a reposição da rede' ?></b>
        <span class="motivo">Motivo: <?= htmlspecialchars($devolucao['motivo'] ?? '') ?></span>
        <?php if (!empty($devolucao['etapa_rotulo'])): ?>
        <span>Etapa devolvida: <?= htmlspecialchars($devolucao['etapa_rotulo']) ?></span>
        <?php endif; ?>
        <span>Quem devolveu: <?= htmlspecialchars(trim((string)($devolucao['usuario_nome'] ?? '')) ?: 'o escritório') ?><?php
          $__t = strtotime((string)($devolucao['created_at'] ?? ''));
          if ($__t) echo ' · ' . htmlspecialchars(date('d/m/Y', $__t) . ' às ' . date('H\\hi', $__t));
        ?></span>
        <span>Corrija na frente o que foi apontado antes de fechar esta medição.</span>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($bloqueado): ?>
    <div class="info" style="border-color:var(--ok)">
      <div class="info-h">
        <span style="font-size:20px">✅</span>
        <div><b>Diário enviado ao Planejador</b><span>Trecho fechado neste escopo. Somente leitura.</span></div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($ehRamais && !$ramais): ?>
    <div class="info" style="border-color:var(--aviso)">
      <div class="info-h">
        <span class="ic i-aviso">⚠️</span>
        <div><b>Sem ramais enviados neste trecho</b><span>A frente de ramais precisa estar enviada no app de ramais.</span></div>
      </div>
    </div>
    <?php endif; ?>

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
        <?php if ($num === 15): ?>
          <div class="hint">Medição encerrada. Total: <b><?= nbrv($areaTotal) ?> m²</b><?= $volAsf > 0 ? ' · asfalto <b>' . nbrv($volAsf, 3) . ' m³</b>' : '' ?>.</div>
          <?php foreach ($areas as $a): ?>
          <div class="med-ro">
            <b><?= htmlspecialchars($a['tipo_pavimento']) ?></b>
            <?php if ($a['numero_imovel']): ?><span class="tagloc">nº <?= htmlspecialchars($a['numero_imovel']) ?></span><?php endif; ?>
            <span class="tagloc <?= ($a['local'] ?? 'via') === 'calcada' ? 'calc' : '' ?>"><?= ($a['local'] ?? 'via') === 'calcada' ? 'calçada' : 'via' ?></span>
            <span style="margin-left:auto"><?= nbrv($a['base_m']) ?> × <?= nbrv($a['largura_m']) ?> m = <b><?= nbrv((float)$a['base_m'] * (float)$a['largura_m']) ?> m²</b></span>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
        <p class="hint">Diário enviado — modo somente leitura.</p>
        <?php endif; ?>
      <?php else: ?>

      <?php if ($num === 1): // Presença ?>
        <form onsubmit="salvarPasso(event,<?= $diarioId ?>,1)">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <input type="hidden" name="diario_id" value="<?= $diarioId ?>">
          <input type="hidden" name="step" value="1">
          <div class="lbl">Toda a equipe está na obra?</div>
          <div class="toggle">
            <button type="button" class="sim <?= !empty($presencas) && !in_array('ausente', array_column($presencas,'status'), true) ? 'on' : '' ?>" onclick="setTodos(this,'s',<?= $diarioId ?>)">Sim, completa</button>
            <button type="button" class="nao <?= in_array('ausente', array_column($presencas,'status'), true) ? 'on' : '' ?>" onclick="setTodos(this,'n',<?= $diarioId ?>)">Falta alguém</button>
          </div>
          <div id="lista-ausentes-<?= $diarioId ?>" style="<?= in_array('ausente', array_column($presencas,'status'), true) ? '' : 'display:none' ?>">
            <div class="lbl">Quem não está no início:</div>
            <?php foreach ($funcionarios as $f): ?>
            <label class="pres">
              <span class="av"><?= htmlspecialchars(mb_strtoupper(mb_substr($f['nome'],0,1))) ?></span>
              <span class="nome"><?= htmlspecialchars($f['nome']) ?><br><span style="font-size:10.5px;color:var(--muted)"><?= htmlspecialchars($f['funcao'] ?? '') ?></span></span>
              <input type="checkbox" name="ausentes[]" value="<?= (int)$f['id'] ?>" style="width:18px;height:18px" <?= ($presMap[$f['id']] ?? '') === 'ausente' ? 'checked' : '' ?>>
            </label>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="todos" id="todos-<?= $diarioId ?>" value="<?= in_array('ausente', array_column($presencas,'status'), true) ? 'n' : 's' ?>">
          <button type="submit" class="btn-salvar">Salvar presença</button>
        </form>

      <?php elseif ($num === 2): // Atrasos ?>
        <form onsubmit="salvarPasso(event,<?= $diarioId ?>,2)">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <input type="hidden" name="diario_id" value="<?= $diarioId ?>">
          <input type="hidden" name="step" value="2">
          <div class="hint">Marque quem chegou atrasado ou saiu mais cedo.</div>
          <?php foreach ($funcionarios as $f): $sts = $presMap[$f['id']] ?? 'presente'; ?>
          <div class="pres">
            <span class="av"><?= htmlspecialchars(mb_strtoupper(mb_substr($f['nome'],0,1))) ?></span>
            <span class="nome"><?= htmlspecialchars($f['nome']) ?></span>
            <label class="mini-flag <?= $sts === 'atrasou' ? 'on' : '' ?>">
              <input type="checkbox" name="atrasou[]" value="<?= (int)$f['id'] ?>" <?= $sts === 'atrasou' ? 'checked' : '' ?> style="display:none">atrasou</label>
            <label class="mini-flag <?= $sts === 'saiu_cedo' ? 'on' : '' ?>">
              <input type="checkbox" name="saiu_cedo[]" value="<?= (int)$f['id'] ?>" <?= $sts === 'saiu_cedo' ? 'checked' : '' ?> style="display:none">saiu cedo</label>
          </div>
          <?php endforeach; ?>
          <button type="submit" class="btn-salvar">Salvar</button>
        </form>

      <?php elseif ($num === 3): // Materiais ?>
        <form onsubmit="salvarPasso(event,<?= $diarioId ?>,3)">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <input type="hidden" name="diario_id" value="<?= $diarioId ?>">
          <input type="hidden" name="step" value="3">
          <div class="lbl">Estoque na frente tem todo o material necessário?</div>
          <div class="toggle">
            <button type="button" class="sim <?= (string)$diario['mat_ok'] === '1' ? 'on' : '' ?>" onclick="setMatOk(this,1)">Sim, tudo</button>
            <button type="button" class="nao <?= (string)$diario['mat_ok'] === '0' ? 'on' : '' ?>" onclick="setMatOk(this,0)">Falta material</button>
          </div>
          <input type="hidden" name="mat_ok" id="mat-ok-<?= $diarioId ?>" value="<?= htmlspecialchars((string)($diario['mat_ok'] ?? '')) ?>">
          <div id="mat-obs-box-<?= $diarioId ?>" style="<?= (string)$diario['mat_ok'] === '0' ? '' : 'display:none' ?>">
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
            $tipo  = array_key_exists('placa', $eq) ? 'pesado' : 'leve';
            $label = $eq['modelo'] . (!empty($eq['placa']) ? ' · ' . $eq['placa'] : '');
            $st    = $equipsMap[$eq['id'] . '_' . $tipo] ?? 'ok';
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
          </div>
          <?php endforeach; ?>
          <button type="submit" class="btn-salvar">Salvar equipamentos</button>
        </form>

      <?php elseif ($num === 10): // Cargas ?>
        <div class="hint">Uma foto por carga de asfalto + foto da NF. Use vírgula na massa (ex.: 6,5).</div>
        <form onsubmit="salvarPasso(event,<?= $diarioId ?>,10)">
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
              <input type="text" inputmode="decimal" name="carga_mass[]" placeholder="Massa (t)" value="<?= htmlspecialchars(campoNum($carga['massa_t'], 2)) ?>">
            </div>
            <div class="lbl">Foto da carga + foto da NF</div>
            <div class="fotos" id="fotos-carga-<?= (int)$carga['id'] ?>">
              <div class="cam" onclick="tirarFoto(<?= $diarioId ?>,10,this)"><span class="cic">📷</span>foto</div>
            </div>
          </div>
          <?php endforeach; ?>
          </div>
          <button type="button" class="add-item" onclick="adicionarCarga(<?= $diarioId ?>)">+ Adicionar carga de asfalto</button>
          <button type="submit" class="btn-salvar">Salvar cargas</button>
        </form>

      <?php elseif ($num === 14): // Croqui ?>
        <div class="hint">Fotografe o croqui cotado com todas as dimensões.</div>
        <div class="fotos" style="grid-template-columns:1fr 1fr" id="fotos-step14">
          <?php foreach ($fotoStep[14] ?? [] as $foto): ?>
          <div class="foto croqui">✏️<span class="tag">croqui</span></div>
          <?php endforeach; ?>
          <div class="cam" onclick="tirarFoto(<?= $diarioId ?>,14,this)"><span class="cic">✏️</span>foto do croqui</div>
        </div>

      <?php elseif ($num === 15 && $ehRamais): // ── MEDIÇÃO DOS RAMAIS ── ?>
        <div class="hint">
          Comprimentos vêm do app de ramais. Confira, ajuste se precisar e informe a <b>largura</b> de cada
          reposição (a espessura só quando for asfalto). Use vírgula decimal.
        </div>
        <form onsubmit="salvarPasso(event,<?= $diarioId ?>,15)">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <input type="hidden" name="diario_id" value="<?= $diarioId ?>">
          <input type="hidden" name="step" value="15">
          <?php foreach ($ramais as $r):
            $linhas = $areasPorRamal[(int)$r['id']] ?? [];
            if (!$linhas) continue;
          ?>
          <div class="card-mini ramal-card">
            <div class="ch">🏠 Nº <?= htmlspecialchars($r['numero_imovel']) ?>
              <span class="badge b-neutro" style="margin-left:8px">ramal <?= (int)$r['sequencia'] ?></span>
            </div>
            <?php foreach ($linhas as $a):
              $ehCalc = ($a['local'] ?? 'via') === 'calcada';
              $opcoes = $ehCalc ? RepavController::PAV_CALCADA : RepavController::PAV_VIA;
            ?>
            <div class="med-row dim-row" id="area-row-<?= (int)$a['id'] ?>">
              <div class="med-h">
                <span class="tagloc <?= $ehCalc ? 'calc' : '' ?>"><?= $ehCalc ? 'CALÇADA' : 'VIA' ?></span>
                <span class="med-area" id="med-area-<?= (int)$a['id'] ?>">— m²</span>
              </div>
              <input type="hidden" name="area_id[]" value="<?= (int)$a['id'] ?>">
              <span class="hint" style="margin:0">Tipo de pavimento</span>
              <select name="area_tipo[]" data-area="<?= (int)$a['id'] ?>" onchange="ajustarEspessura(this)">
                <?php foreach ($opcoes as $chave => $rot):
                  if ($chave === 'sem_calcada') continue; ?>
                <option value="<?= htmlspecialchars($rot) ?>" <?= $rot === $a['tipo_pavimento'] ? 'selected' : '' ?>><?= htmlspecialchars($rot) ?></option>
                <?php endforeach; ?>
                <?php if (!in_array($a['tipo_pavimento'], array_values($opcoes), true)): ?>
                <option value="<?= htmlspecialchars($a['tipo_pavimento']) ?>" selected><?= htmlspecialchars($a['tipo_pavimento']) ?></option>
                <?php endif; ?>
              </select>
              <div class="row3" style="margin-top:8px">
                <div><span class="hint" style="margin:0">Comprim. (m)</span>
                  <input type="text" inputmode="decimal" class="med-in" name="area_base[]" value="<?= htmlspecialchars(campoNum($a['base_m'])) ?>" oninput="calcMedRamal(<?= (int)$a['id'] ?>)"></div>
                <div><span class="hint" style="margin:0">Largura (m)</span>
                  <input type="text" inputmode="decimal" class="med-in" name="area_larg[]" value="<?= htmlspecialchars(campoNum($a['largura_m'])) ?>" placeholder="0,00" oninput="calcMedRamal(<?= (int)$a['id'] ?>)"></div>
                <?php $ehAsf = !$ehCalc && ehAsfalto($a['tipo_pavimento']); ?>
                <div class="box-esp" id="box-esp-<?= (int)$a['id'] ?>"<?= $ehAsf ? '' : ' style="display:none"' ?>>
                  <span class="hint" style="margin:0">Esp. (m)</span>
                  <input type="text" inputmode="decimal" name="area_esp[]" value="<?= $ehAsf ? htmlspecialchars(campoNum($a['espessura_m'], 3)) : '' ?>" placeholder="<?= nbrv(RepavController::ESP_MIN,2) ?> a <?= nbrv(RepavController::ESP_MAX,2) ?>">
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endforeach; ?>
          <div class="calc" id="calc-ramais">
            área total: <b><?= nbrv($areaTotal) ?> m²</b> · via <?= nbrv($areaVia) ?> m² · calçada <?= nbrv($areaCalcada) ?> m²
          </div>
          <button type="submit" class="btn-salvar">Salvar medição dos ramais</button>
        </form>

      <?php elseif ($num === 15): // Dimensões asfalto (rede) ?>
        <form onsubmit="salvarPasso(event,<?= $diarioId ?>,15)">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <input type="hidden" name="diario_id" value="<?= $diarioId ?>">
          <input type="hidden" name="step" value="15">
          <div class="lbl">Asfalto — áreas aplicadas (base × largura). Use vírgula decimal.</div>
          <div id="areas-asfalto">
          <?php $areasAsf = array_filter($areas, fn($a) => ehAsfalto($a['tipo_pavimento']) && $a['ramal_id'] === null); ?>
          <?php foreach ($areasAsf as $a): ?>
          <div class="dim-row" id="area-row-<?= (int)$a['id'] ?>">
            <input type="hidden" name="area_id[]" value="<?= (int)$a['id'] ?>">
            <div class="row3">
              <div><span class="hint" style="margin:0">Base (m)</span><input type="text" inputmode="decimal" name="area_base[]" value="<?= htmlspecialchars(campoNum($a['base_m'])) ?>" oninput="atualizarCalc()"></div>
              <div><span class="hint" style="margin:0">Largura (m)</span><input type="text" inputmode="decimal" name="area_larg[]" value="<?= htmlspecialchars(campoNum($a['largura_m'])) ?>" oninput="atualizarCalc()"></div>
              <button type="button" class="x" onclick="this.closest('.dim-row').remove();atualizarCalc()">✕</button>
            </div>
            <input type="hidden" name="area_esp[]" value="<?= htmlspecialchars(campoNum($a['espessura_m'] ?? $espAsf, 3)) ?>">
          </div>
          <?php endforeach; ?>
          </div>
          <div class="row2" style="margin-top:8px">
            <div><span class="hint" style="margin:0">Espessura asfalto (m) — <?= nbrv(RepavController::ESP_MIN,2) ?> a <?= nbrv(RepavController::ESP_MAX,2) ?></span>
              <input type="text" inputmode="decimal" id="esp-asf" value="<?= htmlspecialchars(campoNum($espAsf, 3)) ?>" oninput="atualizarCalc()"></div>
            <div style="align-self:flex-end;text-align:right" class="area-tot" id="calc-vol">vol: — m³</div>
          </div>
          <div class="calc" id="calc-asf">área total: — m²</div>
          <button type="button" class="add-item" onclick="adicionarAreaAsf(<?= $diarioId ?>)">+ Área</button>
          <button type="submit" class="btn-salvar">Salvar dimensões asfalto</button>
        </form>

      <?php elseif ($num === 16): // Outros pavimentos (rede) ?>
        <form onsubmit="salvarPasso(event,<?= $diarioId ?>,16)">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <input type="hidden" name="diario_id" value="<?= $diarioId ?>">
          <input type="hidden" name="step" value="16">
          <div class="hint">Adicione os demais pavimentos (calçada, paralelepípedo, etc.).</div>
          <div id="areas-outros">
          <?php $areasOtrs = array_filter($areas, fn($a) => !ehAsfalto($a['tipo_pavimento']) && $a['ramal_id'] === null); ?>
          <?php foreach ($areasOtrs as $a): ?>
          <div class="card-mini dim-row" id="area-row-<?= (int)$a['id'] ?>">
            <div class="ch"><?= htmlspecialchars($a['tipo_pavimento']) ?></div>
            <input type="hidden" name="area_id[]" value="<?= (int)$a['id'] ?>">
            <input type="hidden" name="area_esp[]" value="">
            <div class="row2">
              <div><span class="hint" style="margin:0">Base (m)</span><input type="text" inputmode="decimal" name="area_base[]" value="<?= htmlspecialchars(campoNum($a['base_m'])) ?>"></div>
              <div><span class="hint" style="margin:0">Largura (m)</span><input type="text" inputmode="decimal" name="area_larg[]" value="<?= htmlspecialchars(campoNum($a['largura_m'])) ?>"></div>
            </div>
          </div>
          <?php endforeach; ?>
          </div>
          <div style="margin-top:8px">
            <select id="sel-tipo-pav">
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
          Resumo: área total <b id="resumo-area"><?= nbrv($areaTotal) ?> m²</b>
          <?php if ($ehRamais): ?>· via <?= nbrv($areaVia) ?> m² · calçada <?= nbrv($areaCalcada) ?> m²<?php endif; ?>
          · asfalto <b id="resumo-vol"><?= nbrv($volAsf, 3) ?> m³</b>
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

      <?php elseif (!empty($passo['foto'])): // Passos só de foto ?>
        <div class="lbl">Fotos<?= !empty($passo['min']) ? ' (mínimo ' . (int)$passo['min'] . ')' : '' ?></div>
        <?php if (!empty($passo['cond'])): ?>
        <div class="hint">Etapa condicional: preencher só quando houver <?= htmlspecialchars($passo['cond']) ?>.</div>
        <?php endif; ?>
        <div class="fotos" id="fotos-step<?= $num ?>">
          <?php foreach ($fotoStep[$num] ?? [] as $foto): ?>
          <div class="foto">
            <?php if ($foto['thumb']): ?>
            <img src="<?= REPAV_BASE ?>/uploads/repav/thumbs/<?= htmlspecialchars($foto['thumb']) ?>" alt="foto" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0">
            <?php else: ?>📷<?php endif; ?>
            <span class="gpsb">GPS</span>
            <span class="tag"><?= htmlspecialchars(mb_substr($foto['filename'],0,12)) ?>…</span>
          </div>
          <?php endforeach; ?>
          <div class="cam" onclick="tirarFoto(<?= $diarioId ?>,<?= $num ?>,this)"><span class="cic">📷</span>tirar foto</div>
        </div>
        <?php if (!empty($fotoStep[$num])): ?>
        <button type="button" class="btn-salvar btn-marcar" style="margin-top:8px" onclick="marcarStep(<?= $diarioId ?>,<?= $num ?>)">✓ Marcar como feito</button>
        <?php endif; ?>

      <?php else: ?>
        <div class="hint">Preencha este passo.</div>
      <?php endif; ?>

      <?php endif; // bloqueado ?>
      </div>
    </div>
    <?php endforeach; ?>

  </div>

  <div class="footer">
    <div class="resumo">
      <?= $ehRamais ? 'ramais repostos' : 'aplicado hoje' ?><br>
      <b id="foot-resumo"><?= nbrv($areaTotal) ?> m² · <?= nbrv($volAsf, 3) ?> m³</b>
    </div>
    <?php if (!$bloqueado): $liberado = $stepAtual >= RepavController::STEP_ENCERRAR; ?>
    <form id="form-encerrar" method="post" action="<?= REPAV_BASE ?>/diario/<?= $diarioId ?>/encerrar" style="margin:0<?= $liberado ? '' : ';display:none' ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
      <input type="hidden" name="obs_final" id="enc-obs-final" value="<?= htmlspecialchars($diario['obs_final'] ?? '') ?>">
      <button type="submit" class="btn-encerrar" onclick="return confirmarEncerrar()">
        Encerrar &amp; enviar
      </button>
    </form>
    <span id="hint-encerrar" class="hint" style="font-size:11px;text-align:right;color:var(--muted)<?= $liberado ? ';display:none' : '' ?>">Complete até o passo <?= RepavController::STEP_ENCERRAR ?> para encerrar</span>
    <?php endif; ?>
  </div>

</div>

<input type="file" id="file-foto" accept="image/*" capture="environment" style="display:none">

<script>
const REPAV_BASE = '<?= REPAV_BASE ?>';
const DIARIO_ID  = <?= $diarioId ?>;
const ESCOPO     = '<?= htmlspecialchars($escopo) ?>';
const STEP_ENCERRAR = <?= RepavController::STEP_ENCERRAR ?>;
const CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>';
</script>
<script src="<?= REPAV_BASE ?>/assets/js/repav.js"></script>
</body>
</html>
