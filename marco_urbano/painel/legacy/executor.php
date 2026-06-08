<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([5]); // Executor

$executor_id = $_SESSION['usuario_id'];

/* =========================================
   BUSCAR EQUIPES DO EXECUTOR (POR ID)
   ========================================= */
$stmt = $pdo->prepare("
    SELECT 
        e.id,
        e.nome
    FROM equipes e
    INNER JOIN equipes_usuarios eu 
        ON eu.equipe_id = e.id
    WHERE eu.usuario_id = ?
      AND eu.ativo = 1
    ORDER BY e.nome, e.id
");
$stmt->execute([$executor_id]);
$equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$equipes) {
    die('Executor não está vinculado a nenhuma equipe ativa.');
}

/* =========================================
   DEFINIR EQUIPE DE EXECUÇÃO (SOMENTE VIA POST)
   ========================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipe_id'])) {
    $equipe_id_post = (int)$_POST['equipe_id'];

    foreach ($equipes as $e) {
        if ((int)$e['id'] === $equipe_id_post) {
            $_SESSION['equipe_execucao_id']   = $e['id'];
            $_SESSION['equipe_execucao_nome'] = $e['nome'];
            break;
        }
    }
}

/* =========================================
   SE NÃO HÁ EQUIPE NA SESSÃO → FORÇAR ESCOLHA
   ========================================= */
if (!isset($_SESSION['equipe_execucao_id'])) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Vision Hub | Selecionar Equipe</title>
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
                <a href="logout.php">🚪 Sair</a>
            </nav>
        </aside>

        <main class="content">

            <div class="topbar">
                <div>
                    <h1>Selecionar Equipe</h1>
                    <span>Escolha a equipe para executar hoje</span>
                </div>
                <div class="managed">MANAGED BY GRAVITAS</div>
            </div>

            <div class="form-card">
                <form method="post">
                    <div class="form-group">
                        <label>Equipe</label>
                        <select name="equipe_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($equipes as $e): ?>
                                <option value="<?= $e['id'] ?>">
                                    [<?= $e['id'] ?>] <?= htmlspecialchars($e['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button class="btn-primary">Entrar</button>
                        <a href="logout.php" class="btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>

        </main>
    </div>

    </body>
    </html>
    <?php
    exit;
}

/* =========================================
   BUSCAR PLANEJAMENTOS DA EQUIPE SELECIONADA
   ========================================= */
$equipe_id   = $_SESSION['equipe_execucao_id'];
$equipe_nome = $_SESSION['equipe_execucao_nome'];

$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.data_criacao,
        u.nome AS planejador
    FROM planejamentos p
    INNER JOIN usuarios u ON u.id = p.planejador_id
    WHERE p.status = 'ATIVO'
      AND p.equipe_id = ?
    ORDER BY p.data_criacao DESC
");
$stmt->execute([$equipe_id]);
$planejamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Vision Hub | Execução</title>
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

    <div class="form-card" style="margin-bottom:20px;">
        <strong>Executor:</strong><br>
        <?= htmlspecialchars($_SESSION['nome']) ?><br><br>

        <strong>Equipe em execução:</strong><br>
        [<?= $equipe_id ?>] <?= htmlspecialchars($equipe_nome) ?>
    </div>

    <nav>
        <a href="executor.php">🛠️ Execução</a>
        <a href="logout.php">🚪 Sair</a>
    </nav>
</aside>

<!-- CONTEÚDO -->
<main class="content">

<div class="topbar">
    <div>
        <h1>Execução de Produção</h1>
        <span>Registrar atividades da equipe</span>
    </div>
    <div class="managed">MANAGED BY GRAVITAS</div>
</div>

<?php if (!$planejamentos): ?>
    <div class="form-card">
        Nenhum planejamento ativo para esta equipe.
    </div>
<?php endif; ?>

<?php foreach ($planejamentos as $p): ?>
<div class="form-card" style="margin-bottom:25px;">
    <strong>Planejamento criado em:</strong>
    <?= date('d/m/Y', strtotime($p['data_criacao'])) ?><br>
    <strong>Planejador:</strong>
    <?= htmlspecialchars($p['planejador']) ?><br><br>

    <form method="post" action="executor_execucao_salvar.php">

        <input type="hidden" name="planejamento_id" value="<?= $p['id'] ?>">

        <div class="form-group">
            <label>Trecho executado</label>
            <input type="text" name="trecho" required>
        </div>

        <div class="form-group">
            <label>Comprimento executado (m)</label>
            <input type="number" step="0.01" name="comprimento" required>
        </div>

        <div class="form-group">
            <label>Quantidade de ramais</label>
            <input type="number" name="ramais">
        </div>

        <div class="form-group">
            <label>Observações</label>
            <textarea name="observacoes"></textarea>
        </div>

        <div class="form-actions">
            <button class="btn-primary">Registrar Execução</button>
        </div>

    </form>
</div>
<?php endforeach; ?>

</main>
</div>

</body>
</html>
