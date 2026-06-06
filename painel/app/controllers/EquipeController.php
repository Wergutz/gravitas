<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';

class EquipeController
{
    /* =====================================================
       LISTAR
    ===================================================== */
    public function listar()
    {
        auth_required([4]);
        global $pdo;

        $equipes = $pdo->query("
            SELECT e.*, u.nome AS responsavel_nome
            FROM equipes e
            LEFT JOIN usuarios u ON u.id = e.responsavel_id
            ORDER BY e.nome
        ")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/equipes/listar.php';
    }

    /* =====================================================
       CADASTRAR
    ===================================================== */
    public function create()
    {
        auth_required([4]);
        global $pdo;

        $executores = $pdo->query("
            SELECT id, nome
            FROM usuarios
            WHERE tipo_usuario = 5 AND ativo = 1
            ORDER BY nome
        ")->fetchAll(PDO::FETCH_ASSOC);

        $funcionarios = $pdo->query("
            SELECT id, nome
            FROM funcionarios
            WHERE ativo = 1
            ORDER BY nome
        ")->fetchAll(PDO::FETCH_ASSOC);

        $maquinasLeves = $pdo->query("
            SELECT id, referencia, modelo
            FROM equipamentos_leves
            WHERE ativo = 1
            ORDER BY referencia
        ")->fetchAll(PDO::FETCH_ASSOC);

        $maquinasPesadas = $pdo->query("
            SELECT id, tipo, placa
            FROM equipamentos_pesados
            WHERE ativo = 1
            ORDER BY tipo
        ")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/equipes/cadastrar.php';
    }

    /* =====================================================
       SALVAR
    ===================================================== */
    public function store()
    {
        auth_required([4]);
        csrf_verify();
        global $pdo;

        $nome = trim($_POST['nome'] ?? '');
        $responsavel_id = (int)($_POST['responsavel_id'] ?? 0);

        if ($nome === '') {
            header('Location: ' . APP_BASE . '/equipes/cadastrar');
            exit;
        }

        $pdo->prepare("
            INSERT INTO equipes (nome, responsavel_id, ativo)
            VALUES (?, ?, 1)
        ")->execute([$nome, $responsavel_id]);

        $equipe_id = $pdo->lastInsertId();

        /* -------- FUNCIONÁRIOS -------- */
        if (!empty($_POST['funcionarios'])) {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO equipe_funcionarios
                (equipe_id, funcionario_id, data_inicio, ativo)
                VALUES (?, ?, CURDATE(), 1)
            ");

            foreach (array_unique($_POST['funcionarios']) as $f) {
                if ($f) {
                    $stmt->execute([$equipe_id, (int)$f]);
                }
            }
        }

        /* -------- MÁQUINAS LEVES -------- */
        if (!empty($_POST['maquinas_leves'])) {
            $stmt = $pdo->prepare("
                INSERT INTO equipes_equipamentos_leves (equipe_id, equipamento_id)
                VALUES (?, ?)
            ");

            foreach (array_unique($_POST['maquinas_leves']) as $m) {
                if ($m) {
                    $stmt->execute([$equipe_id, (int)$m]);
                }
            }
        }

        /* -------- MÁQUINAS PESADAS -------- */
        if (!empty($_POST['maquinas_pesadas'])) {
            $stmt = $pdo->prepare("
                INSERT INTO equipes_equipamentos_pesados (equipe_id, equipamento_id)
                VALUES (?, ?)
            ");

            foreach (array_unique($_POST['maquinas_pesadas']) as $m) {
                if ($m) {
                    $stmt->execute([$equipe_id, (int)$m]);
                }
            }
        }

        header('Location: ' . APP_BASE . '/equipes');
        exit;
    }

/* =====================================================
   EDITAR
===================================================== */
public function edit()
{
    auth_required([4]);
    global $pdo;

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        header('Location: ' . APP_BASE . '/equipes');
        exit;
    }

    /* =========================
       EQUIPE
    ========================== */
    $stmt = $pdo->prepare("SELECT * FROM equipes WHERE id = ?");
    $stmt->execute([$id]);
    $equipe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$equipe) {
        header('Location: ' . APP_BASE . '/equipes');
        exit;
    }

    /* =========================
       EXECUTORES
    ========================== */
    $executores = $pdo->query("
        SELECT id, nome
        FROM usuarios
        WHERE tipo_usuario = 5 AND ativo = 1
        ORDER BY nome
    ")->fetchAll(PDO::FETCH_ASSOC);

    /* =========================
       FUNCIONÁRIOS (GERAL)
    ========================== */
    $funcionarios = $pdo->query("
        SELECT id, nome
        FROM funcionarios
        WHERE ativo = 1
        ORDER BY nome
    ")->fetchAll(PDO::FETCH_ASSOC);

    /* =========================
       FUNCIONÁRIOS DA EQUIPE
    ========================== */
    $idsFuncionariosEquipe = $pdo->prepare("
        SELECT funcionario_id
        FROM equipe_funcionarios
        WHERE equipe_id = ? AND ativo = 1
    ");
    $idsFuncionariosEquipe->execute([$id]);
    $idsFuncionariosEquipe = $idsFuncionariosEquipe->fetchAll(PDO::FETCH_COLUMN);

    /* =========================
       MÁQUINAS LEVES (GERAL)
    ========================== */
    $maquinasLeves = $pdo->query("
        SELECT id, referencia, modelo
        FROM equipamentos_leves
        WHERE ativo = 1
        ORDER BY referencia
    ")->fetchAll(PDO::FETCH_ASSOC);

    /* =========================
       MÁQUINAS LEVES DA EQUIPE
    ========================== */
    $idsMaquinasLevesEquipe = $pdo->prepare("
        SELECT equipamento_id
        FROM equipes_equipamentos_leves
        WHERE equipe_id = ?
    ");
    $idsMaquinasLevesEquipe->execute([$id]);
    $idsMaquinasLevesEquipe = $idsMaquinasLevesEquipe->fetchAll(PDO::FETCH_COLUMN);

    /* =========================
       MÁQUINAS PESADAS (GERAL)
    ========================== */
    $maquinasPesadas = $pdo->query("
        SELECT id, tipo, placa
        FROM equipamentos_pesados
        WHERE ativo = 1
        ORDER BY tipo
    ")->fetchAll(PDO::FETCH_ASSOC);

    /* =========================
       MÁQUINAS PESADAS DA EQUIPE
    ========================== */
    $idsMaquinasPesadasEquipe = $pdo->prepare("
        SELECT equipamento_id
        FROM equipes_equipamentos_pesados
        WHERE equipe_id = ?
    ");
    $idsMaquinasPesadasEquipe->execute([$id]);
    $idsMaquinasPesadasEquipe = $idsMaquinasPesadasEquipe->fetchAll(PDO::FETCH_COLUMN);

    /* =========================
       VIEW
    ========================== */
    require __DIR__ . '/../views/equipes/editar.php';
}

    /* =====================================================
       ATUALIZAR
    ===================================================== */
    public function update()
    {
        auth_required([4]);
        csrf_verify();
        global $pdo;

        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $responsavel_id = (int)($_POST['responsavel_id'] ?? 0);

        if ($id <= 0 || $nome === '') {
            header('Location: ' . APP_BASE . '/equipes');
            exit;
        }

        /* -------- DADOS BÁSICOS -------- */
        $pdo->prepare("
            UPDATE equipes
            SET nome = ?, responsavel_id = ?
            WHERE id = ?
        ")->execute([$nome, $responsavel_id, $id]);

        /* -------- FUNCIONÁRIOS -------- */
        $pdo->prepare("DELETE FROM equipe_funcionarios WHERE equipe_id = ?")
            ->execute([$id]);

        if (!empty($_POST['funcionarios'])) {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO equipe_funcionarios
                (equipe_id, funcionario_id, data_inicio, ativo)
                VALUES (?, ?, CURDATE(), 1)
            ");

            $count = 0;
            foreach (array_unique($_POST['funcionarios']) as $f) {
                if ($count >= 10) break;
                if ($f) {
                    $stmt->execute([$id, (int)$f]);
                    $count++;
                }
            }
        }

        /* -------- MÁQUINAS LEVES -------- */
        $pdo->prepare("DELETE FROM equipes_equipamentos_leves WHERE equipe_id = ?")
            ->execute([$id]);

        if (!empty($_POST['maquinas_leves'])) {
            $stmt = $pdo->prepare("
                INSERT INTO equipes_equipamentos_leves (equipe_id, equipamento_id)
                VALUES (?, ?)
            ");

            foreach (array_unique($_POST['maquinas_leves']) as $m) {
                if ($m) {
                    $stmt->execute([$id, (int)$m]);
                }
            }
        }

        /* -------- MÁQUINAS PESADAS -------- */
        $pdo->prepare("DELETE FROM equipes_equipamentos_pesados WHERE equipe_id = ?")
            ->execute([$id]);

        if (!empty($_POST['maquinas_pesadas'])) {
            $stmt = $pdo->prepare("
                INSERT INTO equipes_equipamentos_pesados (equipe_id, equipamento_id)
                VALUES (?, ?)
            ");

            foreach (array_unique($_POST['maquinas_pesadas']) as $m) {
                if ($m) {
                    $stmt->execute([$id, (int)$m]);
                }
            }
        }

        header('Location: ' . APP_BASE . '/equipes');
        exit;
    }
        /* =====================================================
       APAGAR EQUIPE (SEGURO)
    ===================================================== */
    public function apagar()
    {
        auth_required([4]);
        global $pdo;
    
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . APP_BASE . '/equipes');
            exit;
        }
    
        // 🔒 Não permite apagar se existir planejamento ativo
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM planejamentos 
            WHERE equipe_id = ? AND status = 'ATIVO'
        ");
        $stmt->execute([$id]);
    
        if ($stmt->fetchColumn() > 0) {
            header('Location: ' . APP_BASE . '/equipes?erro=planejamento_ativo');
            exit;
        }
    
        // Remove vínculos primeiro (ordem correta por FK)
        $pdo->prepare("DELETE FROM equipe_funcionarios WHERE equipe_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM equipes_equipamentos_leves WHERE equipe_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM equipes_equipamentos_pesados WHERE equipe_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM equipes_usuarios WHERE equipe_id = ?")->execute([$id]);
    
        // Remove a equipe
        $pdo->prepare("DELETE FROM equipes WHERE id = ?")->execute([$id]);
    
        header('Location: ' . APP_BASE . '/equipes?apagada=1');
        exit;
    }

}
