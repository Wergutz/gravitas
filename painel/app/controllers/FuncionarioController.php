<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';

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
        csrf_verify();
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

        $_SESSION['flash_ok'] = 'Funcionário cadastrado com sucesso.';
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

        // C3: carregar datas de validade dos documentos
        $docs_map = [];
        try {
            $stmt = $pdo->prepare("SELECT tipo, data_validade FROM funcionario_documentos WHERE funcionario_id = ?");
            $stmt->execute([$id]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $docs_map[$row['tipo']] = $row;
            }
        } catch (\PDOException $e) { /* tabela ainda não existe */ }

        require __DIR__ . '/../views/funcionarios/editar.php';
    }

    /* =====================================================
       ATUALIZAR
       ===================================================== */
    public function update()
    {
        auth_required([4]);
        csrf_verify();
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

        // C3: salvar datas de validade em funcionario_documentos
        $tipos_doc = ['aso','nr06','nr10','nr11','nr12','nr18','nr20','nr23','nr33','nr35','integracao_corsan','sertras'];
        try {
            $stmtDoc = $pdo->prepare("
                INSERT INTO funcionario_documentos (funcionario_id, tipo, status, data_validade)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status), data_validade = VALUES(data_validade),
                                        atualizado_em = CURRENT_TIMESTAMP
            ");
            foreach ($tipos_doc as $tipo) {
                $status   = (int)($_POST[$tipo] ?? 1);
                $validade = trim($_POST['validade_' . $tipo] ?? '');
                $stmtDoc->execute([
                    (int)$_POST['id'],
                    $tipo,
                    $status,
                    ($validade !== '' ? $validade : null),
                ]);
            }
        } catch (\PDOException $e) { /* tabela ainda não existe: ignora */ }

        $_SESSION['flash_ok'] = 'Funcionário atualizado com sucesso.';
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

        $_SESSION['flash_ok'] = 'Status do funcionário atualizado.';
        header('Location: ' . APP_BASE . '/funcionarios');
        exit;
    }

    /* =====================================================
       IMPORTAÇÃO EXCEL 2-FASES
    ===================================================== */
    public function importar()
    {
        auth_required([4]);
        global $pdo;

        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        require_once dirname(__DIR__) . '/helpers/import_excel.php';

        // ── Fase: cancelar ───────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'cancelar') {
            unset($_SESSION['import_prev_funcionarios']);
            header('Location: ' . APP_BASE . '/funcionarios/importar');
            exit;
        }

        // ── Fase: confirmar ──────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'confirmar') {
            csrf_verify();
            $rows = $_SESSION['import_prev_funcionarios']['rows'] ?? [];
            unset($_SESSION['import_prev_funcionarios']);

            $stmtIns = $pdo->prepare("
                INSERT INTO funcionarios (nome, cpf, empresa, funcao, salario,
                    aso, nr06, nr10, nr11, nr12, nr18, nr20, nr23, nr33, nr35,
                    integracao_corsan, sertras, ativo)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,0,1)
            ");
            $stmtUpd = $pdo->prepare("
                UPDATE funcionarios SET
                    nome=?, empresa=?, funcao=?, salario=?,
                    aso=?, nr06=?, nr10=?, nr11=?, nr12=?,
                    nr18=?, nr20=?, nr23=?, nr33=?, nr35=?
                WHERE cpf=?
            ");
            $stmtDoc = $pdo->prepare("
                INSERT INTO funcionario_documentos (funcionario_id, tipo, status, data_validade)
                VALUES (?,?,?,?)
                ON DUPLICATE KEY UPDATE status=VALUES(status), data_validade=VALUES(data_validade),
                                        atualizado_em=CURRENT_TIMESTAMP
            ");
            $stmtId = $pdo->prepare("SELECT id FROM funcionarios WHERE cpf = ?");

            $ok = 0;
            foreach ($rows as $r) {
                if (!in_array($r['_status'], ['novo', 'atualizar'])) continue;
                try {
                    if ($r['_status'] === 'novo') {
                        $stmtIns->execute([
                            $r['nome'], $r['cpf'], $r['empresa'], $r['funcao'], $r['salario'],
                            $r['aso'], $r['nr06'], $r['nr10'], $r['nr11'], $r['nr12'],
                            $r['nr18'], $r['nr20'], $r['nr23'], $r['nr33'], $r['nr35'],
                        ]);
                    } else {
                        $stmtUpd->execute([
                            $r['nome'], $r['empresa'], $r['funcao'], $r['salario'],
                            $r['aso'], $r['nr06'], $r['nr10'], $r['nr11'], $r['nr12'],
                            $r['nr18'], $r['nr20'], $r['nr23'], $r['nr33'], $r['nr35'],
                            $r['cpf'],
                        ]);
                    }
                    $stmtId->execute([$r['cpf']]);
                    $func_id = (int)$stmtId->fetchColumn();
                    if ($func_id > 0) {
                        $docMap = [
                            'aso'  => [$r['aso'],   $r['val_aso']],
                            'nr06' => [$r['nr06'],  $r['val_nr06']],
                            'nr10' => [$r['nr10'],  $r['val_nr10']],
                            'nr11' => [$r['nr11'],  $r['val_nr11']],
                            'nr12' => [$r['nr12'],  $r['val_nr12']],
                            'nr18' => [$r['nr18'],  $r['val_nr18']],
                            'nr20' => [$r['nr20'],  $r['val_nr20']],
                            'nr23' => [$r['nr23'],  $r['val_nr23']],
                            'nr33' => [$r['nr33'],  $r['val_nr33']],
                            'nr35' => [$r['nr35'],  $r['val_nr35']],
                        ];
                        foreach ($docMap as $tipo => [$status, $validade]) {
                            $stmtDoc->execute([$func_id, $tipo, $status, $validade]);
                        }
                    }
                    $ok++;
                } catch (\Exception $e) {}
            }

            $_SESSION['flash_ok'] = "$ok funcionário(s) importado(s) com sucesso.";
            header('Location: ' . APP_BASE . '/funcionarios');
            exit;
        }

        // ── Fase: upload (POST com arquivo) ─────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'upload') {
            csrf_verify();
            if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['flash_erro'] = 'Arquivo inválido ou não enviado.';
                header('Location: ' . APP_BASE . '/funcionarios/importar');
                exit;
            }

            $spreadsheet = IOFactory::load($_FILES['arquivo']['tmp_name']);
            $allRows = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);

            $preview_rows = [];
            foreach ($allRows as $i => $linha) {
                $lineNum = $i + 1;
                if ($i < 6) continue; // skip banner(0-2), instruções(3), headers(4), exemplo(5)
                $nome = trim((string)($linha[0] ?? ''));
                $cpf  = preg_replace('/\D/', '', (string)($linha[1] ?? ''));
                if ($nome === '' || $cpf === '') continue;

                $status = 'novo';
                $chk = $pdo->prepare("SELECT COUNT(*) FROM funcionarios WHERE cpf = ?");
                $chk->execute([$cpf]);
                if ((int)$chk->fetchColumn() > 0) $status = 'atualizar';

                $preview_rows[] = [
                    '_linha'   => $lineNum,
                    '_status'  => $status,
                    '_msg'     => '',
                    'nome'     => $nome,
                    'cpf'      => $cpf,
                    'empresa'  => trim((string)($linha[2] ?? '')),
                    'funcao'   => trim((string)($linha[3] ?? '')),
                    'salario'  => (float)str_replace(',', '.', (string)($linha[4] ?? 0)),
                    'aso'      => import_doc_status((string)($linha[5]  ?? '')),
                    'val_aso'  => import_parse_date($linha[6]  ?? null),
                    'nr06'     => import_doc_status((string)($linha[7]  ?? '')),
                    'val_nr06' => import_parse_date($linha[8]  ?? null),
                    'nr10'     => import_doc_status((string)($linha[9]  ?? '')),
                    'val_nr10' => import_parse_date($linha[10] ?? null),
                    'nr11'     => import_doc_status((string)($linha[11] ?? '')),
                    'val_nr11' => import_parse_date($linha[12] ?? null),
                    'nr12'     => import_doc_status((string)($linha[13] ?? '')),
                    'val_nr12' => import_parse_date($linha[14] ?? null),
                    'nr18'     => import_doc_status((string)($linha[15] ?? '')),
                    'val_nr18' => import_parse_date($linha[16] ?? null),
                    'nr20'     => import_doc_status((string)($linha[17] ?? '')),
                    'val_nr20' => import_parse_date($linha[18] ?? null),
                    'nr23'     => import_doc_status((string)($linha[19] ?? '')),
                    'val_nr23' => import_parse_date($linha[20] ?? null),
                    'nr33'     => import_doc_status((string)($linha[21] ?? '')),
                    'val_nr33' => import_parse_date($linha[22] ?? null),
                    'nr35'     => import_doc_status((string)($linha[23] ?? '')),
                    'val_nr35' => import_parse_date($linha[24] ?? null),
                ];
            }

            $_SESSION['import_prev_funcionarios'] = [
                'rows'   => $preview_rows,
                'totals' => import_preview_totals($preview_rows),
            ];
            header('Location: ' . APP_BASE . '/funcionarios/importar');
            exit;
        }

        // ── GET: show form or preview ────────────────────
        $preview = $_SESSION['import_prev_funcionarios'] ?? null;
        require __DIR__ . '/../views/funcionarios/importar.php';
    }
}
