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

Marca redesenhada em vetor a partir da arte enviada pelo cliente:

- `painel/assets/img/icon-gm.svg` — fundo claro (relatórios impressos, troca de senha)
- `painel/assets/img/icon-gm-white.svg` — fundo escuro (cabeçalhos navy dos apps)

Cores da marca: verde-escuro **`#1E8A3E`**, verde-claro **`#A9D18E`**.

### O que ficou de fora, e por quê

O ícone é renderizado a **32–41 px** em todos os pontos onde aparece
(`.brand img` 32–35 px nos cabeçalhos, `.marca-area img` 41 px nos relatórios).
Nesse tamanho, sobram cerca de 4 px de altura para uma linha de texto — ou seja,
um borrão. Por isso a arte foi reduzida ao essencial:

- **`SERVIÇOS`**, que na marca original vai escrito dentro da faixa diagonal, saiu.
  Ficou só o gesto da faixa com a ponta de seta.
- **`Projetos e Construções`**, o texto curvado no arco inferior do círculo, saiu
  pelo mesmo motivo.

O nome completo já é escrito ao lado do ícone em todas as telas, então nada se perde.
Sobram o disco, o monograma **GM** e a faixa-seta — legíveis a 32 px.

### Diferença entre as duas versões

A versão para fundo escuro não é só "o verde vira branco": o disco verde-claro vira
um **anel** (contorno, miolo transparente) e a faixa fica com preenchimento
transparente. Sem isso, o monograma branco cairia sobre o verde-claro `#A9D18E` —
contraste de ~1,7:1, que some a 32 px. Com o miolo vazado, o fundo escuro atravessa,
o branco lê perfeitamente e o anel mantém a cor da marca presente. Nada de retângulo
ou fundo chapado: a marca funciona sobre o gradiente da sidebar
(`#0b1c2d` → `#071422`) e sobre qualquer outro fundo escuro.

### Onde o logotipo é referenciado

São **11 referências dentro de `GM/`**, mais uma em `login/index.php` (o ícone do
seletor de sistemas do superadmin), fora deste diretório:

| Arquivo | Versão |
|---|---|
| `painel/alterar-senha.php` | clara |
| `painel/app/views/caminhamentos/relatorio_materiais.php` | clara |
| `painel/app/views/caminhamentos/relatorio_medicao.php` | clara |
| `painel/app/views/repavimentacao/relatorio.php` | clara |
| `painel/app/index.php` | escura |
| `painel/app/views/layouts/planejador.php` | escura |
| `painel/app/views/diarios/relatorio_fotos.php` | escura |
| `master/app/views/dashboard.php` | escura |
| `executor/app/views/home.php` | escura |
| `executor/app/views/diario/preencher.php` | escura |
| `executor-repav/app/views/home.php` | escura |
| `login/index.php` (fora de `GM/`) | clara |

Para trocar a arte, basta substituir os dois `.svg` mantendo os nomes. Se o cliente
mandar a marca em alta e o navegador insistir no arquivo antigo, acrescente `?v=2`
nas referências (foi o que se fez na instância BACIN).

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
