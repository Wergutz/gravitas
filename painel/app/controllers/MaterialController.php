<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';

class MaterialController
{
    /* =====================================================
       LISTAR
    ===================================================== */
    public function index()
    {
        auth_required([4]);
        global $pdo;

        $stmt = $pdo->query("
            SELECT mc.*,
                   COALESCE(me.quantidade_fisica, 0)    AS qtd_fisica,
                   COALESCE(me.quantidade_reservada, 0) AS qtd_reservada,
                   COALESCE(me.quantidade_fisica, 0) - COALESCE(me.quantidade_reservada, 0) AS qtd_disponivel
            FROM materiais_catalogo mc
            LEFT JOIN materiais_estoque me ON me.material_id = mc.id
            WHERE mc.ativo = 1
            ORDER BY mc.nome
        ");
        $materiais = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Última contagem de estoque
        $ultima_contagem = null;
        try {
            $ultima_contagem = $pdo->query("
                SELECT data_contagem, responsavel FROM contagens_estoque
                ORDER BY criado_em DESC LIMIT 1
            ")->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {}

        require __DIR__ . '/../views/materiais/listar.php';
    }

    /* =====================================================
       CADASTRAR
    ===================================================== */
    public function create()
    {
        auth_required([4]);
        require __DIR__ . '/../views/materiais/cadastrar.php';
    }

    /* =====================================================
       SALVAR
    ===================================================== */
    public function store()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();

        $codigo          = trim($_POST['codigo'] ?? '') ?: null;
        $nome            = trim($_POST['nome'] ?? '');
        $unidade         = trim($_POST['unidade'] ?? 'un');
        $estoque_minimo  = str_replace(',', '.', trim($_POST['estoque_minimo'] ?? '0'));

        if ($nome === '') {
            $_SESSION['flash_erro'] = 'Nome do material é obrigatório.';
            header('Location: ' . APP_BASE . '/materiais/cadastrar');
            exit;
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare("
                INSERT INTO materiais_catalogo (codigo, nome, unidade, estoque_minimo)
                VALUES (?, ?, ?, ?)
            ")->execute([
                $codigo,
                $nome,
                $unidade,
                is_numeric($estoque_minimo) ? $estoque_minimo : 0,
            ]);

            $material_id = $pdo->lastInsertId();

            // Cria linha de estoque zerada
            $pdo->prepare("
                INSERT INTO materiais_estoque (material_id, quantidade_fisica, quantidade_reservada)
                VALUES (?, 0, 0)
            ")->execute([$material_id]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_erro'] = 'Erro ao cadastrar material.';
            header('Location: ' . APP_BASE . '/materiais/cadastrar');
            exit;
        }

        $_SESSION['flash_ok'] = 'Material cadastrado com sucesso.';
        header('Location: ' . APP_BASE . '/materiais');
        exit;
    }

    /* =====================================================
       MOVIMENTO (ENTRADA / AJUSTE)
    ===================================================== */
    public function movimento()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();

        $material_id  = (int)($_POST['material_id'] ?? 0);
        $tipo         = trim($_POST['tipo'] ?? '');
        $quantidade   = str_replace(',', '.', trim($_POST['quantidade'] ?? ''));
        $observacao   = trim($_POST['observacao'] ?? '') ?: null;

        if ($material_id <= 0 || !in_array($tipo, ['entrada', 'ajuste']) || !is_numeric($quantidade) || (float)$quantidade <= 0) {
            $_SESSION['flash_erro'] = 'Dados inválidos para o movimento.';
            header('Location: ' . APP_BASE . '/materiais');
            exit;
        }

        $qtd = (float)$quantidade;

        $pdo->beginTransaction();
        try {
            // Registrar movimento
            $pdo->prepare("
                INSERT INTO materiais_movimentos (material_id, tipo, quantidade, referencia_tipo, observacao, usuario_id)
                VALUES (?, ?, ?, 'ajuste_manual', ?, ?)
            ")->execute([
                $material_id,
                $tipo,
                $qtd,
                $observacao,
                $_SESSION['usuario_id'] ?? 0,
            ]);

            // Atualizar estoque
            if ($tipo === 'entrada') {
                $pdo->prepare("
                    INSERT INTO materiais_estoque (material_id, quantidade_fisica)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE quantidade_fisica = quantidade_fisica + VALUES(quantidade_fisica)
                ")->execute([$material_id, $qtd]);
            } else { // ajuste: substitui o valor
                $pdo->prepare("
                    UPDATE materiais_estoque SET quantidade_fisica = ? WHERE material_id = ?
                ")->execute([$qtd, $material_id]);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_erro'] = 'Erro ao lançar movimento.';
            header('Location: ' . APP_BASE . '/materiais');
            exit;
        }

        $_SESSION['flash_ok'] = 'Movimento lançado com sucesso.';
        header('Location: ' . APP_BASE . '/materiais');
        exit;
    }

    /* =====================================================
       IMPORTAÇÃO EXCEL CATÁLOGO — 2-FASES
    ===================================================== */
    public function importar()
    {
        auth_required([4]);
        global $pdo;

        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        require_once dirname(__DIR__) . '/helpers/import_excel.php';

        // Excel cols (0-indexed): 0=Código, 1=Nome, 2=Unidade, 3=Estoque mínimo, 4=Estoque atual

        // ── Fase: cancelar ───────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'cancelar') {
            unset($_SESSION['import_prev_materiais']);
            header('Location: ' . APP_BASE . '/materiais/importar');
            exit;
        }

        // ── Fase: confirmar ──────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'confirmar') {
            csrf_verify();
            $rows = $_SESSION['import_prev_materiais']['rows'] ?? [];
            unset($_SESSION['import_prev_materiais']);

            $stmtIns     = $pdo->prepare("INSERT INTO materiais_catalogo (codigo, nome, unidade, estoque_minimo) VALUES (?,?,?,?)");
            $stmtUpdCod  = $pdo->prepare("UPDATE materiais_catalogo SET nome=?, unidade=?, estoque_minimo=? WHERE codigo=?");
            $stmtUpdNome = $pdo->prepare("UPDATE materiais_catalogo SET unidade=?, estoque_minimo=? WHERE (codigo IS NULL OR codigo='') AND nome=?");
            $stmtEstIns  = $pdo->prepare("INSERT INTO materiais_estoque (material_id, quantidade_fisica, quantidade_reservada) VALUES (?,?,0)");
            $stmtEstUpd  = $pdo->prepare("UPDATE materiais_estoque SET quantidade_fisica=? WHERE material_id=?");
            $stmtIdCod   = $pdo->prepare("SELECT id FROM materiais_catalogo WHERE codigo = ?");
            $stmtIdNome  = $pdo->prepare("SELECT id FROM materiais_catalogo WHERE (codigo IS NULL OR codigo='') AND nome = ?");

            $ok = 0;
            foreach ($rows as $r) {
                if (!in_array($r['_status'], ['novo', 'atualizar'])) continue;
                try {
                    $pdo->beginTransaction();
                    if ($r['_status'] === 'novo') {
                        $stmtIns->execute([$r['codigo'] ?: null, $r['nome'], $r['unidade'], $r['estoque_minimo']]);
                        $mid = (int)$pdo->lastInsertId();
                        if ($mid > 0 && $r['estoque_atual'] !== null) {
                            $stmtEstIns->execute([$mid, $r['estoque_atual']]);
                        }
                    } elseif (($r['_chave_tipo'] ?? 'codigo') === 'codigo') {
                        $stmtUpdCod->execute([$r['nome'], $r['unidade'], $r['estoque_minimo'], $r['codigo']]);
                        $stmtIdCod->execute([$r['codigo']]);
                        $mid = (int)$stmtIdCod->fetchColumn();
                        if ($mid > 0 && $r['estoque_atual'] !== null) {
                            $stmtEstUpd->execute([$r['estoque_atual'], $mid]);
                        }
                    } else {
                        $stmtUpdNome->execute([$r['unidade'], $r['estoque_minimo'], $r['nome']]);
                        $stmtIdNome->execute([$r['nome']]);
                        $mid = (int)$stmtIdNome->fetchColumn();
                        if ($mid > 0 && $r['estoque_atual'] !== null) {
                            $stmtEstUpd->execute([$r['estoque_atual'], $mid]);
                        }
                    }
                    $pdo->commit();
                    $ok++;
                } catch (\Exception $e) {
                    $pdo->rollBack();
                }
            }

            try {
                $pdo->prepare("INSERT INTO log_auditoria (admin_id, acao, detalhes) VALUES (?,?,?)")
                    ->execute([(int)($_SESSION['usuario_id']??0), 'importacao',
                        json_encode(['modulo'=>'materiais','linhas'=>count($rows),'importadas'=>$ok])]);
            } catch (\Exception $e) {}
            $_SESSION['flash_ok'] = "$ok material(is) importado(s).";
            header('Location: ' . APP_BASE . '/materiais');
            exit;
        }

        // ── Fase: upload ─────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'upload') {
            csrf_verify();
            if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['flash_erro'] = 'Arquivo inválido.';
                header('Location: ' . APP_BASE . '/materiais/importar');
                exit;
            }

            $allRows = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['arquivo']['tmp_name'])
                ->getActiveSheet()->toArray(null, true, false, false);

            $preview_rows = [];
            $chkCod  = $pdo->prepare("SELECT COUNT(*) FROM materiais_catalogo WHERE codigo = ?");
            $chkNome = $pdo->prepare("SELECT COUNT(*) FROM materiais_catalogo WHERE (codigo IS NULL OR codigo='') AND nome = ?");
            foreach ($allRows as $i => $linha) {
                if ($i < 6) continue;
                $codigo = trim((string)($linha[0] ?? ''));
                $nome   = trim((string)($linha[1] ?? ''));

                if ($nome === '') {
                    $preview_rows[] = ['_linha'=>$i+1,'_status'=>'erro',
                        '_msg'=>"'Nome do material' obrigatório",'_chave_tipo'=>'codigo',
                        'codigo'=>$codigo,'nome'=>'','unidade'=>'un','estoque_minimo'=>0,'estoque_atual'=>null];
                    continue;
                }

                $estMin   = str_replace(',', '.', (string)($linha[3] ?? '0'));
                $estAtual = str_replace(',', '.', (string)($linha[4] ?? ''));

                if ($codigo !== '') {
                    $chkCod->execute([$codigo]);
                    $status = (int)$chkCod->fetchColumn() > 0 ? 'atualizar' : 'novo';
                    $chave_tipo = 'codigo';
                } else {
                    $chkNome->execute([$nome]);
                    $status = (int)$chkNome->fetchColumn() > 0 ? 'atualizar' : 'novo';
                    $chave_tipo = 'nome';
                }

                $preview_rows[] = [
                    '_linha'        => $i + 1,
                    '_status'       => $status,
                    '_msg'          => '',
                    '_chave_tipo'   => $chave_tipo,
                    'codigo'        => $codigo,
                    'nome'          => $nome,
                    'unidade'       => trim((string)($linha[2] ?? 'un')),
                    'estoque_minimo'=> is_numeric($estMin) ? (float)$estMin : 0,
                    'estoque_atual' => is_numeric($estAtual) ? (float)$estAtual : null,
                ];
            }

            $_SESSION['import_prev_materiais'] = [
                'rows'   => $preview_rows,
                'totals' => import_preview_totals($preview_rows),
            ];
            header('Location: ' . APP_BASE . '/materiais/importar');
            exit;
        }

        // ── GET ──────────────────────────────────────────
        $preview = $_SESSION['import_prev_materiais'] ?? null;
        require __DIR__ . '/../views/materiais/importar.php';
    }

    /* =====================================================
       IMPORTAÇÃO POSIÇÃO DE ESTOQUE — 2-FASES
    ===================================================== */
    public function importarEstoque()
    {
        auth_required([4]);
        global $pdo;

        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        require_once dirname(__DIR__) . '/helpers/import_excel.php';

        // File 06 special structure:
        // Row 5 (idx 4): metadata — A="Data da verificação:", B=date, C="Responsável pela contagem:", D=name
        // Row 8 (idx 7): headers — A=Código, B=Tipo de material, C=Unidade, D=Estoque atual
        // Row 9 (idx 8): gray example — SKIP
        // Rows 10+ (idx 9+): data

        // ── Fase: cancelar ───────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'cancelar') {
            unset($_SESSION['import_prev_estoque']);
            header('Location: ' . APP_BASE . '/materiais/importar-estoque');
            exit;
        }

        // ── Fase: confirmar ──────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'confirmar') {
            csrf_verify();
            $data = $_SESSION['import_prev_estoque'] ?? [];
            unset($_SESSION['import_prev_estoque']);

            $rows = $data['rows'] ?? [];
            $meta = $data['meta'] ?? [];

            // Create contagem record
            $stmtCont = $pdo->prepare("
                INSERT INTO contagens_estoque (data_contagem, responsavel, usuario_id)
                VALUES (?,?,?)
            ");
            $stmtCont->execute([
                $meta['data_contagem'] ?? date('Y-m-d'),
                $meta['responsavel'] ?? null,
                (int)($_SESSION['usuario_id'] ?? 0),
            ]);
            $contagem_id = (int)$pdo->lastInsertId();

            $stmtId  = $pdo->prepare("SELECT id FROM materiais_catalogo WHERE codigo = ?");
            $stmtUpd = $pdo->prepare("
                UPDATE materiais_estoque SET quantidade_fisica = ? WHERE material_id = ?
            ");
            $stmtIns = $pdo->prepare("
                INSERT INTO materiais_estoque (material_id, quantidade_fisica, quantidade_reservada)
                VALUES (?,?,0)
                ON DUPLICATE KEY UPDATE quantidade_fisica = VALUES(quantidade_fisica)
            ");
            $stmtMov = $pdo->prepare("
                INSERT INTO materiais_movimentos
                    (material_id, tipo, quantidade, referencia_tipo, referencia_id, observacao, usuario_id)
                VALUES (?,'ajuste',?,'contagem',?,'Contagem física importada',?)
            ");
            $stmtItem = $pdo->prepare("
                INSERT INTO contagens_estoque_itens (contagem_id, material_id, estoque_encontrado)
                VALUES (?,?,?)
                ON DUPLICATE KEY UPDATE estoque_encontrado=VALUES(estoque_encontrado)
            ");

            $ok = 0;
            $uid = (int)($_SESSION['usuario_id'] ?? 0);
            foreach ($rows as $r) {
                if ($r['_status'] !== 'atualizar') continue;
                try {
                    $stmtId->execute([$r['codigo']]);
                    $mid = (int)$stmtId->fetchColumn();
                    if ($mid <= 0) continue;

                    $qtd = (float)$r['estoque_atual'];
                    $stmtIns->execute([$mid, $qtd]);
                    $stmtMov->execute([$mid, $qtd, $contagem_id, $uid]);
                    $stmtItem->execute([$contagem_id, $mid, $qtd]);
                    $ok++;
                } catch (\Exception $e) {}
            }

            try {
                $pdo->prepare("INSERT INTO log_auditoria (admin_id, acao, detalhes) VALUES (?,?,?)")
                    ->execute([(int)($_SESSION['usuario_id']??0), 'importacao',
                        json_encode(['modulo'=>'posicao_estoque','contagem_id'=>$contagem_id,'itens'=>$ok])]);
            } catch (\Exception $e) {}
            $_SESSION['flash_ok'] = "Contagem aplicada: $ok material(is) atualizado(s).";
            header('Location: ' . APP_BASE . '/materiais');
            exit;
        }

        // ── Fase: upload ─────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'upload') {
            csrf_verify();
            if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['flash_erro'] = 'Arquivo inválido.';
                header('Location: ' . APP_BASE . '/materiais/importar-estoque');
                exit;
            }

            $allRows = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['arquivo']['tmp_name'])
                ->getActiveSheet()->toArray(null, true, false, false);

            // Extract metadata from row index 4 (Excel row 5)
            $metaRow = $allRows[4] ?? [];
            $meta = [
                'data_contagem' => import_parse_date($metaRow[1] ?? null) ?? date('Y-m-d'),
                'responsavel'   => trim((string)($metaRow[3] ?? '')),
            ];

            $stmtChk = $pdo->prepare("SELECT id, nome FROM materiais_catalogo WHERE codigo = ?");
            $preview_rows = [];
            foreach ($allRows as $i => $linha) {
                if ($i < 9) continue; // skip banner(0-2), instr(3), metadata(4), blank(5-6), headers(7), example(8)
                $codigo = trim((string)($linha[0] ?? ''));
                if ($codigo === '') continue;

                $estAtual = str_replace(',', '.', (string)($linha[3] ?? ''));

                $stmtChk->execute([$codigo]);
                $mat = $stmtChk->fetch(\PDO::FETCH_ASSOC);

                if (!$mat) {
                    $preview_rows[] = [
                        '_linha'       => $i + 1,
                        '_status'      => 'erro',
                        '_msg'         => 'Código não encontrado',
                        'codigo'       => $codigo,
                        'tipo_material'=> trim((string)($linha[1] ?? '')),
                        'nome'         => '',
                        'unidade'      => trim((string)($linha[2] ?? '')),
                        'estoque_atual'=> $estAtual,
                    ];
                    continue;
                }

                $preview_rows[] = [
                    '_linha'       => $i + 1,
                    '_status'      => 'atualizar',
                    '_msg'         => '',
                    'codigo'       => $codigo,
                    'tipo_material'=> trim((string)($linha[1] ?? '')),
                    'nome'         => $mat['nome'],
                    'unidade'      => trim((string)($linha[2] ?? '')),
                    'estoque_atual'=> is_numeric($estAtual) ? (float)$estAtual : 0,
                ];
            }

            $_SESSION['import_prev_estoque'] = [
                'rows'   => $preview_rows,
                'meta'   => $meta,
                'totals' => import_preview_totals($preview_rows),
            ];
            header('Location: ' . APP_BASE . '/materiais/importar-estoque');
            exit;
        }

        // ── GET ──────────────────────────────────────────
        $preview = $_SESSION['import_prev_estoque'] ?? null;
        require __DIR__ . '/../views/materiais/importar_estoque.php';
    }
}
