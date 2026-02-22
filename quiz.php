<?php
// quiz.php — Halaman Kuis Interaktif
require 'config.php';

$packageId = intval($_GET['package'] ?? 0);
if (!$packageId) { header("Location: index.php"); exit; }

// Ambil info paket
$stmtPkg = $db->prepare("
    SELECT p.*, c.name AS cat_name, c.color AS cat_color, c.level
    FROM packages p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.id = :id AND p.is_active = 1
");
$stmtPkg->execute([':id' => $packageId]);
$package = $stmtPkg->fetch();
if (!$package) { header("Location: index.php"); exit; }

// Ambil soal
$stmtQ = $db->prepare("
    SELECT * FROM questions WHERE package_id = :pid ORDER BY sort_order, id
");
$stmtQ->execute([':pid' => $packageId]);
$questions = $stmtQ->fetchAll();

// Acak soal jika diset
if ($package['shuffle_q']) shuffle($questions);

// Acak opsi jika diset
if ($package['shuffle_opt']) {
    foreach ($questions as &$q) {
        $opts   = ['A'=>$q['option_a'],'B'=>$q['option_b'],'C'=>$q['option_c'],'D'=>$q['option_d']];
        $correct_text = $opts[$q['correct_option']];
        $keys   = array_keys($opts);
        shuffle($keys);
        $new = [];
        foreach ($keys as $i => $k) $new[chr(65+$i)] = $opts[$k];
        $q['option_a'] = $new['A']; $q['option_b'] = $new['B'];
        $q['option_c'] = $new['C']; $q['option_d'] = $new['D'];
        foreach ($new as $letter => $val)
            if ($val === $correct_text) { $q['correct_option'] = $letter; break; }
    }
    unset($q);
}

$totalQ    = count($questions);
$timeLimit = intval($package['time_limit']); // detik per soal, 0 = tanpa batas
$accentColor = $package['cat_color'] ?? '#4f46e5';
$level     = $package['level'] ?? 'SD';
$catName   = $package['cat_name'] ?? '';

// Encode data untuk JS
$questionsJson = json_encode(array_values($questions));
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($package['name']) ?> — Kuis Pintar</title>
  <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --accent:   <?= $accentColor ?>;
      --accent-d: color-mix(in srgb, <?= $accentColor ?> 75%, #000);
      --bg:       #f0f4f8;
      --card:     #ffffff;
      --text:     #1e293b;
      --muted:    #64748b;
      --radius:   18px;
      --opt-a:    #fee2e2; --opt-a-t: #991b1b;
      --opt-b:    #fef9c3; --opt-b-t: #854d0e;
      --opt-c:    #d1fae5; --opt-c-t: #065f46;
      --opt-d:    #dbeafe; --opt-d-t: #1d4ed8;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    /* ========== TOP BAR ========== */
    .quiz-topbar {
      background: var(--accent);
      color: #fff;
      padding: .75rem 1.5rem;
      display: flex; align-items: center; justify-content: space-between;
      box-shadow: 0 2px 12px rgba(0,0,0,.15);
      position: sticky; top: 0; z-index: 50;
    }
    .topbar-left { display: flex; align-items: center; gap: .75rem; }
    .back-btn {
      color: rgba(255,255,255,.8); text-decoration: none;
      font-size: .85rem; font-weight: 700;
      display: flex; align-items: center; gap: .4rem;
      transition: color .2s;
    }
    .back-btn:hover { color: #fff; }
    .topbar-pkg {
      font-weight: 800; font-size: .95rem;
      white-space: nowrap; overflow: hidden;
      text-overflow: ellipsis; max-width: 240px;
    }
    .topbar-cat {
      font-size: .75rem; opacity: .75; margin-top: .1rem;
    }

    /* ========== PROGRESS BAR ========== */
    .progress-wrap {
      height: 5px; background: rgba(255,255,255,.25);
      border-radius: 0;
    }
    .progress-fill {
      height: 100%;
      background: #fff;
      border-radius: 0 3px 3px 0;
      transition: width .5s cubic-bezier(.4,0,.2,1);
    }

    /* ========== MAIN QUIZ AREA ========== */
    .quiz-main {
      max-width: 720px;
      margin: 0 auto;
      padding: 2rem 1.25rem 4rem;
    }

    /* ========== QUESTION COUNTER ========== */
    .q-counter {
      display: flex; align-items: center;
      justify-content: space-between;
      margin-bottom: 1.25rem;
      opacity: 0;
    }
    .q-num {
      font-family: 'Fredoka One', cursive;
      font-size: 1rem; color: var(--muted);
    }
    .q-num span { color: var(--accent); font-size: 1.2rem; }

    /* ========== TIMER ========== */
    .timer-wrap {
      display: flex; align-items: center; gap: .6rem;
    }
    .timer-svg { width: 44px; height: 44px; transform: rotate(-90deg); }
    .timer-track { fill: none; stroke: #e2e8f0; stroke-width: 4; }
    .timer-prog  {
      fill: none; stroke: var(--accent); stroke-width: 4;
      stroke-linecap: round;
      stroke-dasharray: 113;
      stroke-dashoffset: 0;
      transition: stroke .5s, stroke-dashoffset 1s linear;
    }
    .timer-text {
      font-family: 'Fredoka One', cursive;
      font-size: 1.1rem; color: var(--text);
      min-width: 28px; text-align: center;
    }
    .timer-warn .timer-prog  { stroke: #f59e0b; }
    .timer-warn .timer-text  { color: #f59e0b; }
    .timer-danger .timer-prog { stroke: #ef4444; }
    .timer-danger .timer-text { color: #ef4444; }

    /* ========== QUESTION CARD ========== */
    .q-card {
      background: var(--card);
      border-radius: var(--radius);
      padding: 1.75rem;
      box-shadow: 0 4px 24px rgba(0,0,0,.08);
      border: 2px solid transparent;
      margin-bottom: 1.25rem;
      opacity: 0;
      transform: translateX(60px);
    }

    .q-image {
      width: 100%; border-radius: 10px;
      margin-bottom: 1.25rem;
      max-height: 220px; object-fit: cover;
    }

    .q-text {
      font-size: 1.15rem;
      font-weight: 700;
      line-height: 1.55;
      color: var(--text);
    }

    /* ========== OPTIONS ========== */
    .options-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: .85rem;
      margin-top: 1.5rem;
    }
    @media (max-width: 520px) { .options-grid { grid-template-columns: 1fr; } }

    .opt-btn {
      display: flex; align-items: center; gap: .75rem;
      background: #f8fafc;
      border: 2px solid #e2e8f0;
      border-radius: 14px;
      padding: .9rem 1rem;
      cursor: pointer;
      transition: all .22s cubic-bezier(.34,1.2,.64,1);
      text-align: left;
      position: relative;
      overflow: hidden;
    }
    .opt-btn:hover:not(:disabled) {
      border-color: var(--accent);
      background: color-mix(in srgb, var(--accent) 8%, #fff);
      transform: translateY(-3px);
      box-shadow: 0 6px 18px rgba(0,0,0,.08);
    }
    .opt-btn:disabled { cursor: default; }

    .opt-letter {
      width: 32px; height: 32px; flex-shrink: 0;
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-weight: 800; font-size: .85rem;
    }
    .opt-a-lbl { background: var(--opt-a); color: var(--opt-a-t); }
    .opt-b-lbl { background: var(--opt-b); color: var(--opt-b-t); }
    .opt-c-lbl { background: var(--opt-c); color: var(--opt-c-t); }
    .opt-d-lbl { background: var(--opt-d); color: var(--opt-d-t); }

    .opt-text { font-weight: 600; font-size: .95rem; line-height: 1.35; flex: 1; }

    /* State: correct */
    .opt-btn.correct {
      border-color: #10b981 !important;
      background: #d1fae5 !important;
    }
    .opt-btn.correct .opt-letter { background: #10b981 !important; color: #fff !important; }

    /* State: wrong */
    .opt-btn.wrong {
      border-color: #ef4444 !important;
      background: #fef2f2 !important;
    }
    .opt-btn.wrong .opt-letter { background: #ef4444 !important; color: #fff !important; }

    /* Ripple */
    .opt-btn::after {
      content: ''; position: absolute;
      width: 120px; height: 120px; border-radius: 50%;
      background: rgba(255,255,255,.4);
      transform: scale(0); opacity: 0;
      pointer-events: none;
    }
    .opt-btn.ripple::after {
      animation: ripple .5s ease-out forwards;
    }
    @keyframes ripple {
      0%   { transform: scale(0); opacity: 1; }
      100% { transform: scale(4); opacity: 0; }
    }

    /* ========== EXPLANATION ========== */
    .explanation-box {
      background: #fffbeb;
      border: 1.5px solid #fde68a;
      border-radius: 12px;
      padding: 1rem 1.25rem;
      font-size: .9rem;
      color: #78350f;
      margin-top: 1.25rem;
      display: none;
    }
    .explanation-box strong { color: #92400e; }

    /* ========== NEXT BUTTON ========== */
    .next-btn-wrap {
      text-align: center;
      margin-top: 1.5rem;
      display: none;
    }
    .next-btn {
      background: var(--accent);
      color: #fff;
      border: none;
      padding: .85rem 2.5rem;
      border-radius: 50px;
      font-family: 'Fredoka One', cursive;
      font-size: 1.1rem;
      cursor: pointer;
      box-shadow: 0 6px 20px rgba(0,0,0,.15);
      transition: all .25s;
      display: inline-flex; align-items: center; gap: .6rem;
    }
    .next-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(0,0,0,.2); }

    /* ========== FEEDBACK OVERLAY ========== */
    .feedback-overlay {
      position: fixed; inset: 0;
      display: flex; align-items: center; justify-content: center;
      z-index: 200; pointer-events: none;
    }
    .feedback-lottie {
      width: 220px; height: 220px;
      opacity: 0; transform: scale(.5);
    }

    /* ========== SCORE / RESULT SCREEN ========== */
    #result-screen {
      display: none;
      text-align: center;
      padding: 3rem 1.5rem;
    }
    .result-trophy {
      width: 160px; height: 160px;
      margin: 0 auto 1.5rem;
    }
    .result-title {
      font-family: 'Fredoka One', cursive;
      font-size: 2rem; margin-bottom: .5rem;
    }
    .result-score {
      font-family: 'Fredoka One', cursive;
      font-size: 5rem; line-height: 1;
      color: var(--accent);
      text-shadow: 0 4px 20px rgba(0,0,0,.1);
    }
    .result-sub { color: var(--muted); font-size: 1rem; margin-top: .5rem; }

    .result-stats {
      display: flex; justify-content: center; gap: 1.5rem;
      margin: 1.5rem 0;
      flex-wrap: wrap;
    }
    .rstat {
      background: var(--card);
      border-radius: 14px;
      padding: 1rem 1.5rem;
      text-align: center;
      box-shadow: 0 2px 10px rgba(0,0,0,.07);
      min-width: 90px;
    }
    .rstat-val {
      font-family: 'Fredoka One', cursive;
      font-size: 1.8rem;
    }
    .rstat-lbl { font-size: .78rem; color: var(--muted); font-weight: 700; margin-top: .2rem; }

    .predikat {
      display: inline-block;
      padding: .4rem 1.5rem;
      border-radius: 50px;
      font-weight: 800; font-size: 1rem;
      margin-bottom: 1.5rem;
    }

    .result-actions { display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; }
    .btn-result {
      padding: .75rem 1.75rem;
      border-radius: 50px;
      font-weight: 800; font-size: .95rem;
      cursor: pointer; transition: all .25s;
      display: inline-flex; align-items: center; gap: .5rem;
      text-decoration: none;
    }
    .btn-result-primary {
      background: var(--accent); color: #fff; border: none;
      box-shadow: 0 4px 14px rgba(0,0,0,.15);
    }
    .btn-result-primary:hover { transform: translateY(-2px); color: #fff; }
    .btn-result-outline {
      background: #fff; color: var(--text);
      border: 2px solid var(--border, #e2e8f0);
    }
    .btn-result-outline:hover { border-color: var(--accent); color: var(--accent); }

    /* Review per soal */
    .review-list { margin-top: 2rem; text-align: left; }
    .review-item {
      background: var(--card);
      border-radius: 14px;
      padding: 1.1rem 1.25rem;
      margin-bottom: .75rem;
      border-left: 4px solid #e2e8f0;
      box-shadow: 0 1px 6px rgba(0,0,0,.05);
    }
    .review-item.correct-item { border-color: #10b981; }
    .review-item.wrong-item   { border-color: #ef4444; }
    .review-q  { font-weight: 700; font-size: .9rem; margin-bottom: .4rem; }
    .review-ans { font-size: .82rem; color: var(--muted); }
    .review-ans .correct-ans { color: #059669; font-weight: 700; }
    .review-ans .wrong-ans   { color: #dc2626; font-weight: 700; }
    .review-exp { font-size: .8rem; color: #78350f; background: #fffbeb; border-radius: 8px; padding: .4rem .75rem; margin-top: .5rem; }
  </style>
</head>
<body>

<!-- TOP BAR + PROGRESS -->
<div>
  <div class="quiz-topbar">
    <div class="topbar-left">
      <a href="packages.php?cat=<?= $package['category_id'] ?>&level=<?= $level ?>" class="back-btn">
        <i class="fa fa-arrow-left"></i>
      </a>
      <div>
        <div class="topbar-pkg"><?= htmlspecialchars($package['name']) ?></div>
        <div class="topbar-cat"><?= htmlspecialchars($catName) ?> · <?= $level ?></div>
      </div>
    </div>
    <div id="topbar-score" style="font-family:'Fredoka One',cursive;font-size:1rem;color:rgba(255,255,255,.85)">
      <i class="fa fa-star" style="color:#fde68a"></i> <span id="live-score">0</span>
    </div>
  </div>
  <div class="progress-wrap">
    <div class="progress-fill" id="progress-fill" style="width:0%"></div>
  </div>
</div>

<div class="quiz-main">

  <!-- QUIZ AREA -->
  <div id="quiz-area">
    <!-- Question counter + timer -->
    <div class="q-counter" id="q-counter">
      <div class="q-num">Soal <span id="q-cur">1</span> dari <?= $totalQ ?></div>
      <div class="timer-wrap" id="timer-wrap" style="<?= $timeLimit ? '' : 'display:none' ?>">
        <svg class="timer-svg" viewBox="0 0 40 40" id="timer-svg">
          <circle class="timer-track" cx="20" cy="20" r="18"/>
          <circle class="timer-prog"  cx="20" cy="20" r="18" id="timer-circle"/>
        </svg>
        <div class="timer-text" id="timer-text"><?= $timeLimit ?></div>
      </div>
    </div>

    <!-- Question Card -->
    <div class="q-card" id="q-card">
      <img id="q-image" class="q-image" src="" alt="" style="display:none">
      <div class="q-text" id="q-text"></div>
      <div class="options-grid" id="options-grid"></div>
      <div class="explanation-box" id="explanation-box">
        <strong><i class="fa fa-lightbulb me-1"></i> Pembahasan:</strong>
        <span id="explanation-text"></span>
      </div>
    </div>

    <!-- Next button -->
    <div class="next-btn-wrap" id="next-btn-wrap">
      <button class="next-btn" id="next-btn" onclick="nextQuestion()">
        <span id="next-label">Soal Berikutnya</span>
        <i class="fa fa-arrow-right"></i>
      </button>
    </div>
  </div>

  <!-- RESULT SCREEN -->
  <div id="result-screen">
    <div class="result-trophy" id="trophy-lottie"></div>
    <div class="predikat" id="predikat-badge"></div>
    <div class="result-title" id="result-title"></div>
    <div class="result-score" id="result-score">0</div>
    <div class="result-sub" id="result-sub"></div>
    <div class="result-stats">
      <div class="rstat">
        <div class="rstat-val" id="rstat-correct" style="color:#10b981">0</div>
        <div class="rstat-lbl">Benar</div>
      </div>
      <div class="rstat">
        <div class="rstat-val" id="rstat-wrong" style="color:#ef4444">0</div>
        <div class="rstat-lbl">Salah</div>
      </div>
      <div class="rstat">
        <div class="rstat-val" id="rstat-total" style="color:var(--accent)"><?= $totalQ ?></div>
        <div class="rstat-lbl">Total Soal</div>
      </div>
    </div>
    <div class="result-actions">
      <button class="btn-result btn-result-primary" onclick="restartQuiz()">
        <i class="fa fa-rotate-right"></i> Ulangi
      </button>
      <a href="packages.php?cat=<?= $package['category_id'] ?>&level=<?= $level ?>"
         class="btn-result btn-result-outline">
        <i class="fa fa-arrow-left"></i> Paket Lain
      </a>
      <a href="subjects.php?level=<?= $level ?>"
         class="btn-result btn-result-outline">
        <i class="fa fa-book-open"></i> Mata Pelajaran
      </a>
    </div>
    <div class="review-list" id="review-list"></div>
  </div>

</div><!-- /quiz-main -->

<!-- Feedback Overlay (Lottie) -->
<div class="feedback-overlay" id="feedback-overlay">
  <div class="feedback-lottie" id="feedback-lottie-el"></div>
</div>

<!-- Audio -->
<audio id="snd-correct" src="assets/audio/correct.mp3" preload="auto"></audio>
<audio id="snd-wrong"   src="assets/audio/wrong.mp3"   preload="auto"></audio>

<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/gsap/gsap.min.js"></script>
<!-- lottie-web player (offline, SVG renderer) -->
<script src="assets/lottie/lottie.min.js"></script>
<!-- canvas-confetti CDN (sudah ada di index.php, pakai CDN juga di sini) -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<script>
function makeLottie(el, src) {
  return lottie.loadAnimation({
    container: el,
    renderer: 'svg',
    loop: false,
    autoplay: false,
    path: src
  });
}

// Swap animasi feedback sesuai tipe (correct/incorrect), lazy load & cache
let _feedbackType = null;
let _feedbackAnim = null;

window._playFeedbackLottie = function(correct) {
  const el  = document.getElementById('feedback-lottie-el');
  const src = correct ? 'assets/lottie/Correct.json' : 'assets/lottie/Incorrect.json';
  const key = correct ? 'correct' : 'wrong';

  if (_feedbackType !== key) {
    if (_feedbackAnim) { _feedbackAnim.destroy(); }
    el.innerHTML = '';
    _feedbackAnim = makeLottie(el, src);
    _feedbackType = key;
  }

  _feedbackAnim.stop();
  _feedbackAnim.play();
};

window._makeLottie = makeLottie;
</script>

<script>
// ================================================================
//  QUIZ ENGINE
// ================================================================
const QUESTIONS  = <?= $questionsJson ?>;
const TIME_LIMIT = <?= $timeLimit ?>;
const TOTAL      = QUESTIONS.length;

let currentIdx   = 0;
let score        = 0;
let correctCount = 0;
let wrongCount   = 0;
let answered     = false;
let timerInterval= null;
let timeLeft     = TIME_LIMIT;
let answers      = []; // { qIdx, chosen, correct, isCorrect }
let startTime    = Date.now();

// ----------------------------------------------------------------
//  INIT
// ----------------------------------------------------------------
window.addEventListener('DOMContentLoaded', () => {
  // Entrance animation
  gsap.to('#q-counter', { opacity:1, duration:.5, delay:.3 });
  showQuestion(0);
});

// ----------------------------------------------------------------
//  SHOW QUESTION
// ----------------------------------------------------------------
function showQuestion(idx) {
  if (idx >= TOTAL) { showResult(); return; }
  answered = false;
  const q  = QUESTIONS[idx];

  // Update counter
  document.getElementById('q-cur').textContent = idx + 1;

  // Progress bar
  document.getElementById('progress-fill').style.width = ((idx / TOTAL) * 100) + '%';

  // Image
  const img = document.getElementById('q-image');
  if (q.image_url) { img.src = q.image_url; img.style.display = 'block'; }
  else              { img.style.display = 'none'; }

  // Question text
  document.getElementById('q-text').textContent = q.question_text;

  // Options
  const grid = document.getElementById('options-grid');
  grid.innerHTML = '';
  const opts = [
    { letter:'A', text: q.option_a, cls:'opt-a-lbl' },
    { letter:'B', text: q.option_b, cls:'opt-b-lbl' },
    { letter:'C', text: q.option_c, cls:'opt-c-lbl' },
    { letter:'D', text: q.option_d, cls:'opt-d-lbl' },
  ];
  opts.forEach(o => {
    const btn = document.createElement('button');
    btn.className = 'opt-btn';
    btn.dataset.letter = o.letter;
    btn.innerHTML = `
      <span class="opt-letter ${o.cls}">${o.letter}</span>
      <span class="opt-text">${escHtml(o.text)}</span>`;
    btn.addEventListener('click', () => handleAnswer(o.letter, btn));
    grid.appendChild(btn);
  });

  // Hide explanation & next
  document.getElementById('explanation-box').style.display = 'none';
  document.getElementById('next-btn-wrap').style.display   = 'none';

  // Card animation
  const card = document.getElementById('q-card');
  gsap.set(card, { opacity:0, x:60 });
  gsap.to(card, { opacity:1, x:0, duration:.45, ease:'power3.out' });

  // Timer
  stopTimer();
  if (TIME_LIMIT > 0) startTimer();
}

// ----------------------------------------------------------------
//  ANSWER HANDLER
// ----------------------------------------------------------------
function handleAnswer(letter, clickedBtn) {
  if (answered) return;
  answered = true;
  stopTimer();

  const q        = QUESTIONS[currentIdx];
  const isCorrect = letter === q.correct_option;

  // Ripple effect
  clickedBtn.classList.add('ripple');
  setTimeout(() => clickedBtn.classList.remove('ripple'), 600);

  // Mark all buttons
  document.querySelectorAll('.opt-btn').forEach(btn => {
    btn.disabled = true;
    if (btn.dataset.letter === q.correct_option) btn.classList.add('correct');
    if (btn === clickedBtn && !isCorrect)         btn.classList.add('wrong');
  });

  // Score & counters
  if (isCorrect) {
    const pts = TIME_LIMIT > 0 ? Math.max(10, Math.round((timeLeft / TIME_LIMIT) * 100)) : 100;
    score += pts;
    correctCount++;
    document.getElementById('live-score').textContent = score;
    playFeedback(true);
  } else {
    wrongCount++;
    // Shake card
    gsap.to('#q-card', { x:-10, duration:.08, repeat:5, yoyo:true, ease:'power1.inOut',
      onComplete: () => gsap.set('#q-card', { x:0 }) });
    playFeedback(false);
  }

  // Explanation
  if (q.explanation) {
    document.getElementById('explanation-text').textContent = q.explanation;
    document.getElementById('explanation-box').style.display = 'block';
    gsap.from('#explanation-box', { opacity:0, y:10, duration:.4, delay:.2 });
  }

  // Save answer for review
  answers.push({
    qIdx: currentIdx,
    chosen: letter,
    correct: q.correct_option,
    isCorrect
  });

  // Show next button
  const nextWrap = document.getElementById('next-btn-wrap');
  const nextLabel = document.getElementById('next-label');
  nextLabel.textContent = currentIdx + 1 < TOTAL ? 'Soal Berikutnya' : 'Lihat Hasil';
  nextWrap.style.display = 'block';
  gsap.from(nextWrap, { opacity:0, y:12, duration:.4, delay:.35 });
}

// ----------------------------------------------------------------
//  NEXT QUESTION
// ----------------------------------------------------------------
function nextQuestion() {
  currentIdx++;
  // Slide out
  gsap.to('#q-card', {
    opacity:0, x:-60, duration:.3, ease:'power2.in',
    onComplete: () => showQuestion(currentIdx)
  });
}

// ----------------------------------------------------------------
//  TIMER
// ----------------------------------------------------------------
const CIRCUMFERENCE = 2 * Math.PI * 18; // ~113

function startTimer() {
  timeLeft = TIME_LIMIT;
  updateTimerUI();
  timerInterval = setInterval(() => {
    timeLeft--;
    updateTimerUI();
    if (timeLeft <= 0) {
      stopTimer();
      // Time's up — auto wrong answer
      if (!answered) {
        const q = QUESTIONS[currentIdx];
        wrongCount++;
        answers.push({ qIdx: currentIdx, chosen: null, correct: q.correct_option, isCorrect: false });
        document.querySelectorAll('.opt-btn').forEach(btn => {
          btn.disabled = true;
          if (btn.dataset.letter === q.correct_option) btn.classList.add('correct');
        });
        playFeedback(false);
        if (q.explanation) {
          document.getElementById('explanation-text').textContent = q.explanation;
          document.getElementById('explanation-box').style.display = 'block';
        }
        const nextWrap = document.getElementById('next-btn-wrap');
        document.getElementById('next-label').textContent = currentIdx + 1 < TOTAL ? 'Soal Berikutnya' : 'Lihat Hasil';
        nextWrap.style.display = 'block';
      }
    }
  }, 1000);
}

function stopTimer() {
  clearInterval(timerInterval);
  timerInterval = null;
}

function updateTimerUI() {
  const pct    = timeLeft / TIME_LIMIT;
  const offset = CIRCUMFERENCE * (1 - pct);
  const circle = document.getElementById('timer-circle');
  const text   = document.getElementById('timer-text');
  const svg    = document.getElementById('timer-svg');

  circle.style.strokeDashoffset = offset;
  text.textContent = timeLeft;

  svg.classList.remove('timer-warn','timer-danger');
  if (pct <= 0.25)      svg.classList.add('timer-danger');
  else if (pct <= 0.5)  svg.classList.add('timer-warn');
}

// ----------------------------------------------------------------
//  FEEDBACK (Lottie + Audio)
// ----------------------------------------------------------------
function playFeedback(correct) {
  // Audio
  const snd = document.getElementById(correct ? 'snd-correct' : 'snd-wrong');
  snd.currentTime = 0;
  snd.play().catch(()=>{});

  // Confetti for correct
  if (correct && typeof confetti === 'function') {
    confetti({
      particleCount: 80,
      spread: 70,
      origin: { y: 0.55 },
      colors: ['<?= $accentColor ?>','#fde68a','#6ee7b7','#a5b4fc'],
    });
  }

  // Lottie overlay
  const el = document.getElementById('feedback-lottie-el');
  gsap.to(el, { opacity:1, scale:1, duration:.3, ease:'back.out(1.4)' });
  setTimeout(() => {
    gsap.to(el, { opacity:0, scale:.5, duration:.3, ease:'power2.in' });
  }, 1200);

  window._playFeedbackLottie(correct);
}

// ----------------------------------------------------------------
//  RESULT SCREEN
// ----------------------------------------------------------------
function showResult() {
  stopTimer();
  const duration = Math.round((Date.now() - startTime) / 1000);
  const pct      = Math.round((correctCount / TOTAL) * 100);

  // Save session
  saveSession(pct, duration);

  // Hide quiz, show result
  document.getElementById('quiz-area').style.display   = 'none';
  const rs = document.getElementById('result-screen');
  rs.style.display = 'block';
  gsap.from(rs, { opacity:0, y:30, duration:.6, ease:'power3.out' });

  // Predikat
  let predikat, predikatBg, predikatColor, title;
  if (pct >= 90)      { predikat='🏆 Sempurna!';    predikatBg='#fef3c7'; predikatColor='#92400e'; title='Luar Biasa!'; }
  else if (pct >= 75) { predikat='⭐ Hebat!';        predikatBg='#d1fae5'; predikatColor='#065f46'; title='Bagus Sekali!'; }
  else if (pct >= 60) { predikat='👍 Baik';          predikatBg='#dbeafe'; predikatColor='#1d4ed8'; title='Terus Berlatih!'; }
  else                { predikat='💪 Terus Semangat!';predikatBg='#fce7f3'; predikatColor='#9d174d'; title='Jangan Menyerah!'; }

  const badge = document.getElementById('predikat-badge');
  badge.textContent = predikat;
  badge.style.background = predikatBg;
  badge.style.color = predikatColor;

  document.getElementById('result-title').textContent = title;
  document.getElementById('result-sub').textContent   = correctCount + ' dari ' + TOTAL + ' soal benar';
  document.getElementById('rstat-correct').textContent = correctCount;
  document.getElementById('rstat-wrong').textContent   = wrongCount;

  // Animated score counter
  let displayed = 0;
  const el = document.getElementById('result-score');
  const interval = setInterval(() => {
    displayed = Math.min(displayed + 2, pct);
    el.textContent = displayed;
    if (displayed >= pct) clearInterval(interval);
  }, 18);

  // Trophy Lottie
  if (window._makeLottie) {
    const trophyEl = document.getElementById('trophy-lottie');
    const player   = window._makeLottie(trophyEl, 'assets/lottie/Trophy.json');
    setTimeout(() => player.play(), 400);
  }

  // Confetti for good score
  if (pct >= 60 && typeof confetti === 'function') {
    setTimeout(() => confetti({ particleCount:120, spread:90, origin:{y:.5} }), 500);
  }

  // Review list
  buildReview();
}

function buildReview() {
  const list = document.getElementById('review-list');
  list.innerHTML = '<p style="font-weight:800;font-size:.9rem;color:var(--muted);margin-bottom:.75rem;text-transform:uppercase;letter-spacing:.5px">Review Jawaban</p>';
  answers.forEach((ans, i) => {
    const q    = QUESTIONS[ans.qIdx];
    const opts = { A: q.option_a, B: q.option_b, C: q.option_c, D: q.option_d };
    const item = document.createElement('div');
    item.className = 'review-item ' + (ans.isCorrect ? 'correct-item' : 'wrong-item');
    item.innerHTML = `
      <div class="review-q">${i+1}. ${escHtml(q.question_text)}</div>
      <div class="review-ans">
        ${ans.chosen
          ? `Jawabanmu: <span class="${ans.isCorrect ? 'correct-ans':'wrong-ans'}">${ans.chosen}. ${escHtml(opts[ans.chosen]||'')}</span>`
          : '<span class="wrong-ans">Waktu habis</span>'}
        ${!ans.isCorrect ? ` &nbsp;|&nbsp; Jawaban benar: <span class="correct-ans">${ans.correct}. ${escHtml(opts[ans.correct]||'')}</span>` : ''}
      </div>
      ${q.explanation ? `<div class="review-exp"><i class="fa fa-lightbulb"></i> ${escHtml(q.explanation)}</div>` : ''}`;
    list.appendChild(item);
  });
}

async function saveSession(pct, duration) {
  try {
    const fd = new FormData();
    fd.append('package_id',   <?= $packageId ?>);
    fd.append('player_name',  'Siswa');
    fd.append('score',        pct);
    fd.append('total_q',      TOTAL);
    fd.append('correct',      correctCount);
    fd.append('wrong',        wrongCount);
    fd.append('duration_sec', duration);
    await fetch('api_session.php', { method:'POST', body: fd });
  } catch(e) { /* silent */ }
}

function restartQuiz() {
  currentIdx = 0; score = 0; correctCount = 0; wrongCount = 0;
  answered = false; answers = []; startTime = Date.now();
  document.getElementById('live-score').textContent = '0';
  document.getElementById('result-screen').style.display = 'none';
  document.getElementById('quiz-area').style.display     = 'block';
  showQuestion(0);
}

function escHtml(str) {
  return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
</body>
</html>
