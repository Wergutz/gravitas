<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';

class DiarioExecucaoController {

    // --------------------------------------------------------
    // Lista de diários enviados (visão do Planejador)
    // --------------------------------------------------------
    public function index(): void {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        auth_required([4]);
        global $pdo;

        $filtroData  = $_GET['data']   ?? date('Y-m-d');
        $filtroEq    = (int)($_GET['equipe_id'] ?? 0);

        // Tenta query completa; se colunas PA5 não existirem, usa query básica
        try {
            $sql = "
                SELECT de.id, de.data, de.status, de.step_atual, de.versao,
                       de.extensao_gps_m, de.step3_estoque_ok, de.step3_materiais_faltando,
                       e.nome AS equipe_nome,
                       t.pv_montante, t.pv_jusante, t.extensao AS extensao_planejada, t.rua,
                       u.nome AS autor_nome
                FROM diarios_execucao de
                JOIN equipes e ON e.id = de.equipe_id
                JOIN trechos t ON t.id = de.trecho_id
                JOIN usuarios u ON u.id = de.autor_id
                WHERE de.data = ?
            ";
            $params = [$filtroData];
            if ($filtroEq) { $sql .= " AND de.equipe_id = ?"; $params[] = $filtroEq; }
            $sql .= " ORDER BY e.nome, de.id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $diarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Fallback sem colunas opcionais
            try {
                $sql = "
                    SELECT de.id, de.data, de.status, de.step_atual, de.versao,
                           NULL AS extensao_gps_m, NULL AS step3_estoque_ok, NULL AS step3_materiais_faltando,
                           e.nome AS equipe_nome,
                           t.pv_montante, t.pv_jusante, t.extensao AS extensao_planejada, t.rua,
                           u.nome AS autor_nome
                    FROM diarios_execucao de
                    JOIN equipes e ON e.id = de.equipe_id
                    JOIN trechos t ON t.id = de.trecho_id
                    JOIN usuarios u ON u.id = de.autor_id
                    WHERE de.data = ?
                ";
                $params = [$filtroData];
                if ($filtroEq) { $sql .= " AND de.equipe_id = ?"; $params[] = $filtroEq; }
                $sql .= " ORDER BY e.nome, de.id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $diarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Exception $e2) {
                $diarios = [];
            }
        }

        try {
            $equipes = $pdo->query("SELECT id, nome FROM equipes WHERE ativo = 1 ORDER BY nome")
                           ->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $equipes = [];
        }

        try {
            $alertasMat = $pdo->query("
                SELECT af.*, e.nome AS equipe_nome, t.pv_montante, t.pv_jusante
                FROM alertas_falta_material af
                JOIN equipes e ON e.id = af.equipe_id
                JOIN trechos t ON t.id = af.trecho_id
                WHERE af.resolvido = 0
                ORDER BY af.data DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $alertasMat = [];
        }

        try {
            $equipsManut = array_merge(
                $pdo->query("
                    SELECT id, tipo, modelo, placa, obs_manutencao, 'pesado' AS categoria
                    FROM equipamentos_pesados WHERE status_manutencao = 'manutencao' AND ativo = 1
                ")->fetchAll(PDO::FETCH_ASSOC),
                $pdo->query("
                    SELECT id, tipo, modelo, NULL AS placa, obs_manutencao, 'leve' AS categoria
                    FROM equipamentos_leves WHERE status_manutencao = 'manutencao' AND ativo = 1
                ")->fetchAll(PDO::FETCH_ASSOC)
            );
        } catch (\Exception $e) {
            $equipsManut = [];
        }

        require __DIR__ . '/../views/diarios/index.php';
    }

    // --------------------------------------------------------
    // Detalhe de um diário
    // --------------------------------------------------------
    public function ver(): void {
        auth_required([4]);
        global $pdo;

        $id = (int)($_GET['id'] ?? 0);
        $diario = $pdo->prepare("
            SELECT de.*, e.nome AS equipe_nome,
                   t.pv_montante, t.pv_jusante, t.extensao AS extensao_planejada,
                   t.rua, t.bacia, t.dn, t.contrato,
                   u.nome AS autor_nome
            FROM diarios_execucao de
            JOIN equipes e ON e.id = de.equipe_id
            JOIN trechos t ON t.id = de.trecho_id
            JOIN usuarios u ON u.id = de.autor_id
            WHERE de.id = ?
        ");
        $diario->execute([$id]);
        $diario = $diario->fetch(PDO::FETCH_ASSOC);

        if (!$diario) {
            header('Location: ' . APP_BASE . '/diarios');
            exit;
        }

        // Presença
        $presencas = $pdo->prepare("
            SELECT dp.status, dp.obs, f.nome
            FROM diario_presencas dp
            JOIN funcionarios f ON f.id = dp.funcionario_id
            WHERE dp.diario_id = ?
            ORDER BY f.nome
        ");
        $presencas->execute([$id]);
        $presencas = $presencas->fetchAll(PDO::FETCH_ASSOC);

        // GPS
        $gps = $pdo->prepare("SELECT * FROM diario_gps WHERE diario_id = ?");
        $gps->execute([$id]);
        $gps = $gps->fetch(PDO::FETCH_ASSOC);

        // Interferências
        $interferencias = $pdo->prepare("
            SELECT di.*, df.arquivo AS foto_arquivo, df.lat AS foto_lat, df.lng AS foto_lng
            FROM diario_interferencias di
            LEFT JOIN diario_fotos df ON df.id = di.foto_id
            WHERE di.diario_id = ?
        ");
        $interferencias->execute([$id]);
        $interferencias = $interferencias->fetchAll(PDO::FETCH_ASSOC);

        // Reaterros
        $reaterros = $pdo->prepare("
            SELECT dr.*, df.arquivo AS foto_arquivo
            FROM diario_reaterros dr
            LEFT JOIN diario_fotos df ON df.id = dr.foto_id
            WHERE dr.diario_id = ?
        ");
        $reaterros->execute([$id]);
        $reaterros = $reaterros->fetchAll(PDO::FETCH_ASSOC);

        // Ramais
        $ramais = $pdo->prepare("SELECT * FROM diario_ramais WHERE diario_id = ?");
        $ramais->execute([$id]);
        $ramais = $ramais->fetchAll(PDO::FETCH_ASSOC);

        // Equipamentos
        $equipamentos = $pdo->prepare("
            SELECT de2.funcionando, de2.tipo, de2.obs,
                   COALESCE(ep.modelo, el.modelo) AS modelo,
                   COALESCE(ep.tipo, el.tipo) AS tipo_equip,
                   ep.placa,
                   df.thumb AS foto_thumb
            FROM diario_equipamentos de2
            LEFT JOIN equipamentos_pesados ep ON de2.tipo='pesado' AND ep.id=de2.equipamento_id
            LEFT JOIN equipamentos_leves   el ON de2.tipo='leve'   AND el.id=de2.equipamento_id
            LEFT JOIN diario_fotos df ON df.id = de2.foto_id
            WHERE de2.diario_id = ?
            ORDER BY de2.tipo
        ");
        $equipamentos->execute([$id]);
        $equipamentos = $equipamentos->fetchAll(PDO::FETCH_ASSOC);

        // Fotos agrupadas por step
        $fotos = $pdo->prepare("
            SELECT * FROM diario_fotos WHERE diario_id = ? ORDER BY step_num, id
        ");
        $fotos->execute([$id]);
        $fotos = $fotos->fetchAll(PDO::FETCH_ASSOC);
        $fotosPorStep = [];
        foreach ($fotos as $f) $fotosPorStep[$f['step_num']][] = $f;

        // Cargas
        $cargas = $pdo->prepare("
            SELECT dc.tipo, dc.numero, df.thumb AS foto_thumb
            FROM diario_cargas dc
            LEFT JOIN diario_fotos df ON df.id = dc.foto_id
            WHERE dc.diario_id = ?
            ORDER BY dc.tipo, dc.numero
        ");
        $cargas->execute([$id]);
        $cargas = $cargas->fetchAll(PDO::FETCH_ASSOC);

        // Pontões
        $pontoes = $pdo->prepare("
            SELECT dp2.nro_residencia, df.thumb AS foto_thumb
            FROM diario_pontoes dp2
            LEFT JOIN diario_fotos df ON df.id = dp2.foto_id
            WHERE dp2.diario_id = ?
        ");
        $pontoes->execute([$id]);
        $pontoes = $pontoes->fetchAll(PDO::FETCH_ASSOC);

        $painelBase = APP_BASE;
        $executorUploads = '/principal/executor/uploads';

        require __DIR__ . '/../views/diarios/ver.php';
    }

    // --------------------------------------------------------
    // Resolver alerta de falta de material
    // --------------------------------------------------------
    public function resolverAlerta(): void {
        auth_required([4]);
        csrf_verify();
        global $pdo;

        $alertaId = (int)($_POST['alerta_id'] ?? 0);
        $pdo->prepare("
            UPDATE alertas_falta_material
            SET resolvido = 1, resolvido_em = NOW(), resolvido_por = ?
            WHERE id = ?
        ")->execute([$_SESSION['usuario_id'], $alertaId]);

        header('Location: ' . APP_BASE . '/diarios');
        exit;
    }

    // --------------------------------------------------------
    // Marcar equipamento como OK (saiu de manutenção)
    // --------------------------------------------------------
    public function resolverManutencao(): void {
        auth_required([4]);
        csrf_verify();
        global $pdo;

        $equipId   = (int)($_POST['equip_id']   ?? 0);
        $categoria = $_POST['categoria'] === 'leve' ? 'leve' : 'pesado';
        $tabela    = $categoria === 'pesado' ? 'equipamentos_pesados' : 'equipamentos_leves';

        $pdo->prepare("UPDATE {$tabela} SET status_manutencao = 'ok', obs_manutencao = NULL WHERE id = ?")
            ->execute([$equipId]);

        header('Location: ' . APP_BASE . '/diarios');
        exit;
    }

    // --------------------------------------------------------
    // Relatório fotográfico — todas as fotos de um diário
    // --------------------------------------------------------
    public function relatorioFotos(): void {
        auth_required([4]);
        global $pdo;

        $id = (int)($_GET['id'] ?? 0);

        $diario = $pdo->prepare("
            SELECT de.*, e.nome AS equipe_nome,
                   t.pv_montante, t.pv_jusante, t.rua, t.bacia
            FROM diarios_execucao de
            JOIN equipes e ON e.id = de.equipe_id
            JOIN trechos t ON t.id = de.trecho_id
            WHERE de.id = ?
        ");
        $diario->execute([$id]);
        $diario = $diario->fetch(PDO::FETCH_ASSOC);
        if (!$diario) { http_response_code(404); echo "Diário não encontrado."; return; }

        $fotos = $pdo->prepare("
            SELECT * FROM diario_fotos WHERE diario_id = ? ORDER BY step_num, id
        ");
        $fotos->execute([$id]);
        $fotos = $fotos->fetchAll(PDO::FETCH_ASSOC);

        $fotosPorStep = [];
        foreach ($fotos as $f) $fotosPorStep[$f['step_num']][] = $f;

        $stepNomes = [
            1=>'Equipe na obra', 2=>'Atrasos/saídas', 3=>'Estoque', 4=>'Carregando material',
            5=>'Sinalização e EPIs', 6=>'Equipamentos', 7=>'Corte de asfalto',
            8=>'Retirada de pavimento', 9=>'Escavação', 10=>'Escoramento',
            11=>'Interferências', 12=>'GPS início', 13=>'GPS fim',
            14=>'Pontões', 15=>'Cargas bota-fora', 16=>'Cargas importado',
            17=>'Reaterro', 18=>'Ramais', 19=>'Rua limpa', 20=>'Equipe final', 21=>'Finalização',
        ];

        $executorUploads = '/principal/executor/uploads';

        require __DIR__ . '/../views/diarios/relatorio_fotos.php';
    }
}
