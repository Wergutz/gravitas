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
                   (SELECT COUNT(*) FROM caminhamento_trechos ct WHERE ct.caminhamento_id = c.id) AS total_trechos
            FROM caminhamentos c
            JOIN equipes e ON e.id = c.equipe_id
            ORDER BY c.data_execucao DESC, e.nome
        ");
        $caminhamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/caminhamentos/listar.php';
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

        // Validar trechos com OS ativa
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

        $_SESSION['flash_ok'] = 'Caminhamento criado com sucesso (rascunho).';
        header('Location: ' . APP_BASE . '/caminhamentos');
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
            header('Location: ' . APP_BASE . '/caminhamentos');
            exit;
        }

        $pdo->prepare("UPDATE caminhamentos SET status = 'publicado' WHERE id = ?")
            ->execute([$id]);

        $_SESSION['flash_ok'] = 'Caminhamento publicado.';
        header('Location: ' . APP_BASE . '/caminhamentos');
        exit;
    }
}
