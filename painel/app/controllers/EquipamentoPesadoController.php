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
        
            $_SESSION['flash_ok'] = 'Equipamento salvo com sucesso.';
            header('Location: ' . APP_BASE . '/equipamentos-pesados');
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
            unset($_SESSION['import_prev_eq_pesados']);
            header('Location: ' . APP_BASE . '/equipamentos-pesados/importar');
            exit;
        }

        // ── Fase: confirmar ──────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'confirmar') {
            csrf_verify();
            $rows = $_SESSION['import_prev_eq_pesados']['rows'] ?? [];
            unset($_SESSION['import_prev_eq_pesados']);

            $stmtIns    = $pdo->prepare("INSERT INTO equipamentos_pesados (tipo, placa, modelo, fabricante, ano, proprietario, combustivel, ativo) VALUES (?,?,?,?,?,?,?,1)");
            $stmtUpd    = $pdo->prepare("UPDATE equipamentos_pesados SET tipo=?, modelo=?, fabricante=?, ano=?, proprietario=?, combustivel=? WHERE placa=?");
            $stmtUpdFb  = $pdo->prepare("UPDATE equipamentos_pesados SET tipo=?, proprietario=?, combustivel=? WHERE (placa IS NULL OR placa='') AND modelo=? AND fabricante=? AND ano=?");

            $ok = 0;
            foreach ($rows as $r) {
                if (!in_array($r['_status'], ['novo', 'atualizar'])) continue;
                try {
                    if ($r['_status'] === 'novo') {
                        $stmtIns->execute([$r['tipo'], $r['placa'] ?: null, $r['modelo'], $r['fabricante'], $r['ano'], $r['proprietario'], $r['combustivel']]);
                    } elseif (($r['_chave_tipo'] ?? 'placa') === 'placa') {
                        $stmtUpd->execute([$r['tipo'], $r['modelo'], $r['fabricante'], $r['ano'], $r['proprietario'], $r['combustivel'], $r['placa']]);
                    } else {
                        $stmtUpdFb->execute([$r['tipo'], $r['proprietario'], $r['combustivel'], $r['modelo'], $r['fabricante'], $r['ano']]);
                    }
                    $ok++;
                } catch (\Exception $e) {}
            }

            try {
                $pdo->prepare("INSERT INTO log_auditoria (admin_id, acao, detalhes) VALUES (?,?,?)")
                    ->execute([(int)($_SESSION['usuario_id']??0), 'importacao',
                        json_encode(['modulo'=>'equipamentos_pesados','linhas'=>count($rows),'importadas'=>$ok])]);
            } catch (\Exception $e) {}
            $_SESSION['flash_ok'] = "$ok equipamento(s) importado(s).";
            header('Location: ' . APP_BASE . '/equipamentos-pesados');
            exit;
        }

        // ── Fase: upload ─────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'upload') {
            csrf_verify();
            if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['flash_erro'] = 'Arquivo inválido.';
                header('Location: ' . APP_BASE . '/equipamentos-pesados/importar');
                exit;
            }

            $allRows = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['arquivo']['tmp_name'])
                ->getActiveSheet()->toArray(null, true, false, false);

            $preview_rows = [];
            $chkPlaca = $pdo->prepare("SELECT COUNT(*) FROM equipamentos_pesados WHERE placa = ?");
            $chkFb    = $pdo->prepare("SELECT COUNT(*) FROM equipamentos_pesados WHERE (placa IS NULL OR placa='') AND modelo=? AND fabricante=? AND ano=?");
            foreach ($allRows as $i => $linha) {
                if ($i < 6) continue;
                $tipo       = trim((string)($linha[0] ?? ''));
                $placa      = strtoupper(trim((string)($linha[1] ?? '')));
                $modelo     = trim((string)($linha[2] ?? ''));
                $fabricante = trim((string)($linha[3] ?? ''));
                $ano        = (int)($linha[4] ?? 0);

                // Ignorar linha totalmente vazia (células formatadas sem valor)
                if (!array_filter($linha, fn($c) => $c !== null && trim((string)$c) !== '')) continue;

                if ($tipo === '') {
                    $preview_rows[] = ['_linha'=>$i+1,'_status'=>'erro','_msg'=>"'Tipo' obrigatório",
                        '_chave_tipo'=>'placa','tipo'=>'','placa'=>$placa,'modelo'=>$modelo,
                        'fabricante'=>$fabricante,'ano'=>$ano,'proprietario'=>trim((string)($linha[5]??'')),'combustivel'=>trim((string)($linha[6]??''))];
                    continue;
                }

                if ($placa !== '') {
                    $chkPlaca->execute([$placa]);
                    $status = (int)$chkPlaca->fetchColumn() > 0 ? 'atualizar' : 'novo';
                    $chave_tipo = 'placa';
                } else {
                    if ($modelo === '' && $fabricante === '') {
                        $preview_rows[] = ['_linha'=>$i+1,'_status'=>'erro',
                            '_msg'=>"'Placa' ausente; informe Modelo e Fabricante como alternativa",
                            '_chave_tipo'=>'fallback','tipo'=>$tipo,'placa'=>'','modelo'=>$modelo,
                            'fabricante'=>$fabricante,'ano'=>$ano,'proprietario'=>trim((string)($linha[5]??'')),'combustivel'=>trim((string)($linha[6]??''))];
                        continue;
                    }
                    $chkFb->execute([$modelo, $fabricante, $ano]);
                    $status = (int)$chkFb->fetchColumn() > 0 ? 'atualizar' : 'novo';
                    $chave_tipo = 'fallback';
                }

                $preview_rows[] = [
                    '_linha'      => $i + 1,
                    '_status'     => $status,
                    '_msg'        => '',
                    '_chave_tipo' => $chave_tipo,
                    'tipo'        => $tipo,
                    'placa'       => $placa,
                    'modelo'      => $modelo,
                    'fabricante'  => $fabricante,
                    'ano'         => $ano,
                    'proprietario'=> trim((string)($linha[5] ?? '')),
                    'combustivel' => trim((string)($linha[6] ?? '')),
                ];
            }

            $_SESSION['import_prev_eq_pesados'] = [
                'rows'   => $preview_rows,
                'totals' => import_preview_totals($preview_rows),
            ];
            header('Location: ' . APP_BASE . '/equipamentos-pesados/importar');
            exit;
        }

        // ── GET ──────────────────────────────────────────
        $preview = $_SESSION['import_prev_eq_pesados'] ?? null;
        require __DIR__ . '/../views/equipamentos_pesados/importar.php';
    }

}
