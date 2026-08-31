<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('GM2_PAINEL');
    session_set_cookie_params(['path' => '/GM/GM2/', 'samesite' => 'Lax', 'httponly' => true]);
    session_start();
}

$tipo = $_SESSION['tipo_usuario'] ?? null;
$base = '/GM/GM2';
?>

<nav class="menu">
    <ul>

        <?php if ($tipo == 3): // PLANEJADOR ?>

            <li class="menu-section">PLANEJADOR</li>

            <li>
                <a href="<?= $base ?>/planejador/funcionarios.php">
                    Cadastro de Funcionários
                </a>
            </li>

            <li>
                <a href="<?= $base ?>/planejador/equipamentos_pesados.php">
                    Cadastro Equipamento Pesado
                </a>
            </li>

            <li>
                <a href="<?= $base ?>/planejador/equipamentos_leves.php">
                    Cadastro Equipamento Leve
                </a>
            </li>

            <li>
                <a href="<?= $base ?>/planejador/equipes.php">
                    Cadastro Equipe
                </a>
            </li>

            <li>
                <a href="<?= $base ?>/planejador/planejamento.php">
                    Planejamento
                </a>
            </li>

        <?php endif; ?>

        <li class="logout">
            <a href="<?= $base ?>/public/logout.php">Sair</a>
        </li>

    </ul>
</nav>
