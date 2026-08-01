<?php
/**
 * ---------------------------------------------------------------------------
 *  ARMAZENAMENTO PERICIAL DE IMAGENS — Painel Marco Urbano (marco_urbano2)
 * ---------------------------------------------------------------------------
 *  Recebe fotos e croquis preservando o arquivo ORIGINAL byte a byte,
 *  registra a cadeia de custódia (SHA-256 + data/hora + autor) e gera as
 *  cópias de exibição a partir do original, sem nunca sobrescrevê-lo.
 *
 *  PHP 7.4+ · sem Composer · GD ou Imagick (só para as derivadas)
 *  Ver especificação e plano de teste em 10-metadados-pericia.md
 * ---------------------------------------------------------------------------
 */

declare(strict_types=1);

/* ===========================================================================
 *  CONFIGURAÇÃO  —  >>> CONFIRMAR os caminhos no servidor
 * ======================================================================== */

/**
 * Raiz do armazenamento.
 *
 * Este helper vive em marco_urbano2/app/helpers/, e a pasta de uploads do
 * sistema é marco_urbano2/uploads/ — daí os dois níveis acima. O pacote
 * original assumia o helper um nível acima de uploads/ (`/../uploads`).
 *
 * IDEAL, quando houver acesso: mover para fora da raiz web.
 */
const MU_RAIZ_MIDIA = __DIR__ . '/../../uploads';

/** Tipos aceitos: mime real => extensão canônica. */
const MU_TIPOS_ACEITOS = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/heic' => 'heic',   // iPhone; guardamos o original mesmo sem saber converter
    'image/heif' => 'heif',
];

/** Limite por arquivo. Fotos de celular chegam a 12 MB. */
const MU_MAX_BYTES = 25 * 1024 * 1024;

/** Larguras das cópias de exibição. O original NUNCA é redimensionado. */
const MU_DERIVADAS = [1600, 440];


/* ===========================================================================
 *  1. RECEBIMENTO
 * ======================================================================== */

/**
 * Recebe UM arquivo de $_FILES e o arquiva preservando o original.
 *
 * @param array       $arquivo   item de $_FILES (name, tmp_name, size, error)
 * @param int         $trechoId
 * @param string      $tipo      'foto' | 'croqui'
 * @param int|null    $medicaoId
 * @param int|null    $usuarioId
 * @param PDO         $pdo
 * @return array      registro de custódia gravado
 * @throws RuntimeException
 */
function mu_arquivar_midia(
    array $arquivo,
    int $trechoId,
    string $tipo,
    ?int $medicaoId,
    ?int $usuarioId,
    PDO $pdo
): array {

    /* --- 1.1 validação do upload ------------------------------------ */
    if (!isset($arquivo['error']) || is_array($arquivo['error'])) {
        throw new RuntimeException('Upload inválido.');
    }
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no upload (código ' . $arquivo['error'] . ').');
    }
    if (!is_uploaded_file($arquivo['tmp_name'])) {
        throw new RuntimeException('Arquivo não veio de um upload HTTP.');
    }
    if ($arquivo['size'] <= 0 || $arquivo['size'] > MU_MAX_BYTES) {
        throw new RuntimeException('Tamanho fora do permitido: ' . $arquivo['size'] . ' bytes.');
    }
    if (!in_array($tipo, ['foto', 'croqui'], true)) {
        throw new RuntimeException('Tipo de mídia inválido.');
    }

    $tmp = $arquivo['tmp_name'];

    /* --- 1.1b PORTÃO: nada entra sem passar pela validação -----------
       A checagem das regras de campo (metadado, GPS, resolução, WhatsApp,
       duplicidade) vive em 12-validacao-upload.php. Chamar ANTES daqui,
       no medicao.php — ver o exemplo no rodapé daquele arquivo.
       Se preferir centralizar, descomente:

       $v = mu_validar_midia($tmp, (string)$arquivo['name'], $tipo, $trechoId, $dataMedicao, $pdo);
       if (!$v['ok']) { throw new RuntimeException('Mídia reprovada na validação.'); }
    ------------------------------------------------------------------ */

    /* --- 1.2 tipo REAL, nunca pela extensão -------------------------- */
    $info = @getimagesize($tmp);
    $mime = $info['mime'] ?? null;

    if ($mime === null) {                       // HEIC não é lido por getimagesize
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($tmp) ?: '';
    }
    if (!isset(MU_TIPOS_ACEITOS[$mime])) {
        throw new RuntimeException('Tipo de imagem não aceito: ' . $mime);
    }
    $ext     = MU_TIPOS_ACEITOS[$mime];
    $largura = $info[0] ?? null;
    $altura  = $info[1] ?? null;

    /* --- 1.3 hash do que chegou, ANTES de qualquer coisa ------------- */
    $sha256 = hash_file('sha256', $tmp);
    if ($sha256 === false) {
        throw new RuntimeException('Não foi possível calcular o hash do arquivo.');
    }
    $bytes = (int) filesize($tmp);

    /* --- 1.4 metadados, lidos do arquivo original -------------------- */
    $exif = mu_ler_exif($tmp, $mime);

    /* --- 1.5 destino: o nome do arquivo É o hash --------------------- */
    $pasta = MU_RAIZ_MIDIA . '/originais/' . date('Y/m');
    if (!is_dir($pasta) && !mkdir($pasta, 0775, true) && !is_dir($pasta)) {
        throw new RuntimeException('Não foi possível criar ' . $pasta);
    }
    $destino  = $pasta . '/' . $sha256 . '.' . $ext;
    $relativo = 'originais/' . date('Y/m') . '/' . $sha256 . '.' . $ext;

    /* Mesmo conteúdo já arquivado: não duplica, não sobrescreve. */
    if (!is_file($destino)) {
        if (!move_uploaded_file($tmp, $destino)) {
            throw new RuntimeException('Falha ao mover o arquivo para o arquivo permanente.');
        }
        @chmod($destino, 0444);                 // somente leitura: o original é imutável
    }

    /* Conferência pós-gravação: o que está em disco é o que chegou? */
    if (hash_file('sha256', $destino) !== $sha256) {
        throw new RuntimeException('Integridade falhou após a gravação. Arquivo descartado do registro.');
    }

    /* --- 1.6 registro de custódia (append-only) ---------------------- */
    $sql = 'INSERT INTO midia_custodia
              (trecho_id, medicao_id, tipo, nome_original, caminho_original,
               mime_real, bytes, largura, altura, sha256, tem_exif,
               exif_datahora, exif_gps_lat, exif_gps_lon, exif_dispositivo,
               exif_bruto, enviado_por, enviado_em, ip_envio)
            VALUES
              (:trecho, :medicao, :tipo, :nome, :caminho,
               :mime, :bytes, :larg, :alt, :sha, :temexif,
               :dt, :lat, :lon, :disp,
               :bruto, :user, NOW(), :ip)
            ON DUPLICATE KEY UPDATE id = id';

    $st = $pdo->prepare($sql);
    $st->execute([
        ':trecho'  => $trechoId,
        ':medicao' => $medicaoId,
        ':tipo'    => $tipo,
        ':nome'    => mb_substr((string) $arquivo['name'], 0, 255),
        ':caminho' => $relativo,
        ':mime'    => $mime,
        ':bytes'   => $bytes,
        ':larg'    => $largura,
        ':alt'     => $altura,
        ':sha'     => $sha256,
        ':temexif' => $exif['tem_exif'] ? 1 : 0,
        ':dt'      => $exif['datahora'],
        ':lat'     => $exif['lat'],
        ':lon'     => $exif['lon'],
        ':disp'    => $exif['dispositivo'],
        ':bruto'   => $exif['bruto'],
        ':user'    => $usuarioId,
        ':ip'      => mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
    ]);

    /* --- 1.7 cópias de exibição, derivadas do original --------------- */
    foreach (MU_DERIVADAS as $w) {
        try {
            mu_gerar_derivada($destino, $mime, $sha256, $w);
        } catch (Throwable $e) {
            /* Derivada é conveniência. Se falhar, o original continua íntegro
               e o relatório cai para o original. Não aborta o recebimento. */
            error_log('[MU] derivada ' . $w . 'px falhou para ' . $sha256 . ': ' . $e->getMessage());
        }
    }

    return [
        'sha256'   => $sha256,
        'caminho'  => $relativo,
        'mime'     => $mime,
        'bytes'    => $bytes,
        'tem_exif' => $exif['tem_exif'],
        'exif'     => $exif,
    ];
}


/* ===========================================================================
 *  2. LEITURA DE METADADOS
 * ======================================================================== */

/**
 * Lê o EXIF do arquivo original. Devolve sempre a mesma estrutura,
 * com null onde o dado não existir.
 */
function mu_ler_exif(string $caminho, string $mime): array
{
    $vazio = [
        'tem_exif'    => false,
        'datahora'    => null,
        'lat'         => null,
        'lon'         => null,
        'dispositivo' => null,
        'bruto'       => null,
    ];

    if (!function_exists('exif_read_data')) {
        error_log('[MU] extensão exif não está habilitada no PHP — metadados não serão lidos.');
        return $vazio;
    }
    if (!in_array($mime, ['image/jpeg', 'image/heic', 'image/heif'], true)) {
        return $vazio;                          // PNG/WebP normalmente não trazem EXIF de câmera
    }

    $raw = @exif_read_data($caminho, null, true);
    if ($raw === false || $raw === null) {
        return $vazio;
    }

    /* IFD0 sem nenhuma tag = contêiner vazio. É exatamente o que estamos
       recebendo hoje: tratar como "sem metadado", não como "tem EXIF". */
    $ifd0 = $raw['IFD0'] ?? [];
    $exif = $raw['EXIF'] ?? [];
    $gps  = $raw['GPS']  ?? [];
    if (!$ifd0 && !$exif && !$gps) {
        return $vazio;
    }

    $datahora = $exif['DateTimeOriginal'] ?? $ifd0['DateTime'] ?? null;
    if ($datahora !== null) {
        // EXIF usa "2026:07:31 14:22:05"
        $dt = DateTime::createFromFormat('Y:m:d H:i:s', $datahora);
        $datahora = $dt ? $dt->format('Y-m-d H:i:s') : null;
    }

    $fabricante = trim((string) ($ifd0['Make']  ?? ''));
    $modelo     = trim((string) ($ifd0['Model'] ?? ''));
    $dispositivo = trim($fabricante . ' ' . $modelo);

    return [
        'tem_exif'    => true,
        'datahora'    => $datahora,
        'lat'         => mu_gps_para_decimal($gps['GPSLatitude']  ?? null, $gps['GPSLatitudeRef']  ?? null),
        'lon'         => mu_gps_para_decimal($gps['GPSLongitude'] ?? null, $gps['GPSLongitudeRef'] ?? null),
        'dispositivo' => $dispositivo !== '' ? mb_substr($dispositivo, 0, 120) : null,
        'bruto'       => json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
    ];
}

/** Converte o GPS do EXIF (graus/minutos/segundos como frações) em decimal. */
function mu_gps_para_decimal($coord, $ref): ?float
{
    if (!is_array($coord) || count($coord) < 3 || $ref === null) {
        return null;
    }
    $partes = [];
    foreach (array_slice($coord, 0, 3) as $p) {
        if (is_string($p) && strpos($p, '/') !== false) {
            [$n, $d] = array_map('floatval', explode('/', $p, 2));
            $partes[] = $d != 0.0 ? $n / $d : 0.0;
        } else {
            $partes[] = (float) $p;
        }
    }
    $dec = $partes[0] + $partes[1] / 60 + $partes[2] / 3600;
    if (in_array(strtoupper((string) $ref), ['S', 'W'], true)) {
        $dec = -$dec;
    }
    return round($dec, 7);
}


/* ===========================================================================
 *  3. CÓPIAS DE EXIBIÇÃO
 *     O original é a prova. Estas são só para tela e PDF.
 * ======================================================================== */

function mu_gerar_derivada(string $origem, string $mime, string $sha256, int $largura): string
{
    $pasta = MU_RAIZ_MIDIA . '/derivadas/' . $largura;
    if (!is_dir($pasta) && !mkdir($pasta, 0775, true) && !is_dir($pasta)) {
        throw new RuntimeException('Não foi possível criar ' . $pasta);
    }
    $destino = $pasta . '/' . $sha256 . '.jpg';
    if (is_file($destino)) {
        return $destino;
    }

    if (extension_loaded('imagick')) {
        $im = new Imagick($origem);
        $im->autoOrient();
        if ($im->getImageWidth() > $largura) {
            $im->resizeImage($largura, 0, Imagick::FILTER_LANCZOS, 1);
        }
        $im->stripImage();                       // derivada não precisa de metadado
        $im->setImageFormat('jpeg');
        $im->setImageCompressionQuality(82);
        $im->setInterlaceScheme(Imagick::INTERLACE_PLANE);
        $ok = $im->writeImage($destino);
        $im->clear();
        if (!$ok) {
            throw new RuntimeException('Imagick não gravou a derivada.');
        }
        return $destino;
    }

    /* --- fallback GD ------------------------------------------------- */
    switch ($mime) {
        case 'image/jpeg': $src = @imagecreatefromjpeg($origem); break;
        case 'image/png':  $src = @imagecreatefrompng($origem);  break;
        case 'image/webp': $src = @imagecreatefromwebp($origem); break;
        default:
            throw new RuntimeException('GD não abre ' . $mime . ' (instale Imagick para HEIC).');
    }
    if (!$src) {
        throw new RuntimeException('GD não conseguiu abrir a imagem.');
    }

    /* Orientação: GD ignora o EXIF, então corrigimos na mão. */
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $e = @exif_read_data($origem);
        $o = (int) ($e['Orientation'] ?? 1);
        if ($o === 3) { $src = imagerotate($src, 180, 0); }
        elseif ($o === 6) { $src = imagerotate($src, -90, 0); }
        elseif ($o === 8) { $src = imagerotate($src,  90, 0); }
    }

    $w = imagesx($src);
    $h = imagesy($src);
    if ($w > $largura) {
        $nh  = (int) round($h * ($largura / $w));
        $dst = imagecreatetruecolor($largura, $nh);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $largura, $nh, $w, $h);
        imagedestroy($src);
        $src = $dst;
    }

    imageinterlace($src, true);
    $tmp = $destino . '.tmp';
    $ok  = imagejpeg($src, $tmp, 82);
    imagedestroy($src);
    if (!$ok) {
        @unlink($tmp);
        throw new RuntimeException('GD não gravou a derivada.');
    }
    rename($tmp, $destino);                      // gravação atômica
    return $destino;
}


/* ===========================================================================
 *  4. ENTREGA DO ORIGINAL PARA PERÍCIA
 *     Usar num script separado, ex.: original.php?sha=<hash>
 * ======================================================================== */

/**
 * Entrega o arquivo original, bit a bit, como download.
 * Content-Disposition: attachment evita a conversão WebP do CDN.
 */
function mu_baixar_original(string $sha256, PDO $pdo): void
{
    if (!preg_match('/^[a-f0-9]{64}$/', $sha256)) {
        http_response_code(400);
        exit('Hash inválido.');
    }

    $st = $pdo->prepare('SELECT caminho_original, mime_real, nome_original, bytes
                           FROM midia_custodia WHERE sha256 = :s LIMIT 1');
    $st->execute([':s' => $sha256]);
    $reg = $st->fetch(PDO::FETCH_ASSOC);
    if (!$reg) {
        http_response_code(404);
        exit('Mídia não encontrada.');
    }

    $caminho = MU_RAIZ_MIDIA . '/' . $reg['caminho_original'];
    $real    = realpath($caminho);
    $raiz    = realpath(MU_RAIZ_MIDIA);
    if ($real === false || $raiz === false || strpos($real, $raiz) !== 0) {
        http_response_code(404);
        exit('Mídia não encontrada.');
    }

    /* O que sai daqui tem que bater com o hash registrado. */
    if (hash_file('sha256', $real) !== $sha256) {
        http_response_code(500);
        error_log('[MU] INTEGRIDADE: arquivo em disco não confere com o hash ' . $sha256);
        exit('Falha de integridade do arquivo. Registro comunicado ao administrador.');
    }

    $nome = 'MU_' . substr($sha256, 0, 12) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $reg['nome_original']);

    header('Content-Type: ' . $reg['mime_real']);
    header('Content-Length: ' . $reg['bytes']);
    header('Content-Disposition: attachment; filename="' . $nome . '"');
    header('X-Content-Type-Options: nosniff');
    header('X-Checksum-SHA256: ' . $sha256);
    header('Cache-Control: private, no-transform');   // no-transform: não converta
    readfile($real);
    exit;
}


/* ===========================================================================
 *  5. USO NO medicao.php
 * ======================================================================== */
/*
$pdo->beginTransaction();
try {
    // croqui (1 arquivo)
    if (!empty($_FILES['croqui']['name'])) {
        mu_arquivar_midia($_FILES['croqui'], $trechoId, 'croqui', $medicaoId, $usuarioId, $pdo);
    }

    // fotos (vários) — $_FILES['fotos'] vem "invertido" no PHP
    $n = count($_FILES['fotos']['name'] ?? []);
    for ($i = 0; $i < $n; $i++) {
        if (($_FILES['fotos']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        mu_arquivar_midia([
            'name'     => $_FILES['fotos']['name'][$i],
            'tmp_name' => $_FILES['fotos']['tmp_name'][$i],
            'size'     => $_FILES['fotos']['size'][$i],
            'error'    => $_FILES['fotos']['error'][$i],
        ], $trechoId, 'foto', $medicaoId, $usuarioId, $pdo);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('[MU] falha ao arquivar mídia: ' . $e->getMessage());
    // mostrar erro ao usuário — NUNCA engolir em silêncio numa cadeia de custódia
}
*/


/* ===========================================================================
 *  6. ROTINA AVULSA — carimbar o acervo que já existe
 *     Roda uma vez, cria o marco de custódia das imagens antigas.
 *     Não recupera metadado perdido; prova a integridade daqui em diante.
 * ======================================================================== */
/*
$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(MU_RAIZ_MIDIA));
foreach ($dir as $f) {
    if (!$f->isFile()) continue;
    if (!preg_match('/^(foto|croqui)_/', $f->getFilename())) continue;

    $sha  = hash_file('sha256', $f->getPathname());
    $info = @getimagesize($f->getPathname());
    echo implode("\t", [
        $f->getFilename(),
        $sha,
        $f->getSize(),
        $info['mime'] ?? '?',
        ($info[0] ?? '?') . 'x' . ($info[1] ?? '?'),
        date('Y-m-d H:i:s', $f->getMTime()),
    ]), PHP_EOL;
}
// Salvar a saída como CSV, com data, e guardar junto da documentação do contrato.
*/
