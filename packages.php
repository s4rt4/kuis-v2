<?php
// packages.php — Daftar paket soal per kategori
require 'config.php';

$catId = intval($_GET['cat']   ?? 0);
$level = strtoupper(trim($_GET['level'] ?? 'SD'));
if (!in_array($level, ['SD','SMP','SMA'])) $level = 'SD';

// Ambil info kategori
$stmtCat = $db->prepare("SELECT * FROM categories WHERE id = :id");
$stmtCat->execute([':id' => $catId]);
$category = $stmtCat->fetch();

if (!$category) {
  header("Location: index.php");
  exit;
}

// Ambil paket beserta jumlah soal
$stmtPkg = $db->prepare("
  SELECT p.*,
         COUNT(q.id) AS question_count
  FROM packages p
  LEFT JOIN questions q ON q.package_id = p.id
  WHERE p.category_id = :cid AND p.is_active = 1
  GROUP BY p.id
  ORDER BY p.sort_order, p.id
");
$stmtPkg->execute([':cid' => $catId]);
$packages = $stmtPkg->fetchAll();

$levelMeta = [
  'SD'  => ['label' => 'SD',  'color' => '#ff6b6b'],
  'SMP' => ['label' => 'SMP', 'color' => '#4ecdc4'],
  'SMA' => ['label' => 'SMA', 'color' => '#a29bfe'],
];
$accent = $levelMeta[$level]['color'];

function getFaIcon(string $name): string {
  $n = strtolower($name);
  if (str_contains($n,'mat'))                             return 'fa-square-root-variable';
  if (str_contains($n,'fisika'))                          return 'fa-atom';
  if (str_contains($n,'kimia'))                           return 'fa-flask';
  if (str_contains($n,'bio'))                             return 'fa-seedling';
  if (str_contains($n,'ipa') || str_contains($n,'sains')) return 'fa-microscope';
  if (str_contains($n,'ips'))                             return 'fa-earth-asia';
  if (str_contains($n,'geo'))                             return 'fa-map-location-dot';
  if (str_contains($n,'sejar'))                           return 'fa-landmark';
  if (str_contains($n,'ekonomi'))                         return 'fa-chart-line';
  if (str_contains($n,'sosiologi'))                       return 'fa-users';
  if (str_contains($n,'indonesia'))                       return 'fa-book-open';
  if (str_contains($n,'ingg'))                            return 'fa-language';
  if (str_contains($n,'ppkn') || str_contains($n,'pkn')) return 'fa-scale-balanced';
  if (str_contains($n,'agama'))                           return 'fa-mosque';
  if (str_contains($n,'seni') || str_contains($n,'sbdp')) return 'fa-palette';
  if (str_contains($n,'prakarya'))                        return 'fa-screwdriver-wrench';
  if (str_contains($n,'pjok') || str_contains($n,'olahraga')) return 'fa-person-running';
  return 'fa-book';
}

$catIcon  = getFaIcon($category['name']);
$catColor = $category['color'] ?? $accent;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($category['name']) ?> — Kuis Pintar</title>
  <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --accent:  <?= $catColor ?>;
      --bg:      #f0f4f8;
      --card-bg: #ffffff;
      --text:    #1a202c;
      --muted:   #718096;
      --radius:  16px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    /* NAV */
    .top-nav {
      background: #fff;
      border-bottom: 2px solid var(--accent);
      padding: .9rem 1.5rem;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 50;
      box-shadow: 0 2px 10px rgba(0,0,0,.07);
    }
    .back-btn {
      display: flex; align-items: center; gap: .5rem;
      font-weight: 700; font-size: .95rem;
      color: var(--text); text-decoration: none; transition: color .2s;
    }
    .back-btn:hover { color: var(--accent); }
    .nav-brand {
      font-family: 'Fredoka One', cursive;
      font-size: 1.2rem; color: var(--accent);
    }
    .level-pill {
      background: var(--accent); color: #fff;
      font-weight: 800; font-size: .78rem;
      padding: .3rem .9rem; border-radius: 20px;
    }

    /* HERO */
    .page-header {
      background: linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 65%, #000));
      color: #fff;
      padding: 2rem 1.5rem 3.5rem;
      text-align: center;
    }
    .header-icon {
      width: 72px; height: 72px;
      background: rgba(255,255,255,.2);
      border-radius: 50%;
      display: inline-flex; align-items: center; justify-content: center;
      font-size: 2rem; margin-bottom: .75rem;
    }
    .page-header h1 {
      font-family: 'Fredoka One', cursive;
      font-size: clamp(1.5rem, 4vw, 2.2rem);
      margin-bottom: .25rem;
    }
    .page-header p { font-size: .95rem; opacity: .85; }

    .wave { display: block; margin-top: -2px; fill: var(--bg); }

    /* PACKAGES GRID */
    .packages-wrap {
      max-width: 900px;
      margin: 0 auto;
      padding: 1.5rem 1.5rem 4rem;
    }

    .section-label {
      font-weight: 800; font-size: .85rem;
      text-transform: uppercase; letter-spacing: 1px;
      color: var(--muted);
      margin-bottom: 1rem;
      display: flex; align-items: center; gap: .5rem;
    }
    .section-label::after {
      content: ''; flex: 1;
      height: 1px; background: #e2e8f0;
    }

    .pkg-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 1.1rem;
    }

    /* PACKAGE CARD */
    .pkg-card {
      background: var(--card-bg);
      border-radius: var(--radius);
      padding: 1.5rem;
      text-decoration: none;
      color: var(--text);
      border: 2px solid #e2e8f0;
      box-shadow: 0 2px 8px rgba(0,0,0,.06);
      transition: all .28s cubic-bezier(.34,1.2,.64,1);
      opacity: 0;
      transform: translateY(20px);
      display: flex;
      flex-direction: column;
      gap: .75rem;
      position: relative;
      overflow: hidden;
    }
    .pkg-card::after {
      content: '';
      position: absolute; right: -20px; bottom: -20px;
      width: 80px; height: 80px;
      border-radius: 50%;
      background: color-mix(in srgb, var(--accent) 8%, transparent);
      transition: transform .3s;
    }
    .pkg-card:hover {
      border-color: var(--accent);
      box-shadow: 0 10px 24px rgba(0,0,0,.1);
      transform: translateY(-5px);
      color: var(--text);
    }
    .pkg-card:hover::after { transform: scale(2.5); }

    .pkg-header {
      display: flex; align-items: flex-start;
      justify-content: space-between; gap: .5rem;
    }
    .pkg-icon {
      width: 44px; height: 44px; flex-shrink: 0;
      background: color-mix(in srgb, var(--accent) 12%, transparent);
      color: var(--accent);
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.2rem;
    }
    .pkg-number {
      font-size: .72rem; font-weight: 800;
      color: var(--muted); letter-spacing: .5px;
      margin-top: .2rem;
    }

    .pkg-name {
      font-weight: 800; font-size: 1rem;
      line-height: 1.35;
      flex: 1;
    }

    .pkg-desc {
      font-size: .85rem;
      color: var(--muted);
      line-height: 1.5;
      flex: 1;
    }

    .pkg-footer {
      display: flex; align-items: center;
      justify-content: space-between;
      margin-top: auto;
      padding-top: .75rem;
      border-top: 1px solid #f0f4f8;
    }
    .pkg-stats {
      display: flex; gap: .75rem;
    }
    .stat-chip {
      display: inline-flex; align-items: center; gap: .3rem;
      font-size: .78rem; font-weight: 700;
      color: var(--muted);
    }
    .stat-chip i { color: var(--accent); }

    .start-btn {
      display: inline-flex; align-items: center; gap: .4rem;
      background: var(--accent);
      color: #fff;
      font-weight: 800; font-size: .82rem;
      padding: .4rem 1rem;
      border-radius: 20px;
      transition: opacity .2s, transform .2s;
      position: relative; z-index: 1;
    }
    .pkg-card:hover .start-btn {
      opacity: .9;
      transform: scale(1.05);
    }

    /* EMPTY */
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      color: var(--muted);
    }
    .empty-state i { font-size: 4rem; opacity: .25; display: block; margin-bottom: 1rem; }
    .empty-state a { color: var(--accent); font-weight: 700; }

    /* TIMER BADGE */
    .timer-badge {
      display: inline-flex; align-items: center; gap: .3rem;
      font-size: .72rem; font-weight: 700;
      background: #fff3cd; color: #856404;
      padding: .2rem .55rem; border-radius: 8px;
    }
    .no-timer { display: none; }
  </style>
</head>
<body>

<!-- NAV -->
<nav class="top-nav">
  <a href="subjects.php?level=<?= $level ?>" class="back-btn">
    <i class="fa fa-arrow-left"></i> Mata Pelajaran
  </a>
  <span class="nav-brand"><i class="fa fa-graduation-cap"></i> Kuis Pintar</span>
  <span class="level-pill"><?= $level ?></span>
</nav>

<!-- HERO -->
<div class="page-header">
  <div class="header-icon">
    <i class="fa-solid <?= $catIcon ?>"></i>
  </div>
  <h1><?= htmlspecialchars($category['name']) ?></h1>
  <p>Pilih paket soal di bawah untuk memulai latihan</p>
</div>
<svg viewBox="0 0 1440 48" class="wave" xmlns="http://www.w3.org/2000/svg">
  <path d="M0,32 C360,0 1080,64 1440,16 L1440,48 L0,48 Z"/>
</svg>

<!-- PACKAGES -->
<div class="packages-wrap">

<?php if (empty($packages)): ?>
  <div class="empty-state">
    <i class="fa fa-folder-open"></i>
    <p>Belum ada paket soal untuk mata pelajaran ini.</p>
    <p class="mt-2">Minta gurumu untuk <a href="admin.php">menambahkan paket soal</a>.</p>
  </div>

<?php else: ?>
  <div class="section-label">
    <i class="fa fa-layer-group"></i>
    <?= count($packages) ?> Paket Tersedia
  </div>

  <div class="pkg-grid" id="pkg-grid">
    <?php foreach ($packages as $i => $pkg):
      $qCount    = (int)$pkg['question_count'];
      $hasTimer  = $pkg['time_limit'] > 0;
      $timerText = $hasTimer ? $pkg['time_limit'].' dtk/soal' : '';
    ?>
    <a href="quiz.php?package=<?= $pkg['id'] ?>"
       class="pkg-card"
       data-idx="<?= $i ?>">
      <div class="pkg-header">
        <div class="pkg-icon">
          <i class="fa-solid <?= $catIcon ?>"></i>
        </div>
        <div class="pkg-number">PAKET <?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></div>
      </div>

      <div class="pkg-name"><?= htmlspecialchars($pkg['name']) ?></div>

      <?php if (!empty($pkg['description'])): ?>
        <div class="pkg-desc"><?= nl2br(htmlspecialchars($pkg['description'])) ?></div>
      <?php endif; ?>

      <div class="pkg-footer">
        <div class="pkg-stats">
          <span class="stat-chip">
            <i class="fa fa-circle-question"></i>
            <?= $qCount ?> soal
          </span>
          <?php if ($hasTimer): ?>
          <span class="timer-badge">
            <i class="fa fa-clock"></i> <?= $timerText ?>
          </span>
          <?php endif; ?>
        </div>
        <span class="start-btn">
          Mulai <i class="fa fa-play"></i>
        </span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

<?php endif; ?>
</div>

<script src="assets/gsap/gsap.min.js"></script>
<script>
  gsap.to('.pkg-card', {
    opacity: 1, y: 0,
    duration: .5, stagger: .08,
    ease: 'power3.out', delay: .1
  });

  document.querySelectorAll('.pkg-card').forEach(card => {
    card.addEventListener('click', e => {
      e.preventDefault();
      const href = card.href;
      gsap.to('body', {
        opacity: 0, duration: .3, ease: 'power2.in',
        onComplete: () => window.location.href = href
      });
    });
  });
</script>
</body>
</html>
