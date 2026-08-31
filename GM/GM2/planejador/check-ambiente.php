<?php
/**
 * Checagem de ambiente — confere se o servidor tem o que a validação
 * pericial e as miniaturas precisam.
 *
 * Protegido pelo login do sistema (Master ou Planejador). Mostra apenas
 * os itens que interessam, nunca um phpinfo() completo.
 *
 * Pode ficar no ar: é só leitura e não expõe caminho nem credencial.
 */
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/asset.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/config/municipios.php';

auth_required([1, 3]);

$itens = [];

/* --- extensões ----------------------------------------------------- */
$temExif = function_exists('exif_read_data');
$itens[] = [
    'nome'  => 'Extensão exif',
    'ok'    => $temExif,
    'valor' => $temExif ? 'habilitada' : 'AUSENTE',
    'para'  => 'Ler data, GPS e aparelho da foto. Sem ela a validação de metadado não funciona e toda foto é recusada.',
];

$temGd      = extension_loaded('gd');
$temImagick = extension_loaded('imagick');
$itens[] = [
    'nome'  => 'GD ou Imagick',
    'ok'    => $temGd || $temImagick,
    'valor' => trim(($temGd ? 'GD ' : '') . ($temImagick ? 'Imagick' : '')) ?: 'AUSENTES',
    'para'  => 'Gerar as miniaturas. Sem nenhuma das duas, o relatório cai para as imagens originais e fica pesado.',
];

if ($temGd) {
    $info = function_exists('gd_info') ? gd_info() : [];
    $jpeg = !empty($info['JPEG Support']);
    $itens[] = [
        'nome'  => 'GD com suporte a JPEG',
        'ok'    => $jpeg,
        'valor' => $jpeg ? 'sim' : 'NÃO',
        'para'  => 'As fotos de campo são JPEG.',
    ];
}

/* --- limites de upload --------------------------------------------- */
$upload = ini_get('upload_max_filesize');
$post   = ini_get('post_max_size');
$maxFiles = (int) ini_get('max_file_uploads');

function mu_bytes(string $v): int {
    $v = trim($v); $u = strtolower(substr($v, -1)); $n = (int) $v;
    return $u === 'g' ? $n*1024**3 : ($u === 'm' ? $n*1024**2 : ($u === 'k' ? $n*1024 : $n));
}
$okUpload = mu_bytes($upload) >= 12 * 1024 * 1024;
$itens[] = [
    'nome'  => 'upload_max_filesize',
    'ok'    => $okUpload,
    'valor' => $upload,
    'para'  => 'Foto original de celular passa de 12 MB. Abaixo disso o arquivo nem chega ao servidor.',
];
$itens[] = [
    'nome'  => 'post_max_size',
    'ok'    => mu_bytes($post) >= mu_bytes($upload),
    'valor' => $post,
    'para'  => 'Precisa ser maior que upload_max_filesize, e comportar várias fotos de uma vez.',
];
$itens[] = [
    'nome'  => 'max_file_uploads',
    'ok'    => $maxFiles >= 10,
    'valor' => (string) $maxFiles,
    'para'  => 'Quantas fotos podem subir num lançamento só.',
];

/* --- pastas --------------------------------------------------------- */
$raiz = __DIR__ . '/../uploads';
$real = realpath($raiz);
$itens[] = [
    'nome'  => 'Pasta de uploads gravável',
    'ok'    => $real !== false && is_writable($real),
    'valor' => $real === false ? 'NÃO ENCONTRADA' : (is_writable($real) ? 'ok' : 'SEM PERMISSÃO DE ESCRITA'),
    'para'  => 'Onde o original e as miniaturas são gravados.',
];

/* --- banco ---------------------------------------------------------- */
$temTabela = false;
try {
    $pdo->query("SELECT 1 FROM midia_custodia LIMIT 1");
    $temTabela = true;
} catch (Throwable $e) {
    $temTabela = false;
}
$itens[] = [
    'nome'  => 'Tabela midia_custodia',
    'ok'    => $temTabela,
    'valor' => $temTabela ? 'existe' : 'NÃO EXISTE',
    'para'  => 'Cadeia de custódia. Sem ela o lançamento de foto falha. Rodar GM2_midia_custodia.sql.',
];

$tudoOk = !in_array(false, array_column($itens, 'ok'), true);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Checagem de Ambiente | GM SERVIÇOS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= mu_asset('/assets/css/planejador.css') ?>">
</head>
<body>
<div class="app">

<aside class="sidebar">
    <div class="logo">
        <img src="/GM/GM2/assets/img/icon-gm.png" alt="GM SERVIÇOS">
        <span>GM SERVIÇOS</span>
    </div>
    <?= bloco_identidade() ?>
    <nav>
        <a href="/GM/GM2/planejador/menu.php">📊 Dashboard</a>
        <a href="/GM/GM2/planejador/medicao.php">📐 Incluir Medições</a>
        <a href="/GM/GM2/planejador/relatorio.php">📄 Relatório</a>
        <a href="/GM/GM2/public/logout.php">🚪 Sair</a>
    </nav>
</aside>

<main class="content">

<div class="topbar">
    <div>
        <h1>Checagem de Ambiente</h1>
        <span>O que a validação pericial e as miniaturas precisam do servidor</span>
    </div>
</div>

<div class="form-card" style="border:1px solid <?= $tudoOk ? '#22c55e' : '#ef4444' ?>;
     background:<?= $tudoOk ? '#052e16' : '#2a0606' ?>;">
    <strong style="color:<?= $tudoOk ? '#bbf7d0' : '#fecaca' ?>;font-size:16px;">
        <?= $tudoOk ? 'Tudo pronto — pode liberar o lançamento de fotos.'
                    : 'Há item pendente. Veja abaixo o que está em vermelho.' ?>
    </strong>
</div>

<div class="form-card">
<table class="table" style="width:100%;">
<thead>
<tr><th style="width:34px;"></th><th>Item</th><th>No servidor</th><th>Para quê</th></tr>
</thead>
<tbody>
<?php foreach ($itens as $i): ?>
<tr>
    <td style="font-size:18px;"><?= $i['ok'] ? '✅' : '❌' ?></td>
    <td><strong><?= htmlspecialchars($i['nome']) ?></strong></td>
    <td style="color:<?= $i['ok'] ? '#86efac' : '#fca5a5' ?>;font-weight:600;">
        <?= htmlspecialchars($i['valor']) ?>
    </td>
    <td style="font-size:13px;color:#9ca3af;"><?= htmlspecialchars($i['para']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="form-card">
    <h3>Versão em execução</h3>
    <p style="font-size:13px;color:#9ca3af;line-height:1.6;margin-bottom:10px;">
        Serve para saber se o servidor já está rodando a versão mais recente. Arquivo
        estático (CSS) atualiza na hora; PHP pode ficar em cache alguns minutos.
    </p>
    <table class="table" style="width:100%;">
    <tbody>
    <?php
    $arquivos = [
        'Regras de validação'   => __DIR__ . '/../app/helpers/validacao_midia.php',
        'Municípios cadastrados'=> __DIR__ . '/../app/config/municipios.php',
        'Tela de medição'       => __DIR__ . '/medicao.php',
        'Relatório'             => __DIR__ . '/relatorio.php',
    ];
    foreach ($arquivos as $rotulo => $caminho):
        $existe = is_file($caminho);
    ?>
        <tr>
            <td style="width:34px;font-size:18px;"><?= $existe ? '✅' : '❌' ?></td>
            <td><strong><?= htmlspecialchars($rotulo) ?></strong></td>
            <td style="font-size:13px;color:#9ca3af;">
                <?= $existe ? 'atualizado em ' . date('d/m/Y H:i', filemtime($caminho)) : 'não encontrado' ?>
            </td>
        </tr>
    <?php endforeach; ?>
        <tr>
            <td style="font-size:18px;"><?= function_exists('opcache_get_status') ? 'ℹ️' : '✅' ?></td>
            <td><strong>Cache de código (OPcache)</strong></td>
            <td style="font-size:13px;color:#9ca3af;">
                <?php
                if (!function_exists('opcache_get_status')) {
                    echo 'desligado — toda alteração vale na hora';
                } else {
                    $st = @opcache_get_status(false);
                    echo (is_array($st) && !empty($st['opcache_enabled']))
                        ? 'ligado — se a data acima já for a nova mas o comportamento for antigo, aguarde alguns minutos'
                        : 'desligado — toda alteração vale na hora';
                }
                ?>
            </td>
        </tr>
    </tbody>
    </table>
</div>

<div class="form-card" style="border:1px solid <?= MU_VALIDACAO_BLOQUEIA ? '#22c55e' : '#f59e0b' ?>;
     background:<?= MU_VALIDACAO_BLOQUEIA ? '#052e16' : '#2a1a06' ?>;">
    <h3 style="color:<?= MU_VALIDACAO_BLOQUEIA ? '#bbf7d0' : '#fde68a' ?>;margin-bottom:6px;">
        <?= MU_VALIDACAO_BLOQUEIA
            ? '🔒 Validação bloqueando'
            : '⚠️ Validação apenas avisando' ?>
    </h3>
    <p style="font-size:13px;line-height:1.6;color:<?= MU_VALIDACAO_BLOQUEIA ? '#dcfce7' : '#fef3c7' ?>;">
        <?php if (MU_VALIDACAO_BLOQUEIA): ?>
            Mídia que não passa nas regras é recusada, e nada é gravado — nem as medidas
            do trecho.
        <?php else: ?>
            <strong>Nenhuma regra recusa o lançamento.</strong> Foto sem GPS, sem data,
            vinda de aplicativo de mensagem ou reaproveitada de outro trecho entra no
            acervo. A medição sempre grava.
            <br><br>
            O arquivo continua sendo guardado intocado, com SHA-256 calculado no
            recebimento e o metadado que houver registrado — a cadeia de custódia não
            depende desta chave.
            <br><br>
            Para voltar a bloquear: <code>MU_VALIDACAO_BLOQUEIA = true</code> em
            <code>app/helpers/validacao_midia.php</code>.
        <?php endif; ?>
    </p>

    <?php
    try {
        $tot = (int) $pdo->query("SELECT COUNT(*) FROM midia_custodia")->fetchColumn();
        if ($tot > 0):
    ?>
        <p style="font-size:13px;margin-top:10px;color:#e5e7eb;">
            Acervo atual: <strong><?= number_format($tot, 0, ',', '.') ?></strong>
            arquivos em custódia.
        </p>
    <?php endif; } catch (Throwable $e) { /* tabela ainda não criada */ } ?>
</div>

<div class="form-card">
    <h3>Base de municípios</h3>
    <?php
    $totalBase = 0;
    if (is_file(MU_BASE_IBGE) && ($fh = fopen(MU_BASE_IBGE, 'r'))) {
        fgetcsv($fh);
        while (fgetcsv($fh) !== false) { $totalBase++; }
        fclose($fh);
    }
    ?>
    <p style="font-size:13px;color:#9ca3af;line-height:1.6;">
        A distância é conferida contra o município <strong>do trecho</strong>, usando a base do
        IBGE embutida no sistema — <strong><?= number_format($totalBase, 0, ',', '.') ?> municípios</strong>.
        Não é preciso cadastrar cidade: qualquer município digitado num trecho novo já tem
        coordenada, e a distância sai real.
        <?php if (MU_UF_PADRAO): ?>
            <br>A busca está restrita ao <strong><?= htmlspecialchars(MU_UF_PADRAO) ?></strong>,
            onde nenhum nome de município se repete — então toda cidade é identificada sem
            ambiguidade. Trecho de outro estado precisa da UF junto do nome
            (ex.: <em>Chapecó / SC</em>).
        <?php endif; ?>
        <?php if ($totalBase === 0): ?>
            <br><span style="color:#fca5a5;">⚠️ A base não foi encontrada em
            <code>app/config/municipios_ibge.csv</code> — a conferência de distância não roda.</span>
        <?php endif; ?>
    </p>
    <p style="font-size:13px;color:#9ca3af;line-height:1.6;margin-top:8px;">
        Tolerância de <strong><?= number_format(MU_RAIO_PADRAO, 0, ',', '.') ?> km</strong>
        a partir do centro do município. A comparação é com o centro da cidade, não com o
        ponto exato do trecho — o cadastro do trecho não guarda coordenada. Serve para
        pegar foto de outra cidade, não foto do outro lado da mesma cidade.
        <?php if (MU_MUNICIPIOS): ?>
            <br>Cidades com tolerância própria:
            <?php foreach (MU_MUNICIPIOS as $nome => $c): ?>
                <br>· <strong><?= htmlspecialchars($nome) ?></strong> —
                <?= number_format($c['raio_km'] ?? MU_RAIO_PADRAO, 0, ',', '.') ?> km
            <?php endforeach; ?>
        <?php else: ?>
            <br>Vale para todas as cidades. Para tratar alguma de forma diferente,
            edite <code>app/config/municipios.php</code>.
        <?php endif; ?>
    </p>

    <?php
    /* Cidade gravada em cada trecho — é contra ela que a distância é
       conferida. É aqui que se vê por que uma foto foi recusada: se o
       trecho está como "Barra do Quaraí" e a foto foi tirada em outra
       cidade, a recusa está certa; o que precisa mudar é o cadastro do
       trecho, não a regra. */
    $porCidade = [];
    try {
        $q = $pdo->query("
            SELECT cidade, COUNT(*) AS n
            FROM trechos
            GROUP BY cidade
            ORDER BY n DESC
        ");
        foreach ($q as $row) { $porCidade[] = $row; }
    } catch (Throwable $e) { /* sem trechos ainda */ }
    ?>

    <?php if ($porCidade): ?>
    <h3 style="margin-top:22px;">Cidade gravada nos trechos</h3>
    <p style="font-size:13px;color:#9ca3af;line-height:1.6;margin-bottom:10px;">
        A foto é comparada com a cidade <strong>do trecho</strong>, não com a do contrato.
        Se um trecho de teste ficou com a cidade da obra e a foto foi tirada em outro lugar,
        a recusa está certa — o que precisa mudar é a cidade do trecho.
    </p>
    <table class="table" style="width:100%;">
    <thead><tr><th>Cidade no trecho</th><th>Trechos</th><th>Situação</th></tr></thead>
    <tbody>
    <?php foreach ($porCidade as $c):
        $nome = trim((string) $c['cidade']);
        $coord = $nome === '' ? null : mu_coordenada_municipio($nome);
    ?>
        <tr>
            <td><strong><?= $nome === '' ? '(em branco)' : htmlspecialchars($nome) ?></strong></td>
            <td><?= (int) $c['n'] ?></td>
            <td style="font-size:13px;">
                <?php if ($coord !== null): ?>
                    <span style="color:#86efac;">
                        ✅ confere contra <?= htmlspecialchars($coord['nome']) ?>,
                        raio de <?= number_format($coord['raio_km'], 0, ',', '.') ?> km
                    </span>
                <?php else: ?>
                    <span style="color:#fcd34d;">
                        ⚠️ não encontrado na base do IBGE — confira a grafia no cadastro
                        do trecho; a distância não é conferida nestes trechos
                    </span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="form-card">
    <h3>Como corrigir no hPanel da Hostinger</h3>
    <p style="font-size:14px;line-height:1.7;color:#d1d5db;">
        <strong>Extensões (exif, gd):</strong> hPanel → <em>Sites</em> → seu site →
        <em>Avançado</em> → <em>Configuração PHP</em> → aba <em>Extensões PHP</em>.
        Marque <code>exif</code> e <code>gd</code> e salve.<br><br>
        <strong>Limites de upload:</strong> mesma tela, aba <em>Opções PHP</em>
        (<code>upload_max_filesize</code>, <code>post_max_size</code>, <code>max_file_uploads</code>).<br><br>
        <strong>Otimização de imagem do CDN:</strong> hPanel → <em>Desempenho</em> →
        <em>CDN</em> → desligar a otimização/conversão de imagens, ou excluir a pasta
        <code>/GM/GM2/uploads/originais/</code>. Sem isso o download do original
        vem convertido para WebP e não bate com o hash registrado — que é justamente
        o que não pode acontecer numa perícia.
    </p>
</div>

</main>
</div>
</body>
</html>
