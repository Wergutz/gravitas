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

        // Materiais reservados por trecho
        $materiais_por_trecho = [];
        foreach ($trechos_cam as $tc) {
            $stmt2 = $pdo->prepare("
                SELECT tm.*, mc.nome AS material_nome, mc.unidade,
                       COALESCE(me.quantidade_fisica, 0) AS qtd_fisica,
                       COALESCE(me.quantidade_reservada, 0) AS qtd_reservada
                FROM trecho_materiais tm
                JOIN materiais_catalogo mc ON mc.id = tm.material_id
                LEFT JOIN materiais_estoque me ON me.material_id = tm.material_id
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

        // Trechos disponíveis: com OS ativa e status=livre
        $trechos_disponiveis = $pdo->query("
            SELECT t.id, t.pv_montante, t.pv_jusante, t.bacia, t.extensao, t.rua,
                   os.versao AS os_versao
            FROM trechos t
            JOIN ordens_servico os ON os.trecho_id = t.id AND os.ativa = 1
            WHERE t.status_rede = 'livre'
            ORDER BY t.bacia, t.pv_montante
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Regra 18: docs vencidos por equipe
        $docs_vencidos_por_equipe = [];
        foreach ($equipes as $e) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM funcionario_documentos fd
                JOIN equipe_funcionarios ef ON ef.funcionario_id = fd.funcionario_id AND ef.ativo = 1
                WHERE ef.equipe_id = ? AND fd.data_validade < CURDATE()
            ");
            $stmt->execute([$e['id']]);
            $docs_vencidos_por_equipe[$e['id']] = (int)$stmt->fetchColumn();
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

        // Regra 17: Validar que cada trecho tem OS ativa
        $trechos_validos = [];
        foreach ($trechos_ids as $tid) {
            $tid = (int)$tid;
            if ($tid <= 0) continue;
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM ordens_servico
                WHERE trecho_id = ? AND ativa = 1
            ");
            $stmt->execute([$tid]);
            if ($stmt->fetchColumn() > 0) {
                $trechos_validos[] = $tid;
            }
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
       PUBLICAR — Regra 19: reserva materiais
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

        // Buscar trechos do caminhamento
        $stmt = $pdo->prepare("SELECT trecho_id FROM caminhamento_trechos WHERE caminhamento_id = ?");
        $stmt->execute([$id]);
        $trecho_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $pdo->beginTransaction();
        try {
            // Mudar status para publicado
            $pdo->prepare("UPDATE caminhamentos SET status = 'publicado' WHERE id = ?")
                ->execute([$id]);

            // Regra 19: reservar materiais de cada trecho
            $stmtMat = $pdo->prepare("SELECT material_id, quantidade FROM trecho_materiais WHERE trecho_id = ?");
            $stmtMov = $pdo->prepare("
                INSERT INTO materiais_movimentos
                    (material_id, tipo, quantidade, referencia_tipo, referencia_id, observacao, usuario_id)
                VALUES (?, 'reserva', ?, 'caminhamento', ?, 'Reserva automática — publicação caminhamento', ?)
            ");
            $stmtRes = $pdo->prepare("
                UPDATE materiais_estoque
                SET quantidade_reservada = quantidade_reservada + ?
                WHERE material_id = ?
            ");

            foreach ($trecho_ids as $tid) {
                $stmtMat->execute([$tid]);
                foreach ($stmtMat->fetchAll(PDO::FETCH_ASSOC) as $mat) {
                    $stmtMov->execute([$mat['material_id'], $mat['quantidade'], $id, $_SESSION['usuario_id'] ?? 0]);
                    $stmtRes->execute([$mat['quantidade'], $mat['material_id']]);
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_erro'] = 'Erro ao publicar caminhamento: ' . $e->getMessage();
            header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $id);
            exit;
        }

        $_SESSION['flash_ok'] = 'Caminhamento publicado. Materiais reservados no estoque.';
        header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $id);
        exit;
    }

    /* =====================================================
       CONCLUIR TRECHO — Regras 19 (baixa) + 20 (auto-repav)
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

            // 4. Regra 19 (baixa): decrementar física e reservada
            $stmtMat = $pdo->prepare("SELECT material_id, quantidade FROM trecho_materiais WHERE trecho_id = ?");
            $stmtMat->execute([$trecho_id]);
            $stmtMov = $pdo->prepare("
                INSERT INTO materiais_movimentos
                    (material_id, tipo, quantidade, referencia_tipo, referencia_id, observacao, usuario_id)
                VALUES (?, 'baixa', ?, 'trecho', ?, 'Baixa automática — trecho concluído', ?)
            ");
            $stmtBaixa = $pdo->prepare("
                UPDATE materiais_estoque
                SET quantidade_fisica    = GREATEST(0, quantidade_fisica    - ?),
                    quantidade_reservada = GREATEST(0, quantidade_reservada - ?)
                WHERE material_id = ?
            ");
            foreach ($stmtMat->fetchAll(PDO::FETCH_ASSOC) as $mat) {
                $stmtMov->execute([$mat['material_id'], $mat['quantidade'], $trecho_id, $_SESSION['usuario_id'] ?? 0]);
                $stmtBaixa->execute([$mat['quantidade'], $mat['quantidade'], $mat['material_id']]);
            }

            // 5. Se todos os trechos do caminhamento estão concluídos → fechar caminhamento
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

        $_SESSION['flash_ok'] = 'Trecho concluído. Material baixado e trecho adicionado à fila de repavimentação.';
        header('Location: ' . APP_BASE . '/caminhamentos/detalhe?id=' . $caminhamento_id);
        exit;
    }
}
