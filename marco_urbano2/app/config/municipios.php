<?php
/**
 * Coordenada do município da obra.
 *
 * A regra FORA_DA_OBRA compara o GPS da foto com a coordenada do
 * município DO PRÓPRIO TRECHO (campo `trechos.cidade`).
 *
 * A busca é feita na base do IBGE embutida em municipios_ibge.csv, com
 * os 5.571 municípios brasileiros. Não é preciso cadastrar cidade
 * nenhuma: qualquer município digitado num trecho novo já tem
 * coordenada, e a distância sai real.
 *
 * A base é local de propósito. Consultar serviço de geocodificação pela
 * internet criaria dependência externa num ponto que decide se a prova
 * entra ou não — se o serviço cair ou mudar de política, o lançamento
 * para. Base embutida sempre responde igual.
 *
 * COMO A CIDADE É INTERPRETADA
 * ----------------------------
 * Aceita "Pelotas", "Pelotas / RS", "Pelotas - RS" e "Pelotas (RS)".
 * A comparação ignora acento, caixa e espaço repetido.
 */

require_once __DIR__ . '/../helpers/texto.php';

/** Arquivo da base do IBGE: nome,uf,lat,lon */
const MU_BASE_IBGE = __DIR__ . '/municipios_ibge.csv';

/**
 * Estado onde a empresa atua.
 *
 * Restringir a busca tem efeito prático relevante: 28 nomes de
 * municípios gaúchos existem também em outro estado (Bom Jesus, Alvorada,
 * Cachoeirinha, entre outros), e dentro do RS nenhum nome se repete.
 * Com a UF fixa, toda busca é inequívoca — e uma foto tirada em
 * Bom Jesus/SC passa a ser corretamente recusada num trecho de
 * Bom Jesus/RS, o que antes não acontecia.
 *
 * Continua possível informar outra UF no próprio cadastro do trecho
 * ("Chapecó / SC"), que então prevalece.
 *
 * PARA ATUAR EM OUTRO ESTADO: troque a sigla, ou use null para liberar
 * o país inteiro. A base já traz todos os 5.571 municípios — não é
 * preciso gerar arquivo novo.
 */
const MU_UF_PADRAO = 'RS';

/**
 * Tolerância padrão, em km, a partir do ponto central do município.
 *
 * Generosa de propósito: município brasileiro pode ser extenso, e uma
 * recusa indevida trava o lançamento sem que haja quem libere. 30 km
 * ainda separa com folga uma foto da obra de uma foto tirada em outra
 * cidade, que é o que a regra existe para pegar.
 */
const MU_RAIO_PADRAO = 30.0;

/**
 * Ajustes por município, quando o padrão não serve.
 * Só entram aqui os casos com motivo — o resto sai da base do IBGE.
 */
const MU_MUNICIPIOS = [
    // Obra do contrato 4147-2024: perímetro pequeno e bem delimitado,
    // então vale apertar em relação ao padrão.
    'Barra do Quaraí' => ['raio_km' => 15.0],

    // Região metropolitana: a obra pode encostar em municípios vizinhos.
    'Porto Alegre'    => ['raio_km' => 35.0],
];

/** Mantido para compatibilidade com quem já chamava esta função. */
function mu_chave_municipio(string $nome): string
{
    return mu_normalizar_texto($nome);
}

/**
 * Separa a UF quando ela vem junto do nome.
 *
 * @return array{0:string, 1:?string} nome e UF (ou null)
 */
function mu_separar_uf(string $cidade): array
{
    $c = trim($cidade);

    // "Pelotas (RS)"
    if (preg_match('/^(.*?)\s*\(\s*([A-Za-z]{2})\s*\)$/u', $c, $m)) {
        return [trim($m[1]), strtoupper($m[2])];
    }
    // "Pelotas / RS"  |  "Pelotas - RS"  |  "Pelotas, RS"
    if (preg_match('/^(.*?)\s*[\/\-,]\s*([A-Za-z]{2})$/u', $c, $m)) {
        return [trim($m[1]), strtoupper($m[2])];
    }
    return [$c, null];
}

/**
 * Coordenada do município.
 *
 * @return array{lat:float, lon:float, raio_km:float, nome:string, uf:?string,
 *               homonimos:array}|null
 *         null só quando a cidade está em branco ou não existe na base.
 */
function mu_coordenada_municipio(?string $cidade): ?array
{
    $cidade = trim((string) $cidade);
    if ($cidade === '') {
        return null;
    }

    [$nome, $uf] = mu_separar_uf($cidade);
    $alvo = mu_normalizar_texto($nome);
    if ($alvo === '') {
        return null;
    }

    if (!is_file(MU_BASE_IBGE)) {
        error_log('[MU] base de municípios não encontrada: ' . MU_BASE_IBGE);
        return null;
    }

    /* UF a exigir: a informada no próprio trecho vence; sem ela, vale o
       estado onde a empresa atua. Null nos dois libera o país inteiro. */
    $ufExigida = $uf ?? MU_UF_PADRAO;

    /* Varredura direta do CSV, com memória constante: a base tem 192 KB
       e só precisamos das linhas que casam com o nome. */
    $achados = [];
    $fh = fopen(MU_BASE_IBGE, 'r');
    if (!$fh) {
        return null;
    }
    fgetcsv($fh); // cabeçalho
    while (($l = fgetcsv($fh)) !== false) {
        if (count($l) < 4) continue;
        if (mu_normalizar_texto($l[0]) !== $alvo) continue;
        if ($ufExigida !== null && strtoupper($l[1]) !== strtoupper($ufExigida)) continue;
        $achados[] = ['nome' => $l[0], 'uf' => $l[1],
                      'lat' => (float) $l[2], 'lon' => (float) $l[3]];
    }
    fclose($fh);

    if (!$achados) {
        return null;
    }

    $primeiro = $achados[0];

    /* Ajuste manual de raio, se houver para este município. */
    $raio = MU_RAIO_PADRAO;
    foreach (MU_MUNICIPIOS as $n => $cfg) {
        if (mu_normalizar_texto($n) === $alvo) {
            $raio = (float) ($cfg['raio_km'] ?? MU_RAIO_PADRAO);
            break;
        }
    }

    return [
        'lat'       => $primeiro['lat'],
        'lon'       => $primeiro['lon'],
        'raio_km'   => $raio,
        'nome'      => $primeiro['nome'] . ' / ' . $primeiro['uf'],
        'uf'        => $primeiro['uf'],
        // Homônimos em outros estados, para a distância considerar o
        // mais próximo em vez de recusar por causa do estado.
        'homonimos' => $achados,
    ];
}
