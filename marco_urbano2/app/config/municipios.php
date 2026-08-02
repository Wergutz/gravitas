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

require_once __DIR__ . '/../helpers/texto.php';

const MU_MUNICIPIOS = [

    // Contrato 4147-2024 / TC 0147-2024 — CORSAN
    'Barra do Quaraí'      => ['lat' => -30.2072, 'lon' => -57.5547, 'raio_km' => 15.0],

    // Sede — usada em testes e homologação do sistema.
    'Porto Alegre'         => ['lat' => -30.0346, 'lon' => -51.2177, 'raio_km' => 35.0],

    // Demais municípios do RS, para a conferência não ficar sem
    // referência quando surgir contrato novo. Raio proporcional à
    // extensão urbana de cada um.
    'Pelotas'              => ['lat' => -31.7654, 'lon' => -52.3376, 'raio_km' => 25.0],
    'Caxias do Sul'        => ['lat' => -29.1685, 'lon' => -51.1796, 'raio_km' => 25.0],
    'Santa Maria'          => ['lat' => -29.6842, 'lon' => -53.8069, 'raio_km' => 25.0],
    'Rio Grande'           => ['lat' => -32.0350, 'lon' => -52.0986, 'raio_km' => 30.0],
    'Uruguaiana'           => ['lat' => -29.7547, 'lon' => -57.0883, 'raio_km' => 20.0],
    'Canoas'               => ['lat' => -29.9177, 'lon' => -51.1839, 'raio_km' => 15.0],
    'Novo Hamburgo'        => ['lat' => -29.6783, 'lon' => -51.1306, 'raio_km' => 15.0],
    'São Leopoldo'         => ['lat' => -29.7603, 'lon' => -51.1472, 'raio_km' => 15.0],
    'Gravataí'             => ['lat' => -29.9444, 'lon' => -50.9919, 'raio_km' => 20.0],
    'Viamão'               => ['lat' => -30.0811, 'lon' => -51.0233, 'raio_km' => 25.0],
    'Alvorada'             => ['lat' => -29.9897, 'lon' => -51.0808, 'raio_km' => 12.0],
    'Passo Fundo'          => ['lat' => -28.2624, 'lon' => -52.4067, 'raio_km' => 20.0],
    'Bagé'                 => ['lat' => -31.3314, 'lon' => -54.1069, 'raio_km' => 20.0],
    'Santana do Livramento'=> ['lat' => -30.8908, 'lon' => -55.5328, 'raio_km' => 20.0],
    'Quaraí'               => ['lat' => -30.3878, 'lon' => -56.4514, 'raio_km' => 15.0],
    'Alegrete'             => ['lat' => -29.7830, 'lon' => -55.7911, 'raio_km' => 20.0],
    'São Borja'            => ['lat' => -28.6606, 'lon' => -56.0044, 'raio_km' => 15.0],
    'Itaqui'               => ['lat' => -29.1253, 'lon' => -56.5533, 'raio_km' => 15.0],
];

/** Raio adotado quando o município não está no cadastro acima. */
const MU_RAIO_PADRAO = 15.0;

/**
 * Normaliza o nome do município para comparar sem depender de acento,
 * caixa ou espaço sobrando.
 */
function mu_chave_municipio(string $nome): string
{
    return mu_normalizar_texto($nome);
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
