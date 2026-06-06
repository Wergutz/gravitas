<?php
header('X-Robots-Tag: noindex, nofollow');
$hoje = date('d/m/Y');
$diaDaSemana = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][date('w')];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1A2D4F">
<meta name="robots" content="noindex,nofollow">
<title>Frente de Serviço · GRAVITAS</title>
<link rel="stylesheet" href="<?= EXECUTOR_BASE ?>/assets/css/executor.css">
</head>
<body>
<div class="phone">

  <!-- Topo -->
  <div class="top">
    <div class="top-row">
      <svg class="logo" viewBox="-4 -4 108 108" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <ellipse cx="50" cy="50" rx="54" ry="19" fill="none" stroke="#B9C1CC" stroke-width="3.5" transform="rotate(-24 50 50)"/>
        <circle cx="50" cy="50" r="44" fill="#1A2D4F" stroke="#B9C1CC" stroke-width="2.5"/>
        <circle cx="92.8" cy="23.1" r="4.5" fill="#C9A227"/>
        <path d="M 26.7 73.3 A 33 33 0 1 1 73.3 73.3" fill="none" stroke="#FFFFFF" stroke-width="6" stroke-linecap="round"/>
        <path d="M 50 50 L 67.4 29.3 L 55.5 53.5 Z" fill="#C9A227"/>
        <circle cx="50" cy="50" r="6.5" fill="#C9A227"/>
      </svg>
      <div class="nm">GRAVITAS<small>EXECUTOR</small></div>
      <?php if ($equipeId): ?>
      <div class="eq">
        <b><?= htmlspecialchars($caminhamento ? 'Equipe ativa' : 'Sem programação') ?></b>
        <?= htmlspecialchars($_SESSION['nome']) ?>
      </div>
      <?php endif; ?>
    </div>
    <div class="hoje">
      <span>📅 <?= $diaDaSemana ?>, <?= $hoje ?></span>
      <span class="gps" id="gps-status">📍 verificando GPS…</span>
    </div>
  </div>

  <div class="scroll">

    <!-- Progresso do diário -->
    <?php
    $stepAtual = $diarioHoje ? (int)$diarioHoje['step_atual'] : 0;
    $pct       = (int)round($stepAtual / 21 * 100);
    ?>
    <div class="prog-card">
      <div class="t">Diário de execução de hoje <b><?= $pct ?>%</b></div>
      <div class="barra"><i style="width:<?= $pct ?>%"></i></div>
    </div>

    <!-- Frente de serviço (do Planejador) -->
    <div class="sec-tit">📋 Sua frente de serviço hoje</div>

    <?php if (!$trechoAtual): ?>
    <div class="info">
      <div class="info-h">
        <span class="ic i-aviso">⚠️</span>
        <div><b>Sem programação para hoje</b><span>Aguardando o Planejador publicar o caminhamento.</span></div>
      </div>
    </div>

    <?php else: ?>
    <!-- Trecho -->
    <div class="info">
      <div class="info-h">
        <span class="ic i-navy" style="font-size:18px">🔧</span>
        <div>
          <b>Trecho do dia</b>
          <span><?= htmlspecialchars($trechoAtual['rua'] ?? '') ?> · <?= htmlspecialchars($trechoAtual['cidade'] ?? '') ?></span>
        </div>
      </div>
      <div class="corpo">
        <div class="pvs">
          <?= htmlspecialchars($trechoAtual['pv_montante'] ?? '?') ?>
          <span class="seta">→</span>
          <?= htmlspecialchars($trechoAtual['pv_jusante']  ?? '?') ?>
        </div>
        <div style="margin-top:8px">
          <?php if ($trechoAtual['extensao']): ?>
          <span class="badge b-info">📏 <?= number_format($trechoAtual['extensao'], 0, ',', '.') ?> m</span>
          <?php endif; ?>
          <?php if ($trechoAtual['dn']): ?>
          <span class="badge b-neutro">Ø <?= htmlspecialchars($trechoAtual['dn']) ?></span>
          <?php endif; ?>
          <?php if ($trechoAtual['contrato']): ?>
          <span class="badge b-neutro"><?= htmlspecialchars($trechoAtual['contrato']) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Botão de ação do diário -->
    <?php if (!$diarioHoje): ?>
    <form method="post" action="<?= EXECUTOR_BASE ?>/diario/novo">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? csrf_token_executor()) ?>">
      <input type="hidden" name="trecho_id" value="<?= (int)$trechoAtual['id'] ?>">
      <button type="submit" class="btn-start">
        📓 Iniciar diário de hoje
      </button>
    </form>
    <?php elseif ($diarioHoje['status'] === 'rascunho'): ?>
    <a href="<?= EXECUTOR_BASE ?>/diario/<?= (int)$diarioHoje['id'] ?>" class="btn-start">
      ✏️ Continuar diário (<?= $pct ?>% preenchido)
    </a>
    <?php else: ?>
    <div class="info">
      <div class="info-h">
        <span style="font-size:20px">✅</span>
        <div><b>Diário enviado</b><span>Aguardando aprovação do Planejador.</span></div>
      </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

    <!-- Fila offline -->
    <div class="sec-tit" id="offline-sec" style="display:none">⏳ Aguardando conexão</div>
    <div id="offline-queue-info" style="display:none">
      <div class="info">
        <div class="info-h">
          <span style="font-size:18px">📶</span>
          <div>
            <b>Dados salvos localmente</b>
            <span id="offline-count">0 item(s) para sincronizar</span>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /scroll -->

  <!-- Rodapé -->
  <div class="footer">
    <div class="resumo">
      <?= htmlspecialchars($_SESSION['nome']) ?><br>
      <span id="conn-badge">🟢 Online</span>
    </div>
    <a href="<?= EXECUTOR_BASE ?>/login.php?sair=1" class="btn-sair">Sair</a>
  </div>

</div><!-- /phone -->

<script src="<?= EXECUTOR_BASE ?>/assets/js/executor.js"></script>
</body>
</html>
