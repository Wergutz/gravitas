<?php
require_once __DIR__ . '/../app/helpers/auth.php';
auth_required([3]); // Planejador
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Dashboard do Planejador | VisionHub Locar</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- CSS ÚNICO OFICIAL -->
<link rel="stylesheet" href="/marco_urbano2/assets/css/planejador.css?v=1">
</head>
<body>

<div class="app">

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="logo">
        <img src="/marco_urbano2/assets/img/farol.png" alt="VisionHub">
        <span>VISION HUB</span>
    </div>

    <nav>
        <a href="/marco_urbano2/planejador/menu.php" class="active">📊 Dashboard</a>

        <!-- ÚNICA ALTERAÇÃO AQUI -->
        <a href="/marco_urbano2/planejador/planejamento.php">🗂 Novo Planejamento</a>
        <a href="/marco_urbano2/planejador/medicao.php">📐 Incluir Medição</a>
        <a href="/marco_urbano2/planejador/planejamento_selecionar.php">📋 Planejamentos</a>
        <a href="/marco_urbano2/planejador/relatorio.php">📄 Relatórios</a>
        <a href="/marco_urbano2/planejador/equipamentos_pesados.php">🚜 Equip. Pesados</a>
        <a href="/marco_urbano2/planejador/equipamentos_leves.php">🧰 Equip. Leves</a>
        <a href="/marco_urbano2/planejador/funcionarios.php">👷 Funcionários</a>
        <a href="/marco_urbano2/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<!-- CONTEÚDO -->
<main class="content">

<!-- TOPBAR -->
<div class="topbar">
    <div>
        <h1>Dashboard do Planejador</h1>
        <span>Visão geral operacional</span>
    </div>
    <div class="managed">MANAGED BY GRAVITAS</div>
</div>

<!-- CONTEÚDO PRINCIPAL -->
<div class="card">
    <h3>Bem-vindo</h3>
    <p style="color:#9ca3af; margin-top:10px;">
        Utilize o menu lateral para acessar os cadastros, planejamento e controle operacional.
    </p>
</div>

</main>
</div>

</body>
</html>
