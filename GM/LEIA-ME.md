# GM SERVIÇOS — instância do cliente

Réplica completa da instância `CHERONCAMARGO`: mesmos apps, mesmas telas e mesmas
funcionalidades, com caminhos, sessão, banco e marca próprios.

## Apps

| App               | URL                        | Nível de acesso |
|-------------------|----------------------------|-----------------|
| Painel/Planejador | `/GM/painel/`              | 1, 3, 4         |
| Visão Executiva   | `/GM/master/`              | 6               |
| App do Executor   | `/GM/executor/`            | 5               |
| Executor Repav.   | `/GM/executor-repav/`      | 7               |
| Topógrafo         | `/GM/topografo/`           | 8               |

## Isolamento

- Base de URL: `/GM/`
- Sessão: `GM_PAINEL`, cookie com `path=/GM/`
- Banco: `u278289683_GM` (usuário `u278289683_GM`)

Configurado em `*/app/config/app.php`, `*/app/config/database.php`, nos `.htaccess`
(`RewriteBase`) e no login centralizado (`login/index.php`, entrada `id => 'gm'`).

## Passos para colocar no ar

1. Criar no painel da hospedagem o banco **`u278289683_GM`** e o usuário de mesmo
   nome, com a senha usada pelos demais sistemas.
2. Rodar `database/estrutura_completa.sql` — cria as 61 tabelas com índices,
   AUTO_INCREMENT e chaves estrangeiras, em uma execução só.
3. Rodar `painel/database/migrations/GM_usuarios_iniciais.sql` para criar os usuários
   iniciais. Senha temporária: **`Gm@2026`** — todos entram com
   `force_password_change = 1` e trocam a senha no primeiro acesso.
4. O deploy (`.github/workflows/deploy.yml`) já copia `principal/GM` para
   `$BASE/GM` e cria as pastas de upload.

### Não use as migrações `PA*` para montar um banco novo

As migrações em `database/migrations/` e `painel/database/migrations/` são
**incrementais** e pressupõem um esquema base que nunca foi versionado neste
repositório. Executadas sozinhas em um banco vazio, criam 41 das 61 tabelas e
quebram com `#1146 - tabela não existe` no primeiro `ALTER TABLE` de uma tabela
ausente. Faltam nelas, entre outras: `usuarios`, `funcionarios`, `equipes`,
`equipamentos_pesados`, `equipamentos_leves`, `planejamentos` e `execucoes`.

Elas continuam válidas como histórico e para aplicar mudanças pontuais em bancos
já existentes — só não servem como ponto de partida.

Se a importação parar no meio e deixar tabelas pela metade, apague todas as tabelas
do banco (phpMyAdmin → selecionar todas → Apagar, ou `DROP DATABASE` e recriar)
antes de rodar `estrutura_completa.sql` de novo — ele não tem `DROP TABLE IF EXISTS`
e vai reclamar de tabela já existente.

## Logotipo

Marca **oficial** do cliente, em dois recortes:

- `painel/assets/img/icon-gm.png` — **compacta**: círculo + `GM` + faixa branca com
  `SERVIÇOS` e a seta. É a que o sistema usa, nos 12 pontos listados abaixo.
- `painel/assets/img/icon-gm-full.png` — **completa**: a mesma marca com a assinatura
  curva `Projetos e Construções` no arco inferior. **Não é referenciada por nenhuma
  tela** (ver "Por que a completa não é usada"); fica aqui como o arquivo da marca
  inteira, para peças em tamanho grande.

Ambos: PNG com canal alfa, 512×512, fundo transparente.

Cores da marca, amostradas da arte: verde-escuro **`#13823D`** (19, 130, 61) e
verde-claro do círculo **`#B8DD97`** (184, 221, 151), este com um leve gradiente.

### Origem: raster, não vetor

O PDF enviado pelo cliente **não é vetorial** — é uma página A4 com um único JPEG
512×512 embutido (zero fontes, um `/DCTDecode`). Não havia vetor para extrair, ao
contrário do que se fez na instância BACIN. O que existe é o bitmap: fundo branco
removido por flood fill a partir das bordas, com feather nas bordas antialiased para
não deixar halo, recortado no bounding box e centralizado em quadrado.

Consequência prática: **512×512 é o teto de resolução**. Ampliar além disso amolece.
Nos tamanhos em que o sistema usa (28–64 px) sobra resolução de sobra, inclusive para
tela retina e para impressão.

### Um arquivo só serve para fundo claro e escuro

Não existe mais variante `-white`, e não precisa: como o fundo é transparente e o
desenho é um disco verde-claro fechado, o mesmo arquivo se resolve sobre branco e
sobre o navy dos cabeçalhos (`#0b1c2d` → `#071422`). Testado a 28, 30, 32, 35, 41, 64
e 96 px sobre os dois fundos.

### Por que a completa não é usada

A escolha entre as duas é por **tamanho de renderização**, não por cor de fundo. Os
tamanhos reais, tirados do CSS, são estes:

| Onde | Regra CSS | Tamanho |
|---|---|---|
| Cabeçalho do executor / repav | `.top img.logo` | 30 px |
| Troca de senha | `.brand img` (inline) | 32 px |
| Sidebar do painel, topo do app, dashboard master | `.brand img` / `header img` | 35 px |
| Cabeçalho dos relatórios impressos | `.marca-area img` | 41 px |
| Capa do relatório fotográfico | `.capa img` | 64 px |
| Seletor de sistemas do superadmin | `.sistema-header img` | 28 px |

O maior é 64 px. A assinatura `Projetos e Construções` só fica legível a partir de
~96 px — a 64 px ela é uma mancha, e a marca completa ainda encolhe o círculo e o
`GM` para abrir espaço para ela. Por isso **os 12 pontos usam a compacta**, inclusive
os relatórios impressos e a tela de troca de senha. O nome completo já vem escrito ao
lado do ícone em todas as telas, então nada se perde.

Se algum dia surgir um ponto de uso grande (capa de proposta, landing, apresentação),
é aí que entra `icon-gm-full.png`.

### Onde o logotipo é referenciado

São **11 referências dentro de `GM/`**, mais uma em `login/index.php` (o ícone do
seletor de sistemas do superadmin), fora deste diretório. Todas apontam para
`icon-gm.png`:

| Arquivo | Tamanho renderizado |
|---|---|
| `painel/alterar-senha.php` | 32 px |
| `painel/app/index.php` | 35 px |
| `painel/app/views/layouts/planejador.php` | 35 px |
| `painel/app/views/caminhamentos/relatorio_materiais.php` | 41 px |
| `painel/app/views/caminhamentos/relatorio_medicao.php` | 41 px |
| `painel/app/views/repavimentacao/relatorio.php` | 41 px |
| `painel/app/views/diarios/relatorio_fotos.php` | 64 px |
| `master/app/views/dashboard.php` | 35 px |
| `executor/app/views/home.php` | 30 px |
| `executor/app/views/diario/preencher.php` | 30 px |
| `executor-repav/app/views/home.php` | 30 px |
| `login/index.php` (fora de `GM/`) | 28 px |

### Peso dos arquivos

Os PNG passaram por `pngquant` (paleta, alfa preservado): 184 KB → **70 KB** na
compacta e 229 KB → **81 KB** na completa. A compacta é carregada em toda página de
todos os apps, inclusive no app do executor, que roda em campo com dados móveis — daí
a preocupação. A diferença visual medida contra o original é de 2–5 níveis de 255 nos
tamanhos de uso e 1,2 de RMS a 512 px: imperceptível.

### Se um dia chegar o vetor

Se o cliente mandar a marca em SVG/AI/EPS de verdade, é só gerar os `.svg` e trocar as
referências nos mesmos 12 pontos da tabela acima — a estrutura não muda. Ganha-se
nitidez em qualquer tamanho e o peso cai para poucos KB.

Para trocar a arte mantendo os nomes dos arquivos: se o navegador insistir no arquivo
antigo, acrescente `?v=2` nas referências (foi o que se fez na instância BACIN).

## Nome exibido

- `APP_CLIENT` em cada `app/config/app.php`: `GM SERVIÇOS`
- `label` / `sub` na entrada `gm` de `login/index.php`: `GM SERVIÇOS` /
  `Projetos e Construções`

A landing page `index.html` reaproveita as imagens Gravitas de `img/`, igual às
instâncias CHERONCAMARGO e BACIN.

## Diferença extra em relação à instância de origem

Nos dois relatórios impressos que mostram um descritivo sob o nome da empresa
(`painel/app/views/caminhamentos/relatorio_medicao.php` e
`painel/app/views/repavimentacao/relatorio.php`), o texto `Saneamento Básico`,
herdado da CHERONCAMARGO, foi trocado por `Projetos e Construções`. É a única
string fixa fora dos `app/config/app.php` que carrega o descritivo do cliente.
