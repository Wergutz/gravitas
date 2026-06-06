<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([4]); // Planejador

/* ===============================
   BUSCAR EXECUTORES
=============================== */
$stmt = $pdo->prepare("
    SELECT nome
    FROM usuarios
    WHERE tipo_usuario = 5
      AND ativo = 1
    ORDER BY nome
");
$stmt->execute();
$executores = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   BUSCAR EQUIPES ATIVAS
=============================== */
$stmt = $pdo->prepare("
    SELECT nome
    FROM equipes
    WHERE ativo = 1
    ORDER BY nome
");
$stmt->execute();
$equipesDisponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   BUSCAR RETROESCAVADEIRAS
=============================== */
$stmt = $pdo->prepare("
    SELECT modelo, placa
    FROM equipamentos_pesados
    WHERE ativo = 1
      AND tipo = 'RETROESCAVADEIRA'
    ORDER BY modelo
");
$stmt->execute();
$retros = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   BUSCAR CAÇAMBAS
=============================== */
$stmt = $pdo->prepare("
    SELECT modelo, placa
    FROM equipamentos_pesados
    WHERE ativo = 1
      AND tipo = 'CACAMBA'
    ORDER BY modelo
");
$stmt->execute();
$cacambas = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   SALVAR NOVA EQUIPE
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome           = trim($_POST['nome'] ?? '');
    $encarregado    = trim($_POST['encarregado'] ?? '');
    $usuarioSistema = trim($_POST['usuario_sistema'] ?? '');
    $divideFrente   = $_POST['divide_frente'] ?? 'NAO';
    $frenteNome     = ($divideFrente === 'SIM') ? ($_POST['frente_nome'] ?? null) : null;

    if ($nome === '' || $encarregado === '' || $usuarioSistema === '') {
        $_SESSION['erro'] = 'Preencha todos os campos obrigatórios.';
        header("Location: equipes_rede_nova.php");
        exit;
    }

    try {
        $pdo->beginTransaction();

        /* ===== EQUIPES ===== */
        $stmt = $pdo->prepare("
            INSERT INTO equipes
            (nome, responsavel, usuario_sistema, divide_frente, frente_nome)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nome,
            $encarregado,
            $usuarioSistema,
            $divideFrente,
            $frenteNome
        ]);

        $equipeId = $pdo->lastInsertId();

        /* ===== VEÍCULOS ===== */
        $stmt = $pdo->prepare("
            INSERT INTO equipes_veiculos
            (equipe_id, retro_modelo_placa, retro_operador,
             cacamba_modelo_placa, cacamba_motorista,
             divide_cacamba, frente_nome)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $equipeId,
            $_POST['retro_modelo'] ?? '',
            $_POST['retro_operador'] ?? '',
            $_POST['cacamba_modelo'] ?? '',
            $_POST['cacamba_motorista'] ?? '',
            $divideFrente,
            $frenteNome
        ]);

        /* ===== EQUIPAMENTOS LEVES ===== */
        $equip = $_POST['equipamentos'] ?? [];
        $stmt = $pdo->prepare("
            INSERT INTO equipes_equipamentos
            (equipe_id, compactador, placa, motobomba, cortadora)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $equipeId,
            $equip['compactador'] ?? 0,
            $equip['placa'] ?? 0,
            $equip['motobomba'] ?? 0,
            $equip['cortadora'] ?? 0
        ]);

        /* ===== FUNCIONÁRIOS ===== */
        if (!empty($_POST['funcionarios'])) {
            $stmt = $pdo->prepare("
                INSERT INTO equipes_funcionarios
                (equipe_id, numero, nome, funcao, sertras)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($_POST['funcionarios'] as $f) {
                if (trim($f['nome']) !== '') {
                    $stmt->execute([
                        $equipeId,
                        $f['numero'],
                        $f['nome'],
                        $f['funcao'],
                        $f['sertras']
                    ]);
                }
            }
        }

        $pdo->commit();
        header("Location: planejador.php?salvo=1");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['erro'] = 'Erro ao salvar equipe.';
        header("Location: equipes_rede_nova.php");
        exit;
    }
}

$erro = $_SESSION['erro'] ?? null;
unset($_SESSION['erro']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Vision Hub | Nova Equipe</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="/visionhub/assets/css/planejador.css">
<link rel="stylesheet" href="/visionhub/assets/css/equipes_rede.css">
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
        <a href="#" class="active">
            👥 Todas as Equipes
        </a>
        <a href="/visionhub/logout.php">
            <span class="vh-icon">🚪</span>
            <span class="vh-label">Sair</span>
        </a>
    </nav>
</aside>


<main class="content">

<header class="topbar">
    <h1>Cadastrar nova equipe de escavação de rede</h1>
</header>

<?php if ($erro): ?>
<div class="form-card alert"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<div class="form-card">
<form method="post">

<h3>Dados da Equipe</h3>

<div class="form-group">
    <label>Nome da equipe</label>
    <input type="text" name="nome" required>
</div>

<div class="form-group">
    <label>Responsável / Encarregado</label>
    <input type="text" name="encarregado" required>
</div>

<div class="form-group">
    <label>Usuário Executor</label>
    <select name="usuario_sistema" required>
        <option value="">Selecione</option>
        <?php foreach ($executores as $ex): ?>
            <option value="<?= htmlspecialchars($ex['nome']) ?>">
                <?= htmlspecialchars($ex['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label>Divide a caçamba com outra frente?</label>
    <select name="divide_frente" id="divide_frente">
        <option value="NAO">Não</option>
        <option value="SIM">Sim</option>
    </select>
</div>

<div class="form-group" id="frente_group" style="display:none;">
    <label>Se sim, qual equipe?</label>
    <select name="frente_nome">
        <option value="">Selecione a equipe</option>
        <?php foreach ($equipesDisponiveis as $eq): ?>
            <option value="<?= htmlspecialchars($eq['nome']) ?>">
                <?= htmlspecialchars($eq['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<h3>Equipamentos Pesados</h3>

<div class="form-group">
    <label>Retroescavadeira</label>
    <select name="retro_modelo">
        <option value="">Selecione</option>
        <?php foreach ($retros as $r): ?>
            <option value="<?= htmlspecialchars($r['modelo'].' '.$r['placa']) ?>">
                <?= htmlspecialchars($r['modelo']) ?> <?= htmlspecialchars($r['placa']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label>Operador da Retroescavadeira</label>
    <input type="text" name="retro_operador">
</div>

<div class="form-group">
    <label>Caçamba – Modelo / Placa</label>
    <select name="cacamba_modelo">
        <option value="">Selecione</option>
        <?php foreach ($cacambas as $c): ?>
            <option value="<?= htmlspecialchars($c['modelo'].' '.$c['placa']) ?>">
                <?= htmlspecialchars($c['modelo']) ?> <?= htmlspecialchars($c['placa']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label>Motorista da Caçamba</label>
    <input type="text" name="cacamba_motorista">
</div>

<h3>Equipamentos Leves</h3>

<div class="form-group"><label>Compactador</label><input type="number" name="equipamentos[compactador]" value="0"></div>
<div class="form-group"><label>Placa vibratória</label><input type="number" name="equipamentos[placa]" value="0"></div>
<div class="form-group"><label>Motobomba</label><input type="number" name="equipamentos[motobomba]" value="0"></div>
<div class="form-group"><label>Cortadora de asfalto</label><input type="number" name="equipamentos[cortadora]" value="0"></div>

<h3>Funcionários</h3>

<?php for ($i=1;$i<=7;$i++): ?>
<div class="func-row">
    <input type="number" name="funcionarios[<?= $i ?>][numero]" value="<?= $i ?>" style="width:60px">
    <input type="text" name="funcionarios[<?= $i ?>][nome]" placeholder="Nome">
    <input type="text" name="funcionarios[<?= $i ?>][funcao]" placeholder="Função">
    <select name="funcionarios[<?= $i ?>][sertras]">
        <option value="APTO">APTO</option>
        <option value="INAPTO">INAPTO</option>
    </select>
</div>
<?php endfor; ?>

<div class="form-actions">
    <button class="btn-primary">Cadastrar Equipe</button>
    <a href="/visionhub/planejador.php" class="btn-secondary">Cancelar</a>
</div>

</form>
</div>

</main>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const divide = document.getElementById("divide_frente");
    const frente = document.getElementById("frente_group");

    function toggle() {
        frente.style.display = divide.value === "SIM" ? "block" : "none";
    }

    divide.addEventListener("change", toggle);
    toggle();
});
</script>

</body>
</html>
