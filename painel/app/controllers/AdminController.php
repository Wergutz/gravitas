<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';

class AdminController
{
    private static array $perfilLabels = [
        3 => 'Master Gravitas',
        4 => 'Planejador',
        5 => 'Executor de Rede',
        6 => 'Cliente Master',
        7 => 'Executor de Repavimentação',
    ];

    /* -------------------------------------------------------
       Lista de usuários + KPIs
       ------------------------------------------------------- */
    public function usuarios(): void
    {
        auth_required([3]);
        global $pdo;

        // KPIs
        $kpis = $pdo->query("
            SELECT
              COUNT(*) AS total,
              SUM(ativo = 1) AS ativos,
              SUM(ativo = 1 AND tipo_usuario IN (5,7)) AS executores,
              SUM(ativo = 1 AND tipo_usuario = 6) AS clientes_master,
              SUM(ativo = 1 AND tipo_usuario = 3) AS masters
            FROM usuarios
        ")->fetch(PDO::FETCH_ASSOC);

        // Lista completa
        $usuarios = $pdo->query("
            SELECT u.id, u.nome, u.email, u.tipo_usuario, u.ativo,
                   u.ultimo_acesso, u.force_password_change,
                   e.nome AS equipe_nome
            FROM usuarios u
            LEFT JOIN equipes e ON e.responsavel_id = u.id AND e.ativo = 1
            ORDER BY u.tipo_usuario, u.nome
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Equipes para o modal
        $equipes = $pdo->query("SELECT id, nome FROM equipes WHERE ativo = 1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

        $perfilLabels = self::$perfilLabels;

        require __DIR__ . '/../views/admin/usuarios.php';
    }

    /* -------------------------------------------------------
       Criar / Editar usuário (AJAX JSON)
       ------------------------------------------------------- */
    public function salvarUsuario(): void
    {
        auth_required([3]);
        csrf_verify();
        header('Content-Type: application/json');
        global $pdo;

        $id       = (int)($_POST['id'] ?? 0);
        $nome     = trim($_POST['nome'] ?? '');
        $email    = trim(strtolower($_POST['email'] ?? ''));
        $tipo     = (int)($_POST['tipo_usuario'] ?? 0);
        $equipeId = (int)($_POST['equipe_id'] ?? 0) ?: null;

        if (!$nome || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || !isset(self::$perfilLabels[$tipo])) {
            echo json_encode(['ok' => false, 'msg' => 'Dados inválidos.']);
            return;
        }

        // E-mail duplicado
        $dup = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $dup->execute([$email, $id]);
        if ($dup->fetch()) {
            echo json_encode(['ok' => false, 'msg' => 'E-mail já cadastrado.']);
            return;
        }

        $adminId = (int)$_SESSION['usuario_id'];

        $senhaTexto = trim($_POST['senha'] ?? '');
        $adminId    = (int)$_SESSION['usuario_id'];

        if ($id === 0) {
            // CRIAR — senha definida pelo admin ou auto-gerada
            $provisional = null;
            if ($senhaTexto !== '') {
                $hash = password_hash($senhaTexto, PASSWORD_DEFAULT);
                $forceChange = 0;
            } else {
                $provisional = $this->gerarSenhaProvisoria();
                $hash = password_hash($provisional, PASSWORD_DEFAULT);
                $forceChange = 1;
            }

            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nome, email, senha, tipo_usuario, ativo, force_password_change)
                VALUES (?, ?, ?, ?, 1, ?)
            ");
            $stmt->execute([$nome, $email, $hash, $tipo, $forceChange]);
            $novoId = (int)$pdo->lastInsertId();

            if ($equipeId) {
                $pdo->prepare("UPDATE equipes SET responsavel_id = ? WHERE id = ?")->execute([$novoId, $equipeId]);
            }

            $this->auditoria($adminId, 'criar', $novoId, "Criou usuário {$email} (perfil {$tipo})");

            echo json_encode(['ok' => true, 'acao' => 'criado', 'senha' => $provisional, 'id' => $novoId]);

        } else {
            // EDITAR
            $senhaAlterada = false;
            if ($senhaTexto !== '') {
                $hash = password_hash($senhaTexto, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE usuarios SET nome=?, email=?, tipo_usuario=?, senha=?, force_password_change=0 WHERE id=?")
                    ->execute([$nome, $email, $tipo, $hash, $id]);
                $senhaAlterada = true;
            } else {
                $pdo->prepare("UPDATE usuarios SET nome=?, email=?, tipo_usuario=? WHERE id=?")
                    ->execute([$nome, $email, $tipo, $id]);
            }

            if ($equipeId) {
                $pdo->prepare("UPDATE equipes SET responsavel_id = NULL WHERE responsavel_id = ?")->execute([$id]);
                $pdo->prepare("UPDATE equipes SET responsavel_id = ? WHERE id = ?")->execute([$id, $equipeId]);
            }

            $this->auditoria($adminId, 'editar', $id, "Editou usuário {$email} (perfil {$tipo})" . ($senhaAlterada ? ' + senha' : ''));

            echo json_encode(['ok' => true, 'acao' => 'editado', 'senha_alterada' => $senhaAlterada]);
        }
    }

    /* -------------------------------------------------------
       Resetar senha (AJAX JSON)
       ------------------------------------------------------- */
    public function resetarSenha(): void
    {
        auth_required([3]);
        csrf_verify();
        header('Content-Type: application/json');
        global $pdo;

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['ok' => false, 'msg' => 'ID inválido.']);
            return;
        }

        $senhaTexto = trim($_POST['senha'] ?? '');
        $provisional = null;

        if ($senhaTexto !== '') {
            $hash = password_hash($senhaTexto, PASSWORD_DEFAULT);
            $forceChange = 0;
        } else {
            $provisional = $this->gerarSenhaProvisoria();
            $hash = password_hash($provisional, PASSWORD_DEFAULT);
            $forceChange = 1;
        }

        $pdo->prepare("UPDATE usuarios SET senha = ?, force_password_change = ? WHERE id = ?")
            ->execute([$hash, $forceChange, $id]);

        $adminId = (int)$_SESSION['usuario_id'];
        $this->auditoria($adminId, 'resetar_senha', $id, $senhaTexto ? 'Senha definida pelo admin' : 'Senha provisória gerada pelo admin');

        echo json_encode(['ok' => true, 'senha' => $provisional]);
    }

    /* -------------------------------------------------------
       Ativar / Inativar (AJAX JSON)
       ------------------------------------------------------- */
    public function toggleAtivo(): void
    {
        auth_required([3]);
        csrf_verify();
        header('Content-Type: application/json');
        global $pdo;

        $id    = (int)($_POST['id'] ?? 0);
        $ativo = (int)($_POST['ativo'] ?? 0); // novo estado desejado

        if (!$id) {
            echo json_encode(['ok' => false, 'msg' => 'ID inválido.']);
            return;
        }

        // Proteger último Master Gravitas ativo
        if ($ativo === 0) {
            $stmt = $pdo->prepare("SELECT tipo_usuario FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && (int)$row['tipo_usuario'] === 3) {
                $countMasters = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 3 AND ativo = 1")->fetchColumn();
                if ($countMasters <= 1) {
                    echo json_encode(['ok' => false, 'msg' => 'Não é possível inativar o único Master Gravitas ativo.']);
                    return;
                }
            }
        }

        $pdo->prepare("UPDATE usuarios SET ativo = ? WHERE id = ?")->execute([$ativo, $id]);

        $adminId = (int)$_SESSION['usuario_id'];
        $acao = $ativo ? 'ativar' : 'inativar';
        $this->auditoria($adminId, $acao, $id, "Usuário " . ($ativo ? 'ativado' : 'inativado') . " pelo admin");

        echo json_encode(['ok' => true, 'ativo' => $ativo]);
    }

    /* -------------------------------------------------------
       Helpers privados
       ------------------------------------------------------- */
    private function gerarSenhaProvisoria(): string
    {
        return 'Gravitas@2026';
    }

    private function auditoria(int $adminId, string $acao, ?int $afetadoId, string $detalhes): void
    {
        global $pdo;
        try {
            $pdo->prepare("
                INSERT INTO log_auditoria (admin_id, acao, usuario_afetado_id, detalhes)
                VALUES (?, ?, ?, ?)
            ")->execute([$adminId, $acao, $afetadoId, $detalhes]);
        } catch (\Throwable $e) {
            // Log de auditoria não pode derrubar o request principal
        }
    }
}
