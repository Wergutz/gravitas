<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';

class FrenteRepavController
{
    /* =====================================================
       LISTAR
    ===================================================== */
    public function index()
    {
        auth_required([4]);
        global $pdo;

        $frentes = $pdo->query("
            SELECT cr.id, cr.data_execucao, cr.status, cr.obs, cr.created_at,
                   e.nome AS equipe_nome,
                   COUNT(crt.id) AS total_trechos,
                   SUM(CASE WHEN crt.status = 'concluido' THEN 1 ELSE 0 END) AS trechos_concluidos
            FROM caminhamentos_repav cr
            JOIN equipes e ON e.id = cr.equipe_id
            LEFT JOIN caminhamentos_repav_trechos crt ON crt.caminhamento_id = cr.id
            GROUP BY cr.id
            ORDER BY cr.data_execucao DESC, cr.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/repavimentacao/frentes-listar.php';
    }

    /* =====================================================
       CADASTRAR
    ===================================================== */
    public function create()
    {
        auth_required([4]);
        global $pdo;

        $equipes = $pdo->query("
            SELECT e.id, e.nome, u.nome AS responsavel_nome, u.tipo_usuario
            FROM equipes e
            LEFT JOIN usuarios u ON u.id = e.responsavel_id
            WHERE e.ativo = 1
            ORDER BY e.nome
        ")->fetchAll(PDO::FETCH_ASSOC);

        $trechos_fila = $pdo->query("
            SELECT id, pv_montante, pv_jusante, bacia, rua, extensao
            FROM trechos
            WHERE status_repav = 'aguardando'
            ORDER BY bacia, pv_montante
        ")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/repavimentacao/frentes-cadastrar.php';
    }

    /* =====================================================
       SALVAR
    ===================================================== */
    public function store()
    {
        auth_required([4]);
        csrf_verify();
        global $pdo;

        $equipe_id     = (int)($_POST['equipe_id'] ?? 0);
        $data_execucao = trim($_POST['data_execucao'] ?? '');
        $obs           = substr(trim($_POST['obs'] ?? ''), 0, 500);
        $trechos       = array_unique(array_filter(array_map('intval', $_POST['trechos'] ?? [])));

        if (!$equipe_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_execucao)) {
            header('Location: ' . APP_BASE . '/repavimentacao/frentes/cadastrar?erro=dados');
            exit;
        }

        if (empty($trechos)) {
            header('Location: ' . APP_BASE . '/repavimentacao/frentes/cadastrar?erro=trechos');
            exit;
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare("
                INSERT INTO caminhamentos_repav (equipe_id, data_execucao, status, criado_por, obs)
                VALUES (?, ?, 'rascunho', ?, ?)
            ")->execute([$equipe_id, $data_execucao, $_SESSION['usuario_id'], $obs ?: null]);
            $camId = (int)$pdo->lastInsertId();

            $stmtT = $pdo->prepare("
                INSERT INTO caminhamentos_repav_trechos (caminhamento_id, trecho_id, sequencia, status)
                VALUES (?, ?, ?, 'pendente')
            ");
            foreach (array_values($trechos) as $seq => $tid) {
                $stmtT->execute([$camId, $tid, $seq + 1]);
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            header('Location: ' . APP_BASE . '/repavimentacao/frentes/cadastrar?erro=db');
            exit;
        }

        header('Location: ' . APP_BASE . '/repavimentacao/frentes');
        exit;
    }

    /* =====================================================
       PUBLICAR / DESPUBLICAR
    ===================================================== */
    public function publicar()
    {
        auth_required([4]);
        csrf_verify();
        global $pdo;

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            header('Location: ' . APP_BASE . '/repavimentacao/frentes');
            exit;
        }

        $stmt = $pdo->prepare("SELECT status FROM caminhamentos_repav WHERE id = ?");
        $stmt->execute([$id]);
        $cam = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cam || !in_array($cam['status'], ['rascunho', 'publicado'])) {
            header('Location: ' . APP_BASE . '/repavimentacao/frentes');
            exit;
        }

        $novo = $cam['status'] === 'rascunho' ? 'publicado' : 'rascunho';
        $pdo->prepare("UPDATE caminhamentos_repav SET status = ? WHERE id = ?")
            ->execute([$novo, $id]);

        header('Location: ' . APP_BASE . '/repavimentacao/frentes');
        exit;
    }

    /* =====================================================
       EXCLUIR
    ===================================================== */
    public function excluir()
    {
        auth_required([4]);
        csrf_verify();
        global $pdo;

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            header('Location: ' . APP_BASE . '/repavimentacao/frentes');
            exit;
        }

        $stmt = $pdo->prepare("SELECT status FROM caminhamentos_repav WHERE id = ?");
        $stmt->execute([$id]);
        $cam = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cam || $cam['status'] === 'concluido') {
            header('Location: ' . APP_BASE . '/repavimentacao/frentes');
            exit;
        }

        $pdo->beginTransaction();
        try {
            $stmtIds = $pdo->prepare("SELECT id FROM caminhamentos_repav_trechos WHERE caminhamento_id = ?");
            $stmtIds->execute([$id]);
            $ctIds = $stmtIds->fetchAll(PDO::FETCH_COLUMN);

            if ($ctIds) {
                $in = implode(',', array_map('intval', $ctIds));
                $pdo->exec("DELETE FROM caminhamentos_repav_pavimentos WHERE caminhamento_trecho_id IN ($in)");
            }
            $pdo->prepare("DELETE FROM caminhamentos_repav_trechos WHERE caminhamento_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM caminhamentos_repav WHERE id = ?")->execute([$id]);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
        }

        header('Location: ' . APP_BASE . '/repavimentacao/frentes');
        exit;
    }
}
