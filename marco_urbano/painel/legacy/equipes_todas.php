<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([4]); // Planejador

/* ===============================
   BUSCAR TODAS AS EQUIPES
   =============================== */
$stmt = $pdo->query("
    SELECT id, nome, responsavel, ativo, created_at
    FROM equipes
    ORDER BY nome
");
$equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Vision Hub | Todas as Equipes</title>
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
        <a href="/visionhub/planejador.php">📊 Dashboard</a>
        <a href="/visionhub/equipes_rede_nova.php">👷 Cadastrar Equipe</a>
        <a href="/visionhub/equipes_rede.php">✏️ Editar Equipes</a>
        <a href="/visionhub/equipes_todas.php" class="active">👥 Todas as Equipes</a>
        <a href="/visionhub/logout.php">🚪 Sair</a>
    </nav>
</aside>

<!-- CONTEÚDO -->
<main class="content">

<div class="topbar">
    <div>
        <h1>Todas as Equipes</h1>
        <span>Ativar ou desativar equipes cadastradas</span>
    </div>
    <div class="managed">MANAGED BY GRAVITAS</div>
</div>

<div class="card">

<table class="table">
    <thead>
        <tr>
            <th>Equipe</th>
            <th>Responsável</th>
            <th>Criada em</th>
            <th>Status</th>
            <th style="width:160px;">Ações</th>
        </tr>
    </thead>
    <tbody>

    <?php foreach ($equipes as $e): ?>
        <tr>
            <td><?= htmlspecialchars($e['nome']) ?></td>
            <td><?= htmlspecialchars($e['responsavel']) ?></td>
            <td><?= date('d/m/Y', strtotime($e['created_at'])) ?></td>
            <td>
                <?php if ($e['ativo']): ?>
                    <span class="badge badge-success">ATIVA</span>
                <?php else: ?>
                    <span class="badge badge-danger">INATIVA</span>
                <?php endif; ?>
            </td>
<td>
    <div style="display:flex; gap:8px;">
        
        <a href="/visionhub/equipe_detalhe.php?id=<?= $e['id'] ?>"
           class="btn-info btn-sm">
            Ver equipe
        </a>

        <?php if ($e['ativo']): ?>
            <a href="/visionhub/toggle_equipe.php?id=<?= $e['id'] ?>&acao=desativar"
               class="btn-secondary btn-sm"
               onclick="return confirm('Deseja desativar esta equipe?');">
                Desativar
            </a>
        <?php else: ?>
            <a href="/visionhub/toggle_equipe.php?id=<?= $e['id'] ?>&acao=ativar"
               class="btn-secondary btn-sm"
               onclick="return confirm('Deseja reativar esta equipe?');">
                Ativar
            </a>
        <?php endif; ?>

    </div>
</td>


        </tr>
    <?php endforeach; ?>

    </tbody>
</table>

</div>

</main>
</div>

</body>
</html>
