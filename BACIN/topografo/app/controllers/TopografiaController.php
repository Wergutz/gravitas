<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';

class TopografiaController
{
    /* =====================================================
       INDEX — Lista OS do topógrafo
    ===================================================== */
    public function index()
    {
        auth_required_topografo();
        global $pdo;

        $nivel = (int)($_SESSION['nivel'] ?? 0);
        $uid   = (int)($_SESSION['usuario_id'] ?? 0);

        if ($nivel === 1) {
            // Superadmin: vê tudo
            $stmt = $pdo->query("
                SELECT ot.*,
                       t.pv_montante, t.pv_jusante, t.bacia, t.extensao, t.dn, t.rua, t.cidade, t.contrato, t.ramais,
                       u_imp.nome AS importado_nome,
                       u_lib.nome AS liberado_nome
                FROM os_topografia ot
                JOIN trechos t ON t.id = ot.trecho_id
                JOIN usuarios u_imp ON u_imp.id = ot.importado_por
                LEFT JOIN usuarios u_lib ON u_lib.id = ot.liberado_por
                ORDER BY ot.importado_em DESC
            ");
        } else {
            // Topógrafo: apenas suas próprias OS
            $stmt = $pdo->prepare("
                SELECT ot.*,
                       t.pv_montante, t.pv_jusante, t.bacia, t.extensao, t.dn, t.rua, t.cidade, t.contrato, t.ramais,
                       u_imp.nome AS importado_nome,
                       u_lib.nome AS liberado_nome
                FROM os_topografia ot
                JOIN trechos t ON t.id = ot.trecho_id
                JOIN usuarios u_imp ON u_imp.id = ot.importado_por
                LEFT JOIN usuarios u_lib ON u_lib.id = ot.liberado_por
                WHERE ot.importado_por = ?
                ORDER BY ot.importado_em DESC
            ");
            $stmt->execute([$uid]);
        }
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $layout    = 'topografo';
        $pageTitle = 'Topografia';
        require __DIR__ . '/../views/topografia/index.php';
    }

    /* =====================================================
       IMPORTAR — 2 fases
    ===================================================== */
    public function importar()
    {
        auth_required_topografo();
        global $pdo;

        require_once dirname(__DIR__, 3) . '/painel/vendor/autoload.php';
        require_once dirname(__DIR__) . '/helpers/import_excel.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'cancelar') {
            unset($_SESSION['import_prev_topo']);
            header('Location: ' . APP_BASE . '/topografia/importar');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'confirmar') {
            csrf_verify();
            $groups = $_SESSION['import_prev_topo']['groups'] ?? [];
            unset($_SESSION['import_prev_topo']);

            $uid = (int)($_SESSION['usuario_id'] ?? 0);
            $ok  = 0;

            foreach ($groups as $g) {
                if (!in_array($g['_status'], ['novo', 'atualizar'])) continue;

                $pdo->beginTransaction();
                try {
                    $trechoId = (int)$g['trecho_id'];
                    $revisao  = 1;

                    if ($g['_status'] === 'atualizar') {
                        $stmtRev = $pdo->prepare("SELECT MAX(revisao) FROM os_topografia WHERE trecho_id = ?");
                        $stmtRev->execute([$trechoId]);
                        $revisao = (int)$stmtRev->fetchColumn() + 1;
                    }

                    $stmtIns = $pdo->prepare("
                        INSERT INTO os_topografia
                            (trecho_id, data_os, cota_tampa_montante, cota_fundo_montante,
                             cota_tampa_jusante, cota_fundo_jusante, declividade, regua,
                             diam_externo_esp, prof_media, observacoes, revisao, status,
                             importado_por)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'aguardando_liberacao',?)
                    ");
                    $stmtIns->execute([
                        $trechoId,
                        $g['data_os'],
                        $g['cota_tampa_montante'],
                        $g['cota_fundo_montante'],
                        $g['cota_tampa_jusante'],
                        $g['cota_fundo_jus_calc'],
                        $g['declividade'],
                        $g['regua'],
                        $g['diam_externo_esp'],
                        $g['prof_media_calc'],
                        $g['observacoes'],
                        $revisao,
                        $uid,
                    ]);
                    $osId = (int)$pdo->lastInsertId();

                    $stmtEst = $pdo->prepare("
                        INSERT INTO os_topografia_estacas
                            (os_id, estaca, comp_acumulado, cota_auxiliar, cota_eixo,
                             cota_rede_gi, cota_rede_gs, cota_gabarito, altura_gabarito, prof_vala)
                        VALUES (?,?,?,?,?,?,?,?,?,?)
                    ");
                    foreach ($g['estacas'] as $e) {
                        $stmtEst->execute([
                            $osId, $e['estaca'], $e['comp_acumulado'],
                            $e['cota_auxiliar'], $e['cota_eixo'],
                            $e['cota_rede_gi'], $e['cota_rede_gs'],
                            $e['cota_gabarito'], $e['altura_gabarito'], $e['prof_vala'],
                        ]);
                    }

                    $pdo->prepare("UPDATE trechos SET profundidade_media = ? WHERE id = ?")
                        ->execute([$g['prof_media_calc'], $trechoId]);

                    $arquivoOS = $this->gerarArquivoOS($osId);
                    $pdo->prepare("UPDATE os_topografia SET arquivo_os = ? WHERE id = ?")
                        ->execute([$arquivoOS, $osId]);

                    $pdo->commit();
                    $ok++;
                } catch (\Exception $e) {
                    $pdo->rollBack();
                }
            }

            $_SESSION['flash_ok'] = "$ok OS topografia importada(s) com sucesso.";
            header('Location: ' . APP_BASE . '/topografia');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['fase'] ?? '') === 'upload') {
            csrf_verify();
            if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['flash_erro'] = 'Arquivo inválido.';
                header('Location: ' . APP_BASE . '/topografia/importar');
                exit;
            }

            $allRows = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['arquivo']['tmp_name'])
                ->getActiveSheet()->toArray(null, true, false, false);

            $rawGroups = [];
            foreach ($allRows as $i => $linha) {
                if ($i < 6) continue;
                $contrato = trim((string)($linha[0] ?? ''));
                $pv_mont  = trim((string)($linha[1] ?? ''));
                $pv_jus   = trim((string)($linha[2] ?? ''));
                if ($contrato === '' && $pv_mont === '') continue;
                $chave = $contrato . '|' . $pv_mont . '|' . $pv_jus;
                $rawGroups[$chave][] = ['idx' => $i, 'linha' => $linha];
            }

            $stmtTrecho = $pdo->prepare("
                SELECT id, extensao FROM trechos
                WHERE contrato = ? AND pv_montante = ? AND pv_jusante = ?
                LIMIT 1
            ");
            $stmtExisteOS = $pdo->prepare("SELECT COUNT(*) FROM os_topografia WHERE trecho_id = ?");

            $groups = [];
            foreach ($rawGroups as $chave => $linhas) {
                [$contrato, $pv_mont, $pv_jus] = explode('|', $chave, 3);
                $pl = $linhas[0]['linha'];
                $data_os          = import_parse_date($pl[3] ?? null) ?? date('Y-m-d');
                $cota_tampa_mont  = (float)str_replace(',', '.', (string)($pl[4] ?? 0));
                $cota_fundo_mont  = (float)str_replace(',', '.', (string)($pl[5] ?? 0));
                $cota_tampa_jus   = (float)str_replace(',', '.', (string)($pl[6] ?? 0));
                $declividade      = (float)str_replace(',', '.', (string)($pl[7] ?? 0));
                $regua            = (float)str_replace(',', '.', (string)($pl[8] ?? 0));
                $diam_externo_esp = (int)($pl[9] ?? 0);
                $observacoes      = trim((string)($pl[14] ?? '')) ?: null;

                $stmtTrecho->execute([$contrato, $pv_mont, $pv_jus]);
                $trecho = $stmtTrecho->fetch(PDO::FETCH_ASSOC);

                if (!$trecho) {
                    $groups[] = [
                        '_status' => 'erro', '_grupo' => $chave,
                        '_msg' => "Trecho não encontrado: contrato=$contrato PVM=$pv_mont PVJ=$pv_jus",
                        'trecho_id' => null, 'trecho_info' => "$pv_mont → $pv_jus",
                        'contrato' => $contrato, 'pv_montante' => $pv_mont, 'pv_jusante' => $pv_jus,
                        'n_estacas' => count($linhas), 'estacas' => [],
                    ];
                    continue;
                }

                $trechoId    = (int)$trecho['id'];
                $extCadastro = (float)$trecho['extensao'];
                $erros = [];

                if (count($linhas) < 2) $erros[] = 'Mínimo 2 estacas por trecho.';
                if ($declividade < 0.0001 || $declividade > 0.2)
                    $erros[] = "Declividade fora do intervalo (0.0001–0.2): $declividade";

                $estacas = [];
                foreach ($linhas as $item) {
                    $l = $item['linha'];
                    $estacas[] = [
                        'estaca'         => trim((string)($l[10] ?? '')),
                        'comp_acumulado' => (float)str_replace(',', '.', (string)($l[11] ?? 0)),
                        'cota_auxiliar'  => ($l[12] !== null && $l[12] !== '') ? (float)str_replace(',', '.', (string)$l[12]) : null,
                        'cota_eixo'      => ($l[13] !== null && $l[13] !== '') ? (float)str_replace(',', '.', (string)$l[13]) : null,
                    ];
                }

                if (!empty($estacas) && $estacas[0]['comp_acumulado'] != 0)
                    $erros[] = 'Comp. acumulado da primeira estaca deve ser 0.';

                for ($k = 1; $k < count($estacas); $k++) {
                    if ($estacas[$k]['comp_acumulado'] <= $estacas[$k-1]['comp_acumulado']) {
                        $erros[] = 'Comp. acumulado deve ser estritamente crescente.'; break;
                    }
                }

                if (!empty($erros)) {
                    $groups[] = [
                        '_status' => 'erro', '_grupo' => $chave, '_msg' => implode(' | ', $erros),
                        'trecho_id' => $trechoId, 'trecho_info' => "$pv_mont → $pv_jus",
                        'contrato' => $contrato, 'pv_montante' => $pv_mont, 'pv_jusante' => $pv_jus,
                        'n_estacas' => count($estacas),
                        'extensao_planilha' => end($estacas) ? end($estacas)['comp_acumulado'] : 0,
                        'extensao_cadastro' => $extCadastro, 'declividade' => $declividade, 'estacas' => [],
                    ];
                    continue;
                }

                $maxComp = end($estacas)['comp_acumulado'];
                $cota_fundo_jus_calc = $cota_fundo_mont - $maxComp * $declividade;
                $profValaSum = 0; $profValaCount = 0;
                $estacasCalc = [];

                foreach ($estacas as $e) {
                    $comp    = $e['comp_acumulado'];
                    $gi      = $cota_fundo_jus_calc + $comp * $declividade;
                    $gs      = $gi + ($diam_externo_esp / 1000.0);
                    $gab     = $gi + $regua;
                    $altGab  = ($e['cota_auxiliar'] !== null) ? ($gab - $e['cota_auxiliar']) : 0.0;
                    $profVala = ($e['cota_eixo'] !== null) ? ($e['cota_eixo'] - $gi) : 0.0;
                    if ($e['cota_eixo'] !== null) { $profValaSum += $profVala; $profValaCount++; }
                    $estacasCalc[] = [
                        'estaca' => $e['estaca'], 'comp_acumulado' => $comp,
                        'cota_auxiliar' => $e['cota_auxiliar'], 'cota_eixo' => $e['cota_eixo'],
                        'cota_rede_gi' => round($gi, 3), 'cota_rede_gs' => round($gs, 3),
                        'cota_gabarito' => round($gab, 3), 'altura_gabarito' => round($altGab, 3),
                        'prof_vala' => round($profVala, 3),
                    ];
                }

                $prof_media_calc = $profValaCount > 0 ? round($profValaSum / $profValaCount, 3) : null;
                $stmtExisteOS->execute([$trechoId]);
                $jaExiste = (int)$stmtExisteOS->fetchColumn() > 0;

                $groups[] = [
                    '_status' => $jaExiste ? 'atualizar' : 'novo', '_grupo' => $chave, '_msg' => '',
                    'trecho_id' => $trechoId, 'trecho_info' => "$pv_mont → $pv_jus",
                    'contrato' => $contrato, 'pv_montante' => $pv_mont, 'pv_jusante' => $pv_jus,
                    'n_estacas' => count($estacasCalc), 'extensao_planilha' => $maxComp,
                    'extensao_cadastro' => $extCadastro, 'declividade' => $declividade,
                    'prof_media_calc' => $prof_media_calc, 'cota_fundo_jus_calc' => round($cota_fundo_jus_calc, 3),
                    'data_os' => $data_os, 'cota_tampa_montante' => $cota_tampa_mont,
                    'cota_fundo_montante' => $cota_fundo_mont, 'cota_tampa_jusante' => $cota_tampa_jus,
                    'regua' => $regua, 'diam_externo_esp' => $diam_externo_esp,
                    'observacoes' => $observacoes, 'estacas' => $estacasCalc,
                ];
            }

            $totals = ['novo' => 0, 'atualizar' => 0, 'erro' => 0];
            foreach ($groups as $g) { $s = $g['_status'] ?? 'erro'; if (isset($totals[$s])) $totals[$s]++; }

            $_SESSION['import_prev_topo'] = ['groups' => $groups, 'totals' => $totals];
            header('Location: ' . APP_BASE . '/topografia/importar');
            exit;
        }

        $layout    = 'topografo';
        $pageTitle = 'Importar Topografia';
        $preview = $_SESSION['import_prev_topo'] ?? null;
        require __DIR__ . '/../views/topografia/importar.php';
    }

    /* =====================================================
       EDITAR DECLIVIDADE — GET + POST
    ===================================================== */
    public function editarDeclividade(int $id)
    {
        auth_required_topografo();
        global $pdo;

        $nivel = (int)($_SESSION['nivel'] ?? 0);
        $uid   = (int)($_SESSION['usuario_id'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT ot.*,
                   t.pv_montante, t.pv_jusante, t.bacia, t.extensao, t.dn, t.rua, t.cidade, t.contrato, t.ramais,
                   u_imp.nome AS importado_nome
            FROM os_topografia ot
            JOIN trechos t ON t.id = ot.trecho_id
            JOIN usuarios u_imp ON u_imp.id = ot.importado_por
            WHERE ot.id = ?
        ");
        $stmt->execute([$id]);
        $os = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$os) {
            $_SESSION['flash_erro'] = 'OS não encontrada.';
            header('Location: ' . APP_BASE . '/topografia');
            exit;
        }

        if ($nivel === 8 && (int)$os['importado_por'] !== $uid) {
            $_SESSION['flash_erro'] = 'Acesso negado.';
            header('Location: ' . APP_BASE . '/topografia');
            exit;
        }

        $stmtEst = $pdo->prepare("SELECT * FROM os_topografia_estacas WHERE os_id = ? ORDER BY comp_acumulado");
        $stmtEst->execute([$id]);
        $estacas = $stmtEst->fetchAll(PDO::FETCH_ASSOC);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();

            $novaDecl = str_replace(',', '.', trim($_POST['declividade'] ?? ''));
            if (!is_numeric($novaDecl) || (float)$novaDecl < 0.0001 || (float)$novaDecl > 0.2) {
                $_SESSION['flash_erro'] = 'Declividade inválida (0.0001–0.2).';
                header('Location: ' . APP_BASE . '/topografia/' . $id . '/declividade');
                exit;
            }
            $novaDecl  = (float)$novaDecl;
            $declAtual = (float)$os['declividade'];

            $pdo->beginTransaction();
            try {
                $novoRevisao = (int)$os['revisao'] + 1;
                $novaFJ = (float)$os['cota_fundo_montante'] - max(array_column($estacas, 'comp_acumulado')) * $novaDecl;

                $pdo->prepare("
                    INSERT INTO os_topografia_revisoes
                        (os_id, revisao, declividade_de, declividade_para, cota_fj_de, cota_fj_para, alterado_por)
                    VALUES (?,?,?,?,?,?,?)
                ")->execute([$id, $novoRevisao, $declAtual, $novaDecl, (float)$os['cota_fundo_jusante'], round($novaFJ, 3), $uid]);

                $regua = (float)$os['regua']; $diamExt = (int)$os['diam_externo_esp'];
                $profValaSum = 0; $profValaCount = 0;
                $stmtUpdEst = $pdo->prepare("
                    UPDATE os_topografia_estacas
                    SET cota_rede_gi=?, cota_rede_gs=?, cota_gabarito=?, altura_gabarito=?, prof_vala=?
                    WHERE id=?
                ");

                foreach ($estacas as $e) {
                    $comp   = (float)$e['comp_acumulado'];
                    $gi     = $novaFJ + $comp * $novaDecl;
                    $gs     = $gi + ($diamExt / 1000.0);
                    $gab    = $gi + $regua;
                    $altGab = ($e['cota_auxiliar'] !== null) ? ($gab - (float)$e['cota_auxiliar']) : 0.0;
                    $pv     = ($e['cota_eixo'] !== null) ? ((float)$e['cota_eixo'] - $gi) : 0.0;
                    if ($e['cota_eixo'] !== null) { $profValaSum += $pv; $profValaCount++; }
                    $stmtUpdEst->execute([round($gi,3), round($gs,3), round($gab,3), round($altGab,3), round($pv,3), (int)$e['id']]);
                }

                $profMedia = $profValaCount > 0 ? round($profValaSum / $profValaCount, 3) : null;

                $pdo->prepare("
                    UPDATE os_topografia SET declividade=?, cota_fundo_jusante=?, prof_media=?,
                        revisao=?, status='aguardando_liberacao' WHERE id=?
                ")->execute([$novaDecl, round($novaFJ,3), $profMedia, $novoRevisao, $id]);

                if ($profMedia !== null)
                    $pdo->prepare("UPDATE trechos SET profundidade_media=? WHERE id=?")
                        ->execute([$profMedia, (int)$os['trecho_id']]);

                $arquivoOS = $this->gerarArquivoOS($id);
                $pdo->prepare("UPDATE os_topografia SET arquivo_os=? WHERE id=?")->execute([$arquivoOS, $id]);
                $pdo->commit();
            } catch (\Exception $e) {
                $pdo->rollBack();
                $_SESSION['flash_erro'] = 'Erro ao salvar declividade: ' . $e->getMessage();
                header('Location: ' . APP_BASE . '/topografia/' . $id . '/declividade');
                exit;
            }

            $_SESSION['flash_ok'] = 'Declividade atualizada. Nova revisão: ' . $novoRevisao . '.';
            header('Location: ' . APP_BASE . '/topografia');
            exit;
        }

        $prevDecl = null; $prevEstacas = []; $prevFJ = null; $prevProfMedia = null;
        if (!empty($_GET['prev_decl'])) {
            $pd = (float)str_replace(',', '.', $_GET['prev_decl']);
            if ($pd >= 0.0001 && $pd <= 0.2) {
                $prevDecl = $pd;
                $maxComp = max(array_column($estacas, 'comp_acumulado'));
                $prevFJ  = (float)$os['cota_fundo_montante'] - $maxComp * $prevDecl;
                $pSum = 0; $pCnt = 0;
                foreach ($estacas as $e) {
                    $comp = (float)$e['comp_acumulado'];
                    $gi   = $prevFJ + $comp * $prevDecl;
                    $gs   = $gi + ((int)$os['diam_externo_esp'] / 1000.0);
                    $gab  = $gi + (float)$os['regua'];
                    $altG = ($e['cota_auxiliar'] !== null) ? ($gab - (float)$e['cota_auxiliar']) : 0.0;
                    $pv   = ($e['cota_eixo'] !== null) ? ((float)$e['cota_eixo'] - $gi) : 0.0;
                    if ($e['cota_eixo'] !== null) { $pSum += $pv; $pCnt++; }
                    $prevEstacas[] = array_merge($e, ['new_gi'=>round($gi,3),'new_gs'=>round($gs,3),'new_gab'=>round($gab,3),'new_pv'=>round($pv,3),'new_altg'=>round($altG,3)]);
                }
                $prevProfMedia = $pCnt > 0 ? round($pSum / $pCnt, 3) : null;
            }
        }

        $layout    = 'topografo';
        $pageTitle = 'Editar Declividade';
        require __DIR__ . '/../views/topografia/editar_declividade.php';
    }

    /* =====================================================
       VER OS — GET
    ===================================================== */
    public function verOS(int $id)
    {
        auth_required_topografo();
        global $pdo;

        $nivel = (int)($_SESSION['nivel'] ?? 0);
        $uid   = (int)($_SESSION['usuario_id'] ?? 0);

        $stmt = $pdo->prepare("SELECT * FROM os_topografia WHERE id = ?");
        $stmt->execute([$id]);
        $os = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$os) { http_response_code(404); echo 'OS não encontrada.'; exit; }
        if ($nivel === 8 && (int)$os['importado_por'] !== $uid) { http_response_code(403); echo 'Acesso negado.'; exit; }

        if (empty($os['arquivo_os'])) {
            try {
                $arquivoOS = $this->gerarArquivoOS($id);
                $pdo->prepare("UPDATE os_topografia SET arquivo_os=? WHERE id=?")->execute([$arquivoOS, $id]);
                $os['arquivo_os'] = $arquivoOS;
            } catch (\Exception $e) { echo 'Erro ao gerar OS.'; exit; }
        }

        $filePath = __DIR__ . '/../../uploads/os/topo/' . basename($os['arquivo_os']);
        if (!file_exists($filePath)) {
            try {
                $arquivoOS = $this->gerarArquivoOS($id);
                $pdo->prepare("UPDATE os_topografia SET arquivo_os=? WHERE id=?")->execute([$arquivoOS, $id]);
                $filePath = __DIR__ . '/../../uploads/os/topo/' . basename($os['arquivo_os']);
            } catch (\Exception $e) { echo 'Arquivo OS não encontrado.'; exit; }
        }

        header('Content-Type: text/html; charset=utf-8');
        readfile($filePath);
        exit;
    }

    /* =====================================================
       GERAR ARQUIVO OS HTML (privado)
    ===================================================== */
    private function gerarArquivoOS(int $osId): string
    {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT ot.*,
                   t.pv_montante, t.pv_jusante, t.bacia, t.extensao, t.dn, t.rua, t.cidade, t.contrato, t.ramais,
                   u_imp.nome AS importado_nome, u_imp.tipo_usuario AS importado_tipo,
                   u_lib.nome AS liberado_nome, u_lib.tipo_usuario AS liberado_tipo
            FROM os_topografia ot
            JOIN trechos t ON t.id = ot.trecho_id
            JOIN usuarios u_imp ON u_imp.id = ot.importado_por
            LEFT JOIN usuarios u_lib ON u_lib.id = ot.liberado_por
            WHERE ot.id = ?
        ");
        $stmt->execute([$osId]);
        $os = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$os) throw new \RuntimeException("OS $osId não encontrada para geração.");

        $stmtEst = $pdo->prepare("SELECT * FROM os_topografia_estacas WHERE os_id = ? ORDER BY comp_acumulado");
        $stmtEst->execute([$osId]);
        $estacas = $stmtEst->fetchAll(PDO::FETCH_ASSOC);

        $dir = __DIR__ . '/../../uploads/os/topo';
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $revisao  = (int)$os['revisao'];
        $trechoId = (int)$os['trecho_id'];
        $fileName = 'OS-' . $trechoId . '-rev' . $revisao . '.html';
        $filePath = $dir . '/' . $fileName;

        $osNum       = str_pad($osId, 6, '0', STR_PAD_LEFT);
        $dataOS      = $os['data_os'] ? date('d/m/Y', strtotime($os['data_os'])) : '—';
        $geradoEm    = date('d/m/Y H:i');
        $statusLabel = $os['status'] === 'liberado' ? 'LIBERADO' : 'AGUARDANDO LIBERAÇÃO';
        $statusColor = $os['status'] === 'liberado' ? '#155724' : '#856404';
        $statusBg    = $os['status'] === 'liberado' ? '#d4edda' : '#fff3cd';

        $rowsHtml = '';
        foreach ($estacas as $e) {
            $rowsHtml .= '<tr>
                <td>' . htmlspecialchars($e['estaca']) . '</td>
                <td>' . number_format((float)$e['comp_acumulado'], 3, ',', '.') . '</td>
                <td>' . ($e['cota_auxiliar'] !== null ? number_format((float)$e['cota_auxiliar'], 3, ',', '.') : '—') . '</td>
                <td>' . ($e['cota_eixo'] !== null ? number_format((float)$e['cota_eixo'], 3, ',', '.') : '—') . '</td>
                <td>' . number_format((float)$e['cota_rede_gi'], 3, ',', '.') . '</td>
                <td>' . number_format((float)$e['prof_vala'], 3, ',', '.') . '</td>
                <td>' . number_format((float)$e['altura_gabarito'], 3, ',', '.') . '</td>
                <td>' . number_format((float)$e['cota_rede_gs'], 3, ',', '.') . '</td>
                <td>' . number_format((float)$e['cota_gabarito'], 3, ',', '.') . '</td>
            </tr>';
        }

        $liberadoBlock = '';
        if ($os['status'] === 'liberado' && !empty($os['liberado_nome'])) {
            $liberadoEm = $os['liberado_em'] ? date('d/m/Y H:i', strtotime($os['liberado_em'])) : '—';
            $liberadoBlock = '<tr><td style="font-weight:700">Planejador</td><td>' . htmlspecialchars($os['liberado_nome']) . '</td><td>' . htmlspecialchars($os['liberado_tipo'] ?? '') . '</td><td>' . $liberadoEm . '</td></tr>';
        }
        $importadoEm = $os['importado_em'] ? date('d/m/Y H:i', strtotime($os['importado_em'])) : '—';

        $html = '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>OS Topografia N° ' . $osNum . '</title>
<style>body{font-family:Arial,sans-serif;font-size:12px;color:#1a1a1a;margin:0;padding:20px;background:#fff;}
h1{font-size:16px;font-weight:800;text-align:center;text-transform:uppercase;letter-spacing:2px;margin:0 0 4px;}
.sub{text-align:center;font-size:11px;color:#555;margin-bottom:16px;}
.header-info{display:flex;gap:20px;justify-content:center;margin-bottom:18px;flex-wrap:wrap;}
.header-info div{background:#f4f4f4;border-radius:6px;padding:6px 14px;font-size:11px;}
.header-info div span{font-weight:700;font-size:13px;display:block;}
.rev-badge{background:#E0A53D;color:#fff;border-radius:4px;padding:2px 10px;font-weight:700;font-size:12px;}
table{width:100%;border-collapse:collapse;margin-bottom:16px;font-size:11px;}
th{background:#1A2D4F;color:#fff;padding:5px 8px;text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.5px;}
td{border-bottom:1px solid #e0e0e0;padding:5px 8px;}
tr:nth-child(even) td{background:#fafafa;}
.section-title{font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#1A2D4F;border-bottom:2px solid #1A2D4F;padding-bottom:4px;margin:16px 0 8px;}
.info-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:16px;}
.info-item{background:#f4f4f4;border-radius:6px;padding:6px 10px;}
.info-item .label{font-size:9px;text-transform:uppercase;letter-spacing:.8px;color:#666;font-weight:700;}
.info-item .val{font-size:12px;font-weight:700;color:#1a1a1a;margin-top:2px;}
.status-badge{display:inline-block;padding:4px 14px;border-radius:20px;font-weight:700;font-size:12px;background:' . $statusBg . ';color:' . $statusColor . ';}
.footer{border-top:1px solid #ddd;margin-top:20px;padding-top:10px;font-size:10px;color:#888;text-align:center;}
@media print{body{padding:10px;}}</style></head><body>
<h1>Ordem de Serviço para Gabarito</h1>
<p class="sub">Documento gerado pelo Painel de Controle ' . htmlspecialchars(APP_CLIENT) . '</p>
<div class="header-info">
  <div>N° OS<span>' . $osNum . '</span></div>
  <div>Data<span>' . $dataOS . '</span></div>
  <div>Revisão<span class="rev-badge">Rev. ' . $revisao . '</span></div>
  <div>Status<span><span class="status-badge">' . $statusLabel . '</span></span></div>
</div>
<div class="section-title">Identificação do Trecho</div>
<div class="info-grid">
  <div class="info-item"><div class="label">Contrato</div><div class="val">' . htmlspecialchars($os['contrato'] ?? '—') . '</div></div>
  <div class="info-item"><div class="label">PV Montante</div><div class="val">' . htmlspecialchars($os['pv_montante']) . '</div></div>
  <div class="info-item"><div class="label">PV Jusante</div><div class="val">' . htmlspecialchars($os['pv_jusante'] ?? '—') . '</div></div>
  <div class="info-item"><div class="label">Bacia</div><div class="val">' . htmlspecialchars($os['bacia'] ?? '—') . '</div></div>
  <div class="info-item"><div class="label">DN (mm)</div><div class="val">' . htmlspecialchars($os['dn'] ?? '—') . '</div></div>
  <div class="info-item"><div class="label">Diâm. Externo + Esp. (mm)</div><div class="val">' . (int)$os['diam_externo_esp'] . '</div></div>
  <div class="info-item"><div class="label">Régua (m)</div><div class="val">' . number_format((float)$os['regua'], 2, ',', '.') . '</div></div>
  <div class="info-item"><div class="label">Declividade (m/m)</div><div class="val">' . number_format((float)$os['declividade'], 6, ',', '.') . '</div></div>
  <div class="info-item"><div class="label">Nº Ramais</div><div class="val">' . (int)$os['ramais'] . '</div></div>
  <div class="info-item"><div class="label">Prof. Média (m)</div><div class="val">' . ($os['prof_media'] !== null ? number_format((float)$os['prof_media'], 3, ',', '.') : '—') . '</div></div>
  <div class="info-item"><div class="label">Cota Fundo PV Jusante</div><div class="val">' . number_format((float)$os['cota_fundo_jusante'], 3, ',', '.') . '</div></div>
  <div class="info-item"><div class="label">Rua</div><div class="val">' . htmlspecialchars($os['rua'] ?? '—') . '</div></div>
</div>
<div class="section-title">Estacas</div>
<table><thead><tr><th>Estaca</th><th>Comp. (m)</th><th>Cota Auxiliar</th><th>Cota Eixo</th><th>Cota Rede GI</th><th>Prof. Vala</th><th>Alt. Gabarito</th><th>Cota Rede GS</th><th>Cota Gabarito</th></tr></thead><tbody>' . $rowsHtml . '</tbody></table>
<div class="section-title">Responsáveis</div>
<table><thead><tr><th>Função</th><th>Nome</th><th>Perfil</th><th>Data/Hora</th></tr></thead><tbody>
<tr><td style="font-weight:700">Topógrafo</td><td>' . htmlspecialchars($os['importado_nome']) . '</td><td>' . htmlspecialchars($os['importado_tipo'] ?? '') . '</td><td>' . $importadoEm . '</td></tr>
' . $liberadoBlock . '</tbody></table>
' . (!empty($os['observacoes']) ? '<div class="section-title">Observações</div><p style="font-size:11px;color:#444;">' . nl2br(htmlspecialchars($os['observacoes'])) . '</p>' : '') . '
<div class="footer">Documento gerado automaticamente &mdash; ' . $geradoEm . '</div>
</body></html>';

        file_put_contents($filePath, $html);
        return $fileName;
    }
}
