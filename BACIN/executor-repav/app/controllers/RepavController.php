<?php
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

class RepavController {

    /** Rótulos idênticos aos de RamalController::PAV_VIA / ::PAV_CALCADA (app executor-ramais). */
    public const PAV_VIA = [
        'asfalto'                  => 'Asfalto',
        'asfalto_paralelepipedo'   => 'Asfalto sobre paralelepípedo',
        'paralelepipedo_regular'   => 'Paralelepípedo regular',
        'paralelepipedo_irregular' => 'Paralelepípedo irregular',
        'bloco_concreto'           => 'Bloco de concreto',
        'chao_batido'              => 'Chão batido',
    ];

    public const PAV_CALCADA = [
        'concreto'            => 'Concreto',
        'ladrilho_hidraulico' => 'Ladrilho hidráulico',
        'petit_pave'          => 'Petit pavê',
        'bloco_concreto'      => 'Bloco de concreto',
        'basalto'             => 'Basalto',
        'grama_terra'         => 'Grama / terra',
        'sem_calcada'         => 'Sem calçada',
    ];

    /** Faixa plausível de espessura de reposição asfáltica, em metros. */
    public const ESP_MIN = 0.02;
    public const ESP_MAX = 0.15;

    /** A partir deste passo o diário pode ser encerrado e enviado. */
    public const STEP_ENCERRAR = 17;

    /** Só pavimento asfáltico tem espessura/volume de massa (asfalto e asfalto sobre paralelepípedo). */
    public static function ehAsfaltoTipo(?string $tipo): bool {
        $t = mb_strtolower(trim((string)$tipo));
        return $t !== '' && (str_contains($t, 'asfalto') || str_contains($t, 'cbuq'));
    }

    private PDO $db;
    private string $uploadsDir;
    /** @var string[] avisos acumulados no processamento de um passo */
    private array $avisos = [];

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
        $filaCaminh    = [];
        $trechoAtual   = null;
        $pavimentos    = [];
        $filaRede      = [];
        $filaRamais    = [];
        $diariosHoje   = [];

        if ($equipeId) {
            // Libera na fila de ramais os trechos cuja frente de ramais já foi enviada
            // e que ainda não têm status de repavimentação de ramais (normalização idempotente).
            $this->liberarTrechosComRamaisConcluidos();

            // ── Fila da REDE (FIFO por rede_concluida_em) ──
            $filaRede = $this->listar("
                SELECT t.id, t.pv_montante, t.pv_jusante, t.rua, t.bacia, t.contrato,
                       t.extensao, t.status_repav AS status_escopo, t.rede_concluida_em AS liberado_em
                FROM trechos t
                WHERE t.status_repav IN ('aguardando','execucao')
                ORDER BY (t.rede_concluida_em IS NULL), t.rede_concluida_em ASC, t.id ASC
            ");

            // ── Fila de RAMAIS (FIFO por ramais_concluidos_em) ──
            $filaRamais = $this->listar("
                SELECT t.id, t.pv_montante, t.pv_jusante, t.rua, t.bacia, t.contrato,
                       t.extensao, t.status_repav_ramais AS status_escopo,
                       t.ramais_concluidos_em AS liberado_em,
                       COALESCE(r.qtd, 0)    AS qtd_ramais,
                       COALESCE(r.via_m, 0)  AS via_m,
                       COALESCE(r.calc_m, 0) AS calcada_m
                FROM trechos t
                LEFT JOIN (
                    SELECT fr.trecho_id,
                           COUNT(ra.id)                            AS qtd,
                           COALESCE(SUM(ra.comprimento_via_m),0)    AS via_m,
                           COALESCE(SUM(ra.comprimento_calcada_m),0) AS calc_m
                    FROM frentes_ramais fr
                    JOIN ramais ra ON ra.frente_id = fr.id
                    WHERE fr.status = 'enviado' AND fr.trecho_id IS NOT NULL
                    GROUP BY fr.trecho_id
                ) r ON r.trecho_id = t.id
                WHERE t.status_repav_ramais IN ('aguardando','execucao')
                ORDER BY (t.ramais_concluidos_em IS NULL), t.ramais_concluidos_em ASC, t.id ASC
            ");

            // ── Diários de hoje da equipe (chave trecho|escopo, maior versão) ──
            $linhas = $this->listar("
                SELECT id, trecho_id, escopo, status, step_atual, versao, area_total_m2, volume_asf_m3
                FROM diarios_repav
                WHERE equipe_id = ? AND data = CURDATE()
                ORDER BY versao ASC
            ", [$equipeId]);
            foreach ($linhas as $l) {
                $diariosHoje[$l['trecho_id'] . '|' . $l['escopo']] = $l;
            }

            // ── Caminhamento publicado (destaque opcional) ──
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
            $caminhamento = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($caminhamento) {
                $filaCaminh = $this->listar("
                    SELECT ct.id AS ct_id, ct.sequencia AS ordem, ct.status AS ct_status,
                           t.id, t.pv_montante, t.pv_jusante, t.extensao, t.rua, t.bacia, t.contrato
                    FROM caminhamentos_repav_trechos ct
                    JOIN trechos t ON t.id = ct.trecho_id
                    WHERE ct.caminhamento_id = ?
                    ORDER BY ct.sequencia ASC
                ", [$caminhamento['id']]);

                foreach ($filaCaminh as $tc) {
                    if ($tc['ct_status'] !== 'concluido') { $trechoAtual = $tc; break; }
                }
                if ($trechoAtual) {
                    $pavimentos = $this->listar("
                        SELECT tipo_pavimento, espessura_cm
                        FROM caminhamentos_repav_pavimentos
                        WHERE caminhamento_trecho_id = ?
                        ORDER BY id
                    ", [$trechoAtual['ct_id']]);
                }
            }
        }

        // Avisos de devolução do escritório (tabela trecho_devolucoes), por trecho+escopo
        $devolucoes = $equipeId ? $this->devolucoesPendentes($filaRede, $filaRamais) : [];

        $totalLiberados = count($filaRede) + count($filaRamais);

        require __DIR__ . '/../views/home.php';
    }

    // ── Novo diário (por escopo) ───────────────────────────────
    public function novo(): void {
        auth_required_repav();
        csrf_verify_repav();

        $autorId  = (int)$_SESSION['usuario_id'];
        $equipeId = $this->equipeDoAutor($autorId);
        $trechoId = (int)($_POST['trecho_id'] ?? 0);
        $escopo   = ($_POST['escopo'] ?? 'rede') === 'ramais' ? 'ramais' : 'rede';

        if (!$equipeId || !$trechoId) {
            $this->telaErro(400, 'Não deu para abrir o diário',
                !$equipeId
                    ? 'Você não está vinculado a uma equipe de pavimentação. Peça ao Planejador para vincular.'
                    : 'O trecho não veio na solicitação. Volte para a fila e toque de novo no trecho.');
            return;
        }

        $trecho = $this->fetch1("SELECT * FROM trechos WHERE id = ?", [$trechoId]);
        if (!$trecho) {
            $this->telaErro(404, 'Trecho não encontrado',
                'Este trecho não existe mais no sistema. Volte para a fila e escolha outro.');
            return;
        }

        $campo  = $escopo === 'rede' ? 'status_repav' : 'status_repav_ramais';
        $status = $trecho[$campo];
        if (!in_array($status, ['aguardando','execucao'], true)) {
            $ondeEsc  = $escopo === 'rede' ? 'rede' : 'ramais';
            $porque   = $status === 'medido'
                ? 'A medição de ' . $ondeEsc . ' deste trecho já foi encerrada e enviada ao escritório.'
                : ($escopo === 'rede'
                    ? 'A frente de rede ainda não foi concluída — o trecho só entra na fila depois disso.'
                    : 'A frente de ramais ainda não foi enviada — o trecho só entra na fila depois disso.');
            $this->telaErro(409,
                'Trecho não liberado para repavimentação de ' . $ondeEsc,
                $porque . ' Se estiver errado, fale com o Planejador.',
                $trecho);
            return;
        }

        // Unicidade: (equipe, trecho, data, escopo, versão)
        $existente = $this->fetch1("
            SELECT id, status, versao FROM diarios_repav
            WHERE equipe_id = ? AND trecho_id = ? AND escopo = ? AND data = CURDATE()
            ORDER BY versao DESC LIMIT 1
        ", [$equipeId, $trechoId, $escopo]);

        if ($existente && $existente['status'] === 'rascunho') {
            $this->marcarTrechoEmExecucao($trechoId, $escopo);
            header('Location: ' . REPAV_BASE . '/diario/' . $existente['id']);
            exit;
        }

        // A versão é sequencial dentro de (equipe, trecho, data) — a chave única
        // uk_diario_repav não inclui o escopo, então rede e ramais compartilham a sequência.
        $ins = $this->db->prepare("
            INSERT INTO diarios_repav
                (equipe_id, trecho_id, escopo, data, autor_id, status, versao, step_atual)
            VALUES (?, ?, ?, CURDATE(), ?, 'rascunho', ?, 1)
        ");

        $diarioId = 0;
        for ($tentativa = 0; $tentativa < 5 && !$diarioId; $tentativa++) {
            $versao = 1 + (int)$this->fetchCol("
                SELECT COALESCE(MAX(versao),0) FROM diarios_repav
                WHERE equipe_id = ? AND trecho_id = ? AND data = CURDATE()
            ", [$equipeId, $trechoId]);
            try {
                $ins->execute([$equipeId, $trechoId, $escopo, $autorId, $versao]);
                $diarioId = (int)$this->db->lastInsertId();
            } catch (\PDOException $e) {
                if ($e->getCode() !== '23000') throw $e; // só reage a versão duplicada
            }
        }
        if (!$diarioId) {
            $this->telaErro(409, 'Não deu para abrir o diário agora',
                'Outro aparelho pode ter aberto o diário deste trecho no mesmo instante. '
                . 'Volte para a fila e tente de novo.', $trecho);
            return;
        }

        $this->marcarTrechoEmExecucao($trechoId, $escopo);

        if ($escopo === 'ramais') {
            $this->prepararAreasRamais($diarioId, $trechoId);
        }

        header('Location: ' . REPAV_BASE . '/diario/' . $diarioId);
        exit;
    }

    // ── Ver / preencher diário ─────────────────────────────────
    public function ver(int $id): void {
        auth_required_repav();

        $diario = $this->carregarDiario($id);
        if (!$diario) {
            $this->telaErro(404, 'Diário não encontrado',
                'Este diário não existe mais. Volte para a fila e abra o trecho de novo.');
            return;
        }
        $this->verificarPermissao($diario);

        $escopo = $diario['escopo'] === 'ramais' ? 'ramais' : 'rede';

        $trecho = $this->fetch1("SELECT * FROM trechos WHERE id = ?", [$diario['trecho_id']]);
        $funcionarios = $this->listar("
            SELECT f.id, f.nome, f.funcao
            FROM equipe_funcionarios ef
            JOIN funcionarios f ON f.id = ef.funcionario_id
            WHERE ef.equipe_id = ? AND ef.ativo = 1
            ORDER BY f.nome
        ", [$diario['equipe_id']]);

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

        // Diário de ramais: garante as linhas pré-preenchidas (via/calçada) de cada ramal
        $ramais = [];
        if ($escopo === 'ramais') {
            if ($diario['status'] === 'rascunho') {
                $this->prepararAreasRamais($id, (int)$diario['trecho_id']);
            }
            $ramais = $this->ramaisDoTrecho((int)$diario['trecho_id']);
        }

        $presencas    = $this->listar("SELECT rp.*, f.nome, f.funcao FROM diario_repav_presencas rp JOIN funcionarios f ON f.id = rp.funcionario_id WHERE rp.diario_id = ?", [$id]);
        $equipamentos = $this->listar("SELECT * FROM diario_repav_equipamentos WHERE diario_id = ? ORDER BY id", [$id]);
        $cargas       = $this->listar("SELECT * FROM diario_repav_cargas WHERE diario_id = ? ORDER BY sequencia", [$id]);
        $areas        = $this->listar("SELECT * FROM diario_repav_areas WHERE diario_id = ? ORDER BY ramal_id IS NULL, ramal_id, local, tipo_pavimento, sequencia, id", [$id]);
        $fotos        = $this->listar("SELECT * FROM diario_repav_fotos WHERE diario_id = ? ORDER BY step_num, id", [$id]);

        $fotosPorStep = [];
        foreach ($fotos as $f) $fotosPorStep[(int)$f['step_num']][] = $f;

        // Aviso de devolução do escritório para este trecho neste escopo
        $devolucao = $this->devolucaoPendente((int)$diario['trecho_id'], $escopo);

        $presencaMap = [];
        foreach ($presencas as $p) $presencaMap[$p['funcionario_id']] = $p;

        $areasPorRamal = [];
        foreach ($areas as $a) {
            if ($a['ramal_id'] !== null) $areasPorRamal[(int)$a['ramal_id']][] = $a;
        }

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

        if (!$diario || $diario['status'] !== 'rascunho') {
            echo json_encode(['ok' => false, 'msg' => 'Diário bloqueado.']); return;
        }
        $this->verificarPermissao($diario);

        $this->avisos = [];
        $ok = $this->processarStep($diarioId, $step, $diario);

        $stepAtual = (int)$diario['step_atual'];
        if ($ok && !$this->avisos && $step > $stepAtual) {
            $stepAtual = $this->avancarStep($diarioId, $step);
        }
        echo json_encode([
            'ok'         => $ok,
            'msg'        => $this->avisos ? implode(' ', $this->avisos) : '',
            'step_atual' => $stepAtual,
            'encerrar'   => $stepAtual >= self::STEP_ENCERRAR,
        ]);
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
        if (!$diario || $diario['status'] !== 'rascunho') {
            echo json_encode(['ok' => false, 'msg' => 'Diário inválido ou já enviado.']); return;
        }
        $this->verificarPermissao($diario);

        if (empty($_FILES['foto']['tmp_name']) || !is_uploaded_file($_FILES['foto']['tmp_name'])) {
            echo json_encode(['ok' => false, 'msg' => 'Arquivo ausente.']); return;
        }

        $info = $_FILES['foto'];
        if ($info['size'] > 8 * 1024 * 1024) {
            echo json_encode(['ok' => false, 'msg' => 'Arquivo muito grande (máx 8 MB).']); return;
        }

        // Valida o tipo REAL do arquivo (não a extensão informada pelo cliente)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = (string)$finfo->file($info['tmp_name']);
        $extPorMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extPorMime[$mime])) {
            echo json_encode(['ok' => false, 'msg' => 'Formato inválido — use foto JPG, PNG ou WebP.']); return;
        }
        $ext = $extPorMime[$mime];

        $dir = $this->uploadsDir . '/repav';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $dirThumb = $this->uploadsDir . '/repav/thumbs';
        if (!is_dir($dirThumb)) mkdir($dirThumb, 0755, true);

        $nome  = date('Ymd_His') . '_' . $diarioId . '_s' . $step . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destino = $dir . '/' . $nome;

        if (!move_uploaded_file($info['tmp_name'], $destino)) {
            echo json_encode(['ok' => false, 'msg' => 'Falha ao salvar arquivo.']); return;
        }

        $thumb = $this->gerarThumb($destino, $dirThumb . '/' . $nome, 400);

        $ins = $this->db->prepare("
            INSERT INTO diario_repav_fotos (diario_id, step_num, filename, thumb, lat, lng, captured_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([$diarioId, $step, $nome, $thumb ? $nome : null,
                       $lat ?: null, $lng ?: null, $ts ?: null]);

        // A foto É o registro do passo: o passo passa a valer como feito, sem recarregar a tela.
        $stepAtual = $this->avancarStep($diarioId, $step);

        echo json_encode([
            'ok'         => true,
            'filename'   => $nome,
            'thumb'      => $thumb ? $nome : null,
            'step'       => $step,
            'step_atual' => $stepAtual,
            'encerrar'   => $stepAtual >= self::STEP_ENCERRAR,
        ]);
    }

    // ── Adicionar carga de asfalto ─────────────────────────────
    public function addCarga(): void {
        auth_required_repav();
        csrf_verify_repav();

        header('Content-Type: application/json');
        $diarioId = (int)($_POST['diario_id'] ?? 0);
        $diario   = $this->carregarDiario($diarioId);
        if (!$diario || $diario['status'] !== 'rascunho') {
            echo json_encode(['ok' => false, 'msg' => 'Diário já enviado.']); return;
        }
        $this->verificarPermissao($diario);

        $seq = (int)$this->fetchCol(
            "SELECT COALESCE(MAX(sequencia),0)+1 FROM diario_repav_cargas WHERE diario_id = ?",
            [$diarioId]
        );
        $this->db->prepare("INSERT INTO diario_repav_cargas (diario_id, sequencia) VALUES (?, ?)")
                 ->execute([$diarioId, $seq]);
        echo json_encode(['ok' => true, 'id' => (int)$this->db->lastInsertId(), 'seq' => $seq]);
    }

    // ── Adicionar linha de área ────────────────────────────────
    public function addArea(): void {
        auth_required_repav();
        csrf_verify_repav();

        header('Content-Type: application/json');
        $diarioId = (int)($_POST['diario_id'] ?? 0);
        $tipo     = mb_substr(trim($_POST['tipo'] ?? ''), 0, 80);
        $diario   = $this->carregarDiario($diarioId);

        if (!$diario || !$tipo) { echo json_encode(['ok' => false, 'msg' => 'Dados inválidos.']); return; }
        $this->verificarPermissao($diario);

        // Diário encerrado não recebe novas áreas
        if ($diario['status'] !== 'rascunho') {
            echo json_encode(['ok' => false, 'msg' => 'Diário já enviado — não é possível incluir áreas.']); return;
        }

        $seq = (int)$this->fetchCol(
            "SELECT COALESCE(MAX(sequencia),0)+1 FROM diario_repav_areas WHERE diario_id = ? AND tipo_pavimento = ?",
            [$diarioId, $tipo]
        );

        $this->db->prepare("
            INSERT INTO diario_repav_areas (diario_id, tipo_pavimento, local, sequencia, base_m, largura_m)
            VALUES (?, ?, 'via', ?, 0, 0)
        ")->execute([$diarioId, $tipo, $seq]);

        echo json_encode(['ok' => true, 'id' => (int)$this->db->lastInsertId(), 'seq' => $seq]);
    }

    // ── Encerrar & enviar ─────────────────────────────────────
    public function encerrar(int $id): void {
        auth_required_repav();
        csrf_verify_repav();

        $diario = $this->carregarDiario($id);
        if (!$diario) { http_response_code(404); echo "Diário não encontrado."; return; }
        $this->verificarPermissao($diario);

        // Idempotente: já enviado/aprovado não reexecuta a transação
        if ($diario['status'] !== 'rascunho') {
            header('Location: ' . REPAV_BASE . '/'); exit;
        }

        $escopo   = $diario['escopo'] === 'ramais' ? 'ramais' : 'rede';
        $trechoId = (int)$diario['trecho_id'];
        $trecho   = $this->fetch1("SELECT * FROM trechos WHERE id = ?", [$trechoId]);

        // N2 — a observação final digitada no passo 19 vem junto do botão de encerrar
        if (array_key_exists('obs_final', $_POST)) {
            $obsFinal = mb_substr(trim((string)$_POST['obs_final']), 0, 1000);
            $this->db->prepare("UPDATE diarios_repav SET obs_final = ? WHERE id = ?")
                     ->execute([$obsFinal !== '' ? $obsFinal : null, $id]);
        }

        // N3 — sem área medida não há serviço para fechar
        $this->consolidarAreas($id);
        $faltando = $this->linhasSemDimensao($id);
        $areaAtual = (float)$this->fetchCol(
            "SELECT COALESCE(SUM(area_m2),0) FROM diario_repav_areas WHERE diario_id = ?", [$id]
        );
        if ($areaAtual <= 0) {
            $texto = 'O diário está com área total 0,00 m² — não dá para fechar o trecho sem medição. ';
            if ($faltando) {
                $texto .= 'Falta informar comprimento e largura em: ' . implode('; ', $faltando) . '.';
            } else {
                $texto .= 'Abra o passo de medição (' . ($escopo === 'ramais' ? 'passo 15 — Medição dos ramais' : 'passos 15 e 16 — Dimensões')
                        . ') e informe comprimento e largura de cada reposição.';
            }
            $this->telaErro(422, 'Falta a medição para encerrar', $texto, $trecho,
                            REPAV_BASE . '/diario/' . $id, 'Voltar ao diário');
            return;
        }

        $this->db->beginTransaction();
        try {
            // Trava o diário e confere de novo dentro da transação (idempotência sob concorrência)
            $atual = $this->fetch1("SELECT status FROM diarios_repav WHERE id = ? FOR UPDATE", [$id]);
            if (!$atual || $atual['status'] !== 'rascunho') {
                $this->db->rollBack();
                header('Location: ' . REPAV_BASE . '/'); exit;
            }

            // Consolida área e volume de cada linha
            $this->consolidarAreas($id);

            $tot = $this->fetch1("
                SELECT COALESCE(SUM(area_m2),0) AS area, COALESCE(SUM(volume_m3),0) AS vol
                FROM diario_repav_areas WHERE diario_id = ?
            ", [$id]);
            $areaTotal = (float)($tot['area'] ?? 0);
            $volAsf    = (float)($tot['vol']  ?? 0);

            $this->db->prepare("
                UPDATE diarios_repav
                   SET status = 'enviado', step_atual = 19,
                       area_total_m2 = ?, volume_asf_m3 = ?
                 WHERE id = ? AND status = 'rascunho'
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

            // ── Encerrar FECHA o trecho naquele escopo ──
            if ($escopo === 'rede') {
                $this->db->prepare("UPDATE trechos SET status_repav = 'medido' WHERE id = ?")
                         ->execute([$trechoId]);
                $this->db->prepare("
                    UPDATE caminhamentos_repav_trechos ct
                    JOIN caminhamentos_repav cr ON cr.id = ct.caminhamento_id
                       SET ct.status = 'concluido'
                     WHERE ct.trecho_id = ? AND cr.equipe_id = ? AND ct.status <> 'concluido'
                ")->execute([$trechoId, (int)$diario['equipe_id']]);
            } else {
                $this->db->prepare("UPDATE trechos SET status_repav_ramais = 'medido' WHERE id = ?")
                         ->execute([$trechoId]);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->telaErro(500, 'Erro ao encerrar o diário',
                'Nada foi perdido — o diário continua aberto. Confira o sinal e tente enviar de novo.',
                $trecho, REPAV_BASE . '/diario/' . $id, 'Voltar ao diário');
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
                    if ($todos === 's' || $todos === 'n') {
                        $ausentes = $todos === 'n' ? array_map('intval', (array)($_POST['ausentes'] ?? [])) : [];
                        $funcs = $this->listar("SELECT funcionario_id FROM equipe_funcionarios WHERE equipe_id = ? AND ativo = 1", [$diario['equipe_id']]);
                        $this->db->prepare("DELETE FROM diario_repav_presencas WHERE diario_id = ?")->execute([$diarioId]);
                        $ins = $this->db->prepare("INSERT INTO diario_repav_presencas (diario_id, funcionario_id, status) VALUES (?, ?, ?)");
                        foreach ($funcs as $f) {
                            $status = in_array((int)$f['funcionario_id'], $ausentes, true) ? 'ausente' : 'presente';
                            $ins->execute([$diarioId, $f['funcionario_id'], $status]);
                        }
                    }
                    return true;

                case 2: // Atrasos / saídas
                    $atrasados = array_map('intval', (array)($_POST['atrasou'] ?? []));
                    $saiuCedo  = array_map('intval', (array)($_POST['saiu_cedo'] ?? []));
                    foreach (array_unique(array_merge($atrasados, $saiuCedo)) as $fid) {
                        if (!$fid) continue;
                        $status = in_array($fid, $saiuCedo, true) ? 'saiu_cedo' : 'atrasou';
                        $this->db->prepare("
                            UPDATE diario_repav_presencas SET status = ? WHERE diario_id = ? AND funcionario_id = ?
                        ")->execute([$status, $diarioId, $fid]);
                    }
                    return true;

                case 3: // Materiais
                    $ok  = in_array($_POST['mat_ok'] ?? '', ['1','0'], true) ? (int)$_POST['mat_ok'] : null;
                    $obs = mb_substr(trim($_POST['mat_obs'] ?? ''), 0, 500);
                    $this->db->prepare("UPDATE diarios_repav SET mat_ok = ?, mat_obs = ? WHERE id = ?")
                             ->execute([$ok, $obs ?: null, $diarioId]);
                    return true;

                case 6: // Equipamentos
                    $ids    = (array)($_POST['equip_id']     ?? []);
                    $tipos  = (array)($_POST['equip_tipo']   ?? []);
                    $status = (array)($_POST['equip_status'] ?? []);
                    $this->db->prepare("DELETE FROM diario_repav_equipamentos WHERE diario_id = ?")->execute([$diarioId]);
                    $ins = $this->db->prepare("INSERT INTO diario_repav_equipamentos (diario_id, equipamento_id, tipo, nome, status) VALUES (?, ?, ?, ?, ?)");
                    foreach ($ids as $i => $eid) {
                        if (!$eid) continue;
                        $tipo = ($tipos[$i] ?? 'pesado') === 'leve' ? 'leve' : 'pesado';
                        $st   = ($status[$i] ?? 'ok') === 'problema' ? 'problema' : 'ok';
                        $ins->execute([$diarioId, (int)$eid, $tipo, '', $st]);
                    }
                    return true;

                case 10: // Cargas de asfalto
                    $ids    = (array)($_POST['carga_id']   ?? []);
                    $nfs    = (array)($_POST['carga_nf']   ?? []);
                    $massas = (array)($_POST['carga_mass'] ?? []);
                    $upd = $this->db->prepare("UPDATE diario_repav_cargas SET numero_nf = ?, massa_t = ? WHERE id = ? AND diario_id = ?");
                    foreach ($ids as $i => $cid) {
                        if (!$cid) continue;
                        $massa = $this->numero($massas[$i] ?? '', 'Massa da carga ' . ($i + 1), 60.0);
                        $upd->execute([
                            mb_substr(trim($nfs[$i] ?? ''), 0, 50) ?: null,
                            $massa,
                            (int)$cid, $diarioId
                        ]);
                    }
                    return true;

                case 15: // Dimensões (asfalto / medição dos ramais)
                case 16: // Outros pavimentos
                    return $this->salvarAreas($diarioId, $diario);

                case 19: // Finalização
                    $obs = mb_substr(trim($_POST['obs_final'] ?? ''), 0, 1000);
                    $this->db->prepare("UPDATE diarios_repav SET obs_final = ? WHERE id = ?")
                             ->execute([$obs ?: null, $diarioId]);
                    return true;

                default:
                    return true;
            }
        } catch (\PDOException $e) {
            $this->avisos[] = 'Não foi possível gravar este passo.';
            return false;
        }
    }

    /** Grava as linhas de medição (rede e ramais) validando números com vírgula. */
    private function salvarAreas(int $diarioId, array $diario): bool {
        $ids    = (array)($_POST['area_id']   ?? []);
        $bases  = (array)($_POST['area_base'] ?? []);
        $largs  = (array)($_POST['area_larg'] ?? []);
        $esps   = (array)($_POST['area_esp']  ?? []);
        $tipos  = (array)($_POST['area_tipo'] ?? []);
        if (!$ids) return true;

        $atuais = [];
        foreach ($this->listar("SELECT * FROM diario_repav_areas WHERE diario_id = ?", [$diarioId]) as $r) {
            $atuais[(int)$r['id']] = $r;
        }

        $upd = $this->db->prepare("
            UPDATE diario_repav_areas
               SET tipo_pavimento = ?, base_m = ?, largura_m = ?, espessura_m = ?,
                   area_m2 = ROUND(? * ?, 2),
                   volume_m3 = ?
             WHERE id = ? AND diario_id = ?
        ");

        foreach ($ids as $i => $aid) {
            $aid = (int)$aid;
            if (!$aid || !isset($atuais[$aid])) continue;
            $linha = $atuais[$aid];
            $rot   = $this->rotuloLinha($linha, $i);

            $base = $this->numero($bases[$i] ?? '', 'Comprimento ' . $rot, 9999.0);
            if ($base === null) $base = (float)$linha['base_m'];

            $larg = $this->numero($largs[$i] ?? '', 'Largura ' . $rot, 100.0);
            if ($larg === null) $larg = (float)$linha['largura_m'];

            $tipo = $linha['tipo_pavimento'];
            if (isset($tipos[$i]) && trim((string)$tipos[$i]) !== '') {
                $novo = mb_substr(trim((string)$tipos[$i]), 0, 80);
                if (in_array($novo, $this->pavimentosPermitidos($linha['local']), true)) {
                    $tipo = $novo;
                } else {
                    $this->avisos[] = 'Pavimento ' . $rot . ': opção inválida — mantido "' . $linha['tipo_pavimento'] . '".';
                }
            }

            // N5 — só pavimento asfáltico tem espessura e volume de massa
            $ehAsf    = self::ehAsfaltoTipo($tipo);
            $espAtual = ($ehAsf && $linha['espessura_m'] !== null) ? (float)$linha['espessura_m'] : null;
            $espBruta = trim((string)($esps[$i] ?? ''));
            $esp      = $espAtual;
            if ($espBruta !== '' && !$ehAsf) {
                $this->avisos[] = 'Espessura ' . $rot . ': ' . $tipo . ' não leva massa asfáltica — espessura e volume não gravados.';
            } elseif ($espBruta !== '') {
                $v = $this->numero($espBruta, 'Espessura ' . $rot, 5.0);
                if ($v !== null) {
                    if ($v < self::ESP_MIN || $v > self::ESP_MAX) {
                        $this->avisos[] = 'Espessura ' . $rot . ': ' . $this->br($v) . ' m está fora da faixa usual ('
                                        . $this->br(self::ESP_MIN) . ' a ' . $this->br(self::ESP_MAX) . ' m) — valor não aceito.';
                    } else {
                        $esp = $v;
                    }
                }
            }
            if (!$ehAsf) $esp = null;

            // N3 — dimensão zerada não vale medição: grava o que foi digitado, mas avisa alto e bom som
            if ($base <= 0 || $larg <= 0) {
                $falta = ($base <= 0 && $larg <= 0) ? 'o comprimento e a largura'
                       : ($base <= 0 ? 'o comprimento' : 'a largura');
                $this->avisos[] = 'Medição ' . $rot . ': ' . $falta . ' está 0,00 — meça e informe. '
                                . 'Esta linha ficou FORA da área do diário.';
            }

            $vol = ($esp !== null && $base > 0 && $larg > 0) ? round($base * $larg * $esp, 3) : null;
            $upd->execute([$tipo, $base, $larg, $esp, $base, $larg, $vol, $aid, $diarioId]);
        }

        return true;
    }

    /** Lista de rótulos de pavimento aceitos conforme o local da linha. */
    private function pavimentosPermitidos(?string $local): array {
        $livres = ['Asfalto (CBUQ)','Calçada','Paralelepípedo Regular','Paralelepípedo Irregular','Bloco de Concreto','Chão Batido'];
        if ($local === 'calcada') return array_values(self::PAV_CALCADA);
        return array_merge(array_values(self::PAV_VIA), $livres);
    }

    private function rotuloLinha(array $linha, int $i): string {
        if (!empty($linha['numero_imovel'])) {
            return '(nº ' . $linha['numero_imovel'] . ' · ' . ($linha['local'] === 'calcada' ? 'calçada' : 'via') . ')';
        }
        return '(linha ' . ($i + 1) . ')';
    }

    /**
     * Converte texto do campo em número: aceita vírgula decimal, recusa texto
     * e recusa negativo — devolvendo null e registrando aviso.
     */
    private function numero($bruto, string $rotulo, float $max): ?float {
        $s = trim((string)$bruto);
        if ($s === '') return null;

        $t = str_replace(' ', '', $s);
        if (str_contains($t, ',')) $t = str_replace('.', '', $t); // 1.234,56 → 1234,56
        $t = str_replace(',', '.', $t);

        if (!is_numeric($t)) {
            $this->avisos[] = $rotulo . ': "' . $s . '" não é um número — valor não gravado.';
            return null;
        }
        $v = (float)$t;
        if ($v < 0) {
            $this->avisos[] = $rotulo . ': valor negativo não é aceito — valor não gravado.';
            return null;
        }
        if ($v > $max) {
            $this->avisos[] = $rotulo . ': ' . $this->br($v) . ' acima do limite (' . $this->br($max) . ') — valor não gravado.';
            return null;
        }
        return round($v, 3);
    }

    private function br(float $v): string {
        return rtrim(rtrim(number_format($v, 3, ',', '.'), '0'), ',');
    }

    // ─────────────────────────────────────────────────────────
    // Ramais
    // ─────────────────────────────────────────────────────────
    /** Ramais executados e enviados do trecho. */
    public function ramaisDoTrecho(int $trechoId): array {
        return $this->listar("
            SELECT r.*, fr.logradouro, fr.data AS frente_data
            FROM frentes_ramais fr
            JOIN ramais r ON r.frente_id = fr.id
            WHERE fr.trecho_id = ? AND fr.status = 'enviado'
            ORDER BY fr.id, r.sequencia, r.id
        ", [$trechoId]);
    }

    /** Cria, uma única vez, as linhas de medição (via/calçada) de cada ramal do trecho. */
    private function prepararAreasRamais(int $diarioId, int $trechoId): void {
        $ja = (int)$this->fetchCol(
            "SELECT COUNT(*) FROM diario_repav_areas WHERE diario_id = ? AND ramal_id IS NOT NULL",
            [$diarioId]
        );
        if ($ja > 0) return;

        $ramais = $this->ramaisDoTrecho($trechoId);
        if (!$ramais) return;

        $ins = $this->db->prepare("
            INSERT INTO diario_repav_areas
                (diario_id, tipo_pavimento, local, ramal_id, numero_imovel, sequencia,
                 base_m, largura_m, espessura_m)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)
        ");

        foreach ($ramais as $n => $r) {
            $seq = min(127, (int)$r['sequencia'] ?: ($n + 1));

            $compVia = (float)$r['comprimento_via_m'];
            if ($r['pavimento_via'] !== null && $compVia > 0) {
                $rotulo = self::PAV_VIA[$r['pavimento_via']] ?? 'Asfalto';
                $esp    = str_contains($r['pavimento_via'], 'asfalto') ? 0.05 : null;
                $ins->execute([$diarioId, $rotulo, 'via', (int)$r['id'],
                               $r['numero_imovel'], $seq, $compVia, $esp]);
            }

            $compCalc = (float)$r['comprimento_calcada_m'];
            if ($r['pavimento_calcada'] !== null
                && $r['pavimento_calcada'] !== 'sem_calcada'
                && $compCalc > 0) {
                $rotulo = self::PAV_CALCADA[$r['pavimento_calcada']] ?? 'Concreto';
                $ins->execute([$diarioId, $rotulo, 'calcada', (int)$r['id'],
                               $r['numero_imovel'], $seq, $compCalc, null]);
            }
        }
    }

    /** Coloca na fila de ramais os trechos cuja frente de ramais já foi enviada. */
    private function liberarTrechosComRamaisConcluidos(): void {
        $this->db->exec("
            UPDATE trechos t
            JOIN (
                SELECT fr.trecho_id, MAX(fr.updated_at) AS concluido_em
                FROM frentes_ramais fr
                JOIN ramais ra ON ra.frente_id = fr.id
                WHERE fr.status = 'enviado' AND fr.trecho_id IS NOT NULL
                GROUP BY fr.trecho_id
            ) r ON r.trecho_id = t.id
               SET t.status_repav_ramais  = 'aguardando',
                   t.ramais_concluidos_em = COALESCE(t.ramais_concluidos_em, r.concluido_em)
             WHERE t.status_repav_ramais IS NULL
        ");
    }

    private function marcarTrechoEmExecucao(int $trechoId, string $escopo): void {
        $campo = $escopo === 'rede' ? 'status_repav' : 'status_repav_ramais';
        $this->db->prepare("UPDATE trechos SET {$campo} = 'execucao' WHERE id = ? AND {$campo} = 'aguardando'")
                 ->execute([$trechoId]);
    }

    // ─────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────
    /** Avança o passo atual do diário sem nunca retroceder. Devolve o passo gravado. */
    private function avancarStep(int $diarioId, int $step): int {
        if ($step > 0) {
            $this->db->prepare("UPDATE diarios_repav SET step_atual = ? WHERE id = ? AND step_atual < ?")
                     ->execute([$step, $diarioId, $step]);
        }
        return (int)$this->fetchCol("SELECT step_atual FROM diarios_repav WHERE id = ?", [$diarioId]);
    }

    /** Recalcula área e volume das linhas — volume só onde o pavimento é asfalto (N5). */
    private function consolidarAreas(int $diarioId): void {
        $this->db->prepare("
            UPDATE diario_repav_areas
               SET espessura_m = IF(LOWER(tipo_pavimento) LIKE '%asfalto%'
                                    OR LOWER(tipo_pavimento) LIKE '%cbuq%', espessura_m, NULL),
                   area_m2     = ROUND(base_m * largura_m, 2),
                   volume_m3   = IF(espessura_m IS NOT NULL AND base_m > 0 AND largura_m > 0,
                                    ROUND(base_m * largura_m * espessura_m, 3), NULL)
             WHERE diario_id = ?
        ")->execute([$diarioId]);
    }

    /** Rótulos das linhas de medição que ainda estão com comprimento ou largura 0,00. */
    private function linhasSemDimensao(int $diarioId): array {
        $linhas = $this->listar("
            SELECT tipo_pavimento, local, numero_imovel, base_m, largura_m
            FROM diario_repav_areas
            WHERE diario_id = ? AND (base_m <= 0 OR largura_m <= 0)
            ORDER BY ramal_id IS NULL, ramal_id, id
        ", [$diarioId]);

        $saida = [];
        foreach ($linhas as $l) {
            $onde = ($l['local'] ?? 'via') === 'calcada' ? 'calçada' : 'via';
            $qual = $l['numero_imovel'] ? 'nº ' . $l['numero_imovel'] . ' · ' . $onde : $onde;
            $saida[] = $l['tipo_pavimento'] . ' (' . $qual . ')';
            if (count($saida) >= 8) { $saida[] = '…'; break; }
        }
        return $saida;
    }

    // ─────────────────────────────────────────────────────────
    // Devoluções do escritório (tabela trecho_devolucoes)
    // ─────────────────────────────────────────────────────────
    /** Etapa da tabela trecho_devolucoes correspondente ao escopo do diário. */
    private function etapaDevolucao(string $escopo): string {
        return $escopo === 'ramais' ? 'repav_ramais' : 'repav_rede';
    }

    /** Carrega, uma única vez, o helper compartilhado de devoluções (painel/app/helpers). */
    private function carregarHelperDevolucoes(): bool {
        if (!function_exists('devolucao_pendente')) {
            $arq = dirname(__DIR__) . '/helpers/devolucoes.php';
            if (is_file($arq)) require_once $arq;
        }
        return function_exists('devolucao_pendente');
    }

    /**
     * Devolução ainda não resolvida do trecho naquele escopo (ou null).
     * Usa o helper compartilhado devolucao_pendente(); só cai na consulta
     * própria se o helper ainda não estiver disponível.
     */
    public function devolucaoPendente(int $trechoId, string $escopo): ?array {
        $etapa = $this->etapaDevolucao($escopo);

        if ($this->carregarHelperDevolucoes()) {
            $r = devolucao_pendente($this->db, $trechoId, $etapa);
            return is_array($r) && $r ? $r : null;
        }

        return $this->fetch1("
            SELECT td.etapa, td.motivo, td.created_at, u.nome AS usuario_nome
            FROM trecho_devolucoes td
            LEFT JOIN usuarios u ON u.id = td.usuario_id
            WHERE td.trecho_id = ? AND td.etapa = ?
              AND NOT EXISTS (
                    SELECT 1 FROM diarios_repav d
                    WHERE d.trecho_id = td.trecho_id
                      AND d.escopo = ?
                      AND d.status IN ('enviado','aprovado')
                      AND d.updated_at >= td.created_at
              )
            ORDER BY td.created_at DESC, td.id DESC
            LIMIT 1
        ", [$trechoId, $etapa, $escopo]);
    }

    /**
     * Devoluções pendentes dos trechos que estão na fila, indexadas por "trechoId|escopo".
     * @param array $filaRede   trechos da fila da rede
     * @param array $filaRamais trechos da fila de ramais
     */
    private function devolucoesPendentes(array $filaRede, array $filaRamais): array {
        $mapa = [];
        foreach ([['rede', $filaRede], ['ramais', $filaRamais]] as [$escopo, $fila]) {
            foreach ($fila as $t) {
                $tid = (int)($t['id'] ?? 0);
                if (!$tid || isset($mapa[$tid . '|' . $escopo])) continue;
                $dev = $this->devolucaoPendente($tid, $escopo);
                if ($dev) $mapa[$tid . '|' . $escopo] = $dev;
            }
        }
        return $mapa;
    }

    // ─────────────────────────────────────────────────────────
    // Tela de erro no padrão do app (N4)
    // ─────────────────────────────────────────────────────────
    private function telaErro(int $http, string $titulo, string $texto,
                             ?array $trecho = null,
                             ?string $urlVoltar = null, ?string $rotuloVoltar = null): void {
        if (!headers_sent()) {
            http_response_code($http);
            header('Content-Type: text/html; charset=UTF-8');
            header('X-Robots-Tag: noindex, nofollow');
        }

        $h    = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $url  = $urlVoltar    ?: REPAV_BASE . '/';
        $rot  = $rotuloVoltar ?: 'Voltar para a fila';
        $nome = $_SESSION['nome'] ?? '';
        $via  = $trecho
            ? trim(($trecho['pv_montante'] ?? '?') . ' → ' . ($trecho['pv_jusante'] ?? '?')
                   . (!empty($trecho['rua']) ? ' · ' . $trecho['rua'] : ''))
            : '';
        ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1A2D4F">
<meta name="robots" content="noindex,nofollow">
<title>Repavimentação · BACIN</title>
<link rel="stylesheet" href="<?= REPAV_BASE ?>/assets/css/repav.css">
</head>
<body>
<div class="phone">

  <div class="top">
    <div class="top-row">
      <a href="<?= $h($url) ?>" style="color:#fff;display:flex;align-items:center" aria-label="Voltar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      </a>
      <div class="nm">BACIN<small>EXECUTOR · REPAVIMENTAÇÃO</small></div>
      <div class="eq"><b><?= $h($nome) ?></b></div>
    </div>
    <div class="hoje"><span>⚠️ Não deu para seguir</span></div>
  </div>

  <div class="scroll">
    <div class="info" style="border-color:var(--aviso)">
      <div class="info-h">
        <span class="ic i-aviso">⚠️</span>
        <div>
          <b><?= $h($titulo) ?></b>
          <span><?= $h($texto) ?></span>
        </div>
      </div>
      <?php if ($via !== ''): ?>
      <div class="hint" style="margin-top:10px">Trecho: <b><?= $h($via) ?></b></div>
      <?php endif; ?>
    </div>

    <a class="btn-fila" href="<?= $h($url) ?>">← <?= $h($rot) ?></a>
  </div>

  <div class="footer">
    <div class="resumo"><?= $h($nome) ?><br><span>código <?= (int)$http ?></span></div>
    <a href="<?= REPAV_BASE ?>/" class="btn-sair">Início</a>
  </div>

</div>
</body>
</html>
        <?php
    }

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

    private function fetchCol(string $sql, array $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    private function gerarThumb(string $src, string $dest, int $maxW): bool {
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        $img = null;
        if (($ext === 'jpg' || $ext === 'jpeg') && function_exists('imagecreatefromjpeg')) $img = @imagecreatefromjpeg($src);
        elseif ($ext === 'png'  && function_exists('imagecreatefrompng'))  $img = @imagecreatefrompng($src);
        elseif ($ext === 'webp' && function_exists('imagecreatefromwebp')) $img = @imagecreatefromwebp($src);
        if (!$img) return false;

        $w = imagesx($img); $h = imagesy($img);
        if ($w <= $maxW) { imagedestroy($img); return copy($src, $dest); }
        $ratio = $maxW / $w;
        $newH  = max(1, (int)round($h * $ratio));
        $thumb = imagecreatetruecolor($maxW, $newH);
        imagecopyresampled($thumb, $img, 0, 0, 0, 0, $maxW, $newH, $w, $h);
        $ok = imagejpeg($thumb, $dest, 82);
        imagedestroy($img); imagedestroy($thumb);
        return (bool)$ok;
    }
}
