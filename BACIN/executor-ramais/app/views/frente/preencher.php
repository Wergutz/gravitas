<?php
header('X-Robots-Tag: noindex, nofollow');
$h     = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$num   = fn($v) => number_format((float)$v, 2, ',', '.');
$flash = $_SESSION['flash_ramais'] ?? '';
unset($_SESSION['flash_ramais']);

$enviada = $frente['status'] === 'enviado';
$local   = $trecho
         ? trim(($trecho['pv_montante'] ?? '') . ' → ' . ($trecho['pv_jusante'] ?: '—'))
         : $frente['logradouro'];
$fotoUrl = RAMAIS_BASE . '/uploads/ramais/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1A2D4F">
<meta name="robots" content="noindex,nofollow">
<title>Frente de ramais · BACIN</title>
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
        <?= date('d/m/Y', strtotime($frente['data'])) ?>
      </div>
    </div>
    <div class="hoje">
      <span>📍 <?= $h($frente['logradouro']) ?></span>
      <span class="gps" id="gps-status">📍 verificando…</span>
    </div>
  </div>

  <div class="scroll">

    <?php if ($flash): ?><div class="flash"><?= $h($flash) ?></div><?php endif; ?>

    <?php if (!empty($devolucao)): ?>
      <?= devolucao_aviso_html(
              $devolucao,
              'O escritório devolveu a REDE deste trecho.',
              'A vala da rede vai ser reaberta e o ramal pode ir junto. É só um aviso: você continua lançando os ramais normalmente, mas avise o encarregado.'
          ) ?>
    <?php endif; ?>

    <div class="info">
      <div class="info-h">
        <span class="ic i-navy">🏠</span>
        <div>
          <b><?= $h($local) ?></b>
          <span>
            <?= $trecho ? 'Trecho programado · ' . $h($frente['logradouro']) : 'Rua digitada pelo executor' ?>
            <?php if ($trecho && $trecho['ramais']): ?> · <?= (int)$trecho['ramais'] ?> ramais previstos<?php endif; ?>
          </span>
        </div>
        <span class="badge <?= $enviada ? 'b-ok' : 'b-aviso' ?>" style="margin-left:auto">
          <?= $enviada ? 'enviada' : 'em aberto' ?>
        </span>
      </div>
    </div>

    <div class="totais">
      <div><b><?= count($ramais) ?></b><span>Ramais</span></div>
      <div><b><?= $num($totalVia) ?></b><span>Metros na via</span></div>
      <div><b><?= $num($totalCalcada) ?></b><span>Metros na calçada</span></div>
    </div>

    <?php if (!$enviada): ?>
    <div class="sec-tit"><?= $emEdicao ? '✏️ Editando o ramal ' . $h($emEdicao['numero_imovel']) : '➕ Lançar ramal' ?></div>
    <div class="info">
      <form method="post" action="<?= RAMAIS_BASE ?>/ramal/salvar" enctype="multipart/form-data" id="form-ramal">
        <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
        <input type="hidden" name="frente_id" value="<?= (int)$frente['id'] ?>">
        <input type="hidden" name="ramal_id" value="<?= $emEdicao ? (int)$emEdicao['id'] : '' ?>">
        <input type="hidden" name="lat" id="lat"><input type="hidden" name="lng" id="lng">
        <input type="hidden" name="ts" id="ts">

        <div class="campo">
          <label for="numero_imovel">Número do imóvel <span style="color:#B23A2C">*</span></label>
          <input type="text" name="numero_imovel" id="numero_imovel" maxlength="30" required
                 inputmode="text" placeholder="Ex.: 1234 ou 1234-A"
                 value="<?= $emEdicao ? $h($emEdicao['numero_imovel']) : '' ?>">
          <span class="ajuda">É o número da casa atendida pelo ramal.</span>
        </div>

        <div class="campo">
          <label for="foto_ramal"><?= $h(RamalController::FOTOS['ramal']) ?></label>
          <input type="file" name="foto_ramal" id="foto_ramal" accept="image/*" capture="environment">
          <img class="previa" id="previa_ramal" alt="">
        </div>

        <div class="dupla">
          <div class="campo">
            <label for="pavimento_via">Pavimento da via</label>
            <select name="pavimento_via" id="pavimento_via">
              <option value="">— escolher —</option>
              <?php foreach (RamalController::PAV_VIA as $k => $rot): ?>
                <option value="<?= $h($k) ?>" <?= $emEdicao && $emEdicao['pavimento_via'] === $k ? 'selected' : '' ?>>
                  <?= $h($rot) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="campo">
            <label for="comprimento_via_m">Comprimento na via (m)</label>
            <input type="text" name="comprimento_via_m" id="comprimento_via_m" inputmode="decimal"
                   placeholder="0,00" value="<?= $emEdicao ? $num($emEdicao['comprimento_via_m']) : '' ?>">
          </div>
        </div>

        <div class="campo">
          <label for="foto_lancamento_via"><?= $h(RamalController::FOTOS['lancamento_via']) ?></label>
          <input type="file" name="foto_lancamento_via" id="foto_lancamento_via" accept="image/*" capture="environment">
          <img class="previa" id="previa_lancamento_via" alt="">
        </div>

        <div class="dupla">
          <div class="campo">
            <label for="pavimento_calcada">Pavimento da calçada</label>
            <select name="pavimento_calcada" id="pavimento_calcada">
              <option value="">— escolher —</option>
              <?php foreach (RamalController::PAV_CALCADA as $k => $rot): ?>
                <option value="<?= $h($k) ?>" <?= $emEdicao && $emEdicao['pavimento_calcada'] === $k ? 'selected' : '' ?>>
                  <?= $h($rot) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="campo">
            <label for="comprimento_calcada_m">Comprimento na calçada (m)</label>
            <input type="text" name="comprimento_calcada_m" id="comprimento_calcada_m" inputmode="decimal"
                   placeholder="0,00" value="<?= $emEdicao ? $num($emEdicao['comprimento_calcada_m']) : '' ?>">
          </div>
        </div>

        <div class="campo">
          <label for="foto_acabado"><?= $h(RamalController::FOTOS['acabado']) ?></label>
          <input type="file" name="foto_acabado" id="foto_acabado" accept="image/*" capture="environment">
          <img class="previa" id="previa_acabado" alt="">
        </div>

        <div class="campo">
          <label for="observacao">Observação (opcional)</label>
          <input type="text" name="observacao" id="observacao" maxlength="255"
                 value="<?= $emEdicao ? $h($emEdicao['observacao']) : '' ?>">
        </div>

        <button class="btn gold" type="submit" id="btn-salvar">
          <?= $emEdicao ? 'Salvar alterações' : 'Salvar ramal' ?>
        </button>
        <?php if ($emEdicao): ?>
          <a class="btn claro" href="<?= RAMAIS_BASE ?>/frente/<?= (int)$frente['id'] ?>">Cancelar edição</a>
        <?php endif; ?>
      </form>
    </div>
    <?php endif; ?>

    <?php if ($trecho): ?>
    <div class="sec-tit">🔩 Pontões deixados pela rede (<?= count($pontoes) ?>)</div>
    <?php if (!$pontoes): ?>
      <div class="info"><div class="info-h">
        <span class="ic i-aviso">❔</span>
        <div><b>Nenhum pontão registrado neste trecho</b>
        <span>A equipe de rede não lançou pontões no diário, ou o trecho foi executado antes desse controle.</span></div>
      </div></div>
    <?php else: foreach ($pontoes as $p): ?>
      <div class="ramal-card">
        <div class="cab">
          <span class="num">Nº <?= $h($p['nro_residencia'] ?: '—') ?></span>
          <span class="badge <?= $p['ja_lancado'] ? 'b-ok' : 'b-info' ?>" style="margin-left:auto">
            <?= $p['ja_lancado'] ? 'ramal lançado' : 'a executar' ?>
          </span>
        </div>
        <div class="med">
          Profundidade: <b><?= $p['profundidade_m'] !== null ? $num($p['profundidade_m']) . ' m' : '—' ?></b>
          · rede em <?= $p['data_rede'] ? date('d/m/Y', strtotime($p['data_rede'])) : '—' ?>
          <?php if ($p['observacao']): ?><br><span style="color:#6B7686"><?= $h($p['observacao']) ?></span><?php endif; ?>
          <?php if ($p['lat'] && $p['lng']): ?>
            <br><a href="https://www.google.com/maps?q=<?= rawurlencode($p['lat'] . ',' . $p['lng']) ?>"
                   target="_blank" rel="noopener">📍 ver no mapa</a>
          <?php endif; ?>
        </div>
        <?php if ($p['foto_url']): ?>
          <div class="miniaturas"><div class="mini" style="width:50%">
            <a href="<?= $h($p['foto_url']) ?>" target="_blank" rel="noopener">
              <img src="<?= $h($p['foto_url']) ?>" alt="Foto do pontão" loading="lazy">
            </a>
          </div></div>
        <?php endif; ?>
        <?php if (!$enviada && !$p['ja_lancado'] && $p['nro_residencia']): ?>
          <div class="acoes">
            <a href="#form-ramal" onclick="usarPontao('<?= $h(addslashes($p['nro_residencia'])) ?>')">
              Lançar o ramal deste pontão
            </a>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; endif; ?>
    <?php endif; ?>

    <div class="sec-tit">🏘 Ramais desta frente (<?= count($ramais) ?>)</div>

    <?php if (!$ramais): ?>
      <div class="info"><div class="info-h">
        <span class="ic i-info">📭</span>
        <div><b>Nenhum ramal lançado</b><span>Preencha o formulário acima para lançar o primeiro.</span></div>
      </div></div>
    <?php else: foreach ($ramais as $r):
      $fr = $fotos[(int)$r['id']] ?? [];
      $faltando = array_values(array_diff(array_keys(RamalController::FOTOS), array_keys($fr)));
    ?>
      <div class="ramal-card">
        <div class="cab">
          <span class="seq"><?= (int)$r['sequencia'] ?></span>
          <span class="num">Nº <?= $h($r['numero_imovel']) ?></span>
          <?php if ($faltando): ?>
            <span class="badge b-aviso" style="margin-left:auto"><?= count($faltando) ?> foto(s) faltando</span>
          <?php else: ?>
            <span class="badge b-ok" style="margin-left:auto">completo</span>
          <?php endif; ?>
        </div>
        <div class="med">
          Via: <b><?= $h(RamalController::PAV_VIA[$r['pavimento_via']] ?? '—') ?></b>,
          <b><?= $num($r['comprimento_via_m']) ?> m</b><br>
          Calçada: <b><?= $h(RamalController::PAV_CALCADA[$r['pavimento_calcada']] ?? '—') ?></b>,
          <b><?= $num($r['comprimento_calcada_m']) ?> m</b>
          <?php if ($r['observacao']): ?><br><span style="color:#6B7686"><?= $h($r['observacao']) ?></span><?php endif; ?>
        </div>
        <div class="miniaturas">
          <?php foreach (RamalController::FOTOS as $tipo => $rotulo):
            $f = $fr[$tipo] ?? null; ?>
            <div class="mini">
              <?php if ($f): ?>
                <a href="<?= $fotoUrl . rawurlencode($f['filename']) ?>" target="_blank" rel="noopener">
                  <img src="<?= $fotoUrl . ($f['thumb'] ? 'thumbs/' . rawurlencode($f['thumb']) : rawurlencode($f['filename'])) ?>"
                       alt="<?= $h($rotulo) ?>" loading="lazy">
                </a>
              <?php else: ?>
                <?= $h($rotulo) ?>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if (!$enviada): ?>
        <div class="acoes">
          <a href="<?= RAMAIS_BASE ?>/frente/<?= (int)$frente['id'] ?>/ramal/<?= (int)$r['id'] ?>">Editar / trocar fotos</a>
          <form method="post" action="<?= RAMAIS_BASE ?>/ramal/excluir" style="flex:1"
                onsubmit="return confirm('Excluir o ramal nº <?= $h($r['numero_imovel']) ?>?')">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
            <input type="hidden" name="frente_id" value="<?= (int)$frente['id'] ?>">
            <input type="hidden" name="ramal_id" value="<?= (int)$r['id'] ?>">
            <button class="perigo" type="submit" style="width:100%">Excluir</button>
          </form>
        </div>
        <?php endif; ?>
      </div>
    <?php endforeach; endif; ?>

    <div class="rodape">
      <?php if (!$enviada): ?>
        <form method="post" action="<?= RAMAIS_BASE ?>/frente/<?= (int)$frente['id'] ?>/encerrar"
              onsubmit="return confirm('Encerrar a frente e enviar ao escritório? Depois não dá para alterar.')">
          <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
          <div class="campo">
            <label for="obs">Observação da frente (opcional)</label>
            <input type="text" name="obs" id="obs" maxlength="2000">
          </div>
          <button class="btn ok" type="submit">Encerrar frente e enviar</button>
        </form>
      <?php endif; ?>
      <a class="btn claro" href="<?= RAMAIS_BASE ?>/">Voltar</a>
    </div>
  </div>
</div>

<script>
function usarPontao(numero) {
  var campo = document.getElementById('numero_imovel');
  if (!campo) return;
  campo.value = numero;
  campo.scrollIntoView({ behavior: 'smooth', block: 'center' });
  campo.focus();
}
</script>
<script src="<?= RAMAIS_BASE ?>/assets/js/ramais.js"></script>
</body>
</html>
