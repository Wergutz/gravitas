# GW Shop — loja virtual de camisetas

Domínio: **https://gwshop.com.br**

Site estático (HTML + CSS + JS puro), **independente do restante do Gravitas**.
Não precisa de PHP nem de banco de dados: funciona em qualquer hospedagem.

## Publicar no novo domínio

1. Domínio `gwshop.com.br` já registrado no Registro.br. Contrate a hospedagem
   (qualquer plano simples serve) e, no Registro.br, aponte os **servidores DNS**
   do domínio para os que a hospedagem informar. A propagação leva de minutos a algumas horas.
2. Envie **o conteúdo** desta pasta (`index.html`, `css/`, `js/`, `img/`) para a raiz
   pública do domínio (normalmente `public_html/` ou `www/`).
3. Ative o SSL (HTTPS) no painel da hospedagem.

## Antes de publicar

Edite `js/produtos.js`:

- `CONFIG.whatsapp` → número da loja com DDI + DDD, só dígitos (ex.: `5547999998888`).
- `CONFIG.freteGratisAcima`, `CONFIG.freteFixo`, `CONFIG.descontoPix`.
- `PRODUTOS` → nomes, preços, cores e descrições.
  - Sem `imagem`, a camiseta é desenhada automaticamente na cor escolhida.
  - Para usar foto real: coloque o arquivo em `img/` e adicione `imagem: 'img/arquivo.jpg'` ao produto.

Em `index.html`, confira o e-mail de contato (`contato@gwshop.com.br`, que precisa ser
criado na hospedagem) e a tabela de medidas.

## Como funciona o pedido

O carrinho fica salvo no navegador do cliente. No checkout o CEP é preenchido
automaticamente (ViaCEP) e o pedido é enviado para o WhatsApp da loja, com itens,
total, forma de pagamento e endereço. A cobrança (Pix/cartão) é combinada por lá.

Próximo passo possível: integrar pagamento online (Mercado Pago, PagSeguro ou Stripe),
o que exige um pequeno back-end.
