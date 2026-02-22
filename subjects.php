<?php
// subjects.php — Pilih Mata Pelajaran (card design)
require 'config.php';

$level = strtoupper(trim($_GET['level'] ?? 'SD'));
if (!in_array($level, ['SD','SMP','SMA'])) $level = 'SD';

$stmt = $db->prepare("SELECT * FROM v_category_stats WHERE level = :lv ORDER BY sort_order, category_name");
$stmt->execute([':lv' => $level]);
$categories = $stmt->fetchAll();

$levelMeta = [
  'SD'  => ['label' => 'Sekolah Dasar',           'color' => '#ff6b6b'],
  'SMP' => ['label' => 'Sekolah Menengah Pertama', 'color' => '#4ecdc4'],
  'SMA' => ['label' => 'Sekolah Menengah Atas',    'color' => '#a29bfe'],
];
$meta = $levelMeta[$level];

// Map nama kategori → Font Awesome icon class
function getFaIcon(string $name): string {
  $n = strtolower($name);
  if (str_contains($n,'mat'))                          return 'fa-square-root-variable';
  if (str_contains($n,'fisika'))                       return 'fa-atom';
  if (str_contains($n,'kimia'))                        return 'fa-flask';
  if (str_contains($n,'bio'))                          return 'fa-seedling';
  if (str_contains($n,'ipa') || str_contains($n,'sains')) return 'fa-microscope';
  if (str_contains($n,'ips'))                          return 'fa-earth-asia';
  if (str_contains($n,'geo'))                          return 'fa-map-location-dot';
  if (str_contains($n,'sejar'))                        return 'fa-landmark';
  if (str_contains($n,'ekonomi'))                      return 'fa-chart-line';
  if (str_contains($n,'sosiologi'))                    return 'fa-users';
  if (str_contains($n,'indonesia'))                    return 'fa-book-open';
  if (str_contains($n,'ingg'))                         return 'fa-language';
  if (str_contains($n,'ppkn') || str_contains($n,'pkn')) return 'fa-scale-balanced';
  if (str_contains($n,'agama'))                        return 'fa-mosque';
  if (str_contains($n,'seni') || str_contains($n,'sbdp')) return 'fa-palette';
  if (str_contains($n,'prakarya'))                     return 'fa-screwdriver-wrench';
  if (str_contains($n,'pjok') || str_contains($n,'olahraga')) return 'fa-person-running';
  if (str_contains($n,'komputer') || str_contains($n,'tik'))  return 'fa-computer';
  return 'fa-book';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pilih Mata Pelajaran — <?= $level ?> | Kuis Pintar</title>
  <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --accent: <?= $meta['color'] ?>;
      --bg:     #f0f4f8;
      --card-bg:#ffffff;
      --text:   #1a202c;
      --muted:  #718096;
      --radius: 16px;
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
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky; top: 0; z-index: 50;
      box-shadow: 0 2px 10px rgba(0,0,0,.07);
    }
    .back-btn {
      display: flex; align-items: center; gap: .5rem;
      font-weight: 700; font-size: .95rem;
      color: var(--text); text-decoration: none;
      transition: color .2s;
    }
    .back-btn:hover { color: var(--accent); }
    .nav-brand {
      font-family: 'Fredoka One', cursive;
      font-size: 1.3rem;
      color: var(--accent);
    }
    .level-pill {
      background: var(--accent);
      color: #fff;
      font-weight: 800;
      font-size: .78rem;
      padding: .3rem .9rem;
      border-radius: 20px;
      letter-spacing: .5px;
    }

    /* HEADER */
    .page-header {
      background: linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 70%, #000));
      color: #fff;
      padding: 2.5rem 1.5rem 3rem;
      text-align: center;
    }
    .page-header h1 {
      font-family: 'Fredoka One', cursive;
      font-size: clamp(1.6rem, 4vw, 2.4rem);
      margin-bottom: .3rem;
    }
    .page-header p {
      font-size: 1rem;
      opacity: .85;
    }

    /* WAVE DIVIDER */
    .wave {
      display: block;
      margin-top: -2px;
      fill: var(--bg);
    }

    /* GRID */
    .subjects-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 1.25rem;
      padding: 2rem 1.5rem 3rem;
      max-width: 1100px;
      margin: 0 auto;
    }

    /* SUBJECT CARD */
    .subject-card {
      background: var(--card-bg);
      border-radius: var(--radius);
      padding: 1.75rem 1.25rem;
      text-align: center;
      text-decoration: none;
      color: var(--text);
      border: 2px solid transparent;
      box-shadow: 0 2px 12px rgba(0,0,0,.07);
      transition: all .28s cubic-bezier(.34,1.2,.64,1);
      opacity: 0;
      transform: translateY(20px);
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: .75rem;
    }
    .subject-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 4px;
      background: var(--card-accent, var(--accent));
      border-radius: var(--radius) var(--radius) 0 0;
    }
    .subject-card:hover {
      transform: translateY(-6px) scale(1.02);
      border-color: var(--card-accent, var(--accent));
      box-shadow: 0 12px 28px rgba(0,0,0,.12);
      color: var(--text);
    }

    .card-icon-wrap {
      width: 64px; height: 64px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.7rem;
      background: color-mix(in srgb, var(--card-accent, var(--accent)) 15%, transparent);
      color: var(--card-accent, var(--accent));
      transition: transform .3s;
    }
    .subject-card:hover .card-icon-wrap {
      transform: scale(1.15) rotate(-5deg);
    }

    .card-name {
      font-weight: 800;
      font-size: 1rem;
      line-height: 1.3;
    }

    .card-meta {
      font-size: .8rem;
      color: var(--muted);
    }

    .card-meta .badge-pkg {
      display: inline-flex;
      align-items: center;
      gap: .3rem;
      background: #f0f4f8;
      padding: .2rem .6rem;
      border-radius: 20px;
      font-weight: 700;
    }

    /* EMPTY */
    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 4rem 2rem;
      color: var(--muted);
    }
    .empty-state i { font-size: 3.5rem; opacity: .3; margin-bottom: 1rem; display: block; }
    .empty-state a { color: var(--accent); font-weight: 700; }
  </style>
</head>
<body>

<!-- NAV -->
<nav class="top-nav">
  <a href="index.php" class="back-btn"><i class="fa fa-arrow-left"></i> Ganti Jenjang</a>
  <span class="nav-brand"><i class="fa fa-graduation-cap"></i> Kuis Pintar</span>
  <span class="level-pill"><?= $level ?></span>
</nav>

<!-- HEADER -->
<div class="page-header">
  <h1><i class="fa fa-book-open-reader me-2"></i><?= htmlspecialchars($meta['label']) ?></h1>
  <p>Pilih mata pelajaran yang ingin kamu latih hari ini</p>
</div>
<svg viewBox="0 0 1440 48" class="wave" xmlns="http://www.w3.org/2000/svg">
  <path d="M0,32 C360,0 1080,64 1440,16 L1440,48 L0,48 Z"/>
</svg>

<!-- GRID KATEGORI -->
<div class="subjects-grid" id="subjects-grid">

<?php if (empty($categories)): ?>
  <div class="empty-state">
    <i class="fa fa-box-open"></i>
    <p>Belum ada mata pelajaran untuk jenjang <strong><?= $level ?></strong>.</p>
    <p class="mt-2"><a href="admin.php">Tambahkan di panel admin</a></p>
  </div>

<?php else:
  $colors = [
    '#e74c3c','#e67e22','#f1c40f','#2ecc71','#1abc9c',
    '#3498db','#9b59b6','#e91e8c','#ff5722','#00bcd4',
    '#8bc34a','#ff9800','#607d8b','#795548','#5c6bc0',
  ];
  foreach ($categories as $i => $cat):
    $color   = $cat['color'] ?? $colors[$i % count($colors)];
    $icon    = getFaIcon($cat['category_name']);
    $pkgCount = (int)$cat['package_count'];
    $qCount   = (int)$cat['question_count'];
  ?>
  <a href="packages.php?cat=<?= $cat['category_id'] ?>&level=<?= $level ?>"
     class="subject-card"
     style="--card-accent:<?= $color ?>"
     data-delay="<?= $i ?>">
    <div class="card-icon-wrap">
      <i class="fa-solid <?= $icon ?>"></i>
    </div>
    <div class="card-name"><?= htmlspecialchars($cat['category_name']) ?></div>
    <div class="card-meta">
      <span class="badge-pkg">
        <i class="fa fa-layer-group"></i>
        <?= $pkgCount ?> paket
      </span>
    </div>
  </a>
<?php endforeach; endif; ?>

</div>

<script src="assets/gsap/gsap.min.js"></script>
<script>
  // Staggered entrance
  gsap.to('.subject-card', {
    opacity: 1, y: 0,
    duration: .5,
    stagger: .06,
    ease: 'power3.out',
    delay: .15
  });

  // Klik → page transition
  document.querySelectorAll('.subject-card').forEach(card => {
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
