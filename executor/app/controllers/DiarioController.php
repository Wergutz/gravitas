<?php
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

class DiarioController {

    private PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    // --------------------------------------------------------
    // Home — programação do dia para a equipe do executor logado
    // --------------------------------------------------------
    public function home(): void {
        auth_required_executor();

        $autorId  = (int)$_SESSION['usuario_id'];
        $equipeId = $this->equipeDoAutor($autorId);

        // Caminhamento publicado mais próximo da equipe (data >= hoje)
        $caminhamento = null;
        $trechoAtual  = null;
        $osPdf        = null;
        $materiais    = [];
        $filaTrechos  = [];

        if ($equipeId) {
            $stmt = $this->db->prepare("
                SELECT c.id, c.data_execucao, c.status
                FROM caminhamentos c
                WHERE c.equipe_id = ?
                  AND c.status IN ('publicado','execucao')
                  AND c.data_execucao >= CURDATE()
                ORDER BY c.data_execucao ASC
                LIMIT 1
            ");
            $stmt->execute([$equipeId]);
            $caminhamento = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($caminhamento) {
                // Fila completa de trechos do caminhamento
                $stmtFila = $this->db->prepare("
                    SELECT ct.ordem, ct.ct_status,
                           t.id, t.pv_montante, t.pv_jusante, t.extensao,
                           t.rua, t.bacia, t.dn, t.contrato
                    FROM caminhamento_trechos ct
                    JOIN trechos t ON t.id = ct.trecho_id
                    WHERE ct.caminhamento_id = ?
                    ORDER BY ct.ordem ASC
                ");
                $stmtFila->execute([$caminhamento['id']]);
                $filaTrechos = $stmtFila->fetchAll(PDO::FETCH_ASSOC);

                // Primeiro trecho ainda não concluído = trecho atual
                foreach ($filaTrechos as $tc) {
                    if ($tc['ct_status'] !== 'concluido') {
                        $trechoAtual = $tc;
                        break;
                    }
                }

                // OS PDF do trecho atual
                if ($trechoAtual) {
                    $stmtOs = $this->db->prepare("
                        SELECT arquivo_pdf, versao, topografo, data_os
                        FROM ordens_servico
                        WHERE trecho_id = ? AND ativa = 1
                        LIMIT 1
                    ");
                    $stmtOs->execute([$trechoAtual['id']]);
                    $osPdf = $stmtOs->fetch(PDO::FETCH_ASSOC);

                    // Materiais alocados ao trecho
                    $stmtMat = $this->db->prepare("
                        SELECT mc.nome, mc.unidade, tm.quantidade,
                               COALESCE(me.quantidade_fisica, 0)    AS estoque_fisico,
                               COALESCE(me.quantidade_reservada, 0) AS estoque_reservado
                        FROM trecho_materiais tm
                        JOIN materiais_catalogo mc ON mc.id = tm.material_id
                        LEFT JOIN materiais_estoque me ON me.material_id = tm.material_id
                        WHERE tm.trecho_id = ?
                        ORDER BY mc.nome
                    ");
                    $stmtMat->execute([$trechoAtual['id']]);
                    $materiais = $stmtMat->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        }

        // Diário de hoje (se já iniciado)
        $diarioHoje = null;
        if ($equipeId && $trechoAtual) {
            $stmt3 = $this->db->prepare("
                SELECT id, status, step_atual, versao
                FROM diarios_execucao
                WHERE equipe_id = ? AND trecho_id = ? AND data = CURDATE()
                ORDER BY versao DESC
                LIMIT 1
            ");
            $stmt3->execute([$equipeId, $trechoAtual['id']]);
            $diarioHoje = $stmt3->fetch(PDO::FETCH_ASSOC);
        }

        // Base URL do painel para acessar OS PDFs (mesmo banco, painel gerencia os uploads)
        $painelBase = '/principal/painel';

        require __DIR__ . '/../views/home.php';
    }

    // --------------------------------------------------------
    // Novo diário — cria rascunho e redireciona para o passo 1
    // --------------------------------------------------------
    public function novo(): void {
        auth_required_executor();
        csrf_verify_executor();

        $autorId  = (int)$_SESSION['usuario_id'];
        $equipeId = $this->equipeDoAutor($autorId);
        $trechoId = (int)($_POST['trecho_id'] ?? 0);

        if (!$equipeId || !$trechoId) {
            http_response_code(400);
            echo "Dados inválidos.";
            return;
        }

        // Evita duplicata (mesmo equipe/trecho/dia na última versão)
        $stmt = $this->db->prepare("
            SELECT id, status FROM diarios_execucao
            WHERE equipe_id = ? AND trecho_id = ? AND data = CURDATE()
            ORDER BY versao DESC LIMIT 1
        ");
        $stmt->execute([$equipeId, $trechoId]);
        $existente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existente && $existente['status'] !== 'enviado') {
            // Continua o rascunho existente
            header('Location: ' . EXECUTOR_BASE . '/diario/' . $existente['id']);
            exit;
        }

        $versao = $existente ? (int)$existente['versao'] + 1 : 1;

        $ins = $this->db->prepare("
            INSERT INTO diarios_execucao
                (equipe_id, trecho_id, data, autor_id, status, versao, step_atual)
            VALUES (?, ?, CURDATE(), ?, 'rascunho', ?, 1)
        ");
        $ins->execute([$equipeId, $trechoId, $autorId, $versao]);
        $diarioId = (int)$this->db->lastInsertId();

        header('Location: ' . EXECUTOR_BASE . '/diario/' . $diarioId);
        exit;
    }

    // --------------------------------------------------------
    // Ver/preencher diário (21 passos)
    // --------------------------------------------------------
    public function ver(int $id): void {
        auth_required_executor();

        $diario = $this->carregarDiario($id);
        if (!$diario) { http_response_code(404); echo "Diário não encontrado."; return; }

        $this->verificarPermissao($diario);

        // Dados complementares
        $presencas      = $this->listar("SELECT dp.*, f.nome FROM diario_presencas dp JOIN funcionarios f ON f.id = dp.funcionario_id WHERE dp.diario_id = ?", [$id]);
        $interferencias = $this->listar("SELECT * FROM diario_interferencias WHERE diario_id = ?", [$id]);
        $reaterros      = $this->listar("SELECT * FROM diario_reaterros WHERE diario_id = ?", [$id]);
        $ramais         = $this->listar("SELECT * FROM diario_ramais WHERE diario_id = ?", [$id]);
        $cargas         = $this->listar("SELECT * FROM diario_cargas WHERE diario_id = ? ORDER BY tipo, numero", [$id]);
        $pontoes        = $this->listar("SELECT * FROM diario_pontoes WHERE diario_id = ?", [$id]);
        $equipamentos   = $this->listar("SELECT de.*, ep.modelo AS modelo_pesado, ep.tipo AS tipo_pesado, ep.placa, el.modelo AS modelo_leve, el.tipo AS tipo_leve FROM diario_equipamentos de LEFT JOIN equipamentos_pesados ep ON de.tipo='pesado' AND ep.id=de.equipamento_id LEFT JOIN equipamentos_leves el ON de.tipo='leve' AND el.id=de.equipamento_id WHERE de.diario_id=?", [$id]);
        $gps            = $this->db->prepare("SELECT * FROM diario_gps WHERE diario_id = ?");
        $gps->execute([$id]);
        $gps = $gps->fetch(PDO::FETCH_ASSOC);

        $fotos          = $this->listar("SELECT * FROM diario_fotos WHERE diario_id = ? ORDER BY step_num, id", [$id]);

        // Equipe e funcionários para o passo 1
        $funcionariosEquipe = $this->listar("
            SELECT f.id, f.nome, f.funcao
            FROM equipe_funcionarios ef
            JOIN funcionarios f ON f.id = ef.funcionario_id
            WHERE ef.equipe_id = ? AND ef.ativo = 1
            ORDER BY f.nome
        ", [$diario['equipe_id']]);

        // Equipamentos da equipe (passos 6)
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

        $trecho = $this->db->prepare("SELECT * FROM trechos WHERE id = ?");
        $trecho->execute([$diario['trecho_id']]);
        $trecho = $trecho->fetch(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/diario/preencher.php';
    }

    // --------------------------------------------------------
    // Salvar passo (AJAX POST)
    // --------------------------------------------------------
    public function salvar(): void {
        auth_required_executor();
        csrf_verify_executor();

        $diarioId = (int)($_POST['diario_id'] ?? 0);
        $step     = (int)($_POST['step'] ?? 0);
        $diario   = $this->carregarDiario($diarioId);

        if (!$diario || $diario['status'] === 'enviado') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'Diário bloqueado.']);
            return;
        }

        $this->verificarPermissao($diario);
        $ok = $this->processarStep($diarioId, $step, $diario);

        // Atualiza step_atual se avançou
        if ($ok && $step > $diario['step_atual']) {
            $this->db->prepare("UPDATE diarios_execucao SET step_atual = ? WHERE id = ?")
                     ->execute([$step, $diarioId]);
        }

        header('Content-Type: application/json');
        echo json_encode(['ok' => $ok]);
    }

    // --------------------------------------------------------
    // Encerrar & enviar diário
    // --------------------------------------------------------
    public function encerrar(int $id): void {
        auth_required_executor();
        csrf_verify_executor();

        $diario = $this->carregarDiario($id);
        if (!$diario) { http_response_code(404); return; }
        $this->verificarPermissao($diario);

        $this->db->prepare("UPDATE diarios_execucao SET status = 'enviado', step_atual = 21 WHERE id = ?")
                 ->execute([$id]);

        header('Location: ' . EXECUTOR_BASE . '/');
        exit;
    }

    // --------------------------------------------------------
    // Upload de foto (AJAX)
    // --------------------------------------------------------
    public function uploadFoto(): void {
        auth_required_executor();
        csrf_verify_executor();

        $diarioId = (int)($_POST['diario_id'] ?? 0);
        $step     = (int)($_POST['step'] ?? 0);
        $lat      = $_POST['lat'] ?? null;
        $lng      = $_POST['lng'] ?? null;
        $tipo     = substr($_POST['tipo'] ?? '', 0, 50);

        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['ok' => false, 'msg' => 'Erro no upload.']);
            return;
        }

        $uploadDir   = __DIR__ . '/../../../uploads/diario/' . $diarioId . '/';
        $thumbDir    = $uploadDir . 'thumbs/';
        if (!is_dir($uploadDir))  mkdir($uploadDir, 0755, true);
        if (!is_dir($thumbDir))   mkdir($thumbDir,  0755, true);

        $ext      = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $base     = uniqid('f', true);
        $filename = $base . '.' . $ext;
        $thumb    = $base . '_t.' . $ext;

        move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $filename);
        $this->comprimirImagem($uploadDir . $filename, $uploadDir . $filename, 1600);
        $this->comprimirImagem($uploadDir . $filename, $thumbDir . $thumb, 320);

        $rel      = 'diario/' . $diarioId . '/' . $filename;
        $relThumb = 'diario/' . $diarioId . '/thumbs/' . $thumb;

        $ins = $this->db->prepare("
            INSERT INTO diario_fotos (diario_id, step_num, arquivo, thumb, lat, lng, tipo)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([$diarioId, $step, $rel, $relThumb,
            $lat ? (float)$lat : null,
            $lng ? (float)$lng : null,
            $tipo ?: null]);
        $fotoId = (int)$this->db->lastInsertId();

        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'foto_id' => $fotoId, 'thumb' => EXECUTOR_BASE . '/uploads/' . $relThumb]);
    }

    // --------------------------------------------------------
    // Sync offline — recebe fila JSON do localStorage
    // --------------------------------------------------------
    public function sync(): void {
        auth_required_executor();

        $body = file_get_contents('php://input');
        $payload = json_decode($body, true);

        if (!is_array($payload)) {
            echo json_encode(['ok' => false]);
            return;
        }

        $token = $payload['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'Token inválido.']);
            return;
        }

        $results = [];
        foreach ($payload['fila'] ?? [] as $item) {
            $tipo = $item['tipo'] ?? '';
            try {
                $results[] = $this->syncItem($tipo, $item);
            } catch (Throwable $e) {
                $results[] = ['ok' => false, 'tipo' => $tipo, 'msg' => $e->getMessage()];
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'results' => $results]);
    }

    // --------------------------------------------------------
    // Helpers privados
    // --------------------------------------------------------
    private function equipeDoAutor(int $autorId): ?int {
        // Executor é o responsavel_id da equipe
        $stmt = $this->db->prepare("
            SELECT id FROM equipes WHERE responsavel_id = ? AND ativo = 1 LIMIT 1
        ");
        $stmt->execute([$autorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id'] : null;
    }

    private function carregarDiario(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM diarios_execucao WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function verificarPermissao(array $diario): void {
        $autorId  = (int)$_SESSION['usuario_id'];
        $equipeId = $this->equipeDoAutor($autorId);
        if ((int)$diario['equipe_id'] !== $equipeId) {
            http_response_code(403);
            echo "Acesso negado.";
            exit;
        }
    }

    private function listar(string $sql, array $params): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function processarStep(int $diarioId, int $step, array $diario): bool {
        // Cada step persiste os campos que lhe cabem
        switch ($step) {
            case 1: // Presença inicial
                $ids  = $_POST['presentes']  ?? [];
                $aus  = $_POST['ausentes']   ?? [];
                // Zera presença existente para o diário, reinsere
                $this->db->prepare("DELETE FROM diario_presencas WHERE diario_id = ?")->execute([$diarioId]);
                foreach ($ids as $fid) {
                    $this->db->prepare("INSERT INTO diario_presencas (diario_id,funcionario_id,status) VALUES (?,?,'presente')")
                             ->execute([$diarioId, (int)$fid]);
                }
                foreach ($aus as $fid) {
                    $obs = substr($_POST['obs_' . $fid] ?? '', 0, 255);
                    $this->db->prepare("INSERT INTO diario_presencas (diario_id,funcionario_id,status,obs) VALUES (?,?,'ausente',?)")
                             ->execute([$diarioId, (int)$fid, $obs]);
                }
                return true;

            case 2: // Atrasos / saídas
                foreach ($_POST['atraso_func'] ?? [] as $fid => $status) {
                    $obs = substr($_POST['atraso_obs'][$fid] ?? '', 0, 255);
                    $s   = in_array($status, ['atrasou','saiu_cedo']) ? $status : 'presente';
                    $this->db->prepare("
                        INSERT INTO diario_presencas (diario_id,funcionario_id,status,obs)
                        VALUES (?,?,?,?)
                        ON DUPLICATE KEY UPDATE status=VALUES(status), obs=VALUES(obs)
                    ")->execute([$diarioId, (int)$fid, $s, $obs]);
                }
                return true;

            case 6: // Equipamentos
                $this->db->prepare("DELETE FROM diario_equipamentos WHERE diario_id = ?")->execute([$diarioId]);
                foreach ($_POST['equip_id'] ?? [] as $idx => $eid) {
                    $tipo      = ($_POST['equip_tipo'][$idx] ?? '') === 'leve' ? 'leve' : 'pesado';
                    $func      = !empty($_POST['equip_func'][$idx]) ? 1 : 0;
                    $obs       = substr($_POST['equip_obs'][$idx] ?? '', 0, 255);
                    $fotoId    = !empty($_POST['equip_foto'][$idx]) ? (int)$_POST['equip_foto'][$idx] : null;
                    $this->db->prepare("INSERT INTO diario_equipamentos (diario_id,equipamento_id,tipo,funcionando,obs,foto_id) VALUES (?,?,?,?,?,?)")
                             ->execute([$diarioId, (int)$eid, $tipo, $func, $obs, $fotoId]);
                }
                return true;

            case 11: // Interferências
                foreach ($_POST['interf_tipo'] ?? [] as $idx => $tipo) {
                    $tipos_validos = ['pedra','agua_na_vala','ramal_de_agua','rede_de_agua','rede_pluvial','rompimento_de_rede','rede_cloacal_existente','rede_logica','rede_eletrica','outros'];
                    if (!in_array($tipo, $tipos_validos)) continue;
                    $esp    = substr($_POST['interf_esp'][$idx]  ?? '', 0, 255);
                    $lat    = $_POST['interf_lat'][$idx]  ?? null;
                    $lng    = $_POST['interf_lng'][$idx]  ?? null;
                    $fotoId = !empty($_POST['interf_foto'][$idx]) ? (int)$_POST['interf_foto'][$idx] : null;
                    $this->db->prepare("INSERT INTO diario_interferencias (diario_id,tipo,especificacao,lat,lng,foto_id) VALUES (?,?,?,?,?,?)")
                             ->execute([$diarioId, $tipo, $esp, $lat ? (float)$lat : null, $lng ? (float)$lng : null, $fotoId]);
                }
                return true;

            case 12: // GPS início
            case 13: // GPS fim
                $lat    = $_POST['lat']     ?? null;
                $lng    = $_POST['lng']     ?? null;
                $fotoId = !empty($_POST['foto_id']) ? (int)$_POST['foto_id'] : null;
                $existing = $this->db->prepare("SELECT id FROM diario_gps WHERE diario_id = ?");
                $existing->execute([$diarioId]);
                if ($existing->fetch()) {
                    if ($step === 12) {
                        $this->db->prepare("UPDATE diario_gps SET lat_inicio=?,lng_inicio=?,foto_inicio_id=? WHERE diario_id=?")
                                 ->execute([$lat ? (float)$lat : null, $lng ? (float)$lng : null, $fotoId, $diarioId]);
                    } else {
                        $this->atualizarGpsFim($diarioId, $lat, $lng, $fotoId);
                    }
                } else {
                    if ($step === 12) {
                        $this->db->prepare("INSERT INTO diario_gps (diario_id,lat_inicio,lng_inicio,foto_inicio_id) VALUES (?,?,?,?)")
                                 ->execute([$diarioId, $lat ? (float)$lat : null, $lng ? (float)$lng : null, $fotoId]);
                    } else {
                        $this->atualizarGpsFim($diarioId, $lat, $lng, $fotoId);
                    }
                }
                return true;

            case 14: // Pontões
                foreach ($_POST['pontao_res'] ?? [] as $idx => $nro) {
                    $fotoId = !empty($_POST['pontao_foto'][$idx]) ? (int)$_POST['pontao_foto'][$idx] : null;
                    $this->db->prepare("INSERT INTO diario_pontoes (diario_id,nro_residencia,foto_id) VALUES (?,?,?)")
                             ->execute([$diarioId, substr($nro, 0, 50), $fotoId]);
                }
                return true;

            case 15: // Cargas bota-fora
            case 16: // Cargas importado
                $tipo = ($step === 15) ? 'bota_fora' : 'importado';
                $num  = 1;
                foreach ($_POST['carga_foto'] ?? [] as $fotoId) {
                    $this->db->prepare("INSERT INTO diario_cargas (diario_id,tipo,numero,foto_id) VALUES (?,?,?,?)")
                             ->execute([$diarioId, $tipo, $num++, $fotoId ? (int)$fotoId : null]);
                }
                return true;

            case 17: // Reaterros
                foreach ($_POST['reat_tipo'] ?? [] as $idx => $tipo) {
                    $tipos_validos = ['lastro_brita','colchao_areia_po_brita','reaterro_importado','compactacao_importado','reaterro_local','compactacao_local','base_brita_graduada','compactacao_base'];
                    if (!in_array($tipo, $tipos_validos)) continue;
                    $esp    = !empty($_POST['reat_esp'][$idx])  ? (float)$_POST['reat_esp'][$idx] : null;
                    $fotoId = !empty($_POST['reat_foto'][$idx]) ? (int)$_POST['reat_foto'][$idx]  : null;
                    $this->db->prepare("INSERT INTO diario_reaterros (diario_id,tipo,espessura_cm,foto_id) VALUES (?,?,?,?)")
                             ->execute([$diarioId, $tipo, $esp, $fotoId]);
                }
                return true;

            case 18: // Ramais
                foreach ($_POST['ramal_nro'] ?? [] as $idx => $nro) {
                    $pontao  = substr($_POST['ramal_pontao'][$idx]  ?? '', 0, 50);
                    $pista   = !empty($_POST['ramal_pista'][$idx])   ? (float)$_POST['ramal_pista'][$idx]   : null;
                    $calcada = !empty($_POST['ramal_calcada'][$idx]) ? (float)$_POST['ramal_calcada'][$idx] : null;
                    $this->db->prepare("INSERT INTO diario_ramais (diario_id,nro_residencia,dimensao_pontao,ext_pista,ext_calcada) VALUES (?,?,?,?,?)")
                             ->execute([$diarioId, substr($nro, 0, 50), $pontao, $pista, $calcada]);
                }
                return true;

            default:
                return true; // Passos só-foto (3-5, 7-10, 19-21) — foto já foi salva pelo uploadFoto
        }
    }

    private function atualizarGpsFim(int $diarioId, ?string $lat, ?string $lng, ?int $fotoId): void {
        // Calcula extensão haversine se tiver ponto de início
        $existing = $this->db->prepare("SELECT lat_inicio, lng_inicio FROM diario_gps WHERE diario_id = ?");
        $existing->execute([$diarioId]);
        $row = $existing->fetch(PDO::FETCH_ASSOC);

        $extensao = null;
        if ($row && $row['lat_inicio'] && $lat && $lng) {
            $extensao = $this->haversine(
                (float)$row['lat_inicio'], (float)$row['lng_inicio'],
                (float)$lat,              (float)$lng
            );
        }

        if ($row) {
            $this->db->prepare("UPDATE diario_gps SET lat_fim=?,lng_fim=?,foto_fim_id=?,extensao_calculada_m=? WHERE diario_id=?")
                     ->execute([$lat ? (float)$lat : null, $lng ? (float)$lng : null, $fotoId, $extensao, $diarioId]);
        } else {
            $this->db->prepare("INSERT INTO diario_gps (diario_id,lat_fim,lng_fim,foto_fim_id,extensao_calculada_m) VALUES (?,?,?,?,?)")
                     ->execute([$diarioId, $lat ? (float)$lat : null, $lng ? (float)$lng : null, $fotoId, $extensao]);
        }
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float {
        $R  = 6371000; // metros
        $p1 = deg2rad($lat1); $p2 = deg2rad($lat2);
        $dp = deg2rad($lat2 - $lat1);
        $dl = deg2rad($lng2 - $lng1);
        $a  = sin($dp/2)**2 + cos($p1)*cos($p2)*sin($dl/2)**2;
        return round(2 * $R * asin(sqrt($a)), 2);
    }

    private function comprimirImagem(string $src, string $dest, int $maxPx): void {
        if (!extension_loaded('gd')) return;
        $info = @getimagesize($src);
        if (!$info) return;
        [$w, $h, $type] = $info;
        if (max($w, $h) <= $maxPx) {
            if ($src !== $dest) copy($src, $dest);
            return;
        }
        $ratio  = $maxPx / max($w, $h);
        $nw     = (int)round($w * $ratio);
        $nh     = (int)round($h * $ratio);
        $canvas = imagecreatetruecolor($nw, $nh);
        $img    = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($src),
            IMAGETYPE_PNG  => imagecreatefrompng($src),
            IMAGETYPE_WEBP => imagecreatefromwebp($src),
            default        => null,
        };
        if (!$img) return;
        imagecopyresampled($canvas, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($canvas, $dest, 82),
            IMAGETYPE_PNG  => imagepng($canvas, $dest, 6),
            IMAGETYPE_WEBP => imagewebp($canvas, $dest, 82),
            default        => null,
        };
        imagedestroy($canvas);
        imagedestroy($img);
    }

    private function syncItem(string $tipo, array $item): array {
        $diarioId = (int)($item['diario_id'] ?? 0);
        if (!$diarioId) return ['ok' => false, 'tipo' => $tipo, 'msg' => 'diario_id inválido'];

        $diario = $this->carregarDiario($diarioId);
        if (!$diario) return ['ok' => false, 'tipo' => $tipo, 'msg' => 'Diário não encontrado'];

        // Mapeia tipo do item para step e popula $_POST temporariamente
        $_POST = array_merge($_POST, $item['dados'] ?? []);
        $step  = (int)($item['step'] ?? 0);
        $ok    = $this->processarStep($diarioId, $step, $diario);
        return ['ok' => $ok, 'tipo' => $tipo];
    }
}
