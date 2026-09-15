<?php
/**
 * =====================================================================
 *  Licenciamento viário · Butiá — DEMONSTRAÇÃO PÚBLICA
 * =====================================================================
 *
 *  POR QUE EXISTE UMA ROTA SEM LOGIN
 *
 *  O protótipo é material de apresentação: não tem banco, não grava
 *  nada e todos os dados são inventados. Exigir uma conta do painel
 *  para abri-lo só atrapalhava quem precisa mostrar o sistema a uma
 *  prefeitura, sem proteger informação nenhuma — não há informação real
 *  ali para proteger.
 *
 *  O QUE ESTA ROTA NÃO É
 *
 *  Não é o portal público do licenciamento. Aquele, previsto para a
 *  fase 1, mostra só o que a população pode ver (nunca valores de
 *  caução e multa, dados pessoais dos responsáveis ou laudos em
 *  recurso). Aqui o visitante troca de perfil à vontade e vê todas as
 *  telas, porque o propósito é demonstrar o sistema inteiro.
 *
 *  POR QUE O HTML NÃO MORA AQUI DENTRO
 *
 *  O protótipo fica em painel/app/views/licenciamento/, junto com os das
 *  outras cidades, e esta rota o lê do disco. Guardá-lo aqui publicaria
 *  o mesmo conteúdo em dois endereços — este e o .html solto —, e o
 *  arquivo solto sairia sem os cabeçalhos que esta rota acrescenta.
 *
 *  POR QUE UM ARQUIVO POR CIDADE
 *
 *  A UFM, a malha do Centro, os intervenientes e os artigos da
 *  legislação de Butiá estão escritos no próprio HTML — não são
 *  parâmetros que se troquem de fora. Um arquivo por cidade deixa as
 *  três demonstrações no ar ao mesmo tempo, cada uma com os seus
 *  números, sem que um ajuste em uma estrague as outras.
 * =====================================================================
 */

$prototipo = __DIR__ . '/../../painel/app/views/licenciamento/prototipo-butia.html';

if (!is_readable($prototipo)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Não foi possível abrir a demonstração do licenciamento viário.';
    exit;
}

$html = file_get_contents($prototipo);

/* O protótipo de Butiá chegou com o rodapé da barra lateral já sem o
   "← Painel de Controle" — ele só faz sentido dentro do painel, e aqui
   não há painel para onde voltar. As duas substituições abaixo ficam
   como rede: se um dia a página for reentregue no formato do protótipo
   de Uruguaiana, que traz o link, ele sai antes de a página ir ao ar. */
$html = str_replace(
    '<div class="railfoot"><a href="__APP_BASE__/">← Painel de Controle</a><br>',
    '<div class="railfoot">',
    $html
);

/* Mesma ideia, um nível abaixo: se o marcador aparecer com outra forma e
   a substituição acima não pegar, o endereço do painel não pode vazar
   pela metade numa página pública. */
$html = str_replace('__APP_BASE__', 'https://gravitas.net.br', $html);

/* Fora dos buscadores: é peça de apresentação, não conteúdo do site.
   O próprio protótipo já traz <meta name="robots" content="noindex">;
   o cabeçalho abaixo cobre os robôs que não leem a meta. */
header('X-Robots-Tag: noindex, nofollow', true);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=300');
echo $html;
