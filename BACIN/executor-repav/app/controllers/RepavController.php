<?php
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

class RepavController {

    private PDO $db;
    private string $uploadsDir;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
        $this->uploadsDir = dirname(__DIR__, 2) . '/uploads';
    }

    // ── Home ─────────────────────────────────────────────────
    public function home(): void {
        auth_required_repav();
        csrf_token_repav();

        $autorId  = (int)$_SESSION['usuario_id'];
        $equipeId = $this->equipeDoAutor($autorId);

        $caminhamento  = null;
        $trechoAtual   = null;
        $filaTrechos   = [];
        $pavimentos    = [];
        $diarioHoje    = null;

        if ($equipeId) {
            $stmt = $this->db->prepare("
                SELECT id, data_execucao, status
                FROM caminhamentos_repav
                WHERE equipe_id = ?
                  AND status IN ('publicado','execucao')
                  AND data_execucao >= CURDATE()
                ORDER BY data_execucao ASC
                LIMIT 1
            ");
            $stmt->execute([$equipeId]);
            $caminhamento = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($caminhamento) {
                $stmtFila = $this->db->prepare("
                    SELECT ct.id AS ct_id, ct.sequencia AS ordem, ct.status AS ct_status,
                           t.id, t.pv_montante, t.pv_jusante, t.extensao, t.rua, t.bacia, t.contrato
                    FROM caminhamentos_repav_trechos ct
                    JOIN trechos t ON t.id = ct.trecho_id
                    WHERE ct.caminhamento_id = ?
                    ORDER BY ct.sequencia ASC
                ");
                $stmtFila->execute([$caminhamento['id']]);
                $filaTrechos = $stmtFila->fetchAll(PDO::FETCH_ASSOC);

                foreach ($filaTrechos as $tc) {
                    if ($tc['ct_status'] !== 'concluido') {
                        $trechoAtual = $tc;
                        break;
                    }
                }

                if ($trechoAtual) {
                    $stmtPav = $this->db->prepare("
                        SELECT tipo_pavimento, espessura_cm
                        FROM caminhamentos_repav_pavimentos
                        WHERE caminhamento_trecho_id = ?
                        ORDER BY id
                    ");
                    $stmtPav->execute([$trechoAtual['ct_id']]);
                    $pavimentos = $stmtPav->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        }

        if ($equipeId && $trechoAtual) {
            $stmt = $this->db->prepare("
                SELECT id, status, step_atual, area_total_m2, volume_asf_m3, versao
                FROM diarios_repav
                WHERE equipe_id = ? AND trecho_id = ? AND data = CURDATE()
                ORDER BY versao DESC LIMIT 1
            ");
            $stmt->execute([$equipeId, $trechoAtual['id']]);
            $diarioHoje = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        require __DIR__ . '/../views/home.php';
    }

    // ── Novo diário ────────────────────────────────────────────
    public function novo(): void {
        auth_required_repav();
        csrf_verify_repav();

        $autorId  = (int)$_SESSION['usuario_id'];
        $equipeId = $this->equipeDoAutor($autorId);
        $trechoId = (int)($_POST['trecho_id'] ?? 0);

        if (!$equipeId || !$trechoId) {
            http_response_code(400); echo "Dados inválidos."; return;
        }

        $stmt = $this->db->prepare("
            SELECT id, status, versao FROM diarios_repav
            WHERE equipe_id = ? AND trecho_id = ? AND data = CURDATE()
            ORDER BY versao DESC LIMIT 1
        ");
        $stmt->execute([$equipeId, $trechoId]);
        $existente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existente && $existente['status'] === 'rascunho') {
            header('Location: ' . REPAV_BASE . '/diario/' . $existente['id']);
            exit;
        }

        $versao = $existente ? (int)$existente['versao'] + 1 : 1;

        $ins = $this->db->prepare("
            INSERT INTO diarios_repav
                (equipe_id, trecho_id, data, autor_id, status, versao, step_atual)
            VALUES (?, ?, CURDATE(), ?, 'rascunho', ?, 1)
        ");
        $ins->execute([$equipeId, $trechoId, $autorId, $versao]);
        $diarioId = (int)$this->db->lastInsertId();

        header('Location: ' . REPAV_BASE . '/diario/' . $diarioId);
        exit;
    }

    // ── Ver / preencher diário ─────────────────────────────────
    public function ver(int $id): void {
        auth_required_repav();

        $diario = $this->carregarDiario($id);
        if (!$diario) { http_response_code(404); echo "Diário não encontrado."; return; }
        $this->verificarPermissao($diario);

        $trecho = $this->fetch1("SELECT * FROM trechos WHERE id = ?", [$diario['trecho_id']]);
        $funcionarios = $this->listar("
            SELECT f.id, f.nome, f.funcao
            FROM equipe_funcionarios ef
            JOIN funcionarios f ON f.id = ef.funcionario_id
            WHERE ef.equipe_id = ? AND ef.ativo = 1
            ORDER BY f.nome
        ", [$diario['equipe_id']]);

        // Pavimentos do caminhamento para este trecho
        $pavimentos = $this->listar("
            SELECT crp.tipo_pavimento, crp.espessura_cm
            FROM caminhamentos_repav_pavimentos crp
            JOIN caminhamentos_repav_trechos crt ON crt.id = crp.caminhamento_trecho_id
            JOIN caminhamentos_repav cr ON cr.id = crt.caminhamento_id
            WHERE crt.trecho_id = ? AND cr.equipe_id = ?
            ORDER BY crp.id
        ", [$diario['trecho_id'], $diario['equipe_id']]);

        $equipsPesados = $this->listar("
            SELECT ep.id, ep.tipo, ep.modelo, ep.placa
            FROM equipes_equipamentos_pesados eep
            JOIN equipamentos_pesados ep ON ep.id = eep.equipamento_id
            WHERE eep.equipe_id = ?
        ", [$diario['equipe_id']]);

        $equipsLeves = $this->listar("
            SELECT el.id, el.tipo, el.modelo
            FROM equipes_equipamentos_leves eel
            JOIN equipamentos_leves el ON el.id = eel.equipamento_id
            WHERE eel.equipe_id = ?
        ", [$diario['equipe_id']]);

        $presencas    = $this->listar("SELECT rp.*, f.nome, f.funcao FROM diario_repav_presencas rp JOIN funcionarios f ON f.id = rp.funcionario_id WHERE rp.diario_id = ?", [$id]);
        $equipamentos = $this->listar("SELECT * FROM diario_repav_equipamentos WHERE diario_id = ? ORDER BY id", [$id]);
        $cargas       = $this->listar("SELECT * FROM diario_repav_cargas WHERE diario_id = ? ORDER BY sequencia", [$id]);
        $areas        = $this->listar("SELECT * FROM diario_repav_areas WHERE diario_id = ? ORDER BY tipo_pavimento, sequencia", [$id]);
        $fotos        = $this->listar("SELECT * FROM diario_repav_fotos WHERE diario_id = ? ORDER BY step_num, id", [$id]);

        // Group fotos by step
        $fotosPorStep = [];
        foreach ($fotos as $f) $fotosPorStep[(int)$f['step_num']][] = $f;

        // Group presencas by funcionario
        $presencaMap = [];
        foreach ($presencas as $p) $presencaMap[$p['funcionario_id']] = $p;

        // Group areas by tipo_pavimento
        $areasPorTipo = [];
        foreach ($areas as $a) $areasPorTipo[$a['tipo_pavimento']][] = $a;

        require __DIR__ . '/../views/diario/preencher.php';
    }

    // ── Salvar passo ───────────────────────────────────────────
    public function salvar(): void {
        auth_required_repav();
        csrf_verify_repav();

        header('Content-Type: application/json');
        $diarioId = (int)($_POST['diario_id'] ?? 0);
        $step     = (int)($_POST['step'] ?? 0);
        $diario   = $this->carregarDiario($diarioId);

        if (!$diario || $diario['status'] === 'enviado') {
            echo json_encode(['ok' => false, 'msg' => 'Diário bloqueado.']); return;
        }
        $this->verificarPermissao($diario);

        $ok = $this->processarStep($diarioId, $step, $diario);

        if ($ok && $step > (int)$diario['step_atual']) {
            $this->db->prepare("UPDATE diarios_repav SET step_atual = ? WHERE id = ?")
                     ->execute([$step, $diarioId]);
        }
        echo json_encode(['ok' => $ok]);
    }

    // ── Upload de foto ─────────────────────────────────────────
    public function uploadFoto(): void {
        auth_required_repav();
        csrf_verify_repav();

        header('Content-Type: application/json');
        $diarioId = (int)($_POST['diario_id'] ?? 0);
        $step     = (int)($_POST['step'] ?? 0);
        $lat      = preg_replace('/[^0-9.\-]/', '', $_POST['lat'] ?? '');
        $lng      = preg_replace('/[^0-9.\-]/', '', $_POST['lng'] ?? '');
        $ts       = preg_replace('/[^0-9:\-T ]/', '', $_POST['ts'] ?? '');

        $diario = $this->carregarDiario($diarioId);
        if (!$diario || $diario['status'] === 'enviado') {
            echo json_encode(['ok' => false, 'msg' => 'Diário inválido.']); return;
        }
        $this->verificarPermissao($diario);

        if (empty($_FILES['foto']['tmp_name'])) {
            echo json_encode(['ok' => false, 'msg' => 'Arquivo ausente.']); return;
        }

        $info = $_FILES['foto'];
        if ($info['size'] > 8 * 1024 * 1024) {
            echo json_encode(['ok' => false, 'msg' => 'Arquivo muito grande (máx 8 MB).']); return;
        }

        $ext = strtolower(pathinfo($info['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
            echo json_encode(['ok' => false, 'msg' => 'Formato inválido.']); return;
        }

        $dir = $this->uploadsDir . '/repav';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $dirThumb = $this->uploadsDir . '/repav/thumbs';
        if (!is_dir($dirThumb)) mkdir($dirThumb, 0755, true);

        $nome  = date('Ymd_His') . '_' . $diarioId . '_s' . $step . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destino = $dir . '/' . $nome;

        if (!move_uploaded_file($info['tmp_name'], $destino)) {
            echo json_encode(['ok' => false, 'msg' => 'Falha ao salvar arquivo.']); return;
        }

        // Thumbnail
        $thumb = null;
        if (function_exists('imagecreatefromjpeg')) {
            $thumb = $this->gerarThumb($destino, $dirThumb . '/' . $nome, 400);
        }

        $ins = $this->db->prepare("
            INSERT INTO diario_repav_fotos (diario_id, step_num, filename, thumb, lat, lng, captured_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([$diarioId, $step, $nome, $thumb ? $nome : null,
                       $lat ?: null, $lng ?: null, $ts ?: null]);

        echo json_encode(['ok' => true, 'filename' => $nome, 'thumb' => $thumb ? $nome : null]);
    }

    // ── Adicionar carga de asfalto ─────────────────────────────
    public function addCarga(): void {
        auth_required_repav();
        csrf_verify_repav();

        header('Content-Type: application/json');
        $diarioId = (int)($_POST['diario_id'] ?? 0);
        $diario   = $this->carregarDiario($diarioId);
        if (!$diario || $diario['status'] === 'enviado') {
            echo json_encode(['ok' => false]); return;
        }
        $this->verificarPermissao($diario);

        $seq = (int)$this->db->query("SELECT COALESCE(MAX(sequencia),0)+1 FROM diario_repav_cargas WHERE diario_id = $diarioId")->fetchColumn();
        $ins = $this->db->prepare("INSERT INTO diario_repav_cargas (diario_id, sequencia) VALUES (?, ?)");
        $ins->execute([$diarioId, $seq]);
        echo json_encode(['ok' => true, 'id' => (int)$this->db->lastInsertId(), 'seq' => $seq]);
    }

    // ── Adicionar linha de área ────────────────────────────────
    public function addArea(): void {
        auth_required_repav();
        csrf_verify_repav();

        header('Content-Type: application/json');
        $diarioId = (int)($_POST['diario_id'] ?? 0);
        $tipo     = substr(trim($_POST['tipo'] ?? ''), 0, 80);
        $diario   = $this->carregarDiario($diarioId);
        if (!$diario || !$tipo) { echo json_encode(['ok' => false]); return; }
        $this->verificarPermissao($diario);

        $seq = (int)$this->db->prepare("SELECT COALESCE(MAX(sequencia),0)+1 FROM diario_repav_areas WHERE diario_id = ? AND tipo_pavimento = ?")->execute([$diarioId, $tipo]) ? 1 : 1;
        $stmtSeq = $this->db->prepare("SELECT COALESCE(MAX(sequencia),0)+1 FROM diario_repav_areas WHERE diario_id = ? AND tipo_pavimento = ?");
        $stmtSeq->execute([$diarioId, $tipo]);
        $seq = (int)$stmtSeq->fetchColumn();

        $ins = $this->db->prepare("INSERT INTO diario_repav_areas (diario_id, tipo_pavimento, sequencia, base_m, largura_m) VALUES (?, ?, ?, 0, 0)");
        $ins->execute([$diarioId, $tipo, $seq]);
        echo json_encode(['ok' => true, 'id' => (int)$this->db->lastInsertId(), 'seq' => $seq]);
    }

    // ── Encerrar & enviar ─────────────────────────────────────
    public function encerrar(int $id): void {
        auth_required_repav();
        csrf_verify_repav();

        $diario = $this->carregarDiario($id);
        if (!$diario) { http_response_code(404); return; }
        $this->verificarPermissao($diario);

        if ($diario['status'] !== 'rascunho') {
            header('Location: ' . REPAV_BASE . '/'); exit;
        }

        $this->db->beginTransaction();
        try {
            // Calcular totais
            $stmtArea = $this->db->prepare("
                SELECT tipo_pavimento,
                       COALESCE(SUM(base_m * largura_m), 0) AS area,
                       COALESCE(SUM(CASE WHEN espessura_m IS NOT NULL THEN base_m * largura_m * espessura_m ELSE 0 END), 0) AS vol
                FROM diario_repav_areas WHERE diario_id = ?
                GROUP BY tipo_pavimento
            ");
            $stmtArea->execute([$id]);
            $linhas = $stmtArea->fetchAll(PDO::FETCH_ASSOC);

            $areaTotal = 0; $volAsf = 0;
            foreach ($linhas as $l) {
                $areaTotal += (float)$l['area'];
                $volAsf    += (float)$l['vol'];
            }

            // Atualizar areas com valores calculados
            foreach ($linhas as $l) {
                $this->db->prepare("
                    UPDATE diario_repav_areas
                    SET area_m2 = base_m * largura_m,
                        volume_m3 = IF(espessura_m IS NOT NULL, base_m * largura_m * espessura_m, NULL)
                    WHERE diario_id = ? AND tipo_pavimento = ?
                ")->execute([$id, $l['tipo_pavimento']]);
            }

            // Atualizar cabeçalho do diário
            $this->db->prepare("
                UPDATE diarios_repav
                SET status = 'enviado', step_atual = 19,
                    area_total_m2 = ?, volume_asf_m3 = ?
                WHERE id = ?
            ")->execute([$areaTotal, $volAsf, $id]);

            // Equipamentos com problema → manutenção
            $equips = $this->listar(
                "SELECT * FROM diario_repav_equipamentos WHERE diario_id = ? AND status = 'problema'",
                [$id]
            );
            foreach ($equips as $eq) {
                if (!empty($eq['equipamento_id']) && !empty($eq['tipo'])) {
                    $tabela = $eq['tipo'] === 'pesado' ? 'equipamentos_pesados' : 'equipamentos_leves';
                    $this->db->prepare("UPDATE {$tabela} SET status_manutencao = 'manutencao' WHERE id = ?")
                             ->execute([(int)$eq['equipamento_id']]);
                }
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo "Erro ao encerrar: " . $e->getMessage();
            return;
        }

        header('Location: ' . REPAV_BASE . '/');
        exit;
    }

    // ── Sync offline ───────────────────────────────────────────
    public function sync(): void {
        auth_required_repav();
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'synced' => 0]);
    }

    // ─────────────────────────────────────────────────────────
    // Processamento de cada step (AJAX)
    // ─────────────────────────────────────────────────────────
    private function processarStep(int $diarioId, int $step, array $diario): bool {
        try {
            switch ($step) {
                case 1: // Presença
                    $todos = $_POST['todos'] ?? '';
                    if ($todos === 's') {
                        // Todos presentes — registra presença para toda equipe
                        $funcs = $this->listar("SELECT funcionario_id FROM equipe_funcionarios WHERE equipe_id = ? AND ativo = 1", [$diario['equipe_id']]);
                        $this->db->prepare("DELETE FROM diario_repav_presencas WHERE diario_id = ?")->execute([$diarioId]);
                        $ins = $this->db->prepare("INSERT INTO diario_repav_presencas (diario_id, funcionario_id, status) VALUES (?, ?, 'presente')");
                        foreach ($funcs as $f) $ins->execute([$diarioId, $f['funcionario_id']]);
                    } elseif ($todos === 'n') {
                        $ausentes = array_map('intval', $_POST['ausentes'] ?? []);
                        $funcs = $this->listar("SELECT funcionario_id FROM equipe_funcionarios WHERE equipe_id = ? AND ativo = 1", [$diario['equipe_id']]);
                        $this->db->prepare("DELETE FROM diario_repav_presencas WHERE diario_id = ?")->execute([$diarioId]);
                        $ins = $this->db->prepare("INSERT INTO diario_repav_presencas (diario_id, funcionario_id, status) VALUES (?, ?, ?)");
                        foreach ($funcs as $f) {
                            $status = in_array((int)$f['funcionario_id'], $ausentes) ? 'ausente' : 'presente';
                            $ins->execute([$diarioId, $f['funcionario_id'], $status]);
                        }
                    }
                    return true;

                case 2: // Atrasos / saídas
                    $atrasados = array_map('intval', $_POST['atrasou'] ?? []);
                    $saiuCedo  = array_map('intval', $_POST['saiu_cedo'] ?? []);
                    foreach (array_unique(array_merge($atrasados, $saiuCedo)) as $fid) {
                        if (!$fid) continue;
                        $status = in_array($fid, $saiuCedo) ? 'saiu_cedo' : 'atrasou';
                        $this->db->prepare("
                            UPDATE diario_repav_presencas SET status = ? WHERE diario_id = ? AND funcionario_id = ?
                        ")->execute([$status, $diarioId, $fid]);
                    }
                    return true;

                case 3: // Materiais
                    $ok  = in_array($_POST['mat_ok'] ?? '', ['1','0']) ? (int)$_POST['mat_ok'] : null;
                    $obs = substr(trim($_POST['mat_obs'] ?? ''), 0, 500);
                    $this->db->prepare("UPDATE diarios_repav SET mat_ok = ?, mat_obs = ? WHERE id = ?")
                             ->execute([$ok, $obs ?: null, $diarioId]);
                    return true;

                case 6: // Equipamentos
                    $ids     = $_POST['equip_id']     ?? [];
                    $tipos   = $_POST['equip_tipo']   ?? [];
                    $status  = $_POST['equip_status'] ?? [];
                    $this->db->prepare("DELETE FROM diario_repav_equipamentos WHERE diario_id = ?")->execute([$diarioId]);
                    $ins = $this->db->prepare("INSERT INTO diario_repav_equipamentos (diario_id, equipamento_id, tipo, nome, status) VALUES (?, ?, ?, ?, ?)");
                    foreach ($ids as $i => $eid) {
                        if (!$eid) continue;
                        $ins->execute([$diarioId, (int)$eid, $tipos[$i] ?? 'pesado', '', $status[$i] ?? 'ok']);
                    }
                    return true;

                case 10: // Cargas de asfalto
                    $ids     = $_POST['carga_id']  ?? [];
                    $nfs     = $_POST['carga_nf']  ?? [];
                    $massas  = $_POST['carga_mass'] ?? [];
                    $upd = $this->db->prepare("UPDATE diario_repav_cargas SET numero_nf = ?, massa_t = ? WHERE id = ? AND diario_id = ?");
                    foreach ($ids as $i => $cid) {
                        if (!$cid) continue;
                        $upd->execute([
                            substr(trim($nfs[$i] ?? ''), 0, 50) ?: null,
                            is_numeric($massas[$i]) ? (float)$massas[$i] : null,
                            (int)$cid, $diarioId
                        ]);
                    }
                    return true;

                case 15: // Dimensões do asfalto
                case 16: // Outros pavimentos
                    $ids     = $_POST['area_id']    ?? [];
                    $bases   = $_POST['area_base']  ?? [];
                    $largs   = $_POST['area_larg']  ?? [];
                    $esps    = $_POST['area_esp']   ?? [];
                    $upd = $this->db->prepare("UPDATE diario_repav_areas SET base_m = ?, largura_m = ?, espessura_m = ? WHERE id = ? AND diario_id = ?");
                    foreach ($ids as $i => $aid) {
                        if (!$aid) continue;
                        $upd->execute([
                            is_numeric($bases[$i]) ? (float)$bases[$i] : 0,
                            is_numeric($largs[$i]) ? (float)$largs[$i] : 0,
                            is_numeric($esps[$i])  ? (float)$esps[$i]  : null,
                            (int)$aid, $diarioId
                        ]);
                    }
                    return true;

                case 19: // Finalização
                    $obs = substr(trim($_POST['obs_final'] ?? ''), 0, 1000);
                    $this->db->prepare("UPDATE diarios_repav SET obs_final = ? WHERE id = ?")
                             ->execute([$obs ?: null, $diarioId]);
                    return true;

                default:
                    // Passos de foto only — marcados via uploadFoto
                    return true;
            }
        } catch (\PDOException $e) {
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────
    private function equipeDoAutor(int $autorId): ?int {
        $stmt = $this->db->prepare("
            SELECT id FROM equipes
            WHERE responsavel_id = ? AND ativo = 1
            ORDER BY id ASC LIMIT 1
        ");
        $stmt->execute([$autorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id'] : null;
    }

    private function carregarDiario(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM diarios_repav WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function verificarPermissao(array $diario): void {
        $autorId  = (int)$_SESSION['usuario_id'];
        $equipeId = $this->equipeDoAutor($autorId);
        if ((int)$diario['equipe_id'] !== $equipeId && (int)$diario['autor_id'] !== $autorId) {
            http_response_code(403); echo "Acesso negado."; exit;
        }
    }

    private function listar(string $sql, array $params = []): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fetch1(string $sql, array $params = []): ?array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function gerarThumb(string $src, string $dest, int $maxW): bool {
        $img = null;
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        if ($ext === 'jpg' || $ext === 'jpeg') $img = @imagecreatefromjpeg($src);
        elseif ($ext === 'png') $img = @imagecreatefrompng($src);
        elseif ($ext === 'webp') $img = @imagecreatefromwebp($src);
        if (!$img) return false;

        $w = imagesx($img); $h = imagesy($img);
        if ($w <= $maxW) { imagedestroy($img); copy($src, $dest); return true; }
        $ratio = $maxW / $w;
        $newH  = (int)round($h * $ratio);
        $thumb = imagecreatetruecolor($maxW, $newH);
        imagecopyresampled($thumb, $img, 0, 0, 0, 0, $maxW, $newH, $w, $h);
        $ok = imagejpeg($thumb, $dest, 82);
        imagedestroy($img); imagedestroy($thumb);
        return $ok;
    }
}
