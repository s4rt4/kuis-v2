<?php
// index.php — Halaman Landing Pilih Jenjang
// Tidak perlu koneksi DB di sini
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kuis Pintar — Belajar Seru!</title>
  <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-deep:    #0f1b2d;
      --bg-mid:     #162035;
      --gold:       #f5c842;
      --gold-light: #ffe680;
      --wood:       #7c4a1e;
      --wood-light: #a0622a;
      --cream:      #fff8e7;
      --glow-sd:    #ff6b6b;
      --glow-smp:   #4ecdc4;
      --glow-sma:   #a29bfe;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg-deep);
      min-height: 100vh;
      overflow-x: hidden;
      color: var(--cream);
    }

    /* === STARS BACKGROUND === */
    #stars-canvas {
      position: fixed;
      inset: 0;
      z-index: 0;
      pointer-events: none;
    }

    /* === WRAPPER === */
    .page-wrap {
      position: relative;
      z-index: 1;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
    }

    /* === HEADER === */
    .site-title {
      font-family: 'Fredoka One', cursive;
      font-size: clamp(2.2rem, 6vw, 4rem);
      color: var(--gold);
      text-shadow: 0 0 30px rgba(245,200,66,.5), 0 4px 0 rgba(0,0,0,.4);
      letter-spacing: 2px;
      margin-bottom: .3rem;
      opacity: 0;
    }

    .site-subtitle {
      font-size: clamp(.95rem, 2.5vw, 1.2rem);
      color: rgba(255,248,231,.65);
      margin-bottom: 2.5rem;
      font-weight: 600;
      opacity: 0;
    }

    /* === LOTTIE WELCOME === */
    .welcome-lottie {
      width: 180px;
      height: 180px;
      margin-bottom: 1.5rem;
      opacity: 0;
      filter: drop-shadow(0 0 20px rgba(245,200,66,.3));
    }

    /* === LEVEL BUTTONS === */
    .level-row {
      display: flex;
      gap: 2rem;
      flex-wrap: wrap;
      justify-content: center;
      margin-bottom: 2rem;
    }

    .level-card {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: .75rem;
      cursor: pointer;
      text-decoration: none;
      opacity: 0;
      transform: translateY(40px);
    }

    .level-card:hover .btn-img { transform: scale(1.08) translateY(-6px); }
    .level-card:hover .level-label { color: var(--gold); }

    .btn-img {
      width: clamp(140px, 22vw, 200px);
      height: auto;
      transition: transform .35s cubic-bezier(.34,1.56,.64,1);
      filter: drop-shadow(0 8px 24px rgba(0,0,0,.5));
    }

    /* glows per jenjang */
    .level-card.sd:hover  .btn-img { filter: drop-shadow(0 8px 30px var(--glow-sd)); }
    .level-card.smp:hover .btn-img { filter: drop-shadow(0 8px 30px var(--glow-smp)); }
    .level-card.sma:hover .btn-img { filter: drop-shadow(0 8px 30px var(--glow-sma)); }

    .level-label {
      font-family: 'Fredoka One', cursive;
      font-size: 1.3rem;
      color: var(--cream);
      transition: color .25s;
      letter-spacing: 1px;
    }

    /* === SHELF DECORATION === */
    .shelf-bar {
      width: min(700px, 90vw);
      height: 14px;
      background: linear-gradient(180deg, var(--wood-light), var(--wood));
      border-radius: 4px;
      box-shadow: 0 6px 20px rgba(0,0,0,.5);
      margin-top: .5rem;
      opacity: 0;
    }

    /* === FOOTER NOTE === */
    .footer-note {
      margin-top: 2.5rem;
      font-size: .85rem;
      color: rgba(255,248,231,.3);
      opacity: 0;
    }

    .footer-note a { color: var(--gold); text-decoration: none; }

    /* === FLOATING PARTICLES === */
    .particle {
      position: fixed;
      border-radius: 50%;
      pointer-events: none;
      z-index: 0;
      animation: floatUp linear infinite;
    }
    @keyframes floatUp {
      0%   { transform: translateY(0) rotate(0deg); opacity: .8; }
      100% { transform: translateY(-110vh) rotate(720deg); opacity: 0; }
    }
  </style>
</head>
<body>

<canvas id="stars-canvas"></canvas>

<div class="page-wrap">
  <!-- Welcome Lottie -->
  <div class="welcome-lottie" id="lottie-welcome"></div>

  <h1 class="site-title text-center">✨ Kuis Pintar ✨</h1>
  <p class="site-subtitle text-center">Pilih jenjangmu dan mulai belajar seru!</p>

  <!-- Level Buttons -->
  <div class="level-row">
    <a href="subjects.php?level=SD" class="level-card sd">
      <img src="assets/png/button_sd.png" alt="SD" class="btn-img">
      <span class="level-label">Sekolah Dasar</span>
    </a>
    <a href="subjects.php?level=SMP" class="level-card smp">
      <img src="assets/png/button_smp.png" alt="SMP" class="btn-img">
      <span class="level-label">Sekolah Menengah Pertama</span>
    </a>
    <a href="subjects.php?level=SMA" class="level-card sma">
      <img src="assets/png/button_sma.png" alt="SMA" class="btn-img">
      <span class="level-label">Sekolah Menengah Atas</span>
    </a>
  </div>

  <!-- Shelf decoration -->
  <div class="shelf-bar" id="shelf-bar"></div>

  <p class="footer-note" id="footer-note">
    Guru? <a href="admin.php">Masuk ke Panel Admin →</a>
  </p>
</div>

<!-- GSAP lokal -->
<script src="assets/gsap/gsap.min.js"></script>

<!-- lottie-web player (offline, SVG renderer) -->
<script src="assets/lottie/lottie.min.js"></script>
<script>
  window.addEventListener('DOMContentLoaded', () => {
    lottie.loadAnimation({
      container: document.getElementById('lottie-welcome'),
      renderer: 'svg',
      loop: true,
      autoplay: true,
      path: 'assets/lottie/welcome.json'
    });
  });
</script>

<script>
/* === Stars Canvas === */
(function(){
  const canvas = document.getElementById('stars-canvas');
  const ctx = canvas.getContext('2d');
  let stars = [];
  function resize(){ canvas.width=innerWidth; canvas.height=innerHeight; init(); }
  function init(){
    stars = [];
    for(let i=0;i<160;i++) stars.push({
      x: Math.random()*canvas.width, y: Math.random()*canvas.height,
      r: Math.random()*1.5+.3, o: Math.random(), s: Math.random()*.015+.003
    });
  }
  function draw(){
    ctx.clearRect(0,0,canvas.width,canvas.height);
    stars.forEach(s=>{
      s.o += s.s; if(s.o>1||s.o<0) s.s=-s.s;
      ctx.beginPath(); ctx.arc(s.x,s.y,s.r,0,Math.PI*2);
      ctx.fillStyle=`rgba(255,248,200,${s.o})`; ctx.fill();
    });
    requestAnimationFrame(draw);
  }
  window.addEventListener('resize', resize);
  resize(); draw();
})();

/* === Floating Particles === */
(function(){
  const colors = ['#f5c842','#ff6b6b','#4ecdc4','#a29bfe','#fd79a8'];
  for(let i=0;i<18;i++){
    const p = document.createElement('div');
    p.className = 'particle';
    const size = Math.random()*8+4;
    Object.assign(p.style,{
      width: size+'px', height: size+'px',
      left: Math.random()*100+'vw',
      bottom: '-20px',
      background: colors[Math.floor(Math.random()*colors.length)],
      opacity: Math.random()*.6+.2,
      animationDuration: (Math.random()*12+8)+'s',
      animationDelay: (Math.random()*10)+'s',
    });
    document.body.appendChild(p);
  }
})();

/* === GSAP Entrance === */
window.addEventListener('DOMContentLoaded', ()=>{
  const tl = gsap.timeline({ defaults:{ ease:'power3.out' } });

  tl.to('#lottie-welcome', { opacity:1, duration:.6 })
    .to('.site-title',    { opacity:1, y:0, duration:.7 }, '-=.3')
    .to('.site-subtitle', { opacity:1, duration:.5 }, '-=.4')
    .to('.level-card',    { opacity:1, y:0, duration:.6, stagger:.15 }, '-=.2')
    .to('#shelf-bar',     { opacity:1, duration:.5 }, '-=.3')
    .to('#footer-note',   { opacity:1, duration:.4 }, '-=.2');

  /* Hover sound-like visual feedback */
  document.querySelectorAll('.level-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
      gsap.to(card.querySelector('.btn-img'), {
        rotation: gsap.utils.random(-3,3), duration:.2, ease:'power1.out'
      });
    });
    card.addEventListener('mouseleave', () => {
      gsap.to(card.querySelector('.btn-img'), { rotation:0, duration:.3, ease:'elastic.out(1,.5)' });
    });
    card.addEventListener('click', e => {
      e.preventDefault();
      const href = card.href;
      gsap.to('.page-wrap', {
        opacity:0, scale:.95, duration:.4, ease:'power2.in',
        onComplete: ()=>{ window.location.href = href; }
      });
    });
  });
});
</script>
</body>
</html>
