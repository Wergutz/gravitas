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
            unset($_SESSION['import_prev_eq_leves']);
            header('Location: ' . APP_BASE . '/equipamentos-leves/importar');
            exit;
        }

        // ── Fase: confirmar ──────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'confirmar') {
            csrf_verify();
            $rows = $_SESSION['import_prev_eq_leves']['rows'] ?? [];
            unset($_SESSION['import_prev_eq_leves']);

            $stmtIns   = $pdo->prepare("INSERT INTO equipamentos_leves (tipo, referencia, modelo, ano, proprietario, combustivel, ativo) VALUES (?,?,?,?,?,?,1)");
            $stmtUpd   = $pdo->prepare("UPDATE equipamentos_leves SET tipo=?, modelo=?, ano=?, proprietario=?, combustivel=? WHERE referencia=?");
            $stmtUpdFb = $pdo->prepare("UPDATE equipamentos_leves SET proprietario=?, combustivel=? WHERE (referencia IS NULL OR referencia='') AND tipo=? AND modelo=? AND ano=?");

            $ok = 0;
            foreach ($rows as $r) {
                if (!in_array($r['_status'], ['novo', 'atualizar'])) continue;
                try {
                    if ($r['_status'] === 'novo') {
                        $stmtIns->execute([$r['tipo'], $r['referencia'] ?: null, $r['modelo'], $r['ano'], $r['proprietario'], $r['combustivel']]);
                    } elseif (($r['_chave_tipo'] ?? 'referencia') === 'referencia') {
                        $stmtUpd->execute([$r['tipo'], $r['modelo'], $r['ano'], $r['proprietario'], $r['combustivel'], $r['referencia']]);
                    } else {
                        $stmtUpdFb->execute([$r['proprietario'], $r['combustivel'], $r['tipo'], $r['modelo'], $r['ano']]);
                    }
                    $ok++;
                } catch (\Exception $e) {}
            }

            try {
                $pdo->prepare("INSERT INTO log_auditoria (admin_id, acao, detalhes) VALUES (?,?,?)")
                    ->execute([(int)($_SESSION['usuario_id']??0), 'importacao',
                        json_encode(['modulo'=>'equipamentos_leves','linhas'=>count($rows),'importadas'=>$ok])]);
            } catch (\Exception $e) {}
            $_SESSION['flash_ok'] = "$ok equipamento(s) leve(s) importado(s).";
            header('Location: ' . APP_BASE . '/equipamentos-leves');
            exit;
        }

        // ── Fase: upload ─────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'upload') {
            csrf_verify();
            if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['flash_erro'] = 'Arquivo inválido.';
                header('Location: ' . APP_BASE . '/equipamentos-leves/importar');
                exit;
            }

            $allRows = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['arquivo']['tmp_name'])
                ->getActiveSheet()->toArray(null, true, false, false);

            $preview_rows = [];
            $chkRef = $pdo->prepare("SELECT COUNT(*) FROM equipamentos_leves WHERE referencia = ?");
            $chkFbL = $pdo->prepare("SELECT COUNT(*) FROM equipamentos_leves WHERE (referencia IS NULL OR referencia='') AND tipo=? AND modelo=? AND ano=?");
            foreach ($allRows as $i => $linha) {
                if ($i < 6) continue;
                $tipo       = trim((string)($linha[0] ?? ''));
                $referencia = strtoupper(trim((string)($linha[1] ?? '')));
                $modelo     = trim((string)($linha[2] ?? ''));
                $ano        = (int)($linha[3] ?? 0);

                if ($tipo === '') {
                    $preview_rows[] = ['_linha'=>$i+1,'_status'=>'erro','_msg'=>"'Tipo' obrigatório",
                        '_chave_tipo'=>'referencia','tipo'=>'','referencia'=>$referencia,'modelo'=>$modelo,
                        'ano'=>$ano,'proprietario'=>trim((string)($linha[4]??'')),'combustivel'=>trim((string)($linha[5]??''))];
                    continue;
                }

                if ($referencia !== '') {
                    $chkRef->execute([$referencia]);
                    $status = (int)$chkRef->fetchColumn() > 0 ? 'atualizar' : 'novo';
                    $chave_tipo = 'referencia';
                } else {
                    if ($modelo === '') {
                        $preview_rows[] = ['_linha'=>$i+1,'_status'=>'erro',
                            '_msg'=>"'Referência' ausente; informe o Modelo como alternativa",
                            '_chave_tipo'=>'fallback','tipo'=>$tipo,'referencia'=>'','modelo'=>$modelo,
                            'ano'=>$ano,'proprietario'=>trim((string)($linha[4]??'')),'combustivel'=>trim((string)($linha[5]??''))];
                        continue;
                    }
                    $chkFbL->execute([$tipo, $modelo, $ano]);
                    $status = (int)$chkFbL->fetchColumn() > 0 ? 'atualizar' : 'novo';
                    $chave_tipo = 'fallback';
                }

                $preview_rows[] = [
                    '_linha'       => $i + 1,
                    '_status'      => $status,
                    '_msg'         => '',
                    '_chave_tipo'  => $chave_tipo,
                    'tipo'         => $tipo,
                    'referencia'   => $referencia,
                    'modelo'       => $modelo,
                    'ano'          => $ano,
                    'proprietario' => trim((string)($linha[4] ?? '')),
                    'combustivel'  => trim((string)($linha[5] ?? '')),
                ];
            }

            $_SESSION['import_prev_eq_leves'] = [
                'rows'   => $preview_rows,
                'totals' => import_preview_totals($preview_rows),
            ];
            header('Location: ' . APP_BASE . '/equipamentos-leves/importar');
            exit;
        }

        // ── GET ──────────────────────────────────────────
        $preview = $_SESSION['import_prev_eq_leves'] ?? null;
        require __DIR__ . '/../views/equipamentos_leves/importar.php';
    }


}
