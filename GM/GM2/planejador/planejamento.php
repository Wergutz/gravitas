<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/asset.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/tipos_pavimento.php';
require_once __DIR__ . '/../app/helpers/texto.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

auth_required([3]); // Planejador

$erro = null;

/**
 * Procura um planejamento do usuário com o mesmo nome, ignorando caixa,
 * acento e espaço repetido.
 *
 * A comparação roda em PHP de propósito: o LOWER() do SQL não rebaixa
 * acento da mesma forma em todo banco, e "MEDIÇÃO 27" passaria como nome
 * diferente de "Medição 27".
 *
 * @return array{id:int, nome:string}|null
 */
function mu_planejamento_com_nome(PDO $pdo, int $usuarioId, string $nome): ?array
{
    $alvo = mu_normalizar_texto($nome);
    if ($alvo === '') {
        return null;
    }

    $st = $pdo->prepare('SELECT id, nome FROM planejamentos WHERE usuario_id = ?');
    $st->execute([$usuarioId]);

    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $p) {
        if (mu_normalizar_texto($p['nome']) === $alvo) {
            return ['id' => (int) $p['id'], 'nome' => (string) $p['nome']];
        }
    }
    return null;
}

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

            /* O nome do planejamento é o nome da ABA da planilha. */
            $nomePlanejamento = trim($sheet->getTitle());

            if ($nomePlanejamento === '') {
                throw new Exception('A aba da planilha está sem nome — é dela que sai o nome do planejamento.');
            }

            /* Reimportar a mesma planilha criaria outro planejamento com o
               mesmo nome, indistinguível do primeiro nas listas. */
            if ($jaExiste = mu_planejamento_com_nome($pdo, (int) $_SESSION['usuario_id'], $nomePlanejamento)) {
                throw new Exception(
                    'Já existe um planejamento seu chamado “' . $jaExiste['nome'] . '” '
                  . '(nº ' . $jaExiste['id'] . '). Renomeie a aba da planilha antes de importar '
                  . '— é o nome da aba que vira o nome do planejamento.'
                );
            }

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

            /* Nome do planejamento: informado por quem cadastra. Antes era
               fixo, e todo planejamento manual saía com o mesmo nome —
               impossível distinguir um do outro nas listas. */
            $nomePlanejamento = trim((string) ($_POST['nome_planejamento'] ?? ''));

            if ($nomePlanejamento === '') {
                throw new Exception('Dê um nome ao planejamento.');
            }
            if (mb_strlen($nomePlanejamento) > 150) {
                $nomePlanejamento = mb_substr($nomePlanejamento, 0, 150);
            }

            /* Nome repetido derrota o propósito de nomear: barra antes de
               gravar e diz qual já existe. */
            if ($jaExiste = mu_planejamento_com_nome($pdo, (int) $_SESSION['usuario_id'], $nomePlanejamento)) {
                throw new Exception(
                    'Já existe um planejamento seu chamado “' . $jaExiste['nome'] . '” '
                  . '(nº ' . $jaExiste['id'] . '). Escolha outro nome.'
                );
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO planejamentos (nome, usuario_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$nomePlanejamento, $_SESSION['usuario_id']]);
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

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        /* Antes era die(): a página morria e quem estava cadastrando
           perdia tudo que tinha digitado. Agora a mensagem aparece na
           própria tela, com o formulário no lugar. */
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Novo Planejamento | GM SERVIÇOS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= mu_asset('/assets/css/planejador.css') ?>">
</head>
<body>

<div class="app">

<aside class="sidebar">
    <div class="logo">
        <img src="/GM/GM2/assets/img/icon-gm.png" alt="GM SERVIÇOS">
        <span>GM SERVIÇOS</span>
    </div>
    <nav>
        <a href="/GM/GM2/planejador/menu.php">📊 Dashboard</a>
        <a href="/GM/GM2/planejador/planejamento.php" class="active">🗂 Novo Planejamento</a>
        <a href="/GM/GM2/planejador/medicao.php">📐 Incluir Medições</a>
        <a href="/GM/GM2/planejador/relatorio.php">📄 Relatório</a>
        <a href="/GM/GM2/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content">

<h1>Novo Planejamento</h1>

<?php if (!empty($_GET['excel'])): ?>
<div class="form-card" style="border:1px solid #22c55e;background:#052e16;">
    <strong style="color:#bbf7d0;font-size:16px;">✅ Planilha importada.</strong>
    <p style="color:#dcfce7;font-size:14px;margin-top:6px;">
        O planejamento já aparece em <a href="/GM/GM2/planejador/medicao.php"
        style="color:#86efac;">Incluir Medições</a>.
    </p>
</div>
<?php endif; ?>

<?php if (!empty($_GET['manual'])): ?>
<div class="form-card" style="border:1px solid #22c55e;background:#052e16;">
    <strong style="color:#bbf7d0;font-size:16px;">✅ Planejamento cadastrado.</strong>
    <p style="color:#dcfce7;font-size:14px;margin-top:6px;">
        Já aparece em <a href="/GM/GM2/planejador/medicao.php"
        style="color:#86efac;">Incluir Medições</a>.
    </p>
</div>
<?php endif; ?>

<?php if ($erro): ?>
<div class="form-card" style="border:1px solid #ef4444;background:#2a0606;">
    <strong style="color:#fecaca;">Não foi possível salvar</strong>
    <p style="color:#fee2e2;font-size:14px;margin-top:6px;line-height:1.5;">
        <?= htmlspecialchars($erro) ?>
    </p>
    <p style="color:#fca5a5;font-size:13px;margin-top:6px;">Nada foi gravado.</p>
</div>
<?php endif; ?>

<!-- ============ EXCEL ============ -->
<form method="post" enctype="multipart/form-data">
<div class="form-card">
    <h3>Importar por Excel</h3>
    <div class="form-group">
        <label>Arquivo Excel</label>
        <input type="file" name="excel" accept=".xlsx,.xls" required>
    </div>
    <small class="mu-dica">
        O nome do planejamento vem do <strong>nome da aba</strong> da planilha.
        Renomeie a aba antes de importar — dois planejamentos não podem ter o mesmo nome.
    </small>
    <div class="form-actions">
        <button class="btn-primary" name="importar_excel">📥 Enviar Excel</button>
    </div>
</div>
</form>

<!-- ============ MANUAL ============ -->
<form method="post">

<div class="form-card">
    <h3>Cadastrar manualmente</h3>
    <div class="form-group">
        <label>Nome do planejamento</label>
        <input
            type="text"
            name="nome_planejamento"
            maxlength="150"
            required
            placeholder="Ex: Contrato 4147-2024 · Medição 27"
            value="<?= htmlspecialchars($_POST['nome_planejamento'] ?? '') ?>"
        >
        <small class="mu-dica">
            É por este nome que o planejamento aparece nas telas de medição e relatório.
            Use algo que identifique o contrato ou o período — nomes repetidos não são aceitos.
        </small>
    </div>
</div>

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
