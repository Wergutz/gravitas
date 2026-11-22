/*
 * Catálogo da loja. Para adicionar/editar produtos, altere apenas este arquivo.
 *  - preco / precoAntigo em reais
 *  - cores: { nome, hex } — a camiseta é desenhada em SVG nessa cor
 *  - estampa: texto/forma desenhada sobre a camiseta (ou use "imagem": "img/arquivo.jpg")
 */
const CONFIG = {
  whatsapp: '5500000000000',   // número da loja com DDI + DDD, só dígitos
  freteGratisAcima: 199,
  freteFixo: 19.9,
  descontoPix: 0.05,
};

const PRODUTOS = [
  {
    id: 'sol-de-quinta', nome: 'Sol de Quinta', categoria: 'Estampadas', preco: 89.9, precoAntigo: 109.9, destaque: 1,
    descricao: 'Sol geométrico em serigrafia à base d’água. Toque macio, não racha.',
    cores: [{ nome: 'Off-white', hex: '#f2ede3' }, { nome: 'Preto', hex: '#1d1d1f' }, { nome: 'Areia', hex: '#d8c3a0' }],
    estampa: { tipo: 'sol', cor: '#e2572b' },
  },
  {
    id: 'basica-preta', nome: 'Básica Essencial', categoria: 'Básicas', preco: 59.9, destaque: 2,
    descricao: 'A camiseta que você usa toda semana. Gola reforçada e caimento reto.',
    cores: [{ nome: 'Preto', hex: '#1d1d1f' }, { nome: 'Branco', hex: '#fafafa' }, { nome: 'Mescla', hex: '#a9aaad' }, { nome: 'Marinho', hex: '#22304a' }],
    estampa: null,
  },
  {
    id: 'onda-alta', nome: 'Onda Alta', categoria: 'Estampadas', preco: 89.9, destaque: 3,
    descricao: 'Ondas em três camadas inspiradas no litoral sul.',
    cores: [{ nome: 'Branco', hex: '#fafafa' }, { nome: 'Azul claro', hex: '#b9d3e6' }],
    estampa: { tipo: 'ondas', cor: '#1f5fa8' },
  },
  {
    id: 'cafe-forte', nome: 'Café Forte', categoria: 'Frases', preco: 79.9, destaque: 4,
    descricao: 'Para quem só funciona depois do primeiro café.',
    cores: [{ nome: 'Areia', hex: '#d8c3a0' }, { nome: 'Preto', hex: '#1d1d1f' }],
    estampa: { tipo: 'texto', texto: 'CAFÉ\nFORTE', cor: '#5a3418', corEscura: '#e8c9a0' },
  },
  {
    id: 'oversized-verde', nome: 'Oversized Musgo', categoria: 'Oversized', preco: 119.9, destaque: 5,
    descricao: 'Modelagem ampla, ombro caído e malha 200 g/m² mais encorpada.',
    cores: [{ nome: 'Musgo', hex: '#5d6b45' }, { nome: 'Preto', hex: '#1d1d1f' }, { nome: 'Off-white', hex: '#f2ede3' }],
    estampa: { tipo: 'peito', texto: 'GW SHOP', cor: '#f2ede3', corClara: '#1d1d1f' },
  },
  {
    id: 'montanha', nome: 'Serra Acima', categoria: 'Estampadas', preco: 89.9, precoAntigo: 99.9, destaque: 6,
    descricao: 'Picos em linha contínua. Para quem prefere a trilha ao asfalto.',
    cores: [{ nome: 'Mescla', hex: '#a9aaad' }, { nome: 'Off-white', hex: '#f2ede3' }],
    estampa: { tipo: 'montanha', cor: '#22304a' },
  },
  {
    id: 'nao-hoje', nome: 'Hoje Não', categoria: 'Frases', preco: 79.9, destaque: 7,
    descricao: 'Resposta educada para quase tudo.',
    cores: [{ nome: 'Branco', hex: '#fafafa' }, { nome: 'Rosa', hex: '#efc1c4' }],
    estampa: { tipo: 'texto', texto: 'HOJE\nNÃO.', cor: '#1d1d1f' },
  },
  {
    id: 'oversized-terra', nome: 'Oversized Terracota', categoria: 'Oversized', preco: 119.9, destaque: 8,
    descricao: 'Tingimento natural em tom terroso. Cada peça tem leve variação de cor.',
    cores: [{ nome: 'Terracota', hex: '#b5583a' }, { nome: 'Areia', hex: '#d8c3a0' }],
    estampa: null,
  },
];

const TAMANHOS = ['P', 'M', 'G', 'GG', 'XG'];
