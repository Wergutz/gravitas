# BACIN — instância do cliente

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

## Pendências de identidade visual

Os logotipos são **provisórios**: `painel/assets/img/icon-bacin.svg` (fundo claro) e
`painel/assets/img/icon-bacin-white.svg` (fundo escuro), um monograma na paleta do
sistema. Basta substituir esses dois arquivos pela marca definitiva — todas as telas
já apontam para eles. Se a marca vier em PNG, trocar a extensão nas referências
(`grep -rn icon-bacin BACIN`).

Nome exibido e subtítulo do seletor de login estão em:

- `APP_CLIENT` em cada `app/config/app.php` (atualmente `BACIN`)
- `label` / `sub` na entrada `bacin` de `login/index.php`
  (atualmente `BACIN` / `Terraplanagem, Saneamento e Pavimentação`)

A landing page `index.html` reaproveita as imagens Gravitas de `img/`, igual à
instância CHERONCAMARGO.
