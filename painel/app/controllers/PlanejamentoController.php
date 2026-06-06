<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';

class PlanejamentoController
{
    /* =====================================================
       LISTAR
    ===================================================== */
    public function index()
    {
        auth_required([4]);
        global $pdo;

        $planejamentos = $pdo->query("
            SELECT p.*, e.nome AS equipe_nome
            FROM planejamentos p
            JOIN equipes e ON e.id = p.equipe_id
            ORDER BY p.data_execucao DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/planejamentos/listar.php';
    }

    /* =====================================================
       FORM CADASTRAR
    ===================================================== */
    public function create()
    {
        auth_required([4]);
        global $pdo;

        $equipes = $pdo->query("
            SELECT id, nome
            FROM equipes
            WHERE ativo = 1
            ORDER BY nome
        ")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/planejamentos/cadastrar.php';
    }

    /* =====================================================
       SALVAR
    ===================================================== */
    public function store()
    {
        auth_required([4]);
        global $pdo;

        $planejador_id = $_SESSION['auth']['id']; // ✅ CORRETO
        $equipe_id     = (int)$_POST['equipe_id'];
        $data_execucao = $_POST['data_execucao'] ?? null;

        $stmt = $pdo->prepare("
            INSERT INTO planejamentos (
                equipe_id, planejador_id, data_execucao,
                macro, medicao, cidade, contrato, bacia,
                trecho, pv_montante, tipo_pi_montante,
                quantidade_pvs, altura_pv
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $equipe_id,
            $planejador_id,
            $data_execucao,
            $_POST['macro'] ?? null,
            $_POST['medicao'] ?? null,
            $_POST['cidade'] ?? null,
            $_POST['contrato'] ?? null,
            $_POST['bacia'] ?? null,
            $_POST['trecho'] ?? null,
            $_POST['pv_montante'] ?? null,
            $_POST['tipo_pi_montante'] ?? null,
            $_POST['quantidade_pvs'] ?? null,
            $_POST['altura_pv'] ?? null,
        ]);

        header('Location: ' . APP_BASE . '/planejamentos');
        exit;
    }

    /* =====================================================
       EDITAR
    ===================================================== */
    public function edit()
    {
        auth_required([4]);
        global $pdo;

        $id = (int)$_GET['id'];

        $stmt = $pdo->prepare("SELECT * FROM planejamentos WHERE id = ?");
        $stmt->execute([$id]);
        $planejamento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$planejamento) {
            header('Location: ' . APP_BASE . '/planejamentos');
            exit;
        }

        $equipes = $pdo->query("
            SELECT id, nome FROM equipes WHERE ativo = 1
        ")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/planejamentos/editar.php';
    }

    /* =====================================================
       ATUALIZAR
    ===================================================== */
    public function update()
    {
        auth_required([4]);
        global $pdo;

        $stmt = $pdo->prepare("
            UPDATE planejamentos SET
                equipe_id = ?, data_execucao = ?,
                macro = ?, medicao = ?, cidade = ?, contrato = ?, bacia = ?,
                trecho = ?, pv_montante = ?, tipo_pi_montante = ?,
                quantidade_pvs = ?, altura_pv = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $_POST['equipe_id'],
            $_POST['data_execucao'],
            $_POST['macro'],
            $_POST['medicao'],
            $_POST['cidade'],
            $_POST['contrato'],
            $_POST['bacia'],
            $_POST['trecho'],
            $_POST['pv_montante'],
            $_POST['tipo_pi_montante'],
            $_POST['quantidade_pvs'],
            $_POST['altura_pv'],
            $_POST['id']
        ]);

        header('Location: ' . APP_BASE . '/planejamentos');
        exit;
    }
}
