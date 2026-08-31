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

/** Nome do perfil de quem está logado, para aparecer na tela. */
function perfil_label(): string {
    return [
        1 => 'Master',
        2 => 'Proprietário',
        3 => 'Planejador',
        4 => 'Executor',
    ][(int) ($_SESSION['tipo_usuario'] ?? 0)] ?? 'Sem perfil';
}

/**
 * Bloco de identificação para o menu lateral: quem está logado e em que
 * perfil. Planejador e Executor usam as mesmas telas — sem isso, dá para
 * passar a sessão inteira achando que se está no outro perfil.
 */
function bloco_identidade(): string {
    $nome = trim((string) ($_SESSION['nome'] ?? ''));
    if ($nome === '') {
        $nome = trim((string) ($_SESSION['usuario'] ?? ''));
    }

    // Sessão aberta antes de o login passar a guardar o nome: mostra só o
    // perfil, em vez de um traço solto no lugar do nome.
    $linhaNome = $nome !== ''
        ? '<b>' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '</b>'
        : '';

    return '<div class="quem">'
         . $linhaNome
         . '<span>' . htmlspecialchars(perfil_label(), ENT_QUOTES, 'UTF-8') . '</span>'
         . '</div>';
}
