<?php
session_start();

require_once __DIR__ . '/app/config/app.php';

/* ==========================
   AUTENTICAÇÃO
========================== */
require_once __DIR__ . '/app/helpers/auth.php';
auth_required([4]); // Planejador

/* ==========================
   ROTEAMENTO BÁSICO
========================== */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove base do início da URL
$base = APP_BASE;
if (strpos($uri, $base) === 0) {
    $uri = substr($uri, strlen($base));
}

// Rota atual (sidebar)
$currentRoute = $uri;

/* ==========================
   DASHBOARD
========================== */
if ($uri === '' || $uri === '/') {
    require_once __DIR__ . '/app/controllers/PlanejadorController.php';
    (new PlanejadorController())->dashboard();
    exit;
}

/* ==========================
   EQUIPAMENTOS LEVES
========================== */
if ($uri === '/equipamentos-leves') {
    require_once __DIR__ . '/app/controllers/EquipamentoLeveController.php';
    (new EquipamentoLeveController())->index();
    exit;
}

if ($uri === '/equipamentos-leves/cadastrar') {
    require_once __DIR__ . '/app/controllers/EquipamentoLeveController.php';
    (new EquipamentoLeveController())->create();
    exit;
}

if ($uri === '/equipamentos-leves/salvar') {
    require_once __DIR__ . '/app/controllers/EquipamentoLeveController.php';
    (new EquipamentoLeveController())->store();
    exit;
}

if ($uri === '/equipamentos-leves/editar') {
    require_once __DIR__ . '/app/controllers/EquipamentoLeveController.php';
    (new EquipamentoLeveController())->edit();
    exit;
}

if ($uri === '/equipamentos-leves/atualizar') {
    require_once __DIR__ . '/app/controllers/EquipamentoLeveController.php';
    (new EquipamentoLeveController())->update();
    exit;
}

if ($uri === '/equipamentos-leves/inativar') {
    require_once __DIR__ . '/app/controllers/EquipamentoLeveController.php';
    (new EquipamentoLeveController())->toggle();
    exit;
}

if ($uri === '/equipamentos-leves/importar') {
    require_once __DIR__ . '/app/controllers/EquipamentoLeveController.php';
    (new EquipamentoLeveController())->importar();
    exit;
}

/* ==========================
   EQUIPAMENTOS PESADOS
========================== */
if ($uri === '/equipamentos-pesados') {
    require_once __DIR__ . '/app/controllers/EquipamentoPesadoController.php';
    (new EquipamentoPesadoController())->index();
    exit;
}

if ($uri === '/equipamentos-pesados/cadastrar') {
    require_once __DIR__ . '/app/controllers/EquipamentoPesadoController.php';
    (new EquipamentoPesadoController())->create();
    exit;
}

if ($uri === '/equipamentos-pesados/salvar') {
    require_once __DIR__ . '/app/controllers/EquipamentoPesadoController.php';
    (new EquipamentoPesadoController())->store();
    exit;
}

if ($uri === '/equipamentos-pesados/editar') {
    require_once __DIR__ . '/app/controllers/EquipamentoPesadoController.php';
    (new EquipamentoPesadoController())->edit();
    exit;
}

if ($uri === '/equipamentos-pesados/atualizar') {
    require_once __DIR__ . '/app/controllers/EquipamentoPesadoController.php';
    (new EquipamentoPesadoController())->update();
    exit;
}

if ($uri === '/equipamentos-pesados/inativar') {
    require_once __DIR__ . '/app/controllers/EquipamentoPesadoController.php';
    (new EquipamentoPesadoController())->toggle();
    exit;
}

if ($uri === '/equipamentos-pesados/importar') {
    require_once __DIR__ . '/app/controllers/EquipamentoPesadoController.php';
    (new EquipamentoPesadoController())->importar();
    exit;
}

/* ==========================
   EQUIPES
========================== */
if ($uri === '/equipes') {
    require_once __DIR__ . '/app/controllers/EquipeController.php';
    (new EquipeController())->listar();
    exit;
}

if ($uri === '/equipes/cadastrar') {
    require_once __DIR__ . '/app/controllers/EquipeController.php';
    (new EquipeController())->create();
    exit;
}

if ($uri === '/equipes/salvar') {
    require_once __DIR__ . '/app/controllers/EquipeController.php';
    (new EquipeController())->store();
    exit;
}

if ($uri === '/equipes/editar') {
    require_once __DIR__ . '/app/controllers/EquipeController.php';
    (new EquipeController())->edit();
    exit;
}

if ($uri === '/equipes/atualizar') {
    require_once __DIR__ . '/app/controllers/EquipeController.php';
    (new EquipeController())->update();
    exit;
}

if ($uri === '/equipes/apagar') {
    require_once __DIR__ . '/app/controllers/EquipeController.php';
    (new EquipeController())->apagar();
    exit;
}

/* ==========================
   FUNCIONÁRIOS
========================== */
if ($uri === '/funcionarios') {
    require_once __DIR__ . '/app/controllers/FuncionarioController.php';
    (new FuncionarioController())->index();
    exit;
}

if ($uri === '/funcionarios/cadastrar') {
    require_once __DIR__ . '/app/controllers/FuncionarioController.php';
    (new FuncionarioController())->create();
    exit;
}

if ($uri === '/funcionarios/salvar') {
    require_once __DIR__ . '/app/controllers/FuncionarioController.php';
    (new FuncionarioController())->store();
    exit;
}

if ($uri === '/funcionarios/editar') {
    require_once __DIR__ . '/app/controllers/FuncionarioController.php';
    (new FuncionarioController())->edit();
    exit;
}

if ($uri === '/funcionarios/atualizar') {
    require_once __DIR__ . '/app/controllers/FuncionarioController.php';
    (new FuncionarioController())->update();
    exit;
}

if ($uri === '/funcionarios/inativar') {
    require_once __DIR__ . '/app/controllers/FuncionarioController.php';
    (new FuncionarioController())->toggle();
    exit;
}

if ($uri === '/funcionarios/importar') {
    require_once __DIR__ . '/app/controllers/FuncionarioController.php';
    (new FuncionarioController())->importar();
    exit;
}
/* ==========================
   PLANEJAMENTOS
========================== */
if ($uri === '/planejamentos') {
    require_once __DIR__ . '/app/controllers/PlanejamentoController.php';
    (new PlanejamentoController())->index();
    exit;
}

if ($uri === '/planejamentos/cadastrar') {
    require_once __DIR__ . '/app/controllers/PlanejamentoController.php';
    (new PlanejamentoController())->create();
    exit;
}

if ($uri === '/planejamentos/salvar') {
    require_once __DIR__ . '/app/controllers/PlanejamentoController.php';
    (new PlanejamentoController())->store();
    exit;
}

if ($uri === '/planejamentos/editar') {
    require_once __DIR__ . '/app/controllers/PlanejamentoController.php';
    (new PlanejamentoController())->edit();
    exit;
}

if ($uri === '/planejamentos/atualizar') {
    require_once __DIR__ . '/app/controllers/PlanejamentoController.php';
    (new PlanejamentoController())->update();
    exit;
}

/* ==========================
   LOGOUT
========================== */
if ($uri === '/logout.php') {
    require_once __DIR__ . '/logout.php';
    exit;
}

/* ==========================
   404
========================== */
http_response_code(404);
echo "Página não encontrada";
