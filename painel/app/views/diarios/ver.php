<?php
$title = 'Diário de Execução';
$pageTitle = 'Diário de Execução';
$pageSubtitle = date('d/m/Y', strtotime($diario['data'])) . ' · ' . htmlspecialchars($diario['equipe_nome']);
ob_start();

$stepNomes = [
    4=>'Carregando material', 5=>'Sinalização e EPIs', 7=>'Corte de asfalto',
    8=>'Retirada de pavimento', 9=>'Escavação', 10=>'Escoramento',
    19=>'Rua limpa', 20=>'Equipe final',
];
?>

<!-- Cabeçalho -->
<div class="card">
    <div class="kpis" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px">
        <div class="kpi">
            <b><?= htmlspecialchars($diario['pv_montante']) ?> → <?= htmlspecialchars($diario['pv_jusante']) ?></b>
            <span>Trecho</span>
        </div>
        <div class="kpi">
            <b><?= $diario['extensao_planejada'] ? number_format($diario['extensao_planejada'], 0, ',', '.') . ' m' : '—' ?></b>
            <span>Extensão planejada</span>
        </div>
        <div class="kpi">
            <b style="color:<?= $diario['extensao_gps_m'] ? 'var(--cor-ok,#27ae60)' : 'var(--muted)' ?>">
                <?= $diario['extensao_gps_m'] ? number_format($diario['extensao_gps_m'], 0, ',', '.') . ' m' : '—' ?>
            </b>
            <span>Extensão GPS</span>
        </div>
        <div class="kpi">
            <b><?= (int)$diario['step_atual'] ?>/21</b>
            <span>Passos preenchidos</span>
        </div>
    </div>

    <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap">
        <a href="<?= APP_BASE ?>/diarios/fotos?id=<?= (int)$diario['id'] ?>" class="btn btn-sec btn-sm">📷 Relatório fotográfico</a>
        <a href="<?= APP_BASE ?>/diarios" class="btn btn-sec btn-sm">← Voltar</a>
    </div>
</div>

<!-- Presença -->
<div class="card">
    <div class="label">Presença / atrasos</div>
    <?php if ($presencas): ?>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Funcionário</th><th>Status</th><th>Obs</th></tr></thead>
        <tbody>
        <?php foreach ($presencas as $p): ?>
        <?php $cls = match($p['status']) {
            'presente'  => 'c-ok',
            'ausente'   => 'c-erro',
            'atrasou'   => 'c-aviso',
            'saiu_cedo' => 'c-aviso',
            default     => 'c-neutro',
        }; ?>
        <tr>
            <td><?= htmlspecialchars($p['nome']) ?></td>
            <td><span class="chip <?= $cls ?>"><?= htmlspecialchars($p['status']) ?></span></td>
            <td style="font-size:12.5px"><?= htmlspecialchars($p['obs'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
    <div class="alerta a-info">Passo de presença não preenchido.</div>
    <?php endif; ?>
</div>

<!-- GPS -->
<?php if ($gps): ?>
<div class="card">
    <div class="label">Posições GPS</div>
    <div class="kpis" style="grid-template-columns:1fr 1fr 1fr;gap:12px">
        <div class="kpi">
            <b style="font-size:13px"><?= $gps['lat_inicio'] ? $gps['lat_inicio'] . ', ' . $gps['lng_inicio'] : '—' ?></b>
            <span>Posição início</span>
        </div>
        <div class="kpi">
            <b style="font-size:13px"><?= $gps['lat_fim'] ? $gps['lat_fim'] . ', ' . $gps['lng_fim'] : '—' ?></b>
            <span>Posição fim</span>
        </div>
        <div class="kpi">
            <b style="color:var(--cor-ok,#27ae60)"><?= $gps['extensao_calculada_m'] ? number_format($gps['extensao_calculada_m'], 1, ',', '.') . ' m' : '—' ?></b>
            <span>Extensão calculada (haversine)</span>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Estoque na frente -->
<?php if ($diario['step3_estoque_ok'] !== null): ?>
<div class="card">
    <div class="label">Estoque na frente (passo 3)</div>
    <?php if ($diario['step3_estoque_ok']): ?>
    <div class="alerta a-ok">✔ Equipe confirmou ter todo o material necessário.</div>
    <?php else: ?>
    <div class="alerta a-aviso">
        ⚠️ <b>Falta de material reportada:</b><br>
        <?= nl2br(htmlspecialchars($diario['step3_materiais_faltando'] ?? '')) ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Equipamentos -->
<?php if ($equipamentos): ?>
<div class="card">
    <div class="label">Equipamentos</div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tipo</th><th>Modelo</th><th>Placa</th><th>Status</th><th>Obs</th></tr></thead>
        <tbody>
        <?php foreach ($equipamentos as $eq): ?>
        <tr>
            <td><?= htmlspecialchars($eq['tipo']) ?></td>
            <td><?= htmlspecialchars($eq['modelo'] ?? '—') ?></td>
            <td><?= htmlspecialchars($eq['placa'] ?? '—') ?></td>
            <td>
                <?php if ($eq['funcionando']): ?>
                <span class="chip c-ok">Funcionando</span>
                <?php else: ?>
                <span class="chip c-erro">Com problema</span>
                <?php endif; ?>
            </td>
            <td style="font-size:12.5px"><?= htmlspecialchars($eq['obs'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<!-- Interferências -->
<?php if ($interferencias): ?>
<div class="card">
    <div class="label">Interferências (<?= count($interferencias) ?>)</div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tipo</th><th>Especificação</th><th>GPS</th><th>Foto</th></tr></thead>
        <tbody>
        <?php foreach ($interferencias as $interf): ?>
        <tr>
            <td><?= htmlspecialchars(str_replace('_', ' ', ucfirst($interf['tipo']))) ?></td>
            <td><?= htmlspecialchars($interf['especificacao'] ?? '—') ?></td>
            <td style="font-size:11.5px"><?= $interf['lat'] ? $interf['lat'] . ', ' . $interf['lng'] : '—' ?></td>
            <td>
                <?php if ($interf['foto_arquivo']): ?>
                <a href="<?= $executorUploads ?>/<?= htmlspecialchars($interf['foto_arquivo']) ?>" target="_blank">
                    <img src="<?= $executorUploads ?>/<?= htmlspecialchars($interf['foto_arquivo']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px">
                </a>
                <?php else: ?>—<?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<!-- Reaterros -->
<?php if ($reaterros): ?>
<div class="card">
    <div class="label">Camadas de reaterro (<?= count($reaterros) ?>)</div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tipo</th><th>Espessura</th><th>Foto</th></tr></thead>
        <tbody>
        <?php foreach ($reaterros as $r): ?>
        <tr>
            <td><?= htmlspecialchars(str_replace('_', ' ', ucfirst($r['tipo']))) ?></td>
            <td><?= $r['espessura_cm'] ? number_format($r['espessura_cm'], 1, ',', '.') . ' cm' : '—' ?></td>
            <td>
                <?php if ($r['foto_arquivo']): ?>
                <a href="<?= $executorUploads ?>/<?= htmlspecialchars($r['foto_arquivo']) ?>" target="_blank">
                    <img src="<?= $executorUploads ?>/<?= htmlspecialchars($r['foto_arquivo']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px">
                </a>
                <?php else: ?>—<?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<!-- Ramais -->
<?php if ($ramais): ?>
<div class="card">
    <div class="label">Ramais executados (<?= count($ramais) ?>)</div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Nº Residência</th><th>Dim. Pontão</th><th>Ext. Pista</th><th>Ext. Calçada</th></tr></thead>
        <tbody>
        <?php foreach ($ramais as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['nro_residencia'] ?? '—') ?></td>
            <td><?= htmlspecialchars($r['dimensao_pontao'] ?? '—') ?></td>
            <td><?= $r['ext_pista']   ? number_format($r['ext_pista'],   2, ',', '.') . ' m' : '—' ?></td>
            <td><?= $r['ext_calcada'] ? number_format($r['ext_calcada'], 2, ',', '.') . ' m' : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<!-- Pontões -->
<?php if ($pontoes): ?>
<div class="card">
    <div class="label">Pontões de espera (<?= count($pontoes) ?>)</div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;padding:4px 0">
        <?php foreach ($pontoes as $p): ?>
        <div style="border:1px solid var(--line);border-radius:10px;padding:10px;font-size:12.5px;min-width:100px;text-align:center">
            <?php if ($p['foto_thumb']): ?>
            <img src="<?= $executorUploads ?>/<?= htmlspecialchars($p['foto_thumb']) ?>" style="width:64px;height:64px;object-fit:cover;border-radius:6px;display:block;margin:0 auto 6px"><br>
            <?php endif; ?>
            🏠 <?= htmlspecialchars($p['nro_residencia'] ?? '?') ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Cargas -->
<?php if ($cargas): ?>
<div class="card">
    <div class="label">Cargas (<?= count($cargas) ?>)</div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;padding:4px 0">
        <?php foreach ($cargas as $c): ?>
        <div style="border:1px solid var(--line);border-radius:10px;padding:10px;font-size:12px;text-align:center;min-width:80px">
            <?php if ($c['foto_thumb']): ?>
            <img src="<?= $executorUploads ?>/<?= htmlspecialchars($c['foto_thumb']) ?>" style="width:64px;height:64px;object-fit:cover;border-radius:6px;display:block;margin:0 auto 6px">
            <?php endif; ?>
            <?= htmlspecialchars(str_replace('_', ' ', ucfirst($c['tipo']))) ?><br>
            <b>#<?= (int)$c['numero'] ?></b>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/planejador.php';
