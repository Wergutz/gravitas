<?php
session_start();

/*
  Aqui você pode manter depois:
  auth_required([4]); // Planejador
*/

// Mock de equipes (depois vem do banco)
$equipes = [
    'Rede 01 - João da Silva',
    'Rede 02 - Carlos Pereira'
];

// Dias da semana
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
            <img src="img/farol.png" alt="Vision Hub">
            <span>VISION HUB</span>
        </div>

        <nav>
            <a href="#">Dashboard</a>
            <a href="#">Planejamento Rede</a>
            <a href="#">Relatórios</a>
            <a href="#">Sair</a>
        </nav>
    </aside>

    <!-- CONTEÚDO -->
    <main class="content">

        <!-- TOPO -->
        <div class="topbar">
            <div>
                <h1>Planejamento de Produção - Rede</h1>
                <span>Definição dos trechos diários</span>
            </div>
            <div class="managed">MANAGED BY GRAVITAS</div>
        </div>

        <!-- FORMULÁRIO -->
        <div class="form-card">

            <form method="post" action="#">

                <!-- INFORMAÇÕES GERAIS -->
                <div class="form-group">
                    <label>Equipe</label>
                    <select name="equipe" required>
                        <option value="">Selecione a equipe</option>
                        <?php foreach ($equipes as $eq): ?>
                            <option value="<?= $eq ?>"><?= $eq ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Dia da Semana</label>
                    <select name="dia_semana" required>
                        <option value="">Selecione o dia</option>
                        <?php foreach ($diasSemana as $key => $dia): ?>
                            <option value="<?= $key ?>"><?= $dia ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <hr style="margin:25px 0; opacity:0.3;">

                <!-- TRECHOS -->
                <div id="trechos">

                    <div class="trecho card" style="margin-bottom:20px;">
                        <h3 style="margin-bottom:15px;">Trecho 1</h3>

                        <div class="form-group">
                            <label>Local / Rua</label>
                            <input type="text" name="trecho[0][local]" placeholder="Ex: Rua das Flores" required>
                        </div>

                        <div class="form-group">
                            <label>Comprimento (m)</label>
                            <input type="number" step="0.01" name="trecho[0][comprimento]" placeholder="Ex: 120" required>
                        </div>

                        <div class="form-group">
                            <label>Largura (m)</label>
                            <input type="number" step="0.01" name="trecho[0][largura]" placeholder="Ex: 3.5" required>
                        </div>

                        <div class="form-group">
                            <label>Observações</label>
                            <textarea name="trecho[0][obs]" rows="3"></textarea>
                        </div>
                    </div>

                </div>

                <!-- AÇÕES -->
                <div class="form-actions">

                    <!-- BOTÃO AZUL -->
                    <button type="button" class="btn-info" onclick="adicionarTrecho()">
                        + Adicionar novo trecho
                    </button>

                    <!-- SALVAR -->
                    <button type="submit" class="btn-primary">
                        Salvar Planejamento
                    </button>

                </div>

            </form>

        </div>

    </main>

</div>

<script>
let contadorTrechos = 1;

function adicionarTrecho() {
    const container = document.getElementById('trechos');

    const div = document.createElement('div');
    div.className = 'trecho card';
    div.style.marginBottom = '20px';

    div.innerHTML = `
        <h3 style="margin-bottom:15px;">Trecho ${contadorTrechos + 1}</h3>

        <div class="form-group">
            <label>Local / Rua</label>
            <input type="text" name="trecho[${contadorTrechos}][local]" required>
        </div>

        <div class="form-group">
            <label>Comprimento (m)</label>
            <input type="number" step="0.01" name="trecho[${contadorTrechos}][comprimento]" required>
        </div>

        <div class="form-group">
            <label>Largura (m)</label>
            <input type="number" step="0.01" name="trecho[${contadorTrechos}][largura]" required>
        </div>

        <div class="form-group">
            <label>Observações</label>
            <textarea name="trecho[${contadorTrechos}][obs]" rows="3"></textarea>
        </div>
    `;

    container.appendChild(div);
    contadorTrechos++;
}
</script>

</body>
</html>
