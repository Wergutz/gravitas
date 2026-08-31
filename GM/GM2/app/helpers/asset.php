<?php
/**
 * URL de arquivo estático com versão automática.
 *
 * O navegador guarda CSS e JS em cache. Sem um parâmetro que mude junto
 * com o arquivo, uma correção publicada no servidor pode nunca chegar ao
 * aparelho — foi exatamente o que aconteceu com a validação de fotos:
 * o servidor já estava corrigido e o celular continuava rodando a versão
 * antiga do JavaScript, acusando distância errada.
 *
 * Versões escritas na mão (?v=1, ?v=2) resolvem só enquanto alguém
 * lembra de incrementar. Aqui a versão é a data de modificação do
 * próprio arquivo: muda sozinha a cada publicação, e só quando o
 * conteúdo realmente mudou.
 */

function mu_asset(string $caminhoRelativo): string
{
    $caminhoRelativo = '/' . ltrim($caminhoRelativo, '/');
    $absoluto = __DIR__ . '/../..' . $caminhoRelativo;

    $versao = is_file($absoluto) ? filemtime($absoluto) : null;

    return '/GM/GM2' . $caminhoRelativo
         . ($versao ? '?v=' . $versao : '');
}
