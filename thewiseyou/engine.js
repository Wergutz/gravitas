/* Shared test engine for The Wise You.
   Each page defines: ARCH (profiles), Q (questions) and TESTE (config key). */

const CONFIGS = {
  archetype:{
    titlePrefix:"The ", dominant:"your dominant archetype", scoreCount:6,
    ebookKicker:"Go deeper",
    ebookDesc:"A full guide to your archetype: its ancient root, how it shapes your love, work, and choices, and the path to integrating your shadow.",
    foot:"You <i>are</i> not one archetype — you contain them all. This only shows which one is leading right now. The interesting work, Jung said, is precisely integrating the shadow of the ones left behind."
  },
  child:{
    titlePrefix:"", dominant:"your role as a son or daughter", scoreCount:8,
    ebookKicker:"Go deeper",
    ebookDesc:"A guide to your way of being a son or daughter: where this role comes from, how it shaped you, and how to love your family (and yourself) more lightly.",
    foot:"No role is final. Knowing yours is the first step to choosing, consciously, how you want to show up in your family."
  },
  mother:{
    titlePrefix:"The ", dominant:"your way of mothering", scoreCount:8,
    ebookKicker:"Go deeper",
    ebookDesc:"A guide to your way of being a mother: the strength it carries, the place that asks for care, and how to mother without losing yourself.",
    foot:"There's no perfect mother — there's the present mother. This portrait celebrates your way of loving and gently lights up what asks for care."
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
      <div class="progress-top"><span>Question ${i+1} of ${total}</span><span>${pct}%</span></div>
      <div class="bar"><i style="width:${pct}%"></i></div>
    </div>
    <div class="card" key="${i}">
      <div class="qnum">${String(i+1).padStart(2,'0')}</div>
      <div class="qtext">${cur.q}</div>
      <div id="opts"></div>
      <button class="back" id="back" ${i===0?'disabled':''}>← back</button>
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
      <div class="facet light"><h4>The light</h4>${A.luz}</div>
      <div class="facet shadow"><h4>The shadow</h4>${A.sombra}</div>
      <p class="second">Just behind comes your second profile: <b>${titulo(second)}</b> (${B.tag}). Together, the two describe you better than any single label.</p>

      <div class="sofia-note">
        <img src="img/sophia-symbol.svg" alt="Miss Sophia">
        <div><div class="n">MISS SOPHIA</div><p>${A.sofia || "It was a joy to know you a little better today. Come back soon — there's still so much of you to discover."}</p></div>
      </div>

      <div class="rule"><span>your full map</span></div>
      <div class="scores">${scoreRows}</div>

      <div class="ebook">
        <div class="ic">📖</div>
        <div class="rtag" style="color:#E3C77A">${CFG.ebookKicker}</div>
        <h4>Your Guide — ${titulo(top)}</h4>
        <p>${CFG.ebookDesc}</p>
        <button class="btn" id="ebtn">I want my guide</button>
        <div class="soon">E-book in the works — coming soon.</div>
      </div>

      <button class="btn" id="again">↺ Retake the test</button>
      <a class="home" href="index.html">✦ back to start · more tests</a>
      <p class="foot">${CFG.foot}</p>
    </div>`;

  document.getElementById('again').onclick = ()=>{
    i=0; Object.keys(scores).forEach(k=>scores[k]=0); answers.length=0;
    if(disc) disc.style.display='';
    render(); window.scrollTo({top:0,behavior:'smooth'});
  };
  const eb=document.getElementById('ebtn');
  if(eb) eb.onclick=()=>{ eb.textContent="Notify me when it's out ✓"; eb.disabled=true; };

  requestAnimationFrame(()=>{
    document.querySelectorAll('.sfill').forEach(el=>{el.style.width=el.dataset.w+'%';});
  });
  window.scrollTo({top:0,behavior:'smooth'});
}

render();
