# GW Shop — orçamento de camisetas personalizadas

Domínio: **https://gwshop.com.br** · Site independente (PHP 8, sem banco de dados).

## O que tem

| Endereço | O quê |
|---|---|
| `/` | Orçamentador: o cliente escolhe modelo e cor, envia a arte da frente e/ou do verso, posiciona e redimensiona sobre a camiseta, informa as quantidades por tamanho e vê o preço na hora. Ao enviar, o site gera a imagem frente e verso, salva tudo no servidor e abre o WhatsApp com o código do orçamento. |
| `/admin` | Painel: lista os orçamentos, mostra a imagem, os dados do cliente e o cálculo, permite baixar as artes originais, mudar o status e responder pelo WhatsApp. |
| `/loja` | Loja de camisetas prontas (guardada para depois, não está linkada). |

## Publicar

1. No cPanel da hospedagem, adicione o domínio `gwshop.com.br` (Domínios / Domínios adicionais).
   Anote a pasta criada para ele (ex.: `public_html/gwshop.com.br`).
2. No Registro.br, aponte os servidores DNS do domínio para os da hospedagem.
3. Envie **o conteúdo** desta pasta para a pasta do domínio — inclusive os arquivos ocultos
   `.htaccess` e `.user.ini` (no Gerenciador de Arquivos, ative "mostrar arquivos ocultos").
4. Ative o SSL gratuito (AutoSSL / Let's Encrypt) para o domínio.
5. **Logo em seguida**, acesse `https://gwshop.com.br/admin` e crie a senha do painel
   (o primeiro acesso define a senha).
6. A pasta `dados/` precisa permitir escrita pelo PHP (permissão 755 normalmente basta).

## Configurar preços — `api/config.php`

- `loja.whatsapp` → número da loja com DDI + DDD, só dígitos (ex.: `5547999998888`). **Obrigatório.**
- `loja.email` → se preenchido, recebe um aviso a cada orçamento.
- `modelos` → preço da camiseta lisa de cada modelo.
- `cores` → cores disponíveis (nome e código hex).
- `tamanhos` → acréscimo por tamanho (ex.: GG +R$ 4).
- `area` → área máxima de impressão por lado, em cm (padrão 30 × 40).
- `estampa` → faixas de preço por lado. Vale a **menor faixa em que a arte cabe**, em pé ou deitada.
- `descontos` → % de desconto pela quantidade total de peças.

O preço é recalculado no servidor a cada envio, então ninguém consegue alterar o valor pelo navegador.

### Fórmula

```
preço por peça = camiseta + estampa frente + estampa verso + adicional do tamanho
total          = soma de todas as peças − desconto por quantidade
```

## Onde ficam os orçamentos

`dados/orcamentos/<CÓDIGO>/` contém `orcamento.json`, as artes originais (`arte-frente.*`,
`arte-verso.*`) e `mockup.png`. A pasta `dados/` é bloqueada para acesso direto: os arquivos
só são abertos pelo painel com login. Inclua essa pasta no backup da hospedagem.

## Detalhes técnicos

- Artes aceitas: PNG, JPG, WEBP, SVG — até 20 MB (o `api/.user.ini` aumenta o limite de upload do PHP).
- O site avisa o cliente quando a resolução da arte fica abaixo de 150 DPI no tamanho escolhido.
- Campo isca contra robôs no formulário; tipo real dos arquivos conferido no servidor.
