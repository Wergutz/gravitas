<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('MU2_PAINEL');
    session_set_cookie_params(['path' => '/marco_urbano2/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

/* ===== SOMENTE MASTER ===== */
if (!isset($_SESSION['usuario_id'], $_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] != 1) {
    header('Location: /marco_urbano2/public/login.php');
    exit;
}

require_once __DIR__ . '/../app/config/database.php';

/* =================================================
   EXCLUIR PLANEJAMENTO COMPLETO (CASCADE MANUAL)
================================================= */
if (isset($_GET['excluir'])) {

    $planejamentoId = (int) $_GET['excluir'];

    try {
        $pdo->beginTransaction();

        /* ===== BUSCAR TRECHOS ===== */
        $trechos = $pdo->prepare("
            SELECT id, croqui
            FROM trechos
            WHERE planejamento_id = ?
        ");
        $trechos->execute([$planejamentoId]);
        $trechos = $trechos->fetchAll(PDO::FETCH_ASSOC);

        foreach ($trechos as $t) {

            /* ===== EXCLUIR FOTOS (DB + ARQUIVO) ===== */
            $fotos = $pdo->prepare("
                SELECT arquivo
                FROM trecho_fotos
                WHERE trecho_id = ?
            ");
            $fotos->execute([$t['id']]);

            foreach ($fotos->fetchAll() as $f) {
                $arquivo = __DIR__ . '/../uploads/fotos/' . $f['arquivo'];
                if (file_exists($arquivo)) unlink($arquivo);
            }

            $pdo->prepare("DELETE FROM trecho_fotos WHERE trecho_id = ?")
                ->execute([$t['id']]);

            /* ===== EXCLUIR PAVIMENTOS ===== */
            $pdo->prepare("DELETE FROM pavimento_produzido WHERE trecho_id = ?")
                ->execute([$t['id']]);

            /* ===== EXCLUIR CROQUI ===== */
            $croqui = __DIR__ . '/../uploads/croquis/' . $t['croqui'];
            if ($t['croqui'] && file_exists($croqui)) unlink($croqui);
        }

        /* ===== EXCLUIR TRECHOS ===== */
        $pdo->prepare("DELETE FROM trechos WHERE planejamento_id = ?")
            ->execute([$planejamentoId]);

        /* ===== EXCLUIR PLANEJAMENTO ===== */
        $pdo->prepare("DELETE FROM planejamentos WHERE id = ?")
            ->execute([$planejamentoId]);

        $pdo->commit();
        header('Location: planejamentos.php?ok=1');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die('Erro ao excluir planejamento: ' . $e->getMessage());
    }
}

/* =================================================
   LISTAGEM
================================================= */
$planejamentos = $pdo->query("
    SELECT p.id, p.created_at, u.nome
    FROM planejamentos p
    JOIN usuarios u ON u.id = p.usuario_id
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Planejamentos | MASTER</title>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 30px;
    background: #f9fafb;
}

h1 { margin-bottom: 20px; }

table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}

th {
    background: #111827;
    color: #fff;
    padding: 8px;
}

td {
    padding: 8px;
    border: 1px solid #ddd;
    text-align: center;
}

a {
    text-decoration: none;
    font-weight: bold;
}

.excluir {
    color: #dc2626;
}

.voltar {
    display: inline-block;
    margin-bottom: 15px;
}
</style>
</head>

<body>

<a class="voltar" href="usuarios.php">← Voltar ao painel MASTER</a>

<h1>Planejamentos – MASTER</h1>

<?php if (isset($_GET['ok'])): ?>
<p style="color:green;font-weight:bold;">Planejamento excluído com sucesso.</p>
<?php endif; ?>

<table>
<thead>
<tr>
    <th>ID</th>
    <th>Planejador</th>
    <th>Data</th>
    <th>Ações</th>
</tr>
</thead>
<tbody>
<?php foreach ($planejamentos as $p): ?>
<tr>
    <td>#<?= $p['id'] ?></td>
    <td><?= htmlspecialchars($p['nome']) ?></td>
    <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
    <td>
        <a href="/marco_urbano2/planejador/relatorio.php?planejamento_id=<?= $p['id'] ?>" target="_blank">
            Ver Relatório
        </a>
        |
        <a
            class="excluir"
            href="planejamentos.php?excluir=<?= $p['id'] ?>"
            onclick="return confirm('ATENÇÃO: isso irá excluir TODAS as medições, fotos e croquis. Deseja continuar?')"
        >
            Excluir
        </a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</body>
</html>
