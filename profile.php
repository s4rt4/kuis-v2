<?php
// profile.php — Halaman Profil & Edit untuk Siswa dan Guru
session_start();
require 'config.php';

// Wajib login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Admin punya halaman profil sendiri di admin.php
if ($_SESSION['user_role'] === 'admin') {
    header('Location: admin.php');
    exit;
}

$userId   = $_SESSION['user_id'];
$role     = $_SESSION['user_role'];     // teacher | student
$level    = strtoupper($_SESSION['user_level'] ?? 'SD');
$name     = $_SESSION['user_name']     ?? '';
$username = $_SESSION['user_username'] ?? '';
$avatar   = $_SESSION['user_avatar']   ?? '';

// Warna tema per role/level
$levelMeta = [
    'SD'  => ['color' => '#f97316', 'gradient' => 'linear-gradient(135deg, #f97316, #ef4444)'],
    'SMP' => ['color' => '#06b6d4', 'gradient' => 'linear-gradient(135deg, #06b6d4, #3b82f6)'],
    'SMA' => ['color' => '#8b5cf6', 'gradient' => 'linear-gradient(135deg, #8b5cf6, #ec4899)'],
];
$meta = $levelMeta[$level] ?? $levelMeta['SD'];
if ($role === 'teacher') {
    // Teachers get a neutral professional theme regardless of level
    $meta = ['color' => '#10b981', 'gradient' => 'linear-gradient(135deg, #10b981, #3b82f6)'];
}

$backUrl   = $role === 'student' ? 'student.php' : 'admin.php';
$backLabel = $role === 'student' ? 'Dashboard' : 'Panel Admin';
$roleLabel = $role === 'student' ? '🎓 Siswa ' . $level : '👩‍🏫 Guru ' . $level;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Profil Saya — Kuis Pintar</title>
  <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    :root {
      --accent:   <?= $meta['color'] ?>;
      --gradient: <?= $meta['gradient'] ?>;
      --bg:       #f0f4f8;
      --card:     #ffffff;
      --text:     #1e293b;
      --muted:    #64748b;
      --radius:   18px;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Nunito', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

    /* ─── TOPBAR ─── */
    .top-nav {
      background: #fff;
      border-bottom: 3px solid transparent;
      border-image: var(--gradient) 1;
      padding: .85rem 1.5rem;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 50;
      box-shadow: 0 2px 12px rgba(0,0,0,.07);
    }
    .brand { font-family: 'Fredoka One', cursive; font-size: 1.3rem;
      background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .back-btn {
      display: flex; align-items: center; gap: .5rem;
      font-weight: 700; font-size: .9rem; color: var(--muted);
      text-decoration: none; transition: color .2s;
    }
    .back-btn:hover { color: var(--accent); }

    /* ─── HERO ─── */
    .hero {
      background: var(--gradient);
      padding: 3rem 1.5rem 5rem;
      text-align: center;
      position: relative;
    }
    .hero::before {
      content: '';
      position: absolute; inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .hero-avatar-wrap {
      position: relative;
      display: inline-block;
      margin-bottom: 1rem;
      cursor: pointer;
    }
    .hero-avatar {
      width: 110px; height: 110px;
      border-radius: 50%; object-fit: cover;
      border: 4px solid rgba(255,255,255,.6);
      box-shadow: 0 8px 28px rgba(0,0,0,.2);
      transition: opacity .2s;
    }
    .hero-avatar-wrap:hover .hero-avatar { opacity: .85; }
    .avatar-change-badge {
      position: absolute; bottom: 4px; right: 4px;
      background: #fff;
      color: var(--accent);
      border-radius: 50%;
      width: 30px; height: 30px;
      display: flex; align-items: center; justify-content: center;
      font-size: .85rem;
      box-shadow: 0 2px 8px rgba(0,0,0,.15);
      pointer-events: none;
    }
    .hero-name { font-family:'Fredoka One',cursive; font-size: 1.9rem; color:#fff; margin-bottom:.3rem; }
    .hero-role { color: rgba(255,255,255,.8); font-size:.95rem; font-weight:700; }

    /* ─── WAVE ─── */
    .wave { display:block; margin-top:-2px; fill:var(--bg); }

    /* ─── CARD FORM ─── */
    .profile-card {
      max-width: 560px;
      margin: -2rem auto 3rem;
      background: var(--card);
      border-radius: var(--radius);
      box-shadow: 0 8px 32px rgba(0,0,0,.1);
      padding: 2rem;
    }
    .section-label {
      font-family: 'Fredoka One', cursive;
      font-size: 1rem; color: var(--accent);
      text-transform: uppercase; letter-spacing: .5px;
      margin-bottom: 1rem;
      display: flex; align-items: center; gap: .5rem;
    }
    .form-label { font-weight: 700; font-size:.9rem; }
    .form-control { border-radius: 10px; border: 1.5px solid #e2e8f0; padding: .65rem 1rem; font-family:'Nunito',sans-serif; }
    .form-control:focus { border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 12%, transparent); outline: none; }
    .form-hint { font-size: .78rem; color: var(--muted); margin-top: .25rem; }

    /* Password toggle */
    .pw-wrap { position: relative; }
    .pw-toggle {
      position: absolute; right: .75rem; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; color: var(--muted);
      font-size: .9rem; padding: 0;
    }
    .pw-toggle:hover { color: var(--accent); }

    /* Divider */
    .section-divider { border: none; border-top: 1.5px solid #f1f5f9; margin: 1.5rem 0; }

    /* Save button */
    .btn-save {
      width: 100%;
      background: var(--gradient);
      color: #fff; border: none;
      padding: .85rem; border-radius: 50px;
      font-family: 'Fredoka One', cursive;
      font-size: 1.1rem;
      cursor: pointer;
      box-shadow: 0 4px 16px rgba(0,0,0,.15);
      transition: all .25s;
    }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.2); }
    .btn-save:disabled { opacity: .6; cursor: not-allowed; transform: none; }

    /* Toast */
    .toast-float {
      position: fixed; top: 1.25rem; right: 1.25rem;
      z-index: 9999; min-width: 260px;
    }
    .toast-box {
      padding: .75rem 1.1rem; border-radius: 12px;
      font-weight: 700; font-size: .9rem;
      display: flex; align-items: center; gap: .6rem;
      box-shadow: 0 4px 18px rgba(0,0,0,.12);
      animation: slideIn .3s ease;
    }
    .toast-success { background:#d1fae5; color:#065f46; }
    .toast-error   { background:#fee2e2; color:#991b1b; }
    @keyframes slideIn { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
  </style>
</head>
<body>

<!-- TOPBAR -->
<nav class="top-nav">
  <a href="<?= $backUrl ?>" class="back-btn">
    <i class="fa fa-arrow-left"></i> <?= $backLabel ?>
  </a>
  <span class="brand"><i class="fa fa-graduation-cap"></i> Kuis Pintar</span>
  <a href="logout.php" onclick="return confirm('Yakin ingin keluar?')"
     style="font-size:.82rem;font-weight:700;color:#f87171;text-decoration:none;">
    <i class="fa fa-right-from-bracket"></i> Keluar
  </a>
</nav>

<!-- HERO -->
<div class="hero">
  <label class="hero-avatar-wrap" for="avatar-input" title="Klik untuk ganti foto">
    <img id="avatar-preview"
         src="<?= htmlspecialchars(!empty($avatar) && file_exists($avatar) ? $avatar : 'assets/png/avatar.png') ?>?v=<?= time() ?>"
         alt="Foto Profil" class="hero-avatar">
    <div class="avatar-change-badge"><i class="fa fa-camera"></i></div>
  </label>
  <input type="file" id="avatar-input" accept="image/*" style="display:none">
  <div class="hero-name"><?= htmlspecialchars($name ?: $username) ?></div>
  <div class="hero-role"><?= $roleLabel ?></div>
</div>
<svg viewBox="0 0 1440 48" class="wave" xmlns="http://www.w3.org/2000/svg">
  <path d="M0,32 C360,0 1080,64 1440,16 L1440,48 L0,48 Z"/>
</svg>

<!-- FORM CARD -->
<div class="profile-card" id="profile-card">
  <p class="section-label"><i class="fa fa-user-pen"></i> Informasi Akun</p>

  <div class="mb-3">
    <label class="form-label" for="inp-username">Username <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="inp-username"
           value="<?= htmlspecialchars($username) ?>" autocomplete="username">
    <p class="form-hint">Digunakan untuk login. Tidak boleh sama dengan pengguna lain.</p>
  </div>

  <div class="mb-3">
    <label class="form-label" for="inp-name">Nama Lengkap <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="inp-name"
           value="<?= htmlspecialchars($name) ?>" autocomplete="name">
  </div>

  <hr class="section-divider">
  <p class="section-label"><i class="fa fa-lock"></i> Ganti Password</p>

  <div class="mb-3">
    <label class="form-label" for="inp-password">Password Baru</label>
    <div class="pw-wrap">
      <input type="password" class="form-control" id="inp-password"
             placeholder="Kosongkan jika tidak ingin diubah" autocomplete="new-password">
      <button type="button" class="pw-toggle" onclick="togglePw('inp-password', this)">
        <i class="fa fa-eye"></i>
      </button>
    </div>
  </div>

  <div class="mb-4">
    <label class="form-label" for="inp-password-confirm">Konfirmasi Password Baru</label>
    <div class="pw-wrap">
      <input type="password" class="form-control" id="inp-password-confirm"
             placeholder="Ulangi password baru" autocomplete="new-password">
      <button type="button" class="pw-toggle" onclick="togglePw('inp-password-confirm', this)">
        <i class="fa fa-eye"></i>
      </button>
    </div>
  </div>

  <button class="btn-save" id="btn-save" onclick="saveProfile()">
    <i class="fa fa-save"></i> Simpan Perubahan
  </button>
</div>

<!-- Toast container -->
<div class="toast-float" id="toast-float"></div>

<script>
// ─── Avatar Preview ───────────────────────────────────────────────
document.getElementById('avatar-input').addEventListener('change', function() {
  if (this.files && this.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { document.getElementById('avatar-preview').src = e.target.result; };
    reader.readAsDataURL(this.files[0]);
  }
});

// ─── Password Toggle ─────────────────────────────────────────────
function togglePw(inputId, btn) {
  const inp = document.getElementById(inputId);
  const icon = btn.querySelector('i');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.className = 'fa fa-eye-slash';
  } else {
    inp.type = 'password';
    icon.className = 'fa fa-eye';
  }
}

// ─── Toast ───────────────────────────────────────────────────────
function toast(msg, type = 'success') {
  const container = document.getElementById('toast-float');
  const el = document.createElement('div');
  el.className = 'toast-box toast-' + type;
  el.innerHTML = `<i class="fa ${type === 'success' ? 'fa-check-circle' : 'fa-circle-xmark'}"></i> ${msg}`;
  container.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

// ─── Save Profile ─────────────────────────────────────────────────
async function saveProfile() {
  const username  = document.getElementById('inp-username').value.trim();
  const name      = document.getElementById('inp-name').value.trim();
  const password  = document.getElementById('inp-password').value;
  const passConf  = document.getElementById('inp-password-confirm').value;
  const avatarEl  = document.getElementById('avatar-input');

  if (!username) { toast('Username wajib diisi', 'error'); return; }
  if (!name)     { toast('Nama lengkap wajib diisi', 'error'); return; }
  if (password && password !== passConf) {
    toast('Password baru dan konfirmasi tidak cocok', 'error'); return;
  }
  if (password && password.length < 6) {
    toast('Password minimal 6 karakter', 'error'); return;
  }

  const btn = document.getElementById('btn-save');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Menyimpan...';

  const fd = new FormData();
  fd.append('action',       'update_profile');
  fd.append('username',     username);
  fd.append('display_name', name);
  if (password) fd.append('password', password);
  if (avatarEl.files.length > 0) fd.append('avatar', avatarEl.files[0]);

  try {
    const res  = await fetch('api_profile.php', { method: 'POST', body: fd });
    const text = await res.text();
    let json;
    try { json = JSON.parse(text); } catch(e) {
      toast('Server error: ' + text.substring(0, 80), 'error');
      btn.disabled = false; btn.innerHTML = '<i class="fa fa-save"></i> Simpan Perubahan';
      return;
    }

    if (json.success) {
      toast('Profil berhasil diperbarui! ✅');
      // Update avatar preview with fresh URL if changed
      if (json.avatar_url) {
        document.getElementById('avatar-preview').src = json.avatar_url + '?v=' + Date.now();
      }
      // Clear password fields
      document.getElementById('inp-password').value = '';
      document.getElementById('inp-password-confirm').value = '';
      avatarEl.value = '';
      // Give the toast 1.5s then reload to refresh session name in topbar & hero
      setTimeout(() => location.reload(), 1500);
    } else {
      toast(json.message || 'Gagal menyimpan', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-save"></i> Simpan Perubahan';
    }
  } catch(e) {
    toast('Gagal terhubung ke server: ' + e.message, 'error');
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-save"></i> Simpan Perubahan';
  }
}
</script>
</body>
</html>
