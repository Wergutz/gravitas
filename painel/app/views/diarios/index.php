<?php
$title = 'Diários de Execução';
$pageTitle = 'Diários de Execução';
$pageSubtitle = 'Registros enviados pelas equipes de campo';
ob_start();
?>

<!-- Alertas de falta de material -->
<?php if ($alertasMat): ?>
<div class="card" style="border-left:4px solid var(--cor-aviso, #E07B39)">
    <div class="label">⚠️ Alertas de falta de material (<?= count($alertasMat) ?>)</div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Data</th><th>Equipe</th><th>Trecho</th><th>Materiais faltando</th><th>Ação</th></tr></thead>
        <tbody>
        <?php foreach ($alertasMat as $a): ?>
        <tr>
            <td><?= date('d/m/Y', strtotime($a['data'])) ?></td>
            <td><?= htmlspecialchars($a['equipe_nome']) ?></td>
            <td><?= htmlspecialchars($a['pv_montante']) ?> → <?= htmlspecialchars($a['pv_jusante']) ?></td>
            <td style="font-size:12.5px"><?= nl2br(htmlspecialchars($a['materiais_faltando'])) ?></td>
            <td>
                <form method="post" action="<?= APP_BASE ?>/diarios/resolver-alerta" style="display:inline">
                    <?= csrf_input() ?>
                    <input type="hidden" name="alerta_id" value="<?= (int)$a['id'] ?>">
                    <button class="btn btn-ok btn-sm">✔ Resolvido</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<!-- Equipamentos em manutenção -->
<?php if ($equipsManut): ?>
<div class="card" style="border-left:4px solid var(--cor-erro, #C0392B)">
    <div class="label">🔧 Equipamentos aguardando manutenção (<?= count($equipsManut) ?>)</div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tipo</th><th>Modelo</th><th>Placa</th><th>Observação</th><th>Ação</th></tr></thead>
        <tbody>
        <?php foreach ($equipsManut as $eq): ?>
        <tr>
            <td><span class="chip c-erro"><?= htmlspecialchars($eq['categoria']) ?></span></td>
            <td><?= htmlspecialchars($eq['tipo'] . ' ' . $eq['modelo']) ?></td>
            <td><?= htmlspecialchars($eq['placa'] ?? '—') ?></td>
            <td style="font-size:12.5px"><?= htmlspecialchars($eq['obs_manutencao'] ?? '') ?></td>
            <td>
                <form method="post" action="<?= APP_BASE ?>/diarios/resolver-manutencao" style="display:inline">
                    <?= csrf_input() ?>
                    <input type="hidden" name="equip_id"   value="<?= (int)$eq['id'] ?>">
                    <input type="hidden" name="categoria"  value="<?= htmlspecialchars($eq['categoria']) ?>">
                    <button class="btn btn-ok btn-sm">✔ Volta ao normal</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<!-- Filtro por data / equipe -->
<div class="card">
    <form method="get" action="<?= APP_BASE ?>/diarios" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
        <div class="campo" style="min-width:150px">
            <label>Data</label>
            <input type="date" name="data" value="<?= htmlspecialchars($filtroData) ?>">
        </div>
        <div class="campo" style="min-width:160px">
            <label>Equipe</label>
            <select name="equipe_id">
                <option value="">Todas</option>
                <?php foreach ($equipes as $eq): ?>
                <option value="<?= (int)$eq['id'] ?>" <?= $filtroEq == $eq['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($eq['nome']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-sec btn-sm" type="submit">Filtrar</button>
    </form>
</div>

<!-- Lista de diários -->
<div class="card">
    <div class="label">
        Diários do dia <?= date('d/m/Y', strtotime($filtroData)) ?>
        <span class="ver"><?= count($diarios) ?> registro(s)</span>
    </div>

    <?php if (empty($diarios)): ?>
    <div class="alerta a-info">Nenhum diário enviado para este filtro.</div>
    <?php else: ?>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Equipe</th>
                <th>Trecho</th>
                <th>Ext. plan.</th>
                <th>Ext. GPS</th>
                <th>Estoque</th>
                <th>Status</th>
                <th>Passos</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($diarios as $d): ?>
        <?php
        $extGps  = $d['extensao_gps_m'] ? number_format($d['extensao_gps_m'], 0, ',', '.') . ' m' : '—';
        $extPlan = $d['extensao_planejada'] ? number_format($d['extensao_planejada'], 0, ',', '.') . ' m' : '—';
        $statusLabel = match($d['status']) {
            'rascunho' => ['Rascunho', 'c-neutro'],
            'enviado'  => ['Enviado',  'c-ok'],
            'aprovado' => ['Aprovado', 'c-info'],
            default    => [$d['status'], 'c-neutro'],
        };
        $estoqueChip = $d['step3_estoque_ok'] === null ? ''
            : ($d['step3_estoque_ok'] ? '<span class="chip c-ok">OK</span>'
                                      : '<span class="chip c-aviso">Falta</span>');
        ?>
        <tr>
            <td><?= htmlspecialchars($d['equipe_nome']) ?></td>
            <td style="font-size:12.5px">
                <?= htmlspecialchars($d['pv_montante']) ?> → <?= htmlspecialchars($d['pv_jusante']) ?><br>
                <small style="color:var(--muted)"><?= htmlspecialchars($d['rua'] ?? '') ?></small>
            </td>
            <td><?= $extPlan ?></td>
            <td><?= $extGps ?></td>
            <td><?= $estoqueChip ?></td>
            <td><span class="chip <?= $statusLabel[1] ?>"><?= $statusLabel[0] ?></span></td>
            <td>
                <div style="background:var(--bg);border-radius:6px;height:6px;width:60px;overflow:hidden">
                    <div style="background:var(--cor-ok,#2ecc71);height:100%;width:<?= min(100, (int)round($d['step_atual']/21*100)) ?>%"></div>
                </div>
                <small><?= (int)$d['step_atual'] ?>/21</small>
            </td>
            <td>
                <a href="<?= APP_BASE ?>/diarios/ver?id=<?= (int)$d['id'] ?>" class="btn btn-sec btn-sm">Ver</a>
                <a href="<?= APP_BASE ?>/diarios/fotos?id=<?= (int)$d['id'] ?>" class="btn btn-sec btn-sm">📷 Fotos</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/planejador.php';
