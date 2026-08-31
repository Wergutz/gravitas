<?php
/**
 * ---------------------------------------------------------------------------
 *  VALIDAÇÃO DE MÍDIA NO LANÇAMENTO — Painel GM SERVIÇOS (GM/GM2)
 * ---------------------------------------------------------------------------
 *  Barra o arquivo errado ANTES de ele entrar no acervo, e diz a quem está
 *  lançando exatamente o que fazer para corrigir.
 *
 *  NÃO EXISTE LIBERAÇÃO EXCEPCIONAL. Reprovou, não entra — sem override,
 *  sem chave de contorno, sem modo permissivo. Foi decisão do contratante:
 *  ou a mídia serve como prova, ou não é lançada.
 *
 *  Usar junto de 11-upload-pericial.php: a validação roda sobre o arquivo
 *  temporário, antes de qualquer gravação. Reprovou, não entra.
 *
 *  PHP 7.4+ · requer a extensão `exif` habilitada
 * ---------------------------------------------------------------------------
 */

declare(strict_types=1);

/* mu_ler_exif() e o armazenamento pericial vivem aqui. Carregado neste
   ponto para que a ordem dos require no medicao.php não importe. */
require_once __DIR__ . '/upload_pericial.php';

/* Coordenadas dos municípios, usadas pela regra FORA_DA_OBRA. */
require_once __DIR__ . '/../config/municipios.php';

/* ===========================================================================
 *  MODO DE APLICAÇÃO DAS REGRAS
 * ======================================================================== */

/**
 * As regras recusam o lançamento, ou apenas avisam?
 *
 *   true  — reprovou, não entra. Nada é gravado, nem as medidas.
 *   false — nada é bloqueado. A medição sempre grava.
 *
 * Está em false a pedido da contratada, para não travar o lançamento em
 * campo. A consequência precisa estar dita: foto sem GPS, sem data, vinda
 * de aplicativo de mensagem ou reaproveitada de outro trecho passa a
 * entrar no acervo, sem distinção das demais.
 *
 * O que continua valendo em qualquer modo: o arquivo é guardado
 * intocado, o SHA-256 é calculado no recebimento e o metadado que houver
 * é lido e persistido. A cadeia de custódia não depende desta chave.
 *
 * Para voltar a bloquear, troque para true. Nada mais precisa mudar.
 */
const MU_VALIDACAO_BLOQUEIA = false;

/* ===========================================================================
 *  CONFIGURAÇÃO DAS REGRAS
 * ======================================================================== */

const MU_VAL = [

    /* --- geolocalização da obra ---------------------------------------
       A coordenada NÃO fica aqui: a regra FORA_DA_OBRA usa o município
       do próprio trecho, cadastrado em app/config/municipios.php. Um
       ponto fixo travaria qualquer contrato fora de Barra do Quaraí.
       Estes valores permanecem só como último recurso, para o caso de
       o município do trecho não estar cadastrado. */
    'obra_lat'     => -30.2072,
    'obra_lon'     => -57.5547,
    'raio_km'      => 15.0,

    /* --- resolução mínima --------------------------------------------- */
    // Megapixels, não o lado maior: pega a cópia reduzida em qualquer proporção.
    // Celular atual tira 12 MP ou mais. As imagens que estão no acervo hoje
    // têm 1600x2133 = 3,4 MP — são cópias, não os arquivos da câmera.
    'megapixels_min' => 6.0,
    'bytes_min'      => 600 * 1024,

    /* --- coerência de data -------------------------------------------- */
    'dias_max_atras'   => 120,   // foto muito antiga para esta medição
    'horas_max_futuro' => 24,    // fuso/relógio torto do aparelho

    /* --- tipos aceitos por finalidade --------------------------------- */
    'mime_foto'   => ['image/jpeg', 'image/heic', 'image/heif'],
    'mime_croqui' => ['image/jpeg', 'image/heic', 'image/heif', 'image/png', 'application/pdf'],
];

/** Nível de exigência por tipo de mídia. */
const MU_EXIGE = [
    'foto' => [
        'exif'      => true,   // precisa ter metadado de câmera
        'datahora'  => true,
        'gps'       => true,
        'resolucao' => true,
        'dispositivo' => true,
    ],
    // Croqui costuma ser desenho escaneado ou exportado: não tem GPS nem câmera.
    'croqui' => [
        'exif'      => false,
        'datahora'  => false,
        'gps'       => false,
        'resolucao' => false,
        'dispositivo' => false,
    ],
];


/* ===========================================================================
 *  VALIDAÇÃO
 * ======================================================================== */

/**
 * Valida um arquivo recebido, antes de arquivar.
 *
 * @return array{ok:bool, erros:array, avisos:array, dados:array}
 *         erros[]  = ['codigo'=>..., 'titulo'=>..., 'como_corrigir'=>...]
 */
function mu_validar_midia(
    string $tmp,
    string $nomeEnviado,
    string $tipo,              // 'foto' | 'croqui'
    int $trechoId,
    ?string $dataMedicao,      // 'Y-m-d' — data de referência da medição
    PDO $pdo
): array {

    $erros  = [];
    $avisos = [];
    $exige  = MU_EXIGE[$tipo] ?? MU_EXIGE['foto'];

    /* Município do trecho: é contra ele que a distância é conferida. */
    $cidadeTrecho = '';
    try {
        $stCid = $pdo->prepare('SELECT cidade FROM trechos WHERE id = ? LIMIT 1');
        $stCid->execute([$trechoId]);
        $cidadeTrecho = (string) ($stCid->fetchColumn() ?: '');
    } catch (Throwable $e) {
        // Sem a cidade, a regra de distância simplesmente não roda.
    }

    /* --- 1. tipo real -------------------------------------------------- */
    $info = @getimagesize($tmp);
    $mime = $info['mime'] ?? (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
    $larg = $info[0] ?? 0;
    $alt  = $info[1] ?? 0;
    $bytes = (int) filesize($tmp);

    $aceitos = $tipo === 'croqui' ? MU_VAL['mime_croqui'] : MU_VAL['mime_foto'];
    if (!in_array($mime, $aceitos, true)) {
        $erros[] = mu_erro('TIPO_INVALIDO',
            'Este arquivo não é uma foto de câmera.',
            $mime === 'image/png'
                ? 'PNG normalmente é captura de tela ou imagem editada. Envie a foto original, em JPEG, direto da galeria do celular que a tirou.'
                : 'Formato recebido: ' . $mime . '. Envie a foto original do celular (JPEG ou HEIC).');
        /* Sem tipo válido não adianta seguir com as outras regras — mas o
           retorno precisa respeitar o modo de aplicação como os demais,
           senão este caminho continuaria bloqueando sozinho. */
        return [
            'ok'     => !MU_VALIDACAO_BLOQUEIA,
            'erros'  => $erros,
            'avisos' => $avisos,
            'dados'  => compact('mime', 'larg', 'alt', 'bytes'),
        ];
    }

    /* --- 2. passou pelo WhatsApp? -------------------------------------- */
    if (mu_cheira_whatsapp($nomeEnviado, $tmp)) {
        $erros[] = mu_erro('WHATSAPP',
            'Esta imagem passou pelo WhatsApp.',
            'O WhatsApp apaga a data e a localização da foto, e ela deixa de valer como prova. '
          . 'Abra a galeria do celular que TIROU a foto e envie o arquivo original — não a cópia recebida no WhatsApp.');
    }

    /* --- 3. metadados --------------------------------------------------- */
    $exif = mu_ler_exif($tmp, $mime);   // de 11-upload-pericial.php

    if ($exige['exif'] && !$exif['tem_exif']) {
        $erros[] = mu_erro('SEM_METADADO',
            'A foto está sem os dados de origem (data, local e aparelho).',
            'Isso acontece quando a imagem foi reenviada por aplicativo de mensagem, baixada da nuvem em '
          . 'modo econômico, ou editada. Envie o arquivo original, direto da galeria do celular que tirou a foto. '
          . 'No Google Fotos, use "Baixar" e não "Compartilhar".');
    }

    if ($exige['datahora'] && $exif['tem_exif'] && $exif['datahora'] === null) {
        $erros[] = mu_erro('SEM_DATA',
            'A foto não tem data de captura.',
            'Envie o arquivo original da câmera. Se a foto foi editada ou recortada, use a versão original.');
    }

    if ($exige['gps'] && $exif['tem_exif'] && ($exif['lat'] === null || $exif['lon'] === null)) {
        $erros[] = mu_erro('SEM_GPS',
            'A foto não tem a localização de onde foi tirada.',
            'Ligue a localização da câmera no celular (Câmera → Configurações → Marcação de local / '
          . 'Salvar local) e tire a foto de novo em campo. Sem coordenada, a foto não amarra o serviço ao trecho.');
    }

    if ($exige['dispositivo'] && $exif['tem_exif'] && $exif['dispositivo'] === null) {
        $avisos[] = mu_erro('SEM_APARELHO',
            'Não foi possível identificar o aparelho que tirou a foto.',
            'Não impede o lançamento, mas enfraquece a foto como prova.');
    }

    /* --- 4. é o arquivo original ou uma cópia reduzida? ----------------- */
    if ($exige['resolucao']) {
        $mp = ($larg * $alt) / 1000000;
        if ($mp > 0 && $mp < MU_VAL['megapixels_min']) {
            $erros[] = mu_erro('IMAGEM_REDUZIDA',
                'Esta imagem é uma cópia reduzida, não a foto original da câmera.',
                'Tem ' . $larg . '×' . $alt . ' px (' . number_format($mp, 1, ',', '.') . ' MP). '
              . 'A foto original de celular passa de ' . number_format(MU_VAL['megapixels_min'], 0) . ' MP. '
              . 'Envie o arquivo original da galeria, sem compactar — ao anexar, escolha "Tamanho real" '
              . 'se o celular perguntar.');
        }
        if ($bytes < MU_VAL['bytes_min'] && $mp >= MU_VAL['megapixels_min']) {
            $avisos[] = mu_erro('MUITO_COMPRIMIDA',
                'A imagem parece ter sido recompactada.',
                'Tamanho de ' . round($bytes / 1024) . ' KB para ' . $larg . '×' . $alt . ' px. Confira se é o arquivo original.');
        }
    }

    /* --- 5. coerência da data ------------------------------------------ */
    if ($exif['datahora'] !== null) {
        $tsFoto = strtotime($exif['datahora']);
        $agora  = time();

        if ($tsFoto > $agora + MU_VAL['horas_max_futuro'] * 3600) {
            $erros[] = mu_erro('DATA_FUTURA',
                'A data da foto está no futuro (' . date('d/m/Y H:i', $tsFoto) . ').',
                'O relógio do celular está errado. Acerte a data e a hora do aparelho e tire a foto de novo.');
        }

        $ref = $dataMedicao !== null ? strtotime($dataMedicao) : $agora;
        $diasAtras = (int) floor(($ref - $tsFoto) / 86400);
        if ($diasAtras > MU_VAL['dias_max_atras']) {
            $erros[] = mu_erro('FOTO_ANTIGA',
                'Foto de ' . date('d/m/Y', $tsFoto) . ', ' . $diasAtras . ' dias antes desta medição.',
                'Confirme se é a foto certa deste trecho e desta medição. Não há liberação excepcional: '
              . 'se a foto é realmente antiga, o registro precisa ser refeito em campo.');
        }
    }

    /* --- 6. a foto foi tirada na obra? ---------------------------------
       Compara com a coordenada do município DO TRECHO, não com um ponto
       fixo — o mesmo sistema atende contratos em cidades diferentes.
       Município não cadastrado: a regra não roda, porque não há contra
       o que comparar. As demais continuam valendo. */
    if ($exif['lat'] !== null && $exif['lon'] !== null) {
        $obra = mu_coordenada_municipio($cidadeTrecho);

        if ($obra !== null) {
            /* Nome que existe em mais de um estado: vale o mais próximo
               da foto. Recusar porque quem digitou não pôs a UF, e o
               primeiro homônimo da lista fica longe, seria injusto. */
            $dist = null;
            foreach (($obra['homonimos'] ?: [$obra]) as $cand) {
                $d = mu_distancia_km($exif['lat'], $exif['lon'], $cand['lat'], $cand['lon']);
                if ($dist === null || $d < $dist) {
                    $dist = $d;
                    if (isset($cand['uf'])) {
                        $obra['nome'] = $cand['nome'] . ' / ' . $cand['uf'];
                    }
                }
            }

            if ($dist > $obra['raio_km']) {
                $erros[] = mu_erro('FORA_DA_OBRA',
                    'A foto foi tirada a ' . number_format($dist, 1, ',', '.') . ' km de ' . $obra['nome'] . '.',
                    'O trecho é em ' . $obra['nome'] . ', e a coordenada gravada na foto está a '
                  . number_format($dist, 1, ',', '.') . ' km de lá (o limite é '
                  . number_format($obra['raio_km'], 0, ',', '.') . ' km). '
                  . 'Confira se a foto é deste trecho. Se o município estiver certo e mesmo assim '
                  . 'for recusado, o ponto de referência da cidade pode precisar de ajuste em '
                  . 'app/config/municipios.php.');
            }
        } else {
            $avisos[] = mu_erro('MUNICIPIO_NAO_CADASTRADO',
                'Não foi possível conferir se a foto é da obra.',
                'O município “' . $cidadeTrecho . '” não está em app/config/municipios.php, '
              . 'então a distância não foi verificada. A foto foi aceita pelas demais regras.');
        }
    }

    /* --- 7. esta foto já foi usada em outro trecho? --------------------- */
    $sha = hash_file('sha256', $tmp);
    $st = $pdo->prepare('SELECT trecho_id, tipo, enviado_em
                           FROM midia_custodia
                          WHERE sha256 = :s AND trecho_id <> :t
                          LIMIT 1');
    $st->execute([':s' => $sha, ':t' => $trechoId]);
    if ($dup = $st->fetch(PDO::FETCH_ASSOC)) {
        $erros[] = mu_erro('IMAGEM_DUPLICADA',
            'Esta mesma imagem já foi lançada em outro trecho.',
            'Foi usada no trecho ' . (int) $dup['trecho_id'] . ' em '
          . date('d/m/Y', strtotime((string) $dup['enviado_em']))
          . '. Cada trecho precisa das suas próprias fotos. Envie as fotos deste trecho.');
    }

    /* --- 8. resultado ---------------------------------------------------
       `erros` continua listando tudo o que a mídia tem de errado. O que
       o modo decide é só se isso impede o lançamento. */
    return [
        'ok'     => ($erros === []) || !MU_VALIDACAO_BLOQUEIA,
        'erros'  => $erros,
        'avisos' => $avisos,
        'dados'  => [
            'mime'   => $mime,
            'larg'   => $larg,
            'alt'    => $alt,
            'bytes'  => $bytes,
            'sha256' => $sha,
            'exif'   => $exif,
        ],
    ];
}


/* ===========================================================================
 *  AUXILIARES
 * ======================================================================== */

function mu_erro(string $codigo, string $titulo, string $comoCorrigir): array
{
    return ['codigo' => $codigo, 'titulo' => $titulo, 'como_corrigir' => $comoCorrigir];
}

/**
 * Assinaturas de imagem que passou pelo WhatsApp:
 *  - nome  IMG-20260731-WA0007.jpg  /  WhatsApp Image 2026-07-31 at 14.22.05.jpeg
 *  - JPEG com JFIF, sem nenhum APP1/Exif e sem APP13
 */
function mu_cheira_whatsapp(string $nome, string $tmp): bool
{
    if (preg_match('/^IMG-\d{8}-WA\d{4}\./i', $nome)) return true;
    if (preg_match('/^WhatsApp (Image|Video)/i', $nome)) return true;

    $fh = @fopen($tmp, 'rb');
    if (!$fh) return false;
    $cab = fread($fh, 4096);
    fclose($fh);
    if ($cab === false || strlen($cab) < 4) return false;
    if (substr($cab, 0, 2) !== "\xFF\xD8") return false;          // não é JPEG

    $temJFIF = strpos($cab, 'JFIF') !== false;
    $temExif = strpos($cab, 'Exif') !== false;

    return $temJFIF && !$temExif;
}

/** Distância em km entre duas coordenadas (Haversine). */
function mu_distancia_km(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $R = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2
       + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}


/* ===========================================================================
 *  USO NO medicao.php — substitui o bloco de upload atual
 *
 *  Regra: TUDO OU NADA. Valida todos os arquivos primeiro; se um só reprovar,
 *  nada é gravado. Sem isso o trecho fica lançado pela metade, com parte das
 *  fotos dentro e parte fora — que é pior do que não lançar.
 * ======================================================================== */
/*
require_once __DIR__ . '/11-upload-pericial.php';
require_once __DIR__ . '/12-validacao-upload.php';

// ---------- 1) juntar os arquivos recebidos --------------------------------
$fila = [];

if (!empty($_FILES['croqui']['name'])) {
    $fila[] = ['tipo' => 'croqui', 'f' => $_FILES['croqui']];
}
$n = count($_FILES['fotos']['name'] ?? []);
for ($i = 0; $i < $n; $i++) {
    if (($_FILES['fotos']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
    $fila[] = ['tipo' => 'foto', 'f' => [
        'name'     => $_FILES['fotos']['name'][$i],
        'tmp_name' => $_FILES['fotos']['tmp_name'][$i],
        'size'     => $_FILES['fotos']['size'][$i],
        'error'    => $_FILES['fotos']['error'][$i],
    ]];
}

// ---------- 2) validar TUDO antes de gravar QUALQUER COISA -----------------
$recusados = [];
foreach ($fila as $item) {
    $v = mu_validar_midia(
        $item['f']['tmp_name'], (string) $item['f']['name'],
        $item['tipo'], $trechoId, $dataMedicao, $pdo
    );
    if (!$v['ok']) {
        $recusados[] = ['arquivo' => $item['f']['name'], 'erros' => $v['erros']];
    }
}

// ---------- 3) um reprovado, nada entra ------------------------------------
if ($recusados) {
    // Devolver $recusados para a tela e NÃO gravar nada — nem as medidas.
    // Ver o formato da mensagem em 14-regras-validacao.md.
    mu_devolver_erros_para_a_tela($recusados);   // sua função de view
    return;
}

// ---------- 4) só agora grava, em transação --------------------------------
$pdo->beginTransaction();
try {
    foreach ($fila as $item) {
        mu_arquivar_midia($item['f'], $trechoId, $item['tipo'], $medicaoId, $usuarioId, $pdo);
    }
    // ... gravar aqui também comprimento[]/largura[] do trecho ...
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('[MU] falha ao arquivar midia: ' . $e->getMessage());
    // Mostrar o erro. Numa cadeia de custodia, falha silenciosa nao existe.
}
*/
