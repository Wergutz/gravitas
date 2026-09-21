<?php
if (!defined('APP_BASE')) require_once __DIR__ . '/../../config/app.php';
auth_required([3, 4]);

/** @var string $inicio @var string $fim @var array $totais
 *  @var array $porPavVia @var array $porPavCalcada @var array $porLocal */

$num_doc  = 'RAMAIS-' . date('Y') . '-' . date('md', strtotime($inicio)) . date('md', strtotime($fim));
$data_ger = date('d/m/Y H:i');
$usuario_nome = $_SESSION['nome'] ?? '—';

$qtdRamais  = (int)($totais['qtd_ramais'] ?? 0);
$qtdFrentes = (int)($totais['qtd_frentes'] ?? 0);
$viaTotal   = (float)($totais['via_m'] ?? 0);
$calcTotal  = (float)($totais['calcada_m'] ?? 0);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Relatório Físico de Ramais · <?= htmlspecialchars(RamaisController::dataBr($inicio)) ?> a <?= htmlspecialchars(RamaisController::dataBr($fim)) ?></title>
<meta name="robots" content="noindex,nofollow">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,'Helvetica Neue',Arial,sans-serif;font-size:11px;color:#1a1a1a;background:#ddd;line-height:1.5}
.toolbar{background:#1A2D4F;color:#fff;padding:10px 20px;display:flex;align-items:center;gap:12px;font-size:12px;flex-wrap:wrap}
.toolbar a{color:#E0A53D;text-decoration:none;font-weight:700}
.toolbar button{background:#E0A53D;color:#1A2D4F;border:none;padding:7px 18px;border-radius:6px;font-weight:800;font-size:12px;cursor:pointer}
.toolbar form{display:flex;align-items:center;gap:8px;font-size:11px}
.toolbar input[type=date]{border:none;border-radius:5px;padding:5px 7px;font-size:11px;font-family:inherit}
.toolbar .btn-filtro{background:#fff;color:#1A2D4F;border:none;padding:6px 14px;border-radius:5px;font-weight:700;font-size:11px;cursor:pointer}
.pagina{width:210mm;min-height:297mm;margin:20px auto;background:#fff;padding:16mm 15mm 20mm;box-shadow:0 2px 20px rgba(0,0,0,.18)}
.rpt-header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2.5px solid #1A2D4F;padding-bottom:10px;margin-bottom:12px}
.marca-area{display:flex;align-items:center;gap:10px}
.marca-area img{width:41px;height:41px;flex:0 0 auto;object-fit:contain}
.marca-txt b{display:block;font-size:14px;letter-spacing:2px;color:#1A2D4F;font-weight:800}
.marca-txt small{font-size:8.5px;color:#666;letter-spacing:0.5px;text-transform:uppercase}
.doc-meta{text-align:right;font-size:9.5px;color:#555}
.doc-meta strong{display:block;font-size:11px;color:#1A2D4F;font-weight:800;margin-bottom:2px}
.rpt-titulo{text-align:center;margin:10px 0 14px;padding:10px 0;background:#f5f7fa;border-radius:4px}
.rpt-titulo h1{font-size:15px;color:#1A2D4F;text-transform:uppercase;letter-spacing:1.5px;font-weight:800}
.rpt-titulo p{font-size:11px;color:#555;margin-top:3px}
.secao{margin-bottom:14px}
.secao-titulo{font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:1.5px;color:#fff;background:#1A2D4F;padding:4px 8px;margin-bottom:8px}
.grade-dados{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:4px}
.dado label{display:block;font-size:8.5px;text-transform:uppercase;letter-spacing:0.8px;color:#888;font-weight:700;margin-bottom:2px}
.dado span{font-size:11.5px;font-weight:700;color:#1a1a1a}
.kpis-row{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px}
.kpi-box{background:#f5f7fa;border:1px solid #dde;border-radius:4px;padding:10px 8px;text-align:center}
.kpi-box .num{font-size:20px;font-weight:800;color:#1A2D4F;line-height:1.15}
.kpi-box .lab{font-size:8.5px;text-transform:uppercase;letter-spacing:0.8px;color:#888;font-weight:700;margin-top:3px}
table.trch{width:100%;border-collapse:collapse;font-size:11px}
table.trch th{background:#1A2D4F;color:#fff;padding:6px 8px;font-size:9.5px;text-transform:uppercase;letter-spacing:0.4px;text-align:left}
table.trch td{padding:6px 8px;border-bottom:1px solid #eee;vertical-align:middle}
table.trch tr:nth-child(even) td{background:#f8f9fb}
table.trch tfoot td{font-weight:800;background:#f0f3f8;border-top:2px solid #1A2D4F}
.vazio{color:#888;font-size:11px;font-style:italic}
.nota{background:#fafbfd;border:1px solid #dde;border-radius:4px;padding:8px 12px;font-size:10px;color:#555;font-style:italic;margin-bottom:12px}
.assinaturas{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:28px;page-break-inside:avoid}
.assinatura{border-top:1.5px solid #888;padding-top:6px;text-align:center;font-size:10px;color:#555}
.assinatura b{display:block;font-size:10.5px;color:#1a1a1a;margin-bottom:2px}
.linha-data{text-align:center;margin-top:16px;font-size:10px;color:#555;page-break-inside:avoid}
.rpt-footer{margin-top:20px;border-top:1px solid #ccc;padding-top:6px;display:flex;justify-content:space-between;font-size:8.5px;color:#888}
@media print{
  /* Paginacao de tabela: cabecalho repete a cada pagina e nenhuma linha
     e partida ao meio. */
  thead{display:table-header-group}
  tfoot{display:table-footer-group}
  tr{break-inside:avoid;page-break-inside:avoid}

  body{background:#fff}
  .toolbar{display:none!important}
  .pagina{margin:0;box-shadow:none;padding:12mm 12mm 16mm}
  @page{size:A4 portrait;margin:0}
}
</style>
</head>
<body>
<div class="toolbar">
    <a href="<?= APP_BASE ?>/ramais">← Voltar</a>
    <form method="get" action="<?= APP_BASE ?>/ramais/relatorio">
        <label>De</label>
        <input type="date" name="inicio" value="<?= htmlspecialchars($inicio) ?>">
        <label>até</label>
        <input type="date" name="fim" value="<?= htmlspecialchars($fim) ?>">
        <button class="btn-filtro" type="submit">Atualizar</button>
    </form>
    <span style="flex:1"></span>
    <span>Documento: <?= htmlspecialchars($num_doc) ?></span>
    <button onclick="window.print()">Imprimir / Salvar PDF</button>
</div>

<div class="pagina">

    <!-- Cabeçalho -->
    <div class="rpt-header">
        <div class="marca-area">
            <img src="/BACIN/painel/assets/img/icon-bacin.svg?v=2" alt="BACIN">
            <div class="marca-txt">
                <b>BACIN</b>
                <small>Saneamento Básico</small>
            </div>
        </div>
        <div class="doc-meta">
            <strong><?= htmlspecialchars($num_doc) ?></strong>
            Período: <?= htmlspecialchars(RamaisController::dataBr($inicio)) ?> a <?= htmlspecialchars(RamaisController::dataBr($fim)) ?><br>
            Gerado em: <?= htmlspecialchars($data_ger) ?><br>
            Por: <?= htmlspecialchars($usuario_nome) ?>
        </div>
    </div>

    <!-- Título -->
    <div class="rpt-titulo">
        <h1>Relatório Físico de Ramais</h1>
        <p><?= htmlspecialchars(RamaisController::dataBr($inicio)) ?> a <?= htmlspecialchars(RamaisController::dataBr($fim)) ?>
           · <?= $qtdFrentes ?> frente(s) enviada(s)</p>
    </div>

    <div class="nota">
        Relatório exclusivamente físico — quantidades de ramais e comprimentos executados em via e em calçada.
        Considera somente as frentes com status “Enviado” no período.
    </div>

    <!-- KPIs -->
    <div class="kpis-row">
        <div class="kpi-box">
            <div class="num"><?= $qtdFrentes ?></div>
            <div class="lab">Frentes enviadas</div>
        </div>
        <div class="kpi-box">
            <div class="num"><?= $qtdRamais ?></div>
            <div class="lab">Ramais executados</div>
        </div>
        <div class="kpi-box">
            <div class="num"><?= RamaisController::num($viaTotal, 2) ?></div>
            <div class="lab">Metros em via</div>
        </div>
        <div class="kpi-box">
            <div class="num"><?= RamaisController::num($calcTotal, 2) ?></div>
            <div class="lab">Metros em calçada</div>
        </div>
    </div>

    <!-- Via por pavimento -->
    <div class="secao">
        <div class="secao-titulo">Metros em Via por Tipo de Pavimento</div>
        <?php if (empty($porPavVia)): ?>
            <p class="vazio">Nenhum ramal executado no período.</p>
        <?php else: ?>
        <table class="trch">
            <thead>
                <tr>
                    <th>Tipo de pavimento</th>
                    <th style="text-align:right;width:90px;">Ramais</th>
                    <th style="text-align:right;width:120px;">Extensão (m)</th>
                    <th style="text-align:right;width:80px;">% do total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($porPavVia as $p): ?>
                <tr>
                    <td><?= htmlspecialchars(RamaisController::labelPavVia($p['pavimento'])) ?></td>
                    <td style="text-align:right;"><?= (int)$p['qtd_ramais'] ?></td>
                    <td style="text-align:right;font-weight:700;color:#1A2D4F;"><?= RamaisController::num($p['metros'], 2) ?></td>
                    <td style="text-align:right;color:#666;">
                        <?= $viaTotal > 0 ? RamaisController::num((float)$p['metros'] / $viaTotal * 100, 1) . '%' : '—' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td><b>Total em via</b></td>
                    <td style="text-align:right;"><b><?= $qtdRamais ?></b></td>
                    <td style="text-align:right;"><b><?= RamaisController::num($viaTotal, 2) ?></b></td>
                    <td style="text-align:right;"><b>100,0%</b></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>

    <!-- Calçada por pavimento -->
    <div class="secao">
        <div class="secao-titulo">Metros em Calçada por Tipo de Pavimento</div>
        <?php if (empty($porPavCalcada)): ?>
            <p class="vazio">Nenhum ramal executado no período.</p>
        <?php else: ?>
        <table class="trch">
            <thead>
                <tr>
                    <th>Tipo de pavimento</th>
                    <th style="text-align:right;width:90px;">Ramais</th>
                    <th style="text-align:right;width:120px;">Extensão (m)</th>
                    <th style="text-align:right;width:80px;">% do total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($porPavCalcada as $p): ?>
                <tr>
                    <td><?= htmlspecialchars(RamaisController::labelPavCalcada($p['pavimento'])) ?></td>
                    <td style="text-align:right;"><?= (int)$p['qtd_ramais'] ?></td>
                    <td style="text-align:right;font-weight:700;color:#1A2D4F;"><?= RamaisController::num($p['metros'], 2) ?></td>
                    <td style="text-align:right;color:#666;">
                        <?= $calcTotal > 0 ? RamaisController::num((float)$p['metros'] / $calcTotal * 100, 1) . '%' : '—' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td><b>Total em calçada</b></td>
                    <td style="text-align:right;"><b><?= $qtdRamais ?></b></td>
                    <td style="text-align:right;"><b><?= RamaisController::num($calcTotal, 2) ?></b></td>
                    <td style="text-align:right;"><b>100,0%</b></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>

    <!-- Por logradouro / trecho -->
    <div class="secao">
        <div class="secao-titulo">Execução por Logradouro / Trecho</div>
        <?php if (empty($porLocal)): ?>
            <p class="vazio">Nenhuma frente enviada no período.</p>
        <?php else: ?>
        <table class="trch">
            <thead>
                <tr>
                    <th>Logradouro</th>
                    <th>Trecho (PV mont. → jus.)</th>
                    <th style="text-align:right;width:60px;">Frentes</th>
                    <th style="text-align:right;width:60px;">Ramais</th>
                    <th style="text-align:right;width:85px;">Via (m)</th>
                    <th style="text-align:right;width:85px;">Calçada (m)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($porLocal as $l): ?>
                <tr>
                    <td><b><?= htmlspecialchars((string)($l['logradouro'] ?: '—')) ?></b></td>
                    <td style="font-size:10.5px;">
                        <?php if (!empty($l['pv_montante'])): ?>
                            <?= htmlspecialchars((string)$l['pv_montante']) ?> → <?= htmlspecialchars((string)($l['pv_jusante'] ?: '?')) ?>
                            <?php if (!empty($l['trecho_rua'])): ?>
                                <br><span style="color:#888;"><?= htmlspecialchars((string)$l['trecho_rua']) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:#888;">Sem trecho</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;"><?= (int)$l['qtd_frentes'] ?></td>
                    <td style="text-align:right;"><?= (int)$l['qtd_ramais'] ?></td>
                    <td style="text-align:right;font-weight:700;color:#1A2D4F;"><?= RamaisController::num($l['via_m'], 2) ?></td>
                    <td style="text-align:right;font-weight:700;color:#1A2D4F;"><?= RamaisController::num($l['calcada_m'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2"><b>Total do período</b></td>
                    <td style="text-align:right;"><b><?= $qtdFrentes ?></b></td>
                    <td style="text-align:right;"><b><?= $qtdRamais ?></b></td>
                    <td style="text-align:right;"><b><?= RamaisController::num($viaTotal, 2) ?></b></td>
                    <td style="text-align:right;"><b><?= RamaisController::num($calcTotal, 2) ?></b></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>

    <!-- Assinaturas -->
    <div class="linha-data">
        _______________, _____ de __________________ de ________
    </div>
    <div class="assinaturas">
        <div class="assinatura">
            <b>Responsável pelo Planejamento</b>
            <?= htmlspecialchars($usuario_nome) ?><br>
            <span style="font-size:9px;">Gravitas — Planejamento</span>
        </div>
        <div class="assinatura">
            <b>Visto / Aprovação</b>
            <br>
            <span style="font-size:9px;">______________________________</span>
        </div>
    </div>

    <!-- Rodapé -->
    <div class="rpt-footer">
        <span>BACIN · Relatório Físico de Ramais · <?= htmlspecialchars($num_doc) ?></span>
        <span>Gerado em <?= htmlspecialchars($data_ger) ?></span>
    </div>

</div>
</body>
</html>
