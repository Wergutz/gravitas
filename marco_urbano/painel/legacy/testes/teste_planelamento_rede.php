<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/helpers/auth.php';
auth_required([4]); // Planejador

// MOCK DE EQUIPES (depois vem do banco)
$equipes = [
    'Rede 01 - João da Silva',
    'Rede 02 - Carlos Pereira'
];

$diasSemana = [
    'segunda' => 'SEGUNDA-FEIRA',
    'terca'   => 'TERÇA-FEIRA',
    'quarta' => 'QUARTA-FEIRA',
    'quinta' => 'QUINTA-FEIRA',
    'sexta'  => 'SEXTA-FEIRA'
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Vision Hub | Planejamento Produção Rede</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="/visionhub/assets/css/planejador.css">
</head>
<body>

<div class="app">

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="logo">
        <img src="/visionhub/assets/img/farol.png">
        <span>VISION HUB</span>
    </div>
    <nav>
        <a href="planejamento_producao.php">⬅ Voltar</a>
        <a href="logout.php">🚪 Sair</a>
    </nav>
</aside>

<!-- CONTEÚDO -->
<main class="content">

<header class="topbar">
    <div class="title">
        <h1>Planejamento Produção – Rede</h1>
        <span>Organização semanal da produção por equipe</span>
    </div>
    <div class="right">
        <span class="managed">MANAGED BY GRAVITAS</span>
    </div>
</header>

<!-- SELEÇÃO DE EQUIPE -->
<div class="form-card">
    <div class="form-group">
        <label>Equipe</label>
        <select>
            <option value="">ESCOLHER ENTRE AS EQUIPES JÁ CADASTRADAS</option>
            <?php foreach ($equipes as $e): ?>
                <option><?= htmlspecialchars($e) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- PLANEJAMENTO SEMANAL -->
<?php foreach ($diasSemana as $key => $label): ?>
<div class="form-card" style="margin-top:25px;">

    <h3 style="text-align:center;"><?= $label ?></h3>

    <div class="form-group">
        <label>Data</label>
        <input type="date">
    </div>

    <!-- TRECHO 1 (BASE) -->
    <div class="card" style="margin-top:15px;">
        <h4>TRECHO 1</h4>

        <div class="form-group">
            <label>PV Montante</label>
            <input type="text">
        </div>

        <div class="form-group">
            <label>PV Juzante</label>
            <input type="text">
        </div>

        <div class="form-group">
            <label>Comprimento do Trecho</label>
            <input type="text">
        </div>

        <div class="form-group">
            <label>Número de Ramais</label>
            <input type="number">
        </div>

        <div class="form-group">
            <label>Upload OS (PDF)</label>
            <input type="file" accept=".pdf">
        </div>
    </div>

    <div class="form-actions">
        <button class="btn-primary">
            ➕ Adicionar Novo Trecho
        </button>
    </div>

</div>
<?php endforeach; ?>

<!-- AÇÕES -->
<div class="form-actions" style="margin-top:30px;">
    <button class="btn-primary">
        💾 Salvar Planejamento
    </button>

    <a href="planejamento_producao.php" class="btn-secondary">
        Cancelar
    </a>
</div>

</main>
</div>

</body>
</html>
