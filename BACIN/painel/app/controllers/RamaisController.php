<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';

/**
 * PA24 — Acompanhamento das frentes do Executor de Ramais (nível 9).
 *
 * Trabalha sobre frentes_ramais / ramais / ramal_fotos.
 * NÃO confundir com `diario_ramais` (ramais lançados dentro do diário
 * do executor de REDE).
 *
 * Relatórios do painel são SEMPRE físicos — nenhum valor em R$.
 */
class RamaisController
{
    /** Caminho público das fotos enviadas pelo app do executor de ramais. */
    public const FOTOS_BASE  = '/BACIN/executor-ramais/uploads/ramais';
    public const THUMBS_BASE = '/BACIN/executor-ramais/uploads/ramais/thumbs';

    /* =====================================================
       RÓTULOS DOS ENUMS — português legível
    ===================================================== */
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

    public const STATUS_FRENTE = [
        'rascunho' => 'Rascunho',
        'enviado'  => 'Enviado',
    ];

    public const TIPO_FOTO = [
        'ramal'          => 'Ramal',
        'lancamento_via' => 'Lançamento na via',
        'acabado'        => 'Acabado',
    ];

    public static function labelPavVia(?string $v): string
    {
        if ($v === null || $v === '') return '—';
        return self::PAV_VIA[$v] ?? $v;
    }

    public static function labelPavCalcada(?string $v): string
    {
        if ($v === null || $v === '') return '—';
        return self::PAV_CALCADA[$v] ?? $v;
    }

    public static function labelStatus(?string $v): string
    {
        if ($v === null || $v === '') return '—';
        return self::STATUS_FRENTE[$v] ?? $v;
    }

    public static function labelTipoFoto(?string $v): string
    {
        if ($v === null || $v === '') return '—';
        return self::TIPO_FOTO[$v] ?? $v;
    }

    /** Número com vírgula decimal (padrão brasileiro). */
    public static function num($valor, int $casas = 2): string
    {
        return number_format((float)$valor, $casas, ',', '.');
    }

    /** Data aaaa-mm-dd → dd/mm/aaaa (nunca quebra com valor inválido). */
    public static function dataBr(?string $iso): string
    {
        if (empty($iso)) return '—';
        $ts = strtotime($iso);
        return $ts ? date('d/m/Y', $ts) : '—';
    }

    /** Valida aaaa-mm-dd vindo de $_GET; devolve null se inválida. */
    private static function dataGet(string $chave): ?string
    {
        $v = trim((string)($_GET[$chave] ?? ''));
        if ($v === '') return null;
        $d = DateTime::createFromFormat('Y-m-d', $v);
        return ($d && $d->format('Y-m-d') === $v) ? $v : null;
    }

    /** Descrição do local da frente: trecho (PV→PV + rua) ou logradouro. */
    public static function descricaoLocal(array $row): string
    {
        if (!empty($row['pv_montante'])) {
            $txt = $row['pv_montante'] . ' → ' . ($row['pv_jusante'] ?: '?');
            if (!empty($row['trecho_rua'])) {
                $txt .= ' · ' . $row['trecho_rua'];
            }
            return $txt;
        }
        return (string)($row['logradouro'] ?? '—');
    }

    /* =====================================================
       LISTAR FRENTES DE RAMAIS
    ===================================================== */
    public function index(): void
    {
        auth_required([3, 4]);
        global $pdo, $currentRoute;

        $fInicio = self::dataGet('inicio');
        $fFim    = self::dataGet('fim');
        $fEquipe = (int)($_GET['equipe_id'] ?? 0);
        $fStatus = (string)($_GET['status'] ?? '');
        if (!isset(self::STATUS_FRENTE[$fStatus])) {
            $fStatus = '';
        }

        $sql = "
            SELECT fr.id, fr.data, fr.status, fr.versao, fr.logradouro, fr.obs,
                   e.nome AS equipe_nome,
                   u.nome AS autor_nome,
                   t.pv_montante, t.pv_jusante, t.rua AS trecho_rua,
                   (SELECT COUNT(*) FROM ramais r WHERE r.frente_id = fr.id) AS qtd_ramais,
                   (SELECT COALESCE(SUM(r.comprimento_via_m), 0)
                      FROM ramais r WHERE r.frente_id = fr.id) AS total_via_m,
                   (SELECT COALESCE(SUM(r.comprimento_calcada_m), 0)
                      FROM ramais r WHERE r.frente_id = fr.id) AS total_calcada_m
            FROM frentes_ramais fr
            JOIN equipes  e ON e.id = fr.equipe_id
            LEFT JOIN usuarios u ON u.id = fr.autor_id
            LEFT JOIN trechos  t ON t.id = fr.trecho_id
            WHERE 1 = 1
        ";
        $params = [];

        if ($fInicio !== null) { $sql .= " AND fr.data >= ?";      $params[] = $fInicio; }
        if ($fFim    !== null) { $sql .= " AND fr.data <= ?";      $params[] = $fFim; }
        if ($fEquipe > 0)      { $sql .= " AND fr.equipe_id = ?";  $params[] = $fEquipe; }
        if ($fStatus !== '')   { $sql .= " AND fr.status = ?";     $params[] = $fStatus; }

        $sql .= " ORDER BY fr.data DESC, e.nome, fr.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $frentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Resumo do filtro
        $resumo = [
            'frentes'   => count($frentes),
            'ramais'    => 0,
            'via_m'     => 0.0,
            'calcada_m' => 0.0,
        ];
        foreach ($frentes as $f) {
            $resumo['ramais']    += (int)$f['qtd_ramais'];
            $resumo['via_m']     += (float)$f['total_via_m'];
            $resumo['calcada_m'] += (float)$f['total_calcada_m'];
        }

        $equipes = $pdo->query("SELECT id, nome FROM equipes WHERE ativo = 1 ORDER BY nome")
                       ->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/ramais/index.php';
    }

    /* =====================================================
       DETALHE DE UMA FRENTE
    ===================================================== */
    public function ver(int $id = 0): void
    {
        auth_required([3, 4]);
        global $pdo, $currentRoute;

        if ($id <= 0) {
            $id = (int)($_GET['id'] ?? 0);
        }
        if ($id <= 0) {
            header('Location: ' . APP_BASE . '/ramais');
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT fr.*,
                   e.nome AS equipe_nome,
                   u.nome AS autor_nome,
                   t.pv_montante, t.pv_jusante, t.rua AS trecho_rua,
                   t.bacia AS trecho_bacia, t.extensao AS trecho_extensao
            FROM frentes_ramais fr
            JOIN equipes  e ON e.id = fr.equipe_id
            LEFT JOIN usuarios u ON u.id = fr.autor_id
            LEFT JOIN trechos  t ON t.id = fr.trecho_id
            WHERE fr.id = ?
        ");
        $stmt->execute([$id]);
        $frente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$frente) {
            $_SESSION['flash_erro'] = 'Frente de ramais não encontrada.';
            header('Location: ' . APP_BASE . '/ramais');
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT r.*
            FROM ramais r
            WHERE r.frente_id = ?
            ORDER BY r.sequencia, r.id
        ");
        $stmt->execute([$id]);
        $ramais = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fotos de todos os ramais da frente, agrupadas por ramal
        $fotosPorRamal = [];
        $stmt = $pdo->prepare("
            SELECT rf.id, rf.ramal_id, rf.tipo, rf.filename, rf.thumb,
                   rf.lat, rf.lng, rf.captured_at
            FROM ramal_fotos rf
            JOIN ramais r ON r.id = rf.ramal_id
            WHERE r.frente_id = ?
            ORDER BY rf.ramal_id,
                     FIELD(rf.tipo, 'ramal', 'lancamento_via', 'acabado'),
                     rf.id
        ");
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $foto) {
            $fotosPorRamal[(int)$foto['ramal_id']][] = $foto;
        }

        // Totais recalculados (não confia nos campos de cache da frente)
        $totais = [
            'ramais'    => count($ramais),
            'via_m'     => 0.0,
            'calcada_m' => 0.0,
            'fotos'     => 0,
        ];
        foreach ($ramais as $r) {
            $totais['via_m']     += (float)$r['comprimento_via_m'];
            $totais['calcada_m'] += (float)$r['comprimento_calcada_m'];
        }
        foreach ($fotosPorRamal as $lista) {
            $totais['fotos'] += count($lista);
        }

        $fotosBase  = self::FOTOS_BASE;
        $thumbsBase = self::THUMBS_BASE;

        require __DIR__ . '/../views/ramais/ver.php';
    }

    /* =====================================================
       RELATÓRIO FÍSICO POR PERÍODO
       (somente quantidades e metros — nunca valores em R$)
    ===================================================== */
    public function relatorio(): void
    {
        auth_required([3, 4]);
        global $pdo, $currentRoute;

        $inicio = self::dataGet('inicio') ?? date('Y-m-d', strtotime('-30 days'));
        $fim    = self::dataGet('fim')    ?? date('Y-m-d');
        if ($inicio > $fim) {
            [$inicio, $fim] = [$fim, $inicio];
        }

        // Só frentes efetivamente enviadas entram na medição física.
        $filtroBase = " FROM frentes_ramais fr
                        JOIN ramais r ON r.frente_id = fr.id
                        WHERE fr.status = 'enviado' AND fr.data BETWEEN ? AND ? ";

        // Totais gerais
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS qtd_ramais,
                   COALESCE(SUM(r.comprimento_via_m), 0)     AS via_m,
                   COALESCE(SUM(r.comprimento_calcada_m), 0) AS calcada_m
            $filtroBase
        ");
        $stmt->execute([$inicio, $fim]);
        $totais = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['qtd_ramais' => 0, 'via_m' => 0, 'calcada_m' => 0];

        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS qtd_frentes
            FROM frentes_ramais fr
            WHERE fr.status = 'enviado' AND fr.data BETWEEN ? AND ?
        ");
        $stmt->execute([$inicio, $fim]);
        $totais['qtd_frentes'] = (int)$stmt->fetchColumn();

        // Metros em via por tipo de pavimento
        $stmt = $pdo->prepare("
            SELECT r.pavimento_via AS pavimento,
                   COUNT(*) AS qtd_ramais,
                   COALESCE(SUM(r.comprimento_via_m), 0) AS metros
            $filtroBase
            GROUP BY r.pavimento_via
            ORDER BY metros DESC
        ");
        $stmt->execute([$inicio, $fim]);
        $porPavVia = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Metros em calçada por tipo de pavimento
        $stmt = $pdo->prepare("
            SELECT r.pavimento_calcada AS pavimento,
                   COUNT(*) AS qtd_ramais,
                   COALESCE(SUM(r.comprimento_calcada_m), 0) AS metros
            $filtroBase
            GROUP BY r.pavimento_calcada
            ORDER BY metros DESC
        ");
        $stmt->execute([$inicio, $fim]);
        $porPavCalcada = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lista por logradouro / trecho
        $stmt = $pdo->prepare("
            SELECT fr.logradouro, fr.trecho_id,
                   t.pv_montante, t.pv_jusante, t.rua AS trecho_rua,
                   COUNT(DISTINCT fr.id) AS qtd_frentes,
                   COUNT(r.id)           AS qtd_ramais,
                   COALESCE(SUM(r.comprimento_via_m), 0)     AS via_m,
                   COALESCE(SUM(r.comprimento_calcada_m), 0) AS calcada_m
            FROM frentes_ramais fr
            LEFT JOIN ramais  r ON r.frente_id = fr.id
            LEFT JOIN trechos t ON t.id = fr.trecho_id
            WHERE fr.status = 'enviado' AND fr.data BETWEEN ? AND ?
            GROUP BY fr.logradouro, fr.trecho_id, t.pv_montante, t.pv_jusante, t.rua
            ORDER BY via_m DESC, fr.logradouro
        ");
        $stmt->execute([$inicio, $fim]);
        $porLocal = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/ramais/relatorio.php';
    }
}
