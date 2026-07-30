<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/tipos_pavimento.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

auth_required([3]); // Planejador

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        if (empty($_SESSION['usuario_id'])) {
            throw new Exception('Usuário não autenticado');
        }

        /* =============================
           IMPORTAÇÃO POR EXCEL
        ============================== */
        if (isset($_POST['importar_excel'])) {

            if (empty($_FILES['excel']['tmp_name'])) {
                throw new Exception('Arquivo Excel não enviado');
            }

            $spreadsheet = IOFactory::load($_FILES['excel']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();

            $nomePlanejamento = trim($sheet->getTitle());

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO planejamentos (usuario_id) VALUES (?)");
            $stmt->execute([$_SESSION['usuario_id']]);
            $planejamentoId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("
                UPDATE planejamentos
                SET nome = ?
                WHERE id = ? AND usuario_id = ?
            ");
            $stmt->execute([
                $nomePlanejamento,
                $planejamentoId,
                $_SESSION['usuario_id']
            ]);

            $rows = $sheet->toArray(null, true, true, true);

            $linhaCabecalho = null;
            foreach ($rows as $i => $linha) {
                foreach ($linha as $valor) {
                    if (is_string($valor) && strtolower(trim($valor)) === 'medicao') {
                        $linhaCabecalho = $i;
                        break 2;
                    }
                }
            }

            if ($linhaCabecalho === null) {
                throw new Exception('Cabeçalho "medicao" não encontrado');
            }

            $header = [];
            foreach ($rows[$linhaCabecalho] as $col => $name) {
                if (is_string($name)) {
                    $header[$col] = strtolower(trim($name));
                }
            }

            for ($i = $linhaCabecalho + 1; $i <= count($rows); $i++) {
                if (empty($rows[$i])) continue;

                $linha = [];
                foreach ($header as $col => $campo) {
                    $linha[$campo] = trim((string)($rows[$i][$col] ?? ''));
                }

                if ($linha['medicao'] === '') continue;

                $stmt = $pdo->prepare("
                    INSERT INTO trechos (
                        planejamento_id, medicao, cidade, contrato, bacia,
                        pv_montante, pv_jusante, tipo_pi_montante,
                        quantidade_pvs, tipo_pavimento, area_total
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
                ");

                $stmt->execute([
                    $planejamentoId,
                    $linha['medicao'],
                    $linha['cidade'],
                    $linha['contrato'],
                    $linha['bacia'],
                    $linha['pv_montante'],
                    $linha['pv_jusante'],
                    $linha['tipo_pi_montante'],
                    (int)$linha['quantidade_pvs'],
                    $linha['tipo_pavimento']
                ]);
            }

            $pdo->commit();
            header('Location: planejamento.php?excel=ok');
            exit;
        }

        /* =============================
           FLUXO MANUAL
        ============================== */
        if (isset($_POST['salvar_manual'])) {

            if (empty($_POST['trechos'])) {
                throw new Exception('Nenhum trecho manual informado');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO planejamentos (nome, usuario_id)
                VALUES ('Planejamento Manual', ?)
            ");
            $stmt->execute([$_SESSION['usuario_id']]);
            $planejamentoId = $pdo->lastInsertId();

            foreach ($_POST['trechos'] as $t) {
                $stmt = $pdo->prepare("
                    INSERT INTO trechos (
                        planejamento_id, medicao, cidade, contrato, bacia,
                        pv_montante, pv_jusante, tipo_pi_montante,
                        quantidade_pvs, tipo_pavimento, area_total
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
                ");

                $stmt->execute([
                    $planejamentoId,
                    $t['medicao'],
                    $t['cidade'],
                    $t['contrato'],
                    $t['bacia'],
                    $t['pv_montante'],
                    $t['pv_jusante'],
                    $t['tipo_pi_montante'],
                    $t['quantidade_pvs'],
                    $t['tipo_pavimento']
                ]);
            }

            $pdo->commit();
            header('Location: planejamento.php?manual=ok');
            exit;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die('Erro ao salvar planejamento: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Novo Planejamento | VisionHub Locar</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/marco_urbano2/assets/css/planejador.css?v=1">
</head>
<body>

<div class="app">

<aside class="sidebar">
    <div class="logo">
        <img src="/marco_urbano2/assets/img/logo-marco-urbano.svg" alt="Marco Urbano">
        <span>MARCO URBANO</span>
    </div>
    <nav>
        <a href="/marco_urbano2/planejador/menu.php">📊 Dashboard</a>
        <a href="/marco_urbano2/planejador/planejamento.php" class="active">🗂 Novo Planejamento</a>
        <a href="/marco_urbano2/planejador/medicao.php">📐 Incluir Medições</a>
        <a href="/marco_urbano2/planejador/relatorio.php">📄 Relatório</a>
        <a href="/marco_urbano2/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content">

<h1>Novo Planejamento</h1>

<!-- ============ EXCEL ============ -->
<form method="post" enctype="multipart/form-data">
<div class="form-card">
    <h3>Importar por Excel</h3>
    <div class="form-group">
        <label>Arquivo Excel</label>
        <input type="file" name="excel" accept=".xlsx,.xls" required>
    </div>
    <div class="form-actions">
        <button class="btn-primary" name="importar_excel">📥 Enviar Excel</button>
    </div>
</div>
</form>

<!-- ============ MANUAL ============ -->
<form method="post">

<div id="trechos-container"></div>

<div class="form-actions" style="justify-content:flex-start;">
    <button type="button" class="btn-secondary" onclick="adicionarTrecho()">➕ Incluir Trecho</button>
</div>

<div class="form-actions">
    <button class="btn-primary" name="salvar_manual">💾 Salvar Planejamento Manual</button>
</div>

</form>

</main>
</div>

<script>
let idx = 0;

function adicionarTrecho() {
    const i = idx++;
    const container = document.getElementById('trechos-container');

    const div = document.createElement('div');
    div.className = 'form-card';

    div.innerHTML = `
        <h3>Trecho ${i + 1}</h3>

        <div class="form-group">
            <label>Incluir Medição</label>
            <input 
                name="trechos[${i}][medicao]"
                placeholder="Ex: Medição 01 / M-2024 / Etapa A"
                required
            >
        </div>

        <div class="form-group"><label>Cidade</label><input name="trechos[${i}][cidade]" required></div>
        <div class="form-group"><label>Contrato</label><input name="trechos[${i}][contrato]" required></div>
        <div class="form-group"><label>Bacia</label><input name="trechos[${i}][bacia]" required></div>
        <div class="form-group"><label>PV Montante</label><input name="trechos[${i}][pv_montante]" required></div>
        <div class="form-group"><label>PV Jusante</label><input name="trechos[${i}][pv_jusante]" required></div>

        <div class="form-group">
            <label>Tipo PI Montante</label>
            <select name="trechos[${i}][tipo_pi_montante]" required>
                <option value="">Selecione</option>
                <option>PV</option>
                <option>PI</option>
                <option>CA</option>
                <option>Outro</option>
            </select>
        </div>

        <div class="form-group"><label>Qtd PVs</label><input type="number" name="trechos[${i}][quantidade_pvs]" required></div>

        <div class="form-group">
            <label>Tipo Pavimento</label>
            <select name="trechos[${i}][tipo_pavimento]" required>
                <option value="">Selecione</option>
                <?php foreach (TIPOS_PAVIMENTO as $valor => $label): ?>
                <option value="<?= $valor ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    `;

    container.appendChild(div);
}

document.addEventListener('DOMContentLoaded', adicionarTrecho);
</script>

</body>
</html>
