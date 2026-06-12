<?php
session_start();

require_once __DIR__ . '/app/config/app.php';

/* ==========================
   AUTENTICAÇÃO
========================== */
require_once __DIR__ . '/app/helpers/auth.php';
auth_required([3, 4]); // 3=Master Gravitas (admin), 4=Planejador

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
    // Master Gravitas vai direto para gestão de usuários
    if ((int)($_SESSION['nivel'] ?? 0) === 3) {
        header('Location: ' . APP_BASE . '/admin/usuarios');
        exit;
    }
    require_once __DIR__ . '/app/controllers/PlanejadorController.php';
    (new PlanejadorController())->dashboard();
    exit;
}

/* ==========================
   ADMIN — Usuários & Acessos
========================== */
if ($uri === '/admin/usuarios') {
    require_once __DIR__ . '/app/controllers/AdminController.php';
    (new AdminController())->usuarios();
    exit;
}

if ($uri === '/admin/salvar-usuario') {
    require_once __DIR__ . '/app/controllers/AdminController.php';
    (new AdminController())->salvarUsuario();
    exit;
}

if ($uri === '/admin/resetar-senha') {
    require_once __DIR__ . '/app/controllers/AdminController.php';
    (new AdminController())->resetarSenha();
    exit;
}

if ($uri === '/admin/toggle-ativo') {
    require_once __DIR__ . '/app/controllers/AdminController.php';
    (new AdminController())->toggleAtivo();
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
   TRECHOS & OS
========================== */
if ($uri === '/trechos') {
    require_once __DIR__ . '/app/controllers/TrechoController.php';
    (new TrechoController())->index();
    exit;
}

if ($uri === '/trechos/cadastrar') {
    require_once __DIR__ . '/app/controllers/TrechoController.php';
    (new TrechoController())->create();
    exit;
}

if ($uri === '/trechos/salvar') {
    require_once __DIR__ . '/app/controllers/TrechoController.php';
    (new TrechoController())->store();
    exit;
}

if ($uri === '/trechos/editar') {
    require_once __DIR__ . '/app/controllers/TrechoController.php';
    (new TrechoController())->edit();
    exit;
}

if ($uri === '/trechos/atualizar') {
    require_once __DIR__ . '/app/controllers/TrechoController.php';
    (new TrechoController())->update();
    exit;
}

if ($uri === '/trechos/upload-os') {
    require_once __DIR__ . '/app/controllers/TrechoController.php';
    (new TrechoController())->uploadOS();
    exit;
}

if ($uri === '/trechos/material-add') {
    require_once __DIR__ . '/app/controllers/TrechoController.php';
    (new TrechoController())->addMaterial();
    exit;
}

if ($uri === '/trechos/material-remove') {
    require_once __DIR__ . '/app/controllers/TrechoController.php';
    (new TrechoController())->removeMaterial();
    exit;
}
if ($uri === '/trechos/importar') {
    require_once __DIR__ . '/app/controllers/TrechoController.php';
    (new TrechoController())->importar();
    exit;
}

/* ==========================
   TOPOGRAFIA
========================== */
if ($uri === '/topografia') {
    require_once __DIR__ . '/app/controllers/TopografiaController.php';
    (new TopografiaController())->index();
    exit;
}
if ($uri === '/topografia/importar') {
    require_once __DIR__ . '/app/controllers/TopografiaController.php';
    (new TopografiaController())->importar();
    exit;
}
if (preg_match('#^/topografia/(\d+)/liberar$#', $uri, $m)) {
    require_once __DIR__ . '/app/controllers/TopografiaController.php';
    (new TopografiaController())->liberar((int)$m[1]);
    exit;
}
if (preg_match('#^/topografia/(\d+)/declividade$#', $uri, $m)) {
    require_once __DIR__ . '/app/controllers/TopografiaController.php';
    (new TopografiaController())->editarDeclividade((int)$m[1]);
    exit;
}
if (preg_match('#^/topografia/(\d+)/ver$#', $uri, $m)) {
    require_once __DIR__ . '/app/controllers/TopografiaController.php';
    (new TopografiaController())->verOS((int)$m[1]);
    exit;
}

if ($uri === '/materiais/importar') {
    require_once __DIR__ . '/app/controllers/MaterialController.php';
    (new MaterialController())->importar();
    exit;
}
if ($uri === '/materiais/importar-estoque') {
    require_once __DIR__ . '/app/controllers/MaterialController.php';
    (new MaterialController())->importarEstoque();
    exit;
}

/* ==========================
   CAMINHAMENTOS
========================== */
if ($uri === '/caminhamentos') {
    require_once __DIR__ . '/app/controllers/CaminhamentoController.php';
    (new CaminhamentoController())->index();
    exit;
}

if ($uri === '/caminhamentos/cadastrar') {
    require_once __DIR__ . '/app/controllers/CaminhamentoController.php';
    (new CaminhamentoController())->create();
    exit;
}

if ($uri === '/caminhamentos/salvar') {
    require_once __DIR__ . '/app/controllers/CaminhamentoController.php';
    (new CaminhamentoController())->store();
    exit;
}

if ($uri === '/caminhamentos/publicar') {
    require_once __DIR__ . '/app/controllers/CaminhamentoController.php';
    (new CaminhamentoController())->publicar();
    exit;
}

if ($uri === '/caminhamentos/detalhe') {
    require_once __DIR__ . '/app/controllers/CaminhamentoController.php';
    (new CaminhamentoController())->detalhe();
    exit;
}

if ($uri === '/caminhamentos/concluir-trecho') {
    require_once __DIR__ . '/app/controllers/CaminhamentoController.php';
    (new CaminhamentoController())->concluirTrecho();
    exit;
}

if ($uri === '/caminhamentos/relatorio-materiais') {
    require_once __DIR__ . '/app/controllers/CaminhamentoController.php';
    (new CaminhamentoController())->relatorioMateriais();
    exit;
}

if ($uri === '/caminhamentos/relatorio-medicao') {
    require_once __DIR__ . '/app/controllers/CaminhamentoController.php';
    (new CaminhamentoController())->relatorioMedicao();
    exit;
}

if ($uri === '/caminhamentos/excluir') {
    require_once __DIR__ . '/app/controllers/CaminhamentoController.php';
    (new CaminhamentoController())->excluir();
    exit;
}

if ($uri === '/caminhamentos/adicionar-trechos') {
    require_once __DIR__ . '/app/controllers/CaminhamentoController.php';
    (new CaminhamentoController())->adicionarTrechos();
    exit;
}

/* ==========================
   MATERIAIS
========================== */
if ($uri === '/materiais') {
    require_once __DIR__ . '/app/controllers/MaterialController.php';
    (new MaterialController())->index();
    exit;
}

if ($uri === '/materiais/cadastrar') {
    require_once __DIR__ . '/app/controllers/MaterialController.php';
    (new MaterialController())->create();
    exit;
}

if ($uri === '/materiais/salvar') {
    require_once __DIR__ . '/app/controllers/MaterialController.php';
    (new MaterialController())->store();
    exit;
}

if ($uri === '/materiais/movimento') {
    require_once __DIR__ . '/app/controllers/MaterialController.php';
    (new MaterialController())->movimento();
    exit;
}

/* ==========================
   FRENTES DE REPAVIMENTAÇÃO
========================== */
if ($uri === '/repavimentacao/frentes') {
    require_once __DIR__ . '/app/controllers/FrenteRepavController.php';
    (new FrenteRepavController())->index();
    exit;
}

if ($uri === '/repavimentacao/frentes/cadastrar') {
    require_once __DIR__ . '/app/controllers/FrenteRepavController.php';
    (new FrenteRepavController())->create();
    exit;
}

if ($uri === '/repavimentacao/frentes/salvar') {
    require_once __DIR__ . '/app/controllers/FrenteRepavController.php';
    (new FrenteRepavController())->store();
    exit;
}

if ($uri === '/repavimentacao/frentes/publicar') {
    require_once __DIR__ . '/app/controllers/FrenteRepavController.php';
    (new FrenteRepavController())->publicar();
    exit;
}

if ($uri === '/repavimentacao/frentes/excluir') {
    require_once __DIR__ . '/app/controllers/FrenteRepavController.php';
    (new FrenteRepavController())->excluir();
    exit;
}

/* ==========================
   REPAVIMENTAÇÃO
========================== */
if ($uri === '/repavimentacao') {
    require_once __DIR__ . '/app/controllers/RepavimentacaoController.php';
    (new RepavimentacaoController())->index();
    exit;
}

if ($uri === '/repavimentacao/medicao') {
    require_once __DIR__ . '/app/controllers/RepavimentacaoController.php';
    (new RepavimentacaoController())->create();
    exit;
}

if ($uri === '/repavimentacao/salvar-pavimento') {
    require_once __DIR__ . '/app/controllers/RepavimentacaoController.php';
    (new RepavimentacaoController())->salvarPavimento();
    exit;
}

if ($uri === '/repavimentacao/concluir-medicao') {
    require_once __DIR__ . '/app/controllers/RepavimentacaoController.php';
    (new RepavimentacaoController())->concluirMedicao();
    exit;
}

if ($uri === '/repavimentacao/upload-foto') {
    require_once __DIR__ . '/app/controllers/RepavimentacaoController.php';
    (new RepavimentacaoController())->uploadFoto();
    exit;
}

if ($uri === '/repavimentacao/relatorio') {
    require_once __DIR__ . '/app/controllers/RepavimentacaoController.php';
    (new RepavimentacaoController())->relatorio();
    exit;
}

/* ==========================
   DIÁRIOS DE EXECUÇÃO (Executor → Planejador)
========================== */
if ($uri === '/diarios') {
    require_once __DIR__ . '/app/controllers/DiarioExecucaoController.php';
    (new DiarioExecucaoController())->index();
    exit;
}
if ($uri === '/diarios/ver') {
    require_once __DIR__ . '/app/controllers/DiarioExecucaoController.php';
    (new DiarioExecucaoController())->ver();
    exit;
}
if ($uri === '/diarios/fotos') {
    require_once __DIR__ . '/app/controllers/DiarioExecucaoController.php';
    (new DiarioExecucaoController())->relatorioFotos();
    exit;
}
if ($uri === '/diarios/resolver-alerta') {
    require_once __DIR__ . '/app/controllers/DiarioExecucaoController.php';
    (new DiarioExecucaoController())->resolverAlerta();
    exit;
}
if ($uri === '/diarios/resolver-manutencao') {
    require_once __DIR__ . '/app/controllers/DiarioExecucaoController.php';
    (new DiarioExecucaoController())->resolverManutencao();
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
