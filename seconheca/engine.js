/* Motor compartilhado dos testes do Se Conheça.
   Cada página define: ARCH (perfis), Q (perguntas) e TESTE (chave de config). */

const CONFIGS = {
  arquetipo:{
    titlePrefix:"O ", dominant:"seu arquétipo dominante", scoreCount:6,
    ebookKicker:"Vá mais fundo",
    ebookDesc:"Um guia completo sobre o seu arquétipo: a raiz antiga dele, como molda o seu amor, trabalho e escolhas, e o caminho para integrar a sua sombra.",
    foot:"Você não <i>é</i> um arquétipo — você os contém todos. Este só mostra qual está conduzindo agora. O trabalho interessante, dizia Jung, é justamente integrar a sombra dos que ficaram para trás."
  },
  filho:{
    titlePrefix:"", dominant:"o seu papel como filho(a)", scoreCount:8,
    ebookKicker:"Vá mais fundo",
    ebookDesc:"Um guia sobre o seu jeito de ser filho(a): de onde vem esse papel, como ele te marcou, e como amar a sua família (e a si mesmo) com mais leveza.",
    foot:"Nenhum papel é definitivo. Conhecer o seu é o primeiro passo para escolher, com consciência, como você quer estar na sua família."
  },
  mae:{
    titlePrefix:"A ", dominant:"a sua forma de maternar", scoreCount:8,
    ebookKicker:"Vá mais fundo",
    ebookDesc:"Um guia sobre o seu jeito de ser mãe: a força que ele carrega, o ponto de atenção a cuidar, e como maternar sem se perder de si.",
    foot:"Não existe mãe perfeita — existe a mãe presente. Este retrato celebra a sua forma de amar e ilumina, com carinho, o que pede cuidado."
  }
};

const CFG = CONFIGS[TESTE];
const app = document.getElementById('app');
let i = 0;
const scores = {};
Object.keys(ARCH).forEach(k=>scores[k]=0);
const answers = [];

function titulo(k){ return ARCH[k].titulo || (CFG.titlePrefix + ARCH[k].nome); }

function render(){
  const total = Q.length;
  const pct = Math.round((i/total)*100);
  const cur = Q[i];
  app.innerHTML = `
    <div class="progress">
      <div class="progress-top"><span>Pergunta ${i+1} de ${total}</span><span>${pct}%</span></div>
      <div class="bar"><i style="width:${pct}%"></i></div>
    </div>
    <div class="card" key="${i}">
      <div class="qnum">${String(i+1).padStart(2,'0')}</div>
      <div class="qtext">${cur.q}</div>
      <div id="opts"></div>
      <button class="back" id="back" ${i===0?'disabled':''}>← voltar</button>
    </div>`;
  const opts = document.getElementById('opts');
  const marks = ['I','II','III','IV','V'];
  cur.o.forEach((opt,idx)=>{
    const b = document.createElement('button');
    b.className='opt';
    b.innerHTML = `<span class="mk">${marks[idx]}</span>${opt[0]}`;
    b.onclick = ()=>choose(opt[1]);
    opts.appendChild(b);
  });
  document.getElementById('back').onclick = back;
}

function choose(key){
  scores[key]++; answers[i]=key; i++;
  if(i>=Q.length) showResult(); else render();
  window.scrollTo({top:0,behavior:'smooth'});
}
function back(){
  if(i===0) return;
  i--; if(answers[i]) scores[answers[i]]--;
  render(); window.scrollTo({top:0,behavior:'smooth'});
}

function showResult(){
  const ranked = Object.keys(scores).sort((a,b)=>scores[b]-scores[a]);
  const top = ranked[0], second = ranked[1];
  const A = ARCH[top], B = ARCH[second];
  const disc = document.getElementById('disc'); if(disc) disc.style.display='none';
  const maxOpts = Q.length;

  const scoreRows = ranked.slice(0,CFG.scoreCount).map(k=>{
    const w = Math.round((scores[k]/maxOpts)*100);
    return `<div class="srow">
      <div class="slabel">${ARCH[k].nome}</div>
      <div class="strack"><div class="sfill" data-w="${w}"></div></div>
      <div class="sval">${scores[k]}</div>
    </div>`;
  }).join('');

  app.innerHTML = `
    <div class="rule"><span>✦ ✦ ✦</span></div>
    <div class="result">
      <div class="crest">${A.crest}</div>
      <div class="rtag">${CFG.dominant}</div>
      <div class="rname">${titulo(top)}</div>
      <div class="rtag">${A.tag}</div>
      <p class="rbody">${A.desc}</p>
      <div class="facet light"><h4>A luz</h4>${A.luz}</div>
      <div class="facet shadow"><h4>A sombra</h4>${A.sombra}</div>
      <p class="second">Logo atrás vem o seu segundo perfil: <b>${titulo(second)}</b> (${B.tag}). Os dois juntos descrevem você melhor do que qualquer rótulo isolado.</p>

      <div class="sofia-note">
        <img src="img/simbolo-dona-sofia.svg" alt="Dona Sofia">
        <div><div class="n">DONA SOFIA</div><p>${A.sofia || "Que bom te conhecer um pouco mais hoje. Volte sempre — você tem muito ainda a descobrir sobre você."}</p></div>
      </div>

      <div class="rule"><span>seu mapa completo</span></div>
      <div class="scores">${scoreRows}</div>

      <div class="ebook">
        <div class="ic">📖</div>
        <div class="rtag" style="color:#E3C77A">${CFG.ebookKicker}</div>
        <h4>O seu Guia — ${titulo(top)}</h4>
        <p>${CFG.ebookDesc}</p>
        <button class="btn" id="ebtn">Quero meu guia</button>
        <div class="soon">E-book em preparação — em breve disponível.</div>
      </div>

      <button class="btn" id="again">↺ Refazer o teste</button>
      <a class="home" href="index.html">✦ voltar ao início · outros testes</a>
      <p class="foot">${CFG.foot}</p>
    </div>`;

  document.getElementById('again').onclick = ()=>{
    i=0; Object.keys(scores).forEach(k=>scores[k]=0); answers.length=0;
    if(disc) disc.style.display='';
    render(); window.scrollTo({top:0,behavior:'smooth'});
  };
  const eb=document.getElementById('ebtn');
  if(eb) eb.onclick=()=>{ eb.textContent="Avise-me quando sair ✓"; eb.disabled=true; };

  requestAnimationFrame(()=>{
    document.querySelectorAll('.sfill').forEach(el=>{el.style.width=el.dataset.w+'%';});
  });
  window.scrollTo({top:0,behavior:'smooth'});
}

render();
