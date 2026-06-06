<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';

class MaterialController
{
    /* =====================================================
       LISTAR
    ===================================================== */
    public function index()
    {
        auth_required([4]);
        global $pdo;

        $stmt = $pdo->query("
            SELECT mc.*,
                   COALESCE(me.quantidade_fisica, 0)    AS qtd_fisica,
                   COALESCE(me.quantidade_reservada, 0) AS qtd_reservada,
                   COALESCE(me.quantidade_fisica, 0) - COALESCE(me.quantidade_reservada, 0) AS qtd_disponivel
            FROM materiais_catalogo mc
            LEFT JOIN materiais_estoque me ON me.material_id = mc.id
            WHERE mc.ativo = 1
            ORDER BY mc.nome
        ");
        $materiais = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/materiais/listar.php';
    }

    /* =====================================================
       CADASTRAR
    ===================================================== */
    public function create()
    {
        auth_required([4]);
        require __DIR__ . '/../views/materiais/cadastrar.php';
    }

    /* =====================================================
       SALVAR
    ===================================================== */
    public function store()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();

        $codigo          = trim($_POST['codigo'] ?? '') ?: null;
        $nome            = trim($_POST['nome'] ?? '');
        $unidade         = trim($_POST['unidade'] ?? 'un');
        $estoque_minimo  = str_replace(',', '.', trim($_POST['estoque_minimo'] ?? '0'));

        if ($nome === '') {
            $_SESSION['flash_erro'] = 'Nome do material é obrigatório.';
            header('Location: ' . APP_BASE . '/materiais/cadastrar');
            exit;
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare("
                INSERT INTO materiais_catalogo (codigo, nome, unidade, estoque_minimo)
                VALUES (?, ?, ?, ?)
            ")->execute([
                $codigo,
                $nome,
                $unidade,
                is_numeric($estoque_minimo) ? $estoque_minimo : 0,
            ]);

            $material_id = $pdo->lastInsertId();

            // Cria linha de estoque zerada
            $pdo->prepare("
                INSERT INTO materiais_estoque (material_id, quantidade_fisica, quantidade_reservada)
                VALUES (?, 0, 0)
            ")->execute([$material_id]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_erro'] = 'Erro ao cadastrar material.';
            header('Location: ' . APP_BASE . '/materiais/cadastrar');
            exit;
        }

        $_SESSION['flash_ok'] = 'Material cadastrado com sucesso.';
        header('Location: ' . APP_BASE . '/materiais');
        exit;
    }

    /* =====================================================
       MOVIMENTO (ENTRADA / AJUSTE)
    ===================================================== */
    public function movimento()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();

        $material_id  = (int)($_POST['material_id'] ?? 0);
        $tipo         = trim($_POST['tipo'] ?? '');
        $quantidade   = str_replace(',', '.', trim($_POST['quantidade'] ?? ''));
        $observacao   = trim($_POST['observacao'] ?? '') ?: null;

        if ($material_id <= 0 || !in_array($tipo, ['entrada', 'ajuste']) || !is_numeric($quantidade) || (float)$quantidade <= 0) {
            $_SESSION['flash_erro'] = 'Dados inválidos para o movimento.';
            header('Location: ' . APP_BASE . '/materiais');
            exit;
        }

        $qtd = (float)$quantidade;

        $pdo->beginTransaction();
        try {
            // Registrar movimento
            $pdo->prepare("
                INSERT INTO materiais_movimentos (material_id, tipo, quantidade, referencia_tipo, observacao, usuario_id)
                VALUES (?, ?, ?, 'ajuste_manual', ?, ?)
            ")->execute([
                $material_id,
                $tipo,
                $qtd,
                $observacao,
                $_SESSION['usuario_id'] ?? 0,
            ]);

            // Atualizar estoque
            if ($tipo === 'entrada') {
                $pdo->prepare("
                    INSERT INTO materiais_estoque (material_id, quantidade_fisica)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE quantidade_fisica = quantidade_fisica + VALUES(quantidade_fisica)
                ")->execute([$material_id, $qtd]);
            } else { // ajuste: substitui o valor
                $pdo->prepare("
                    UPDATE materiais_estoque SET quantidade_fisica = ? WHERE material_id = ?
                ")->execute([$qtd, $material_id]);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_erro'] = 'Erro ao lançar movimento.';
            header('Location: ' . APP_BASE . '/materiais');
            exit;
        }

        $_SESSION['flash_ok'] = 'Movimento lançado com sucesso.';
        header('Location: ' . APP_BASE . '/materiais');
        exit;
    }
}
