<?php
// books.php — Rak Buku per Jenjang
require 'config.php';

$level = strtoupper(trim($_GET['level'] ?? 'SD'));
if (!in_array($level, ['SD','SMP','SMA'])) $level = 'SD';

// Ambil kategori sesuai jenjang
// Kolom 'level' dan 'color' perlu ditambahkan ke tabel categories (lihat schema_update.sql)
$stmt = $db->prepare("SELECT * FROM categories WHERE level = :lv ORDER BY sort_order, name");
$stmt->execute([':lv' => $level]);
$categories = $stmt->fetchAll();

// Warna default jika belum diisi di DB
$defaultColors = [
  '#e74c3c','#e67e22','#f1c40f','#2ecc71',
  '#1abc9c','#3498db','#9b59b6','#e91e8c',
  '#ff5722','#00bcd4','#8bc34a','#ff9800',
];

// Label dan tema per jenjang
$levelMeta = [
  'SD'  => ['label'=>'Sekolah Dasar',          'icon'=>'🏫', 'theme'=>'theme-sd'],
  'SMP' => ['label'=>'Sekolah Menengah Pertama','icon'=>'📚', 'theme'=>'theme-smp'],
  'SMA' => ['label'=>'Sekolah Menengah Atas',   'icon'=>'🎓', 'theme'=>'theme-sma'],
];
$meta = $levelMeta[$level];

// Ikon default per kata kunci nama kategori
function getBookIcon(string $name): string {
  $name = strtolower($name);
  if (str_contains($name,'mat'))    return '🔢';
  if (str_contains($name,'ipa')||str_contains($name,'sains')) return '🔬';
  if (str_contains($name,'ips'))    return '🌍';
  if (str_contains($name,'bhs')||str_contains($name,'bahasa')) return '📖';
  if (str_contains($name,'ingg'))   return '🌐';
  if (str_contains($name,'ppkn')||str_contains($name,'pkn')) return '🏛️';
  if (str_contains($name,'agama'))  return '🕌';
  if (str_contains($name,'penjas')||str_contains($name,'olahraga')) return '⚽';
  if (str_contains($name,'seni'))   return '🎨';
  if (str_contains($name,'kimia'))  return '⚗️';
  if (str_contains($name,'fisika')) return '⚡';
  if (str_contains($name,'bio'))    return '🌱';
  if (str_contains($name,'sejar'))  return '📜';
  if (str_contains($name,'geo'))    return '🗺️';
  if (str_contains($name,'ekonomi')) return '💰';
  if (str_contains($name,'sosiologi')) return '👥';
  return '📚';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pilih Pelajaran — <?= $level ?> | Kuis Pintar</title>
  <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg:        #0f1b2d;
      --gold:      #f5c842;
      --wood:      #6b3a1f;
      --wood-top:  #8b5a2b;
      --wood-edge: #4a2810;
      --cream:     #fff8e7;
      --shelf-h:   18px;
    }

    /* === Jenjang Themes === */
    .theme-sd  { --accent:#ff6b6b; --accent2:#ffb347; --shelf-bg:linear-gradient(135deg,#1a2a1a,#0d1f0d); }
    .theme-smp { --accent:#4ecdc4; --accent2:#44a3ff; --shelf-bg:linear-gradient(135deg,#0d1a2a,#0a1520); }
    .theme-sma { --accent:#a29bfe; --accent2:#fd79a8; --shelf-bg:linear-gradient(135deg,#1a1a2a,#0f0f1f); }

    * { box-sizing:border-box; margin:0; padding:0; }

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      background-image: var(--shelf-bg);
      min-height: 100vh;
      color: var(--cream);
      overflow-x: hidden;
    }

    /* === HEADER NAV === */
    .top-nav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem 2rem;
      background: rgba(0,0,0,.35);
      backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(255,255,255,.07);
      position: sticky; top: 0; z-index: 100;
    }
    .back-btn {
      display: flex; align-items: center; gap: .5rem;
      color: var(--cream); text-decoration: none; font-weight: 700;
      font-size: .95rem; transition: color .2s;
    }
    .back-btn:hover { color: var(--gold); }
    .back-arrow { font-size: 1.2rem; }

    .nav-title {
      font-family: 'Fredoka One', cursive;
      font-size: 1.4rem;
      color: var(--gold);
      letter-spacing: 1px;
    }
    .level-badge {
      background: var(--accent);
      color: #fff;
      font-weight: 800;
      font-size: .8rem;
      padding: .25rem .75rem;
      border-radius: 20px;
      letter-spacing: 1px;
    }

    /* === PAGE HEADING === */
    .page-heading {
      text-align: center;
      padding: 2.5rem 1rem 1rem;
      opacity: 0;
    }
    .page-heading h2 {
      font-family: 'Fredoka One', cursive;
      font-size: clamp(1.6rem,5vw,2.8rem);
      color: var(--cream);
      text-shadow: 0 0 20px rgba(255,255,255,.1);
    }
    .page-heading p {
      color: rgba(255,248,231,.55);
      font-size: 1rem;
      margin-top: .4rem;
    }

    /* === BOOKSHELF AREA === */
    .library-room {
      padding: 2rem 2rem 4rem;
      max-width: 1100px;
      margin: 0 auto;
    }

    /* One shelf = one row of books + wooden plank */
    .shelf-row {
      margin-bottom: 3rem;
      opacity: 0;
      transform: translateY(30px);
    }

    .books-on-shelf {
      display: flex;
      gap: 1.4rem;
      flex-wrap: wrap;
      align-items: flex-end;
      padding: 0 1rem .6rem;
      min-height: 200px;
    }

    /* === WOODEN SHELF PLANK === */
    .shelf-plank {
      height: var(--shelf-h);
      background: linear-gradient(180deg, var(--wood-top) 0%, var(--wood) 60%, var(--wood-edge) 100%);
      border-radius: 4px 4px 6px 6px;
      box-shadow: 0 6px 18px rgba(0,0,0,.6), inset 0 1px 0 rgba(255,255,255,.15);
      position: relative;
    }
    .shelf-plank::after {
      content:'';
      position:absolute; inset:0;
      background: repeating-linear-gradient(90deg,transparent,transparent 80px,rgba(0,0,0,.08) 80px,rgba(0,0,0,.08) 82px);
      border-radius: inherit;
    }

    /* === BOOK (CSS 3D) === */
    .book-wrap {
      perspective: 800px;
      cursor: pointer;
      flex-shrink: 0;
    }

    .book {
      width: 110px;
      height: 180px;
      position: relative;
      transform-style: preserve-3d;
      transform: rotateY(-20deg);
      transition: transform .45s cubic-bezier(.34,1.2,.64,1), filter .3s;
      filter: drop-shadow(6px 6px 14px rgba(0,0,0,.7));
    }
    .book-wrap:hover .book {
      transform: rotateY(0deg) translateY(-14px) scale(1.06);
      filter: drop-shadow(0 20px 30px rgba(0,0,0,.6));
    }

    /* Spine (visible part when tilted) */
    .book-spine {
      position: absolute;
      width: 26px;
      height: 100%;
      left: 0;
      transform-origin: right center;
      transform: rotateY(-90deg) translateX(-13px);
      background: inherit; /* inherit from .book */
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 3px 0 0 3px;
      font-family: 'Fredoka One', cursive;
      font-size: .6rem;
      color: rgba(255,255,255,.5);
      writing-mode: vertical-rl;
      text-orientation: mixed;
      overflow: hidden;
    }

    /* Front cover */
    .book-front {
      position: absolute;
      inset: 0;
      border-radius: 0 4px 4px 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: .5rem;
      padding: .75rem .5rem;
      overflow: hidden;
      backface-visibility: hidden;
    }
    .book-front::before {
      content:'';
      position:absolute;
      top:0; left:0; right:0;
      height:40%;
      background: rgba(255,255,255,.1);
      border-radius: 0 4px 0 0;
    }
    .book-front::after {
      content:'';
      position:absolute;
      left:8px; top:0; bottom:0; width:3px;
      background: rgba(0,0,0,.2);
      border-radius: 0;
    }

    .book-icon {
      font-size: 2.6rem;
      z-index: 1;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,.3));
      line-height: 1;
    }
    .book-title {
      font-family: 'Fredoka One', cursive;
      font-size: .82rem;
      color: rgba(255,255,255,.95);
      text-align: center;
      z-index: 1;
      line-height: 1.2;
      text-shadow: 0 1px 4px rgba(0,0,0,.4);
      word-break: break-word;
    }
    .book-count {
      position: absolute;
      bottom: 8px;
      right: 8px;
      font-size: .68rem;
      font-weight: 800;
      background: rgba(0,0,0,.3);
      color: rgba(255,255,255,.8);
      padding: 2px 6px;
      border-radius: 8px;
      z-index: 1;
    }

    /* BOOK SHINE EFFECT ON HOVER */
    .book-wrap:hover .book-front::before {
      background: rgba(255,255,255,.18);
    }

    /* === EMPTY STATE === */
    .empty-library {
      text-align: center;
      padding: 4rem 2rem;
      color: rgba(255,248,231,.4);
    }
    .empty-library .empty-icon { font-size: 5rem; display:block; margin-bottom:1rem; }
    .empty-library p { font-size: 1.1rem; }
    .empty-library a {
      color: var(--gold); font-weight: 700; text-decoration: none;
      border-bottom: 1px dashed var(--gold);
    }

    /* === DECORATIVE DUST PARTICLES === */
    .dust {
      position: fixed; bottom: 0; left: 0; right: 0;
      height: 200px; pointer-events: none; z-index: 0;
      background: linear-gradient(0deg, rgba(106,58,31,.15) 0%, transparent 100%);
    }

    /* === MODAL PAKET === */
    .modal-content {
      background: #1a2540;
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 16px;
      color: var(--cream);
    }
    .modal-header {
      border-bottom: 1px solid rgba(255,255,255,.1);
      padding: 1.25rem 1.5rem;
    }
    .modal-title {
      font-family: 'Fredoka One', cursive;
      color: var(--gold);
      font-size: 1.4rem;
    }
    .btn-close { filter: invert(1); }
    .modal-body { padding: 1.5rem; }

    .pkg-list { display: flex; flex-direction: column; gap: .75rem; }

    .pkg-item {
      display: flex; align-items: center; justify-content: space-between;
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 12px;
      padding: 1rem 1.25rem;
      cursor: pointer;
      transition: all .25s;
      text-decoration: none; color: var(--cream);
    }
    .pkg-item:hover {
      background: rgba(var(--accent),.15);
      border-color: var(--accent);
      transform: translateX(4px);
      color: var(--cream);
    }
    .pkg-name {
      font-weight: 700;
      font-size: 1rem;
    }
    .pkg-desc {
      font-size: .82rem;
      color: rgba(255,248,231,.5);
      margin-top: .2rem;
    }
    .pkg-arrow {
      font-size: 1.2rem;
      color: var(--accent);
      flex-shrink: 0;
    }

    #loading-pkg {
      text-align: center;
      padding: 2rem;
      color: rgba(255,248,231,.5);
    }

    .pkg-empty {
      text-align: center;
      padding: 2rem;
      color: rgba(255,248,231,.4);
    }
  </style>
</head>
<body class="<?= $meta['theme'] ?>">

<div class="dust"></div>

<!-- TOP NAV -->
<nav class="top-nav">
  <a href="index.php" class="back-btn">
    <span class="back-arrow">←</span> Ganti Jenjang
  </a>
  <div class="nav-title"><?= $meta['icon'] ?> Perpustakaan Kuis</div>
  <span class="level-badge"><?= $level ?></span>
</nav>

<!-- PAGE HEADING -->
<div class="page-heading" id="page-heading">
  <h2><?= $meta['icon'] ?> <?= htmlspecialchars($meta['label']) ?></h2>
  <p>Klik buku pelajaran yang ingin kamu kerjakan soalnya!</p>
</div>

<!-- LIBRARY -->
<div class="library-room" id="library-room">

<?php if(empty($categories)): ?>
  <div class="empty-library">
    <span class="empty-icon">📭</span>
    <p>Belum ada kategori untuk jenjang <strong><?= $level ?></strong>.</p>
    <p class="mt-2">Minta gurumu untuk <a href="admin.php">menambahkan di panel admin</a>.</p>
  </div>

<?php else:
  // Bagi kategori per rak (maks 5 buku per rak)
  $chunks = array_chunk($categories, 5);
  foreach($chunks as $shelfIdx => $shelf):
?>
  <div class="shelf-row" id="shelf-<?= $shelfIdx ?>">
    <div class="books-on-shelf">
      <?php foreach($shelf as $idx => $cat):
        $color   = $cat['color'] ?? $defaultColors[$idx % count($defaultColors)];
        $icon    = $cat['icon']  ?? getBookIcon($cat['name']);
        $pkgCount = (int)($cat['package_count'] ?? 0);
        // variasi tinggi buku supaya natural
        $heights = [180, 195, 170, 188, 175];
        $h = $heights[($shelfIdx * 5 + $idx) % count($heights)];
      ?>
      <div class="book-wrap" 
           data-cat-id="<?= $cat['id'] ?>"
           data-cat-name="<?= htmlspecialchars($cat['name']) ?>"
           data-cat-icon="<?= $icon ?>"
           onclick="openPackages(this)">
        <div class="book" style="background:<?= $color ?>; height:<?= $h ?>px">
          <div class="book-spine" style="background:<?= $color ?>; height:<?= $h ?>px"></div>
          <div class="book-front">
            <div class="book-icon"><?= $icon ?></div>
            <div class="book-title"><?= htmlspecialchars($cat['name']) ?></div>
            <?php if($pkgCount > 0): ?>
              <div class="book-count"><?= $pkgCount ?> paket</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="shelf-plank"></div>
  </div>
<?php endforeach; endif; ?>

</div><!-- /library-room -->

<!-- MODAL: Daftar Paket -->
<div class="modal fade" id="packageModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal-title">Pilih Paket</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="pkg-body">
          <div id="loading-pkg">⏳ Memuat paket...</div>
          <div class="pkg-list" id="pkg-list" style="display:none"></div>
          <div class="pkg-empty" id="pkg-empty" style="display:none">
            📭 Belum ada paket untuk mata pelajaran ini.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/gsap/gsap.min.js"></script>
<script>
/* === GSAP Entrance === */
window.addEventListener('DOMContentLoaded', ()=>{
  gsap.to('#page-heading', { opacity:1, y:0, duration:.7, ease:'power3.out', delay:.1 });

  const shelves = document.querySelectorAll('.shelf-row');
  shelves.forEach((shelf, i) => {
    gsap.to(shelf, {
      opacity:1, y:0, duration:.7, delay:.2 + i*.15,
      ease:'power3.out'
    });
    // stagger books inside
    const books = shelf.querySelectorAll('.book-wrap');
    gsap.from(books, {
      opacity:0, y:40, stagger:.08, duration:.5,
      delay:.35 + i*.15, ease:'back.out(1.4)'
    });
  });
});

/* === Open Packages Modal === */
const pkgModal = new bootstrap.Modal(document.getElementById('packageModal'));

function openPackages(el) {
  const catId   = el.dataset.catId;
  const catName = el.dataset.catName;
  const catIcon = el.dataset.catIcon;

  document.getElementById('modal-title').textContent = catIcon + ' ' + catName;
  document.getElementById('loading-pkg').style.display = 'block';
  document.getElementById('pkg-list').style.display = 'none';
  document.getElementById('pkg-empty').style.display = 'none';

  // book click animation
  gsap.to(el.querySelector('.book'), {
    scale: 1.12, duration: .15,
    yoyo:true, repeat:1, ease:'power2.inOut'
  });

  pkgModal.show();

  fetch('fetch_packages.php?category=' + catId)
    .then(r => r.json())
    .then(data => {
      document.getElementById('loading-pkg').style.display = 'none';
      const list = document.getElementById('pkg-list');
      list.innerHTML = '';

      if (!data.length) {
        document.getElementById('pkg-empty').style.display = 'block';
        return;
      }

      data.forEach(pkg => {
        const item = document.createElement('a');
        item.href = `quiz.php?package=${pkg.id}`;
        item.className = 'pkg-item';
        item.innerHTML = `
          <div>
            <div class="pkg-name">📦 ${pkg.name}</div>
            ${pkg.description ? `<div class="pkg-desc">${pkg.description}</div>` : ''}
          </div>
          <span class="pkg-arrow">→</span>
        `;
        list.appendChild(item);
      });

      list.style.display = 'flex';
      // animate items
      gsap.from(list.children, { opacity:0, x:-20, stagger:.07, duration:.35, ease:'power2.out' });
    })
    .catch(() => {
      document.getElementById('loading-pkg').style.display = 'none';
      document.getElementById('pkg-empty').textContent = '❌ Gagal memuat paket.';
      document.getElementById('pkg-empty').style.display = 'block';
    });
}
</script>
</body>
</html>
