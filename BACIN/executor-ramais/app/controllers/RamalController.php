<?php
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
// Devoluções do Planejador (PA26) — helper compartilhado com o Painel
require_once dirname(__DIR__, 3) . '/painel/app/helpers/devolucoes.php';

/**
 * App do Executor de Ramais (nível 9).
 *
 * Uma FRENTE = uma rua (ou um trecho do caminhamento) num dia, de uma equipe.
 * Dentro da frente o executor lança um RAMAL por imóvel, com pavimento e
 * comprimento em via e em calçada, mais três fotos (ramal, lançamento na via,
 * ramal pronto).
 */
class RamalController
{
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

    public const FOTOS = [
        'ramal'          => 'Foto do ramal',
        'lancamento_via' => 'Foto do lançamento na via',
        'acabado'        => 'Foto do ramal pronto',
    ];

    private PDO $db;
    private string $uploadsDir;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
        $this->uploadsDir = dirname(__DIR__, 2) . '/uploads/ramais';
    }

    /* ==========================================================
       HOME — frentes do dia e trechos disponíveis
       ========================================================== */
    public function home(): void
    {
        auth_required_ramais();
        csrf_token_ramais();

        $autorId  = (int)$_SESSION['usuario_id'];
        $equipeId = $this->equipeDoAutor($autorId);

        $frentes = [];
        $trechos = [];

        if ($equipeId) {
            $frentes = $this->listar("
                SELECT f.*, t.pv_montante, t.pv_jusante, t.rua AS trecho_rua,
                       (SELECT COUNT(*) FROM ramais r WHERE r.frente_id = f.id) AS ramais_lancados
                FROM frentes_ramais f
                LEFT JOIN trechos t ON t.id = f.trecho_id
                WHERE f.equipe_id = ?
                ORDER BY f.data DESC, f.id DESC
                LIMIT 20
            ", [$equipeId]);

            // O ramal é executado DEPOIS da rede: só entram trechos com a rede
            // concluída. A frente também pode ser aberta só com o nome da rua.
            $trechos = $this->listar("
                SELECT t.id, t.pv_montante, t.pv_jusante, t.rua, t.extensao, t.ramais,
                       (SELECT COUNT(*) FROM diario_pontoes p
                          JOIN diarios_execucao de ON de.id = p.diario_id
                         WHERE de.trecho_id = t.id) AS pontoes
                FROM trechos t
                WHERE t.status_rede = 'concluido'
                ORDER BY t.rua, t.pv_montante
                LIMIT 200
            ");
        }

        $equipe = $equipeId
            ? $this->fetch1("SELECT id, nome FROM equipes WHERE id = ?", [$equipeId])
            : null;

        // A repavimentação dos ramais NÃO é desta equipe. O que interessa aqui é a
        // devolução da REDE: se a vala da rede voltou, o ramal daquele trecho pode
        // ter ido junto. É aviso, não bloqueio — o executor continua lançando.
        $devolucoesTrechos = [];   // trecho_id => devolução pendente da rede
        foreach ($frentes as $f) {
            $tid = (int)($f['trecho_id'] ?? 0);
            if ($tid > 0 && !array_key_exists($tid, $devolucoesTrechos)) {
                $dev = devolucao_pendente($this->db, $tid, 'rede');
                if ($dev) {
                    $dev['trecho_nome'] = trim(($f['pv_montante'] ?? '') . ' → ' . ($f['pv_jusante'] ?: '—'));
                    $devolucoesTrechos[$tid] = $dev;
                }
            }
        }

        $csrf = csrf_token_ramais();
        require __DIR__ . '/../views/home.php';
    }

    /* ==========================================================
       NOVA FRENTE
       ========================================================== */
    public function novaFrente(): void
    {
        auth_required_ramais();
        csrf_verify_ramais();

        $autorId  = (int)$_SESSION['usuario_id'];
        $equipeId = $this->equipeDoAutor($autorId);
        if (!$equipeId) {
            $this->erro('Você não está como responsável por nenhuma equipe ativa. Fale com o planejador.');
            return;
        }

        $trechoId   = (int)($_POST['trecho_id'] ?? 0) ?: null;
        $logradouro = trim((string)($_POST['logradouro'] ?? ''));

        if ($trechoId) {
            $trecho = $this->fetch1("SELECT id, rua FROM trechos WHERE id = ?", [$trechoId]);
            if (!$trecho) {
                $this->erro('Trecho não encontrado.');
                return;
            }
            if ($logradouro === '') {
                $logradouro = (string)($trecho['rua'] ?? '');
            }
        }

        if ($logradouro === '') {
            $this->erro('Informe a rua (ou escolha um trecho).');
            return;
        }
        $logradouro = mb_substr($logradouro, 0, 160);

        // Já existe frente aberta hoje para a mesma rua/trecho? Reabre.
        $aberta = $this->fetch1("
            SELECT id FROM frentes_ramais
            WHERE equipe_id = ? AND data = CURDATE() AND status = 'rascunho'
              AND logradouro = ? AND (trecho_id <=> ?)
            ORDER BY id DESC LIMIT 1
        ", [$equipeId, $logradouro, $trechoId]);

        if ($aberta) {
            header('Location: ' . RAMAIS_BASE . '/frente/' . (int)$aberta['id']);
            exit;
        }

        $ins = $this->db->prepare("
            INSERT INTO frentes_ramais (equipe_id, autor_id, trecho_id, logradouro, data, status)
            VALUES (?, ?, ?, ?, CURDATE(), 'rascunho')
        ");
        $ins->execute([$equipeId, $autorId, $trechoId, $logradouro]);

        header('Location: ' . RAMAIS_BASE . '/frente/' . (int)$this->db->lastInsertId());
        exit;
    }

    /* ==========================================================
       ABRIR FRENTE (lista de ramais + formulário)
       ========================================================== */
    public function abrirFrente(int $id, int $ramalId = 0): void
    {
        auth_required_ramais();

        $frente = $this->carregarFrente($id);
        if (!$frente) {
            $this->erro('Frente não encontrada.', 404);
            return;
        }
        $this->verificarPermissao($frente);

        $trecho = $frente['trecho_id']
            ? $this->fetch1("SELECT * FROM trechos WHERE id = ?", [$frente['trecho_id']])
            : null;

        $ramais = $this->listar("
            SELECT * FROM ramais WHERE frente_id = ? ORDER BY sequencia ASC, id ASC
        ", [$id]);

        $fotos = [];
        if ($ramais) {
            $ids = array_column($ramais, 'id');
            $in  = implode(',', array_fill(0, count($ids), '?'));
            foreach ($this->listar("SELECT * FROM ramal_fotos WHERE ramal_id IN ($in) ORDER BY id", $ids) as $f) {
                $fotos[(int)$f['ramal_id']][$f['tipo']] = $f;
            }
        }

        // Pontões que a equipe de rede deixou neste trecho (passo 14 do diário
        // de rede): é o ponto de partida do ramal.
        $pontoes = [];
        if ($frente['trecho_id']) {
            $pontoes = $this->listar("
                SELECT p.id, p.nro_residencia, p.profundidade_m, p.observacao,
                       p.lat, p.lng, f.arquivo, f.thumb, de.data AS data_rede
                FROM diario_pontoes p
                JOIN diarios_execucao de ON de.id = p.diario_id
                LEFT JOIN diario_fotos f ON f.id = p.foto_id
                WHERE de.trecho_id = ?
                ORDER BY p.id
            ", [$frente['trecho_id']]);

            $jaLancados = array_map(
                fn($r) => mb_strtolower(trim((string)$r['numero_imovel'])),
                $ramais
            );
            foreach ($pontoes as $i => $p) {
                $pontoes[$i]['ja_lancado'] = in_array(
                    mb_strtolower(trim((string)$p['nro_residencia'])), $jaLancados, true
                );
                $pontoes[$i]['foto_url'] = $p['arquivo'] ? $this->urlFotoRede($p['arquivo'], $p['thumb']) : null;
            }
        }

        $emEdicao = null;
        if ($ramalId) {
            foreach ($ramais as $r) {
                if ((int)$r['id'] === $ramalId) { $emEdicao = $r; break; }
            }
            if (!$emEdicao) {
                $this->erro('Ramal não encontrado nesta frente.', 404);
                return;
            }
        }

        $totalVia     = 0.0;
        $totalCalcada = 0.0;
        foreach ($ramais as $r) {
            $totalVia     += (float)$r['comprimento_via_m'];
            $totalCalcada += (float)$r['comprimento_calcada_m'];
        }

        // Aviso (não bloqueio): a rede deste trecho foi devolvida pelo escritório
        $devolucao = $frente['trecho_id']
            ? devolucao_pendente($this->db, (int)$frente['trecho_id'], 'rede')
            : null;

        $csrf = csrf_token_ramais();
        require __DIR__ . '/../views/frente/preencher.php';
    }

    /* ==========================================================
       SALVAR RAMAL (cria ou edita) — multipart com até 3 fotos
       ========================================================== */
    public function salvarRamal(): void
    {
        auth_required_ramais();
        csrf_verify_ramais();

        $frenteId = (int)($_POST['frente_id'] ?? 0);
        $frente   = $this->carregarFrente($frenteId);
        if (!$frente) {
            $this->erro('Frente não encontrada.', 404);
            return;
        }
        $this->verificarPermissao($frente);
        if ($frente['status'] === 'enviado') {
            $this->erro('Esta frente já foi encerrada e não aceita mais lançamentos.');
            return;
        }

        $ramalId = (int)($_POST['ramal_id'] ?? 0);
        if ($ramalId && !$this->ramalDaFrente($ramalId, $frenteId)) {
            $this->erro('Ramal não pertence a esta frente.', 403);
            return;
        }

        $numero = trim((string)($_POST['numero_imovel'] ?? ''));
        if ($numero === '') {
            $this->erro('Informe o número do imóvel do ramal.');
            return;
        }
        $numero = mb_substr($numero, 0, 30);

        $pavVia     = $this->enumOuNulo($_POST['pavimento_via'] ?? '', self::PAV_VIA);
        $pavCalcada = $this->enumOuNulo($_POST['pavimento_calcada'] ?? '', self::PAV_CALCADA);
        $compVia     = $this->metros($_POST['comprimento_via_m'] ?? '');
        $compCalcada = $this->metros($_POST['comprimento_calcada_m'] ?? '');
        $obs = mb_substr(trim((string)($_POST['observacao'] ?? '')), 0, 255);
        $lat = $this->coord($_POST['lat'] ?? '');
        $lng = $this->coord($_POST['lng'] ?? '');

        if ($ramalId) {
            $upd = $this->db->prepare("
                UPDATE ramais
                   SET numero_imovel = ?, pavimento_via = ?, comprimento_via_m = ?,
                       pavimento_calcada = ?, comprimento_calcada_m = ?, observacao = ?,
                       lat = COALESCE(?, lat), lng = COALESCE(?, lng)
                 WHERE id = ? AND frente_id = ?
            ");
            $upd->execute([$numero, $pavVia, $compVia, $pavCalcada, $compCalcada,
                           $obs ?: null, $lat, $lng, $ramalId, $frenteId]);
        } else {
            $seq = (int)$this->db->query("SELECT COALESCE(MAX(sequencia),0)+1 FROM ramais WHERE frente_id = " . (int)$frenteId)
                                 ->fetchColumn();
            $ins = $this->db->prepare("
                INSERT INTO ramais (frente_id, sequencia, numero_imovel, pavimento_via, comprimento_via_m,
                                    pavimento_calcada, comprimento_calcada_m, observacao, lat, lng)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$frenteId, $seq, $numero, $pavVia, $compVia,
                           $pavCalcada, $compCalcada, $obs ?: null, $lat, $lng]);
            $ramalId = (int)$this->db->lastInsertId();
        }

        $avisos = [];
        foreach (array_keys(self::FOTOS) as $tipo) {
            $campo = 'foto_' . $tipo;
            if (empty($_FILES[$campo]['tmp_name']) || $_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
                continue;
            }
            $msg = $this->salvarFoto($ramalId, $tipo, $_FILES[$campo], $lat, $lng, $_POST['ts'] ?? '');
            if ($msg !== null) {
                $avisos[] = self::FOTOS[$tipo] . ': ' . $msg;
            }
        }

        $_SESSION['flash_ramais'] = $avisos
            ? 'Ramal salvo, mas ' . implode(' / ', $avisos)
            : 'Ramal ' . $numero . ' salvo.';

        header('Location: ' . RAMAIS_BASE . '/frente/' . $frenteId);
        exit;
    }

    /* ==========================================================
       EXCLUIR RAMAL (só com a frente em rascunho)
       ========================================================== */
    public function excluirRamal(): void
    {
        auth_required_ramais();
        csrf_verify_ramais();

        $frenteId = (int)($_POST['frente_id'] ?? 0);
        $ramalId  = (int)($_POST['ramal_id'] ?? 0);

        $frente = $this->carregarFrente($frenteId);
        if (!$frente) { $this->erro('Frente não encontrada.', 404); return; }
        $this->verificarPermissao($frente);
        if ($frente['status'] === 'enviado') {
            $this->erro('Frente encerrada: não é possível excluir ramais.');
            return;
        }
        if (!$this->ramalDaFrente($ramalId, $frenteId)) {
            $this->erro('Ramal não pertence a esta frente.', 403);
            return;
        }

        foreach ($this->listar("SELECT filename, thumb FROM ramal_fotos WHERE ramal_id = ?", [$ramalId]) as $f) {
            @unlink($this->uploadsDir . '/' . $f['filename']);
            if ($f['thumb']) @unlink($this->uploadsDir . '/thumbs/' . $f['thumb']);
        }
        $this->db->prepare("DELETE FROM ramais WHERE id = ? AND frente_id = ?")->execute([$ramalId, $frenteId]);

        $_SESSION['flash_ramais'] = 'Ramal excluído.';
        header('Location: ' . RAMAIS_BASE . '/frente/' . $frenteId);
        exit;
    }

    /* ==========================================================
       ENCERRAR FRENTE
       ========================================================== */
    public function encerrarFrente(int $id): void
    {
        auth_required_ramais();
        csrf_verify_ramais();

        $frente = $this->carregarFrente($id);
        if (!$frente) { $this->erro('Frente não encontrada.', 404); return; }
        $this->verificarPermissao($frente);
        if ($frente['status'] === 'enviado') {
            header('Location: ' . RAMAIS_BASE . '/frente/' . $id);
            exit;
        }

        $tot = $this->fetch1("
            SELECT COUNT(*) AS qtd,
                   COALESCE(SUM(comprimento_via_m),0)     AS via,
                   COALESCE(SUM(comprimento_calcada_m),0) AS calcada
            FROM ramais WHERE frente_id = ?
        ", [$id]);

        if ((int)$tot['qtd'] === 0) {
            $_SESSION['flash_ramais'] = 'Lance pelo menos um ramal antes de encerrar a frente.';
            header('Location: ' . RAMAIS_BASE . '/frente/' . $id);
            exit;
        }

        $obs = mb_substr(trim((string)($_POST['obs'] ?? '')), 0, 2000);

        $upd = $this->db->prepare("
            UPDATE frentes_ramais
               SET status = 'enviado', qtd_ramais = ?, total_via_m = ?, total_calcada_m = ?, obs = ?
             WHERE id = ? AND equipe_id = ?
        ");
        $upd->execute([(int)$tot['qtd'], $tot['via'], $tot['calcada'], $obs ?: null, $id, (int)$frente['equipe_id']]);

        $_SESSION['flash_ramais'] = 'Frente encerrada e enviada ao escritório: '
            . (int)$tot['qtd'] . ' ramal(is).';
        header('Location: ' . RAMAIS_BASE . '/');
        exit;
    }

    /* ==========================================================
       Helpers
       ========================================================== */
    private function salvarFoto(int $ramalId, string $tipo, array $arquivo, ?string $lat, ?string $lng, string $ts): ?string
    {
        if ($arquivo['size'] > 12 * 1024 * 1024) {
            return 'arquivo maior que 12 MB não foi enviado.';
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = (string)$finfo->file($arquivo['tmp_name']);
        $extPorMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extPorMime[$mime])) {
            return 'formato não aceito (use foto JPG, PNG ou WebP).';
        }
        $ext = $extPorMime[$mime];

        if (!is_dir($this->uploadsDir))            mkdir($this->uploadsDir, 0755, true);
        if (!is_dir($this->uploadsDir . '/thumbs')) mkdir($this->uploadsDir . '/thumbs', 0755, true);

        $nome = date('Ymd_His') . '_r' . $ramalId . '_' . $tipo . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($arquivo['tmp_name'], $this->uploadsDir . '/' . $nome)) {
            return 'falha ao salvar a foto.';
        }

        $this->redimensionar($this->uploadsDir . '/' . $nome, $this->uploadsDir . '/' . $nome, 1600);
        $thumbOk = $this->redimensionar($this->uploadsDir . '/' . $nome, $this->uploadsDir . '/thumbs/' . $nome, 400);

        // uma foto por tipo: a nova substitui a anterior
        $antiga = $this->fetch1("SELECT filename, thumb FROM ramal_fotos WHERE ramal_id = ? AND tipo = ?", [$ramalId, $tipo]);
        if ($antiga) {
            @unlink($this->uploadsDir . '/' . $antiga['filename']);
            if ($antiga['thumb']) @unlink($this->uploadsDir . '/thumbs/' . $antiga['thumb']);
            $this->db->prepare("DELETE FROM ramal_fotos WHERE ramal_id = ? AND tipo = ?")->execute([$ramalId, $tipo]);
        }

        $ins = $this->db->prepare("
            INSERT INTO ramal_fotos (ramal_id, tipo, filename, thumb, lat, lng, captured_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([$ramalId, $tipo, $nome, $thumbOk ? $nome : null, $lat, $lng,
                       preg_replace('/[^0-9:\-T\.Z ]/', '', $ts) ?: null]);

        return null;
    }

    private function redimensionar(string $origem, string $destino, int $maior): bool
    {
        if (!function_exists('imagecreatefromjpeg')) return false;
        $info = @getimagesize($origem);
        if (!$info) return false;

        [$w, $h, $tipo] = $info;
        $img = match ($tipo) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($origem),
            IMAGETYPE_PNG  => @imagecreatefrompng($origem),
            IMAGETYPE_WEBP => @imagecreatefromwebp($origem),
            default        => null,
        };
        if (!$img) return false;

        $escala = min(1, $maior / max($w, $h));
        $nw = max(1, (int)round($w * $escala));
        $nh = max(1, (int)round($h * $escala));

        $canvas = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($canvas, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $ok = match ($tipo) {
            IMAGETYPE_JPEG => imagejpeg($canvas, $destino, 82),
            IMAGETYPE_PNG  => imagepng($canvas, $destino, 6),
            IMAGETYPE_WEBP => imagewebp($canvas, $destino, 82),
            default        => false,
        };
        imagedestroy($canvas);
        imagedestroy($img);
        return (bool)$ok;
    }

    /**
     * A foto do executor de rede é gravada em /BACIN/uploads/diario/... mas as
     * telas dele apontam para /BACIN/executor/uploads/... (defeito conhecido).
     * Aqui descobrimos no disco qual dos dois caminhos existe.
     */
    private function urlFotoRede(string $arquivo, ?string $thumb): ?string
    {
        $raiz = dirname(__DIR__, 4);              // .../BACIN
        foreach ([$thumb, $arquivo] as $rel) {
            if (!$rel) continue;
            foreach (['/executor/uploads/', '/uploads/'] as $prefixo) {
                if (is_file($raiz . $prefixo . $rel)) {
                    return '/BACIN' . $prefixo . $rel;
                }
            }
        }
        return null;
    }

    private function enumOuNulo(string $valor, array $permitidos): ?string
    {
        return isset($permitidos[$valor]) ? $valor : null;
    }

    private function metros($valor): float
    {
        $n = (float)str_replace(',', '.', preg_replace('/[^0-9,.\-]/', '', (string)$valor));
        if ($n < 0)     $n = 0;
        if ($n > 999999) $n = 999999;
        return round($n, 2);
    }

    private function coord($valor): ?string
    {
        $v = preg_replace('/[^0-9.\-]/', '', (string)$valor);
        return ($v === '' || !is_numeric($v)) ? null : $v;
    }

    private function equipeDoAutor(int $autorId): ?int
    {
        $stmt = $this->db->prepare("
            SELECT id FROM equipes WHERE responsavel_id = ? AND ativo = 1 ORDER BY id ASC LIMIT 1
        ");
        $stmt->execute([$autorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id'] : null;
    }

    private function carregarFrente(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM frentes_ramais WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** A frente é da equipe do executor logado (ou foi criada por ele). */
    private function verificarPermissao(array $frente): void
    {
        $autorId  = (int)$_SESSION['usuario_id'];
        $equipeId = $this->equipeDoAutor($autorId);
        if ((int)$frente['equipe_id'] !== (int)$equipeId && (int)$frente['autor_id'] !== $autorId) {
            http_response_code(403);
            echo 'Acesso negado.';
            exit;
        }
    }

    private function ramalDaFrente(int $ramalId, int $frenteId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM ramais WHERE id = ? AND frente_id = ?");
        $stmt->execute([$ramalId, $frenteId]);
        return (bool)$stmt->fetchColumn();
    }

    private function listar(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fetch1(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function erro(string $msg, int $codigo = 400): void
    {
        http_response_code($codigo);
        $_SESSION['flash_ramais'] = $msg;
        $voltar = isset($_POST['frente_id']) && (int)$_POST['frente_id'] > 0
            ? RAMAIS_BASE . '/frente/' . (int)$_POST['frente_id']
            : RAMAIS_BASE . '/';
        echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
           . '<body style="font:16px system-ui;background:#0b1c2d;color:#fff;padding:24px">'
           . '<p>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p>'
           . '<p><a style="color:#ffb4ae" href="' . htmlspecialchars($voltar, ENT_QUOTES, 'UTF-8') . '">Voltar</a></p>';
    }
}
