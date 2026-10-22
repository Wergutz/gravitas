<?php
/**
 * Banco do site gravitas.net.br — exclusivo das funções do site.
 *
 * Separado de propósito do banco das obras (u278289683_vh_planeja): são
 * assuntos diferentes, e um problema num não derruba o outro.
 *
 * ONDE FICAM OS DADOS DE ACESSO
 *
 * Não aqui. Este arquivo é versionado e publicado em duas pastas; senha de
 * banco em repositório fica no histórico para sempre, e qualquer pessoa com
 * acesso ao código passa a ter acesso ao banco.
 *
 * Em vez disso, o arquivo `contatos-db.php` é procurado nas pastas acima
 * desta, até sair da área publicada. O lugar recomendado no servidor é
 *
 *     /home/<conta>/domains/gravitas.net.br/contatos-db.php
 *
 * ou seja, UM NÍVEL ACIMA de public_html — fora do alcance do navegador,
 * mesmo que algum .htaccess falhe, e intocado pelo deploy, que só mexe
 * dentro de public_html.
 *
 * O conteúdo dele é só isto:
 *
 *     <?php return [
 *         'host'  => 'localhost',
 *         'nome'  => 'u278289683_gravitas',
 *         'user'  => 'u278289683_gravitas',
 *         'senha' => 'a senha do banco',
 *     ];
 *
 * Sem esse arquivo, o sistema avisa na tela que falta configurar.
 */

$pasta = __DIR__;
for ($i = 0; $i < 7; $i++) {
    $pasta = dirname($pasta);
    $tentativa = $pasta . '/contatos-db.php';
    if (is_file($tentativa) && is_readable($tentativa)) {
        $cfg = require $tentativa;
        if (is_array($cfg) && ($cfg['nome'] ?? '') !== '') {
            return $cfg + ['host' => 'localhost'];
        }
    }
    if ($pasta === '/' || $pasta === dirname($pasta)) {
        break;
    }
}

/* Nada encontrado: devolve vazio, e o ct_pdo() explica o que falta. */
return [
    'host'  => 'localhost',
    'nome'  => '',
    'user'  => '',
    'senha' => '',
];
