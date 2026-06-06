<?php
/**
 * View: Dashboard do Planejador
 * Variáveis disponíveis:
 * - $total_planejamentos_ativos
 * - $total_equipes_ativas
 * - $total_funcionarios_ativos
 */

$title = 'Dashboard do Planejador';
$pageTitle = 'Dashboard do Planejador de Redes';
$pageSubtitle = 'Visão geral da produção e gestão';

ob_start();
?>

<div class="dashboard">

    <!-- ===============================
         KPIs
         =============================== -->
    <div class="dashboard-kpis">

        <div class="card">
            <strong>Planejamentos Ativos</strong>
            <h2 style="margin-top:10px;">
                <?= $total_planejamentos_ativos ?>
            </h2>
        </div>

        <div class="card">
            <strong>Equipes Ativas</strong>
            <h2 style="margin-top:10px;">
                <?= $total_equipes_ativas ?>
            </h2>
        </div>

        <div class="card">
            <strong>Funcionários Ativos</strong>
            <h2 style="margin-top:10px;">
                <?= $total_funcionarios_ativos ?>
            </h2>
        </div>

    </div>

    <!-- ===============================
         AÇÕES PRINCIPAIS
         =============================== -->
<div class="dashboard-actions">

    <a href="<?= APP_BASE ?>/planejamentos/cadastrar" class="feature-card">
        <h3>Novo Planejamento</h3>
        <p>Criar novo planejamento de rede.</p>
    </a>

    <a href="<?= APP_BASE ?>/planejamentos" class="feature-card">
        <h3>Planejamentos Ativos</h3>
        <p>Acompanhar planejamentos em andamento.</p>
    </a>

    <a href="<?= APP_BASE ?>/equipes" class="feature-card">
        <h3>Equipes</h3>
        <p>Visualizar e gerenciar equipes.</p>
    </a>

    <a href="<?= APP_BASE ?>/funcionarios" class="feature-card">
        <h3>Funcionários</h3>
        <p>Cadastro e controle de ASO, NRs e integrações.</p>
    </a>

    <a href="<?= APP_BASE ?>/equipamentos-pesados" class="feature-card">
        <h3>Equipamentos Pesados</h3>
        <p>Gerenciar frota pesada.</p>
    </a>

    <a href="<?= APP_BASE ?>/equipamentos-leves" class="feature-card">
        <h3>Equipamentos Leves</h3>
        <p>Gerenciar equipamentos leves.</p>
    </a>

</div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
