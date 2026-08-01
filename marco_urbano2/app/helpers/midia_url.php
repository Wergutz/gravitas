<?php
/**
 * Resolve a URL pública de uma mídia gravada em `trechos.croqui` ou
 * `trecho_fotos.arquivo`.
 *
 * Existem dois formatos no acervo:
 *
 *   NOVO (armazenamento pericial): caminho relativo à pasta uploads,
 *         ex. "originais/2026/08/<sha256>.jpg" — já contém barra.
 *   ANTIGO (anterior a esta regra): nome solto, ex. "foto_67a1b2.jpeg",
 *         que morava em uploads/fotos/ ou uploads/croquis/.
 *
 * Manter os dois evita quebrar o que já foi lançado.
 */

const MU_BASE_UPLOADS = '/marco_urbano2/uploads';

function mu_url_midia(?string $arquivo, string $tipo = 'foto'): string
{
    $arquivo = trim((string) $arquivo);
    if ($arquivo === '') {
        return '';
    }

    // Formato novo: já vem com o caminho relativo a partir de uploads/.
    if (strpos($arquivo, '/') !== false) {
        return MU_BASE_UPLOADS . '/' . $arquivo;
    }

    // Formato antigo: nome solto na pasta por tipo.
    $pasta = $tipo === 'croqui' ? 'croquis' : 'fotos';
    return MU_BASE_UPLOADS . '/' . $pasta . '/' . $arquivo;
}

/**
 * Caminho ABSOLUTO no disco, para quem precisa ler o arquivo (TCPDF,
 * rotina de exclusão). Devolve null se o arquivo não existir.
 *
 * Mesma dupla de formatos de mu_url_midia().
 */
function mu_path_midia(?string $arquivo, string $tipo = 'foto'): ?string
{
    $arquivo = trim((string) $arquivo);
    if ($arquivo === '' || strpos($arquivo, '..') !== false) {
        return null;
    }

    $raiz = realpath(__DIR__ . '/../../uploads');
    if ($raiz === false) {
        return null;
    }

    $candidatos = [$arquivo];
    if (strpos($arquivo, '/') === false) {
        $pasta = $tipo === 'croqui' ? 'croquis' : 'fotos';
        $candidatos = [$pasta . '/' . $arquivo, $arquivo];
    }

    foreach ($candidatos as $rel) {
        $p = realpath($raiz . '/' . $rel);
        // Precisa existir e estar contido na pasta de uploads.
        if ($p !== false && is_file($p) && strncmp($p, $raiz . DIRECTORY_SEPARATOR, strlen($raiz) + 1) === 0) {
            return $p;
        }
    }
    return null;
}

/**
 * URL da miniatura. Cai para o original quando o arquivo é do acervo
 * antigo (o thumb.php só enxerga o que está sob a raiz de uploads).
 */
function mu_url_thumb(?string $arquivo, int $largura = 440, string $tipo = 'foto'): string
{
    $arquivo = trim((string) $arquivo);
    if ($arquivo === '') {
        return '';
    }
    return '/marco_urbano2/planejador/thumb.php?f=' . rawurlencode($arquivo) . '&w=' . $largura;
}
