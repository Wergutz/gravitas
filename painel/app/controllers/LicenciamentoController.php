<?php

/**
 * Licenciamento viário — protótipo de demonstração (cidade-piloto: Uruguaiana).
 *
 * O protótipo é uma página HTML autossuficiente (HTML, CSS e JS num arquivo só,
 * sem backend e sem persistência), entregue pronta e mantida como veio.
 *
 * Ela fica em app/views/licenciamento/ e é entregue por esta rota, e não como
 * arquivo solto dentro do painel: o .htaccess da raiz do app só reescreve para
 * o index.php os caminhos que NÃO existem no disco, então um .html publicado
 * direto ficaria acessível a qualquer pessoa, sem login. Passando pela rota, a
 * página herda o auth_required() do index.php.
 */
class LicenciamentoController {

    public function index(): void {
        $arquivo = dirname(__DIR__) . '/views/licenciamento/prototipo.html';

        if (!is_readable($arquivo)) {
            http_response_code(500);
            echo 'Não foi possível abrir a demonstração do licenciamento viário.';
            return;
        }

        header('Content-Type: text/html; charset=utf-8');
        header('X-Frame-Options: SAMEORIGIN');
        header('Cache-Control: private, no-store');

        /* O protótipo ocupa a tela inteira com a barra lateral dele, então a
           barra do painel não fica visível: o link "← Painel de Controle" no
           rodapé da barra é o único caminho de volta. No arquivo original ele
           era um `href="#"` que só mostrava um aviso — fazia sentido na
           demonstração solta, mas aqui deixaria a página sem saída.

           O caminho entra aqui, e não no HTML, para o protótipo continuar
           sem endereço fixo: quem decide onde o painel mora é o APP_BASE. */
        echo str_replace('__APP_BASE__', APP_BASE, file_get_contents($arquivo));
    }
}
