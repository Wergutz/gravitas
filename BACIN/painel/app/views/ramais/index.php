<?php
/** @var array $frentes @var array $equipes @var array $resumo */
$title        = 'Ramais';
$pageTitle    = 'Frentes de Ramais';
$pageSubtitle = 'Acompanhamento das ligações prediais lançadas em campo';

$currentRoute = $currentRoute ?? '/ramais';

ob_start();
?>

<div class="topo" style="margin-bottom:14px;">
    <div class="acoes">
        <a href="<?= APP_BASE ?>/ramais/relatorio" class="btn btn-sec btn-sm">
            Relatório físico do período
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="card mb16">
    <div class="label">Filtros</div>
    <form method="get" action="<?= APP_BASE ?>/ramais"
          style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
        <div class="campo" style="min-width:150px">
            <label>Data inicial</label>
            <input type="date" name="inicio" value="<?= htmlspecialchars((string)$fInicio) ?>">
        </div>
        <div class="campo" style="min-width:150px">
            <label>Data final</label>
            <input type="date" name="fim" value="<?= htmlspecialchars((string)$fFim) ?>">
        </div>
        <div class="campo" style="min-width:170px">
            <label>Equipe</label>
            <select name="equipe_id">
                <option value="">Todas</option>
                <?php foreach ($equipes as $eq): ?>
                    <option value="<?= (int)$eq['id'] ?>" <?= $fEquipe === (int)$eq['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($eq['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo" style="min-width:150px">
            <label>Status</label>
            <select name="status">
                <option value="">Todos</option>
                <?php foreach (RamaisController::STATUS_FRENTE as $sv => $sl): ?>
                    <option value="<?= htmlspecialchars($sv) ?>" <?= $fStatus === $sv ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sl) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-sec btn-sm" type="submit">Filtrar</button>
        <a class="btn btn-sec btn-sm" href="<?= APP_BASE ?>/ramais">Limpar</a>
    </form>
</div>

<!-- Resumo do filtro -->
<div class="kpis" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px">
    <div class="kpi">
        <b><?= (int)$resumo['frentes'] ?></b>
        <span>Frentes no filtro</span>
    </div>
    <div class="kpi">
        <b><?= (int)$resumo['ramais'] ?></b>
        <span>Ramais lançados</span>
    </div>
    <div class="kpi">
        <b><?= RamaisController::num($resumo['via_m'], 2) ?> m</b>
        <span>Total em via</span>
    </div>
    <div class="kpi">
        <b><?= RamaisController::num($resumo['calcada_m'], 2) ?> m</b>
        <span>Total em calçada</span>
    </div>
</div>

<!-- Lista -->
<div class="card">
    <div class="label">
        Frentes de ramais
        <span class="ver"><?= count($frentes) ?> registro(s)</span>
    </div>

    <?php if (empty($frentes)): ?>
        <div class="alerta a-info">
            Nenhuma frente de ramais lançada até o momento.
            <small>Assim que uma equipe enviar uma frente pelo app do Executor de Ramais, ela aparece aqui.</small>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Data</th>
                        <th>Equipe</th>
                        <th>Autor</th>
                        <th>Trecho / Logradouro</th>
                        <th style="text-align:right">Ramais</th>
                        <th style="text-align:right">Via (m)</th>
                        <th style="text-align:right">Calçada (m)</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($frentes as $f): ?>
                    <?php
                    $chipClasse = $f['status'] === 'enviado' ? 'c-ok' : 'c-neutro';
                    ?>
                    <tr>
                        <td><?= (int)$f['id'] ?></td>
                        <td><?= htmlspecialchars(RamaisController::dataBr($f['data'])) ?></td>
                        <td><?= htmlspecialchars((string)$f['equipe_nome']) ?></td>
                        <td style="font-size:12.5px;color:var(--muted)">
                            <?= htmlspecialchars((string)($f['autor_nome'] ?? '—')) ?>
                        </td>
                        <td style="font-size:12.5px">
                            <?= htmlspecialchars(RamaisController::descricaoLocal($f)) ?>
                            <?php if (!empty($f['pv_montante']) && !empty($f['logradouro'])): ?>
                                <br><small style="color:var(--muted)"><?= htmlspecialchars((string)$f['logradouro']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right"><?= (int)$f['qtd_ramais'] ?></td>
                        <td style="text-align:right"><?= RamaisController::num($f['total_via_m'], 2) ?></td>
                        <td style="text-align:right"><?= RamaisController::num($f['total_calcada_m'], 2) ?></td>
                        <td>
                            <span class="chip <?= $chipClasse ?>">
                                <?= htmlspecialchars(RamaisController::labelStatus($f['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= APP_BASE ?>/ramais/ver?id=<?= (int)$f['id'] ?>"
                               class="btn btn-sec btn-sm">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" style="font-weight:700">Total do filtro</td>
                        <td style="text-align:right;font-weight:700"><?= (int)$resumo['ramais'] ?></td>
                        <td style="text-align:right;font-weight:700"><?= RamaisController::num($resumo['via_m'], 2) ?></td>
                        <td style="text-align:right;font-weight:700"><?= RamaisController::num($resumo['calcada_m'], 2) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
