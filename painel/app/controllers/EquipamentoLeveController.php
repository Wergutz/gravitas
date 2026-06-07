<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';

class EquipamentoLeveController {

    public function index() {
        auth_required([4]);
        global $pdo;

        $stmt = $pdo->query("
            SELECT id, referencia, fabricante, modelo, ano, proprietario, combustivel, ativo
            FROM equipamentos_leves
            ORDER BY fabricante, modelo
        ");
        $equipamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/equipamentos_leves/listar.php';
    }

    public function create() {
        auth_required([4]);
        require __DIR__ . '/../views/equipamentos_leves/cadastrar.php';
    }

    public function edit()
    {
        auth_required([4]);
        global $pdo;

        if (!isset($_GET['id'])) {
            header('Location: ' . APP_BASE . '/equipamentos-leves');
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT *
            FROM equipamentos_leves
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$_GET['id']]);
        $equipamento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$equipamento) {
            header('Location: ' . APP_BASE . '/equipamentos-leves');
            exit;
        }

        require __DIR__ . '/../views/equipamentos_leves/editar.php';
    }

    public function update()
    {
        auth_required([4]);
        csrf_verify();
        global $pdo;

        $stmt = $pdo->prepare("
            UPDATE equipamentos_leves
            SET referencia = ?, fabricante = ?, modelo = ?, ano = ?, proprietario = ?, combustivel = ?
            WHERE id = ?
        ");

        $stmt->execute([
            strtoupper(trim($_POST['referencia'])),
            trim($_POST['fabricante']),
            trim($_POST['modelo']),
            (int) $_POST['ano'],
            trim($_POST['proprietario']),
            $_POST['combustivel'],
            $_POST['id']
        ]);

        header('Location: ' . APP_BASE . '/equipamentos-leves');
        exit;
    }

    public function toggle()
    {
        auth_required([4]);
        global $pdo;

        $stmt = $pdo->prepare("
            UPDATE equipamentos_leves
            SET ativo = IF(ativo = 1, 0, 1)
            WHERE id = ?
        ");
        $stmt->execute([(int)($_GET['id'] ?? 0)]);

        header('Location: ' . APP_BASE . '/equipamentos-leves');
        exit;
    }

    public function store()
    {
        auth_required([4]);
        csrf_verify();
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_BASE . '/equipamentos-leves');
            exit;
        }

        $tipo = trim($_POST['tipo']);
    
        if ($tipo === '') {
            header('Location: ' . APP_BASE . '/equipamentos-leves/cadastrar?erro=tipo');
            exit;
        }
    
        $sql = "
            INSERT INTO equipamentos_leves
            (referencia, nome, tipo, fabricante, modelo, ano, proprietario, combustivel, numero_serie, ativo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ";
    
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            trim($_POST['referencia']),
            trim($_POST['nome']),
            $tipo,
            trim($_POST['fabricante']),
            trim($_POST['modelo']),
            $_POST['ano'] ?: null,
            trim($_POST['proprietario']),
            $_POST['combustivel'],
            trim($_POST['numero_serie'])
        ]);
    
        $_SESSION['flash_ok'] = 'Equipamento salvo com sucesso.';
        header('Location: ' . APP_BASE . '/equipamentos-leves');
        exit;
    }
    /* ========= IMPORTAÇÃO ========= */
    public function importar()
    {
        require __DIR__ . '/../views/equipamentos_leves/importar.php';
    }
    
    public function importExcel()
{
    auth_required([4]);
    global $pdo;

    require_once dirname(__DIR__) . '/../vendor/autoload.php';

    if (!isset($_FILES['excel']) || $_FILES['excel']['error'] !== UPLOAD_ERR_OK) {
        die('Arquivo não recebido');
    }

    // força PDO a mostrar erro (temporário, ajuda muito)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(
        $_FILES['excel']['tmp_name']
    )->getActiveSheet();

    $linhas = $sheet->toArray(null, true, true, true);
    $importados = 0;

    foreach ($linhas as $i => $linha) {

        // pula cabeçalho
        if ($i === 1) continue;

        $referencia   = trim($linha['A'] ?? '');
        $fabricante   = trim($linha['B'] ?? '');
        $modelo       = trim($linha['C'] ?? '');
        $ano          = (int)($linha['D'] ?? 0);
        $proprietario = trim($linha['E'] ?? '');
        $combustivel  = trim($linha['F'] ?? '');
        $tipo         = trim($linha['G'] ?? '');

        // validações mínimas
        if ($referencia === '' || $tipo === '') {
            continue;
        }

        // evita duplicidade
        $check = $pdo->prepare(
            "SELECT id FROM equipamentos_leves WHERE referencia = ?"
        );
        $check->execute([$referencia]);
        if ($check->fetch()) {
            continue;
        }

        $stmt = $pdo->prepare("
            INSERT INTO equipamentos_leves
            (referencia, tipo, fabricante, modelo, ano, proprietario, combustivel, ativo)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");

        $stmt->execute([
            strtoupper($referencia),
            $tipo,
            $fabricante,
            $modelo,
            $ano,
            $proprietario,
            $combustivel
        ]);

        $importados++;
    }

    header('Location: ' . APP_BASE . '/equipamentos-leves?importado=' . $importados);
    exit;
}


}
