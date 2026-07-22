<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';

class MasterController {

    private PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    // ─── Dashboard (3 modos) ──────────────────────────────────────────

    public function dashboard(): void {
        auth_required_master();

        $modo   = in_array($_GET['modo'] ?? '', ['rt','dia','periodo']) ? $_GET['modo'] : 'rt';
        $inicio = $this->validarData($_GET['inicio'] ?? '') ?: date('Y-m-d', strtotime('-30 days'));
        $fim    = $this->validarData($_GET['fim']    ?? '') ?: date('Y-m-d');

        // M1: default "dia" to last date with diary data (not today which may be empty)
        if ($modo === 'dia' && empty($_GET['data'])) {
            $ultimaData = $this->db->query(
                "SELECT MAX(data) FROM diarios_execucao WHERE status IN ('enviado','aprovado')"
            )->fetchColumn();
            $data = $this->validarData($ultimaData ?: '') ?: date('Y-m-d');
        } else {
            $data = $this->validarData($_GET['data'] ?? '') ?: date('Y-m-d');
        }

        $dados = match($modo) {
            'dia'     => $this->dadosDia($data),
            'periodo' => $this->dadosPeriodo($inicio, $fim),
            default   => $this->dadosTempoReal(),
        };

        extract($dados);
        require __DIR__ . '/../views/dashboard.php';
    }

    // ─── Relatórios ───────────────────────────────────────────────────

    public function relatorio(string $tipo): void {
        auth_required_master();

        $tipos_validos = ['boletim','rdo','interferencias','avanco','produtividade','materiais','resumo','fotos'];
        if (!in_array($tipo, $tipos_validos)) {
            http_response_code(404); echo 'Relatório não encontrado.'; return;
        }

        $data   = $this->validarData($_GET['data']   ?? '') ?: date('Y-m-d');
        $inicio = $this->validarData($_GET['inicio'] ?? '') ?: date('Y-m-d', strtotime('-30 days'));
        $fim    = $this->validarData($_GET['fim']    ?? '') ?: date('Y-m-d');
        $fmt    = $_GET['fmt'] === 'csv' ? 'csv' : 'html';

        $dados = match($tipo) {
            'rdo'     => $this->dadosDia($data),
            'fotos'   => $this->dadosFotosRelatorio($data),
            'materiais' => $this->dadosMateriais(),
            default   => $this->dadosPeriodo($inicio, $fim),
        };

        if ($fmt === 'csv') {
            $this->exportarCsv($tipo, $dados, $inicio, $fim, $data);
            return;
        }

        $viewFile = __DIR__ . '/../views/relatorios/' . $tipo . '.php';
        extract($dados);
        require $viewFile;
    }

    // ─── Modo: Tempo Real ─────────────────────────────────────────────

    private function dadosTempoReal(): array {
        $hoje = date('Y-m-d');

        $totalEquipes = (int)$this->db->query("SELECT COUNT(*) FROM equipes WHERE ativo=1")->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT equipe_id) FROM diarios_execucao WHERE data=?");
        $stmt->execute([$hoje]); $equipesCampo = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COALESCE(SUM(extensao_gps_m),0) FROM diarios_execucao WHERE data=?");
        $stmt->execute([$hoje]); $metrosHoje = (float)$stmt->fetchColumn();

        // Presença
        $stmt = $this->db->prepare("
            SELECT dp.status, COUNT(*) AS qtd
            FROM diario_presencas dp
            JOIN diarios_execucao de ON de.id=dp.diario_id
            WHERE de.data=? GROUP BY dp.status
        ");
        $stmt->execute([$hoje]);
        $presMap = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $presMap[$r['status']] = (int)$r['qtd'];
        $presentes = $presMap['presente'] ?? 0;
        $ausentes  = ($presMap['ausente'] ?? 0) + ($presMap['atrasou'] ?? 0) + ($presMap['saiu_cedo'] ?? 0);

        // Alertas
        $alertasMat = 0; $equipsManut = 0;
        try {
            $alertasMat = (int)$this->db->query("SELECT COUNT(*) FROM alertas_falta_material WHERE resolvido=0")->fetchColumn();
            $equipsManut = (int)$this->db->query("SELECT COUNT(*) FROM equipamentos_pesados WHERE ativo=1 AND status_manutencao='manutencao'")->fetchColumn()
                         + (int)$this->db->query("SELECT COUNT(*) FROM equipamentos_leves WHERE ativo=1 AND status_manutencao='manutencao'")->fetchColumn();
        } catch (\PDOException $e) {}
        $trechosSemOs = (int)$this->db->query("SELECT COUNT(*) FROM trechos WHERE id NOT IN (SELECT trecho_id FROM ordens_servico WHERE ativa=1)")->fetchColumn();
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM funcionario_documentos WHERE data_validade BETWEEN ? AND DATE_ADD(?,INTERVAL 15 DAY)");
        $stmt->execute([$hoje, $hoje]); $docsVencer = (int)$stmt->fetchColumn();
        $totalAlertas = $alertasMat + ($trechosSemOs > 0 ? 1 : 0) + ($docsVencer > 0 ? 1 : 0);

        // Equipes em campo
        $stmt = $this->db->prepare("
            SELECT e.id, e.nome,
                c.id AS cam_id, c.status AS cam_status,
                de.id AS diario_id, de.step_atual, de.status AS diario_status,
                de.updated_at, de.extensao_gps_m,
                (SELECT COUNT(*) FROM caminhamento_trechos WHERE caminhamento_id=c.id) AS total_trechos,
                (SELECT COUNT(*) FROM caminhamento_trechos WHERE caminhamento_id=c.id AND status='concluido') AS trechos_concluidos,
                (SELECT t.pv_montante FROM caminhamento_trechos ct JOIN trechos t ON t.id=ct.trecho_id WHERE ct.caminhamento_id=c.id AND ct.status!='concluido' ORDER BY ct.sequencia LIMIT 1) AS pv_montante,
                (SELECT t.pv_jusante  FROM caminhamento_trechos ct JOIN trechos t ON t.id=ct.trecho_id WHERE ct.caminhamento_id=c.id AND ct.status!='concluido' ORDER BY ct.sequencia LIMIT 1) AS pv_jusante,
                (SELECT t.rua         FROM caminhamento_trechos ct JOIN trechos t ON t.id=ct.trecho_id WHERE ct.caminhamento_id=c.id AND ct.status!='concluido' ORDER BY ct.sequencia LIMIT 1) AS rua
            FROM equipes e
            LEFT JOIN caminhamentos c ON c.equipe_id=e.id AND c.data_execucao=? AND c.status IN ('publicado','execucao')
            LEFT JOIN diarios_execucao de ON de.equipe_id=e.id AND de.data=?
            WHERE e.ativo=1
            GROUP BY e.id ORDER BY e.nome
        ");
        $stmt->execute([$hoje, $hoje]); $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Interferências do dia
        $stmt = $this->db->prepare("
            SELECT di.tipo, COUNT(*) AS qtd FROM diario_interferencias di
            JOIN diarios_execucao de ON de.id=di.diario_id WHERE de.data=?
            GROUP BY di.tipo ORDER BY qtd DESC
        ");
        $stmt->execute([$hoje]); $interfs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalInterfs = array_sum(array_column($interfs, 'qtd'));

        // Equipamentos total
        $equipsTotal = (int)$this->db->query("SELECT COUNT(*) FROM equipamentos_pesados WHERE ativo=1")->fetchColumn()
                     + (int)$this->db->query("SELECT COUNT(*) FROM equipamentos_leves WHERE ativo=1")->fetchColumn();

        // Última sincronização
        $stmt = $this->db->prepare("SELECT MAX(updated_at) FROM diarios_execucao WHERE data=?");
        $stmt->execute([$hoje]); $ultimaSinc = $stmt->fetchColumn();

        return compact(
            'hoje','totalEquipes','equipesCampo','metrosHoje',
            'presentes','ausentes','totalAlertas','alertasMat','trechosSemOs','docsVencer',
            'equipes','interfs','totalInterfs','equipsTotal','equipsManut','ultimaSinc'
        );
    }

    // ─── Modo: Produção do Dia ────────────────────────────────────────

    private function dadosDia(string $data): array {
        $stmt = $this->db->prepare("
            SELECT e.nome AS equipe, de.extensao_gps_m, t.extensao AS extensao_planejada,
                   t.pv_montante, t.pv_jusante, t.bacia
            FROM diarios_execucao de
            JOIN equipes e ON e.id=de.equipe_id
            JOIN trechos t ON t.id=de.trecho_id
            WHERE de.data=? ORDER BY e.nome
        ");
        $stmt->execute([$data]); $producaoPorEquipe = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $metrosDia = (float)array_sum(array_column($producaoPorEquipe, 'extensao_gps_m'));

        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS qtd, COALESCE(SUM(dr.ext_pista),0) AS m_pista, COALESCE(SUM(dr.ext_calcada),0) AS m_calcada
            FROM diario_ramais dr JOIN diarios_execucao de ON de.id=dr.diario_id WHERE de.data=?
        ");
        $stmt->execute([$data]); $ramais = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("
            SELECT dc.tipo, COUNT(*) AS qtd FROM diario_cargas dc
            JOIN diarios_execucao de ON de.id=dc.diario_id WHERE de.data=? GROUP BY dc.tipo
        ");
        $stmt->execute([$data]); $cargasArr = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $cargas = []; foreach ($cargasArr as $c) $cargas[$c['tipo']] = (int)$c['qtd'];

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM diario_pontoes dp JOIN diarios_execucao de ON de.id=dp.diario_id WHERE de.data=?
        ");
        $stmt->execute([$data]); $pontoes = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT dp.status, COUNT(*) AS qtd FROM diario_presencas dp
            JOIN diarios_execucao de ON de.id=dp.diario_id WHERE de.data=? GROUP BY dp.status
        ");
        $stmt->execute([$data]);
        $presMap = []; foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $presMap[$r['status']] = (int)$r['qtd'];
        $presentes = $presMap['presente'] ?? 0;
        $ausentes  = ($presMap['ausente'] ?? 0) + ($presMap['atrasou'] ?? 0) + ($presMap['saiu_cedo'] ?? 0);

        $stmt = $this->db->prepare("
            SELECT di.tipo, COUNT(*) AS qtd FROM diario_interferencias di
            JOIN diarios_execucao de ON de.id=di.diario_id WHERE de.data=? GROUP BY di.tipo ORDER BY qtd DESC
        ");
        $stmt->execute([$data]); $interfs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("
            SELECT df.thumb, df.step_num, df.lat, df.lng, e.nome AS equipe
            FROM diario_fotos df
            JOIN diarios_execucao de ON de.id=df.diario_id
            JOIN equipes e ON e.id=de.equipe_id
            WHERE de.data=? AND df.thumb IS NOT NULL AND df.step_num IN (5,9,11,17,19,20)
            ORDER BY df.step_num, de.equipe_id, df.id LIMIT 12
        ");
        $stmt->execute([$data]); $fotosGaleria = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return compact('data','metrosDia','producaoPorEquipe','ramais','cargas','pontoes','presentes','ausentes','interfs','fotosGaleria');
    }

    // ─── Modo: Acumulado por Período ──────────────────────────────────

    private function dadosPeriodo(string $inicio, string $fim): array {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(extensao_gps_m),0) FROM diarios_execucao
            WHERE data BETWEEN ? AND ? AND status IN ('enviado','aprovado')
        ");
        $stmt->execute([$inicio, $fim]); $metrosTotal = (float)$stmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT data, COALESCE(SUM(extensao_gps_m),0) AS metros FROM diarios_execucao
            WHERE data BETWEEN ? AND ? AND status IN ('enviado','aprovado')
            GROUP BY data ORDER BY data
        ");
        $stmt->execute([$inicio, $fim]); $curvaProd = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $diasTrabalhados = count($curvaProd);
        $mediaDiaria = $diasTrabalhados > 0 ? round($metrosTotal / $diasTrabalhados, 1) : 0;

        $stmt = $this->db->prepare("
            SELECT t.bacia, e.nome AS equipe, COALESCE(SUM(de.extensao_gps_m),0) AS metros
            FROM diarios_execucao de
            JOIN equipes e ON e.id=de.equipe_id JOIN trechos t ON t.id=de.trecho_id
            WHERE de.data BETWEEN ? AND ? AND de.status IN ('enviado','aprovado')
            GROUP BY t.bacia, e.id ORDER BY t.bacia, e.nome
        ");
        $stmt->execute([$inicio, $fim]); $porBaciaEquipe = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $previsto = (float)$this->db->query("SELECT COALESCE(SUM(extensao),0) FROM trechos")->fetchColumn();
        $executadoTotal = (float)$this->db->query("SELECT COALESCE(SUM(extensao_gps_m),0) FROM diarios_execucao WHERE status IN ('enviado','aprovado')")->fetchColumn();
        $pctAvanco = $previsto > 0 ? min(100, round($executadoTotal / $previsto * 100, 1)) : 0;
        $projecao = null;
        if ($mediaDiaria > 0 && $previsto > $executadoTotal) {
            $dias = (int)ceil(($previsto - $executadoTotal) / $mediaDiaria);
            $projecao = date('d/m/Y', strtotime("+$dias days"));
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS qtd, COALESCE(SUM(dr.ext_pista),0) AS m_pista, COALESCE(SUM(dr.ext_calcada),0) AS m_calcada
            FROM diario_ramais dr JOIN diarios_execucao de ON de.id=dr.diario_id WHERE de.data BETWEEN ? AND ?
        ");
        $stmt->execute([$inicio, $fim]); $ramaisTotal = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("
            SELECT tipo, COUNT(*) AS qtd FROM diario_interferencias di
            JOIN diarios_execucao de ON de.id=di.diario_id WHERE de.data BETWEEN ? AND ?
            GROUP BY tipo ORDER BY qtd DESC
        ");
        $stmt->execute([$inicio, $fim]); $interfsTotal = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalInterfs = array_sum(array_column($interfsTotal, 'qtd'));

        $stmt = $this->db->prepare("
            SELECT e.nome AS equipe, COUNT(DISTINCT de.data) AS dias,
                COALESCE(SUM(de.extensao_gps_m),0) AS metros,
                ROUND(COALESCE(SUM(de.extensao_gps_m),0)/NULLIF(COUNT(DISTINCT de.data),0),1) AS m_por_dia
            FROM diarios_execucao de JOIN equipes e ON e.id=de.equipe_id
            WHERE de.data BETWEEN ? AND ? AND de.status IN ('enviado','aprovado')
            GROUP BY e.id ORDER BY m_por_dia DESC
        ");
        $stmt->execute([$inicio, $fim]); $produtividade = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // M5: Repavimentação no período
        $repavPeriodo = null;
        try {
            $stmtR = $this->db->prepare("
                SELECT COALESCE(SUM(dr.area_total_m2), 0) AS area_total,
                       COALESCE(SUM(dr.volume_asf_m3), 0) AS volume_total,
                       COUNT(DISTINCT dr.trecho_id)        AS trechos_medidos
                FROM diarios_repav dr
                WHERE dr.data BETWEEN ? AND ? AND dr.status IN ('enviado','aprovado')
            ");
            $stmtR->execute([$inicio, $fim]);
            $row = $stmtR->fetch(PDO::FETCH_ASSOC);
            $stmtFila = $this->db->query("SELECT COUNT(*) FROM trechos WHERE status_repav IS NOT NULL AND status_repav != 'medido'");
            $row['fila_pendente'] = (int)$stmtFila->fetchColumn();
            $repavPeriodo = $row;
        } catch (\PDOException $e) {}

        return compact(
            'inicio','fim','metrosTotal','curvaProd','diasTrabalhados','mediaDiaria',
            'porBaciaEquipe','previsto','executadoTotal','pctAvanco','projecao',
            'ramaisTotal','interfsTotal','totalInterfs','produtividade','repavPeriodo'
        );
    }

    // ─── Materiais ────────────────────────────────────────────────────

    private function dadosMateriais(): array {
        $stmt = $this->db->query("
            SELECT mc.nome, mc.unidade,
                COALESCE(me.quantidade_fisica,0) AS estoque_atual,
                COALESCE(me.quantidade_reservada,0) AS reservado,
                COALESCE(me.quantidade_minima,0) AS minimo
            FROM materiais_catalogo mc
            LEFT JOIN materiais_estoque me ON me.material_id=mc.id
            ORDER BY mc.nome
        ");
        return ['materiais' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'inicio' => '', 'fim' => ''];
    }

    private function dadosFotosRelatorio(string $data): array {
        $stmt = $this->db->prepare("
            SELECT df.*, e.nome AS equipe, t.pv_montante, t.pv_jusante
            FROM diario_fotos df
            JOIN diarios_execucao de ON de.id=df.diario_id
            JOIN equipes e ON e.id=de.equipe_id
            JOIN trechos t ON t.id=de.trecho_id
            WHERE de.data=?
            ORDER BY df.step_num, de.equipe_id, df.id
        ");
        $stmt->execute([$data]); $fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $fotosPorStep = [];
        foreach ($fotos as $f) $fotosPorStep[(int)$f['step_num']][] = $f;
        return compact('data','fotos','fotosPorStep');
    }

    // ─── CSV export ───────────────────────────────────────────────────

    private function exportarCsv(string $tipo, array $dados, string $inicio, string $fim, string $data): void {
        $nome = 'gravitas_' . $tipo . '_' . ($tipo === 'rdo' ? $data : $inicio . '_' . $fim) . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nome . '"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        match($tipo) {
            'interferencias' => $this->csvInterferencias($out, $dados),
            'produtividade'  => $this->csvProdutividade($out, $dados),
            'materiais'      => $this->csvMateriais($out, $dados),
            'boletim'        => $this->csvBoletim($out, $dados),
            default          => fputcsv($out, ['Sem exportação CSV para este relatório.'], ';'),
        };
        fclose($out);
    }

    private function csvInterferencias($out, array $d): void {
        fputcsv($out, ['Tipo', 'Quantidade'], ';');
        foreach ($d['interfsTotal'] ?? [] as $i)
            fputcsv($out, [str_replace('_', ' ', ucfirst($i['tipo'])), $i['qtd']], ';');
    }

    private function csvProdutividade($out, array $d): void {
        fputcsv($out, ['Equipe', 'Dias trabalhados', 'Metros total', 'M/dia médio'], ';');
        foreach ($d['produtividade'] ?? [] as $p)
            fputcsv($out, [$p['equipe'], $p['dias'], number_format($p['metros'],1,',','.'), number_format($p['m_por_dia'],1,',','.')], ';');
    }

    private function csvMateriais($out, array $d): void {
        fputcsv($out, ['Material', 'Unidade', 'Estoque atual', 'Reservado', 'Mínimo'], ';');
        foreach ($d['materiais'] ?? [] as $m)
            fputcsv($out, [$m['nome'], $m['unidade'], $m['estoque_atual'], $m['reservado'], $m['minimo']], ';');
    }

    private function csvBoletim($out, array $d): void {
        fputcsv($out, ['Bacia', 'Equipe', 'Rede executada (m)'], ';');
        foreach ($d['porBaciaEquipe'] ?? [] as $b)
            fputcsv($out, [$b['bacia'], $b['equipe'], number_format($b['metros'],1,',','.')], ';');
        fputcsv($out, ['TOTAL', '', number_format($d['metrosTotal'] ?? 0, 1, ',', '.')], ';');
    }

    // ─── Helper ───────────────────────────────────────────────────────

    private function validarData(string $s): string {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : '';
    }
}
