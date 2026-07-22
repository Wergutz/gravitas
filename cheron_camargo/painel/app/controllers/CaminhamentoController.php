<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';

class CaminhamentoController
{
    /* =====================================================
       LISTAR
    ===================================================== */
    public function index()
    {
        auth_required([4]);
        global $pdo;

        $stmt = $pdo->query("
            SELECT c.id, c.data_execucao, c.status, c.observacoes,
                   e.nome AS equipe_nome,
                   (SELECT COUNT(*) FROM caminhamento_trechos ct WHERE ct.caminhamento_id = c.id) AS total_trechos,
                   (SELECT COUNT(*) FROM caminhamento_trechos ct WHERE ct.caminhamento_id = c.id AND ct.status = 'concluido') AS trechos_concluidos
            FROM caminhamentos c
            JOIN equipes e ON e.id = c.equipe_id
            ORDER BY c.data_execucao DESC, e.nome
        ");
        $caminhamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/caminhamentos/listar.php';
    }

    /* =====================================================
       DETALHES (lista trechos + concluir)
    ===================================================== */
    public function detalhe()
    {
        auth_required([4]);
        global $pdo;

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . APP_BASE . '/caminhamentos');
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT c.*, e.nome AS equipe_nome
            FROM caminhamentos c
            JOIN equipes e ON e.id = c.equipe_id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $caminhamento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$caminhamento) {
            header('Location: ' . APP_BASE . '/caminhamentos');
            exit;
        }

        // Trechos do caminhamento com OS e materiais
        $stmt = $pdo->prepare("
            SELECT ct.id AS ct_id, ct.sequencia, ct.status AS ct_status,
                   t.id AS trecho_id, t.pv_montante, t.pv_jusante, t.bacia, t.rua, t.extensao,
                   t.status_rede, t.status_repav,
                   os.arquivo_pdf AS os_arquivo, os.versao AS os_versao
            FROM caminhamento_trechos ct
            JOIN trechos t ON t.id = ct.trecho_id
            LEFT JOIN ordens_servico os ON os.trecho_id = t.id AND os.ativa = 1
            WHERE ct.caminhamento_id = ?
            ORDER BY ct.sequencia
        ");
        $stmt->execute([$id]);
        $trechos_cam = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Materiais alocados por trecho
        $materiais_por_trecho = [];
        foreach ($trechos_cam as $tc) {
            $stmt2 = $pdo->prepare("
                SELECT tm.*, mc.nome AS material_nome, mc.unidade
                FROM trecho_materiais tm
                JOIN materiais_catalogo mc ON mc.id = tm.material_id
                WHERE tm.trecho_id = ?
            ");
            $stmt2->execute([$tc['trecho_id']]);
            $materiais_por_trecho[$tc['trecho_id']] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }

        // Funcionários com doc vencido na equipe
        $stmt = $pdo->prepare("
            SELECT f.nome, fd.tipo, fd.data_validade
            FROM funcionario_documentos fd
            JOIN funcionarios f ON f.id = fd.funcionario_id
            JOIN equipe_funcionarios ef ON ef.funcionario_id = fd.funcionario_id AND ef.ativo = 1
            JOIN caminhamentos c ON c.equipe_id = ef.equipe_id
            WHERE c.id = ? AND fd.data_validade < CURDATE()
            ORDER BY fd.data_validade
        ");
        $stmt->execute([$id]);
        $docs_vencidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Trechos disponíveis para adicionar (status livre, não já vinculados)
        $trechos_vinculados = array_column($trechos_cam, 'trecho_id');
        $trechos_disponiveis_det = [];
        if (in_array($caminhamento['status'], ['rascunho', 'publicado'])) {
            $trechos_disponiveis_det = $pdo->query("
                SELECT t.id, t.pv_montante, t.pv_jusante, t.bacia, t.extensao, t.rua
                FROM trechos t
                WHERE t.status_rede = 'livre'
                ORDER BY t.bacia, t.pv_montante
            ")->fetchAll(PDO::FETCH_ASSOC);
        }

        require __DIR__ . '/../views/caminhamentos/detalhe.php';
    }

    /* =====================================================
       CADASTRAR
    ===================================================== */
    public function create()
    {
        auth_required([4]);
        global $pdo;

        $equipes = $pdo->query("SELECT id, nome FROM equipes WHERE ativo = 1 ORDER BY nome")
                       ->fetchAll(PDO::FETCH_ASSOC);

        // Trechos disponíveis: status=livre (OS é opcional)
        $trechos_disponiveis = $pdo->query("
            SELECT t.id, t.pv_montante, t.pv_jusante, t.bacia, t.extensao, t.rua,
                   os.versao AS os_versao
            FROM trechos t
            LEFT JOIN ordens_servico os ON os.trecho_id = t.id AND os.ativa = 1
            WHERE t.status_rede = 'livre'
            ORDER BY t.bacia, t.pv_montante
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Regra 18: docs vencidos e a vencer (≤30d) por equipe
        $docs_vencidos_por_equipe = [];
        $docs_a_vencer_por_equipe = [];
        $stmtVenc = $pdo->prepare("
            SELECT COUNT(*)
            FROM funcionario_documentos fd
            JOIN equipe_funcionarios ef ON ef.funcionario_id = fd.funcionario_id AND ef.ativo = 1
            WHERE ef.equipe_id = ? AND fd.data_validade IS NOT NULL AND fd.data_validade < CURDATE()
        ");
        $stmtAVenc = $pdo->prepare("
            SELECT COUNT(*)
            FROM funcionario_documentos fd
            JOIN equipe_funcionarios ef ON ef.funcionario_id = fd.funcionario_id AND ef.ativo = 1
            WHERE ef.equipe_id = ?
              AND fd.data_validade IS NOT NULL
              AND fd.data_validade >= CURDATE()
              AND fd.data_validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");
        foreach ($equipes as $e) {
            $stmtVenc->execute([$e['id']]);
            $docs_vencidos_por_equipe[$e['id']] = (int)$stmtVenc->fetchColumn();
            $stmtAVenc->execute([$e['id']]);
            $docs_a_vencer_por_equipe[$e['id']] = (int)$stmtAVenc->fetchColumn();
        }

        require __DIR__ . '/../views/caminhamentos/cadastrar.php';
    }

    /* =====================================================
       SALVAR
    ===================================================== */
    public function store()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();

        $equipe_id     = (int)($_POST['equipe_id'] ?? 0);
        $data_execucao = trim($_POST['data_execucao'] ?? '');
        $observacoes   = trim($_POST['observacoes'] ?? '');
        $trechos_ids   = $_POST['trechos'] ?? [];

        if ($equipe_id <= 0 || $data_execucao === '') {
            $_SESSION['flash_erro'] = 'Equipe e data são obrigatórios.';
            header('Location: ' . APP_BASE . '/caminhamentos/cadastrar');
            exit;
        }

        $trechos_validos = [];
        foreach ($trechos_ids as $tid) {
            $tid = (int)$tid;
            if ($tid > 0) $trechos_validos[] = $tid;
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare("
                INSERT INTO caminhamentos (equipe_id, planejador_id, data_execucao, status, observacoes)
                VALUES (?, ?, ?, 'rascunho', ?)
            ")->execute([
                $equipe_id,
                $_SESSION['usuario_id'] ?? 0,
                $data_execucao,
                $observacoes ?: null,
            ]);

            $caminhamento_id = $pdo->lastInsertId();

            if (!empty($trechos_validos)) {
                $stmt = $pdo->prepare("
                    INSERT INTO caminhamento_trechos (caminhamento_id, trecho_id, sequencia)
                    VALUES (?, ?, ?)
                ");
                foreach ($trechos_validos as $seq => $tid) {
                    $stmt->execute([$caminhamento_id, $tid, $seq + 1]);
                }

                // Marcar trechos como programados
                $pdo->prepare("
                    UPDATE trechos SET status_rede = 'programado'
                    WHERE id IN (" . implode(',', array_fill(0, count($trechos_validos), '?')) . ")
                ")->execute($trechos_validos);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_erro'] = 'Erro ao salvar caminhamento.';
            header('Location: ' . APP_BASE . '/caminhamentos/cadastrar');
            exit;
        }

        $_SESSION['flash_ok'] = 'Caminhamento criado (rascunho). Revise e publique quando pronto.';
        header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $caminhamento_id);
        exit;
    }

    /* =====================================================
       PUBLICAR
    ===================================================== */
    public function publicar()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_erro'] = 'Caminhamento inválido.';
            header('Location: ' . APP_BASE . '/caminhamentos');
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, status FROM caminhamentos WHERE id = ?");
        $stmt->execute([$id]);
        $cam = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cam || $cam['status'] !== 'rascunho') {
            $_SESSION['flash_erro'] = 'Só é possível publicar caminhamentos em rascunho.';
            header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $id);
            exit;
        }

        // Regra PA11: bloquear publicação se há documentos vencidos na equipe
        $stmtEquipe = $pdo->prepare("SELECT equipe_id FROM caminhamentos WHERE id = ?");
        $stmtEquipe->execute([$id]);
        $equipe_id_cam = (int)$stmtEquipe->fetchColumn();

        $stmtDocBlock = $pdo->prepare("
            SELECT COUNT(*)
            FROM funcionario_documentos fd
            JOIN equipe_funcionarios ef ON ef.funcionario_id = fd.funcionario_id AND ef.ativo = 1
            WHERE ef.equipe_id = ? AND fd.data_validade IS NOT NULL AND fd.data_validade < CURDATE()
        ");
        $stmtDocBlock->execute([$equipe_id_cam]);
        if ((int)$stmtDocBlock->fetchColumn() > 0) {
            $_SESSION['flash_erro'] = 'Não é possível publicar: há documentos vencidos em membros desta equipe. Atualize os documentos antes de publicar.';
            header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $id);
            exit;
        }

        $pdo->beginTransaction();
        try {
            // Mudar status para publicado
            $pdo->prepare("UPDATE caminhamentos SET status = 'publicado' WHERE id = ?")
                ->execute([$id]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_erro'] = 'Erro ao publicar caminhamento: ' . $e->getMessage();
            header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $id);
            exit;
        }

        $_SESSION['flash_ok'] = 'Caminhamento publicado.';
        header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $id);
        exit;
    }

    /* =====================================================
       CONCLUIR TRECHO — Regra 20 (auto-repav)
    ===================================================== */
    public function concluirTrecho()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();

        $caminhamento_id = (int)($_POST['caminhamento_id'] ?? 0);
        $trecho_id       = (int)($_POST['trecho_id'] ?? 0);

        if ($caminhamento_id <= 0 || $trecho_id <= 0) {
            $_SESSION['flash_erro'] = 'Dados inválidos.';
            header('Location: ' . APP_BASE . '/caminhamentos');
            exit;
        }

        // Verificar que o trecho pertence ao caminhamento
        $stmt = $pdo->prepare("
            SELECT ct.id, ct.status, c.status AS cam_status
            FROM caminhamento_trechos ct
            JOIN caminhamentos c ON c.id = ct.caminhamento_id
            WHERE ct.caminhamento_id = ? AND ct.trecho_id = ?
        ");
        $stmt->execute([$caminhamento_id, $trecho_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $_SESSION['flash_erro'] = 'Trecho não pertence a este caminhamento.';
            header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $caminhamento_id);
            exit;
        }

        if ($row['ct_status'] === 'concluido') {
            $_SESSION['flash_erro'] = 'Este trecho já foi concluído.';
            header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $caminhamento_id);
            exit;
        }

        if (!in_array($row['cam_status'], ['publicado', 'execucao'])) {
            $_SESSION['flash_erro'] = 'O caminhamento precisa estar publicado para concluir trechos.';
            header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $caminhamento_id);
            exit;
        }

        $pdo->beginTransaction();
        try {
            // 1. Marcar trecho no caminhamento como concluído
            $pdo->prepare("
                UPDATE caminhamento_trechos SET status = 'concluido'
                WHERE caminhamento_id = ? AND trecho_id = ?
            ")->execute([$caminhamento_id, $trecho_id]);

            // 2. Mover caminhamento para execucao se ainda estava publicado
            if ($row['cam_status'] === 'publicado') {
                $pdo->prepare("UPDATE caminhamentos SET status = 'execucao' WHERE id = ?")
                    ->execute([$caminhamento_id]);
            }

            // 3. Regra 20: marcar trecho como concluído + entrada automática na fila de repav
            $pdo->prepare("
                UPDATE trechos
                SET status_rede = 'concluido', status_repav = 'aguardando'
                WHERE id = ?
            ")->execute([$trecho_id]);

            // 4. Se todos os trechos do caminhamento estão concluídos → fechar caminhamento
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM caminhamento_trechos
                WHERE caminhamento_id = ? AND status != 'concluido'
            ");
            $stmt->execute([$caminhamento_id]);
            if ((int)$stmt->fetchColumn() === 0) {
                $pdo->prepare("UPDATE caminhamentos SET status = 'concluido' WHERE id = ?")
                    ->execute([$caminhamento_id]);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_erro'] = 'Erro ao concluir trecho: ' . $e->getMessage();
            header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $caminhamento_id);
            exit;
        }

        $_SESSION['flash_ok'] = 'Trecho concluído e adicionado à fila de repavimentação.';
        header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $caminhamento_id);
        exit;
    }

    /* =====================================================
       EXCLUIR — só rascunho ou publicado (sem diários)
    ===================================================== */
    public function excluir()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_erro'] = 'Caminhamento inválido.';
            header('Location: ' . APP_BASE . '/caminhamentos');
            exit;
        }

        $cam = $pdo->prepare("SELECT id, status FROM caminhamentos WHERE id = ?");
        $cam->execute([$id]);
        $cam = $cam->fetch(PDO::FETCH_ASSOC);

        if (!$cam || in_array($cam['status'], ['execucao', 'concluido'])) {
            $_SESSION['flash_erro'] = 'Não é possível excluir um caminhamento em execução ou concluído.';
            header('Location: ' . APP_BASE . '/caminhamentos');
            exit;
        }

        $pdo->beginTransaction();
        try {
            // Pegar trechos vinculados
            $stmtT = $pdo->prepare("SELECT trecho_id FROM caminhamento_trechos WHERE caminhamento_id = ?");
            $stmtT->execute([$id]);
            $trechoIds = $stmtT->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($trechoIds)) {
                // Voltar trechos para livre
                $in = implode(',', array_fill(0, count($trechoIds), '?'));
                $pdo->prepare("UPDATE trechos SET status_rede = 'livre' WHERE id IN ($in) AND status_rede = 'programado'")
                    ->execute($trechoIds);
            }

            $pdo->prepare("DELETE FROM caminhamento_trechos WHERE caminhamento_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM caminhamentos WHERE id = ?")->execute([$id]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_erro'] = 'Erro ao excluir: ' . $e->getMessage();
            header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $id);
            exit;
        }

        $_SESSION['flash_ok'] = 'Caminhamento excluído. Trechos liberados.';
        header('Location: ' . APP_BASE . '/caminhamentos');
        exit;
    }

    /* =====================================================
       ADICIONAR TRECHOS — a um caminhamento rascunho ou publicado
    ===================================================== */
    public function adicionarTrechos()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();

        $id         = (int)($_POST['id'] ?? 0);
        $trechoIds  = $_POST['trechos'] ?? [];

        $cam = $pdo->prepare("SELECT id, status FROM caminhamentos WHERE id = ?");
        $cam->execute([$id]);
        $cam = $cam->fetch(PDO::FETCH_ASSOC);

        if (!$cam || in_array($cam['status'], ['execucao', 'concluido'])) {
            $_SESSION['flash_erro'] = 'Não é possível alterar trechos de um caminhamento em execução ou concluído.';
            header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $id);
            exit;
        }

        $pdo->beginTransaction();
        try {
            // Próxima sequência
            $maxSeq = $pdo->prepare("SELECT COALESCE(MAX(sequencia), 0) FROM caminhamento_trechos WHERE caminhamento_id = ?");
            $maxSeq->execute([$id]);
            $seq = (int)$maxSeq->fetchColumn();

            $stmtIns = $pdo->prepare("INSERT IGNORE INTO caminhamento_trechos (caminhamento_id, trecho_id, sequencia) VALUES (?, ?, ?)");
            $novos = [];
            foreach ($trechoIds as $tid) {
                $tid = (int)$tid;
                if ($tid <= 0) continue;
                $seq++;
                $stmtIns->execute([$id, $tid, $seq]);
                if ($stmtIns->rowCount() > 0) $novos[] = $tid;
            }

            if (!empty($novos)) {
                $in = implode(',', array_fill(0, count($novos), '?'));
                $pdo->prepare("UPDATE trechos SET status_rede = 'programado' WHERE id IN ($in)")
                    ->execute($novos);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_erro'] = 'Erro ao adicionar trechos: ' . $e->getMessage();
            header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $id);
            exit;
        }

        $_SESSION['flash_ok'] = count($novos ?? []) . ' trecho(s) adicionado(s) ao caminhamento.';
        header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $id);
        exit;
    }

    /* =====================================================
       RELATÓRIO — SOLICITAÇÃO DE MATERIAIS
    ===================================================== */
    public function relatorioMateriais()
    {
        auth_required([4]);
        global $pdo;

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . APP_BASE . '/caminhamentos');
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT c.*, e.nome AS equipe_nome
            FROM caminhamentos c
            JOIN equipes e ON e.id = c.equipe_id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $caminhamento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$caminhamento) {
            header('Location: ' . APP_BASE . '/caminhamentos');
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT mc.nome AS material_nome, mc.unidade, SUM(tm.quantidade) AS total_qtd
            FROM caminhamento_trechos ct
            JOIN trecho_materiais tm ON tm.trecho_id = ct.trecho_id
            JOIN materiais_catalogo mc ON mc.id = tm.material_id
            WHERE ct.caminhamento_id = ?
            GROUP BY mc.id, mc.nome, mc.unidade
            ORDER BY mc.nome
        ");
        $stmt->execute([$id]);
        $materiais = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT t.pv_montante, t.pv_jusante, t.extensao, t.rua, t.bacia, t.cidade, ct.sequencia
            FROM caminhamento_trechos ct
            JOIN trechos t ON t.id = ct.trecho_id
            WHERE ct.caminhamento_id = ?
            ORDER BY ct.sequencia
        ");
        $stmt->execute([$id]);
        $trechos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/caminhamentos/relatorio_materiais.php';
    }

    /* =====================================================
       RELATÓRIO — MEDIÇÃO DA REDE
    ===================================================== */
    public function relatorioMedicao()
    {
        auth_required([4]);
        global $pdo;

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . APP_BASE . '/caminhamentos');
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT c.*, e.nome AS equipe_nome
            FROM caminhamentos c
            JOIN equipes e ON e.id = c.equipe_id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $caminhamento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$caminhamento) {
            header('Location: ' . APP_BASE . '/caminhamentos');
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT ct.sequencia, ct.status AS ct_status,
                   t.id AS trecho_id, t.pv_montante, t.pv_jusante, t.bacia, t.rua, t.extensao, t.status_rede
            FROM caminhamento_trechos ct
            JOIN trechos t ON t.id = ct.trecho_id
            WHERE ct.caminhamento_id = ?
            ORDER BY ct.sequencia
        ");
        $stmt->execute([$id]);
        $trechos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Materiais baixados via movimentos
        $trecho_ids = array_column($trechos, 'trecho_id');
        $materiais_baixados = [];
        if (!empty($trecho_ids)) {
            $in = implode(',', array_fill(0, count($trecho_ids), '?'));
            $stmt = $pdo->prepare("
                SELECT mc.nome AS material_nome, mc.unidade, SUM(mm.quantidade) AS total_baixado
                FROM materiais_movimentos mm
                JOIN materiais_catalogo mc ON mc.id = mm.material_id
                WHERE mm.tipo = 'baixa' AND mm.referencia_tipo = 'trecho'
                  AND mm.referencia_id IN ($in)
                GROUP BY mc.id, mc.nome, mc.unidade
                ORDER BY mc.nome
            ");
            $stmt->execute($trecho_ids);
            $materiais_baixados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        require __DIR__ . '/../views/caminhamentos/relatorio_medicao.php';
    }
}
