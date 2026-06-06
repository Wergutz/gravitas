<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';

class RepavimentacaoController
{
    /* =====================================================
       LISTAR
    ===================================================== */
    public function index()
    {
        auth_required([4]);
        global $pdo;

        $stmt = $pdo->query("
            SELECT t.id, t.pv_montante, t.pv_jusante, t.bacia, t.rua, t.extensao,
                   t.status_repav,
                   mr.id AS medicao_id, mr.status AS medicao_status
            FROM trechos t
            LEFT JOIN medicoes_repavimentacao mr ON mr.trecho_id = t.id
            WHERE t.status_repav IS NOT NULL
            ORDER BY
                FIELD(t.status_repav, 'aguardando', 'execucao', 'medido'),
                t.bacia, t.pv_montante
        ");
        $trechos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/repavimentacao/listar.php';
    }

    /* =====================================================
       FORMULÁRIO DE MEDIÇÃO
    ===================================================== */
    public function create()
    {
        auth_required([4]);
        global $pdo;

        $trecho_id = (int)($_GET['trecho_id'] ?? 0);
        if ($trecho_id <= 0) {
            header('Location: ' . APP_BASE . '/repavimentacao');
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM trechos WHERE id = ?");
        $stmt->execute([$trecho_id]);
        $trecho = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trecho) {
            header('Location: ' . APP_BASE . '/repavimentacao');
            exit;
        }

        // Buscar ou criar medição
        $stmt = $pdo->prepare("SELECT * FROM medicoes_repavimentacao WHERE trecho_id = ?");
        $stmt->execute([$trecho_id]);
        $medicao = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$medicao) {
            $pdo->prepare("
                INSERT INTO medicoes_repavimentacao (trecho_id, status, criado_por)
                VALUES (?, 'rascunho', ?)
            ")->execute([$trecho_id, $_SESSION['usuario_id'] ?? null]);
            $medicao_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM medicoes_repavimentacao WHERE id = ?");
            $stmt->execute([$medicao_id]);
            $medicao = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Pavimentos já cadastrados
        $stmt = $pdo->prepare("
            SELECT mp.*, GROUP_CONCAT(mpl.comprimento, 'x', mpl.largura ORDER BY mpl.sequencia SEPARATOR '|') AS linhas_raw
            FROM medicao_pavimentos mp
            LEFT JOIN medicao_pavimento_linhas mpl ON mpl.pavimento_id = mp.id
            WHERE mp.medicao_id = ?
            GROUP BY mp.id
            ORDER BY mp.ordem
        ");
        $stmt->execute([$medicao['id']]);
        $pavimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fotos
        $stmt = $pdo->prepare("SELECT * FROM medicao_fotos WHERE medicao_id = ? ORDER BY tipo, criado_em");
        $stmt->execute([$medicao['id']]);
        $fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/repavimentacao/medicao.php';
    }

    /* =====================================================
       SALVAR PAVIMENTO
    ===================================================== */
    public function salvarPavimento()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();

        $medicao_id     = (int)($_POST['medicao_id'] ?? 0);
        $tipo_pavimento = trim($_POST['tipo_pavimento'] ?? '');
        $espessura      = str_replace(',', '.', trim($_POST['espessura_cm'] ?? ''));
        $comprimentos   = $_POST['comprimentos'] ?? [];
        $larguras       = $_POST['larguras'] ?? [];

        $tipos_validos = [
            'paralelepipedo_regular','paralelepipedo_irregular',
            'bloco_concreto','asfalto','asfalto_paralelepipedo',
            'chao_batido','calcada'
        ];

        if ($medicao_id <= 0 || !in_array($tipo_pavimento, $tipos_validos)) {
            $_SESSION['flash_erro'] = 'Dados inválidos.';
            header('Location: ' . APP_BASE . '/repavimentacao');
            exit;
        }

        // Buscar trecho_id para redirecionar
        $stmt = $pdo->prepare("SELECT trecho_id FROM medicoes_repavimentacao WHERE id = ?");
        $stmt->execute([$medicao_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $_SESSION['flash_erro'] = 'Medição não encontrada.';
            header('Location: ' . APP_BASE . '/repavimentacao');
            exit;
        }
        $trecho_id = $row['trecho_id'];

        // Regra 21: ao menos uma linha de dimensão válida
        $hasValidLine = false;
        foreach ($comprimentos as $seq => $comp) {
            $c = str_replace(',', '.', trim($comp));
            $l = str_replace(',', '.', trim($larguras[$seq] ?? ''));
            if (is_numeric($c) && is_numeric($l) && (float)$c > 0 && (float)$l > 0) {
                $hasValidLine = true;
                break;
            }
        }
        if (!$hasValidLine) {
            $_SESSION['flash_erro'] = 'Adicione ao menos uma linha de dimensão (comprimento × largura) antes de salvar.';
            header('Location: ' . APP_BASE . '/repavimentacao/medicao?trecho_id=' . $trecho_id);
            exit;
        }

        // Regra 21 (alerta): asfalto sem espessura — salva mas avisa
        $avisoEspessura = ($tipo_pavimento === 'asfalto' && (!is_numeric($espessura) || (float)$espessura <= 0));

        // Próxima ordem
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(ordem), 0) + 1 FROM medicao_pavimentos WHERE medicao_id = ?");
        $stmt->execute([$medicao_id]);
        $ordem = (int)$stmt->fetchColumn();

        $pdo->beginTransaction();
        try {
            $pdo->prepare("
                INSERT INTO medicao_pavimentos (medicao_id, tipo_pavimento, espessura_cm, ordem)
                VALUES (?, ?, ?, ?)
            ")->execute([
                $medicao_id,
                $tipo_pavimento,
                (is_numeric($espessura) && (float)$espessura > 0) ? $espessura : null,
                $ordem,
            ]);

            $pavimento_id = $pdo->lastInsertId();

            $stmtLinha = $pdo->prepare("
                INSERT INTO medicao_pavimento_linhas (pavimento_id, comprimento, largura, sequencia)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($comprimentos as $seq => $comp) {
                $comp = str_replace(',', '.', trim($comp));
                $larg = str_replace(',', '.', trim($larguras[$seq] ?? ''));
                if (is_numeric($comp) && is_numeric($larg) && (float)$comp > 0 && (float)$larg > 0) {
                    $stmtLinha->execute([$pavimento_id, $comp, $larg, $seq + 1]);
                }
            }

            // Atualizar status do trecho para execucao
            $pdo->prepare("UPDATE trechos SET status_repav = 'execucao' WHERE id = ? AND status_repav = 'aguardando'")
                ->execute([$trecho_id]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_erro'] = 'Erro ao salvar pavimento.';
            header('Location: ' . APP_BASE . '/repavimentacao/medicao?trecho_id=' . $trecho_id);
            exit;
        }

        if ($avisoEspessura) {
            $_SESSION['flash_ok'] = 'Pavimento salvo. Atenção: asfalto sem espessura — volume m³ não será calculado no relatório.';
        } else {
            $_SESSION['flash_ok'] = 'Pavimento salvo com sucesso.';
        }
        header('Location: ' . APP_BASE . '/repavimentacao/medicao?trecho_id=' . $trecho_id);
        exit;
    }

    /* =====================================================
       RELATÓRIO DE REPAVIMENTAÇÃO
    ===================================================== */
    public function relatorio()
    {
        auth_required([4]);
        global $pdo;

        $medicao_id = (int)($_GET['medicao_id'] ?? 0);
        if ($medicao_id <= 0) {
            header('Location: ' . APP_BASE . '/repavimentacao');
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT mr.*, t.pv_montante, t.pv_jusante, t.bacia, t.rua, t.extensao
            FROM medicoes_repavimentacao mr
            JOIN trechos t ON t.id = mr.trecho_id
            WHERE mr.id = ?
        ");
        $stmt->execute([$medicao_id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dados) {
            header('Location: ' . APP_BASE . '/repavimentacao');
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT mp.*,
                   GROUP_CONCAT(mpl.comprimento, 'x', mpl.largura ORDER BY mpl.sequencia SEPARATOR '|') AS linhas_raw
            FROM medicao_pavimentos mp
            LEFT JOIN medicao_pavimento_linhas mpl ON mpl.pavimento_id = mp.id
            WHERE mp.medicao_id = ?
            GROUP BY mp.id
            ORDER BY mp.ordem
        ");
        $stmt->execute([$medicao_id]);
        $pavimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT * FROM medicao_fotos WHERE medicao_id = ? ORDER BY tipo, criado_em");
        $stmt->execute([$medicao_id]);
        $fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/repavimentacao/relatorio.php';
    }

    /* =====================================================
       CONCLUIR MEDIÇÃO — Regra 21 (valida linhas + asfalto)
    ===================================================== */
    public function concluirMedicao()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();

        $medicao_id = (int)($_POST['medicao_id'] ?? 0);
        if ($medicao_id <= 0) {
            $_SESSION['flash_erro'] = 'Medição inválida.';
            header('Location: ' . APP_BASE . '/repavimentacao');
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT mr.*, t.id AS trecho_id
            FROM medicoes_repavimentacao mr
            JOIN trechos t ON t.id = mr.trecho_id
            WHERE mr.id = ?
        ");
        $stmt->execute([$medicao_id]);
        $medicao = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$medicao) {
            $_SESSION['flash_erro'] = 'Medição não encontrada.';
            header('Location: ' . APP_BASE . '/repavimentacao');
            exit;
        }

        // Regra 21: ao menos um pavimento
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM medicao_pavimentos WHERE medicao_id = ?");
        $stmt->execute([$medicao_id]);
        if ((int)$stmt->fetchColumn() === 0) {
            $_SESSION['flash_erro'] = 'Adicione ao menos um pavimento antes de concluir a medição.';
            header('Location: ' . APP_BASE . '/repavimentacao/medicao?trecho_id=' . $medicao['trecho_id']);
            exit;
        }

        // Regra 21: todos os pavimentos precisam ter ao menos uma linha
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM medicao_pavimentos mp
            WHERE mp.medicao_id = ?
            AND NOT EXISTS (
                SELECT 1 FROM medicao_pavimento_linhas mpl WHERE mpl.pavimento_id = mp.id
            )
        ");
        $stmt->execute([$medicao_id]);
        if ((int)$stmt->fetchColumn() > 0) {
            $_SESSION['flash_erro'] = 'Todos os pavimentos precisam ter ao menos uma linha de dimensão para concluir a medição.';
            header('Location: ' . APP_BASE . '/repavimentacao/medicao?trecho_id=' . $medicao['trecho_id']);
            exit;
        }

        // Regra 21: asfalto sem espessura (aviso, não bloqueio)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM medicao_pavimentos
            WHERE medicao_id = ? AND tipo_pavimento = 'asfalto'
              AND (espessura_cm IS NULL OR espessura_cm = 0)
        ");
        $stmt->execute([$medicao_id]);
        $asfSemEspessura = (int)$stmt->fetchColumn();

        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE medicoes_repavimentacao SET status = 'concluida' WHERE id = ?")
                ->execute([$medicao_id]);
            $pdo->prepare("UPDATE trechos SET status_repav = 'medido' WHERE id = ?")
                ->execute([$medicao['trecho_id']]);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_erro'] = 'Erro ao concluir medição.';
            header('Location: ' . APP_BASE . '/repavimentacao/medicao?trecho_id=' . $medicao['trecho_id']);
            exit;
        }

        if ($asfSemEspessura > 0) {
            $_SESSION['flash_ok'] = 'Medição concluída. Atenção: ' . $asfSemEspessura . ' pavimento(s) de asfalto sem espessura — volume m³ não calculado.';
        } else {
            $_SESSION['flash_ok'] = 'Medição concluída com sucesso.';
        }
        header('Location: ' . APP_BASE . '/repavimentacao');
        exit;
    }

    /* =====================================================
       UPLOAD DE FOTO
    ===================================================== */
    public function uploadFoto()
    {
        auth_required([4]);
        global $pdo;
        csrf_verify();

        $medicao_id = (int)($_POST['medicao_id'] ?? 0);
        $tipo_foto  = trim($_POST['tipo_foto'] ?? '');
        $tipos_foto_validos = ['antes', 'durante', 'depois', 'croqui'];

        if ($medicao_id <= 0 || !in_array($tipo_foto, $tipos_foto_validos)) {
            $_SESSION['flash_erro'] = 'Dados inválidos.';
            header('Location: ' . APP_BASE . '/repavimentacao');
            exit;
        }

        // Buscar trecho_id
        $stmt = $pdo->prepare("SELECT trecho_id FROM medicoes_repavimentacao WHERE id = ?");
        $stmt->execute([$medicao_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $_SESSION['flash_erro'] = 'Medição não encontrada.';
            header('Location: ' . APP_BASE . '/repavimentacao');
            exit;
        }
        $trecho_id = $row['trecho_id'];

        if (empty($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_erro'] = 'Arquivo não recebido.';
            header('Location: ' . APP_BASE . '/repavimentacao/medicao?trecho_id=' . $trecho_id);
            exit;
        }

        $arquivo = $_FILES['foto'];

        // Tamanho max 10MB
        if ($arquivo['size'] > 10 * 1024 * 1024) {
            $_SESSION['flash_erro'] = 'Arquivo muito grande (máx. 10MB).';
            header('Location: ' . APP_BASE . '/repavimentacao/medicao?trecho_id=' . $trecho_id);
            exit;
        }

        // Validar mime
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($arquivo['tmp_name']);
        $mimeOk   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeReal, $mimeOk)) {
            $_SESSION['flash_erro'] = 'Apenas imagens (JPEG, PNG, GIF, WebP) são aceitas.';
            header('Location: ' . APP_BASE . '/repavimentacao/medicao?trecho_id=' . $trecho_id);
            exit;
        }

        $ext      = ($mimeReal === 'image/png') ? 'png' : (($mimeReal === 'image/gif') ? 'gif' : (($mimeReal === 'image/webp') ? 'webp' : 'jpg'));
        $baseName = 'repav_' . $medicao_id . '_' . $tipo_foto . '_' . time();
        $nome     = $baseName . '.' . $ext;
        $nomeThumb = $baseName . '_thumb.' . $ext;
        $dir      = __DIR__ . '/../../uploads/repavimentacao/';

        if (!move_uploaded_file($arquivo['tmp_name'], $dir . $nome)) {
            $_SESSION['flash_erro'] = 'Falha ao salvar imagem.';
            header('Location: ' . APP_BASE . '/repavimentacao/medicao?trecho_id=' . $trecho_id);
            exit;
        }

        // Processar com GD: redimensionar max 1600px e gerar thumb 320px
        $this->processarImagem($dir . $nome, $dir . $nome, 1600, $mimeReal);
        $this->processarImagem($dir . $nome, $dir . $nomeThumb, 320, $mimeReal);

        $pdo->prepare("
            INSERT INTO medicao_fotos (medicao_id, tipo, arquivo, thumb)
            VALUES (?, ?, ?, ?)
        ")->execute([$medicao_id, $tipo_foto, $nome, $nomeThumb]);

        $_SESSION['flash_ok'] = 'Foto adicionada com sucesso.';
        header('Location: ' . APP_BASE . '/repavimentacao/medicao?trecho_id=' . $trecho_id);
        exit;
    }

    private function processarImagem(string $src, string $dest, int $maxPx, string $mime): void
    {
        if (!function_exists('imagecreatefromjpeg')) return;

        $img = match($mime) {
            'image/jpeg' => @imagecreatefromjpeg($src),
            'image/png'  => @imagecreatefrompng($src),
            'image/gif'  => @imagecreatefromgif($src),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false,
            default      => false,
        };

        if (!$img) return;

        $w = imagesx($img);
        $h = imagesy($img);

        if ($w <= $maxPx && $h <= $maxPx) {
            imagedestroy($img);
            if ($src !== $dest) copy($src, $dest);
            return;
        }

        if ($w >= $h) {
            $nw = $maxPx;
            $nh = (int)round($h * $maxPx / $w);
        } else {
            $nh = $maxPx;
            $nw = (int)round($w * $maxPx / $h);
        }

        $new = imagecreatetruecolor($nw, $nh);
        if ($mime === 'image/png') {
            imagealphablending($new, false);
            imagesavealpha($new, true);
        }
        imagecopyresampled($new, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        match($mime) {
            'image/jpeg' => imagejpeg($new, $dest, 85),
            'image/png'  => imagepng($new, $dest, 8),
            'image/gif'  => imagegif($new, $dest),
            'image/webp' => function_exists('imagewebp') ? imagewebp($new, $dest, 85) : imagejpeg($new, $dest, 85),
            default      => imagejpeg($new, $dest, 85),
        };

        imagedestroy($img);
        imagedestroy($new);
    }
}
