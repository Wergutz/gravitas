<?php
$usuario    = $_SESSION['user'] ?? null;
$iniciais   = strtoupper(substr($usuario['nome'] ?? 'M', 0, 1));
$hoje_fmt   = date('d/m/Y');
$repavPeriodo = $repavPeriodo ?? null;

function seloStatus(string $camStatus, ?string $diarioStatus): string {
    if ($diarioStatus === 'enviado')  return '<span class="selo s-conc">enviado</span>';
    if ($diarioStatus === 'rascunho') return '<span class="selo s-exec">em execução</span>';
    if ($camStatus === 'publicado')   return '<span class="selo s-pub">publicado</span>';
    if ($camStatus === 'execucao')    return '<span class="selo s-exec">em execução</span>';
    return '<span class="selo s-rasc">sem programação</span>';
}
function fmtM(float $v): string { return number_format($v, 0, ',', '.'); }
function fmtM1(float $v): string { return number_format($v, 1, ',', '.'); }
function svgIcon(string $path): string {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Visão Executiva · GRAVITAS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= MASTER_BASE ?>/assets/css/master.css">
</head>
<body>
<div class="app">

<aside class="sidebar">
  <div class="brand">
    <svg viewBox="-4 -4 108 108" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <ellipse cx="50" cy="50" rx="54" ry="19" fill="none" stroke="#B9C1CC" stroke-width="3.5" transform="rotate(-24 50 50)"/>
      <circle cx="50" cy="50" r="44" fill="#1A2D4F" stroke="#B9C1CC" stroke-width="2.5"/>
      <circle cx="92.8" cy="23.1" r="4.5" fill="#C9A227"/>
      <path d="M 26.7 73.3 A 33 33 0 1 1 73.3 73.3" fill="none" stroke="#FFFFFF" stroke-width="6" stroke-linecap="round"/>
      <path d="M 50 50 L 67.4 29.3 L 55.5 53.5 Z" fill="#C9A227"/>
      <circle cx="50" cy="50" r="6.5" fill="#C9A227"/>
    </svg>
    <div><b>GRAVITAS</b><small>VISÃO EXECUTIVA</small></div>
  </div>
  <nav class="nav">
    <a href="<?= MASTER_BASE ?>/?modo=rt" class="<?= $modo==='rt'?'ativo':'' ?>">
      <?= svgIcon('<rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="8" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/><rect x="13" y="13" width="8" height="8" rx="2"/>') ?>
      Visão geral
    </a>
    <a href="<?= MASTER_BASE ?>/?modo=dia" class="<?= $modo==='dia'?'ativo':'' ?>">
      <?= svgIcon('<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>') ?>
      Produção do dia
    </a>
    <a href="<?= MASTER_BASE ?>/?modo=periodo" class="<?= $modo==='periodo'?'ativo':'' ?>">
      <?= svgIcon('<line x1="5" y1="20" x2="5" y2="12"/><line x1="12" y1="20" x2="12" y2="5"/><line x1="19" y1="20" x2="19" y2="9"/>') ?>
      Acumulado
    </a>
    <a href="<?= MASTER_BASE ?>/relatorio/boletim?inicio=<?= $inicio ?? date('Y-m-d',strtotime('-30 days')) ?>&fim=<?= $fim ?? date('Y-m-d') ?>">
      <?= svgIcon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>') ?>
      Relatórios
    </a>
    <a href="/principal/painel/alterar-senha.php">
      <?= svgIcon('<circle cx="8" cy="14" r="4.5"/><path d="M11.5 10.5 20 2m-3.5 1.5 3 3M14 8l2.5 2.5"/>') ?>
      Alterar Senha
    </a>
    <a href="/principal/painel/logout.php" class="sair">
      <?= svgIcon('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>') ?>
      Sair
    </a>
  </nav>
</aside>

<main>
  <div class="topo">
    <div>
      <h1>Visão executiva da obra</h1>
      <p>Sistema Gravitas · Painel do gestor · <?= $hoje_fmt ?></p>
    </div>
    <div class="perfil">
      <div class="avatar"><?= htmlspecialchars($iniciais) ?></div>
      <span><b><?= htmlspecialchars($usuario['nome'] ?? 'Master') ?></b><small>PERFIL MASTER</small></span>
    </div>
  </div>

  <!-- abas de modo -->
  <div class="modos">
    <a href="<?= MASTER_BASE ?>/?modo=rt" class="modo <?= $modo==='rt'?'on':'' ?>">
      <?= $modo==='rt' ? '<span class="pulse"></span>' : '' ?>Tempo real
    </a>
    <a href="<?= MASTER_BASE ?>/?modo=dia&data=<?= $data ?? date('Y-m-d') ?>" class="modo <?= $modo==='dia'?'on':'' ?>">
      Produção do dia
    </a>
    <a href="<?= MASTER_BASE ?>/?modo=periodo&inicio=<?= $inicio ?? date('Y-m-d',strtotime('-30 days')) ?>&fim=<?= $fim ?? date('Y-m-d') ?>" class="modo <?= $modo==='periodo'?'on':'' ?>">
      Acumulado por período
    </a>
  </div>

  <?php if ($modo === 'dia'): ?>
  <form class="periodo show" method="get" action="<?= MASTER_BASE ?>/">
    <input type="hidden" name="modo" value="dia">
    <label>Data:</label>
    <input type="date" name="data" value="<?= htmlspecialchars($data) ?>">
    <div class="ap"><button type="submit" class="btn-sec">Aplicar</button></div>
  </form>
  <?php elseif ($modo === 'periodo'): ?>
  <form class="periodo show" method="get" action="<?= MASTER_BASE ?>/">
    <input type="hidden" name="modo" value="periodo">
    <label>Período de medição:</label>
    <input type="date" name="inicio" value="<?= htmlspecialchars($inicio) ?>">
    <span style="color:var(--muted)">até</span>
    <input type="date" name="fim" value="<?= htmlspecialchars($fim) ?>">
    <div class="ap"><button type="submit" class="btn-sec">Aplicar</button></div>
  </form>
  <?php endif; ?>

  <?php if ($modo === 'rt'): ?>
  <!-- ══════════════════════════════════════════ TEMPO REAL -->
  <div class="kpis">
    <div class="kpi">
      <div class="ic ic-info"><?= svgIcon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>') ?></div>
      <b><?= $equipesCampo ?><span style="font-size:14px;color:var(--muted)">/<?= $totalEquipes ?></span></b>
      <span>equipes em campo agora</span>
      <?php if ($equipesCampo < $totalEquipes): ?>
      <div class="delta d-aviso"><?= $totalEquipes - $equipesCampo ?> sem início</div>
      <?php endif; ?>
    </div>
    <div class="kpi">
      <div class="ic ic-ok"><?= svgIcon('<line x1="5" y1="20" x2="5" y2="12"/><line x1="12" y1="20" x2="12" y2="5"/><line x1="19" y1="20" x2="19" y2="9"/>') ?></div>
      <b><?= fmtM($metrosHoje) ?> m</b>
      <span>executado até agora (hoje)</span>
    </div>
    <div class="kpi">
      <div class="ic ic-navy"><?= svgIcon('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>') ?></div>
      <b><?= $presentes ?><span style="font-size:14px;color:var(--muted)">/<?= $presentes+$ausentes ?></span></b>
      <span>presentes em campo</span>
      <?php if ($ausentes > 0): ?><div class="delta d-neutro"><?= $ausentes ?> ausente(s)/atraso</div><?php endif; ?>
    </div>
    <div class="kpi">
      <div class="ic <?= $totalAlertas > 0 ? 'ic-erro' : 'ic-ok' ?>"><?= svgIcon('<path d="M12 3 2.5 19.5h19z"/><line x1="12" y1="10" x2="12" y2="14"/><line x1="12" y1="16.5" x2="12.01" y2="16.5"/>') ?></div>
      <b><?= $totalAlertas ?></b>
      <span>pendências travando</span>
      <?php if ($totalAlertas === 0): ?><div class="delta d-ok">Tudo OK</div><?php endif; ?>
    </div>
  </div>

  <div class="grade">
    <div>
      <div class="card">
        <p class="label">Equipes em campo agora
          <?php if ($ultimaSinc): ?>
          <span class="liv">● ao vivo · <?= date('H:i', strtotime($ultimaSinc)) ?></span>
          <?php endif; ?>
        </p>
        <?php if (empty($equipes)): ?>
        <p style="color:var(--muted);font-size:13px">Nenhuma equipe ativa cadastrada.</p>
        <?php else: foreach ($equipes as $eq): ?>
        <div class="eqrow">
          <span class="nm"><?= htmlspecialchars($eq['nome']) ?></span>
          <div class="tr">
            <?php if ($eq['pv_montante']): ?>
            <div class="pv"><?= htmlspecialchars($eq['pv_montante']) ?> → <?= htmlspecialchars($eq['pv_jusante']) ?></div>
            <div class="mini"><?= htmlspecialchars($eq['rua'] ?? '') ?></div>
            <?php if ((int)$eq['total_trechos'] > 0): ?>
            <?php $pct = round((int)$eq['trechos_concluidos'] / (int)$eq['total_trechos'] * 100); ?>
            <div class="barra"><i style="width:<?= $pct ?>%"></i></div>
            <?php endif; ?>
            <?php else: ?>
            <div class="mini" style="color:var(--muted)">Sem trecho em execução</div>
            <?php endif; ?>
          </div>
          <?= seloStatus($eq['cam_status'] ?? '', $eq['diario_status'] ?? null) ?>
        </div>
        <?php endforeach; endif; ?>
      </div>

      <?php if (!empty($interfs)): ?>
      <div class="card">
        <p class="label">Interferências encontradas hoje</p>
        <?php
        $interf_nomes = ['pedra'=>'Pedra','agua_na_vala'=>'Água na vala','ramal_de_agua'=>'Ramal de água',
          'rede_de_agua'=>'Rede de água','rede_pluvial'=>'Rede pluvial','rompimento_de_rede'=>'Rompimento de rede',
          'rede_cloacal_existente'=>'Rede cloacal existente','rede_logica'=>'Rede lógica',
          'rede_eletrica'=>'Rede elétrica','outros'=>'Outros'];
        ?>
        <div class="alerta a-aviso">⚠ <?= $totalInterfs ?> interferência(s) registrada(s) hoje
          <small><?= implode(' · ', array_map(fn($i) => ($interf_nomes[$i['tipo']] ?? $i['tipo']) . ' (' . $i['qtd'] . ')', $interfs)) ?></small>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div>
      <?php if ($totalAlertas > 0): ?>
      <div class="card">
        <p class="label">Pendências que travam a produção</p>
        <?php if ($alertasMat > 0): ?>
        <div class="alerta a-erro">📦 <?= $alertasMat ?> alerta(s) de material faltando<small>Verificar no Planejador → Diários</small></div>
        <?php endif; ?>
        <?php if ($trechosSemOs > 0): ?>
        <div class="alerta a-erro">📄 <?= $trechosSemOs ?> trecho(s) sem OS ativa</div>
        <?php endif; ?>
        <?php if ($docsVencer > 0): ?>
        <div class="alerta a-aviso">🪪 <?= $docsVencer ?> documento(s) vencendo em 15 dias</div>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="card"><div class="alerta a-ok">✅ Sem pendências travando a produção</div></div>
      <?php endif; ?>

      <div class="card">
        <p class="label">Equipamentos</p>
        <div class="eqrow" style="padding:8px 0"><span style="flex:1;font-size:13px">Total cadastrado</span><b><?= $equipsTotal ?></b></div>
        <div class="eqrow" style="padding:8px 0"><span style="flex:1;font-size:13px">Em manutenção</span><b style="color:<?= $equipsManut > 0 ? 'var(--aviso)' : 'var(--ok)' ?>"><?= $equipsManut ?></b></div>
      </div>
    </div>
  </div>

  <?php elseif ($modo === 'dia'): ?>
  <!-- ══════════════════════════════════════════ PRODUÇÃO DO DIA -->
  <?php
  $ramaisQtd = (int)($ramais['qtd'] ?? 0);
  $totalInterfsD = array_sum(array_column($interfs, 'qtd'));
  ?>
  <div class="kpis">
    <div class="kpi">
      <div class="ic ic-ok"><?= svgIcon('<line x1="5" y1="20" x2="5" y2="12"/><line x1="12" y1="20" x2="12" y2="5"/><line x1="19" y1="20" x2="19" y2="9"/>') ?></div>
      <b><?= fmtM1($metrosDia) ?> m</b><span>rede executada — <?= date('d/m', strtotime($data)) ?></span>
    </div>
    <div class="kpi">
      <div class="ic ic-info"><?= svgIcon('<circle cx="5" cy="12" r="2.5"/><circle cx="19" cy="6" r="2.5"/><circle cx="19" cy="18" r="2.5"/><path d="M7 11 17 6.8M7 13l10 4.2"/>') ?></div>
      <b><?= $ramaisQtd ?></b><span>ramais executados</span>
      <?php if ($ramaisQtd > 0): ?><div class="delta d-neutro"><?= fmtM1((float)$ramais['m_pista']) ?>m pista + <?= fmtM1((float)$ramais['m_calcada']) ?>m calçada</div><?php endif; ?>
    </div>
    <div class="kpi">
      <div class="ic ic-navy"><?= svgIcon('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>') ?></div>
      <b><?= $presentes ?><span style="font-size:14px;color:var(--muted)">/<?= $presentes+$ausentes ?></span></b>
      <span>presença no dia</span>
    </div>
    <div class="kpi">
      <div class="ic <?= $totalInterfsD > 0 ? 'ic-aviso' : 'ic-ok' ?>"><?= svgIcon('<path d="M12 3 2.5 19.5h19z"/><line x1="12" y1="10" x2="12" y2="14"/><line x1="12" y1="16.5" x2="12.01" y2="16.5"/>') ?></div>
      <b><?= $totalInterfsD ?></b><span>interferências no dia</span>
    </div>
  </div>

  <div class="grade">
    <div>
      <div class="card">
        <p class="label">Produção por equipe — <?= date('d/m/Y', strtotime($data)) ?></p>
        <?php if (empty($producaoPorEquipe)): ?>
        <p style="color:var(--muted);font-size:13px">Nenhum diário enviado nesta data.</p>
        <?php else: ?>
        <table class="tab">
          <tr>
            <th>Equipe</th><th>Trecho</th>
            <th style="text-align:right">Planejado (m)</th>
            <th style="text-align:right">Executado GPS (m)</th>
            <th style="text-align:right">%</th>
          </tr>
          <?php
          $totalPlan = 0;
          foreach ($producaoPorEquipe as $p):
              $plan = (float)($p['extensao_planejada'] ?? 0);
              $exec = (float)($p['extensao_gps_m'] ?? 0);
              $pct  = $plan > 0 ? min(999, round($exec / $plan * 100)) : null;
              $totalPlan += $plan;
          ?>
          <tr>
            <td><?= htmlspecialchars($p['equipe']) ?></td>
            <td><?= htmlspecialchars($p['pv_montante']) ?> → <?= htmlspecialchars($p['pv_jusante']) ?></td>
            <td class="n" style="color:var(--muted)"><?= $plan > 0 ? fmtM1($plan) : '—' ?></td>
            <td class="n"><?= $exec > 0 ? fmtM1($exec) : '—' ?></td>
            <td class="n" style="color:<?= $pct !== null ? ($pct >= 90 ? '#1F7A6E' : ($pct >= 60 ? '#D97706' : '#DC2626')) : 'var(--muted)' ?>">
              <?= $pct !== null ? $pct . '%' : '—' ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <tr class="tot">
            <td>Total</td><td></td>
            <td class="n"><?= $totalPlan > 0 ? fmtM1($totalPlan) : '—' ?></td>
            <td class="n"><?= fmtM1($metrosDia) ?></td>
            <td class="n"><?= $totalPlan > 0 ? round($metrosDia / $totalPlan * 100) . '%' : '—' ?></td>
          </tr>
        </table>
        <p style="font-size:11px;color:var(--muted);margin-top:6px;">
          * Planejado = extensão do trecho no projeto. Executado GPS = medição em campo.<br>
          Régua oficial de faturamento: a combinar com o contratante (GPS ou planejado).
        </p>
        <?php endif; ?>
      </div>

      <?php if (!empty($fotosGaleria)): ?>
      <div class="card">
        <p class="label">Galeria do dia (evidências)</p>
        <?php
        $stepNomes = [5=>'Sinalização/EPIs',9=>'Escavação',11=>'Interferência',17=>'Reaterro',19=>'Rua limpa',20=>'Equipe fim'];
        ?>
        <div class="galeria">
          <?php foreach ($fotosGaleria as $f): ?>
          <div class="gfoto">
            <img src="<?= EXECUTOR_UPLOADS ?>/<?= htmlspecialchars($f['thumb']) ?>" alt="">
            <div class="cap"><?= htmlspecialchars($stepNomes[$f['step_num']] ?? 'Passo '.$f['step_num']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div>
      <div class="card">
        <p class="label">Fechamento do dia</p>
        <div class="eqrow" style="padding:9px 0"><span style="flex:1;font-size:13px">Cargas bota-fora</span><b><?= $cargas['bota_fora'] ?? 0 ?></b></div>
        <div class="eqrow" style="padding:9px 0"><span style="flex:1;font-size:13px">Cargas importado</span><b><?= $cargas['importado'] ?? 0 ?></b></div>
        <div class="eqrow" style="padding:9px 0"><span style="flex:1;font-size:13px">Pontões deixados</span><b><?= $pontoes ?></b></div>
        <div class="eqrow" style="padding:9px 0"><span style="flex:1;font-size:13px">Ramais executados</span><b><?= $ramaisQtd ?></b></div>
      </div>

      <div class="card">
        <p class="label">Relatório do dia</p>
        <a class="rel" href="<?= MASTER_BASE ?>/relatorio/rdo?data=<?= $data ?>" target="_blank" style="display:flex">
          <?= svgIcon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>') ?>
          <span><b>RDO Executivo — <?= date('d/m/Y', strtotime($data)) ?></b><span>produção, presença e interferências</span></span>
          <span class="fmt">PDF</span>
        </a>
        <a class="rel" href="<?= MASTER_BASE ?>/relatorio/fotos?data=<?= $data ?>" target="_blank" style="display:flex;margin-top:8px">
          <?= svgIcon('<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>') ?>
          <span><b>Relatório fotográfico — <?= date('d/m/Y', strtotime($data)) ?></b><span>todas as fotos do dia</span></span>
          <span class="fmt">PDF</span>
        </a>
      </div>
    </div>
  </div>

  <?php else: ?>
  <!-- ══════════════════════════════════════════ ACUMULADO POR PERÍODO -->
  <?php
  $totalInterfsP = array_sum(array_column($interfsTotal, 'qtd'));
  $r = $previsto > 0 ? round(($pctAvanco / 100) * 2 * M_PI * 46, 2) : 0;
  $circ = round(2 * M_PI * 46, 2);
  $off  = round($circ * (1 - $pctAvanco / 100), 2);
  ?>
  <div class="kpis">
    <div class="kpi">
      <div class="ic ic-navy"><?= svgIcon('<line x1="5" y1="20" x2="5" y2="12"/><line x1="12" y1="20" x2="12" y2="5"/><line x1="19" y1="20" x2="19" y2="9"/>') ?></div>
      <b><?= fmtM($metrosTotal) ?> m</b><span>rede executada no período</span>
      <div class="delta d-neutro"><?= date('d/m',strtotime($inicio)) ?> a <?= date('d/m',strtotime($fim)) ?></div>
    </div>
    <div class="kpi">
      <div class="ic ic-info"><?= svgIcon('<circle cx="5" cy="12" r="2.5"/><circle cx="19" cy="6" r="2.5"/><circle cx="19" cy="18" r="2.5"/><path d="M7 11 17 6.8M7 13l10 4.2"/>') ?></div>
      <b><?= (int)($ramaisTotal['qtd'] ?? 0) ?></b><span>ramais no período</span>
      <?php if (($ramaisTotal['qtd'] ?? 0) > 0): ?>
      <div class="delta d-neutro"><?= fmtM1((float)$ramaisTotal['m_pista']) ?>m pista + <?= fmtM1((float)$ramaisTotal['m_calcada']) ?>m calçada</div>
      <?php endif; ?>
    </div>
    <div class="kpi">
      <div class="ic ic-ok"><?= svgIcon('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>') ?></div>
      <b><?= fmtM1($mediaDiaria) ?> m</b><span>média diária</span>
      <div class="delta d-neutro"><?= $diasTrabalhados ?> dia(s) trabalhado(s)</div>
    </div>
    <div class="kpi">
      <div class="ic ic-aviso"><?= svgIcon('<path d="M12 3 2.5 19.5h19z"/><line x1="12" y1="10" x2="12" y2="14"/><line x1="12" y1="16.5" x2="12.01" y2="16.5"/>') ?></div>
      <b><?= $totalInterfsP ?></b><span>interferências no período</span>
      <div class="delta d-neutro">base para aditivos</div>
    </div>
  </div>

  <div class="grade">
    <div>
      <?php if (!empty($curvaProd)): ?>
      <div class="card">
        <p class="label">Curva de produção (metros/dia)</p>
        <?php
        $maxM = max(array_column($curvaProd, 'metros')) * 1.15 ?: 1;
        $hoje_ = date('Y-m-d');
        ?>
        <div class="grafico">
          <?php foreach ($curvaProd as $cp): ?>
          <?php $h = max(4, round((float)$cp['metros'] / $maxM * 100)); ?>
          <div class="gcol">
            <div class="gbar <?= $cp['data'] === $hoje_ ? 'hoje' : '' ?>" style="height:<?= $h ?>%">
              <span class="v"><?= fmtM((float)$cp['metros']) ?></span>
            </div>
            <small><?= date('d/m', strtotime($cp['data'])) ?></small>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if ($mediaDiaria > 0): ?>
        <p style="font-size:11px;color:var(--muted);margin-top:8px">Média diária: <?= fmtM1($mediaDiaria) ?> m</p>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="card">
        <p class="label">Acumulado por bacia / equipe</p>
        <?php if (empty($porBaciaEquipe)): ?>
        <p style="color:var(--muted);font-size:13px">Sem dados para o período selecionado.</p>
        <?php else: ?>
        <table class="tab">
          <tr><th>Bacia</th><th>Equipe</th><th style="text-align:right">Rede (m)</th></tr>
          <?php foreach ($porBaciaEquipe as $b): ?>
          <tr><td><?= htmlspecialchars($b['bacia'] ?? '—') ?></td><td><?= htmlspecialchars($b['equipe']) ?></td><td class="n"><?= fmtM1((float)$b['metros']) ?></td></tr>
          <?php endforeach; ?>
          <tr class="tot"><td>Total</td><td></td><td class="n"><?= fmtM1($metrosTotal) ?></td></tr>
        </table>
        <?php endif; ?>
      </div>
    </div>

    <div>
      <div class="card">
        <p class="label">Avanço físico do contrato</p>
        <?php if ($previsto > 0): ?>
        <div class="avanco">
          <svg class="donut" viewBox="0 0 120 120">
            <circle cx="60" cy="60" r="46" fill="none" stroke="#E4E8EF" stroke-width="14"/>
            <circle cx="60" cy="60" r="46" fill="none" stroke="#1F7A6E" stroke-width="14"
              stroke-linecap="round" stroke-dasharray="<?= $circ ?>" stroke-dashoffset="<?= $off ?>"
              transform="rotate(-90 60 60)"/>
            <text x="60" y="57" text-anchor="middle" font-size="21" font-weight="800" fill="#1A2D4F"><?= $pctAvanco ?>%</text>
            <text x="60" y="72" text-anchor="middle" font-size="9" fill="#6B7686" font-weight="700">CONCLUÍDO</text>
          </svg>
          <div class="leg">
            <div><span class="dot" style="background:#1F7A6E"></span><b><?= fmtM($executadoTotal) ?> m</b> executados</div>
            <div><span class="dot" style="background:#E4E8EF"></span><b><?= fmtM($previsto) ?> m</b> previstos</div>
            <?php if ($projecao): ?>
            <div style="color:var(--ok)">▲ término projetado: <?= $projecao ?></div>
            <?php endif; ?>
          </div>
        </div>
        <?php else: ?>
        <p style="color:var(--muted);font-size:13px">Sem trechos com extensão cadastrada.</p>
        <?php endif; ?>
      </div>

      <?php if (!empty($interfsTotal)): ?>
      <div class="card">
        <p class="label">Interferências (base para aditivos)</p>
        <?php
        $interf_nomes = ['pedra'=>'Pedra','agua_na_vala'=>'Água na vala','ramal_de_agua'=>'Ramal de água',
          'rede_de_agua'=>'Rede de água','rede_pluvial'=>'Rede pluvial','rompimento_de_rede'=>'Rompimento',
          'rede_cloacal_existente'=>'Rede cloacal','rede_logica'=>'Rede lógica','rede_eletrica'=>'Rede elétrica','outros'=>'Outros'];
        $resumo = implode(' · ', array_map(fn($i) => ($interf_nomes[$i['tipo']] ?? $i['tipo']) . ' (' . $i['qtd'] . ')', $interfsTotal));
        ?>
        <div class="alerta a-info">🧱 <?= $totalInterfsP ?> interferência(s) no período<small><?= htmlspecialchars($resumo) ?> — todas com foto e GPS</small></div>
      </div>
      <?php endif; ?>

      <div class="card">
        <p class="label">Produtividade por equipe</p>
        <?php if (empty($produtividade)): ?>
        <p style="color:var(--muted);font-size:13px">Sem dados para o período.</p>
        <?php else: ?>
        <table class="tab">
          <tr><th>Equipe</th><th style="text-align:right">Dias</th><th style="text-align:right">m/dia</th></tr>
          <?php foreach ($produtividade as $p): ?>
          <tr><td><?= htmlspecialchars($p['equipe']) ?></td><td class="n"><?= $p['dias'] ?></td><td class="n"><?= fmtM1((float)$p['m_por_dia']) ?></td></tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
      </div>

      <?php if (!empty($repavPeriodo)): ?>
      <div class="card">
        <p class="label">Repavimentação no período</p>
        <div class="eqrow" style="padding:8px 0">
          <span style="flex:1;font-size:13px">Área repavimentada</span>
          <b><?= number_format((float)$repavPeriodo['area_total'], 2, ',', '.') ?> m²</b>
        </div>
        <?php if ((float)$repavPeriodo['volume_total'] > 0): ?>
        <div class="eqrow" style="padding:8px 0">
          <span style="flex:1;font-size:13px">Volume de asfalto</span>
          <b style="color:#1F7A6E"><?= number_format((float)$repavPeriodo['volume_total'], 3, ',', '.') ?> m³
          <small style="font-weight:400;color:var(--muted)">≈ <?= number_format((float)$repavPeriodo['volume_total'] * 2.4, 2, ',', '.') ?> t</small></b>
        </div>
        <?php endif; ?>
        <div class="eqrow" style="padding:8px 0">
          <span style="flex:1;font-size:13px">Trechos medidos</span>
          <b><?= (int)$repavPeriodo['trechos_medidos'] ?></b>
        </div>
        <?php if ((int)$repavPeriodo['fila_pendente'] > 0): ?>
        <div class="alerta a-aviso" style="margin-top:8px">
          ⏳ <?= $repavPeriodo['fila_pendente'] ?> trecho(s) ainda aguardando repavimentação
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Central de relatórios -->
  <div class="card" style="margin-top:0">
    <p class="label">Central de relatórios do período</p>
    <div class="rels">
      <?php
      $base_url = MASTER_BASE . '/relatorio/';
      $periodo_qs = "?inicio={$inicio}&fim={$fim}";
      $rels = [
        ['boletim',      'ic-gold',  'Boletim de Medição do Período',    'rede + ramais — base de faturamento',  'PDF · CSV'],
        ['avanco',       'ic-navy',  'Relatório de Avanço Físico',        '% concluído, curva e projeção',         'PDF'],
        ['interferencias','ic-info', 'Relatório de Interferências',       'por tipo, com foto e GPS — aditivos',   'PDF · CSV'],
        ['produtividade','ic-aviso', 'Relatório de Produtividade',        'm por equipe-dia e ranking',            'PDF · CSV'],
        ['materiais',    'ic-ok',    'Relatório de Materiais',            'estoque atual × reservado',             'PDF · CSV'],
        ['resumo',       'ic-gold',  'Resumo Gerencial (1 página)',       'visão do dono: produção e marcos',      'PDF'],
      ];
      foreach ($rels as [$tipo, $cls, $titulo, $desc, $fmts]):
      ?>
      <a class="rel" href="<?= $base_url . $tipo . $periodo_qs ?>" target="_blank">
        <span class="ic <?= $cls ?>"><?= svgIcon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>') ?></span>
        <span><b><?= $titulo ?></b><span><?= $desc ?></span></span>
        <span class="fmt"><?= $fmts ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</main>
</div>
</body>
</html>
