# Contatos com Prefeituras

Sistema de acompanhamento da prospecção junto a prefeituras: quem é quem em
cada cidade, com quem já falamos, o que ficou combinado e o que vem pela
frente. Usado pela equipe da Gravitas.

No ar em `gravitas.net.br/contatos/`.

## Como está montado

```
contatos/
  index.html        a página; o sistema inteiro é uma tela só
  js/app.js         a interface (não conhece banco nenhum)
  js/store.js       a camada de dados: MysqlStore (uso normal) e DemoStore
  js/config.js      só escolhe entre os dois modos
  api/index.php     a API; é o único ponto que fala com o banco
  app/bd.php        conexão e sessão
  app/config/db.exemplo.php  modelo; copiar para db.php no servidor
  sql/              os scripts de criação, na ordem de execução
  fotos/            retratos usados nas fichas
```

O navegador nunca fala com o banco: fala com `api/`, e só o PHP fala com o
MySQL. É por isso que as permissões vivem em `api/index.php`, e não no banco.

## Instalação

1. Criar um banco MySQL no hPanel (é o banco do site, separado do banco das
   obras).
2. No servidor, copiar `app/config/db.exemplo.php` para `app/config/db.php`
   e preencher. Esse arquivo **não** está no repositório — senha em Git fica
   no histórico para sempre. Ele é criado uma vez e sobrevive às publicações:
   o deploy usa `git reset --hard`, que não apaga arquivo fora do repositório,
   e a cópia para `/contatos/` o leva junto.
3. No phpMyAdmin, rodar na ordem:
   `sql/01-schema.sql` → `sql/02-usuarios.sql` → `sql/03-seed.sql`.

Enquanto o passo 2 não for feito, a tela de entrada aparece normalmente e
avisa, ao tentar entrar, o que falta — e a mensagem muda conforme o passo:
falta o db.php, ou falta rodar os scripts.

## Permissões

As mesmas regras que a versão anterior tinha no banco, agora no PHP:

| Regra | Onde |
|---|---|
| Sem login, nada | `ct_exige_login()` em toda ação, menos entrar e consultar sessão |
| A equipe lê e escreve cidades, pessoas, interações e agendas | sem filtro por pessoa |
| Cada um só altera o próprio perfil e a própria senha | a ação usa o id da sessão e ignora o que vier no pedido |
| Só o autor apaga a própria interação ou o próprio compromisso | `WHERE ... AND usuario_id = ?` |

Cada tabela tem uma lista de colunas graváveis em `CT_CAMPOS`; o que não
estiver nela não entra no banco, mesmo que venha no pedido.

## Tempo real

A tela pergunta de 15 em 15 segundos se algo mudou, comparando uma impressão
digital barata (contagem de linhas e data da última alteração, com
milissegundos). Só quando ela muda é que os dados são recarregados e
comparados com os anteriores, gerando os eventos que a interface aplica.
"Quem está online" é quem bateu ponto nos últimos 90 segundos.

Não é instantâneo como era com o serviço externo, mas não depende de
ninguém de fora.

## Modo demonstração

`js/config.js` com `MODO: "demo"` abre o sistema com dados locais, só no
navegador de quem abriu. Serve para mostrar a ferramenta sem tocar no banco.
O arquivo de dados fictícios não é publicado — no modo demonstração o
sistema abre vazio.
