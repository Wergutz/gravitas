<?php
/**
 * Coordenadas dos municípios onde há obra.
 *
 * A regra FORA_DA_OBRA compara o GPS da foto com a coordenada do
 * município DO PRÓPRIO TRECHO (campo `trechos.cidade`) — não com um
 * ponto fixo. Assim o mesmo sistema atende contratos em cidades
 * diferentes sem precisar mexer em código.
 *
 * PARA INCLUIR UM MUNICÍPIO NOVO
 * ------------------------------
 * 1. Abra o Google Maps, clique com o botão direito no centro da cidade
 *    e copie o par de números que aparece (ex.: -30.0346, -51.2177).
 * 2. Acrescente uma linha abaixo, no mesmo formato.
 * 3. O nome precisa bater com o que está gravado em `trechos.cidade`
 *    (a comparação ignora maiúsculas e acentos).
 *
 * O `raio_km` é a folga aceita a partir desse ponto. Cidade grande e
 * espalhada pede raio maior; distrito pequeno, menor.
 */

const MU_MUNICIPIOS = [

    // Contrato 4147-2024 / TC 0147-2024 — CORSAN
    'Barra do Quaraí' => ['lat' => -30.2072, 'lon' => -57.5547, 'raio_km' => 15.0],

    // Sede — usada em testes e homologação do sistema.
    'Porto Alegre'    => ['lat' => -30.0346, 'lon' => -51.2177, 'raio_km' => 35.0],
];

/** Raio adotado quando o município não está no cadastro acima. */
const MU_RAIO_PADRAO = 15.0;

/**
 * Normaliza o nome do município para comparar sem depender de acento,
 * caixa ou espaço sobrando.
 */
function mu_chave_municipio(string $nome): string
{
    $n = trim(mb_strtolower($nome, 'UTF-8'));
    $de = ['á','à','ã','â','ä','é','ê','ë','í','ï','ó','ô','õ','ö','ú','ü','ç'];
    $para = ['a','a','a','a','a','e','e','e','i','i','o','o','o','o','u','u','c'];
    $n = str_replace($de, $para, $n);
    return preg_replace('/\s+/', ' ', $n);
}

/**
 * Coordenada do município da obra.
 *
 * @return array{lat:float, lon:float, raio_km:float}|null
 *         null quando o município não está cadastrado — nesse caso a
 *         regra de distância não roda, porque não há contra o que
 *         comparar. As demais regras continuam valendo.
 */
function mu_coordenada_municipio(?string $cidade): ?array
{
    $cidade = trim((string) $cidade);
    if ($cidade === '') {
        return null;
    }

    $alvo = mu_chave_municipio($cidade);
    foreach (MU_MUNICIPIOS as $nome => $c) {
        if (mu_chave_municipio($nome) === $alvo) {
            return [
                'lat'     => (float) $c['lat'],
                'lon'     => (float) $c['lon'],
                'raio_km' => (float) ($c['raio_km'] ?? MU_RAIO_PADRAO),
                'nome'    => $nome,
            ];
        }
    }
    return null;
}
