<?php
/**
 * =====================================================================
 *  Relatório Oficial de Medição — PDF GERADO NO SERVIDOR
 * =====================================================================
 *
 *  POR QUE ESTE ARQUIVO EXISTE
 *
 *  A tela do relatório (relatorio.php) sai em papel pelo "Imprimir" do
 *  navegador, e o resultado depende de quem imprime: a versão do
 *  navegador decide onde cai a quebra de página, e o diálogo de
 *  impressão ainda mexe em margem, escala e cabeçalho. Foi assim que
 *  fotos apareceram cortadas ao meio entre duas páginas — o que um
 *  laudo pericial não admite.
 *
 *  Aqui o PDF é montado pelo servidor. O arquivo sai igual para todo
 *  mundo, venha de que máquina vier, e a paginação é decidida por um
 *  único motor, sempre o mesmo.
 *
 *  A GARANTIA DE QUE A FOTO NÃO É CORTADA
 *
 *  Cada linha de fotos é uma <tr> com `page-break-inside: avoid`. Não
 *  quebrar dentro de uma linha de tabela é a construção de paginação
 *  mais bem suportada que existe. Além disso, as caixas de imagem têm
 *  altura fixa em milímetros, calculada aqui, muito menor que a área
 *  útil da página — ou seja, uma linha sempre CABE inteira em uma
 *  página, e o motor nunca é forçado a parti-la.
 *
 *  Os números e os textos não são recalculados: vêm do mesmo
 *  app/helpers/relatorio_dados.php que alimenta a tela. Tela e PDF não
 *  podem divergir.
 * =====================================================================
 */

require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/config/relatorio.php';
require_once __DIR__ . '/../app/helpers/tipos_pavimento.php';
require_once __DIR__ . '/../app/helpers/midia_url.php';

auth_required([3]);

require __DIR__ . '/../app/helpers/relatorio_dados.php';

/* thumb.php como biblioteca: só queremos a função que gera a miniatura
   em disco e devolve o caminho. Sem a constante abaixo ele se comporta
   como endpoint HTTP e responderia à requisição sozinho. */
define('GRV_THUMB_AS_LIBRARY', true);
require_once __DIR__ . '/thumb.php';

require_once __DIR__ . '/../vendor/autoload.php';

if (!$dados) {
    header('Location: relatorio.php' . ($planejamentoSelecionado ? '?planejamento_id=' . (int) $planejamentoSelecionado : ''));
    exit;
}

/* =====================================================================
   GEOMETRIA — tudo em milímetros, fixado aqui e não pelo navegador
   ---------------------------------------------------------------------
   A4 retrato: 210 x 297. Com as margens abaixo sobram 178 mm de largura
   e 261 mm de altura útil por página.
   ===================================================================== */
const MU_PDF_MARGEM_TOPO   = 23.0;   // abriga o cabeçalho repetido
const MU_PDF_MARGEM_BAIXO  = 16.0;   // abriga o rodapé repetido
const MU_PDF_MARGEM_LADO   = 16.0;

const MU_PDF_LARG_UTIL     = 210.0 - (2 * MU_PDF_MARGEM_LADO);          // 178
const MU_PDF_ALT_UTIL      = 297.0 - MU_PDF_MARGEM_TOPO - MU_PDF_MARGEM_BAIXO; // 258

/* Dentro do quadro do trecho: 3 mm de recuo de cada lado. */
const MU_PDF_LARG_TRECHO   = MU_PDF_LARG_UTIL - 6.0;                    // 172
const MU_PDF_VAO           = 2.5;                                        // entre fotos

/* Caixa de cada foto: 3 colunas com dois vãos. */
const MU_PDF_FOTO_W = (MU_PDF_LARG_TRECHO - (2 * MU_PDF_VAO)) / 3;      // 55,66
const MU_PDF_FOTO_H = MU_PDF_FOTO_W * 4 / 3;                            // 74,2

/* Croqui ocupa metade da largura. */
const MU_PDF_CROQUI_W = 64.0;
const MU_PDF_CROQUI_H = MU_PDF_CROQUI_W * 4 / 3;                        // 98,7

/**
 * Caminho em disco da imagem que vai para o PDF.
 *
 * Usa a miniatura em cache (mesma que a tela usa) para não embutir um
 * JPEG de 4 MB por foto: com 30 fotos isso estouraria a memória da
 * hospedagem e geraria um PDF impossível de enviar por e-mail. A
 * miniatura de 440 px numa caixa de 55,6 mm dá cerca de 200 dpi, acima
 * do que uma impressão de laudo exige.
 *
 * Se a miniatura falhar (sem GD, cache não gravável), cai para o
 * original — pesado, mas o documento sai.
 */
function mu_pdf_arquivo_imagem(?string $arquivo, int $largura, string $tipo = 'foto'): ?string
{
    $arquivo = trim((string) $arquivo);
    if ($arquivo === '') {
        return null;
    }

    $mini = grv_thumb_build($arquivo, $largura);
    if ($mini !== null && is_file($mini)) {
        return $mini;
    }

    $orig = mu_path_midia($arquivo, $tipo);
    return ($orig !== null && is_file($orig)) ? $orig : null;
}

/**
 * Monta a célula de uma figura.
 *
 * A imagem é encaixada DENTRO da caixa preservando a proporção real do
 * arquivo — nada de esticar. Numa peça pericial a foto não pode ser
 * deformada nem cortada, então o que sobra da caixa vira margem, e a
 * caixa mantém a altura fixa para todas as fotos da linha ficarem
 * alinhadas e a altura da linha ser previsível.
 */
function mu_pdf_figura(?string $caminho, float $caixaW, float $caixaH, string $legenda): string
{
    $conteudo = '';

    if ($caminho !== null) {
        $info = @getimagesize($caminho);
        if (is_array($info) && !empty($info[0]) && !empty($info[1])) {
            $escala = min($caixaW / $info[0], $caixaH / $info[1]);
            $w = $info[0] * $escala;
            $h = $info[1] * $escala;
            $topo = ($caixaH - $h) / 2;

            $conteudo = sprintf(
                '<img src="%s" style="width:%.2fmm;height:%.2fmm;margin-top:%.2fmm">',
                htmlspecialchars($caminho, ENT_QUOTES),
                $w,
                $h,
                $topo
            );
        }
    }

    if ($conteudo === '') {
        $conteudo = '<span class="mu-ausente">imagem indisponível</span>';
    }

    return sprintf(
        '<div class="mu-caixa" style="width:%.2fmm;height:%.2fmm">%s</div>'
        . '<div class="mu-legenda">%s</div>',
        $caixaW,
        $caixaH,
        $conteudo,
        htmlspecialchars($legenda)
    );
}

/** Caminho absoluto de um arquivo de assets, para o Dompdf ler do disco. */
function mu_pdf_asset(string $rel): string
{
    return __DIR__ . '/../' . ltrim($rel, '/');
}

/* =====================================================================
   MONTAGEM DO HTML
   ---------------------------------------------------------------------
   CSS deliberadamente antigo: sem grid, sem flex, sem variáveis, sem
   calc() e sem aspect-ratio. O motor de PDF não implementa nada disso,
   e o que ele não entende ele ignora em silêncio — num laudo, um estilo
   ignorado em silêncio é um defeito que ninguém vê até ser tarde.
   ===================================================================== */
ob_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<style>
@page{
  margin: <?= MU_PDF_MARGEM_TOPO ?>mm <?= MU_PDF_MARGEM_LADO ?>mm <?= MU_PDF_MARGEM_BAIXO ?>mm <?= MU_PDF_MARGEM_LADO ?>mm;
}
body{
  font-family: "DejaVu Serif", serif;
  font-size: 9pt;
  line-height: 1.35;
  color: #111111;
  margin: 0;
}

/* ---- cabeçalho e rodapé: repetem em todas as páginas -------------- */
.mu-cab{
  position: fixed;
  top: -18mm; left: 0; right: 0;
  width: 100%;
  height: 12mm;
  border-bottom: 2.2pt solid #31A862;
}
.mu-cab td{ border: none; padding: 0; vertical-align: bottom; width: 50%; }
.mu-cab img{ height: 11mm; }
.mu-cab .mu-cab-doc{
  text-align: right;
  font-family: "DejaVu Sans", sans-serif;
  font-size: 7.5pt;
  line-height: 1.35;
  color: #5A5A5A;
}
.mu-rod{
  position: fixed;
  width: 100%;
  bottom: -12mm; left: 0; right: 0;
  height: 8mm;
  border-top: .6pt solid #B9B9B9;
  padding-top: 1.6mm;
  font-family: "DejaVu Sans", sans-serif;
  font-size: 6pt;
  color: #5A5A5A;
}
.mu-rod td{ border: none; padding: 0; }
.mu-rod .esq{ width: 66%; padding-right: 4mm; }
.mu-rod .dir{ width: 34%; }
.mu-rod .esq{ padding-right: 4mm; }
.mu-rod .dir{ text-align: right; }

/* ---- título ------------------------------------------------------- */
h1.mu-title{
  font-size: 13.5pt;
  text-transform: uppercase;
  text-align: center;
  letter-spacing: .06em;
  margin: 0 0 1.5mm;
}
p.mu-subtitle{
  text-align: center;
  font-size: 9.5pt;
  color: #5A5A5A;
  margin: 0 0 6mm;
}

/* ---- identificação ------------------------------------------------ */
table.mu-ident{
  width: 100%;
  border-collapse: collapse;
  font-size: 7.8pt;
  margin-bottom: 5mm;
}
table.mu-ident th, table.mu-ident td{
  border: .6pt solid #B9B9B9;
  padding: 1.2mm 1.8mm;
  text-align: left;
  vertical-align: top;
}
table.mu-ident th{
  width: 23mm;
  background-color: #EAF6EF;
  color: #278A52;
  font-family: "DejaVu Sans", sans-serif;
  font-size: 7.2pt;
  text-transform: uppercase;
}
table.mu-ident td{ font-weight: bold; }
.mu-conf{ background-color: #FFF6D6; }

/* ---- trecho -------------------------------------------------------- */
.mu-trecho{ margin-bottom: 7mm; }
table.mu-trecho-head{
  width: 100%;
  border-collapse: collapse;
  background-color: #278A52;
  color: #FFFFFF;
  font-family: "DejaVu Sans", sans-serif;
  font-size: 8.6pt;
  text-transform: uppercase;
}
table.mu-trecho-head td{ border: none; padding: 1.8mm 2.6mm; font-weight: bold; }
table.mu-trecho-head td.dir{ text-align: right; font-weight: normal; font-size: 7.4pt; }
.mu-trecho-body{
  border: .6pt solid #B9B9B9;
  border-top: none;
  padding: 3mm;
}
table.mu-meta{
  width: 100%;
  border-collapse: collapse;
  font-family: "DejaVu Sans", sans-serif;
  font-size: 8pt;
  margin-bottom: 3mm;
  page-break-inside: avoid;
}
table.mu-meta td{ border: none; padding: 0 3mm 0 0; width: 33.33%; vertical-align: top; }
.mu-rot{
  display: block;
  color: #5A5A5A;
  font-size: 6.8pt;
  text-transform: uppercase;
}
.mu-val{ display: block; font-weight: bold; }

/* ---- tabela de medição -------------------------------------------- */
table.mu-tab{
  width: 100%;
  border-collapse: collapse;
  font-family: "DejaVu Sans", sans-serif;
  font-size: 8.4pt;
}
table.mu-tab th{
  background-color: #31A862;
  color: #FFFFFF;
  border: .6pt solid #31A862;
  padding: 1.5mm;
  text-transform: uppercase;
  font-size: 7.2pt;
}
table.mu-tab td{
  border: .6pt solid #B9B9B9;
  padding: 1.4mm;
  text-align: center;
}
table.mu-tab tfoot td{
  background-color: #EAF6EF;
  color: #278A52;
  font-weight: bold;
  text-align: right;
}
table.mu-tab tfoot td.mu-tot-val{ text-align: center; }

/* ---- galeria: A REGRA QUE IMPEDE A FOTO CORTADA -------------------- */
h5.mu-figs-tit{
  page-break-after: avoid;
  font-family: "DejaVu Sans", sans-serif;
  font-size: 7.4pt;
  text-transform: uppercase;
  color: #278A52;
  border-bottom: .6pt solid #EAF6EF;
  padding-bottom: .8mm;
  margin: 3.5mm 0 2mm;
}
table.mu-galeria{
  width: 100%;
  border-collapse: collapse;
}
table.mu-galeria tr{
  page-break-inside: avoid;   /* a linha inteira muda de página, nunca parte */
}
table.mu-galeria td{
  border: none;
  padding: 0 <?= MU_PDF_VAO ?>mm <?= MU_PDF_VAO ?>mm 0;
  vertical-align: top;
}
.mu-caixa{
  border: .6pt solid #B9B9B9;
  background-color: #F4F4F4;
  text-align: center;
  overflow: hidden;
}
.mu-legenda{
  font-family: "DejaVu Sans", sans-serif;
  font-size: 6.6pt;
  color: #5A5A5A;
  padding-top: .8mm;
}
.mu-ausente{
  font-family: "DejaVu Sans", sans-serif;
  font-size: 7pt;
  color: #A8A8A8;
}

/* ---- resumo e assinatura ------------------------------------------- */
.mu-resumo{ margin-top: 8mm; }
table.mu-resumo-tab{
  width: 100%;
  border-collapse: collapse;
  font-family: "DejaVu Sans", sans-serif;
  font-size: 8.4pt;
}
table.mu-resumo-tab th, table.mu-resumo-tab td{
  border: .6pt solid #B9B9B9;
  padding: 1.7mm 2.2mm;
}
table.mu-resumo-tab th{
  background-color: #EAF6EF;
  color: #278A52;
  text-transform: uppercase;
  font-size: 7.2pt;
  text-align: left;
}
table.mu-resumo-tab td.num{ text-align: center; }
table.mu-resumo-tab tfoot td{ background-color: #F2F2F2; font-weight: bold; }
p.mu-nota{
  font-family: "DejaVu Sans", sans-serif;
  font-size: 6.8pt;
  color: #5A5A5A;
  margin-top: 1.6mm;
}
p.mu-local-data{ text-align: right; font-size: 9.5pt; margin-top: 10mm; }
.mu-assin{
  page-break-inside: avoid;
  margin-top: 16mm;
  text-align: center;
  font-family: "DejaVu Sans", sans-serif;
  font-size: 8pt;
}
.mu-assin .mu-linha-assin{
  width: 92mm;
  margin: 0 auto 1.4mm;
  border-top: .8pt solid #111111;
  padding-top: 1.4mm;
}
.mu-assin span{ color: #5A5A5A; font-size: 7pt; }
</style>
</head>
<body>

<table class="mu-cab"><tr>
  <td><img src="<?= htmlspecialchars(mu_pdf_asset('assets/img/marco-urbano-logo.png')) ?>" alt=""></td>
  <td class="mu-cab-doc">
    <strong>Relatório de Medição</strong><br>
    Contrato <?= htmlspecialchars($contratoDoc) ?><br>
    <?= htmlspecialchars($municipioDoc) ?>
  </td>
</tr></table>

<table class="mu-rod"><tr>
  <td class="esq"><?= htmlspecialchars(MU_RELATORIO['contratada']) ?> ·
      CNPJ: <?= htmlspecialchars(MU_RELATORIO['cnpj']) ?> ·
      <?= htmlspecialchars(MU_RELATORIO['crea']) ?></td>
  <td class="dir">Contrato <?= htmlspecialchars($contratoDoc) ?></td>
</tr></table>

<h1 class="mu-title"><?= htmlspecialchars(MU_RELATORIO['titulo']) ?></h1>
<p class="mu-subtitle"><?= htmlspecialchars(MU_RELATORIO['subtitulo']) ?></p>

<table class="mu-ident">
  <tr>
    <th>Contratante</th><td><?= htmlspecialchars(MU_RELATORIO['contratante']) ?></td>
    <th>Contrato</th><td><?= htmlspecialchars($contratoDoc) ?></td>
  </tr>
  <tr>
    <th>Contratada</th><td><?= htmlspecialchars(MU_RELATORIO['contratada']) ?></td>
    <th>Município</th><td><?= htmlspecialchars($municipioDoc) ?></td>
  </tr>
  <tr>
    <th>Bacia</th><td><?= htmlspecialchars($listaBacias !== '' ? $listaBacias : '—') ?></td>
    <th>Medições</th><td><?= htmlspecialchars($listaMedicoes !== '' ? $listaMedicoes : '—') ?></td>
  </tr>
  <tr>
    <th>Período</th>
    <td<?= $periodoPendente ? ' class="mu-conf"' : '' ?>><?= htmlspecialchars($periodoTexto) ?></td>
    <th>Emissão</th><td><?= htmlspecialchars($emissao) ?></td>
  </tr>
  <tr>
    <th>Resp. técnico</th>
    <td<?= mu_rel_pendente('resp_tecnico') ? ' class="mu-conf"' : '' ?>><?= htmlspecialchars(mu_rel('resp_tecnico')) ?></td>
    <th>ART</th>
    <td<?= mu_rel_pendente('art') ? ' class="mu-conf"' : '' ?>><?= htmlspecialchars(mu_rel('art')) ?></td>
  </tr>
</table>

<?php foreach ($dados as $d):
    $t = $d['t'];
    $rotuloTrecho = $t['pv_montante'] . ' → ' . $t['pv_jusante'];
?>
<div class="mu-trecho">
  <table class="mu-trecho-head"><tr>
    <td>Trecho <?= htmlspecialchars($rotuloTrecho) ?></td>
    <td class="dir"><?= htmlspecialchars(trim((string) $t['medicao'])) ?></td>
  </tr></table>

  <div class="mu-trecho-body">

    <table class="mu-meta"><tr>
      <td><span class="mu-rot">Município</span><span class="mu-val"><?= htmlspecialchars((string) $t['cidade']) ?></span></td>
      <td><span class="mu-rot">Bacia</span><span class="mu-val"><?= htmlspecialchars((string) $t['bacia']) ?></span></td>
      <td><span class="mu-rot">Tipo de pavimento</span><span class="mu-val"><?= htmlspecialchars(tipo_pavimento_label($t['tipo_pavimento'])) ?></span></td>
    </tr></table>

    <?php if ($d['segmentos']): ?>
    <table class="mu-tab">
      <thead>
        <tr><th style="width:12mm">Seg.</th><th>Comprimento (m)</th><th>Largura (m)</th><th>Área (m²)</th></tr>
      </thead>
      <tbody>
      <?php foreach ($d['segmentos'] as $k => $s): ?>
        <tr>
          <td><?= $k + 1 ?></td>
          <td><?= mu_num($s['comprimento']) ?></td>
          <td><?= mu_num($s['largura']) ?></td>
          <td><?= mu_num($s['area']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="3">Área total do trecho</td>
            <td class="mu-tot-val"><?= mu_num($d['area']) ?> m²</td></tr>
      </tfoot>
    </table>
    <?php endif; ?>

    <?php if (!empty($t['croqui'])): ?>
    <h5 class="mu-figs-tit">Croqui</h5>
    <table class="mu-galeria"><tr>
      <td style="width:50%"><?= mu_pdf_figura(
              mu_pdf_arquivo_imagem($t['croqui'], 880, 'croqui'),
              MU_PDF_CROQUI_W, MU_PDF_CROQUI_H,
              'Croqui 1 — trecho ' . $rotuloTrecho
          ) ?></td>
      <td style="width:50%"></td>
    </tr></table>
    <?php endif; ?>

    <?php if ($d['fotos']): ?>
    <h5 class="mu-figs-tit">Registro fotográfico</h5>
    <table class="mu-galeria">
      <?php $k = 0; foreach (array_chunk($d['fotos'], MU_FOTOS_POR_LINHA) as $linha): ?>
      <tr>
        <?php foreach ($linha as $f):
            $k++;
            $dataFoto = !empty($f['created_at']) ? date('d/m/Y', strtotime($f['created_at'])) : '';
        ?>
        <td style="width:33.33%"><?= mu_pdf_figura(
                mu_pdf_arquivo_imagem($f['arquivo'], 440),
                MU_PDF_FOTO_W, MU_PDF_FOTO_H,
                'Foto ' . $k . ($dataFoto !== '' ? ' — ' . $dataFoto : '')
            ) ?></td>
        <?php endforeach; ?>
        <?php for ($i = count($linha); $i < MU_FOTOS_POR_LINHA; $i++): ?>
        <td style="width:33.33%"></td>
        <?php endfor; ?>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>

  </div>
</div>
<?php endforeach; ?>

<div class="mu-resumo">
  <table class="mu-trecho-head"><tr>
    <td>Resumo consolidado</td>
    <td class="dir"><?= count($dados) ?> trecho<?= count($dados) === 1 ? '' : 's' ?></td>
  </tr></table>
  <div class="mu-trecho-body">
    <table class="mu-resumo-tab">
      <thead>
        <tr><th>Medição</th><th>Tipo de pavimento</th><th class="num">Trechos</th><th class="num">Área reposta (m²)</th></tr>
      </thead>
      <tbody>
      <?php foreach ($resumo as $med => $porTipo): ?>
          <?php ksort($porTipo); foreach ($porTipo as $tipo => $r): ?>
          <tr>
            <td><?= htmlspecialchars((string) $med) ?></td>
            <td><?= htmlspecialchars(tipo_pavimento_label($tipo)) ?></td>
            <td class="num"><?= $r['trechos'] ?></td>
            <td class="num"><?= mu_num($r['area']) ?></td>
          </tr>
          <?php endforeach; ?>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
      <?php ksort($totalPorTipo); foreach ($totalPorTipo as $tipo => $r): ?>
        <tr>
          <td colspan="2">Total <?= htmlspecialchars(tipo_pavimento_label($tipo)) ?></td>
          <td class="num"><?= $r['trechos'] ?></td>
          <td class="num"><?= mu_num($r['area']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tfoot>
    </table>
    <p class="mu-nota">
      Os totais são apresentados <strong>separadamente por tipo de pavimento</strong>,
      por corresponderem a itens distintos da planilha contratual, com preços unitários
      próprios. Não se aplica soma geral entre tipos diferentes.
    </p>
  </div>
</div>

<p class="mu-local-data">
  <?= htmlspecialchars($municipioAssinatura) ?>, <?= htmlspecialchars($emissao) ?>.
</p>

<div class="mu-assin">
  <div class="mu-linha-assin"></div>
  <strong<?= mu_rel_pendente('resp_tecnico') ? ' class="mu-conf"' : '' ?>><?= htmlspecialchars(mu_rel('resp_tecnico', 'Responsável Técnico — a confirmar')) ?></strong><br>
  <span><?= htmlspecialchars(MU_RELATORIO['contratada']) ?><br>
        CNPJ <?= htmlspecialchars(MU_RELATORIO['cnpj']) ?> · <?= htmlspecialchars(MU_RELATORIO['crea']) ?></span>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

/* =====================================================================
   GERAÇÃO
   ---------------------------------------------------------------------
   `isRemoteEnabled` fica DESLIGADO de propósito: as imagens entram por
   caminho de disco, e com o remoto ligado uma URL no documento viraria
   requisição saindo do servidor (SSRF). O `chroot` limita a leitura à
   pasta do app.
   ===================================================================== */
@ini_set('memory_limit', '512M');
@set_time_limit(180);

$opcoes = new \Dompdf\Options();
$opcoes->set('isRemoteEnabled', false);
$opcoes->set('isHtml5ParserEnabled', true);
$opcoes->set('chroot', realpath(__DIR__ . '/..'));
$opcoes->set('defaultMediaType', 'print');
$opcoes->set('defaultPaperSize', 'a4');
$opcoes->set('defaultPaperOrientation', 'portrait');

$dompdf = new \Dompdf\Dompdf($opcoes);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

/* Numeracao so e possivel aqui: o navegador nao expoe counter(page)
   fora das margin boxes do @page, que ele tambem nao implementa. */
$canvas = $dompdf->getCanvas();
$canvas->page_text(
    (210.0 / 2) * 72 / 25.4 - 24,
    (297.0 - 7.5) * 72 / 25.4,
    'Página {PAGE_NUM} de {PAGE_COUNT}',
    $dompdf->getFontMetrics()->getFont('DejaVu Sans'),
    6,
    [0.35, 0.35, 0.35]
);

$nome = 'relatorio-medicao-'
      . preg_replace('/[^A-Za-z0-9]+/', '-', $contratoDoc) . '-'
      . date('Y-m-d') . '.pdf';

$dompdf->stream($nome, ['Attachment' => true]);
