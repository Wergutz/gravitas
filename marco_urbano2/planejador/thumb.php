<?php
/**
 * =============================================================================
 *  thumb.php  —  Gerador + servidor de miniaturas com cache em disco
 * =============================================================================
 *
 *  Projeto : Painel de Controle Gravitas — Relatório Oficial de Medição
 *  Obra    : Contrato 4147-2024 — Barra do Quaraí / CORSAN — cliente MARCO URBANO
 *  Página  : /marco_urbano2/planejador/relatorio.php?planejamento_id=4
 *  Autor   : Desenvolvedor Backend Sênior — Gravitas
 *  Alvo    : PHP 7.4+ / hospedagem compartilhada / sem Composer / sem dependência externa
 *
 *  ARQUIVO DE DESTINO NO SERVIDOR (sugestão):
 *      /marco_urbano2/planejador/thumb.php
 *
 *  USO NO HTML:
 *      <img src="thumb.php?f=foto_67a1b2c3d4e5f.jpeg&w=440" ...>
 *
 * -----------------------------------------------------------------------------
 *  1) DECISÃO DE ARQUITETURA — on-demand (thumb.php) x geração no upload
 * -----------------------------------------------------------------------------
 *
 *  ESCOLHIDO: **on-demand com cache em disco** (este arquivo), com um atalho de
 *  .htaccess que faz o Apache servir a miniatura já cacheada SEM passar pelo PHP.
 *
 *  Por quê, nesta hospedagem e neste projeto:
 *
 *  a) O acervo JÁ EXISTE. Há centenas/milhares de `croqui_*.jpeg` e `foto_*.jpeg`
 *     na pasta de uploads, enviados antes de qualquer decisão de miniatura.
 *     Geração no upload só resolveria as fotos FUTURAS — os relatórios antigos,
 *     que são exatamente os que o fiscal abre para conferir medição passada,
 *     continuariam pesando 15,93 MB. On-demand cobre o acervo inteiro no
 *     primeiro acesso, sem migração e sem script de lote obrigatório.
 *
 *  b) Não mexe no fluxo de upload do app de campo. O upload é o ponto mais
 *     frágil da operação (4G ruim, celular no barro). Enfiar redimensionamento
 *     síncrono ali aumenta o tempo de resposta do POST e cria uma nova classe de
 *     falha ("a foto subiu mas o app deu timeout"). Risco desnecessário agora.
 *
 *  c) Custo é pago uma única vez por imagem. Medido em bancada (PHP 8, GD,
 *     original 1600×2133): 79 ms para 220 px, 98 ms para 440 px, 194 ms para
 *     880 px, com pico de 2 MB no contador de memória do PHP. Depois disso o
 *     arquivo fica em disco para sempre — o nome do original é um `uniqid`,
 *     ou seja, o conteúdo é imutável e cache eterno é seguro. Com cache
 *     quente o custo cai para o tempo de um readfile().
 *
 *  d) Com o `.htaccess` da seção 6 abaixo, no regime permanente o PHP NÃO é
 *     invocado: o Apache serve o JPEG estático do cache direto. Ou seja, ficamos
 *     com a performance da geração no upload sem o risco de mexer no upload.
 *
 *  QUANDO MIGRAR PARA GERAÇÃO NO UPLOAD:
 *  Se um dia a Marco Urbano subir centenas de fotos de uma vez e o primeiro
 *  acesso ao relatório ficar lento (pico de CPU na geração), basta chamar a
 *  função pública deste arquivo logo após o `move_uploaded_file()`:
 *
 *      require_once __DIR__ . '/thumb.php';        // não executa nada (ver guarda no rodapé)
 *      grv_thumb_build('foto_67a1b2c3d4e5f.jpeg', 440);
 *      grv_thumb_build('foto_67a1b2c3d4e5f.jpeg', 220);
 *
 *  O código está escrito para servir aos dois modos: a função de geração é
 *  reutilizável e o "modo servidor HTTP" só roda quando o arquivo é acessado
 *  diretamente pelo navegador. Mesmo assim, mantenha o thumb.php no ar — ele é
 *  a rede de segurança para qualquer imagem que escape do fluxo de upload.
 *
 *  ALTERNATIVA DESCARTADA: gerar miniatura em memória a cada request, sem cache.
 *  Em hospedagem compartilhada isso derruba o site na primeira medição grande.
 *  Nunca faça isso.
 *
 * -----------------------------------------------------------------------------
 *  2) SEGURANÇA — o que este arquivo faz para não virar um "file reader"
 * -----------------------------------------------------------------------------
 *
 *  O parâmetro `f` NUNCA é usado como caminho. Ele passa por 5 barreiras:
 *
 *   1. Whitelist de formato por regex: só `croqui_...` ou `foto_...` seguido de
 *      caracteres alfanuméricos/ponto/hífen e extensão jpg/jpeg/png. Um `../`,
 *      uma barra, um `%00` ou um nome fora do padrão é rejeitado antes de tocar
 *      no disco.
 *   2. `basename()` — remove qualquer componente de diretório que sobrevivesse.
 *   3. Comparação `realpath()` — o caminho resolvido tem de estar DENTRO da
 *      pasta de uploads resolvida. Isso mata symlink apontando para fora.
 *   4. Verificação de conteúdo — `getimagesize()` precisa reconhecer JPEG ou PNG.
 *      Um `.jpeg` que na verdade é PHP não passa.
 *   5. Whitelist de largura — `w` só aceita 220, 440, 560 ou 880. Sem isso, um robô
 *      pediria `w=1..2000` e encheria o disco da hospedagem (cache-flood DoS).
 *
 *  Além disso: limite de pixels do original (anti "decompression bomb"),
 *  `X-Content-Type-Options: nosniff`, escrita atômica no cache (tmp + rename)
 *  para não servir arquivo pela metade em acesso concorrente, e nenhuma
 *  mensagem de erro que revele caminho do servidor.
 *
 * -----------------------------------------------------------------------------
 *  3) GD x IMAGICK
 * -----------------------------------------------------------------------------
 *
 *  O código tenta GD primeiro (praticamente universal em hospedagem
 *  compartilhada) e cai para Imagick automaticamente se GD não existir.
 *  A escolha é feita em runtime por `extension_loaded()` — não é preciso
 *  editar nada. Se as DUAS faltarem, o script devolve 501 e o relatório
 *  continua funcionando com as imagens originais (ver fallback no doc 04).
 *
 * -----------------------------------------------------------------------------
 *  4) O QUE O ALEX PRECISA CONFIRMAR NO SERVIDOR (marcado com >>> CONFIRMAR)
 * -----------------------------------------------------------------------------
 *      - o caminho absoluto REAL da pasta de uploads;
 *      - se a pasta de cache pode ser criada dentro dela (permissão de escrita);
 *      - se a extensão GD está ativa (script de checagem no doc 05).
 *
 * =============================================================================
 */

/* ===========================================================================
 *  CONFIGURAÇÃO  —  ajuste APENAS este bloco
 * ===========================================================================*/

/**
 * >>> CONFIRMAR (1/2): caminho ABSOLUTO da pasta onde ficam croqui_*.jpeg e foto_*.jpeg.
 *
 * NÃO estou inventando o caminho. Deixe o valor abaixo se a pasta `uploads`
 * estiver um nível acima de `planejador/` (estrutura mais provável:
 * /marco_urbano2/uploads/ com /marco_urbano2/planejador/relatorio.php).
 * Se não for, rode o `check-ambiente.php` do doc 05 — ele imprime o caminho real.
 *
 * Exemplos possíveis, para referência:
 *   __DIR__ . '/../uploads'          -> /marco_urbano2/uploads
 *   __DIR__ . '/uploads'             -> /marco_urbano2/planejador/uploads
 *   '/home/USUARIO/public_html/marco_urbano2/uploads'
 */
define('GRV_UPLOAD_DIR', __DIR__ . '/../uploads');

/**
 * >>> CONFIRMAR (2/2): pasta de cache das miniaturas.
 * Precisa ser gravável pelo usuário do PHP. Recomendo criar DENTRO de uploads,
 * assim herda o mesmo dono/permissão e entra no mesmo backup.
 */
define('GRV_CACHE_DIR', GRV_UPLOAD_DIR . '/_thumbs');

/**
 * Larguras permitidas (whitelist). Qualquer outro valor cai para o padrão.
 * Os valores casam com o CSS do Frontend (01-frontend-css.css):
 *   220 -> --rel-thumb (miniatura de foto na tela, 1x)
 *   440 -> a mesma miniatura em tela retina (2x)
 *   560 -> --rel-croqui-max (croqui na tela, 1x)
 *   880 -> croqui em retina e na impressão (120 mm ≈ 186 dpi)
 * NÃO aceite largura livre: é assim que um robô enche o disco da hospedagem.
 */
define('GRV_ALLOWED_WIDTHS', '220,440,560,880');

/** Largura padrão quando `w` não vem na URL. */
define('GRV_DEFAULT_WIDTH', 440);

/** Qualidade JPEG da miniatura (78 = ótimo equilíbrio tamanho/nitidez). */
define('GRV_JPEG_QUALITY', 78);

/** Teto de pixels do arquivo ORIGINAL. 40 MP cobre 1600x2133 (3,4 MP) com folga. */
define('GRV_MAX_SRC_PIXELS', 40000000);

/** Tempo de cache no navegador, em segundos. 1 ano — o nome é uniqid, é imutável. */
define('GRV_BROWSER_TTL', 31536000);

/** Prefixos de arquivo aceitos (whitelist de negócio). */
define('GRV_ALLOWED_PREFIXES', 'croqui,foto');


/* ===========================================================================
 *  FUNÇÕES PÚBLICAS REUTILIZÁVEIS
 *  (podem ser chamadas por outro script, ex.: no upload ou no warm-up)
 * ===========================================================================*/

/**
 * Valida o nome de arquivo recebido do cliente.
 *
 * Só aceita: <prefixo>_<alfanumérico/./-/_>.<jpg|jpeg|png>
 * Rejeita: barras, `..`, byte nulo, nome vazio, nome longo demais.
 *
 * @param  string      $raw Nome cru vindo de $_GET.
 * @return string|null Nome seguro, ou null se inválido.
 */
function grv_thumb_safe_name($raw)
{
    if (!is_string($raw) || $raw === '' || strlen($raw) > 128) {
        return null;
    }
    // Byte nulo, controle e barra invertida: fora. A barra normal é tratada
    // adiante, porque o formato pericial usa subpasta.
    if (strpbrk($raw, "\0\r\n\t\\") !== false) {
        return null;
    }
    // Nunca deixa passar travessia de diretório, mesmo codificada.
    if (strpos($raw, '..') !== false) {
        return null;
    }
    // Caminho absoluto nunca.
    if ($raw[0] === '/') {
        return null;
    }

    /* --- formato pericial: originais/AAAA/MM/<sha256>.<ext> -------------
       O nome do arquivo É o hash do conteúdo, então não há nada vindo do
       usuário na parte final do caminho. */
    if (preg_match('#^originais/[0-9]{4}/[0-9]{2}/[a-f0-9]{64}\.(?:jpe?g|png|webp)$#i', $raw)) {
        return $raw;
    }

    /* --- formato pericial: derivadas/<largura>/<sha256>.jpg ------------- */
    if (preg_match('#^derivadas/[0-9]{2,4}/[a-f0-9]{64}\.jpg$#i', $raw)) {
        return $raw;
    }

    /* --- acervo antigo: nome solto com prefixo de negócio ---------------
       Aqui não pode haver barra nenhuma. */
    if (strpos($raw, '/') !== false) {
        return null;
    }

    $prefixes = implode('|', array_map(
        'preg_quote',
        array_map('trim', explode(',', GRV_ALLOWED_PREFIXES))
    ));

    // croqui_67a1b2c3d4e5f.jpeg  |  foto_67a1b2c3d4e5f.12345678.jpg  |  foto_abc-1_2.png
    $re = '/^(?:' . $prefixes . ')_[A-Za-z0-9][A-Za-z0-9._-]{0,96}\.(?:jpe?g|png|webp)$/i';
    if (!preg_match($re, $raw)) {
        return null;
    }

    // Cinto e suspensório: basename remove qualquer resquício de caminho.
    $name = basename($raw);
    return ($name === $raw) ? $name : null;
}

/**
 * Normaliza a largura pedida contra a whitelist.
 *
 * @param  mixed $raw
 * @return int
 */
function grv_thumb_safe_width($raw)
{
    $allowed = array_map('intval', explode(',', GRV_ALLOWED_WIDTHS));
    $w = (int) $raw;
    return in_array($w, $allowed, true) ? $w : (int) GRV_DEFAULT_WIDTH;
}

/**
 * Resolve o caminho do arquivo original, garantindo que ele está DENTRO da
 * pasta de uploads (defesa contra symlink e contra falha da regex).
 *
 * @param  string      $safeName Nome já validado por grv_thumb_safe_name().
 * @return string|null Caminho absoluto real, ou null.
 */
function grv_thumb_source_path($safeName)
{
    $base = realpath(GRV_UPLOAD_DIR);
    if ($base === false) {
        return null; // pasta de uploads mal configurada
    }

    /* O formato pericial já traz o caminho relativo completo. O acervo
       antigo é um nome solto que pode estar em fotos/ ou croquis/. */
    $candidatos = array($safeName);
    if (strpos($safeName, '/') === false) {
        $candidatos[] = 'fotos/' . $safeName;
        $candidatos[] = 'croquis/' . $safeName;
    }

    foreach ($candidatos as $rel) {
        $path = realpath($base . DIRECTORY_SEPARATOR . $rel);
        if ($path === false || !is_file($path) || !is_readable($path)) {
            continue;
        }
        // O caminho resolvido TEM de começar pela pasta de uploads resolvida.
        if (strncmp($path, $base . DIRECTORY_SEPARATOR, strlen($base) + 1) !== 0) {
            continue;
        }
        return $path;
    }
    return null;
}

/**
 * Caminho do arquivo de cache correspondente.
 * Formato: <nome-original-com-extensão>.w<LARGURA>.jpg
 * Ex.: foto_67a1b2c3.jpeg.w440.jpg
 *
 * O nome já foi validado, então não há como injetar caminho aqui.
 *
 * @param  string $safeName
 * @param  int    $width
 * @return string
 */
function grv_thumb_cache_path($safeName, $width)
{
    /* O nome já foi validado. Achatamos a barra do formato pericial para
       que o cache continue sendo uma pasta só, sem subdiretórios. */
    $plano = str_replace('/', '__', $safeName);

    return rtrim(GRV_CACHE_DIR, '/\\') . DIRECTORY_SEPARATOR
         . $plano . '.w' . (int) $width . '.jpg';
}

/**
 * Garante que a pasta de cache existe e está protegida.
 *
 * @return bool
 */
function grv_thumb_ensure_cache_dir()
{
    $dir = GRV_CACHE_DIR;
    if (!is_dir($dir)) {
        // 0755 é suficiente quando o PHP roda como dono da pasta (suPHP/FPM,
        // que é o padrão em hospedagem compartilhada). Se o host rodar como
        // "nobody", o doc 05 explica quando usar 0775/0777.
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }
    }
    if (!is_writable($dir)) {
        return false;
    }

    // Impede que qualquer coisa dentro do cache seja executada como PHP.
    $ht = $dir . DIRECTORY_SEPARATOR . '.htaccess';
    if (!file_exists($ht)) {
        @file_put_contents(
            $ht,
            "# Gravitas - cache de miniaturas. Nada aqui pode ser executado.\n" .
            "<FilesMatch \"\\.(php|phtml|php[0-9]|phar|pl|py|cgi|sh|html?)$\">\n" .
            "  <IfModule mod_authz_core.c>\n" .
            "    Require all denied\n" .
            "  </IfModule>\n" .
            "  <IfModule !mod_authz_core.c>\n" .
            "    Order allow,deny\n" .
            "    Deny from all\n" .
            "  </IfModule>\n" .
            "</FilesMatch>\n"
        );
    }
    return true;
}

/**
 * Lê a orientação EXIF de um JPEG. Retorna 1 (normal) quando não há EXIF.
 *
 * As fotos vêm de celular em campo — muitas têm EXIF Orientation 6 (retrato
 * feito com o telefone deitado). Sem tratar isso, a miniatura sai virada
 * mesmo que a original apareça certa no navegador.
 *
 * @param  string $path
 * @param  int    $type Constante IMAGETYPE_*
 * @return int    1..8
 */
function grv_thumb_exif_orientation($path, $type)
{
    if ($type !== IMAGETYPE_JPEG || !function_exists('exif_read_data')) {
        return 1;
    }
    // @ porque EXIF corrompido é comum e emite warning.
    $exif = @exif_read_data($path);
    if (!is_array($exif) || empty($exif['Orientation'])) {
        return 1;
    }
    $o = (int) $exif['Orientation'];
    return ($o >= 1 && $o <= 8) ? $o : 1;
}

/**
 * Aplica a orientação EXIF numa imagem GD.
 *
 * @param  resource|GdImage $im
 * @param  int              $orientation
 * @return resource|GdImage
 */
function grv_thumb_apply_orientation_gd($im, $orientation)
{
    switch ($orientation) {
        case 2: imageflip($im, IMG_FLIP_HORIZONTAL); break;
        case 3: $im = imagerotate($im, 180, 0); break;
        case 4: imageflip($im, IMG_FLIP_VERTICAL); break;
        case 5: imageflip($im, IMG_FLIP_VERTICAL);   $im = imagerotate($im, -90, 0); break;
        case 6: $im = imagerotate($im, -90, 0); break;
        case 7: imageflip($im, IMG_FLIP_HORIZONTAL); $im = imagerotate($im, -90, 0); break;
        case 8: $im = imagerotate($im, 90, 0); break;
        default: break; // 1 = já está certo
    }
    return $im;
}

/**
 * GERA a miniatura e grava no cache. Idempotente: se o cache já existe e está
 * mais novo que o original, não faz nada.
 *
 * Esta é a função que o script de upload / warm-up deve chamar.
 *
 * @param  string      $safeName Nome já validado.
 * @param  int         $width    Largura alvo (da whitelist).
 * @return string|null Caminho do arquivo de cache, ou null em caso de falha.
 */
function grv_thumb_build($safeName, $width = null)
{
    $safeName = grv_thumb_safe_name($safeName);
    if ($safeName === null) {
        return null;
    }
    $width = grv_thumb_safe_width($width === null ? GRV_DEFAULT_WIDTH : $width);

    $src = grv_thumb_source_path($safeName);
    if ($src === null) {
        return null;
    }

    $cache = grv_thumb_cache_path($safeName, $width);

    // Cache válido? (existe, tem conteúdo e não é mais velho que a original)
    if (is_file($cache) && filesize($cache) > 0 && filemtime($cache) >= filemtime($src)) {
        return $cache;
    }

    if (!grv_thumb_ensure_cache_dir()) {
        return null;
    }

    // Valida o CONTEÚDO: precisa ser imagem de verdade e de tipo permitido.
    $info = @getimagesize($src);
    if (!is_array($info) || empty($info[0]) || empty($info[1])) {
        return null;
    }
    list($srcW, $srcH) = $info;
    $type = isset($info[2]) ? (int) $info[2] : 0;
    $tiposOk = array(IMAGETYPE_JPEG, IMAGETYPE_PNG);
    if (defined('IMAGETYPE_WEBP')) {
        $tiposOk[] = IMAGETYPE_WEBP;
    }
    if (!in_array($type, $tiposOk, true)) {
        return null;
    }
    // Anti decompression bomb.
    if (($srcW * $srcH) > GRV_MAX_SRC_PIXELS) {
        return null;
    }

    $orientation = grv_thumb_exif_orientation($src, $type);

    // Se a orientação EXIF gira 90°, o alvo troca de eixo — calculamos depois
    // de rotacionar, então aqui só passamos a largura desejada adiante.
    if (extension_loaded('gd')) {
        $ok = grv_thumb_build_gd($src, $cache, $type, $width, $orientation);
    } elseif (extension_loaded('imagick')) {
        $ok = grv_thumb_build_imagick($src, $cache, $width);
    } else {
        return null; // nem GD nem Imagick — o chamador decide o fallback
    }

    return $ok ? $cache : null;
}

/**
 * Implementação com GD (caminho principal).
 *
 * @return bool
 */
function grv_thumb_build_gd($src, $cache, $type, $width, $orientation)
{
    // 1600x2133 RGBA ~ 14 MB. Damos folga sem exagerar (hospedagem compartilhada).
    // Se o host bloqueia ini_set (comum em plano básico), o valor original é
    // mantido e a geração ainda funciona com memory_limit >= 64M.
    $oldMem = @ini_get('memory_limit');
    if (!is_string($oldMem) || $oldMem === '') {
        $oldMem = '128M';
    }
    @ini_set('memory_limit', '256M');

    $im = false;
    if ($type === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg')) {
        $im = @imagecreatefromjpeg($src);
    } elseif ($type === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) {
        $im = @imagecreatefrompng($src);
    } elseif (defined('IMAGETYPE_WEBP') && $type === IMAGETYPE_WEBP
              && function_exists('imagecreatefromwebp')) {
        $im = @imagecreatefromwebp($src);
    }
    if (!$im) {
        @ini_set('memory_limit', $oldMem);
        return false;
    }

    $im = grv_thumb_apply_orientation_gd($im, $orientation);

    $w0 = imagesx($im);
    $h0 = imagesy($im);
    if ($w0 < 1 || $h0 < 1) {
        imagedestroy($im);
        @ini_set('memory_limit', $oldMem);
        return false;
    }

    // Nunca AMPLIA: se a original já é menor que o alvo, mantém o tamanho.
    $ratio = min(1.0, $width / $w0);
    $w1 = max(1, (int) round($w0 * $ratio));
    $h1 = max(1, (int) round($h0 * $ratio));

    $dst = imagecreatetruecolor($w1, $h1);
    if (!$dst) {
        imagedestroy($im);
        @ini_set('memory_limit', $oldMem);
        return false;
    }
    // PNG com transparência vira JPEG: fundo branco em vez de preto.
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $w1, $h1, $white);

    imagecopyresampled($dst, $im, 0, 0, 0, 0, $w1, $h1, $w0, $h0);
    imagedestroy($im);

    // JPEG progressivo: a miniatura "aparece" antes de terminar de baixar — em
    // 4G de campo isso muda a percepção de velocidade.
    if (function_exists('imageinterlace')) {
        imageinterlace($dst, true);
    }

    // Escrita ATÔMICA: grava em tmp e renomeia. Dois acessos simultâneos ao
    // mesmo relatório nunca servem um JPEG pela metade.
    $tmp = $cache . '.' . getmypid() . '.' . mt_rand(1000, 9999) . '.tmp';
    $ok  = @imagejpeg($dst, $tmp, (int) GRV_JPEG_QUALITY);
    imagedestroy($dst);

    if ($ok && is_file($tmp) && filesize($tmp) > 0) {
        $ok = @rename($tmp, $cache);
        if ($ok) {
            @chmod($cache, 0644);
        }
    } else {
        $ok = false;
    }
    if (!$ok && is_file($tmp)) {
        @unlink($tmp);
    }

    @ini_set('memory_limit', $oldMem);
    return (bool) $ok;
}

/**
 * Implementação com Imagick (FALLBACK — só usada se GD não existir).
 *
 * Vantagem: `autoOrient()` resolve o EXIF sozinho e `stripImage()` remove os
 * metadados (a miniatura fica ~3–8 KB menor e não vaza GPS da obra).
 * Desvantagem: menos comum em hospedagem compartilhada e mais faminto por RAM.
 *
 * @return bool
 */
function grv_thumb_build_imagick($src, $cache, $width)
{
    try {
        $img = new Imagick();
        // Dica de leitura: pede ao decoder JPEG para já entregar reduzido.
        // Corta drasticamente a RAM usada em originais grandes.
        $img->setOption('jpeg:size', ((int) $width * 2) . 'x');
        $img->readImage($src);

        if (method_exists($img, 'autoOrient')) {
            $img->autoOrient();               // resolve EXIF Orientation
        }
        $img->setImageBackgroundColor('white');
        if (defined('Imagick::ALPHACHANNEL_REMOVE')) {
            $img->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
        }
        if (method_exists($img, 'mergeImageLayers') && defined('Imagick::LAYERMETHOD_FLATTEN')) {
            $img = $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        }

        $w0 = $img->getImageWidth();
        if ($w0 > $width) {
            // 0 na altura = mantém proporção
            $img->resizeImage((int) $width, 0, Imagick::FILTER_LANCZOS, 1);
        }

        $img->setImageFormat('jpeg');
        $img->setImageCompressionQuality((int) GRV_JPEG_QUALITY);
        $img->setInterlaceScheme(Imagick::INTERLACE_PLANE); // progressivo
        $img->stripImage();

        $tmp = $cache . '.' . getmypid() . '.' . mt_rand(1000, 9999) . '.tmp';
        $ok  = $img->writeImage($tmp);
        $img->clear();
        $img->destroy();

        if ($ok && is_file($tmp) && filesize($tmp) > 0) {
            if (@rename($tmp, $cache)) {
                @chmod($cache, 0644);
                return true;
            }
        }
        if (is_file($tmp)) {
            @unlink($tmp);
        }
        return false;
    } catch (Exception $e) {
        // Nunca vaza a mensagem para o cliente; registra no log do PHP.
        error_log('[gravitas/thumb] Imagick: ' . $e->getMessage());
        return false;
    }
}


/* ===========================================================================
 *  MODO SERVIDOR HTTP
 *  Roda somente quando este arquivo é acessado direto pelo navegador.
 *  Se outro script fizer `require thumb.php`, nada abaixo é executado.
 * ===========================================================================*/

if (!defined('GRV_THUMB_AS_LIBRARY')
    && isset($_SERVER['SCRIPT_FILENAME'])
    && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {

    grv_thumb_serve();
}

/**
 * Ponto de entrada HTTP.
 */
function grv_thumb_serve()
{
    // Nenhuma saída acidental antes dos headers.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    ignore_user_abort(true);
    @set_time_limit(30);

    header('X-Content-Type-Options: nosniff');
    header_remove('Pragma');

    // Só GET/HEAD.
    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
    if ($method !== 'GET' && $method !== 'HEAD') {
        grv_thumb_abort(405, 'Method Not Allowed');
    }

    $name = grv_thumb_safe_name(isset($_GET['f']) ? $_GET['f'] : '');
    if ($name === null) {
        grv_thumb_abort(400, 'Bad Request');
    }

    $width = grv_thumb_safe_width(isset($_GET['w']) ? $_GET['w'] : GRV_DEFAULT_WIDTH);

    $src = grv_thumb_source_path($name);
    if ($src === null) {
        grv_thumb_abort(404, 'Not Found');
    }

    if (!extension_loaded('gd') && !extension_loaded('imagick')) {
        // Sem biblioteca de imagem: 501 e o log avisa. O relatório continua
        // funcionando porque o <img> tem onerror -> original (ver doc 04).
        error_log('[gravitas/thumb] Nem GD nem Imagick estao ativos neste PHP.');
        grv_thumb_abort(501, 'Not Implemented');
    }

    $cache = grv_thumb_build($name, $width);
    if ($cache === null || !is_file($cache)) {
        error_log('[gravitas/thumb] Falha ao gerar miniatura de ' . $name . ' (w=' . $width . ')');
        grv_thumb_abort(500, 'Internal Server Error');
    }

    grv_thumb_send($cache, $method === 'HEAD');
}

/**
 * Envia o arquivo com headers de cache e suporte a 304.
 *
 * @param string $file
 * @param bool   $headOnly
 */
function grv_thumb_send($file, $headOnly = false)
{
    $size  = filesize($file);
    $mtime = filemtime($file);
    $etag  = '"' . md5(basename($file) . '|' . $mtime . '|' . $size) . '"';
    $lastM = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';

    header('Content-Type: image/jpeg');
    header('Content-Disposition: inline; filename="' . basename($file) . '"');
    // O nome do original é um uniqid -> o conteúdo nunca muda -> immutable.
    header('Cache-Control: public, max-age=' . (int) GRV_BROWSER_TTL . ', immutable');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (int) GRV_BROWSER_TTL) . ' GMT');
    header('Last-Modified: ' . $lastM);
    header('ETag: ' . $etag);
    header('Vary: Accept-Encoding');
    header('Accept-Ranges: none');

    // Revalidação condicional -> 304 (0 byte na rede).
    $inm = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : '';
    $ims = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? trim($_SERVER['HTTP_IF_MODIFIED_SINCE']) : '';

    $etagMatch = ($inm !== '' && (strpos($inm, $etag) !== false || $inm === '*'));
    $timeMatch = ($ims !== '' && ($t = strtotime($ims)) !== false && $t >= $mtime);

    if ($etagMatch || $timeMatch) {
        header('HTTP/1.1 304 Not Modified', true, 304);
        exit;
    }

    header('Content-Length: ' . $size);
    if ($headOnly) {
        exit;
    }
    readfile($file);
    exit;
}

/**
 * Encerra com erro, sem revelar nada do servidor.
 *
 * @param int    $code
 * @param string $msg
 */
function grv_thumb_abort($code, $msg)
{
    header('HTTP/1.1 ' . (int) $code . ' ' . $msg, true, (int) $code);
    header('Content-Type: text/plain; charset=utf-8');
    // Erro NÃO pode ser cacheado pelo navegador nem por proxy.
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo $msg;
    exit;
}

/* =============================================================================
 *  5) SCRIPT DE AQUECIMENTO (opcional) — gerar miniaturas em lote
 * -----------------------------------------------------------------------------
 *  Se quiser pré-gerar tudo (evita a lentidão do primeiro acesso), salve como
 *  `warmup-thumbs.php` NA MESMA PASTA, abra pelo navegador e APAGUE depois.
 *  Protegido por token para não virar botão de DoS público.
 *
 *  <?php
 *  define('GRV_THUMB_AS_LIBRARY', true);
 *  require __DIR__ . '/thumb.php';
 *
 *  // >>> CONFIRMAR: troque por um valor aleatório seu e use ?k=SEU_TOKEN
 *  if (!isset($_GET['k']) || !hash_equals('TROQUE_ESTE_TOKEN', $_GET['k'])) {
 *      http_response_code(403); exit('nope');
 *  }
 *  @set_time_limit(0);
 *  header('Content-Type: text/plain; charset=utf-8');
 *
 *  $larguras = array(220, 440, 560, 880);
 *  $inicio   = isset($_GET['de']) ? (int) $_GET['de'] : 0;
 *  $lote     = 100;                       // 100 por vez, para não estourar o time limit
 *
 *  $arquivos = array();
 *  foreach (array('croqui_*.jp*g', 'foto_*.jp*g', 'croqui_*.png', 'foto_*.png') as $g) {
 *      $arquivos = array_merge($arquivos, glob(GRV_UPLOAD_DIR . '/' . $g) ?: array());
 *  }
 *  sort($arquivos);
 *  $total = count($arquivos);
 *  $fatia = array_slice($arquivos, $inicio, $lote);
 *
 *  foreach ($fatia as $p) {
 *      $n = basename($p);
 *      foreach ($larguras as $w) {
 *          echo (grv_thumb_build($n, $w) ? 'OK   ' : 'FALHA') . "  w{$w}  {$n}\n";
 *      }
 *      flush();
 *  }
 *  $prox = $inicio + $lote;
 *  echo "\n--- {$prox}/{$total} ---\n";
 *  if ($prox < $total) { echo "Proxima: ?k=SEU_TOKEN&de={$prox}\n"; }
 *  else { echo "CONCLUIDO. APAGUE ESTE ARQUIVO DO SERVIDOR.\n"; }
 *
 * =============================================================================
 *  6) ATALHO DO APACHE (opcional, mas recomendado) — servir o cache sem PHP
 * -----------------------------------------------------------------------------
 *  Depois que a miniatura existe, não há motivo para acordar o PHP a cada
 *  request. Acrescente ao `.htaccess` da pasta `planejador/`:
 *
 *      <IfModule mod_rewrite.c>
 *        RewriteEngine On
 *        # >>> CONFIRMAR: ajuste "../uploads/_thumbs" ao caminho real relativo
 *        #     ao DocumentRoot. Ex.: /marco_urbano2/uploads/_thumbs
 *        RewriteCond %{QUERY_STRING} ^(?=.*(?:^|&)f=(croqui|foto)_([A-Za-z0-9._-]+))(?=.*(?:^|&)w=(220|440|560|880))
 *        RewriteRule ^thumb\.php$ /marco_urbano2/uploads/_thumbs/%2%3.w%4.jpg [L,QSD]
 *      </IfModule>
 *
 *  ATENÇÃO: essa regra só é segura porque o arquivo de destino ou existe
 *  (miniatura já gerada) ou não existe (404). Para evitar 404 quando o cache
 *  ainda não foi gerado, adicione ANTES da RewriteRule:
 *
 *      RewriteCond %{DOCUMENT_ROOT}/marco_urbano2/uploads/_thumbs/%2%3.w%4.jpg -f
 *
 *  Se o padrão de nome do seu upload for diferente, a regra falha em silêncio e
 *  o PHP assume — ou seja, o risco de aplicar é baixo. Se preferir simplicidade,
 *  PULE esta seção: o thumb.php sozinho já resolve o problema de peso.
 *  Meça primeiro, otimize depois.
 * =============================================================================
 */
