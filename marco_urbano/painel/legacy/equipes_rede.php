<?php
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([4]); // Planejador

/* =========================
   LISTA DE EQUIPES ATIVAS
========================= */
$equipes = $pdo->query("
    SELECT id, nome
    FROM equipes
    WHERE ativo = 1
    ORDER BY nome
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   LISTA DE USUÁRIOS EXECUTORES
========================= */
$executores = $pdo->query("
    SELECT nome
    FROM usuarios
    WHERE tipo_usuario = 5
      AND ativo = 1
    ORDER BY nome
")->fetchAll(PDO::FETCH_COLUMN);

/* =========================
   EQUIPE SELECIONADA
========================= */
$equipe = null;
$veiculos = [];
$equip = [];
$funcs = [];

if (!empty($_GET['id'])) {
    $id = (int)$_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM equipes WHERE id = ?");
    $stmt->execute([$id]);
    $equipe = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM equipes_veiculos WHERE equipe_id = ?");
    $stmt->execute([$id]);
    $veiculos = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmt = $pdo->prepare("SELECT * FROM equipes_equipamentos WHERE equipe_id = ?");
    $stmt->execute([$id]);
    $equip = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmt = $pdo->prepare("
        SELECT * FROM equipes_funcionarios
        WHERE equipe_id = ?
        ORDER BY numero
    ");
    $stmt->execute([$id]);
    $funcs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Vision Hub | Editar Equipe de Escavação de Rede</title>
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
            <a href="/visionhub/equipes_rede.php" class="active">
                <span class="vh-icon">✏️</span>
                <span class="vh-label">Editar equipes de Rede</span>
            </a>
        <a href="/visionhub/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content">

<header class="topbar">
    <div>
        <h1>Editar equipe de escavação de rede</h1>
        <span>Selecione uma equipe para editar</span>
    </div>
</header>

<div class="form-card">
<form method="get">
    <div class="form-group">
        <label>Equipe cadastrada</label>
        <select name="id" onchange="this.form.submit()">
            <option value="">— Selecione uma equipe —</option>
            <?php foreach ($equipes as $e): ?>
                <option value="<?= $e['id'] ?>" <?= ($equipe && $equipe['id']==$e['id'])?'selected':'' ?>>
                    <?= htmlspecialchars($e['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>
</div>

<?php if ($equipe): ?>
<div class="form-card" style="margin-top:20px;">
<form method="post" action="salvar_equipe.php">

<input type="hidden" name="id" value="<?= $equipe['id'] ?>">

<h3>Dados da Equipe</h3>

<div class="form-group">
    <label>Nome da equipe</label>
    <input type="text" name="nome" value="<?= htmlspecialchars($equipe['nome']) ?>" required>
</div>

<div class="form-group">
    <label>Responsável / Encarregado</label>
    <input type="text" name="encarregado" value="<?= htmlspecialchars($equipe['responsavel'] ?? '') ?>" required>
</div>

<!-- 🔽 CAMPO ATUALIZADO CONFORME SOLICITADO -->
<div class="form-group">
    <label>Usuário Executor</label>
    <select name="usuario_sistema">
        <option value="">— Selecione —</option>
        <?php foreach ($executores as $nome): ?>
            <option value="<?= htmlspecialchars($nome) ?>"
                <?= ($equipe['usuario_sistema'] === $nome) ? 'selected' : '' ?>>
                <?= htmlspecialchars($nome) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label>Divide a caçamba com outra frente?</label>
    <select name="divide_frente">
        <option value="NAO" <?= ($equipe['divide_frente']=='NAO')?'selected':'' ?>>Não</option>
        <option value="SIM" <?= ($equipe['divide_frente']=='SIM')?'selected':'' ?>>Sim</option>
    </select>
</div>

<div class="form-group">
    <label>Se sim, qual equipe?</label>
    <input type="text" name="frente_nome" value="<?= htmlspecialchars($equipe['frente_nome'] ?? '') ?>">
</div>

<h3>Retroescavadeira</h3>
<div class="form-group">
    <label>Modelo / Placa</label>
    <input type="text" name="retro_modelo" value="<?= htmlspecialchars($veiculos['retro_modelo_placa'] ?? '') ?>">
</div>
<div class="form-group">
    <label>Operador</label>
    <input type="text" name="retro_operador" value="<?= htmlspecialchars($veiculos['retro_operador'] ?? '') ?>">
</div>

<h3>Caçamba</h3>
<div class="form-group">
    <label>Modelo / Placa</label>
    <input type="text" name="cacamba_modelo" value="<?= htmlspecialchars($veiculos['cacamba_modelo_placa'] ?? '') ?>">
</div>
<div class="form-group">
    <label>Motorista</label>
    <input type="text" name="cacamba_motorista" value="<?= htmlspecialchars($veiculos['cacamba_motorista'] ?? '') ?>">
</div>

<h3>Equipamentos Leves</h3>
<div class="form-group">
    <label>Compactador</label>
    <input type="number" name="equipamentos[compactador]" value="<?= $equip['compactador'] ?? 0 ?>">
</div>
<div class="form-group">
    <label>Placa vibratória</label>
    <input type="number" name="equipamentos[placa]" value="<?= $equip['placa'] ?? 0 ?>">
</div>
<div class="form-group">
    <label>Motobomba</label>
    <input type="number" name="equipamentos[motobomba]" value="<?= $equip['motobomba'] ?? 0 ?>">
</div>
<div class="form-group">
    <label>Cortadora de asfalto</label>
    <input type="number" name="equipamentos[cortadora]" value="<?= $equip['cortadora'] ?? 0 ?>">
</div>

<h3>Funcionários</h3>
<?php for ($i=1; $i<=7; $i++):
    $f = $funcs[$i-1] ?? [];
?>
<div class="func-row">
    <input type="number" name="funcionarios[<?= $i ?>][numero]" value="<?= $f['numero'] ?? $i ?>" style="width:60px">
    <input type="text" name="funcionarios[<?= $i ?>][nome]" value="<?= $f['nome'] ?? '' ?>">
    <input type="text" name="funcionarios[<?= $i ?>][funcao]" value="<?= $f['funcao'] ?? '' ?>">
    <select name="funcionarios[<?= $i ?>][sertras]">
        <option value="APTO" <?= ($f['sertras'] ?? '')=='APTO'?'selected':'' ?>>APTO</option>
        <option value="INAPTO" <?= ($f['sertras'] ?? '')=='INAPTO'?'selected':'' ?>>INAPTO</option>
    </select>
</div>
<?php endfor; ?>

<div class="form-actions" style="margin-top:30px;">
    <button class="btn-primary">Salvar Alterações</button>
    <a href="/visionhub/planejador.php" class="btn-secondary">Cancelar</a>
</div>

</form>
</div>
<?php endif; ?>

</main>
</div>
</body>
</html>
