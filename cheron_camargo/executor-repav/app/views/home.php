<?php
header('X-Robots-Tag: noindex, nofollow');
$hoje      = date('d/m/Y');
$diaSem    = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][date('w')];
$stepAtual = $diarioHoje ? (int)$diarioHoje['step_atual'] : 0;
$pct       = (int)round($stepAtual / 19 * 100);
$temAsfalto = !empty(array_filter($pavimentos, fn($p) => str_contains(strtolower($p['tipo_pavimento']), 'asfalto') || str_contains(strtolower($p['tipo_pavimento']), 'cbuq')));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1A2D4F">
<meta name="robots" content="noindex,nofollow">
<meta name="csrf" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
<title>Repavimentação · CHERON CAMARGO</title>
<link rel="stylesheet" href="<?= REPAV_BASE ?>/assets/css/repav.css">
</head>
<body>
<div class="phone">

  <div class="top">
    <div class="top-row">
      <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Cheron Camargo">
  <rect x="4" y="4" width="92" height="92" rx="26" fill="none" stroke="#3CB86A" stroke-width="3"/>
  <rect x="14" y="14" width="72" height="72" rx="19" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="50" cy="33" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="50" cy="67" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="33" cy="50" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="67" cy="50" r="17" fill="none" stroke="#3CB86A" stroke-width="2.5"/>
  <circle cx="50" cy="50" r="5" fill="#3CB86A"/>
</svg>
      <div class="nm">CHERON CAMARGO<small>EXECUTOR · REPAVIMENTAÇÃO</small></div>
      <div class="eq">
        <b><?= htmlspecialchars($_SESSION['nome']) ?></b>
        <?= $diaSem ?>, <?= $hoje ?>
      </div>
    </div>
    <div class="hoje">
      <span>📅 <?= $caminhamento ? date('d/m/Y', strtotime($caminhamento['data_execucao'])) : 'Sem programação' ?></span>
      <span class="gps" id="gps-status">📍 verificando…</span>
    </div>
  </div>

  <div class="scroll">

    <div class="prog-card">
      <div class="t">Diário de repavimentação de hoje <b id="prog-pct"><?= $pct ?>%</b></div>
      <div class="barra"><i id="prog-bar" style="width:<?= $pct ?>%"></i></div>
    </div>

    <?php if (!$trechoAtual): ?>

    <div class="info">
      <div class="info-h">
        <span class="ic i-aviso">⚠️</span>
        <div>
          <b>Sem programação para hoje</b>
          <span>Aguardando o Planejador publicar o caminhamento de repavimentação da equipe.</span>
        </div>
      </div>
    </div>

    <?php else: ?>

    <div class="sec-tit">🛣 Sua frente de repavimentação hoje</div>

    <!-- Trecho a repavimentar -->
    <div class="info">
      <div class="info-h">
        <span class="ic i-navy" style="font-size:18px">🔧</span>
        <div>
          <b>Trecho a repavimentar</b>
          <span>rede e ramais já concluídos</span>
        </div>
      </div>
      <div class="corpo">
        <div class="pvs">
          <?= htmlspecialchars($trechoAtual['pv_montante'] ?? '?') ?>
          <span class="seta">→</span>
          <?= htmlspecialchars($trechoAtual['pv_jusante']  ?? '?') ?>
        </div>
        <div style="font-size:12.5px;color:var(--muted);margin-top:3px">
          <?php $partes = array_filter([
            $trechoAtual['rua']   ?? null,
            $trechoAtual['bacia'] ? 'Bacia ' . $trechoAtual['bacia'] : null,
          ]); echo implode(' · ', $partes); ?>
        </div>
        <?php if ($trechoAtual['contrato']): ?>
        <div style="margin-top:8px"><span class="badge b-neutro"><?= htmlspecialchars($trechoAtual['contrato']) ?></span></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tipo de pavimento -->
    <?php if ($pavimentos): ?>
    <div class="info">
      <div class="info-h">
        <span class="ic i-gold" style="font-size:18px">🏗</span>
        <div>
          <b>Tipo de pavimento a aplicar</b>
          <span>definido no planejamento</span>
        </div>
      </div>
      <div style="margin-top:10px">
        <?php foreach ($pavimentos as $pav): ?>
        <div class="pav-li">
          <?= htmlspecialchars($pav['tipo_pavimento']) ?>
          <?php if ($pav['espessura_cm']): ?>
          <span style="color:var(--muted);font-size:11px"> · esp. <?= number_format($pav['espessura_cm'],1,',','.') ?> cm</span>
          <?php endif; ?>
          <?php if (str_contains(strtolower($pav['tipo_pavimento']),'asfalto') || str_contains(strtolower($pav['tipo_pavimento']),'cbuq')): ?>
          <span class="badge b-info" style="margin-left:auto">principal</span>
          <?php else: ?>
          <span class="badge b-neutro" style="margin-left:auto">complementar</span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Caminhamento -->
    <?php if ($filaTrechos): ?>
    <div class="info">
      <div class="info-h">
        <span class="ic i-ok" style="font-size:18px">📈</span>
        <div><b>Caminhamento</b><span>próximos trechos a repavimentar</span></div>
      </div>
      <div style="margin-top:10px">
        <?php foreach ($filaTrechos as $tc):
          $ehHoje    = ($tc['id'] == ($trechoAtual['id'] ?? -1));
          $concluido = $tc['ct_status'] === 'concluido';
        ?>
        <div class="next">
          <span class="o" style="<?= $ehHoje ? 'background:var(--ok-bg);color:var(--ok)' : ($concluido ? 'background:#e0e0e0;color:#aaa' : '') ?>">
            <?= $tc['ordem'] ?>
          </span>
          <span style="<?= $concluido ? 'color:var(--muted);text-decoration:line-through' : '' ?>">
            <?= htmlspecialchars($tc['pv_montante'] ?? '') ?> → <?= htmlspecialchars($tc['pv_jusante'] ?? '') ?>
          </span>
          <?php if ($ehHoje): ?><b style="margin-left:auto;color:var(--ok);font-size:11px">hoje</b>
          <?php elseif ($concluido): ?><span style="margin-left:auto;font-size:10px;color:var(--muted)">✅</span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Diário -->
    <div class="sec-tit">📝 Diário de lançamentos</div>

    <?php if (!$diarioHoje): ?>
    <form method="post" action="<?= REPAV_BASE ?>/diario/novo">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? csrf_token_repav()) ?>">
      <input type="hidden" name="trecho_id" value="<?= (int)$trechoAtual['id'] ?>">
      <button type="submit" class="btn-start">
        🛣 Iniciar diário de repavimentação
      </button>
    </form>
    <?php elseif ($diarioHoje['status'] === 'rascunho'): ?>
    <a href="<?= REPAV_BASE ?>/diario/<?= (int)$diarioHoje['id'] ?>" class="btn-start">
      ✏️ Continuar diário — <?= $pct ?>% preenchido
    </a>
    <?php else: ?>
    <div class="info" style="border-color:var(--ok);margin-bottom:14px">
      <div class="info-h">
        <span style="font-size:20px">✅</span>
        <div>
          <b>Diário enviado</b>
          <span>
            <?= number_format($diarioHoje['area_total_m2'],1,',','.') ?> m²
            <?php if ($diarioHoje['volume_asf_m3'] > 0): ?>
            · asfalto <?= number_format($diarioHoje['volume_asf_m3'],2,',','.') ?> m³
            <?php endif; ?>
            — Aguardando aprovação.
          </span>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php endif; // $trechoAtual ?>

    <div id="offline-sec" class="sec-tit" style="display:none">⏳ Aguardando conexão</div>
    <div id="offline-queue-info" style="display:none">
      <div class="info">
        <div class="info-h"><span style="font-size:18px">📶</span>
          <div><b>Dados salvos localmente</b><span id="offline-count">0 item(s) para sincronizar</span></div>
        </div>
      </div>
    </div>

  </div>

  <div class="footer">
    <div class="resumo">
      <?= htmlspecialchars($_SESSION['nome']) ?><br>
      <span id="conn-badge">🟢 Online</span>
    </div>
    <a href="/cheron_camargo/painel/alterar-senha.php" class="btn-sair" style="margin-right:6px">🔑 Senha</a>
    <a href="<?= REPAV_BASE ?>/login.php?sair=1" class="btn-sair">Sair</a>
  </div>

</div>
<script src="<?= REPAV_BASE ?>/assets/js/repav.js"></script>
</body>
</html>
