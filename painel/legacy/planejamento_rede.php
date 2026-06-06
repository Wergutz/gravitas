<?php
session_start();
// auth_required([4]);

require_once __DIR__ . '/app/config/database.php';

$stmt = $pdo->query("
    SELECT id, nome
    FROM equipes
    WHERE ativo = 1
    ORDER BY nome
");
$equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

<aside class="sidebar">
    <div class="logo">
        <img src="/visionhub/assets/img/farol.png">
        <span>VISION HUB</span>
    </div>
    <nav>
        <a href="/visionhub/planejador.php">
            <span class="vh-icon">📊</span>
            <span class="vh-label">Dashboard</span>
        </a>
        <a href="/visionhub/planejamento_rede.php" class="active">
            <span class="vh-icon">🗓️</span>
            <span class="vh-label">Planejamento Rede</span>
        </a>
        <a href="/visionhub/logout.php">
            <span class="vh-icon">🚪</span>
            <span class="vh-label">Sair</span>
        </a>
    </nav>
</aside>

<main class="content">

<div class="topbar">
    <div>
        <h1>Planejamento Produção Rede</h1>
        <span>Organização diária da produção</span>
    </div>
    <div class="managed">MANAGED BY GRAVITAS</div>
</div>

<form method="post" action="salvar_planejamento_rede.php" enctype="multipart/form-data">

<div class="form-card">
    <div class="form-group">
        <label>Equipe</label>
        <select name="equipe_id" required>
            <option value="">Selecione a equipe</option>
            <?php foreach ($equipes as $e): ?>
                <option value="<?= $e['id'] ?>">
                    <?= htmlspecialchars($e['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div id="diasPlanejamento">
    <div class="form-actions" id="botaoAdicionarDia" style="margin-bottom:30px;">
        <button type="button" class="btn-info" onclick="adicionarDia()">
            + Adicionar dia de planejamento
        </button>
    </div>
</div>

<div class="form-actions">
    <button class="btn-primary">Salvar Planejamento</button>
</div>

</form>

</main>
</div>

<script>
const diasSemana = ['DOMINGO','SEGUNDA','TERÇA','QUARTA','QUINTA','SEXTA','SÁBADO'];
let diasAdicionados = 0;
const maxDias = 28;

function adicionarDia() {
    if (diasAdicionados >= maxDias) return alert('Limite de 4 semanas atingido');

    const index = diasAdicionados++;
    const container = document.getElementById('diasPlanejamento');
    const botao = document.getElementById('botaoAdicionarDia');

    const card = document.createElement('div');
    card.className = 'form-card';
    card.style.marginBottom = '30px';

    card.innerHTML = `
        <h2 class="dia-titulo">Dia ${index + 1}</h2>
        <div class="form-group">
            <label>Data</label>
            <input type="date" name="planejamento[${index}][data]"
                   onchange="atualizarDiaSemana(this)" required>
        </div>
        <div class="trechos"></div>
        <div class="form-actions">
            <button type="button" class="btn-info"
                onclick="adicionarTrecho(this, ${index})">
                + Adicionar trecho
            </button>
        </div>
    `;

    container.insertBefore(card, botao);
    adicionarTrecho(card.querySelector('.btn-info'), index);
}

function atualizarDiaSemana(input) {
    const d = new Date(input.value + 'T00:00');
    input.closest('.form-card').querySelector('.dia-titulo').innerText =
        diasSemana[d.getDay()];
}

function adicionarTrecho(btn, diaIndex) {
    const trechos = btn.closest('.form-card').querySelector('.trechos');
    const t = trechos.children.length;

    const div = document.createElement('div');
    div.className = 'form-card';
    div.style.marginTop = '20px';

    div.innerHTML = `
        <h3>Trecho ${t + 1}</h3>

        <div class="form-group">
            <label>PV Montante</label>
            <input type="text"
                name="planejamento[${diaIndex}][trechos][${t}][pv_montante]" required>
        </div>

        <div class="form-group">
            <label>PV Juzante</label>
            <input type="text"
                name="planejamento[${diaIndex}][trechos][${t}][pv_juzante]" required>
        </div>

        <div class="form-group">
            <label>Comprimento (m)</label>
            <input type="number" step="0.01"
                name="planejamento[${diaIndex}][trechos][${t}][comprimento]" required>
        </div>

        <div class="form-group">
            <label>Ramais</label>
            <input type="number"
                name="planejamento[${diaIndex}][trechos][${t}][ramais]" required>
        </div>

        <div class="form-group">
            <label>OS do Trecho (PDF ou imagem)</label>
            <input type="file"
                name="planejamento[${diaIndex}][trechos][${t}][os_pdf]"
                accept=".pdf,image/*">
        </div>
    `;

    trechos.appendChild(div);
}
</script>

</body>
</html>
