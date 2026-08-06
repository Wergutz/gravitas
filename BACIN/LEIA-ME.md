# BACIN TERRAPLANAGEM — instância do cliente

Réplica completa da instância `CHERONCAMARGO`: mesmos apps, mesmas telas e mesmas
funcionalidades, com caminhos, sessão, banco e marca próprios.

## Apps

| App               | URL                        | Nível de acesso |
|-------------------|----------------------------|-----------------|
| Painel/Planejador | `/BACIN/painel/`           | 1, 3, 4         |
| Visão Executiva   | `/BACIN/master/`           | 6               |
| App do Executor   | `/BACIN/executor/`         | 5               |
| Executor Repav.   | `/BACIN/executor-repav/`   | 7               |
| Topógrafo         | `/BACIN/topografo/`        | 8               |

## Isolamento

- Base de URL: `/BACIN/`
- Sessão: `BC_PAINEL`, cookie com `path=/BACIN/`
- Banco: `u278289683_BACIN` (usuário `u278289683_BACIN`)

Configurado em `*/app/config/app.php`, `*/app/config/database.php`, nos `.htaccess`
(`RewriteBase`) e no login centralizado (`login/index.php`, entrada `id => 'bacin'`).

## Passos para colocar no ar

1. Criar no painel da hospedagem o banco **`u278289683_BACIN`** e o usuário de mesmo
   nome, com a senha usada pelos demais sistemas.
2. Rodar `database/estrutura_completa.sql` — cria as 61 tabelas com índices,
   AUTO_INCREMENT e chaves estrangeiras, em uma execução só.
3. Rodar `painel/database/migrations/BC_usuarios_iniciais.sql` para criar os usuários
   iniciais. Senha temporária: **`Bacin@2026`** — todos entram com
   `force_password_change = 1` e trocam a senha no primeiro acesso.
4. O deploy (`.github/workflows/deploy.yml`) já copia `principal/BACIN` para
   `$BASE/BACIN` e cria as pastas de upload.

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

Marca oficial, em vetor, extraída do PDF fornecido pelo cliente:

- `painel/assets/img/icon-bacin.svg` — fundo claro (relatórios impressos, troca de senha)
- `painel/assets/img/icon-bacin-white.svg` — fundo escuro (cabeçalhos navy dos apps)

Cores da marca: vermelho **`#d9251b`**, preto **`#1d1917`**.

As duas versões saem do mesmo vetor. Na versão para fundo escuro o preto vira branco e
os vazados do desenho (a caçamba) ficam **transparentes**, em vez de preenchidos com
uma cor sólida — assim a marca funciona sobre o gradiente da sidebar
(`#0b1c2d` → `#071422`) e sobre qualquer outro fundo escuro, sem virar um retângulo
de cor chapada.

O arquivo traz só o símbolo do hexágono, sem o texto "BACIN TERRAPLANAGEM" e sem o
telefone, porque o nome já é escrito ao lado do ícone nos cabeçalhos — e a esse
tamanho (30–35 px) o telefone seria ilegível.

São 11 referências dentro de `BACIN/`, mais uma em `login/index.php` (o ícone do
seletor de sistemas do superadmin), fora deste diretório. Para trocar a arte de novo,
basta substituir os dois `.svg` mantendo os nomes.

Nome exibido e subtítulo do seletor de login estão em:

- `APP_CLIENT` em cada `app/config/app.php` (atualmente `BACIN TERRAPLANAGEM`)
- `label` / `sub` na entrada `bacin` de `login/index.php`
  (atualmente `BACIN` / `Terraplanagem`)

A landing page `index.html` reaproveita as imagens Gravitas de `img/`, igual à
instância CHERONCAMARGO.
