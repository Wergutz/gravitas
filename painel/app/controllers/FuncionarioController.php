<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class FuncionarioController
{
    /* =====================================================
       LISTAGEM
       ===================================================== */
    public function index()
    {
        auth_required([4]); // Planejador
        global $pdo;

        $stmt = $pdo->query("
            SELECT *
            FROM funcionarios
            ORDER BY nome
        ");
        $funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/funcionarios/listar.php';
    }

    /* =====================================================
       FORMULÁRIO DE CADASTRO
       ===================================================== */
    public function create()
    {
        auth_required([4]);
        require __DIR__ . '/../views/funcionarios/cadastrar.php';
    }

    /* =====================================================
       SALVAR NOVO FUNCIONÁRIO
       ===================================================== */
    public function store()
    {
        auth_required([4]);
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_BASE . '/funcionarios');
            exit;
        }

        $cpf = preg_replace('/\D/', '', $_POST['cpf']);

        // CPF duplicado
        $check = $pdo->prepare("SELECT COUNT(*) FROM funcionarios WHERE cpf = ?");
        $check->execute([$cpf]);
        if ($check->fetchColumn() > 0) {
            header('Location: ' . APP_BASE . '/funcionarios/cadastrar?erro=cpf');
            exit;
        }

        $sql = "
            INSERT INTO funcionarios
            (nome, cpf, empresa, funcao, salario,
             aso, nr06, nr10, nr11, nr12, nr18, nr20, nr23, nr33, nr35,
             integracao_corsan, sertras, ativo)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['nome'],
            $cpf,
            $_POST['empresa'],
            $_POST['funcao'],
            $_POST['salario'],
            $_POST['aso'],
            $_POST['nr06'],
            $_POST['nr10'],
            $_POST['nr11'],
            $_POST['nr12'],
            $_POST['nr18'],
            $_POST['nr20'],
            $_POST['nr23'],
            $_POST['nr33'],
            $_POST['nr35'],
            $_POST['integracao_corsan'],
            $_POST['sertras']
        ]);

        header('Location: ' . APP_BASE . '/funcionarios');
        exit;
    }

    /* =====================================================
       EDITAR
       ===================================================== */
    public function edit()
    {
        auth_required([4]);
        global $pdo;

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: ' . APP_BASE . '/funcionarios');
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE id = ?");
        $stmt->execute([$id]);
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$funcionario) {
            header('Location: ' . APP_BASE . '/funcionarios');
            exit;
        }

        require __DIR__ . '/../views/funcionarios/editar.php';
    }

    /* =====================================================
       ATUALIZAR
       ===================================================== */
    public function update()
    {
        auth_required([4]);
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_BASE . '/funcionarios');
            exit;
        }

        $sql = "
            UPDATE funcionarios SET
                nome = ?, empresa = ?, funcao = ?, salario = ?,
                aso = ?, nr06 = ?, nr10 = ?, nr11 = ?, nr12 = ?, nr18 = ?,
                nr20 = ?, nr23 = ?, nr33 = ?, nr35 = ?,
                integracao_corsan = ?, sertras = ?
            WHERE id = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['nome'],
            $_POST['empresa'],
            $_POST['funcao'],
            $_POST['salario'],
            $_POST['aso'],
            $_POST['nr06'],
            $_POST['nr10'],
            $_POST['nr11'],
            $_POST['nr12'],
            $_POST['nr18'],
            $_POST['nr20'],
            $_POST['nr23'],
            $_POST['nr33'],
            $_POST['nr35'],
            $_POST['integracao_corsan'],
            $_POST['sertras'],
            $_POST['id']
        ]);

        header('Location: ' . APP_BASE . '/funcionarios');
        exit;
    }

    /* =====================================================
       ATIVAR / INATIVAR
       ===================================================== */
    public function toggle()
    {
        auth_required([4]);
        global $pdo;

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: ' . APP_BASE . '/funcionarios');
            exit;
        }

        $pdo->prepare("
            UPDATE funcionarios
            SET ativo = IF(ativo = 1, 0, 1)
            WHERE id = ?
        ")->execute([$id]);

        header('Location: ' . APP_BASE . '/funcionarios');
        exit;
    }

    /* =====================================================
       IMPORTAÇÃO EXCEL (.XLSX)
       ===================================================== */
    public function importar()
    {
        auth_required([4]);
        global $pdo;

        // Formulário
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require __DIR__ . '/../views/funcionarios/importar.php';
            exit;
        }

        if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            header('Location: ' . APP_BASE . '/funcionarios?erro=arquivo');
            exit;
        }

        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

        $spreadsheet = IOFactory::load($_FILES['arquivo']['tmp_name']);
        $rows = $spreadsheet->getActiveSheet()->toArray();

        // remove cabeçalho
        array_shift($rows);

        $stmt = $pdo->prepare("
            INSERT INTO funcionarios
            (nome, cpf, empresa, funcao, salario,
             aso, nr06, nr10, nr11, nr12, nr18, nr20, nr23, nr33, nr35,
             integracao_corsan, sertras, ativo)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");

        $importados = 0;
        $erros = [];

        foreach ($rows as $i => $linha) {
            $linhaNum = $i + 2;

            if (count($linha) < 17 || empty($linha[0]) || empty($linha[1])) {
                $erros[] = "Linha {$linhaNum}: dados insuficientes";
                continue;
            }

            $cpf = preg_replace('/\D/', '', $linha[1]);

            $check = $pdo->prepare("SELECT COUNT(*) FROM funcionarios WHERE cpf = ?");
            $check->execute([$cpf]);
            if ($check->fetchColumn() > 0) {
                $erros[] = "Linha {$linhaNum}: CPF duplicado";
                continue;
            }

            try {
                $stmt->execute([
                    trim($linha[0]),
                    $cpf,
                    trim($linha[2]),
                    trim($linha[3]),
                    (float)$linha[4],
                    (int)$linha[5],
                    (int)$linha[6],
                    (int)$linha[7],
                    (int)$linha[8],
                    (int)$linha[9],
                    (int)$linha[10],
                    (int)$linha[11],
                    (int)$linha[12],
                    (int)$linha[13],
                    (int)$linha[14],
                    (int)$linha[15],
                    (int)$linha[16]
                ]);
                $importados++;
            } catch (Exception $e) {
                $erros[] = "Linha {$linhaNum}: erro ao salvar";
            }
        }

        $_SESSION['import_result'] = [
            'importados' => $importados,
            'erros' => $erros
        ];

        header('Location: ' . APP_BASE . '/funcionarios');
        exit;
    }
}
