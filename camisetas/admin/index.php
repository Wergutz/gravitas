<?php
declare(strict_types=1);
require __DIR__ . '/_auth.php';

$hash = gw_hash_senha();
$msg = '';

// ---------- Ações POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = (string) ($_POST['acao'] ?? '');

    if ($acao === 'definir' && $hash === null) {
        $s1 = (string) ($_POST['senha'] ?? '');
        $s2 = (string) ($_POST['senha2'] ?? '');
        if (strlen($s1) < 8) {
            $msg = 'A senha precisa ter pelo menos 8 caracteres.';
        } elseif ($s1 !== $s2) {
            $msg = 'As senhas não conferem.';
        } else {
            @mkdir(dirname(gw_arquivo_senha()), 0755, true);
            file_put_contents(gw_arquivo_senha(), json_encode(['hash' => password_hash($s1, PASSWORD_DEFAULT)]), LOCK_EX);
            session_regenerate_id(true);
            $_SESSION['gw_admin'] = true;
            header('Location: ./');
            exit;
        }
    } elseif ($acao === 'entrar' && $hash !== null) {
        if (password_verify((string) ($_POST['senha'] ?? ''), $hash)) {
            session_regenerate_id(true);
            $_SESSION['gw_admin'] = true;
            header('Location: ./');
            exit;
        }
        sleep(1);
        $msg = 'Senha incorreta.';
    } elseif ($acao === 'sair') {
        $_SESSION = [];
        session_destroy();
        header('Location: ./');
        exit;
    } elseif ($acao === 'status' && gw_logado() && gw_csrf_ok()) {
        $codigo = (string) ($_POST['c'] ?? '');
        $novo = (string) ($_POST['status'] ?? '');
        $orc = gw_ler_orcamento($codigo);
        if ($orc && isset(GW_STATUS[$novo])) {
            $orc['status'] = $novo;
            $orc['historico'][] = ['status' => $novo, 'em' => date('c')];
            file_put_contents(gw_dir_orcamentos() . "/{$codigo}/orcamento.json",
                json_encode($orc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        }
        header('Location: ./?c=' . urlencode($codigo));
        exit;
    }
}

// ---------- Dados para a tela ----------
$tela = $hash === null ? 'definir' : (gw_logado() ? 'painel' : 'entrar');
$detalhe = null;
$lista = [];
$filtro = (string) ($_GET['s'] ?? '');

if ($tela === 'painel') {
    if (isset($_GET['c'])) {
        $detalhe = gw_ler_orcamento((string) $_GET['c']);
    }
    if (!$detalhe) {
        foreach (glob(gw_dir_orcamentos() . '/GW*/orcamento.json') ?: [] as $f) {
            $o = json_decode((string) file_get_contents($f), true);
            if ($o && ($filtro === '' || $o['status'] === $filtro)) $lista[] = $o;
        }
        usort($lista, fn($a, $b) => strcmp($b['criado'], $a['criado']));
    }
}

function data_br(string $iso): string
{
    return date('d/m/Y H:i', strtotime($iso));
}
function whats_link(string $num, string $texto = ''): string
{
    $n = preg_replace('/\D/', '', $num);
    if (strlen($n) <= 11) $n = '55' . $n;
    return 'https://wa.me/' . $n . ($texto !== '' ? '?text=' . rawurlencode($texto) : '');
}
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Painel GW Shop</title>
<style>
  :root { --fundo:#f7f4ee; --sup:#fff; --texto:#1d1d1f; --suave:#6b6760; --linha:#e4dfd5; --dest:#e2572b; --cartao:#efe9de; }
  * { box-sizing: border-box; }
  body { margin:0; background:var(--fundo); color:var(--texto); font:15px/1.5 system-ui, -apple-system, 'Segoe UI', sans-serif; }
  a { color:inherit; }
  .wrap { max-width:1100px; margin:0 auto; padding:0 16px; }
  header { background:var(--sup); border-bottom:1px solid var(--linha); }
  header .wrap { display:flex; align-items:center; justify-content:space-between; height:60px; gap:12px; }
  .marca { font-weight:900; font-size:20px; text-decoration:none; letter-spacing:.02em; }
  .marca span { color:var(--dest); font-size:12px; letter-spacing:.12em; margin-left:4px; }
  h1 { font-size:24px; margin:28px 0 16px; }
  .caixa { background:var(--sup); border:1px solid var(--linha); border-radius:16px; padding:20px; }
  .login { max-width:380px; margin:60px auto; }
  label { display:flex; flex-direction:column; gap:4px; font-weight:500; margin-bottom:12px; }
  input, select { font:inherit; padding:10px 12px; border:1px solid var(--linha); border-radius:10px; background:var(--fundo); }
  .btn { display:inline-flex; align-items:center; gap:6px; font:inherit; font-weight:600; padding:9px 16px; border-radius:999px; border:1.5px solid var(--texto); background:transparent; text-decoration:none; cursor:pointer; }
  .btn--p { background:var(--texto); color:var(--fundo); }
  .btn--w { background:#1fa855; border-color:#1fa855; color:#fff; }
  .msg { color:#b3261e; }
  .filtros { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:14px; }
  .filtros a { text-decoration:none; padding:6px 12px; border-radius:999px; border:1px solid var(--linha); background:var(--sup); font-size:13px; }
  .filtros a.ativo { background:var(--texto); color:var(--fundo); border-color:var(--texto); }
  table { width:100%; border-collapse:collapse; background:var(--sup); border:1px solid var(--linha); border-radius:16px; overflow:hidden; }
  th, td { text-align:left; padding:10px 12px; border-bottom:1px solid var(--linha); vertical-align:middle; }
  th { font-size:12px; text-transform:uppercase; letter-spacing:.06em; color:var(--suave); background:var(--cartao); }
  tr:last-child td { border-bottom:0; }
  td.num { text-align:right; white-space:nowrap; }
  .thumb { width:96px; height:56px; object-fit:cover; border-radius:6px; background:var(--cartao); display:block; }
  .tag { display:inline-block; font-size:12px; font-weight:600; padding:3px 10px; border-radius:999px; background:var(--cartao); white-space:nowrap; }
  .tag.novo { background:#fde4da; color:#9c3a17; } .tag.aprovado,.tag.producao { background:#dcefe1; color:#1f6b36; }
  .tag.entregue { background:#e2e8f3; color:#22304a; } .tag.cancelado { background:#eee; color:#888; }
  .vazio { text-align:center; color:var(--suave); padding:40px; }
  .grid { display:grid; grid-template-columns:1.4fr 1fr; gap:20px; align-items:start; }
  .mockup { width:100%; border-radius:12px; background:var(--cartao); display:block; }
  dl { display:grid; grid-template-columns:auto 1fr; gap:6px 14px; margin:0; }
  dt { color:var(--suave); } dd { margin:0; font-weight:500; }
  .linha { display:flex; justify-content:space-between; gap:10px; padding:5px 0; border-bottom:1px solid var(--linha); }
  .total { font-size:20px; font-weight:800; border:0; }
  .acoes { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; }
  h2 { font-size:16px; margin:0 0 10px; }
  .mt { margin-top:16px; }
  .obs { white-space:pre-wrap; background:var(--fundo); padding:10px; border-radius:10px; margin:8px 0 0; }
  @media (max-width:800px) {
    .grid { grid-template-columns:1fr; }
    table, tbody, tr, td { display:block; } thead, th { display:none; }
    tr { border-bottom:1px solid var(--linha); padding:8px 0; } td { border:0; padding:4px 12px; } td.num { text-align:left; }
  }
</style>
</head>
<body>
<header><div class="wrap">
  <a class="marca" href="./">GW<span>SHOP</span> · painel</a>
  <?php if ($tela === 'painel'): ?>
  <form method="post"><input type="hidden" name="acao" value="sair"><button class="btn">Sair</button></form>
  <?php endif; ?>
</div></header>

<main class="wrap">
<?php if ($tela === 'definir'): ?>
  <div class="caixa login">
    <h1>Criar senha do painel</h1>
    <p>Primeiro acesso: defina a senha de administrador.</p>
    <?php if ($msg): ?><p class="msg"><?= h($msg) ?></p><?php endif; ?>
    <form method="post">
      <input type="hidden" name="acao" value="definir">
      <label>Senha (mín. 8 caracteres)<input type="password" name="senha" required minlength="8" autocomplete="new-password"></label>
      <label>Repita a senha<input type="password" name="senha2" required minlength="8" autocomplete="new-password"></label>
      <button class="btn btn--p">Salvar e entrar</button>
    </form>
  </div>

<?php elseif ($tela === 'entrar'): ?>
  <div class="caixa login">
    <h1>Entrar</h1>
    <?php if ($msg): ?><p class="msg"><?= h($msg) ?></p><?php endif; ?>
    <form method="post">
      <input type="hidden" name="acao" value="entrar">
      <label>Senha<input type="password" name="senha" required autofocus autocomplete="current-password"></label>
      <button class="btn btn--p">Entrar</button>
    </form>
  </div>

<?php elseif ($detalhe): $o = $detalhe; $calc = $o['calculo']; $c = $o['codigo']; ?>
  <p class="mt"><a href="./">← Todos os orçamentos</a></p>
  <h1><?= h($c) ?> <span class="tag <?= h($o['status']) ?>"><?= h(GW_STATUS[$o['status']] ?? $o['status']) ?></span></h1>
  <div class="grid">
    <div>
      <?php if (isset($o['arquivos']['mockup'])): ?>
        <img class="mockup" src="arquivo.php?c=<?= urlencode($c) ?>&f=mockup" alt="Simulação frente e verso">
      <?php endif; ?>
      <div class="acoes">
        <?php foreach (['arte_frente' => 'Arte da frente', 'arte_verso' => 'Arte do verso', 'mockup' => 'Imagem frente/verso'] as $k => $rot): ?>
          <?php if (isset($o['arquivos'][$k])): ?>
            <a class="btn" href="arquivo.php?c=<?= urlencode($c) ?>&f=<?= $k ?>&baixar=1">⬇ <?= $rot ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php foreach ($calc['lados'] as $lado => $l): $arq = $o['arquivos']["arte_{$lado}"] ?? null; ?>
        <p class="mt"><strong><?= ucfirst(h($lado)) ?>:</strong> <?= h(str_replace('.', ',', (string) $l['w'])) ?> × <?= h(str_replace('.', ',', (string) $l['h'])) ?> cm,
          a <?= h(str_replace('.', ',', (string) $l['x'])) ?> cm da esquerda e <?= h(str_replace('.', ',', (string) $l['y'])) ?> cm do topo da área de impressão
          <?php if ($arq): ?> · arquivo “<?= h($arq['nome_original']) ?>” (<?= number_format($arq['bytes'] / 1048576, 1, ',', '.') ?> MB)<?php endif; ?></p>
      <?php endforeach; ?>
    </div>
    <div>
      <div class="caixa">
        <h2>Cliente</h2>
        <dl>
          <dt>Nome</dt><dd><?= h($o['cliente']['nome']) ?></dd>
          <dt>WhatsApp</dt><dd><?= h($o['cliente']['whatsapp']) ?></dd>
          <?php if ($o['cliente']['email']): ?><dt>E-mail</dt><dd><a href="mailto:<?= h($o['cliente']['email']) ?>"><?= h($o['cliente']['email']) ?></a></dd><?php endif; ?>
          <dt>Data</dt><dd><?= data_br($o['criado']) ?></dd>
        </dl>
        <?php if ($o['cliente']['obs']): ?><p class="obs"><?= h($o['cliente']['obs']) ?></p><?php endif; ?>
        <div class="acoes">
          <a class="btn btn--w" target="_blank" rel="noopener" href="<?= h(whats_link($o['cliente']['whatsapp'], "Olá, {$o['cliente']['nome']}! Sobre o seu orçamento {$c} na GW Shop:")) ?>">Responder no WhatsApp</a>
        </div>
      </div>
      <div class="caixa mt">
        <h2>Pedido</h2>
        <div class="linha"><span><?= h($calc['modelo']['nome']) ?> · <?= h($calc['cor']['nome']) ?></span><span><?= gw_brl($calc['modelo']['preco']) ?></span></div>
        <?php foreach ($calc['lados'] as $lado => $l): ?>
          <div class="linha"><span>Estampa <?= h($lado) ?> (<?= h($l['faixa']) ?>)</span><span><?= gw_brl($l['preco']) ?></span></div>
        <?php endforeach; ?>
        <div class="linha"><strong>Por peça</strong><strong><?= gw_brl($calc['por_peca']) ?></strong></div>
        <?php foreach ($calc['itens'] as $i): ?>
          <div class="linha"><span><?= (int) $i['qtd'] ?>× <?= h($i['tamanho']) ?></span><span><?= gw_brl($i['total']) ?></span></div>
        <?php endforeach; ?>
        <?php if ($calc['desconto_pct']): ?>
          <div class="linha"><span>Desconto <?= (int) $calc['desconto_pct'] ?>%</span><span>− <?= gw_brl($calc['desconto']) ?></span></div>
        <?php endif; ?>
        <div class="linha total"><span>Total</span><span><?= gw_brl($calc['total']) ?></span></div>
      </div>
      <form class="caixa mt" method="post">
        <h2>Status</h2>
        <input type="hidden" name="acao" value="status">
        <input type="hidden" name="csrf" value="<?= h(gw_csrf()) ?>">
        <input type="hidden" name="c" value="<?= h($c) ?>">
        <div class="acoes" style="margin-top:0">
          <select name="status"><?php foreach (GW_STATUS as $k => $v): ?><option value="<?= $k ?>"<?= $k === $o['status'] ? ' selected' : '' ?>><?= h($v) ?></option><?php endforeach; ?></select>
          <button class="btn btn--p">Salvar</button>
        </div>
      </form>
    </div>
  </div>

<?php else: ?>
  <h1>Orçamentos</h1>
  <nav class="filtros">
    <a href="./" class="<?= $filtro === '' ? 'ativo' : '' ?>">Todos</a>
    <?php foreach (GW_STATUS as $k => $v): ?><a href="?s=<?= $k ?>" class="<?= $filtro === $k ? 'ativo' : '' ?>"><?= h($v) ?></a><?php endforeach; ?>
  </nav>
  <?php if (!$lista): ?>
    <div class="caixa vazio">Nenhum orçamento<?= $filtro ? ' com esse status' : ' ainda' ?>.</div>
  <?php else: ?>
  <table>
    <thead><tr><th></th><th>Código</th><th>Cliente</th><th>Pedido</th><th>Status</th><th class="num">Total</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $o): $c = $o['codigo']; $calc = $o['calculo']; ?>
      <tr>
        <td><a href="?c=<?= urlencode($c) ?>"><?php if (isset($o['arquivos']['mockup'])): ?><img class="thumb" loading="lazy" src="arquivo.php?c=<?= urlencode($c) ?>&f=mockup" alt=""><?php endif; ?></a></td>
        <td><a href="?c=<?= urlencode($c) ?>"><strong><?= h($c) ?></strong></a><br><small><?= data_br($o['criado']) ?></small></td>
        <td><?= h($o['cliente']['nome']) ?><br><small><?= h($o['cliente']['whatsapp']) ?></small></td>
        <td><?= (int) $calc['qtd'] ?>× <?= h($calc['modelo']['nome']) ?> <?= h($calc['cor']['nome']) ?><br><small><?= h(implode(' + ', array_map('ucfirst', array_keys($calc['lados'])))) ?></small></td>
        <td><span class="tag <?= h($o['status']) ?>"><?= h(GW_STATUS[$o['status']] ?? $o['status']) ?></span></td>
        <td class="num"><strong><?= gw_brl($calc['total']) ?></strong></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
<?php endif; ?>
</main>
</body>
</html>
