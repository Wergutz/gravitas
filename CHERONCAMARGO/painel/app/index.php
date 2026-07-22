<?php
// ============================================================
// Painel Gravitas — Área restrita (placeholder)
// As telas reais do painel substituirão este arquivo.
// ============================================================
require __DIR__ . '/../auth/protege.php';
$nome   = htmlspecialchars($_SESSION['gv_nome'] ?? 'Usuário', ENT_QUOTES, 'UTF-8');
$perfil = ($_SESSION['gv_perfil'] ?? 'empresa') === 'usuario' ? 'Usuário' : 'Empresa';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1A2D4F">
<meta name="robots" content="noindex,nofollow">
<title>Painel · Cheron &amp; Camargo</title>
<style>
  :root{--navy:#1A2D4F;--navy-900:#11203B;--ink:#1E2738;--muted:#6B7686;--line:#E4E8EF;
        --bg:#F4F6FA;--gold:#E0A53D;
        --font:"Inter",-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif}
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:var(--font);background:var(--bg);color:var(--ink);min-height:100vh}
  header{background:linear-gradient(160deg,var(--navy),var(--navy-900));color:#fff;
         display:flex;align-items:center;gap:14px;padding:16px 28px}
  header img{width:35px;height:35px;object-fit:contain}
  .nm{font-weight:800;letter-spacing:3px;font-size:15px}
  .tag{font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:#C7D2E5;
       border:1px solid #ffffff2e;border-radius:999px;padding:4px 10px}
  .sair{margin-left:auto;color:#fff;text-decoration:none;font-size:13px;font-weight:600;
        border:1px solid #ffffff3a;border-radius:9px;padding:8px 16px}
  .sair:hover{background:#ffffff14}
  main{max-width:980px;margin:0 auto;padding:48px 24px}
  h1{font-size:24px;font-weight:800;letter-spacing:-.3px}
  .sub{color:var(--muted);font-size:14px;margin:6px 0 32px}
  .cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}
  .card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:22px}
  .card h3{font-size:15.5px;margin-bottom:6px}
  .card p{font-size:13px;color:var(--muted);line-height:1.5}
  .breve{display:inline-block;margin-top:12px;font-size:11px;letter-spacing:1.5px;
         text-transform:uppercase;font-weight:700;color:#A96C2A;background:#c9853d1f;
         border-radius:999px;padding:4px 10px}
</style>
</head>
<body>
<header>
  <img src="/CHERONCAMARGO/painel/assets/img/icon-cheron-camargo-white.png" alt="Cheron & Camargo">
  <span class="nm">Cheron &amp; Camargo</span>
  <span class="tag">Painel · <?php echo $perfil; ?></span>
  <a class="sair" href="../auth/logout.php">Sair</a>
</header>

<main>
  <h1>Bem-vindo, <?php echo $nome; ?>.</h1>
  <p class="sub">Login funcionando. Os módulos do painel serão ativados aqui nas próximas etapas do projeto.</p>

  <div class="cards">
    <div class="card"><h3>App de Produção</h3><p>Coleta de campo, medição automática e RDO.</p><span class="breve">Em breve</span></div>
    <div class="card"><h3>Planejamento</h3><p>Programação diária de equipes e equipamentos.</p><span class="breve">Em breve</span></div>
    <div class="card"><h3>Controle &amp; Indicadores</h3><p>Materiais, estoque e dashboards gerenciais.</p><span class="breve">Em breve</span></div>
  </div>
</main>
</body>
</html>
