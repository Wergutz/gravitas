<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController
{
    public static function login()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /visionhub/index.php");
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if ($email === '' || $senha === '') {
            $_SESSION['erro'] = "Informe login e senha.";
            header("Location: /visionhub/index.php");
            exit;
        }

        global $pdo;
        $usuarioModel = new Usuario($pdo);
        $usuario = $usuarioModel->buscarPorEmail($email);

        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            $_SESSION['erro'] = "Usuário ou senha inválidos.";
            header("Location: /visionhub/index.php");
            exit;
        }

        // Regenera sessão (segurança)
        session_regenerate_id(true);

        // Sessão do usuário
        $_SESSION['usuario_id']   = $usuario['id'];
        $_SESSION['nome']         = $usuario['nome'];
        $_SESSION['tipo_usuario'] = (int)$usuario['tipo_usuario'];

        /*
         * Redirecionamento por perfil
         */
        switch ($_SESSION['tipo_usuario']) {

            case 1: // MASTER
                header("Location: /visionhub/master.php");
                break;

            case 2: // ADMINISTRADOR
                header("Location: /visionhub/admin.php");
                break;

            case 3: // PROPRIETÁRIO
                header("Location: /visionhub/proprietario.php");
                break;

            case 4: // PLANEJADOR
                header("Location: /visionhub/planejador.php");
                break;

            case 5: // EXECUTOR
                header("Location: /visionhub/executor.php");
                break;

            default:
                // perfil inválido → encerra sessão
                session_destroy();
                header("Location: /visionhub/index.php");
        }

        exit;
    }

    public static function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_destroy();
        header("Location: /visionhub/index.php");
        exit;
    }
}
