<?php
session_start();

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/database.php';

auth_required([4]); // Planejador

$id              = $_POST['id'] ?? null;
$nome            = trim($_POST['nome'] ?? '');
$encarregado     = trim($_POST['encarregado'] ?? '');
$usuarioSistema  = trim($_POST['usuario_sistema'] ?? '');
$divideFrente    = $_POST['divide_frente'] ?? 'NAO';
$frenteNome      = trim($_POST['frente_nome'] ?? '');

if ($nome === '' || $encarregado === '') {
    $_SESSION['erro'] = 'Preencha todos os campos obrigatórios.';
    header('Location: equipes_rede.php' . ($id ? '?id='.$id : ''));
    exit;
}

try {
    if ($id) {
        /* =========================
           ATUALIZAR EQUIPE
        ========================= */
        $stmt = $pdo->prepare("
            UPDATE equipes SET
                nome = ?,
                responsavel = ?,
                usuario_sistema = ?,
                divide_frente = ?,
                frente_nome = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $nome,
            $encarregado,
            $usuarioSistema,
            $divideFrente,
            $frenteNome,
            $id
        ]);

    } else {
        /* =========================
           NOVA EQUIPE
        ========================= */
        $stmt = $pdo->prepare("
            INSERT INTO equipes
                (nome, responsavel, usuario_sistema, divide_frente, frente_nome, ativo)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $nome,
            $encarregado,
            $usuarioSistema,
            $divideFrente,
            $frenteNome
        ]);

        $id = $pdo->lastInsertId();
    }

    $_SESSION['sucesso'] = 'Equipe salva com sucesso.';
    header("Location: equipes_rede.php?id=".$id);
    exit;

} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro ao salvar equipe.';
    header('Location: equipes_rede.php' . ($id ? '?id='.$id : ''));
    exit;
}
