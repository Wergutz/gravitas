<?php
/**
 * API do Contatos com Prefeituras.
 *
 * Único ponto por onde o navegador chega aos dados. Toda ação passa por
 * ct_exige_login(), menos entrar e consultar a sessão — é aqui que mora
 * o que no Supabase era feito por RLS.
 *
 * As regras são as mesmas que as políticas do Postgres tinham:
 *   - sem login, nada;
 *   - a equipe lê e escreve cidades, pessoas, interações e agendas;
 *   - cada um só altera o próprio perfil e a própria senha.
 *
 * As colunas graváveis de cada tabela são listadas abaixo, e nada fora
 * dessa lista entra no banco. Assim um campo a mais no corpo do pedido
 * não vira uma coluna alterada por acidente.
 */

declare(strict_types=1);

require __DIR__ . '/../app/bd.php';

/* Colunas que o cliente pode gravar, por tabela. */
const CT_CAMPOS = [
    'cidades'    => ['id','nome','uf','populacao','prefeito','vice','presidente_camara',
                     'cadeiras_camara','status','resumo','ordem'],
    'pessoas'    => ['id','cidade_id','grupo','orgao','cargo','nome','partido','telefone','whatsapp',
                     'email','endereco','rede_social','obs_fonte','foto','ordem','prioritario',
                     'vinculado_a','status','responsavel_id','notas','atualizado_por'],
    'interacoes' => ['cidade_id','pessoa_id','tipo','data','resumo','resultado','proximo_passo'],
    'agendas'    => ['cidade_id','pessoa_ids','titulo','inicio','fim','local','status','obs'],
];

/* ------------------------------------------------------------------ */
function ct_corpo(): array
{
    $cru = file_get_contents('php://input') ?: '';
    $d = json_decode($cru, true);
    return is_array($d) ? $d : [];
}

/** Mantém só as colunas permitidas; devolve [colunas => valores]. */
function ct_filtra(string $tabela, array $dados): array
{
    $ok = CT_CAMPOS[$tabela] ?? [];
    $out = [];
    foreach ($ok as $c) {
        if (array_key_exists($c, $dados)) {
            $v = $dados[$c];
            if (is_array($v)) {           // pessoa_ids chega como lista
                $v = json_encode(array_values($v), JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($v)) {
                $v = $v ? 1 : 0;
            } elseif ($v === '') {
                $v = null;
            }
            $out[$c] = $v;
        }
    }
    return $out;
}

function ct_insere(string $tabela, array $campos): array
{
    $cols = array_keys($campos);
    $sql = sprintf(
        'INSERT INTO contatos_%s (%s) VALUES (%s)',
        $tabela,
        implode(',', array_map(fn($c) => "`$c`", $cols)),
        implode(',', array_fill(0, count($cols), '?'))
    );
    ct_pdo()->prepare($sql)->execute(array_values($campos));
    return ct_linha($tabela, (string) $campos['id']);
}

/** Momento atual com milissegundos, no formato que o MySQL aceita. */
function ct_agora(): string
{
    return (new DateTimeImmutable('now'))->format('Y-m-d H:i:s.v');
}

function ct_atualiza(string $tabela, string $id, array $campos): array
{
    unset($campos['id']);
    if ($campos === []) {
        return ct_linha($tabela, $id);
    }
    // Carimbado aqui, e não pela coluna: o ON UPDATE do MySQL não dispara
    // quando a gravação não muda valor nenhum, e é esse carimbo que avisa
    // as outras telas de que há novidade.
    if (in_array($tabela, ['cidades', 'pessoas', 'agendas'], true)) {
        $campos['atualizado_em'] = ct_agora();
    }
    $sets = implode(',', array_map(fn($c) => "`$c` = ?", array_keys($campos)));
    $st = ct_pdo()->prepare("UPDATE contatos_$tabela SET $sets WHERE id = ?");
    $st->execute([...array_values($campos), $id]);
    return ct_linha($tabela, $id);
}

function ct_linha(string $tabela, string $id): array
{
    $st = ct_pdo()->prepare("SELECT * FROM contatos_$tabela WHERE id = ?");
    $st->execute([$id]);
    $r = $st->fetch();
    if (!$r) {
        ct_falha(404, 'nao_encontrado', 'Registro não encontrado.');
    }
    return ct_normaliza($tabela, $r);
}

/** Devolve ao navegador os tipos que o JavaScript espera. */
function ct_normaliza(string $tabela, array $r): array
{
    foreach (['prioritario','ativo','trocar_senha'] as $c) {
        if (isset($r[$c])) { $r[$c] = (bool) $r[$c]; }
    }
    foreach (['populacao','cadeiras_camara','ordem'] as $c) {
        if (isset($r[$c])) { $r[$c] = (int) $r[$c]; }
    }
    if ($tabela === 'agendas') {
        $r['pessoa_ids'] = $r['pessoa_ids'] ? (json_decode($r['pessoa_ids'], true) ?: []) : [];
    }
    unset($r['senha']);                   // nunca sai daqui
    return $r;
}

function ct_exige_id(array $d): string
{
    $id = trim((string) ($d['id'] ?? ''));
    if ($id === '') {
        ct_falha(400, 'sem_id', 'Faltou dizer qual registro.');
    }
    return $id;
}

/* ------------------------------------------------------------------ */
$acao = (string) ($_GET['a'] ?? '');
$d    = ct_corpo();

try {
    switch ($acao) {

        /* ---- sessão ------------------------------------------------ */
        case 'sessao':
            ct_json(['usuario' => ct_usuario()]);

        case 'entrar': {
            ct_sessao_inicia();
            $ate = (int) ($_SESSION['ct_bloqueio'] ?? 0);
            if ($ate > time()) {
                ct_falha(429, 'muitas_tentativas',
                    'Muitas tentativas. Tente de novo em ' . ceil(($ate - time()) / 60) . ' min.');
            }

            $email = strtolower(trim((string) ($d['email'] ?? '')));
            $senha = (string) ($d['senha'] ?? '');

            $st = ct_pdo()->prepare('SELECT * FROM contatos_usuarios WHERE email = ? AND ativo = 1');
            $st->execute([$email]);
            $u = $st->fetch();

            if (!$u || !password_verify($senha, $u['senha'])) {
                $n = (int) ($_SESSION['ct_tentativas'] ?? 0) + 1;
                $_SESSION['ct_tentativas'] = $n;
                if ($n >= CT_MAX_TENTA) {
                    $_SESSION['ct_bloqueio']   = time() + CT_BLOQUEIO_SEG;
                    $_SESSION['ct_tentativas'] = 0;
                }
                // Mesma resposta para e-mail inexistente e senha errada: não
                // entrega a quem tenta adivinhar quais e-mails existem.
                ct_falha(401, 'credenciais', 'E-mail ou senha incorretos.');
            }

            unset($_SESSION['ct_tentativas'], $_SESSION['ct_bloqueio']);
            session_regenerate_id(true);          // troca o id ao elevar privilégio
            $_SESSION['ct_uid'] = $u['id'];
            ct_pdo()->prepare('UPDATE contatos_usuarios SET ultimo_acesso = NOW() WHERE id = ?')
                    ->execute([$u['id']]);
            ct_json(['usuario' => ct_usuario()]);
        }

        case 'sair':
            ct_sessao_inicia();
            $_SESSION = [];
            session_destroy();
            ct_json(['ok' => true]);

        case 'senha': {
            $me = ct_exige_login();
            $nova = (string) ($d['senha'] ?? '');
            if (strlen($nova) < 8) {
                ct_falha(400, 'senha_curta', 'A senha precisa de ao menos 8 caracteres.');
            }
            ct_pdo()->prepare('UPDATE contatos_usuarios SET senha = ?, trocar_senha = 0 WHERE id = ?')
                    ->execute([password_hash($nova, PASSWORD_DEFAULT), $me['id']]);
            ct_json(['usuario' => ct_usuario()]);
        }

        /* ---- dados ------------------------------------------------- */
        case 'tudo': {
            ct_exige_login();
            $p = ct_pdo();
            $le = function (string $t, string $ordem) use ($p) {
                $linhas = $p->query("SELECT * FROM contatos_$t ORDER BY $ordem")->fetchAll();
                return array_map(fn($r) => ct_normaliza($t, $r), $linhas);
            };
            $usuarios = array_map(
                fn($r) => ['id' => $r['id'], 'nome' => $r['nome'], 'cor' => $r['cor']],
                $p->query('SELECT id, nome, cor FROM contatos_usuarios WHERE ativo = 1 ORDER BY nome')->fetchAll()
            );
            ct_json([
                'usuarios'   => $usuarios,
                'cidades'    => $le('cidades', '`ordem`'),
                'pessoas'    => $le('pessoas', '`ordem`'),
                'interacoes' => $le('interacoes', '`data` DESC'),
                'agendas'    => $le('agendas', '`inicio`'),
            ]);
        }

        /* Impressão digital barata: o cliente só recarrega tudo quando muda. */
        case 'versao': {
            ct_exige_login();
            $p = ct_pdo();
            $f = [];
            foreach (['cidades' => 'atualizado_em', 'pessoas' => 'atualizado_em',
                      'interacoes' => 'criado_em', 'agendas' => 'atualizado_em',
                      'contatos_usuarios' => 'criado_em'] as $t => $col) {
                $tab = str_starts_with($t, 'contatos_') ? $t : "contatos_$t";
                $r = $p->query("SELECT COUNT(*) n, COALESCE(MAX(`$col`),'') m FROM $tab")->fetch();
                $f[] = $r['n'] . ':' . $r['m'];
            }
            ct_json(['versao' => md5(implode('|', $f))]);
        }

        /* ---- perfil: cada um só o seu ------------------------------ */
        case 'perfil': {
            $me = ct_exige_login();
            $campos = [];
            if (isset($d['nome']) && trim((string) $d['nome']) !== '') {
                $campos['nome'] = trim((string) $d['nome']);
            }
            if (isset($d['cor'])) {
                $campos['cor'] = (string) $d['cor'];
            }
            if ($campos === []) {
                ct_json(['usuario' => $me]);
            }
            $sets = implode(',', array_map(fn($c) => "`$c` = ?", array_keys($campos)));
            ct_pdo()->prepare("UPDATE contatos_usuarios SET $sets WHERE id = ?")
                    ->execute([...array_values($campos), $me['id']]);
            ct_json(['usuario' => ct_usuario()]);
        }

        /* ---- cidades, pessoas, interações, agendas ----------------- */
        case 'cidade.criar':
            ct_exige_login();
            $c = ct_filtra('cidades', $d);
            if (empty($c['id']) || empty($c['nome'])) {
                ct_falha(400, 'faltou', 'Cidade precisa de identificador e nome.');
            }
            ct_json(['linha' => ct_insere('cidades', $c)]);

        case 'cidade.editar':
            ct_exige_login();
            ct_json(['linha' => ct_atualiza('cidades', ct_exige_id($d), ct_filtra('cidades', $d))]);

        case 'pessoa.criar':
            ct_exige_login();
            $c = ct_filtra('pessoas', $d);
            if (empty($c['id']) || empty($c['nome']) || empty($c['cidade_id'])) {
                ct_falha(400, 'faltou', 'Pessoa precisa de identificador, nome e cidade.');
            }
            ct_json(['linha' => ct_insere('pessoas', $c)]);

        case 'pessoa.editar':
            ct_exige_login();
            ct_json(['linha' => ct_atualiza('pessoas', ct_exige_id($d), ct_filtra('pessoas', $d))]);

        case 'interacao.criar': {
            $me = ct_exige_login();
            $c = ct_filtra('interacoes', $d);
            $c['id']         = ct_uuid();
            $c['usuario_id'] = $me['id'];          // sempre quem está logado
            $c['data']       = $c['data'] ?? ct_agora();
            ct_json(['linha' => ct_insere('interacoes', $c)]);
        }

        case 'interacao.apagar': {
            $me = ct_exige_login();
            $id = ct_exige_id($d);
            // Só o autor apaga o próprio registro: é histórico de contato.
            $st = ct_pdo()->prepare('DELETE FROM contatos_interacoes WHERE id = ? AND usuario_id = ?');
            $st->execute([$id, $me['id']]);
            if ($st->rowCount() === 0) {
                ct_falha(403, 'nao_e_seu', 'Só quem registrou o contato pode apagá-lo.');
            }
            ct_json(['ok' => true]);
        }

        case 'agenda.criar': {
            $me = ct_exige_login();
            $c = ct_filtra('agendas', $d);
            $c['id']         = ct_uuid();
            $c['usuario_id'] = $me['id'];
            if (empty($c['titulo']) || empty($c['inicio'])) {
                ct_falha(400, 'faltou', 'Compromisso precisa de título e início.');
            }
            ct_json(['linha' => ct_insere('agendas', $c)]);
        }

        case 'agenda.editar':
            ct_exige_login();
            ct_json(['linha' => ct_atualiza('agendas', ct_exige_id($d), ct_filtra('agendas', $d))]);

        case 'agenda.apagar': {
            $me = ct_exige_login();
            $id = ct_exige_id($d);
            $st = ct_pdo()->prepare('DELETE FROM contatos_agendas WHERE id = ? AND usuario_id = ?');
            $st->execute([$id, $me['id']]);
            if ($st->rowCount() === 0) {
                ct_falha(403, 'nao_e_seu', 'Só quem marcou o compromisso pode apagá-lo.');
            }
            ct_json(['ok' => true]);
        }

        /* ---- quem está online -------------------------------------- */
        case 'presenca': {
            $me = ct_exige_login();
            $p  = ct_pdo();
            $p->prepare('UPDATE contatos_usuarios SET visto_em = NOW() WHERE id = ?')->execute([$me['id']]);
            $st = $p->prepare(
                'SELECT id, nome, cor FROM contatos_usuarios
                  WHERE ativo = 1 AND visto_em >= (NOW() - INTERVAL ? SECOND) ORDER BY nome'
            );
            $st->execute([CT_ONLINE_SEG]);
            ct_json(['online' => $st->fetchAll()]);
        }

        default:
            ct_falha(404, 'acao_desconhecida', 'Ação não reconhecida.');
    }
} catch (PDOException $e) {
    error_log('[contatos] ' . $acao . ': ' . $e->getMessage());

    // 42S02 = tabela não existe. É exatamente o estado entre configurar o
    // banco e rodar os scripts; sem esta distinção a tela diria apenas
    // "o banco recusou a operação", que não indica o que fazer.
    if ($e->getCode() === '42S02') {
        ct_falha(503, 'tabelas_ausentes',
            'O banco respondeu, mas as tabelas ainda não existem. '
            . 'Rode os scripts de contatos/sql/ no phpMyAdmin, na ordem.');
    }

    ct_falha(500, 'erro_banco', 'O banco recusou a operação.');
}
