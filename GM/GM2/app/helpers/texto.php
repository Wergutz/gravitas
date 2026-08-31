<?php
/**
 * Normalização de texto para comparação.
 *
 * Usada onde dois textos digitados por pessoas diferentes devem contar
 * como o mesmo: nome de município, nome de planejamento.
 *
 * Não dá para delegar isso ao banco. O LOWER() do SQL só rebaixa ASCII
 * em alguns bancos, então "MEDIÇÃO" e "Medição" passariam como nomes
 * distintos. Em PHP o resultado é o mesmo em qualquer servidor.
 */

if (!function_exists('mu_normalizar_texto')) {

    function mu_normalizar_texto(?string $texto): string
    {
        $t = trim(mb_strtolower((string) $texto, 'UTF-8'));

        $de = [
            'á','à','ã','â','ä','é','è','ê','ë','í','ì','î','ï',
            'ó','ò','õ','ô','ö','ú','ù','û','ü','ç','ñ',
        ];
        $para = [
            'a','a','a','a','a','e','e','e','e','i','i','i','i',
            'o','o','o','o','o','u','u','u','u','c','n',
        ];
        $t = str_replace($de, $para, $t);

        // Espaço repetido não distingue nome nenhum.
        return preg_replace('/\s+/u', ' ', $t);
    }
}
