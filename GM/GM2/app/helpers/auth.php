<?php
function auth_required(array $perfis) {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('GM2_PAINEL');
        session_set_cookie_params(['path' => '/GM/GM2/', 'samesite' => 'Lax', 'httponly' => true]);
        session_start();
    }

    if (
        !isset($_SESSION['usuario_id'], $_SESSION['tipo_usuario']) ||
        !in_array($_SESSION['tipo_usuario'], $perfis)
    ) {
        header('Location: /GM/GM2/public/login.php');
        exit;
    }
}

/**
 * true quando quem está logado é o Planejador (3).
 *
 * O Executor (4) usa as mesmas telas, mas só o fluxo de medição: criar
 * planejamento e mexer nos cadastros é do planejador. Serve para esconder
 * o que ele não alcança — sem isso o link aparece e derruba para o login.
 * Não substitui o auth_required() de cada tela: esconder link não protege
 * nada, quem protege é a checagem no topo do arquivo.
 */
function eh_planejador(): bool {
    return ((int) ($_SESSION['tipo_usuario'] ?? 0)) === 3;
}
