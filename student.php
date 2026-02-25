<?php
// student.php — Dashboard Siswa
session_start();
require 'config.php';

// Hanya untuk siswa yang sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Jika admin/guru nyasar ke sini, kirim ke admin panel
if (in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header('Location: admin.php');
    exit;
}

$studentName  = $_SESSION['user_name']  ?? $_SESSION['user_username'] ?? 'Siswa';
$studentLevel = strtoupper($_SESSION['user_level'] ?? 'SD'); // SD, SMP, SMA
$studentAvatar = $_SESSION['user_avatar'] ?? 'assets/png/avatar.png';

// Ambil kategori sesuai level siswa — query langsung tanpa view
try {
    $stmt = $db->prepare("
        SELECT
            c.id   AS category_id,
            c.name AS category_name,
            c.color,
            c.sort_order,
            COUNT(p.id) AS package_count
        FROM categories c
        LEFT JOIN packages p
            ON p.category_id = c.id
            AND LOWER(p.target_level) IN (:lv, 'all')
            AND p.is_active = 1
        GROUP BY c.id, c.name, c.color, c.sort_order
        HAVING package_count > 0
        ORDER BY c.sort_order, c.name
    ");
    $stmt->execute([':lv' => strtolower($studentLevel)]);
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = []; // Graceful fallback — tampilkan empty state
}

// Warna tema per jenjang
$levelMeta = [
    'SD'  => ['label' => 'Sekolah Dasar',           'color' => '#f97316', 'gradient' => 'linear-gradient(135deg, #f97316, #ef4444)'],
    'SMP' => ['label' => 'Sekolah Menengah Pertama', 'color' => '#06b6d4', 'gradient' => 'linear-gradient(135deg, #06b6d4, #3b82f6)'],
    'SMA' => ['label' => 'Sekolah Menengah Atas',    'color' => '#8b5cf6', 'gradient' => 'linear-gradient(135deg, #8b5cf6, #ec4899)'],
];
$meta = $levelMeta[$studentLevel] ?? $levelMeta['SD'];

function getFaIcon(string $name): string {
    $n = strtolower($name);
    if (str_contains($n,'mat'))                            return 'fa-square-root-variable';
    if (str_contains($n,'fisika'))                         return 'fa-atom';
    if (str_contains($n,'kimia'))                          return 'fa-flask';
    if (str_contains($n,'bio'))                            return 'fa-seedling';
    if (str_contains($n,'ipa') || str_contains($n,'sains'))return 'fa-microscope';
    if (str_contains($n,'ips'))                            return 'fa-earth-asia';
    if (str_contains($n,'geo'))                            return 'fa-map-location-dot';
    if (str_contains($n,'sejar'))                          return 'fa-landmark';
    if (str_contains($n,'ekonomi'))                        return 'fa-chart-line';
    if (str_contains($n,'sosiologi'))                      return 'fa-users';
    if (str_contains($n,'indonesia'))                      return 'fa-book-open';
    if (str_contains($n,'ingg'))                           return 'fa-language';
    if (str_contains($n,'ppkn') || str_contains($n,'pkn')) return 'fa-scale-balanced';
    if (str_contains($n,'agama'))                          return 'fa-mosque';
    if (str_contains($n,'seni') || str_contains($n,'sbdp'))return 'fa-palette';
    if (str_contains($n,'prakarya'))                       return 'fa-screwdriver-wrench';
    if (str_contains($n,'pjok') || str_contains($n,'olahraga')) return 'fa-person-running';
    if (str_contains($n,'komputer') || str_contains($n,'tik'))  return 'fa-computer';
    return 'fa-book';
}

// Salam berdasarkan waktu
$hour = (int) date('G', time() + 7*3600); // WIB
if ($hour >= 4 && $hour < 11)      $greeting = 'Selamat Pagi';
elseif ($hour >= 11 && $hour < 15) $greeting = 'Selamat Siang';
elseif ($hour >= 15 && $hour < 18) $greeting = 'Selamat Sore';
else                               $greeting = 'Selamat Malam';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard Siswa — Kuis Pintar</title>
  <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    :root {
      --accent:   <?= $meta['color'] ?>;
      --gradient: <?= $meta['gradient'] ?>;
      --bg:       #f0f4f8;
      --card-bg:  #ffffff;
      --text:     #1e293b;
      --muted:    #64748b;
      --radius:   18px;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    /* ===== TOPBAR ===== */
    .top-nav {
      background: #fff;
      border-bottom: 3px solid transparent;
      border-image: var(--gradient) 1;
      padding: .85rem 1.5rem;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 50;
      box-shadow: 0 2px 12px rgba(0,0,0,.07);
    }
    .brand {
      font-family: 'Fredoka One', cursive;
      font-size: 1.4rem;
      background: var(--gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .nav-right { display: flex; align-items: center; gap: 1rem; }
    .level-badge {
      background: var(--gradient);
      color: #fff;
      font-weight: 800;
      font-size: .75rem;
      padding: .3rem .9rem;
      border-radius: 20px;
    }
    .nav-avatar {
      width: 34px; height: 34px;
      border-radius: 50%; object-fit: cover;
      border: 2px solid var(--accent);
    }
    .logout-link {
      font-size: .82rem; font-weight: 700;
      color: var(--muted); text-decoration: none;
      display: flex; align-items: center; gap: .3rem;
      transition: color .2s;
    }
    .logout-link:hover { color: #ef4444; }

    /* ===== HERO ===== */
    .hero {
      background: var(--gradient);
      color: #fff;
      padding: 3rem 1.5rem 4rem;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .hero::before {
      content: '';
      position: absolute; inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .hero-avatar {
      width: 80px; height: 80px;
      border-radius: 50%; object-fit: cover;
      border: 4px solid rgba(255,255,255,.5);
      margin-bottom: 1rem;
      box-shadow: 0 8px 24px rgba(0,0,0,.2);
    }
    .hero-greeting {
      font-size: 1rem;
      opacity: .85;
      margin-bottom: .25rem;
    }
    .hero-name {
      font-family: 'Fredoka One', cursive;
      font-size: clamp(1.8rem, 5vw, 2.6rem);
      margin-bottom: .5rem;
    }
    .hero-sub {
      font-size: 1rem;
      opacity: .8;
    }

    /* WAVE */
    .wave { display: block; margin-top: -2px; fill: var(--bg); }

    /* ===== SECTION TITLE ===== */
    .section-title {
      font-family: 'Fredoka One', cursive;
      font-size: 1.25rem;
      color: var(--text);
      padding: 1.5rem 1.5rem .5rem;
      display: flex; align-items: center; gap: .5rem;
    }
    .section-title i { color: var(--accent); }

    /* ===== SUBJECT GRID ===== */
    .subjects-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1.1rem;
      padding: .75rem 1.5rem 4rem;
      max-width: 1100px;
      margin: 0 auto;
    }

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
      transform: translateY(24px);
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
    .subject-card:hover .card-icon-wrap { transform: scale(1.15) rotate(-5deg); }
    .card-name { font-weight: 800; font-size: 1rem; line-height: 1.3; }
    .card-meta { font-size: .8rem; color: var(--muted); }
    .badge-pkg {
      display: inline-flex; align-items: center; gap: .3rem;
      background: #f0f4f8; padding: .2rem .6rem;
      border-radius: 20px; font-weight: 700;
    }

    /* EMPTY */
    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 4rem 2rem;
      color: var(--muted);
    }
    .empty-state i { font-size: 3.5rem; opacity: .3; margin-bottom: 1rem; display: block; }
  </style>
</head>
<body>

<!-- TOP NAV -->
<nav class="top-nav">
  <span class="brand"><i class="fa fa-graduation-cap"></i> Kuis Pintar</span>
  <div class="nav-right">
    <span class="level-badge"><i class="fa fa-shield-halved"></i> <?= $studentLevel ?></span>
    <a href="profile.php" title="Edit Profil Saya" style="display:flex;align-items:center;gap:.4rem;text-decoration:none;color:var(--text);font-size:.82rem;font-weight:700">
      <img src="<?= htmlspecialchars(!empty($studentAvatar) && file_exists($studentAvatar) ? $studentAvatar : 'assets/png/avatar.png') ?>?v=<?= time() ?>" alt="Avatar" class="nav-avatar">
      Profil
    </a>
    <a href="logout.php" class="logout-link" onclick="return confirm('Yakin ingin keluar?')">
      <i class="fa fa-right-from-bracket"></i> Keluar
    </a>
  </div>
</nav>

<!-- HERO -->
<div class="hero">
  <img class="hero-avatar"
       src="<?= htmlspecialchars(!empty($studentAvatar) && file_exists($studentAvatar) ? $studentAvatar : 'assets/png/avatar.png') ?>?v=<?= time() ?>"
       alt="<?= htmlspecialchars($studentName) ?>">
  <p class="hero-greeting"><?= $greeting ?>, 👋</p>
  <h1 class="hero-name"><?= htmlspecialchars($studentName) ?>!</h1>
  <p class="hero-sub">Silakan pilih mata pelajaran yang ingin kamu latih hari ini</p>
</div>
<svg viewBox="0 0 1440 48" class="wave" xmlns="http://www.w3.org/2000/svg">
  <path d="M0,32 C360,0 1080,64 1440,16 L1440,48 L0,48 Z"/>
</svg>

<!-- SECTION TITLE -->
<div class="section-title">
  <i class="fa fa-book-open-reader"></i>
  Mata Pelajaran — <?= $meta['label'] ?>
</div>

<!-- SUBJECT GRID -->
<div class="subjects-grid" id="subjects-grid">
<?php if (empty($categories)): ?>
  <div class="empty-state">
    <i class="fa fa-box-open"></i>
    <p>Belum ada mata pelajaran untuk jenjang <strong><?= $studentLevel ?></strong>.</p>
    <p class="mt-2" style="font-size:.85rem;color:var(--muted)">Hubungi guru atau administrator kamu.</p>
  </div>

<?php else:
  $colors = [
    '#e74c3c','#e67e22','#f1c40f','#2ecc71','#1abc9c',
    '#3498db','#9b59b6','#e91e8c','#ff5722','#00bcd4',
    '#8bc34a','#ff9800','#607d8b','#795548','#5c6bc0',
  ];
  foreach ($categories as $i => $cat):
    $color    = $cat['color'] ?? $colors[$i % count($colors)];
    $icon     = getFaIcon($cat['category_name']);
    $pkgCount = (int)$cat['package_count'];
?>
  <a href="packages.php?cat=<?= $cat['category_id'] ?>&level=<?= $studentLevel ?>"
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
  // Entrance animation
  gsap.to('.subject-card', {
    opacity: 1, y: 0,
    duration: .5,
    stagger: .07,
    ease: 'power3.out',
    delay: .2
  });

  // Page transition on click
  document.querySelectorAll('.subject-card').forEach(card => {
    card.addEventListener('click', e => {
      e.preventDefault();
      const href = card.href;
      gsap.to('body', {
        opacity: 0, duration: .25, ease: 'power2.in',
        onComplete: () => window.location.href = href
      });
    });
  });
</script>
</body>
</html>
