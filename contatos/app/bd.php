<?php
/**
 * Conexão e apoio do Contatos com Prefeituras.
 *
 * O navegador nunca fala com o banco: fala com /api, e só este código
 * fala com o MySQL. É por isso que aqui não há nada parecido com RLS —
 * quem decide o que cada um pode fazer é a camada de PHP, em api/index.php.
 */

declare(strict_types=1);

const CT_SESSAO       = 'GRV_CONTATOS';   // nome próprio: não colide com o painel
const CT_ONLINE_SEG   = 90;               // quem bateu ponto nesse prazo conta como online
const CT_MAX_TENTA    = 8;                // tentativas de senha antes de segurar
const CT_BLOQUEIO_SEG = 300;

function ct_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = require __DIR__ . '/config/db.php';

    if (($cfg['nome'] ?? '') === '' || ($cfg['user'] ?? '') === '') {
        ct_falha(503, 'banco_nao_configurado',
            'O banco do site ainda não foi configurado. Preencha contatos/app/config/db.php.');
    }

    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['nome']),
            $cfg['user'],
            $cfg['senha'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        // Mesmo fuso do resto do site, para as datas baterem com as do painel.
        $pdo->exec("SET time_zone = '-03:00'");
    } catch (PDOException $e) {
        // A mensagem do driver traz host, usuário e às vezes a senha: fica no log.
        error_log('[contatos] falha de conexão: ' . $e->getMessage());
        ct_falha(503, 'banco_indisponivel', 'Não foi possível falar com o banco.');
    }

    return $pdo;
}

/** Inicia a sessão própria do Contatos. */
function ct_sessao_inicia(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    session_name(CT_SESSAO);
    session_set_cookie_params([
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

/** Devolve o usuário da sessão, ou null. */
function ct_usuario(): ?array
{
    ct_sessao_inicia();
    $id = $_SESSION['ct_uid'] ?? null;
    if (!$id) {
        return null;
    }
    $st = ct_pdo()->prepare(
        'SELECT id, email, nome, cor, trocar_senha FROM contatos_usuarios WHERE id = ? AND ativo = 1'
    );
    $st->execute([$id]);
    $u = $st->fetch();
    if (!$u) {                      // conta desativada no meio da sessão
        unset($_SESSION['ct_uid']);
        return null;
    }
    $u['trocar_senha'] = (bool) $u['trocar_senha'];
    return $u;
}

/** Exige sessão. Sem ela, encerra com 401. */
function ct_exige_login(): array
{
    $u = ct_usuario();
    if (!$u) {
        ct_falha(401, 'sem_sessao', 'Faça login para continuar.');
    }
    return $u;
}

function ct_uuid(): string
{
    $b = random_bytes(16);
    $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
    $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

function ct_json($dados, int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ct_falha(int $codigo, string $chave, string $msg): void
{
    ct_json(['erro' => $chave, 'mensagem' => $msg], $codigo);
}
