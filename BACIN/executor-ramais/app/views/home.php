<?php
header('X-Robots-Tag: noindex, nofollow');
$hoje   = date('d/m/Y');
$diaSem = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][date('w')];
$flash  = $_SESSION['flash_ramais'] ?? '';
unset($_SESSION['flash_ramais']);
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1A2D4F">
<meta name="robots" content="noindex,nofollow">
<title>Ramais · BACIN</title>
<link rel="stylesheet" href="<?= RAMAIS_BASE ?>/assets/css/ramais.css">
</head>
<body>
<div class="phone">

  <div class="top">
    <div class="top-row">
      <img class="logo" src="/BACIN/painel/assets/img/icon-bacin-white.svg?v=2" alt="BACIN">
      <div class="nm">BACIN<small>EXECUTOR · RAMAIS</small></div>
      <div class="eq">
        <b><?= $h($_SESSION['nome'] ?? '') ?></b>
        <?= $diaSem ?>, <?= $hoje ?>
      </div>
    </div>
    <div class="hoje">
      <span>👷 <?= $equipe ? $h($equipe['nome']) : 'Sem equipe' ?></span>
      <span class="gps" id="gps-status">📍 verificando…</span>
    </div>
  </div>

  <div class="scroll">

    <?php if ($flash): ?><div class="flash"><?= $h($flash) ?></div><?php endif; ?>

    <?php foreach (($devolucoesTrechos ?? []) as $dev): ?>
      <?= devolucao_aviso_html(
              $dev,
              'O escritório devolveu a REDE do trecho ' . ($dev['trecho_nome'] ?? ''),
              'A vala da rede vai ser reaberta, então o ramal deste trecho pode ir junto. Fale com o encarregado antes de dar o serviço por pronto — você pode continuar lançando normalmente.'
          ) ?>
    <?php endforeach; ?>

    <?php if (!$equipe): ?>
      <div class="info">
        <div class="info-h">
          <span class="ic i-aviso">⚠️</span>
          <div>
            <b>Você ainda não é responsável por uma equipe</b>
            <span>Peça ao planejador para colocar você como responsável da equipe de ramais.</span>
          </div>
        </div>
      </div>
    <?php else: ?>

    <div class="sec-tit">➕ Começar uma frente de ramais</div>
    <div class="info">
      <form method="post" action="<?= RAMAIS_BASE ?>/frente/nova">
        <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">

        <div class="campo">
          <label for="trecho_id">Trecho programado (se houver)</label>
          <select name="trecho_id" id="trecho_id">
            <option value="">— sem trecho, vou digitar a rua —</option>
            <?php foreach ($trechos as $t): ?>
              <option value="<?= (int)$t['id'] ?>" data-rua="<?= $h($t['rua']) ?>">
                <?= $h($t['pv_montante']) ?> → <?= $h($t['pv_jusante'] ?: '—') ?>
                <?= $t['rua'] ? ' · ' . $h($t['rua']) : '' ?>
                <?= $t['ramais'] ? ' · ' . (int)$t['ramais'] . ' ramais previstos' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <span class="ajuda">Escolhendo o trecho, a rua vem preenchida e os ramais entram na medição dele.</span>
        </div>

        <div class="campo">
          <label for="logradouro">Rua / avenida <span style="color:#B23A2C">*</span></label>
          <input type="text" name="logradouro" id="logradouro" maxlength="160"
                 placeholder="Ex.: Rua Barão do Amazonas" autocomplete="street-address">
        </div>

        <button class="btn gold" type="submit">Abrir frente</button>
      </form>
    </div>

    <div class="sec-tit">📋 Suas frentes</div>
    <?php if (!$frentes): ?>
      <div class="info"><div class="info-h">
        <span class="ic i-info">📭</span>
        <div><b>Nenhuma frente lançada ainda</b><span>Abra a primeira acima.</span></div>
      </div></div>
    <?php else: foreach ($frentes as $f):
      $enviada = $f['status'] === 'enviado';
      $local   = $f['trecho_id']
               ? trim(($f['pv_montante'] ?? '') . ' → ' . ($f['pv_jusante'] ?: '—')) . ' · ' . $f['logradouro']
               : $f['logradouro'];
    ?>
      <a class="ramal-card" style="display:block;text-decoration:none;color:inherit"
         href="<?= RAMAIS_BASE ?>/frente/<?= (int)$f['id'] ?>">
        <div class="cab">
          <span class="num"><?= $h($local) ?></span>
          <span class="badge <?= $enviada ? 'b-ok' : 'b-aviso' ?>" style="margin-left:auto">
            <?= $enviada ? 'enviada' : 'em aberto' ?>
          </span>
        </div>
        <div class="med">
          <?= date('d/m/Y', strtotime($f['data'])) ?> ·
          <b><?= (int)$f['ramais_lancados'] ?></b> ramal(is)
          <?php if ($enviada): ?>
            · via <b><?= number_format((float)$f['total_via_m'], 2, ',', '.') ?> m</b>
            · calçada <b><?= number_format((float)$f['total_calcada_m'], 2, ',', '.') ?> m</b>
          <?php endif; ?>
        </div>
      </a>
    <?php endforeach; endif; ?>

    <?php endif; ?>

    <div class="rodape">
      <a class="btn claro" href="/BACIN/painel/alterar-senha.php">🔑 Trocar minha senha</a>
      <a class="btn claro" href="/BACIN/painel/logout.php">Sair</a>
    </div>
  </div>
</div>

<script>
// Preenche a rua ao escolher o trecho e mostra o estado do GPS.
document.getElementById('trecho_id')?.addEventListener('change', function () {
  var rua = this.selectedOptions[0]?.dataset.rua || '';
  var campo = document.getElementById('logradouro');
  if (rua && !campo.value) campo.value = rua;
});
(function () {
  var el = document.getElementById('gps-status');
  if (!el || !navigator.geolocation) { if (el) el.textContent = '📍 sem GPS'; return; }
  navigator.geolocation.getCurrentPosition(
    function () { el.textContent = '📍 GPS ok'; },
    function () { el.textContent = '📍 GPS desligado'; },
    { enableHighAccuracy: true, timeout: 8000 }
  );
})();
</script>
</body>
</html>
