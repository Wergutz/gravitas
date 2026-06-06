<?php

class FuncionarioEquipeController
{
    /* =====================================================
       LISTAR FUNCIONÁRIOS DA EQUIPE
       ===================================================== */
    public function listar()
    {
        auth_required([4]);
        global $pdo;

        $equipe_id = (int) $_GET['equipe_id'];

        $stmt = $pdo->prepare("
            SELECT *
            FROM equipes_funcionarios
            WHERE equipe_id = ?
            ORDER BY nome
        ");
        $stmt->execute([$equipe_id]);
        $funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/equipes/funcionarios/listar.php';
    }

    /* =====================================================
       FORMULÁRIO DE CADASTRO
       ===================================================== */
    public function create()
    {
        auth_required([4]);
        $equipe_id = (int) $_GET['equipe_id'];

        require __DIR__ . '/../views/equipes/funcionarios/cadastrar.php';
    }

    /* =====================================================
       SALVAR FUNCIONÁRIO NA EQUIPE
       ===================================================== */
    public function store()
    {
        auth_required([4]);
        global $pdo;

        $sql = "
            INSERT INTO equipes_funcionarios
            (equipe_id, nome, funcao, aso, nr06, nr10, nr11, nr12, nr18, nr20, nr23, nr33, nr35, sertras)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['equipe_id'],
            trim($_POST['nome']),
            trim($_POST['funcao']),
            $_POST['aso'],
            $_POST['nr06'],
            $_POST['nr10'],
            $_POST['nr11'],
            $_POST['nr12'],
            $_POST['nr18'],
            $_POST['nr20'],
            $_POST['nr23'],
            $_POST['nr33'],
            $_POST['nr35'],
            $_POST['sertras']
        ]);

        header('Location: ' . APP_BASE . '/equipes/funcionarios?equipe_id=' . $_POST['equipe_id']);
        exit;
    }
}
