<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';

class TrechoController
{
    /* =====================================================
       LISTAR
    ===================================================== */
    public function index()
    {
        auth_required([4]);
        global $pdo;

        $bacia      = trim($_GET['bacia'] ?? '');
        $status_rede = trim($_GET['status_rede'] ?? '');
        $sel_id     = (int)($_GET['sel'] ?? 0);

        $where = '1=1';
        $params = [];

        if ($bacia !== '') {
            $where .= ' AND t.bacia = ?';
            $params[] = $bacia;
        }
        if ($status_rede !== '') {
            $where .= ' AND t.status_rede = ?';
            $params[] = $status_rede;
        }

        $stmt = $pdo->prepare("
            SELECT t.*,
                   os.id AS os_id, os.arquivo_pdf AS os_arquivo, os.versao AS os_versao, os.data_os
            FROM trechos t
            LEFT JOIN ordens_servico os ON os.trecho_id = t.id AND os.ativa = 1
            WHERE $where
            ORDER BY t.bacia, t.pv_montante
        ");
        $stmt->execute($params);
        $trechos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Bacias disponíveis para filtro
        $bacias = $pdo->query("SELECT DISTINCT bacia FROM trechos WHERE bacia IS NOT NULL ORDER BY bacia")
                      ->fetchAll(PDO::FETCH_COLUMN);

        // Trecho selecionado para painel lateral
        $trecho_sel = null;
        $os_historico = [];
        if ($sel_id > 0) {
            $stmt2 = $pdo->prepare("SELECT * FROM trechos WHERE id = ?");
            $stmt2->execute([$sel_id]);
            $trecho_sel = $stmt2->fetch(PDO::FETCH_ASSOC);

            if ($trecho_sel) {
                $stmt3 = $pdo->prepare("
                    SELECT os.*, u.nome AS criador
                    FROM ordens_servico os
                    LEFT JOIN usuarios u ON u.id = os.criado_por
                    WHERE os.trecho_id = ?
                    ORDER BY os.versao DESC
                ");
                $stmt3->execute([$sel_id]);
                $os_historico = $stmt3->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        require __DIR__ . '/../views/trechos/listar.php';
    }

    /* =====================================================
       CADASTRAR
    ===================================================== */
    public function create()
    {
        auth_required([4]);
        require __DIR__ . '/../views/trechos/cadastrar.php';
    }

    /* =====================================================
       SALVAR
    ===================================================== */
    public function store()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();

        $bacia            = trim($_POST['bacia'] ?? '');
        $pv_montante      = trim($_POST['pv_montante'] ?? '');
        $pv_jusante       = trim($_POST['pv_jusante'] ?? '');
        $tipo_pi_montante = trim($_POST['tipo_pi_montante'] ?? '');
        $extensao         = str_replace(',', '.', trim($_POST['extensao'] ?? ''));
        $profundidade     = str_replace(',', '.', trim($_POST['profundidade_media'] ?? ''));
        $dn               = trim($_POST['dn'] ?? '');
        $rua              = trim($_POST['rua'] ?? '');
        $cidade           = trim($_POST['cidade'] ?? '');
        $contrato         = trim($_POST['contrato'] ?? '');
        $ramais           = (int)($_POST['ramais'] ?? 0);

        if ($pv_montante === '') {
            $_SESSION['flash_erro'] = 'PV Montante é obrigatório.';
            header('Location: ' . APP_BASE . '/trechos/cadastrar');
            exit;
        }

        $pdo->prepare("
            INSERT INTO trechos
                (bacia, pv_montante, pv_jusante, tipo_pi_montante, extensao, profundidade_media,
                 dn, rua, cidade, contrato, ramais, criado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $bacia ?: null,
            $pv_montante,
            $pv_jusante ?: null,
            $tipo_pi_montante ?: null,
            is_numeric($extensao) ? $extensao : null,
            is_numeric($profundidade) ? $profundidade : null,
            $dn ?: null,
            $rua ?: null,
            $cidade ?: null,
            $contrato ?: null,
            $ramais,
            $_SESSION['usuario_id'] ?? null,
        ]);

        $_SESSION['flash_ok'] = 'Trecho cadastrado com sucesso.';
        header('Location: ' . APP_BASE . '/trechos');
        exit;
    }

    /* =====================================================
       EDITAR
    ===================================================== */
    public function edit()
    {
        auth_required([4]);
        global $pdo;

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . APP_BASE . '/trechos');
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM trechos WHERE id = ?");
        $stmt->execute([$id]);
        $trecho = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trecho) {
            header('Location: ' . APP_BASE . '/trechos');
            exit;
        }

        // C1: materiais do trecho e catálogo disponível
        $stmt = $pdo->prepare("
            SELECT tm.id, tm.material_id, mc.nome, mc.unidade, tm.quantidade
            FROM trecho_materiais tm
            JOIN materiais_catalogo mc ON mc.id = tm.material_id
            WHERE tm.trecho_id = ?
            ORDER BY mc.nome
        ");
        $stmt->execute([$id]);
        $materiais_trecho = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $catalogo = $pdo->query("
            SELECT id, nome, unidade
            FROM materiais_catalogo
            WHERE ativo = 1
            ORDER BY nome
        ")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/trechos/editar.php';
    }

    /* =====================================================
       C1 — MATERIAIS DO TRECHO (AJAX)
    ===================================================== */
    public function addMaterial()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();
        header('Content-Type: application/json');

        $trecho_id   = (int)($_POST['trecho_id'] ?? 0);
        $material_id = (int)($_POST['material_id'] ?? 0);
        $quantidade  = str_replace(',', '.', trim($_POST['quantidade'] ?? ''));

        if ($trecho_id <= 0 || $material_id <= 0 || !is_numeric($quantidade) || (float)$quantidade <= 0) {
            echo json_encode(['ok' => false, 'erro' => 'Dados inválidos.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM trechos WHERE id = ?");
        $stmt->execute([$trecho_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['ok' => false, 'erro' => 'Trecho não encontrado.']);
            exit;
        }

        $pdo->prepare("
            INSERT INTO trecho_materiais (trecho_id, material_id, quantidade)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantidade = VALUES(quantidade)
        ")->execute([$trecho_id, $material_id, (float)$quantidade]);

        $stmt = $pdo->prepare("
            SELECT tm.id, tm.material_id, mc.nome, mc.unidade, tm.quantidade
            FROM trecho_materiais tm
            JOIN materiais_catalogo mc ON mc.id = tm.material_id
            WHERE tm.trecho_id = ? AND tm.material_id = ?
        ");
        $stmt->execute([$trecho_id, $material_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['ok' => true, 'item' => $item]);
        exit;
    }

    public function removeMaterial()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();
        header('Content-Type: application/json');

        $id        = (int)($_POST['id'] ?? 0);
        $trecho_id = (int)($_POST['trecho_id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['ok' => false, 'erro' => 'ID inválido.']);
            exit;
        }

        $pdo->prepare("DELETE FROM trecho_materiais WHERE id = ? AND trecho_id = ?")
            ->execute([$id, $trecho_id]);

        echo json_encode(['ok' => true]);
        exit;
    }

    /* =====================================================
       ATUALIZAR
    ===================================================== */
    public function update()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();

        $id               = (int)($_POST['id'] ?? 0);
        $bacia            = trim($_POST['bacia'] ?? '');
        $pv_montante      = trim($_POST['pv_montante'] ?? '');
        $pv_jusante       = trim($_POST['pv_jusante'] ?? '');
        $tipo_pi_montante = trim($_POST['tipo_pi_montante'] ?? '');
        $extensao         = str_replace(',', '.', trim($_POST['extensao'] ?? ''));
        $profundidade     = str_replace(',', '.', trim($_POST['profundidade_media'] ?? ''));
        $dn               = trim($_POST['dn'] ?? '');
        $rua              = trim($_POST['rua'] ?? '');
        $cidade           = trim($_POST['cidade'] ?? '');
        $contrato         = trim($_POST['contrato'] ?? '');
        $ramais           = (int)($_POST['ramais'] ?? 0);

        if ($id <= 0 || $pv_montante === '') {
            $_SESSION['flash_erro'] = 'Dados inválidos.';
            header('Location: ' . APP_BASE . '/trechos');
            exit;
        }

        $pdo->prepare("
            UPDATE trechos SET
                bacia = ?, pv_montante = ?, pv_jusante = ?, tipo_pi_montante = ?,
                extensao = ?, profundidade_media = ?, dn = ?, rua = ?, cidade = ?,
                contrato = ?, ramais = ?
            WHERE id = ?
        ")->execute([
            $bacia ?: null,
            $pv_montante,
            $pv_jusante ?: null,
            $tipo_pi_montante ?: null,
            is_numeric($extensao) ? $extensao : null,
            is_numeric($profundidade) ? $profundidade : null,
            $dn ?: null,
            $rua ?: null,
            $cidade ?: null,
            $contrato ?: null,
            $ramais,
            $id,
        ]);

        $_SESSION['flash_ok'] = 'Trecho atualizado com sucesso.';
        header('Location: ' . APP_BASE . '/trechos?sel=' . $id);
        exit;
    }

    /* =====================================================
       UPLOAD DE OS (PDF)
    ===================================================== */
    public function uploadOS()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();

        $trecho_id = (int)($_POST['trecho_id'] ?? 0);
        if ($trecho_id <= 0) {
            $_SESSION['flash_erro'] = 'Trecho inválido.';
            header('Location: ' . APP_BASE . '/trechos');
            exit;
        }

        // Verificar que o trecho existe
        $stmt = $pdo->prepare("SELECT id FROM trechos WHERE id = ?");
        $stmt->execute([$trecho_id]);
        if (!$stmt->fetch()) {
            $_SESSION['flash_erro'] = 'Trecho não encontrado.';
            header('Location: ' . APP_BASE . '/trechos');
            exit;
        }

        // Validação do arquivo
        if (empty($_FILES['arquivo_os']) || $_FILES['arquivo_os']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_erro'] = 'Arquivo não recebido ou erro no upload.';
            header('Location: ' . APP_BASE . '/trechos/editar?id=' . $trecho_id);
            exit;
        }

        $arquivo = $_FILES['arquivo_os'];

        // Validar tamanho (max 10MB)
        if ($arquivo['size'] > 10 * 1024 * 1024) {
            $_SESSION['flash_erro'] = 'Arquivo muito grande (máx. 10MB).';
            header('Location: ' . APP_BASE . '/trechos/editar?id=' . $trecho_id);
            exit;
        }

        // Validar mime type real
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($arquivo['tmp_name']);
        if ($mimeReal !== 'application/pdf') {
            $_SESSION['flash_erro'] = 'Apenas arquivos PDF são aceitos.';
            header('Location: ' . APP_BASE . '/trechos/editar?id=' . $trecho_id);
            exit;
        }

        // Buscar versão atual
        $stmt = $pdo->prepare("SELECT MAX(versao) FROM ordens_servico WHERE trecho_id = ?");
        $stmt->execute([$trecho_id]);
        $versaoAtual = (int)$stmt->fetchColumn();
        $novaVersao  = $versaoAtual + 1;

        // Salvar arquivo
        $nomeArquivo = 'os_trecho' . $trecho_id . '_v' . $novaVersao . '_' . time() . '.pdf';
        $destino     = __DIR__ . '/../../uploads/os/' . $nomeArquivo;

        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            $_SESSION['flash_erro'] = 'Falha ao salvar arquivo.';
            header('Location: ' . APP_BASE . '/trechos/editar?id=' . $trecho_id);
            exit;
        }

        // Desativar OS anteriores
        $pdo->prepare("UPDATE ordens_servico SET ativa = 0 WHERE trecho_id = ?")
            ->execute([$trecho_id]);

        // Inserir nova OS
        $data_os    = trim($_POST['data_os'] ?? '') ?: null;
        $topografo  = trim($_POST['topografo'] ?? '') ?: null;

        $pdo->prepare("
            INSERT INTO ordens_servico (trecho_id, topografo, data_os, arquivo_pdf, versao, ativa, criado_por)
            VALUES (?, ?, ?, ?, ?, 1, ?)
        ")->execute([
            $trecho_id,
            $topografo,
            $data_os,
            $nomeArquivo,
            $novaVersao,
            $_SESSION['usuario_id'] ?? null,
        ]);

        $_SESSION['flash_ok'] = 'OS v' . $novaVersao . ' enviada com sucesso.';
        header('Location: ' . APP_BASE . '/trechos?sel=' . $trecho_id);
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

        // Excel columns (0-indexed): 0=PV Mont, 1=PV Jus, 2=Bacia, 3=Tipo PI Mont,
        // 4=Extensão(m), 5=Prof.Média(m), 6=DN/Material, 7=Ramais, 8=Rua, 9=Cidade, 10=Contrato

        // ── Fase: cancelar ───────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'cancelar') {
            unset($_SESSION['import_prev_trechos']);
            header('Location: ' . APP_BASE . '/trechos/importar');
            exit;
        }

        // ── Fase: confirmar ──────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'confirmar') {
            csrf_verify();
            $rows = $_SESSION['import_prev_trechos']['rows'] ?? [];
            unset($_SESSION['import_prev_trechos']);

            $stmtChk = $pdo->prepare("SELECT id FROM trechos WHERE pv_montante = ? AND pv_jusante = ? AND (contrato = ? OR (contrato IS NULL AND ? IS NULL))");
            $stmtIns = $pdo->prepare("
                INSERT INTO trechos
                    (bacia, pv_montante, pv_jusante, tipo_pi_montante, extensao,
                     profundidade_media, dn, ramais, rua, cidade, contrato, criado_por)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmtUpd = $pdo->prepare("
                UPDATE trechos SET
                    bacia=?, tipo_pi_montante=?, extensao=?,
                    profundidade_media=?, dn=?, ramais=?, rua=?, cidade=?
                WHERE pv_montante=? AND pv_jusante=? AND (contrato = ? OR (contrato IS NULL AND ? IS NULL))
            ");

            $ok = 0;
            $uid = (int)($_SESSION['usuario_id'] ?? 0);
            foreach ($rows as $r) {
                if (!in_array($r['_status'], ['novo', 'atualizar'])) continue;
                try {
                    if ($r['_status'] === 'novo') {
                        $stmtIns->execute([
                            $r['bacia'], $r['pv_montante'], $r['pv_jusante'],
                            $r['tipo_pi_montante'], $r['extensao'], $r['profundidade'],
                            $r['dn'], $r['ramais'], $r['rua'], $r['cidade'], $r['contrato'],
                            $uid,
                        ]);
                    } else {
                        $stmtUpd->execute([
                            $r['bacia'], $r['tipo_pi_montante'], $r['extensao'],
                            $r['profundidade'], $r['dn'], $r['ramais'],
                            $r['rua'], $r['cidade'],
                            $r['pv_montante'], $r['pv_jusante'], $r['contrato'], $r['contrato'],
                        ]);
                    }
                    $ok++;
                } catch (\Exception $e) {}
            }

            try {
                $pdo->prepare("INSERT INTO log_auditoria (admin_id, acao, detalhes) VALUES (?,?,?)")
                    ->execute([(int)($_SESSION['usuario_id']??0), 'importacao',
                        json_encode(['modulo'=>'trechos','linhas'=>count($rows),'importadas'=>$ok])]);
            } catch (\Exception $e) {}
            $_SESSION['flash_ok'] = "$ok trecho(s) importado(s) com sucesso.";
            header('Location: ' . APP_BASE . '/trechos');
            exit;
        }

        // ── Fase: upload ─────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'upload') {
            csrf_verify();
            if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['flash_erro'] = 'Arquivo inválido.';
                header('Location: ' . APP_BASE . '/trechos/importar');
                exit;
            }

            $allRows = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['arquivo']['tmp_name'])
                ->getActiveSheet()->toArray(null, true, false, false);

            $preview_rows = [];
            $stmtChk = $pdo->prepare("SELECT COUNT(*) FROM trechos WHERE pv_montante = ? AND pv_jusante = ? AND (contrato = ? OR (contrato IS NULL AND ? IS NULL))");
            foreach ($allRows as $i => $linha) {
                if ($i < 6) continue;
                $pv_mont  = trim((string)($linha[0] ?? ''));
                $pv_jus   = trim((string)($linha[1] ?? ''));
                $contrato = trim((string)($linha[10] ?? '')) ?: null;
                if ($pv_mont === '') {
                    $ext2  = str_replace(',', '.', (string)($linha[4] ?? ''));
                    $prof2 = str_replace(',', '.', (string)($linha[5] ?? ''));
                    $preview_rows[] = [
                        '_linha'=>$i+1,'_status'=>'erro','_msg'=>"'PV Montante' obrigatório",
                        'pv_montante'=>'','pv_jusante'=>$pv_jus,'bacia'=>trim((string)($linha[2]??'')),
                        'tipo_pi_montante'=>'','extensao'=>null,'profundidade'=>null,'dn'=>null,
                        'ramais'=>0,'rua'=>'','cidade'=>'','contrato'=>$contrato,
                    ];
                    continue;
                }

                $stmtChk->execute([$pv_mont, $pv_jus, $contrato, $contrato]);
                $status = (int)$stmtChk->fetchColumn() > 0 ? 'atualizar' : 'novo';

                $ext  = str_replace(',', '.', (string)($linha[4] ?? ''));
                $prof = str_replace(',', '.', (string)($linha[5] ?? ''));
                $preview_rows[] = [
                    '_linha'         => $i + 1,
                    '_status'        => $status,
                    '_msg'           => '',
                    'pv_montante'    => $pv_mont,
                    'pv_jusante'     => $pv_jus,
                    'bacia'          => trim((string)($linha[2] ?? '')),
                    'tipo_pi_montante'=> trim((string)($linha[3] ?? '')),
                    'extensao'       => is_numeric($ext) ? (float)$ext : null,
                    'profundidade'   => is_numeric($prof) ? (float)$prof : null,
                    'dn'             => ($linha[6] !== null && $linha[6] !== '') ? (string)(int)$linha[6] : null,
                    'ramais'         => (int)($linha[7] ?? 0),
                    'rua'            => trim((string)($linha[8] ?? '')),
                    'cidade'         => trim((string)($linha[9] ?? '')),
                    'contrato'       => trim((string)($linha[10] ?? '')),
                ];
            }

            $_SESSION['import_prev_trechos'] = [
                'rows'   => $preview_rows,
                'totals' => import_preview_totals($preview_rows),
            ];
            header('Location: ' . APP_BASE . '/trechos/importar');
            exit;
        }

        // ── GET ──────────────────────────────────────────
        $preview = $_SESSION['import_prev_trechos'] ?? null;
        require __DIR__ . '/../views/trechos/importar.php';
    }
}
