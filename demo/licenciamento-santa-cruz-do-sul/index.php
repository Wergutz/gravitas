<?php
/**
 * =====================================================================
 *  Licenciamento viário · Santa Cruz do Sul — DEMONSTRAÇÃO PÚBLICA
 * =====================================================================
 *
 *  Mesma página que o módulo do painel serve, aberta sem login.
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
 *  O ARQUIVO É UM SÓ
 *
 *  A página não é copiada: sai do mesmo protótipo que o painel serve.
 *  Duas cópias do mesmo HTML acabariam divergindo, e aí a demonstração
 *  mostraria uma coisa e o painel outra.
 *
 *  POR QUE UM ARQUIVO SEPARADO DO DE URUGUAIANA
 *
 *  Cada cidade tem o seu protótipo. O de Uruguaiana traz a UFM, a malha
 *  do Centro, o corredor da BR-472 e os textos da legislação de lá
 *  escritos no próprio HTML — não são parâmetros que se troquem de fora.
 *  Manter um arquivo por cidade deixa as duas demonstrações no ar ao
 *  mesmo tempo, cada uma com os seus números, sem que um ajuste em uma
 *  estrague a outra.
 * =====================================================================
 */

$prototipo = __DIR__ . '/../../painel/app/views/licenciamento/prototipo-santacruz.html';

if (!is_readable($prototipo)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Não foi possível abrir a demonstração do licenciamento viário.';
    exit;
}

$html = file_get_contents($prototipo);

/* O protótipo de Santa Cruz do Sul chegou com o rodapé da barra lateral
   já sem o "← Painel de Controle" — ele só faz sentido dentro do painel, e
   aqui não há painel para onde voltar. As duas substituições abaixo ficam
   como rede: se um dia a página for reentregue no formato do protótipo de
   Uruguaiana, que traz o link, ele sai antes de a página ir ao ar. */
$html = str_replace(
    '<div class="railfoot"><a href="__APP_BASE__/">← Painel de Controle</a><br>',
    '<div class="railfoot">',
    $html
);

/* Mesma ideia, um nível abaixo: se o marcador aparecer com outra forma e
   a substituição acima não pegar, o endereço do painel não pode vazar pela
   metade numa página pública. */
$html = str_replace('__APP_BASE__', 'https://gravitas.net.br', $html);

/* Fora dos buscadores: é peça de apresentação, não conteúdo do site.
   O próprio protótipo já traz <meta name="robots" content="noindex">;
   o cabeçalho abaixo cobre os robôs que não leem a meta. */
header('X-Robots-Tag: noindex, nofollow', true);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=300');
echo $html;
