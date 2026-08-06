<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';

class EquipamentoEquipeController
{
    /* =====================================================
       EQUIPAMENTOS LEVES — LISTAR
       ===================================================== */
    public function listarLeves()
    {
        auth_required([4]);
        global $pdo;

        $equipe_id = (int) $_GET['equipe_id'];

        $stmt = $pdo->prepare("
            SELECT el.id, el.tipo, el.modelo, rel.quantidade
            FROM equipes_equipamentos_leves rel
            JOIN equipamentos_leves el ON el.id = rel.equipamento_id
            WHERE rel.equipe_id = ?
        ");
        $stmt->execute([$equipe_id]);
        $equipamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/equipes/equipamentos/leves_listar.php';
    }

    /* =====================================================
       EQUIPAMENTOS LEVES — CADASTRAR
       ===================================================== */
    public function cadastrarLeve()
    {
        auth_required([4]);
        global $pdo;

        $equipe_id = (int) $_GET['equipe_id'];

        $equipamentos = $pdo->query("
            SELECT id, tipo, modelo
            FROM equipamentos_leves
            WHERE ativo = 1
            ORDER BY tipo
        ")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/equipes/equipamentos/leves_cadastrar.php';
    }

    public function salvarLeve()
    {
        auth_required([4]);
        csrf_verify();
        global $pdo;

        $stmt = $pdo->prepare("
            INSERT INTO equipes_equipamentos_leves
            (equipe_id, equipamento_id, quantidade)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $_POST['equipe_id'],
            $_POST['equipamento_id'],
            $_POST['quantidade']
        ]);

        header('Location: ' . APP_BASE . '/equipes/equipamentos/leves?equipe_id=' . $_POST['equipe_id']);
        exit;
    }

    /* =====================================================
       EQUIPAMENTOS PESADOS — LISTAR
       ===================================================== */
    public function listarPesados()
    {
        auth_required([4]);
        global $pdo;

        $equipe_id = (int) $_GET['equipe_id'];

        $stmt = $pdo->prepare("
            SELECT ep.id, ep.tipo, ep.modelo, ep.placa, rep.operador
            FROM equipes_equipamentos_pesados rep
            JOIN equipamentos_pesados ep ON ep.id = rep.equipamento_id
            WHERE rep.equipe_id = ?
        ");
        $stmt->execute([$equipe_id]);
        $equipamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/equipes/equipamentos/pesados_listar.php';
    }

    /* =====================================================
       EQUIPAMENTOS PESADOS — CADASTRAR
       ===================================================== */
    public function cadastrarPesado()
    {
        auth_required([4]);
        global $pdo;

        $equipe_id = (int) $_GET['equipe_id'];

        $equipamentos = $pdo->query("
            SELECT id, tipo, modelo, placa
            FROM equipamentos_pesados
            WHERE ativo = 1
            ORDER BY tipo
        ")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/equipes/equipamentos/pesados_cadastrar.php';
    }

    public function salvarPesado()
    {
        auth_required([4]);
        csrf_verify();
        global $pdo;

        $stmt = $pdo->prepare("
            INSERT INTO equipes_equipamentos_pesados
            (equipe_id, equipamento_id, operador)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $_POST['equipe_id'],
            $_POST['equipamento_id'],
            trim($_POST['operador'])
        ]);

        header('Location: ' . APP_BASE . '/equipes/equipamentos/pesados?equipe_id=' . $_POST['equipe_id']);
        exit;
    }
}
