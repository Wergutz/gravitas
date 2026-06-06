<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';

class EquipamentoPesadoController
{
    public function index()
    {
        auth_required([4]);
        global $pdo;

        $stmt = $pdo->query("
            SELECT 
                id,
                tipo,
                placa,
                fabricante,
                modelo,
                ano,
                proprietario,
                combustivel,
                ativo
            FROM equipamentos_pesados
            ORDER BY tipo, modelo
        ");

        $equipamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/equipamentos_pesados/listar.php';
    }

    public function create()
    {
        auth_required([4]);
        require __DIR__ . '/../views/equipamentos_pesados/cadastrar.php';
    }
    public function edit()
    {
        auth_required([4]);
        global $pdo;
    
        if (!isset($_GET['id'])) {
            header('Location: ' . APP_BASE . '/equipamentos-pesados');
            exit;
        }
    
        $stmt = $pdo->prepare("
            SELECT *
            FROM equipamentos_pesados
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$_GET['id']]);
        $equipamento = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$equipamento) {
            header('Location: ' . APP_BASE . '/equipamentos-pesados');
            exit;
        }
    
        require __DIR__ . '/../views/equipamentos_pesados/editar.php';
    }
    public function toggle()
    {
        auth_required([4]);
        global $pdo;

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . APP_BASE . '/equipamentos-pesados');
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE equipamentos_pesados
            SET ativo = IF(ativo = 1, 0, 1)
            WHERE id = ?
        ");
        $stmt->execute([$id]);
    
        header('Location: ' . APP_BASE . '/equipamentos-pesados');
        exit;
    }

    public function update()
    {
        auth_required([4]);
        csrf_verify();
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_BASE . '/equipamentos-pesados');
            exit;
        }
    
        if (
            empty($_POST['id']) ||
            empty($_POST['tipo']) ||
            empty($_POST['placa']) ||
            empty($_POST['fabricante']) ||
            empty($_POST['modelo']) ||
            empty($_POST['ano']) ||
            empty($_POST['proprietario']) ||
            empty($_POST['combustivel'])
        ) {
            header('Location: ' . APP_BASE . '/equipamentos-pesados/editar?id=' . $_POST['id'] . '&erro=campos');
            exit;
        }
    
        $stmt = $pdo->prepare("
            UPDATE equipamentos_pesados
            SET
                tipo = ?,
                placa = ?,
                fabricante = ?,
                modelo = ?,
                ano = ?,
                proprietario = ?,
                combustivel = ?
            WHERE id = ?
        ");
    
        $stmt->execute([
            $_POST['tipo'],
            strtoupper(trim($_POST['placa'])),
            trim($_POST['fabricante']),
            trim($_POST['modelo']),
            (int) $_POST['ano'],
            trim($_POST['proprietario']),
            $_POST['combustivel'],
            $_POST['id']
        ]);
    
        header('Location: ' . APP_BASE . '/equipamentos-pesados');
        exit;
    }
        public function store()
        {
            auth_required([4]);
            csrf_verify();
            global $pdo;

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . APP_BASE . '/equipamentos-pesados');
                exit;
            }
        
            $tipo = trim($_POST['tipo']);
        
            if ($tipo === '') {
                header('Location: ' . APP_BASE . '/equipamentos-pesados/cadastrar?erro=tipo');
                exit;
            }
        
            $sql = "
                INSERT INTO equipamentos_pesados
                (tipo, fabricante, modelo, ano, proprietario, combustivel, placa, descricao, ativo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ";
        
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $tipo,
                trim($_POST['fabricante']),
                trim($_POST['modelo']),
                $_POST['ano'] ?: null,
                trim($_POST['proprietario']),
                $_POST['combustivel'],
                trim($_POST['placa']),
                trim($_POST['descricao'])
            ]);
        
            header('Location: ' . APP_BASE . '/equipamentos-pesados?salvo=1');
            exit;
        }
        public function importar()
        {
            require __DIR__ . '/../views/equipamentos_pesados/importar.php';
        }
        
        public function importExcel()
        {
            auth_required([4]);
            global $pdo;
        
            require_once dirname(__DIR__) . '/../vendor/autoload.php';
        
            if (!isset($_FILES['excel']) || $_FILES['excel']['error'] !== UPLOAD_ERR_OK) {
                header('Location: ' . APP_BASE . '/equipamentos-pesados?erro=excel');
                exit;
            }
        
            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(
                $_FILES['excel']['tmp_name']
            )->getActiveSheet();
        
            $linhas = $sheet->toArray(null, true, true, true);
        
            foreach ($linhas as $i => $linha) {
        
                if ($i === 1) continue; // cabeçalho
        
                $placa        = trim($linha['A'] ?? '');
                $fabricante   = trim($linha['B'] ?? '');
                $modelo       = trim($linha['C'] ?? '');
                $ano          = (int)($linha['D'] ?? 0);
                $proprietario = trim($linha['E'] ?? '');
                $combustivel  = trim($linha['F'] ?? '');
                $tipo         = trim($linha['G'] ?? '');
        
                if ($placa === '' || $tipo === '') continue;
        
                // evita duplicar por placa
                $check = $pdo->prepare(
                    "SELECT id FROM equipamentos_pesados WHERE placa = ?"
                );
                $check->execute([$placa]);
                if ($check->fetch()) continue;
        
                $stmt = $pdo->prepare("
                    INSERT INTO equipamentos_pesados
                    (placa, tipo, fabricante, modelo, ano, proprietario, combustivel, ativo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                ");
        
                $stmt->execute([
                    strtoupper($placa),
                    $tipo,
                    $fabricante,
                    $modelo,
                    $ano,
                    $proprietario,
                    $combustivel
                ]);
            }
        
            header('Location: ' . APP_BASE . '/equipamentos-pesados');
            exit;
        }

}
