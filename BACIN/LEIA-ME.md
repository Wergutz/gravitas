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
2. Rodar as migrações no banco novo, nesta ordem:
   - `painel/database/migrations/PA4_fase1_modelo_dados.sql`
   - `painel/database/migrations/PA4_fase1_fk_patch.sql`
   - `painel/database/migrations/PA5_fase1_modelo_dados.sql`
   - `painel/database/migrations/PA5_fase3_integracoes.sql`
   - `database/migrations/PA7_repavimentacao.sql`
   - `database/migrations/PA9_auditoria.sql`
   - `database/migrations/PA10_trecho_materiais.sql`
   - `database/migrations/PA11_importacao.sql`
   - `database/migrations/PA12_equipamentos_manutencao.sql`
   - `database/migrations/PA13_fix_funcionario_documentos.sql`
   - `painel/database/migrations/PA19_topografo.sql`
3. Rodar `painel/database/migrations/BC_usuarios_iniciais.sql` para criar os usuários
   iniciais. Senha temporária: **`Bacin@2026`** — todos entram com
   `force_password_change = 1` e trocam a senha no primeiro acesso.
4. O deploy (`.github/workflows/deploy.yml`) já copia `principal/BACIN` para
   `$BASE/BACIN` e cria as pastas de upload.

## Pendência: logotipo oficial

Os arquivos de marca hoje são **provisórios** — uma aproximação geométrica nas cores
da empresa (hexágono preto `#231F20` + vermelho `#E1251B`), **não** a arte oficial:

- `painel/assets/img/icon-bacin.svg` — fundo claro (relatórios impressos)
- `painel/assets/img/icon-bacin-white.svg` — fundo escuro (cabeçalhos navy)

Para colocar a marca definitiva, salvar a arte nesses dois caminhos:

- versão colorida sobre fundo claro → `icon-bacin.png`
- versão para fundo escuro (traço em branco, vermelho preservado) → `icon-bacin-white.png`

e trocar a extensão nas 11 referências: `grep -rln icon-bacin BACIN | xargs sed -i
's/icon-bacin\.svg/icon-bacin.png/g; s/icon-bacin-white\.svg/icon-bacin-white.png/g'`
— sem esquecer a referência em `login/index.php`, fora deste diretório.

Recomendado: PNG quadrado com fundo transparente, 800×800, só o símbolo do hexágono
(sem o texto "BACIN TERRAPLANAGEM" nem o telefone), porque o nome já é escrito ao lado
do ícone nos cabeçalhos e ficaria duplicado.

Nome exibido e subtítulo do seletor de login estão em:

- `APP_CLIENT` em cada `app/config/app.php` (atualmente `BACIN TERRAPLANAGEM`)
- `label` / `sub` na entrada `bacin` de `login/index.php`
  (atualmente `BACIN` / `Terraplanagem`)

A landing page `index.html` reaproveita as imagens Gravitas de `img/`, igual à
instância CHERONCAMARGO.
