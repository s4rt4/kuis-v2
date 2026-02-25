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
  <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
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
      opacity: 0;
      filter: drop-shadow(0 0 20px rgba(245,200,66,.3));
    }

    /* === CAROUSEL DECORATION === */
    .carousel-wrapper {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .level-viewport {
      position: relative;
      width: clamp(260px, 45vw, 320px);
      height: 300px; /* fixed height for bounding box */
      display: flex;
      justify-content: center;
    }

    .level-card {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: .75rem;
      cursor: pointer;
      text-decoration: none;
      opacity: 0; /* managed by JS */
      pointer-events: none; /* managed by JS */
      transform: translateX(100px); /* managed by JS */
    }
    .level-card.active {
      pointer-events: auto;
    }

    .carousel-btn {
      background: rgba(255, 255, 255, 0.1);
      border: 2px solid rgba(255, 255, 255, 0.2);
      color: var(--gold);
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      cursor: pointer;
      transition: all 0.3s ease;
      backdrop-filter: blur(4px);
      opacity: 0; /* Entrance animation */
      transform: translateY(20px);
    }
    .carousel-btn:hover {
      background: rgba(255, 255, 255, 0.2);
      border-color: var(--gold);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(245, 200, 66, 0.3);
    }
    .carousel-btn:active {
      transform: scale(0.95);
    }

    /* specific shadows */
    .sd .btn-img { filter: drop-shadow(0 8px 30px var(--glow-sd)); }
    .smp .btn-img { filter: drop-shadow(0 8px 30px var(--glow-smp)); }
    .sma .btn-img { filter: drop-shadow(0 8px 30px var(--glow-sma)); }

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

<div class="page-wrap container">
  <div class="row w-100 align-items-center">
    
    <!-- Bagian Kiri: Teks & Animasi -->
    <div class="col-md-6 d-flex flex-column align-items-center text-center mb-5 mb-md-0">
      <h1 class="site-title">✨ Kuis Pintar ✨</h1>
      <p class="site-subtitle">Pilih jenjangmu dan mulai belajar seru!</p>

      <!-- Welcome Lottie (Diperbesar) -->
      <div class="welcome-lottie" id="lottie-welcome" style="width: 250px; height: 250px; margin-bottom: 2rem;"></div>

      <p class="footer-note" id="footer-note" style="margin-top: 1rem;">
        Guru atau Admin? <a href="login.php">Masuk ke Panel →</a>
      </p>
    </div>

    <!-- Bagian Kanan: Carousel -->
    <div class="col-md-6 d-flex justify-content-center">
      <div class="carousel-wrapper" style="margin-bottom: 0;">
        <!-- Prev Arrow -->
        <button class="carousel-btn" id="btn-prev" aria-label="Sebelumnya">
          <i class="fa fa-chevron-left"></i>
        </button>

        <div class="level-viewport">
          <a href="subjects.php?level=sd" class="level-card sd active" data-index="0">
            <img src="assets/png/button_sd.png" alt="SD" class="btn-img" onerror="this.src='assets/img/button_sd.png'">
            <span class="level-label">Sekolah Dasar</span>
          </a>
          <a href="subjects.php?level=smp" class="level-card smp" data-index="1">
            <img src="assets/png/button_smp.png" alt="SMP" class="btn-img" onerror="this.src='assets/img/button_smp.png'">
            <span class="level-label">Sekolah Menengah Pertama</span>
          </a>
          <a href="subjects.php?level=sma" class="level-card sma" data-index="2">
            <img src="assets/png/button_sma.png" alt="SMA" class="btn-img" onerror="this.src='assets/img/button_sma.png'">
            <span class="level-label">Sekolah Menengah Atas</span>
          </a>
        </div>

        <!-- Next Arrow -->
        <button class="carousel-btn" id="btn-next" aria-label="Selanjutnya">
          <i class="fa fa-chevron-right"></i>
        </button>
      </div>
    </div>

  </div>
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
    .to('.carousel-btn',  { opacity:1, y:0, duration:.5, stagger:.1 }, '-=.2')
    .to('#footer-note',   { opacity:1, duration:.4 }, '-=.2');

  const cards = document.querySelectorAll('.level-card');
  let currentIndex = 0;
  let isAnimating = false;

  // Initial setup: position all cards. 0 is center, left is -100px, right is 100px
  gsap.set(cards, { opacity: 0, x: 100, scale: 0.9 });
  // Show the first card immediately after loader
  gsap.to(cards[0], { opacity: 1, x: 0, scale: 1, duration: 0.8, ease: "back.out(1.2)", delay: 0.8 });
  cards[0].classList.add('active');

  function goToSlide(newIndex, direction) {
    if (isAnimating || newIndex === currentIndex) return;
    isAnimating = true;

    const currentCard = cards[currentIndex];
    const nextCard = cards[newIndex];

    // Remove active class to disable clicks during transition
    currentCard.classList.remove('active');
    
    // Animate out current
    const outX = direction === 'next' ? -100 : 100;
    const inX = direction === 'next' ? 100 : -100;

    gsap.to(currentCard, {
      opacity: 0, x: outX, scale: 0.9, duration: 0.5, ease: "power2.inOut"
    });

    // Prepare next card
    gsap.set(nextCard, { opacity: 0, x: inX, scale: 0.9 });
    
    // Animate in next
    gsap.to(nextCard, {
      opacity: 1, x: 0, scale: 1, duration: 0.6, ease: "back.out(1)", delay: 0.1,
      onComplete: () => {
        nextCard.classList.add('active');
        isAnimating = false;
      }
    });

    currentIndex = newIndex;
  }

  document.getElementById('btn-next').addEventListener('click', () => {
    let nextIndex = currentIndex + 1;
    if (nextIndex >= cards.length) nextIndex = 0; // loop
    goToSlide(nextIndex, 'next');
  });

  document.getElementById('btn-prev').addEventListener('click', () => {
    let prevIndex = currentIndex - 1;
    if (prevIndex < 0) prevIndex = cards.length - 1; // loop
    goToSlide(prevIndex, 'prev');
  });

  // Swipe support for mobile
  let touchStartX = 0;
  const viewport = document.querySelector('.level-viewport');
  
  viewport.addEventListener('touchstart', e => {
    touchStartX = e.changedTouches[0].screenX;
  }, {passive: true});

  viewport.addEventListener('touchend', e => {
    if (isAnimating) return;
    const touchEndX = e.changedTouches[0].screenX;
    if (touchEndX < touchStartX - 40) {
      document.getElementById('btn-next').click();
    } else if (touchEndX > touchStartX + 40) {
      document.getElementById('btn-prev').click();
    }
  }, {passive: true});

  /* Hover sound-like visual feedback */
  cards.forEach(card => {
    card.addEventListener('mouseenter', () => {
      if(card.classList.contains('active')) {
        gsap.to(card.querySelector('.btn-img'), {
          scale: 1.08, y: -6, rotation: gsap.utils.random(-3,3), duration:.2, ease:'power1.out'
        });
        gsap.to(card.querySelector('.level-label'), { color: 'var(--gold)', duration: .2 });
      }
    });
    card.addEventListener('mouseleave', () => {
      gsap.to(card.querySelector('.btn-img'), { scale: 1, y: 0, rotation:0, duration:.3, ease:'elastic.out(1,.5)' });
      gsap.to(card.querySelector('.level-label'), { color: 'var(--cream)', duration: .3 });
    });
    card.addEventListener('click', e => {
      if(!card.classList.contains('active')) {
        e.preventDefault();
        return;
      }
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
