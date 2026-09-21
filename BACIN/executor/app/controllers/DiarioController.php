<?php
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
// Devoluções do Planejador (PA26) — helper compartilhado com o Painel
require_once dirname(__DIR__, 3) . '/painel/app/helpers/devolucoes.php';

class DiarioController {

    private PDO $db;

    /** Mensagem de recusa do último passo processado (mostrada no celular). */
    private ?string $erroStep = null;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    // --------------------------------------------------------
    // Home — programação do dia para a equipe do executor logado
    // --------------------------------------------------------
    public function home(): void {
        auth_required_executor();

        $autorId  = (int)$_SESSION['usuario_id'];
        $equipes  = $this->equipesDoAutor($autorId);   // o executor pode responder por mais de uma
        $equipeId = $this->equipeDoAutor($autorId);    // null enquanto ele não escolher a equipe do dia
        $precisaEscolherEquipe = (count($equipes) > 1 && !$equipeId);
        $equipeNome = '';
        foreach ($equipes as $eq) {
            if ((int)$eq['id'] === (int)$equipeId) $equipeNome = (string)$eq['nome'];
        }

        $flash = $_SESSION['flash_executor'] ?? null;
        unset($_SESSION['flash_executor']);

        // Caminhamento publicado mais próximo da equipe (data >= hoje)
        $caminhamento = null;
        $trechoAtual  = null;
        $osPdf        = null;
        $materiais    = [];
        $filaTrechos  = [];
        $tudoConcluido = false;

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
                    SELECT ct.sequencia AS ordem, ct.status AS ct_status,
                           t.id, t.pv_montante, t.pv_jusante, t.extensao,
                           t.rua, t.bacia, t.dn, t.contrato,
                           t.status_rede, t.status_repav, t.rede_concluida_em
                    FROM caminhamento_trechos ct
                    JOIN trechos t ON t.id = ct.trecho_id
                    WHERE ct.caminhamento_id = ?
                    ORDER BY ct.sequencia ASC
                ");
                $stmtFila->execute([$caminhamento['id']]);
                $filaTrechos = $stmtFila->fetchAll(PDO::FETCH_ASSOC);

                // Primeiro trecho cuja REDE ainda não foi concluída = trecho atual
                foreach ($filaTrechos as $tc) {
                    if ($tc['ct_status'] !== 'concluido' && $tc['status_rede'] !== 'concluido') {
                        $trechoAtual = $tc;
                        break;
                    }
                }
                $tudoConcluido = ($filaTrechos && !$trechoAtual);

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
                        SELECT mc.nome, mc.unidade, tm.quantidade
                        FROM trecho_materiais tm
                        JOIN materiais_catalogo mc ON mc.id = tm.material_id
                        WHERE tm.trecho_id = ?
                        ORDER BY mc.nome
                    ");
                    $stmtMat->execute([$trechoAtual['id']]);
                    $materiais = $stmtMat->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        }

        // Diário do trecho — achado pelo TRECHO, não pela data (o diário nasce com a data do
        // caminhamento, que quase nunca é CURDATE(); procurar por CURDATE() criava versão 2 vazia).
        $diarioHoje = null;
        if ($equipeId && $trechoAtual) {
            // 1º) diário ainda aberto (rascunho) deste trecho, seja de que dia for
            $stmt3 = $this->db->prepare("
                SELECT id, status, step_atual, versao, data
                FROM diarios_execucao
                WHERE equipe_id = ? AND trecho_id = ? AND status <> 'enviado'
                ORDER BY data DESC, versao DESC
                LIMIT 1
            ");
            $stmt3->execute([$equipeId, $trechoAtual['id']]);
            $diarioHoje = $stmt3->fetch(PDO::FETCH_ASSOC) ?: null;

            // 2º) senão, diário já enviado do mesmo dia (do caminhamento ou de hoje):
            //     nesse caso NÃO se oferece novo diário.
            if (!$diarioHoje) {
                $dataPrevista = $caminhamento['data_execucao'] ?? date('Y-m-d');
                $stmt4 = $this->db->prepare("
                    SELECT id, status, step_atual, versao, data
                    FROM diarios_execucao
                    WHERE equipe_id = ? AND trecho_id = ? AND status = 'enviado'
                      AND (data = ? OR data = CURDATE())
                    ORDER BY data DESC, versao DESC
                    LIMIT 1
                ");
                $stmt4->execute([$equipeId, $trechoAtual['id'], $dataPrevista]);
                $diarioHoje = $stmt4->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        }

        // Devolução pendente da REDE deste trecho (o escritório mandou refazer)
        $devolucao = $trechoAtual
            ? devolucao_pendente($this->db, (int)$trechoAtual['id'], 'rede')
            : null;

        // Base URL do painel para acessar OS PDFs (mesmo banco, painel gerencia os uploads)
        $painelBase = '/BACIN/painel';

        require __DIR__ . '/../views/home.php';
    }

    // --------------------------------------------------------
    // Equipe do dia — quando o executor responde por mais de uma equipe
    // --------------------------------------------------------
    public function escolherEquipe(): void {
        auth_required_executor();
        csrf_verify_executor();

        $autorId  = (int)$_SESSION['usuario_id'];
        $escolhida = (int)($_POST['equipe_id'] ?? 0);

        $valida = false;
        foreach ($this->equipesDoAutor($autorId) as $eq) {
            if ((int)$eq['id'] === $escolhida) { $valida = true; break; }
        }
        if (!$valida) {
            $_SESSION['flash_executor'] = ['tipo' => 'erro', 'msg' => 'Equipe inválida.'];
        } else {
            $_SESSION['executor_equipe_id'] = $escolhida;
        }

        header('Location: ' . EXECUTOR_BASE . '/');
        exit;
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

        // Usa a data_execucao do caminhamento publicado (não a data atual do servidor)
        $stmtCamData = $this->db->prepare("
            SELECT c.data_execucao
            FROM caminhamentos c
            JOIN caminhamento_trechos ct ON ct.caminhamento_id = c.id
            WHERE c.equipe_id = ? AND ct.trecho_id = ?
              AND c.status IN ('publicado','execucao')
            ORDER BY c.data_execucao ASC
            LIMIT 1
        ");
        $stmtCamData->execute([$equipeId, $trechoId]);
        $camRow    = $stmtCamData->fetch(PDO::FETCH_ASSOC);
        $dataDiario = $camRow ? $camRow['data_execucao'] : date('Y-m-d');

        // Evita duplicata (mesmo equipe/trecho/dia na última versão)
        $stmt = $this->db->prepare("
            SELECT id, status, versao FROM diarios_execucao
            WHERE equipe_id = ? AND trecho_id = ? AND data = ?
            ORDER BY versao DESC LIMIT 1
        ");
        $stmt->execute([$equipeId, $trechoId, $dataDiario]);
        $existente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            // Rascunho aberto → continua; já enviado no mesmo dia/trecho → abre o que existe,
            // em vez de criar uma versão 2 vazia.
            header('Location: ' . EXECUTOR_BASE . '/diario/' . $existente['id']);
            exit;
        }

        // Rascunho aberto deste trecho em outro dia (o diário nasce com a data do caminhamento)
        $stmtAberto = $this->db->prepare("
            SELECT id FROM diarios_execucao
            WHERE equipe_id = ? AND trecho_id = ? AND status <> 'enviado'
            ORDER BY data DESC, versao DESC LIMIT 1
        ");
        $stmtAberto->execute([$equipeId, $trechoId]);
        if ($abertoId = $stmtAberto->fetchColumn()) {
            header('Location: ' . EXECUTOR_BASE . '/diario/' . (int)$abertoId);
            exit;
        }

        $versao = 1;

        $ins = $this->db->prepare("
            INSERT INTO diarios_execucao
                (equipe_id, trecho_id, data, autor_id, status, versao, step_atual)
            VALUES (?, ?, ?, ?, 'rascunho', ?, 1)
        ");
        $ins->execute([$equipeId, $trechoId, $dataDiario, $autorId, $versao]);
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
        $cargas         = $this->listar("SELECT dc.*, df.thumb AS foto_thumb FROM diario_cargas dc LEFT JOIN diario_fotos df ON df.id = dc.foto_id WHERE dc.diario_id = ? ORDER BY dc.tipo, dc.numero", [$id]);
        $pontoes        = $this->listar("SELECT dp.*, df.thumb AS foto_thumb FROM diario_pontoes dp LEFT JOIN diario_fotos df ON df.id = dp.foto_id WHERE dp.diario_id = ? ORDER BY dp.id", [$id]);
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

        // Devolução pendente da REDE deste trecho — aparece no topo do diário
        $devolucao = devolucao_pendente($this->db, (int)$diario['trecho_id'], 'rede');

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
        echo json_encode(['ok' => $ok, 'msg' => $this->erroStep]);
    }

    // --------------------------------------------------------
    // Encerrar & enviar diário — dispara todas as integrações
    // --------------------------------------------------------
    public function encerrar(int $id): void {
        auth_required_executor();
        csrf_verify_executor();

        $diario = $this->carregarDiario($id);
        if (!$diario) { http_response_code(404); return; }
        $this->verificarPermissao($diario);

        // Decisão do executor: a rede DESTE trecho terminou ou continua amanhã?
        $escolha = $_POST['rede_concluida'] ?? '';
        if ($escolha !== '0' && $escolha !== '1') {
            $this->telaAviso(
                'Falta dizer como o trecho ficou',
                'Antes de enviar, marque "Terminei o trecho — rede concluída" ou "Continua amanhã".',
                EXECUTOR_BASE . '/diario/' . $id
            );
            return;
        }
        $concluirRede = ($escolha === '1');
        $trechoId     = (int)$diario['trecho_id'];
        $jaEnviado    = ($diario['status'] === 'enviado');
        $redeJaEstavaConcluida = false;

        $this->db->beginTransaction();
        try {
            // 1. Marca como enviado (só na primeira vez — reenviar não reexecuta nada)
            if (!$jaEnviado) {
            $this->db->prepare("UPDATE diarios_execucao SET status = 'enviado' WHERE id = ?")
                     ->execute([$id]);
            }

            if (!$jaEnviado) {
            // 2. GPS → extensão executada no caminhamento_trechos
            $gps = $this->db->prepare("SELECT extensao_calculada_m FROM diario_gps WHERE diario_id = ?");
            $gps->execute([$id]);
            $gpsRow = $gps->fetch(PDO::FETCH_ASSOC);
            if ($gpsRow && $gpsRow['extensao_calculada_m']) {
                $ext = (float)$gpsRow['extensao_calculada_m'];
                // Copia no cabeçalho do diário
                $this->db->prepare("UPDATE diarios_execucao SET extensao_gps_m = ? WHERE id = ?")
                         ->execute([$ext, $id]);
                // Atualiza caminhamento_trechos com extensão real
                $this->db->prepare("
                    UPDATE caminhamento_trechos ct
                    JOIN caminhamentos c ON c.id = ct.caminhamento_id
                    SET ct.extensao_executada_m = ?
                    WHERE ct.trecho_id = ? AND c.equipe_id = ?
                      AND c.data_execucao = ?
                ")->execute([$ext, $diario['trecho_id'], $diario['equipe_id'], $diario['data']]);
            }

            // 3. Equipamentos com problema → status_manutencao
            $equips = $this->listar(
                "SELECT equipamento_id, tipo, obs FROM diario_equipamentos WHERE diario_id = ? AND funcionando = 0",
                [$id]
            );
            foreach ($equips as $eq) {
                $tabela = $eq['tipo'] === 'pesado' ? 'equipamentos_pesados' : 'equipamentos_leves';
                $this->db->prepare("UPDATE {$tabela} SET status_manutencao = 'manutencao', obs_manutencao = ? WHERE id = ?")
                         ->execute([substr($eq['obs'] ?? 'Reportado pelo Executor em ' . $diario['data'], 0, 255), (int)$eq['equipamento_id']]);
            }
            } // fim das integrações do envio

            // 4. Estado da REDE do trecho — quem conclui é quem executou (decisão de 19/09/2026).
            //    Idempotente: trecho já concluído não é reprocessado nem recarimbado.
            $stTrecho = $this->db->prepare("SELECT status_rede FROM trechos WHERE id = ? FOR UPDATE");
            $stTrecho->execute([$trechoId]);
            $statusRedeAtual = (string)($stTrecho->fetchColumn() ?: '');
            $redeJaEstavaConcluida = ($statusRedeAtual === 'concluido');

            if (!$redeJaEstavaConcluida) {
                if ($concluirRede) {
                    // Libera a equipe de ramais e a fila de repavimentação da rede
                    $this->db->prepare("
                        UPDATE trechos
                           SET status_rede = 'concluido',
                               rede_concluida_em = NOW(),
                               status_repav = 'aguardando'
                         WHERE id = ? AND status_rede <> 'concluido'
                    ")->execute([$trechoId]);

                    $this->db->prepare("
                        UPDATE caminhamento_trechos ct
                          JOIN caminhamentos c ON c.id = ct.caminhamento_id
                           SET ct.status = 'concluido'
                         WHERE ct.trecho_id = ? AND c.equipe_id = ?
                           AND c.status IN ('publicado','execucao')
                    ")->execute([$trechoId, (int)$diario['equipe_id']]);
                } else {
                    // Continua amanhã: fica em execução e a fila de repavimentação não é tocada
                    $this->db->prepare("
                        UPDATE trechos SET status_rede = 'execucao'
                         WHERE id = ? AND status_rede <> 'concluido'
                    ")->execute([$trechoId]);

                    $this->db->prepare("
                        UPDATE caminhamento_trechos ct
                          JOIN caminhamentos c ON c.id = ct.caminhamento_id
                           SET ct.status = 'execucao'
                         WHERE ct.trecho_id = ? AND c.equipe_id = ?
                           AND ct.status <> 'concluido'
                           AND c.status IN ('publicado','execucao')
                    ")->execute([$trechoId, (int)$diario['equipe_id']]);
                }
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo "Erro ao encerrar diário: " . htmlspecialchars($e->getMessage());
            return;
        }

        if ($redeJaEstavaConcluida) {
            $_SESSION['flash_executor'] = ['tipo' => 'ok',
                'msg' => 'Este trecho já estava com a rede concluída — nada foi refeito.'];
        } elseif ($concluirRede) {
            $_SESSION['flash_executor'] = ['tipo' => 'ok',
                'msg' => 'Trecho encerrado com a REDE CONCLUÍDA. A equipe de ramais e a de pavimento já podem entrar.'];
        } else {
            $_SESSION['flash_executor'] = ['tipo' => 'aviso',
                'msg' => 'Diário enviado. O trecho continua em execução — a rede NÃO foi marcada como concluída.'];
        }

        header('Location: ' . EXECUTOR_BASE . '/');
        exit;
    }

    // --------------------------------------------------------
    // Tela simples de aviso (mobile) com botão de voltar
    // --------------------------------------------------------
    private function telaAviso(string $titulo, string $texto, string $voltarUrl): void {
        http_response_code(400);
        header('Content-Type: text/html; charset=utf-8');
        $t = htmlspecialchars($titulo);
        $x = htmlspecialchars($texto);
        $u = htmlspecialchars($voltarUrl);
        echo "<!DOCTYPE html><html lang=\"pt-BR\"><head><meta charset=\"UTF-8\">"
           . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">"
           . "<title>{$t}</title><link rel=\"stylesheet\" href=\"" . EXECUTOR_BASE . "/assets/css/executor.css\"></head>"
           . "<body><div class=\"phone\"><div class=\"scroll\">"
           . "<div class=\"info\" style=\"border-color:var(--aviso);margin-top:16px\">"
           . "<div class=\"info-h\"><span class=\"ic i-aviso\">⚠️</span><div><b>{$t}</b><span>{$x}</span></div></div>"
           . "</div><a class=\"btn-start\" href=\"{$u}\">← Voltar ao diário</a>"
           . "</div></div></body></html>";
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

        header('Content-Type: application/json');

        // Diário enviado não recebe mais foto (as outras rotas já bloqueavam)
        $diarioFoto = $this->carregarDiario($diarioId);
        if (!$diarioFoto || !$this->autorizado($diarioFoto)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'Diário não encontrado ou de outra equipe.']);
            return;
        }
        if ($diarioFoto['status'] === 'enviado') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'Diário já enviado — não aceita mais fotos.']);
            return;
        }

        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['ok' => false, 'msg' => 'Erro no upload.']);
            return;
        }

        // Limite de 15 MB
        if ($_FILES['foto']['size'] > 15 * 1024 * 1024) {
            echo json_encode(['ok' => false, 'msg' => 'Arquivo muito grande (máx. 15 MB).']);
            return;
        }

        // Validar MIME real (não confiar na extensão)
        $mimeReal = mime_content_type($_FILES['foto']['tmp_name']);
        $mimesPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];
        if (!in_array($mimeReal, $mimesPermitidos, true)) {
            echo json_encode(['ok' => false, 'msg' => 'Tipo de arquivo não permitido. Use JPG, PNG ou WebP.']);
            return;
        }

        // Extensão segura baseada no MIME (ignora extensão enviada)
        $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
                   'image/heic' => 'heic', 'image/heif' => 'heif'];
        $ext  = $extMap[$mimeReal];
        $base = bin2hex(random_bytes(12));

        $uploadDir = __DIR__ . '/../../../uploads/diario/' . $diarioId . '/';
        $thumbDir  = $uploadDir . 'thumbs/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        if (!is_dir($thumbDir))  mkdir($thumbDir,  0755, true);

        $filename = $base . '.' . $ext;
        $thumb    = $base . '_t.jpg'; // thumbs sempre em JPEG

        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $filename)) {
            echo json_encode(['ok' => false, 'msg' => 'Falha ao salvar arquivo.']);
            return;
        }
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
    /** Todas as equipes ativas em que o executor é o responsável (ele pode ter mais de uma). */
    private function equipesDoAutor(int $autorId): array {
        $stmt = $this->db->prepare("
            SELECT id, nome FROM equipes WHERE responsavel_id = ? AND ativo = 1 ORDER BY id ASC
        ");
        $stmt->execute([$autorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Equipe do dia. Uma equipe só → é ela. Mais de uma → a que o executor escolheu
     * na home (guardada na sessão); enquanto ele não escolher, devolve null.
     */
    private function equipeDoAutor(int $autorId): ?int {
        $equipes = $this->equipesDoAutor($autorId);
        if (!$equipes) return null;
        if (count($equipes) === 1) return (int)$equipes[0]['id'];

        $escolhida = (int)($_SESSION['executor_equipe_id'] ?? 0);
        foreach ($equipes as $eq) {
            if ((int)$eq['id'] === $escolhida) return $escolhida;
        }
        return null;
    }

    /** O diário pertence a alguma das equipes do executor logado? */
    private function autorizado(array $diario): bool {
        $autorId = (int)($_SESSION['usuario_id'] ?? 0);
        foreach ($this->equipesDoAutor($autorId) as $eq) {
            if ((int)$eq['id'] === (int)$diario['equipe_id']) return true;
        }
        return false;
    }

    private function carregarDiario(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM diarios_execucao WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function verificarPermissao(array $diario): void {
        if (!$this->autorizado($diario)) {
            http_response_code(403);
            echo "Acesso negado.";
            exit;
        }
        // Trabalhando neste diário → a equipe dele passa a ser a equipe do dia
        $_SESSION['executor_equipe_id'] = (int)$diario['equipe_id'];
    }

    private function listar(string $sql, array $params): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function processarStep(int $diarioId, int $step, array $diario): bool {
        $this->erroStep = null;
        // Cada step persiste os campos que lhe cabem
        switch ($step) {
            case 1: // Presença inicial — lê presenca[ID]=status direto do FormData
                $presencaPost = $_POST['presenca'] ?? [];
                $this->db->prepare("DELETE FROM diario_presencas WHERE diario_id = ?")->execute([$diarioId]);
                $statusValidos = ['presente', 'ausente', 'atrasou', 'saiu_cedo'];
                foreach ($presencaPost as $fid => $status) {
                    $s   = in_array($status, $statusValidos) ? $status : 'presente';
                    $obs = substr($_POST['obs_' . (int)$fid] ?? '', 0, 255);
                    $this->db->prepare("
                        INSERT INTO diario_presencas (diario_id, funcionario_id, status, obs)
                        VALUES (?, ?, ?, ?)
                    ")->execute([$diarioId, (int)$fid, $s, $obs]);
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

            case 11: // Interferências — substitui o conjunto (salvar duas vezes não duplica)
                $tipos_validos = ['pedra','agua_na_vala','ramal_de_agua','rede_de_agua','rede_pluvial','rompimento_de_rede','rede_cloacal_existente','rede_logica','rede_eletrica','outros'];
                $linhas = []; $removidos = 0;
                foreach ($_POST['interf_tipo'] ?? [] as $idx => $tipo) {
                    if (!empty($_POST['interf_remover'][$idx])) { $removidos++; continue; }
                    if (!in_array($tipo, $tipos_validos, true)) continue;
                    $esp    = substr(trim((string)($_POST['interf_esp'][$idx] ?? '')), 0, 255);
                    $lat    = $this->numeroDecimal($_POST['interf_lat'][$idx] ?? null);
                    $lng    = $this->numeroDecimal($_POST['interf_lng'][$idx] ?? null);
                    $fotoId = !empty($_POST['interf_foto'][$idx]) ? (int)$_POST['interf_foto'][$idx] : null;
                    $linhas[] = [$diarioId, $tipo, $esp, $lat, $lng, $fotoId];
                }
                if (!$this->podeSubstituir('diario_interferencias', $diarioId, count($linhas), $removidos, 'interferência')) return false;
                return $this->substituirConjunto(
                    'diario_interferencias', $diarioId, null,
                    "INSERT INTO diario_interferencias (diario_id,tipo,especificacao,lat,lng,foto_id) VALUES (?,?,?,?,?,?)",
                    $linhas
                );

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

            case 14: // Pontões de ramal (espera) — lançamento da rede até a cota do ramal
                // Substitui o conjunto (mesmo padrão dos passos 1 e 6) para não duplicar a cada
                // salvamento, mas NUNCA apaga tudo quando a tela vem vazia.
                $linhas = []; $removidos = 0;
                foreach ($_POST['pontao_res'] ?? [] as $idx => $nro) {
                    if (!empty($_POST['pontao_remover'][$idx])) { $removidos++; continue; }

                    $nro    = substr(trim((string)$nro), 0, 50);
                    $profIn = trim((string)($_POST['pontao_prof'][$idx] ?? ''));
                    $prof   = $this->numeroDecimal($profIn);
                    $fotoId = !empty($_POST['pontao_foto'][$idx]) ? (int)$_POST['pontao_foto'][$idx] : null;
                    $lat    = $this->numeroDecimal($_POST['pontao_lat'][$idx] ?? null);
                    $lng    = $this->numeroDecimal($_POST['pontao_lng'][$idx] ?? null);
                    $obs    = substr(trim((string)($_POST['pontao_obs'][$idx] ?? '')), 0, 255);

                    // Linha totalmente vazia (campo em branco deixado pelo "+ Adicionar pontão") não vira registro
                    if ($nro === '' && $profIn === '' && $fotoId === null && $obs === '' && $lat === null) continue;

                    $pos = count($linhas) + 1;
                    if ($nro === '') {
                        return $this->falhar('Pontão ' . $pos . ': falta o nº do imóvel. Todo pontão precisa do número da casa.');
                    }
                    if ($profIn !== '' && $prof === null) {
                        return $this->falhar('Pontão ' . $pos . ' (imóvel ' . $nro . '): profundidade inválida. Use números, ex.: 0,80.');
                    }
                    if ($prof !== null && ($prof < 0.40 || $prof > 4.00)) {
                        return $this->falhar('Pontão ' . $pos . ' (imóvel ' . $nro . '): profundidade de ' .
                            number_format($prof, 2, ',', '.') . ' m está fora do que se cava em obra. Informe entre 0,40 m e 4,00 m.');
                    }

                    $linhas[] = [$diarioId, $nro, $prof, $fotoId, $lat, $lng, $obs !== '' ? $obs : null];
                }
                if (!$this->podeSubstituir('diario_pontoes', $diarioId, count($linhas), $removidos, 'pontão')) return false;
                return $this->substituirConjunto(
                    'diario_pontoes', $diarioId, null,
                    "INSERT INTO diario_pontoes (diario_id, nro_residencia, profundidade_m, foto_id, lat, lng, observacao)
                     VALUES (?,?,?,?,?,?,?)",
                    $linhas
                );

            case 15: // Cargas bota-fora
            case 16: // Cargas importado
                // Substitui as cargas DESTE tipo (salvar duas vezes não duplica)
                $tipo = ($step === 15) ? 'bota_fora' : 'importado';
                $linhas = []; $removidos = 0; $num = 1;
                foreach ($_POST['carga_foto'] ?? [] as $idx => $fotoId) {
                    if (!empty($_POST['carga_remover'][$idx])) { $removidos++; continue; }
                    $fid = (int)$fotoId;
                    if ($fid <= 0) continue;
                    $linhas[] = [$diarioId, $tipo, $num++, $fid];
                }
                if (!$this->podeSubstituir('diario_cargas', $diarioId, count($linhas), $removidos, 'carga', $tipo)) return false;
                return $this->substituirConjunto(
                    'diario_cargas', $diarioId, $tipo,
                    "INSERT INTO diario_cargas (diario_id,tipo,numero,foto_id) VALUES (?,?,?,?)",
                    $linhas
                );

            case 17: // Reaterros — substitui o conjunto (salvar duas vezes não duplica)
                $tipos_validos = ['lastro_brita','colchao_areia_po_brita','reaterro_importado','compactacao_importado','reaterro_local','compactacao_local','base_brita_graduada','compactacao_base'];
                $linhas = []; $removidos = 0;
                foreach ($_POST['reat_tipo'] ?? [] as $idx => $tipo) {
                    if (!empty($_POST['reat_remover'][$idx])) { $removidos++; continue; }
                    if (!in_array($tipo, $tipos_validos, true)) continue;
                    $espIn  = trim((string)($_POST['reat_esp'][$idx] ?? ''));
                    $esp    = $this->numeroDecimal($espIn);
                    if ($espIn !== '' && $esp === null) {
                        return $this->falhar('Camada ' . (count($linhas) + 1) . ': espessura inválida. Use números, ex.: 10 ou 12,5.');
                    }
                    if ($esp !== null && ($esp <= 0 || $esp > 200)) {
                        return $this->falhar('Camada ' . (count($linhas) + 1) . ': espessura de ' .
                            number_format($esp, 1, ',', '.') . ' cm não confere. Informe entre 1 e 200 cm.');
                    }
                    $fotoId = !empty($_POST['reat_foto'][$idx]) ? (int)$_POST['reat_foto'][$idx]  : null;
                    $linhas[] = [$diarioId, $tipo, $esp, $fotoId];
                }
                if (!$this->podeSubstituir('diario_reaterros', $diarioId, count($linhas), $removidos, 'camada de reaterro')) return false;
                return $this->substituirConjunto(
                    'diario_reaterros', $diarioId, null,
                    "INSERT INTO diario_reaterros (diario_id,tipo,espessura_cm,foto_id) VALUES (?,?,?,?)",
                    $linhas
                );

            case 18: // Ramal completo — NÃO é mais lançado no diário de rede (PA25, 18/09/2026)
                // A equipe de ramais registra o ramal no app /BACIN/executor-ramais.
                // diario_ramais virou histórico: nenhum INSERT aqui. O passo só avança o step_atual.
                return true;

            case 3: // Estoque na frente — persiste para alertas
                $ok      = isset($_POST['estoque_ok']) ? (int)$_POST['estoque_ok'] : null;
                $faltando = substr(trim($_POST['materiais_faltando'] ?? ''), 0, 2000);
                if ($ok !== null) {
                    $this->db->prepare("
                        UPDATE diarios_execucao SET step3_estoque_ok = ?, step3_materiais_faltando = ? WHERE id = ?
                    ")->execute([$ok, $faltando ?: null, $diarioId]);
                }
                return true;

            default:
                return true; // Passos só-foto (4-5, 7-10, 19-21) — foto já foi salva pelo uploadFoto
        }
    }

    /** Recusa o passo com uma mensagem em linguagem de obra. */
    private function falhar(string $msg): bool {
        $this->erroStep = $msg;
        return false;
    }

    private const TABELAS_CONJUNTO = [
        'diario_pontoes', 'diario_interferencias', 'diario_cargas', 'diario_reaterros',
    ];

    private function contarLinhas(string $tabela, int $diarioId, ?string $tipoCarga = null): int {
        if (!in_array($tabela, self::TABELAS_CONJUNTO, true)) return 0;
        if ($tipoCarga === null) {
            $st = $this->db->prepare("SELECT COUNT(*) FROM {$tabela} WHERE diario_id = ?");
            $st->execute([$diarioId]);
        } else {
            $st = $this->db->prepare("SELECT COUNT(*) FROM {$tabela} WHERE diario_id = ? AND tipo = ?");
            $st->execute([$diarioId, $tipoCarga]);
        }
        return (int)$st->fetchColumn();
    }

    /**
     * Protege o padrão DELETE + INSERT: salvar com a tela vazia NÃO pode apagar o que já foi
     * lançado. Só libera quando veio alguma linha preenchida, quando houve remoção explícita
     * ou quando ainda não há nada gravado.
     */
    private function podeSubstituir(string $tabela, int $diarioId, int $novas, int $removidos,
                                    string $oQue, ?string $tipoCarga = null): bool {
        if ($novas > 0 || $removidos > 0) return true;
        if ($this->contarLinhas($tabela, $diarioId, $tipoCarga) === 0) return true;
        return $this->falhar('Nenhum item de "' . $oQue . '" foi preenchido — o que já estava lançado foi mantido. '
            . 'Para tirar um item, use o botão 🗑 Remover dele e salve de novo.');
    }

    /** DELETE + INSERT do conjunto, em transação. */
    private function substituirConjunto(string $tabela, int $diarioId, ?string $tipoCarga,
                                        string $sqlInsert, array $linhas): bool {
        if (!in_array($tabela, self::TABELAS_CONJUNTO, true)) return false;
        $this->db->beginTransaction();
        try {
            if ($tipoCarga === null) {
                $this->db->prepare("DELETE FROM {$tabela} WHERE diario_id = ?")->execute([$diarioId]);
            } else {
                $this->db->prepare("DELETE FROM {$tabela} WHERE diario_id = ? AND tipo = ?")
                         ->execute([$diarioId, $tipoCarga]);
            }
            $ins = $this->db->prepare($sqlInsert);
            foreach ($linhas as $l) $ins->execute($l);
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return $this->falhar('Não deu para salvar agora. Tente de novo em alguns segundos.');
        }
    }

    /**
     * Converte número vindo do celular aceitando vírgula decimal ("0,80" → 0.80).
     * Retorna null quando vazio ou não numérico.
     */
    private function numeroDecimal($valor): ?float {
        if ($valor === null) return null;
        $v = str_replace(' ', '', trim((string)$valor));
        if ($v === '') return null;
        if (strpos($v, ',') !== false) {
            $v = str_replace(',', '.', str_replace('.', '', $v)); // "1.234,56" → "1234.56"
        }
        return is_numeric($v) ? (float)$v : null;
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
        if (!$this->autorizado($diario)) return ['ok' => false, 'tipo' => $tipo, 'msg' => 'Diário de outra equipe'];
        if ($diario['status'] === 'enviado') return ['ok' => false, 'tipo' => $tipo, 'msg' => 'Diário já enviado'];

        // Mapeia tipo do item para step e popula $_POST temporariamente
        $_POST = array_merge($_POST, $item['dados'] ?? []);
        $step  = (int)($item['step'] ?? 0);
        $ok    = $this->processarStep($diarioId, $step, $diario);
        return ['ok' => $ok, 'tipo' => $tipo, 'msg' => $this->erroStep];
    }
}
