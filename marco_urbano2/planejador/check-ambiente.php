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
require_once __DIR__ . '/../app/config/database.php';

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
    'para'  => 'Cadeia de custódia. Sem ela o lançamento de foto falha. Rodar marco_urbano2_midia_custodia.sql.',
];

$tudoOk = !in_array(false, array_column($itens, 'ok'), true);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Checagem de Ambiente | Marco Urbano</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/marco_urbano2/assets/css/planejador.css?v=2">
</head>
<body>
<div class="app">

<aside class="sidebar">
    <div class="logo">
        <img src="/marco_urbano2/assets/img/logo-marco-urbano.svg" alt="Marco Urbano">
        <span>MARCO URBANO</span>
    </div>
    <nav>
        <a href="/marco_urbano2/planejador/menu.php">📊 Dashboard</a>
        <a href="/marco_urbano2/planejador/medicao.php">📐 Incluir Medições</a>
        <a href="/marco_urbano2/planejador/relatorio.php">📄 Relatório</a>
        <a href="/marco_urbano2/public/logout.php">🚪 Sair</a>
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
    <h3>Como corrigir no hPanel da Hostinger</h3>
    <p style="font-size:14px;line-height:1.7;color:#d1d5db;">
        <strong>Extensões (exif, gd):</strong> hPanel → <em>Sites</em> → seu site →
        <em>Avançado</em> → <em>Configuração PHP</em> → aba <em>Extensões PHP</em>.
        Marque <code>exif</code> e <code>gd</code> e salve.<br><br>
        <strong>Limites de upload:</strong> mesma tela, aba <em>Opções PHP</em>
        (<code>upload_max_filesize</code>, <code>post_max_size</code>, <code>max_file_uploads</code>).<br><br>
        <strong>Otimização de imagem do CDN:</strong> hPanel → <em>Desempenho</em> →
        <em>CDN</em> → desligar a otimização/conversão de imagens, ou excluir a pasta
        <code>/marco_urbano2/uploads/originais/</code>. Sem isso o download do original
        vem convertido para WebP e não bate com o hash registrado — que é justamente
        o que não pode acontecer numa perícia.
    </p>
</div>

</main>
</div>
</body>
</html>
