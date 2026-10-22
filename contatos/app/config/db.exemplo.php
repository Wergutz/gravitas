<?php
/**
 * MODELO. Copie para `db.php`, na mesma pasta, e preencha.
 *
 * O db.php está no .gitignore: ele existe no servidor e não entra no
 * repositório — senha em Git fica no histórico para sempre, e apagar
 * depois não resolve, porque os commits antigos continuam lá.
 *
 * O arquivo é criado UMA vez, pelo hPanel, em
 *
 *     public_html/principal/contatos/app/config/db.php
 *
 * e sobrevive às publicações: o deploy usa `git reset --hard`, que não
 * apaga arquivo fora do repositório, e a cópia para /contatos/ o leva
 * junto. Conferido.
 */
return [
    'host'  => 'localhost',
    'nome'  => 'u278289683_gravitas',
    'user'  => 'u278289683_gravitas',
    'senha' => 'A SENHA DO BANCO',
];
