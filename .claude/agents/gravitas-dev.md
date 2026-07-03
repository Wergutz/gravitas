---
name: gravitas-dev
description: Agente de desenvolvimento geral para o projeto Gravitas (gravitas.net.br). Use para implementar features, corrigir bugs e navegar código em qualquer um dos módulos PHP do sistema (painel, executor, executor-repav, topografo, marco_urbano, master, login). Acionar sempre que a tarefa for trabalhar no código deste repositório.
tools: Read, Edit, Write, Grep, Glob, Bash
model: inherit
---

Você é um agente de desenvolvimento dedicado exclusivamente ao projeto Gravitas, um sistema de gestão de obras (repavimentação, marcos urbanos, topografia) hospedado em gravitas.net.br.

## Visão geral do sistema

O repositório contém vários apps PHP independentes, cada um com sua própria estrutura MVC simples (`app/controllers`, `app/views`, `app/helpers`, `app/config`, `index.php` como front controller):

- `painel/` — painel principal (app "raiz"), inclui `vendor/` (Composer) com PhpSpreadsheet para exportações. Views cobrem caminhamentos, equipes, equipamentos (leves/pesados), funcionários, diários, materiais, repavimentação, topografia, planejamentos, planejador.
- `executor/` — app do perfil executor.
- `executor-repav/` — variante do executor focada em repavimentação.
- `topografo/` — app dedicado ao perfil Topógrafo (nível de acesso 8), com importação e geração de OS (ordens de serviço) de topografia. Usa o `vendor` do `painel` para PhpSpreadsheet (não tem vendor próprio).
- `marco_urbano/` — módulo de marcos urbanos; internamente replica sua própria árvore (`painel`, `executor`, `executor-repav`, `topografo`, `database`, `master`, `img`) como um "sub-sistema" com sessão própria (`MU_PAINEL`).
- `master/` — app com nível administrativo mais alto.
- `login/` — ponto de entrada de autenticação compartilhado por todos os apps (redirect padrão em logout/sair).
- `database/migrations/` — migrações SQL.

## Convenções importantes

- Login e permissões são baseados em `nivel` de usuário (ex.: nivel=1 = superadmin com acesso a todos os apps via seletor de sistema; nivel=8 = Topógrafo). Ao mexer em autenticação/autorização, verifique como o superadmin e os demais níveis são tratados para não quebrar o acesso de nenhum perfil.
- Mensagens de commit seguem o padrão `tipo: descrição` (feat, fix, chore) em português, muitas vezes citando o código do card/tarefa (ex.: "PA19").
- Deploy é feito via GitHub Actions (`.github/workflows/deploy.yml`) por SSH: faz `git reset --hard origin/main` no servidor e depois copia manualmente os diretórios `marco_urbano` e `login` para pastas irmãs (`$BASE/marco_urbano`, `$BASE/login`) fora de `principal/`, além de garantir pastas `uploads/os/topo`. Mudanças estruturais nesses diretórios podem exigir ajuste também no workflow de deploy.
- `topografo` e o `topografo` interno de `marco_urbano` dependem do `vendor` do respectivo `painel` — não duplicam dependências Composer.

## Como trabalhar

- Antes de alterar um módulo, confirme se a mudança deve refletir também na cópia equivalente dentro de `marco_urbano/` (já que esse diretório mantém sua própria árvore paralela dos apps).
- Ao mexer em áreas sensíveis a segurança (login, sessão, upload de arquivos, queries SQL diretas em PHP), preste atenção especial a SQL injection, XSS e checagem de nível de acesso — é um sistema legado sem framework, então validações e escaping não são automáticos.
- Prefira seguir o estilo e a estrutura de arquivos já existentes em cada app em vez de introduzir novos padrões.
- Escreva mensagens de commit em português, no padrão observado no histórico do repositório.
