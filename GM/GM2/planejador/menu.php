<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/asset.php';
auth_required([3, 4]); // 3 = Planejador, 4 = Executor

/* O executor (4) entra só no fluxo de medição: lança e corrige medição,
   consulta planejamentos e relatórios. Criar planejamento e mexer nos
   cadastros é do planejador (3) — por isso os itens abaixo somem do menu
   dele. Não é só o menu: as telas em si recusam o nível 4. */
// (usa o helper eh_planejador(), definido em app/helpers/auth.php)
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Dashboard do Planejador | GM SERVIÇOS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- CSS ÚNICO OFICIAL -->
<link rel="stylesheet" href="<?= mu_asset('/assets/css/planejador.css') ?>">
</head>
<body>

<div class="app">

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="logo">
        <img src="/GM/GM2/assets/img/icon-gm.png" alt="GM SERVIÇOS">
        <span>GM SERVIÇOS</span>
    </div>

    <nav>
        <a href="/GM/GM2/planejador/menu.php" class="active">📊 Dashboard</a>

        <?php if (eh_planejador()): ?>
        <a href="/GM/GM2/planejador/planejamento.php">🗂 Novo Planejamento</a>
        <?php endif; ?>
        <a href="/GM/GM2/planejador/medicao.php">📐 Incluir Medição</a>
        <a href="/GM/GM2/planejador/planejamento_selecionar.php">📋 Planejamentos</a>
        <a href="/GM/GM2/planejador/relatorio.php">📄 Relatórios</a>
        <?php if (eh_planejador()): ?>
        <a href="/GM/GM2/planejador/equipamentos_pesados.php">🚜 Equip. Pesados</a>
        <a href="/GM/GM2/planejador/equipamentos_leves.php">🧰 Equip. Leves</a>
        <a href="/GM/GM2/planejador/funcionarios.php">👷 Funcionários</a>
        <?php endif; ?>
        <a href="/GM/GM2/public/logout.php">🚪 Sair</a>
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
