<?php
header('X-Robots-Tag: noindex, nofollow');
$hoje   = date('d/m/Y');
$diaSem = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][date('w')];
$csrf   = $_SESSION['csrf_token'] ?? csrf_token_repav();

/** número em português com vírgula decimal */
function nbr(?float $v, int $dec = 2): string {
    return number_format((float)$v, $dec, ',', '.');
}

/** "19/09/2026 às 14h03" */
function quando(?string $ts): string {
    if (!$ts) return '';
    $t = strtotime($ts);
    return $t ? date('d/m/Y', $t) . ' às ' . date('H\\hi', $t) : '';
}

/**
 * Aviso de devolução do escritório (tabela trecho_devolucoes).
 * @param array  $dv     linha da devolução
 * @param string $escopo rede|ramais
 */
function avisoDevolucao(array $dv, string $escopo): void {
    $quem = trim((string)($dv['usuario_nome'] ?? '')) ?: 'o escritório';
    ?>
    <div class="devol">
      <span class="ic">🔁</span>
      <div>
        <b>Serviço devolvido pelo escritório — refazer <?= $escopo === 'ramais' ? 'a reposição dos ramais' : 'a reposição da rede' ?></b>
        <span class="motivo">Motivo: <?= htmlspecialchars($dv['motivo'] ?? '') ?></span>
        <?php if (!empty($dv['etapa_rotulo'])): ?>
        <span>Etapa devolvida: <?= htmlspecialchars($dv['etapa_rotulo']) ?></span>
        <?php endif; ?>
        <span>Quem devolveu: <?= htmlspecialchars($quem) ?><?php
          $q = quando($dv['created_at'] ?? null);
          if ($q) echo ' · ' . htmlspecialchars($q);
        ?></span>
        <span>Volte na frente, corrija o que foi apontado e faça um diário novo desta repavimentação.</span>
      </div>
    </div>
    <?php
}

/**
 * Cartão de um trecho da fila.
 * @param array       $t        linha da fila
 * @param int         $pos      posição na fila (1 = primeiro)
 * @param string      $escopo   rede|ramais
 * @param array|null  $diario   diário de hoje deste trecho/escopo
 */
function cartaoFila(array $t, int $pos, string $escopo, ?array $diario, string $csrf, ?array $devol = null): void {
    $emExec  = ($t['status_escopo'] ?? '') === 'execucao';
    $enviado = $diario && $diario['status'] !== 'rascunho';
    $partes  = array_filter([
        $t['rua'] ?? null,
        !empty($t['bacia']) ? 'Bacia ' . $t['bacia'] : null,
    ]);
    ?>
    <div class="fila-card<?= $pos === 1 ? ' primeiro' : '' ?>">
      <div class="fila-h">
        <span class="pos"><?= (int)$pos ?>º</span>
        <div class="fila-tt">
          <b><?= htmlspecialchars($t['pv_montante'] ?? '?') ?> <span class="seta">→</span> <?= htmlspecialchars($t['pv_jusante'] ?? '?') ?></b>
          <span><?= htmlspecialchars(implode(' · ', $partes)) ?></span>
        </div>
        <?php if ($devol): ?>
          <span class="badge b-aviso">devolvido</span>
        <?php elseif ($enviado): ?>
          <span class="badge b-ok">medido</span>
        <?php elseif ($emExec): ?>
          <span class="badge b-info">em execução</span>
        <?php else: ?>
          <span class="badge b-neutro">na fila</span>
        <?php endif; ?>
      </div>

      <?php if ($devol) avisoDevolucao($devol, $escopo); ?>

      <div class="fila-dados">
        <span><b>Extensão</b><?= nbr((float)($t['extensao'] ?? 0), 2) ?> m</span>
        <?php if ($escopo === 'ramais'): ?>
        <span><b>Ramais</b><?= (int)($t['qtd_ramais'] ?? 0) ?></span>
        <span><b>Via</b><?= nbr((float)($t['via_m'] ?? 0), 2) ?> m</span>
        <span><b>Calçada</b><?= nbr((float)($t['calcada_m'] ?? 0), 2) ?> m</span>
        <?php endif; ?>
        <?php if (!empty($t['contrato'])): ?>
        <span><b>Contrato</b><?= htmlspecialchars($t['contrato']) ?></span>
        <?php endif; ?>
      </div>

      <?php if ($enviado): ?>
        <div class="fila-ok">
          ✅ Diário enviado — <?= nbr((float)$diario['area_total_m2'], 2) ?> m²
          <?php if ((float)$diario['volume_asf_m3'] > 0): ?>
            · asfalto <?= nbr((float)$diario['volume_asf_m3'], 3) ?> m³
          <?php endif; ?>
        </div>
      <?php elseif ($diario): ?>
        <a class="btn-fila continuar" href="<?= REPAV_BASE ?>/diario/<?= (int)$diario['id'] ?>">
          ✏️ Continuar diário (passo <?= (int)$diario['step_atual'] ?> de 19)
        </a>
      <?php else: ?>
        <form method="post" action="<?= REPAV_BASE ?>/diario/novo" style="margin:0">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="trecho_id" value="<?= (int)$t['id'] ?>">
          <input type="hidden" name="escopo" value="<?= htmlspecialchars($escopo) ?>">
          <button type="submit" class="btn-fila">
            <?= $escopo === 'rede' ? '🛣 Abrir diário da rede' : '🏠 Abrir diário dos ramais' ?>
          </button>
        </form>
      <?php endif; ?>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1A2D4F">
<meta name="robots" content="noindex,nofollow">
<meta name="csrf" content="<?= htmlspecialchars($csrf) ?>">
<title>Repavimentação · BACIN</title>
<link rel="stylesheet" href="<?= REPAV_BASE ?>/assets/css/repav.css">
</head>
<body>
<div class="phone">

  <div class="top">
    <div class="top-row">
      <img class="logo" src="/BACIN/painel/assets/img/icon-bacin-white.svg?v=2" alt="BACIN">
      <div class="nm">BACIN<small>EXECUTOR · REPAVIMENTAÇÃO</small></div>
      <div class="eq">
        <b><?= htmlspecialchars($_SESSION['nome'] ?? '') ?></b>
        <?= $diaSem ?>, <?= $hoje ?>
      </div>
    </div>
    <div class="hoje">
      <span>📅 <?= $caminhamento ? date('d/m/Y', strtotime($caminhamento['data_execucao'])) : 'Fila de repavimentação' ?></span>
      <span class="gps" id="gps-status">📍 verificando…</span>
    </div>
  </div>

  <div class="scroll">

    <?php if (!$equipeId): ?>
    <div class="info" style="border-color:var(--aviso)">
      <div class="info-h">
        <span class="ic i-aviso">⚠️</span>
        <div><b>Sem equipe vinculada</b><span>Peça ao Planejador para vincular você a uma equipe de pavimentação.</span></div>
      </div>
    </div>
    <?php else: ?>

    <?php
      $devolucoes  = $devolucoes ?? [];
      $devolNaFila = 0;
      foreach ($filaRede   as $__t) if (isset($devolucoes[$__t['id'] . '|rede']))   $devolNaFila++;
      foreach ($filaRamais as $__t) if (isset($devolucoes[$__t['id'] . '|ramais'])) $devolNaFila++;
    ?>
    <?php if ($devolNaFila > 0): ?>
    <div class="devol devol-topo">
      <span class="ic">🔁</span>
      <div>
        <b><?= (int)$devolNaFila ?> <?= $devolNaFila > 1 ? 'repavimentações devolvidas' : 'repavimentação devolvida' ?> pelo escritório</b>
        <span>Veja o motivo no cartão do trecho e refaça o serviço antes de medir de novo.</span>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($totalLiberados > 0): ?>
    <div class="aviso-fila">
      <span class="ic">⚠️</span>
      <div>
        <b><?= (int)$totalLiberados ?> trecho<?= $totalLiberados > 1 ? 's' : '' ?> liberado<?= $totalLiberados > 1 ? 's' : '' ?> esperando repavimentação</b>
        <span>
          <?= count($filaRede) ?> de rede · <?= count($filaRamais) ?> de ramais.
          Comece pelo 1º da fila — não é preciso esperar o caminhamento do Planejador.
        </span>
      </div>
    </div>
    <?php else: ?>
    <div class="info">
      <div class="info-h">
        <span class="ic i-ok">✅</span>
        <div><b>Nenhum trecho na fila</b><span>Assim que a rede ou os ramais forem concluídos, o trecho aparece aqui.</span></div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($caminhamento && $trechoAtual): ?>
    <div class="sec-tit">⭐ Caminhamento publicado — prioridade de hoje</div>
    <div class="info" style="border-color:var(--gold)">
      <div class="info-h">
        <span class="ic i-gold" style="font-size:18px">🛣</span>
        <div>
          <b><?= htmlspecialchars($trechoAtual['pv_montante'] ?? '?') ?> → <?= htmlspecialchars($trechoAtual['pv_jusante'] ?? '?') ?></b>
          <span><?= htmlspecialchars($trechoAtual['rua'] ?? '') ?> · ordem <?= (int)$trechoAtual['ordem'] ?> do caminhamento</span>
        </div>
      </div>
      <?php if ($pavimentos): ?>
      <div style="margin-top:10px">
        <?php foreach ($pavimentos as $pav): ?>
        <div class="pav-li">
          <?= htmlspecialchars($pav['tipo_pavimento']) ?>
          <?php if ($pav['espessura_cm']): ?>
          <span style="color:var(--muted);font-size:11px"> · esp. <?= nbr((float)$pav['espessura_cm'], 1) ?> cm</span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── Fila da REDE ───────────────────────────────────── -->
    <div class="sec-tit">🛣 Repavimentação da REDE <span class="cnt"><?= count($filaRede) ?></span></div>
    <?php if (!$filaRede): ?>
    <div class="info"><div class="info-h"><span class="ic i-neutro">—</span>
      <div><b>Fila vazia</b><span>Nenhum trecho com a rede concluída aguardando reposição.</span></div></div></div>
    <?php else: ?>
      <?php foreach ($filaRede as $i => $t):
        cartaoFila($t, $i + 1, 'rede', $diariosHoje[$t['id'] . '|rede'] ?? null, $csrf,
                   $devolucoes[$t['id'] . '|rede'] ?? null);
      endforeach; ?>
    <?php endif; ?>

    <!-- ── Fila de RAMAIS ─────────────────────────────────── -->
    <div class="sec-tit">🏠 Repavimentação de RAMAIS <span class="cnt"><?= count($filaRamais) ?></span></div>
    <?php if (!$filaRamais): ?>
    <div class="info"><div class="info-h"><span class="ic i-neutro">—</span>
      <div><b>Fila vazia</b><span>Nenhum trecho com os ramais concluídos aguardando reposição.</span></div></div></div>
    <?php else: ?>
      <?php foreach ($filaRamais as $i => $t):
        cartaoFila($t, $i + 1, 'ramais', $diariosHoje[$t['id'] . '|ramais'] ?? null, $csrf,
                   $devolucoes[$t['id'] . '|ramais'] ?? null);
      endforeach; ?>
    <?php endif; ?>

    <?php if ($filaCaminh): ?>
    <div class="sec-tit">📈 Ordem do caminhamento</div>
    <div class="info">
      <div style="margin-top:2px">
        <?php foreach ($filaCaminh as $tc):
          $concluido = $tc['ct_status'] === 'concluido';
          $ehHoje    = ($tc['id'] == ($trechoAtual['id'] ?? -1));
        ?>
        <div class="next">
          <span class="o" style="<?= $ehHoje ? 'background:var(--ok-bg);color:var(--ok)' : ($concluido ? 'background:#e0e0e0;color:#aaa' : '') ?>"><?= (int)$tc['ordem'] ?></span>
          <span style="<?= $concluido ? 'color:var(--muted);text-decoration:line-through' : '' ?>">
            <?= htmlspecialchars($tc['pv_montante'] ?? '') ?> → <?= htmlspecialchars($tc['pv_jusante'] ?? '') ?>
          </span>
          <?php if ($ehHoje): ?><b style="margin-left:auto;color:var(--ok);font-size:11px">hoje</b>
          <?php elseif ($concluido): ?><span style="margin-left:auto;font-size:10px;color:var(--muted)">✅</span><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php endif; // equipeId ?>

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
      <?= htmlspecialchars($_SESSION['nome'] ?? '') ?><br>
      <span id="conn-badge">🟢 Online</span>
    </div>
    <a href="/BACIN/painel/alterar-senha.php" class="btn-sair" style="margin-right:6px">🔑 Senha</a>
    <a href="/BACIN/painel/logout.php" class="btn-sair">Sair</a>
  </div>

</div>
<script src="<?= REPAV_BASE ?>/assets/js/repav.js"></script>
</body>
</html>
