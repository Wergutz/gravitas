<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([5]); // Executor

$executor_id = $_SESSION['usuario_id'];

/* ===============================
   BUSCAR EQUIPES DO EXECUTOR
   =============================== */
$stmt = $pdo->prepare("
    SELECT 
        e.id,
        e.nome
    FROM equipes e
    JOIN equipes_usuarios eu ON eu.equipe_id = e.id
    WHERE eu.usuario_id = ?
      AND eu.ativo = 1
    ORDER BY e.nome
");
$stmt->execute([$executor_id]);
$equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$equipes) {
    die('Você não está vinculado a nenhuma equipe.');
}

/* ===============================
   SELECIONAR EQUIPE
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $equipe_id = $_POST['equipe_id'] ?? null;

    foreach ($equipes as $e) {
        if ($e['id'] == $equipe_id) {
            $_SESSION['equipe_execucao_id']   = $e['id'];
            $_SESSION['equipe_execucao_nome'] = $e['nome'];

            header("Location: executor.php");
            exit;
        }
    }

    $erro = 'Equipe inválida.';
}
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

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="logo">
        <img src="/visionhub/assets/img/farol.png">
        <span>VISION HUB</span>
    </div>

    <nav>
        <a href="logout.php">🚪 Sair</a>
    </nav>
</aside>

<!-- CONTEÚDO -->
<main class="content">

<div class="topbar">
    <div>
        <h1>Selecione a Equipe</h1>
        <span>Escolha com qual equipe você irá executar hoje</span>
    </div>
    <div class="managed">MANAGED BY GRAVITAS</div>
</div>

<?php if (!empty($erro)): ?>
    <div class="form-card">
        <?= htmlspecialchars($erro) ?>
    </div>
<?php endif; ?>

<div class="form-card">
    <form method="post">

        <div class="form-group">
            <label>Equipe</label>
            <select name="equipe_id" required>
                <option value="">Selecione sua equipe</option>
                <?php foreach ($equipes as $e): ?>
                    <option value="<?= $e['id'] ?>">
                        <?= htmlspecialchars($e['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-actions">
            <button class="btn-primary">Entrar na Execução</button>
            <a href="logout.php" class="btn-secondary">Cancelar</a>
        </div>

    </form>
</div>

</main>
</div>

</body>
</html>
