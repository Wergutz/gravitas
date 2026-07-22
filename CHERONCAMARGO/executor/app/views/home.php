<?php
header('X-Robots-Tag: noindex, nofollow');
$hoje = date('d/m/Y');
$diaDaSemana = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][date('w')];
$stepAtual = $diarioHoje ? (int)$diarioHoje['step_atual'] : 0;
$pct       = (int)round($stepAtual / 21 * 100);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1A2D4F">
<meta name="robots" content="noindex,nofollow">
<meta name="csrf" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
<title>Frente de Serviço · Cheron &amp; Camargo</title>
<link rel="stylesheet" href="<?= EXECUTOR_BASE ?>/assets/css/executor.css">
</head>
<body>
<div class="phone">

  <!-- Topo fixo -->
  <div class="top">
    <div class="top-row">
      <img class="logo" src="/CHERONCAMARGO/painel/assets/img/icon-cheron-camargo-white.png" alt="Cheron & Camargo">
      <div class="nm">Cheron &amp; Camargo<small>EXECUTOR</small></div>
      <div class="eq">
        <b><?= htmlspecialchars($_SESSION['nome']) ?></b>
        <?= $diaDaSemana ?>, <?= $hoje ?>
      </div>
    </div>
    <div class="hoje">
      <span>📅 <?= $caminhamento ? date('d/m/Y', strtotime($caminhamento['data_execucao'])) : 'Sem programação' ?></span>
      <span class="gps" id="gps-status">📍 verificando…</span>
    </div>
  </div>

  <div class="scroll">

    <!-- ── Progresso do diário ───────────────────────────── -->
    <div class="prog-card">
      <div class="t">Diário de hoje <b id="prog-pct"><?= $pct ?>%</b></div>
      <div class="barra"><i id="prog-bar" style="width:<?= $pct ?>%"></i></div>
    </div>

    <?php if (!$trechoAtual): ?>
    <!-- Sem programação -->
    <div class="info">
      <div class="info-h">
        <span class="ic i-aviso" style="font-size:20px">⚠️</span>
        <div>
          <b>Sem programação para hoje</b>
          <span>Aguardando o Planejador publicar o caminhamento da equipe.</span>
        </div>
      </div>
    </div>

    <?php else: ?>

    <!-- ═══════════════════════════════════════════════════
         TOPO — SOMENTE LEITURA (do Planejador)
         ═══════════════════════════════════════════════════ -->
    <div class="sec-tit">📋 Sua frente de serviço hoje</div>

    <!-- 1. Trecho a executar -->
    <div class="info">
      <div class="info-h">
        <span class="ic i-navy" style="font-size:18px">🔧</span>
        <div>
          <b>Trecho a executar</b>
          <span>1º da sequência de hoje</span>
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
            $trechoAtual['rua']    ?? null,
            $trechoAtual['bacia']  ? 'Bacia ' . $trechoAtual['bacia'] : null,
            $trechoAtual['extensao'] ? number_format($trechoAtual['extensao'], 0, ',', '.') . ' m' : null,
            $trechoAtual['dn']     ? 'DN ' . $trechoAtual['dn'] : null,
          ]); echo implode(' · ', $partes); ?>
        </div>
        <div style="margin-top:8px">
          <?php if ($osPdf): ?>
          <span class="badge b-ok">OS anexada</span>
          <?php endif; ?>
          <?php if ($trechoAtual['contrato']): ?>
          <span class="badge b-neutro"><?= htmlspecialchars($trechoAtual['contrato']) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- 2. Ordem de Serviço PDF -->
    <?php if ($osPdf): ?>
    <div class="info">
      <div class="info-h">
        <span class="ic i-gold" style="font-size:20px">📄</span>
        <div>
          <b>Ordem de Serviço</b>
          <span>do topógrafo — abra antes de iniciar</span>
        </div>
      </div>
      <a class="pdf-link" href="#"
         onclick="abrirPdf('<?= $painelBase ?>/uploads/os/<?= htmlspecialchars($osPdf['arquivo_pdf']) ?>');return false">
        📄 <?= htmlspecialchars($osPdf['arquivo_pdf']) ?>
        <?php if ($osPdf['topografo']): ?>
        <small style="font-size:10.5px;color:var(--muted);display:block;margin-top:2px">
          <?= htmlspecialchars($osPdf['topografo']) ?>
          <?= $osPdf['data_os'] ? ' · ' . date('d/m/Y', strtotime($osPdf['data_os'])) : '' ?>
        </small>
        <?php endif; ?>
        <span class="pp">abrir ›</span>
      </a>
    </div>
    <?php else: ?>
    <div class="info">
      <div class="info-h">
        <span class="ic i-info" style="font-size:18px">📄</span>
        <div>
          <b>Ordem de Serviço</b>
          <span>Nenhuma OS anexada a este trecho</span>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- 3. Material a pegar no almoxarifado -->
    <div class="info">
      <div class="info-h">
        <span class="ic i-info" style="font-size:18px">📦</span>
        <div>
          <b>Material a pegar no almoxarifado</b>
          <span>retire antes de seguir à frente</span>
        </div>
      </div>
      <?php if ($materiais): ?>
      <div style="margin-top:10px">
        <?php foreach ($materiais as $mat): ?>
        <div class="mat-li">
          <span>
            <?= htmlspecialchars($mat['nome']) ?>
          </span>
          <span class="q">
            <?= number_format($mat['quantidade'], 2, ',', '.') ?> <?= htmlspecialchars($mat['unidade']) ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div style="margin-top:10px;font-size:13px;color:var(--muted)">Nenhum material alocado a este trecho.</div>
      <?php endif; ?>
    </div>

    <!-- 4. Caminhamento — fila de trechos -->
    <?php if ($filaTrechos): ?>
    <div class="info">
      <div class="info-h">
        <span class="ic i-ok" style="font-size:18px">📈</span>
        <div>
          <b>Caminhamento</b>
          <span>próximos trechos da programação</span>
        </div>
      </div>
      <div style="margin-top:10px">
        <?php foreach ($filaTrechos as $idx => $tc):
          $ehHoje = ($tc['id'] == ($trechoAtual['id'] ?? -1));
          $concluido = $tc['ct_status'] === 'concluido';
        ?>
        <div class="next">
          <span class="o" style="<?= $ehHoje ? 'background:var(--ok-bg);color:var(--ok)' : ($concluido ? 'background:#e0e0e0;color:#aaa' : '') ?>">
            <?= $tc['ordem'] ?>
          </span>
          <span style="<?= $concluido ? 'color:var(--muted);text-decoration:line-through' : '' ?>">
            <?= htmlspecialchars($tc['pv_montante'] ?? '') ?> → <?= htmlspecialchars($tc['pv_jusante'] ?? '') ?>
            <?php if ($tc['extensao']): ?>
            <span style="color:var(--muted);font-size:11px"> · <?= number_format($tc['extensao'], 0, ',', '.') ?> m</span>
            <?php endif; ?>
          </span>
          <?php if ($ehHoje): ?>
          <b style="margin-left:auto;color:var(--ok);font-size:11px">hoje</b>
          <?php elseif ($concluido): ?>
          <span style="margin-left:auto;font-size:10px;color:var(--muted)">✅</span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════
         BOTÃO DE DIÁRIO
         ═══════════════════════════════════════════════════ -->
    <div class="sec-tit">📝 Diário de lançamentos</div>

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
      ✏️ Continuar diário — <?= $pct ?>% preenchido
    </a>
    <?php else: ?>
    <div class="info" style="border-color:var(--ok);margin-bottom:14px">
      <div class="info-h">
        <span style="font-size:20px">✅</span>
        <div>
          <b>Diário de hoje enviado</b>
          <span>Aguardando aprovação do Planejador.</span>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php endif; // fim $trechoAtual ?>

    <!-- Fila offline -->
    <div id="offline-sec" class="sec-tit" style="display:none">⏳ Aguardando conexão</div>
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
    <a href="/CHERONCAMARGO/painel/alterar-senha.php" class="btn-sair" style="margin-right:6px">🔑 Senha</a>
    <a href="<?= EXECUTOR_BASE ?>/login.php?sair=1" class="btn-sair">Sair</a>
  </div>

</div><!-- /phone -->

<!-- Overlay OS PDF em tela cheia -->
<div id="pdf-overlay" style="display:none;position:fixed;inset:0;background:#000;z-index:100;flex-direction:column">
  <div style="display:flex;align-items:center;background:#1A2D4F;padding:10px 14px;gap:10px">
    <span style="color:#fff;font-size:13px;font-weight:700;flex:1">📄 Ordem de Serviço</span>
    <button onclick="fecharPdf()" style="border:0;background:#E0A53D;color:#3A2A06;border-radius:8px;padding:7px 14px;font-weight:800;font-size:13px">✕ Fechar</button>
  </div>
  <iframe id="pdf-frame" src="" style="flex:1;border:0;width:100%;height:100%"></iframe>
</div>

<script>
function abrirPdf(url) {
  const ov = document.getElementById('pdf-overlay');
  document.getElementById('pdf-frame').src = url;
  ov.style.display = 'flex';
}
function fecharPdf() {
  const ov = document.getElementById('pdf-overlay');
  ov.style.display = 'none';
  document.getElementById('pdf-frame').src = '';
}
</script>
<script src="<?= EXECUTOR_BASE ?>/assets/js/executor.js"></script>
</body>
</html>
