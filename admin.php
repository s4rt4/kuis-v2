<?php
// admin.php — Panel Admin Modern
session_start();
require 'config.php';

// Auth Guard
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header("Location: login.php");
    exit;
}

// Ambil data awal untuk dropdown
$allCats = $db->query("SELECT * FROM categories ORDER BY level, sort_order, name")->fetchAll();
$allPkgs = $db->query("SELECT p.*, c.name as cat_name FROM packages p LEFT JOIN categories c ON c.id=p.category_id ORDER BY c.name, p.name")->fetchAll();

// Stats untuk dashboard cards
$statCats = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$statPkgs = $db->query("SELECT COUNT(*) FROM packages")->fetchColumn();
$statQs   = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$statSess = $db->query("SELECT COUNT(*) FROM quiz_sessions")->fetchColumn();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Panel — Kuis Pintar</title>

  <!-- Bootstrap -->
  <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root {
      --sidebar-w: 260px;
      --primary:   #4f46e5;
      --primary-d: #3730a3;
      --success:   #10b981;
      --danger:    #ef4444;
      --warning:   #f59e0b;
      --info:      #3b82f6;
      --bg:        #f1f5f9;
      --sidebar-bg:#1e1b4b;
      --card-bg:   #ffffff;
      --text:      #1e293b;
      --muted:     #64748b;
      --border:    #e2e8f0;
      --radius:    14px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    /* =========================================
       LAYOUT
    ========================================= */
    .admin-layout {
      display: flex;
      min-height: 100vh;
    }

    /* =========================================
       SIDEBAR
    ========================================= */
    .sidebar {
      width: var(--sidebar-w);
      background: var(--sidebar-bg);
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0; left: 0; bottom: 0;
      z-index: 100;
      overflow-y: auto;
      transition: transform .3s;
    }

    .sidebar-brand {
      padding: 1.5rem 1.25rem 1.25rem;
      border-bottom: 1px solid rgba(255,255,255,.08);
    }
    .sidebar-brand .brand-name {
      font-family: 'Fredoka One', cursive;
      font-size: 1.35rem;
      color: #fff;
      display: flex;
      align-items: center;
      gap: .6rem;
    }
    .sidebar-brand .brand-name i {
      color: #818cf8;
    }
    .sidebar-brand small {
      color: rgba(255,255,255,.4);
      font-size: .75rem;
      margin-top: .2rem;
      display: block;
    }

    .sidebar-section {
      padding: 1rem .75rem .25rem;
      font-size: .68rem;
      font-weight: 800;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: rgba(255,255,255,.3);
    }

    .nav-item { margin: .15rem .75rem; }

    .nav-link {
      display: flex;
      align-items: center;
      gap: .75rem;
      padding: .65rem 1rem;
      border-radius: 10px;
      color: rgba(255,255,255,.65);
      font-weight: 600;
      font-size: .9rem;
      text-decoration: none;
      transition: all .2s;
      cursor: pointer;
      border: none;
      background: none;
      width: 100%;
    }
    .nav-link i { width: 18px; text-align: center; font-size: .95rem; }
    .nav-link:hover {
      background: rgba(255,255,255,.08);
      color: #fff;
    }
    .nav-link.active {
      background: var(--primary);
      color: #fff;
      box-shadow: 0 4px 12px rgba(79,70,229,.4);
    }

    .sidebar-footer {
      margin-top: auto;
      padding: 1rem .75rem;
      border-top: 1px solid rgba(255,255,255,.08);
    }
    .sidebar-footer a {
      display: flex; align-items: center; gap: .6rem;
      color: rgba(255,255,255,.45); font-size: .85rem;
      text-decoration: none; padding: .5rem 1rem; border-radius: 8px;
      transition: all .2s;
    }
    .sidebar-footer a:hover { color: #fff; background: rgba(255,255,255,.07); }

    /* =========================================
       MAIN CONTENT
    ========================================= */
    .main-content {
      margin-left: var(--sidebar-w);
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    /* TOPBAR */
    .topbar {
      background: #fff;
      border-bottom: 1px solid var(--border);
      padding: .85rem 1.75rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky; top: 0; z-index: 50;
      box-shadow: 0 1px 6px rgba(0,0,0,.06);
    }
    .topbar-title {
      font-family: 'Fredoka One', cursive;
      font-size: 1.3rem;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: .5rem;
    }
    .topbar-title i { color: var(--primary); }

    .topbar-right {
      display: flex; align-items: center; gap: 1rem;
    }
    .topbar-right a {
      color: var(--muted); text-decoration: none; font-size: .85rem;
      font-weight: 700; display: flex; align-items: center; gap: .4rem;
      transition: color .2s;
    }
    .topbar-right a:hover { color: var(--primary); }

    /* PAGE WRAPPER */
    .page-wrapper {
      padding: 1.75rem;
      flex: 1;
    }

    /* =========================================
       STAT CARDS
    ========================================= */
    .stat-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
      gap: 1.1rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: var(--card-bg);
      border-radius: var(--radius);
      padding: 1.35rem 1.5rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      box-shadow: 0 1px 8px rgba(0,0,0,.06);
      border: 1px solid var(--border);
    }
    .stat-icon {
      width: 52px; height: 52px;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.4rem;
      flex-shrink: 0;
    }
    .stat-info { flex: 1; }
    .stat-value {
      font-family: 'Fredoka One', cursive;
      font-size: 1.9rem;
      line-height: 1;
      color: var(--text);
    }
    .stat-label {
      font-size: .8rem;
      font-weight: 700;
      color: var(--muted);
      margin-top: .15rem;
    }

    /* =========================================
       SECTION PANELS
    ========================================= */
    .panel {
      background: var(--card-bg);
      border-radius: var(--radius);
      border: 1px solid var(--border);
      box-shadow: 0 1px 8px rgba(0,0,0,.05);
      margin-bottom: 1.5rem;
      display: none;
    }
    .panel.active { display: block; }

    .panel-header {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: .75rem;
    }
    .panel-title {
      font-weight: 800;
      font-size: 1.05rem;
      display: flex;
      align-items: center;
      gap: .6rem;
      color: var(--text);
    }
    .panel-title i { color: var(--primary); }

    .panel-body { padding: 1.25rem 1.5rem; overflow-x: auto; }

    /* =========================================
       BUTTONS
    ========================================= */
    .btn-primary-custom {
      background: var(--primary);
      color: #fff;
      border: none;
      padding: .5rem 1.1rem;
      border-radius: 10px;
      font-weight: 700;
      font-size: .88rem;
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      cursor: pointer;
      transition: all .2s;
    }
    .btn-primary-custom:hover { background: var(--primary-d); transform: translateY(-1px); }

    .btn-success-custom {
      background: var(--success);
      color: #fff;
      border: none;
      padding: .5rem 1.1rem;
      border-radius: 10px;
      font-weight: 700;
      font-size: .88rem;
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      cursor: pointer;
      transition: all .2s;
    }
    .btn-success-custom:hover { opacity: .88; transform: translateY(-1px); }

    .btn-icon {
      border: none; border-radius: 8px;
      width: 32px; height: 32px;
      display: inline-flex; align-items: center; justify-content: center;
      font-size: .8rem; cursor: pointer; transition: all .2s;
    }
    .btn-edit  { background: #eff6ff; color: var(--info);    }
    .btn-del   { background: #fef2f2; color: var(--danger);  }
    .btn-edit:hover { background: var(--info);    color: #fff; }
    .btn-del:hover  { background: var(--danger);  color: #fff; }

    /* =========================================
       DATATABLES OVERRIDES
    ========================================= */
    .dataTables_wrapper .dataTables_filter input {
      border: 1.5px solid var(--border);
      border-radius: 8px;
      padding: .35rem .75rem;
      font-size: .88rem;
      outline: none;
      font-family: 'Nunito', sans-serif;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
      border-color: var(--primary);
    }
    .dataTables_wrapper .dataTables_length select {
      border: 1.5px solid var(--border);
      border-radius: 8px;
      padding: .3rem .5rem;
      font-family: 'Nunito', sans-serif;
    }
    table.dataTable thead th {
      background: #f8fafc;
      font-size: .8rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .5px;
      color: var(--muted);
      border-bottom: 2px solid var(--border) !important;
    }
    table.dataTable tbody tr { font-size: .9rem; }
    table.dataTable tbody tr:hover { background: #f8fafc !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: var(--primary) !important;
      border-color: var(--primary) !important;
      color: #fff !important;
      border-radius: 8px;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
      background: #eff6ff !important;
      border-color: #eff6ff !important;
      color: var(--primary) !important;
      border-radius: 8px;
    }

    /* =========================================
       BADGES / PILLS
    ========================================= */
    .badge-level {
      font-size: .7rem; font-weight: 800;
      padding: .25rem .6rem;
      border-radius: 20px;
      letter-spacing: .5px;
    }
    .badge-sd  { background: #fee2e2; color: #b91c1c; }
    .badge-smp { background: #d1fae5; color: #065f46; }
    .badge-sma { background: #ede9fe; color: #5b21b6; }
    .badge-correct {
      font-size: .75rem; font-weight: 800;
      padding: .25rem .55rem; border-radius: 6px;
      background: #d1fae5; color: #065f46;
    }

    .color-dot {
      display: inline-block;
      width: 12px; height: 12px;
      border-radius: 50%;
      margin-right: 6px;
      vertical-align: middle;
    }

    /* =========================================
       MODAL
    ========================================= */
    .modal-content {
      border: none;
      border-radius: var(--radius);
      box-shadow: 0 20px 60px rgba(0,0,0,.15);
    }
    .modal-header {
      background: var(--primary);
      color: #fff;
      border-radius: var(--radius) var(--radius) 0 0;
      padding: 1rem 1.5rem;
    }
    .modal-title { font-weight: 800; font-size: 1rem; }
    .btn-close-white { filter: invert(1); }
    .modal-body { padding: 1.5rem; }
    .modal-footer { padding: .75rem 1.5rem; border-top: 1px solid var(--border); }

    .form-label { font-weight: 700; font-size: .85rem; margin-bottom: .3rem; }
    .form-control, .form-select {
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-family: 'Nunito', sans-serif;
      font-size: .9rem;
      padding: .5rem .85rem;
      transition: border-color .2s;
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79,70,229,.1);
    }

    /* Option buttons A B C D */
    .option-row { display: grid; gap: .6rem; }
    .option-label {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 28px; height: 28px;
      border-radius: 8px;
      font-weight: 800; font-size: .8rem;
      flex-shrink: 0;
    }
    .opt-a { background: #fee2e2; color: #b91c1c; }
    .opt-b { background: #fef9c3; color: #854d0e; }
    .opt-c { background: #d1fae5; color: #065f46; }
    .opt-d { background: #dbeafe; color: #1d4ed8; }

    .correct-radios {
      display: flex; gap: .5rem; flex-wrap: wrap;
    }
    .correct-radios input[type=radio] { display: none; }
    .correct-radios label {
      padding: .35rem .9rem;
      border-radius: 8px;
      border: 2px solid var(--border);
      font-weight: 800; font-size: .85rem;
      cursor: pointer; transition: all .2s;
    }
    .correct-radios input[type=radio]:checked + label {
      border-color: var(--success);
      background: #d1fae5;
      color: #065f46;
    }

    /* Import JSON area */
    .import-dropzone {
      border: 2px dashed var(--border);
      border-radius: var(--radius);
      padding: 2rem;
      text-align: center;
      color: var(--muted);
      cursor: pointer;
      transition: all .25s;
    }
    .import-dropzone:hover, .import-dropzone.drag-over {
      border-color: var(--primary);
      background: #eff6ff;
      color: var(--primary);
    }
    .import-dropzone i { font-size: 2.5rem; display: block; margin-bottom: .5rem; }

    /* =========================================
       PASTE EDITOR
    ========================================= */
    .paste-toggle-btn {
      width: 36px; height: 36px;
      border-radius: 10px;
      border: 1.5px solid var(--border);
      background: #f8fafc;
      color: var(--muted);
      display: flex; align-items: center; justify-content: center;
      font-size: .95rem;
      cursor: pointer;
      transition: all .2s;
    }
    .paste-toggle-btn:hover,
    .paste-toggle-btn.active {
      background: var(--primary);
      border-color: var(--primary);
      color: #fff;
      box-shadow: 0 4px 12px rgba(79,70,229,.3);
    }

    /* Slide-down wrapper */
    #paste-editor-wrap {
      max-height: 0;
      overflow: hidden;
      transition: max-height .4s cubic-bezier(.4,0,.2,1);
      border-top: 0px solid var(--border);
    }
    #paste-editor-wrap.open {
      max-height: 620px;
      border-top: 1px solid var(--border);
    }

    #paste-editor-inner {
      display: flex;
      flex-direction: column;
    }

    .paste-editor-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: .85rem 1.5rem;
      background: #1e293b;
      color: #e2e8f0;
      font-weight: 700;
      font-size: .9rem;
    }
    .paste-close-btn {
      background: rgba(255,255,255,.1);
      border: none;
      color: #e2e8f0;
      width: 28px; height: 28px;
      border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer;
      transition: background .2s;
    }
    .paste-close-btn:hover { background: rgba(255,255,255,.2); }

    .paste-editor-body {
      padding: 1.25rem 1.5rem;
      background: #0f172a;
    }

    .paste-top-bar {
      display: flex;
      gap: 1rem;
      align-items: flex-start;
      margin-bottom: .85rem;
    }
    .paste-top-bar .form-label { color: #94a3b8; }
    .paste-top-bar .form-select {
      background: #1e293b;
      border-color: #334155;
      color: #e2e8f0;
      font-size: .85rem;
    }
    .paste-top-bar .form-select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79,70,229,.2);
    }

    .editor-container {
      display: flex;
      border: 1.5px solid #334155;
      border-radius: 10px;
      overflow: hidden;
      height: 280px;
      background: #0d1117;
    }

    .line-numbers {
      background: #161b22;
      color: #484f58;
      font-family: 'Consolas', 'Monaco', monospace;
      font-size: .8rem;
      line-height: 1.6;
      padding: .75rem .6rem;
      text-align: right;
      min-width: 42px;
      user-select: none;
      overflow: hidden;
      border-right: 1px solid #21262d;
      white-space: pre;
    }

    .paste-textarea {
      flex: 1;
      background: #0d1117;
      color: #e6edf3;
      border: none;
      outline: none;
      font-family: 'Consolas', 'Monaco', monospace;
      font-size: .82rem;
      line-height: 1.6;
      padding: .75rem 1rem;
      resize: none;
      height: 100%;
      tab-size: 2;
      white-space: pre;
      overflow-x: auto;
    }
    .paste-textarea::placeholder { color: #484f58; }

    .paste-statusbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: .6rem;
      font-size: .75rem;
      font-family: 'Consolas', monospace;
    }
    #paste-status-valid.valid   { color: #4ade80; }
    #paste-status-valid.invalid { color: #f87171; }

    .paste-editor-footer {
      display: flex;
      justify-content: flex-end;
      gap: .75rem;
      padding: 1rem 1.5rem;
      background: #1e293b;
      border-top: 1px solid #334155;
    }
    .paste-editor-footer .btn-light {
      background: rgba(255,255,255,.1);
      border: none; color: #e2e8f0; font-size: .85rem;
    }
    .paste-editor-footer .btn-light:hover { background: rgba(255,255,255,.18); }

    /* Alert messages */
    .toast-container {
      position: fixed;
      top: 1.25rem; right: 1.25rem;
      z-index: 9999;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.open { transform: translateX(0); }
      .main-content { margin-left: 0; }
    }
  </style>
</head>
<body>

<div class="admin-layout">

  <!-- ===================== SIDEBAR ===================== -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-name">
        <i class="fa fa-graduation-cap"></i> Kuis Pintar
      </div>
      <small>Panel Manajemen Konten</small>
    </div>

    <div class="sidebar-section">Menu Utama</div>
    <div class="nav-item">
      <button class="nav-link active" data-panel="dashboard" onclick="showPanel('dashboard', this)">
        <i class="fa fa-gauge-high"></i> Dashboard
      </button>
    </div>

    <div class="sidebar-section">Kelola Konten</div>
    <div class="nav-item">
      <button class="nav-link" data-panel="categories" onclick="showPanel('categories', this)">
        <i class="fa fa-folder-tree"></i> Mata Pelajaran
      </button>
    </div>
    <div class="nav-item">
      <button class="nav-link" data-panel="packages" onclick="showPanel('packages', this)">
        <i class="fa fa-layer-group"></i> Paket Soal
      </button>
    </div>
    <div class="nav-item">
      <button class="nav-link" data-panel="questions" onclick="showPanel('questions', this)">
        <i class="fa fa-circle-question"></i> Bank Soal
      </button>
    </div>
    <div class="nav-item">
      <button class="nav-link" data-panel="import" onclick="showPanel('import', this)">
        <i class="fa fa-file-import"></i> Import JSON
      </button>
    </div>

    <?php if ($_SESSION['user_role'] === 'admin'): ?>
    <div class="sidebar-section">Sistem</div>
    <div class="nav-item">
      <button class="nav-link" data-panel="users" onclick="showPanel('users', this)">
        <i class="fa fa-users-gear"></i> Kelola Pengguna
      </button>
    </div>
    <?php endif; ?>

    <div class="sidebar-footer">
      <a href="index.php" target="_blank">
        <i class="fa fa-arrow-up-right-from-square"></i> Lihat Halaman Kuis
      </a>
    </div>
  </aside>

  <!-- ===================== MAIN ===================== -->
  <div class="main-content">

    <!-- TOPBAR -->
    <div class="topbar">
      <div class="topbar-title">
        <i class="fa fa-gauge-high" id="topbar-icon"></i>
        <span id="topbar-label">Dashboard</span>
      </div>
      <div class="topbar-right">
        <a href="javascript:void(0)" onclick="openProfileModal()">
          <i class="fa fa-user-circle"></i> Profil Saya
        </a>
        <a href="index.php" target="_blank" style="margin-left:.75rem">
          <i class="fa fa-eye"></i> Preview Kuis
        </a>
      </div>
    </div>

    <div class="page-wrapper">

      <!-- ===================== DASHBOARD ===================== -->
      <div class="panel active" id="panel-dashboard">
        <div class="panel-body" style="padding:0">
          <!-- Stat Cards -->
          <div class="stat-grid" style="padding:1.5rem 1.5rem 0">
            <div class="stat-card">
              <div class="stat-icon" style="background:#ede9fe;color:#7c3aed">
                <i class="fa fa-folder-tree"></i>
              </div>
              <div class="stat-info">
                <div class="stat-value"><?= $statCats ?></div>
                <div class="stat-label">Mata Pelajaran</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon" style="background:#dbeafe;color:#2563eb">
                <i class="fa fa-layer-group"></i>
              </div>
              <div class="stat-info">
                <div class="stat-value"><?= $statPkgs ?></div>
                <div class="stat-label">Paket Soal</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon" style="background:#d1fae5;color:#059669">
                <i class="fa fa-circle-question"></i>
              </div>
              <div class="stat-info">
                <div class="stat-value"><?= $statQs ?></div>
                <div class="stat-label">Total Soal</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon" style="background:#fef3c7;color:#d97706">
                <i class="fa fa-gamepad"></i>
              </div>
              <div class="stat-info">
                <div class="stat-value"><?= $statSess ?></div>
                <div class="stat-label">Sesi Kuis</div>
              </div>
            </div>
          </div>

          <!-- Quick actions -->
          <div style="padding:1.5rem">
            <p class="fw-bold mb-3" style="color:var(--muted);font-size:.85rem;text-transform:uppercase;letter-spacing:1px">
              Aksi Cepat
            </p>
            <div style="display:flex;gap:.75rem;flex-wrap:wrap">
              <button class="btn-primary-custom" onclick="showPanel('categories', document.querySelector('[data-panel=categories]'))">
                <i class="fa fa-plus"></i> Tambah Mata Pelajaran
              </button>
              <button class="btn-primary-custom" onclick="showPanel('packages', document.querySelector('[data-panel=packages]'))">
                <i class="fa fa-plus"></i> Tambah Paket
              </button>
              <button class="btn-success-custom" onclick="showPanel('questions', document.querySelector('[data-panel=questions]'))">
                <i class="fa fa-plus"></i> Tambah Soal
              </button>
              <button class="btn-success-custom" style="background:#0ea5e9" onclick="showPanel('import', document.querySelector('[data-panel=import]'))">
                <i class="fa fa-file-import"></i> Import JSON
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ===================== CATEGORIES ===================== -->
      <div class="panel" id="panel-categories">
        <div class="panel-header">
          <div class="panel-title">
            <i class="fa fa-folder-tree"></i> Mata Pelajaran
          </div>
          <button class="btn-primary-custom" onclick="openCatModal()">
            <i class="fa fa-plus"></i> Tambah
          </button>
        </div>
        <div class="panel-body">
          <table id="tbl-categories" class="table table-hover w-100">
            <thead>
              <tr>
                <th>#</th>
                <th>Nama</th>
                <th>Jenjang</th>
                <th>Warna</th>
                <th>Urutan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($allCats as $i => $c): ?>
              <tr id="cat-row-<?= $c['id'] ?>">
                <td><?= $i+1 ?></td>
                <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                <td>
                  <span class="badge-level badge-<?= strtolower($c['level']) ?>">
                    <?= $c['level'] ?>
                  </span>
                </td>
                <td>
                  <?php if($c['color']): ?>
                    <span class="color-dot" style="background:<?= $c['color'] ?>"></span>
                    <code style="font-size:.78rem"><?= $c['color'] ?></code>
                  <?php else: echo '—'; endif; ?>
                </td>
                <td><?= $c['sort_order'] ?></td>
                <td>
                  <button class="btn-icon btn-edit" title="Edit"
                    onclick="editCat(<?= htmlspecialchars(json_encode($c)) ?>)">
                    <i class="fa fa-pen"></i>
                  </button>
                  <button class="btn-icon btn-del" title="Hapus"
                    onclick="deleteCat(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name']) ?>')">
                    <i class="fa fa-trash"></i>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ===================== PACKAGES ===================== -->
      <div class="panel" id="panel-packages">
        <div class="panel-header">
          <div class="panel-title">
            <i class="fa fa-layer-group"></i> Paket Soal
          </div>
          <button class="btn-primary-custom" onclick="openPkgModal()">
            <i class="fa fa-plus"></i> Tambah Paket
          </button>
        </div>
        <div class="panel-body">
          <table id="tbl-packages" class="table table-hover w-100">
            <thead>
              <tr>
                <th>#</th>
                <th>Nama Paket</th>
                <th>Mata Pelajaran</th>
                <th>Deskripsi</th>
                <th>Timer</th>
                <th>Acak</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody id="tbody-packages">
              <!-- diisi JS -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- ===================== QUESTIONS ===================== -->
      <div class="panel" id="panel-questions">
        <div class="panel-header">
          <div class="panel-title">
            <i class="fa fa-circle-question"></i> Bank Soal
          </div>
          <button class="btn-primary-custom" onclick="openQModal()">
            <i class="fa fa-plus"></i> Tambah Soal
          </button>
        </div>
        <div class="panel-body">
          <table id="tbl-questions" class="table table-hover w-100">
            <thead>
              <tr>
                <th>#</th>
                <th>Soal</th>
                <th>Paket</th>
                <th>Mata Pelajaran</th>
                <th>Jawaban</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody id="tbody-questions">
              <!-- diisi JS -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- ===================== IMPORT JSON ===================== -->
      <div class="panel" id="panel-import">
        <div class="panel-header">
          <div class="panel-title">
            <i class="fa fa-file-import"></i> Import Soal via JSON
          </div>
          <!-- Tombol tersembunyi paste JSON -->
          <button class="paste-toggle-btn" id="paste-toggle-btn" onclick="togglePasteEditor()" title="Paste JSON langsung">
            <i class="fa fa-clipboard-list"></i>
          </button>
        </div>

        <!-- PASTE EDITOR — tersembunyi by default -->
        <div id="paste-editor-wrap">
          <div id="paste-editor-inner">
            <div class="paste-editor-header">
              <span><i class="fa fa-code me-2"></i>Paste JSON Langsung</span>
              <button class="paste-close-btn" onclick="togglePasteEditor()">
                <i class="fa fa-xmark"></i>
              </button>
            </div>
            <div class="paste-editor-body">
              <div class="paste-top-bar">
                <div class="mb-2" style="flex:1">
                  <label class="form-label" style="font-size:.8rem">Paket Tujuan <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" id="paste-package">
                    <option value="">— Pilih paket —</option>
                    <?php foreach($allPkgs as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['cat_name'].' — '.$p['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div style="display:flex;gap:.5rem;align-items:flex-end;padding-bottom:.05rem">
                  <button class="btn-icon" style="background:#f1f5f9;color:var(--muted);width:auto;padding:0 .75rem;font-size:.78rem;font-weight:700;height:34px" onclick="formatPasteJson()" title="Format / Prettify JSON">
                    <i class="fa fa-wand-magic-sparkles me-1"></i> Format
                  </button>
                  <button class="btn-icon" style="background:#f1f5f9;color:var(--muted);width:auto;padding:0 .75rem;font-size:.78rem;font-weight:700;height:34px" onclick="clearPasteEditor()" title="Kosongkan editor">
                    <i class="fa fa-trash-can me-1"></i> Hapus
                  </button>
                </div>
              </div>

              <div class="editor-container">
                <!-- Line numbers -->
                <div class="line-numbers" id="line-numbers">1</div>
                <!-- Textarea -->
                <textarea
                  id="paste-textarea"
                  class="paste-textarea"
                  spellcheck="false"
                  placeholder='Paste JSON di sini...&#10;&#10;Contoh:&#10;[&#10;  {&#10;    "question_text": "2 + 2 = ?",&#10;    "option_a": "3",&#10;    "option_b": "4",&#10;    "option_c": "5",&#10;    "option_d": "6",&#10;    "correct_option": "B",&#10;    "explanation": "2 + 2 = 4"&#10;  }&#10;]'
                  oninput="onPasteInput()"
                  onscroll="syncLineScroll()"
                ></textarea>
              </div>

              <!-- Status bar -->
              <div class="paste-statusbar">
                <span id="paste-status-info" style="color:var(--muted)">Belum ada JSON</span>
                <span id="paste-status-valid"></span>
              </div>

              <!-- Error box -->
              <div id="paste-errors" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:.75rem;font-size:.82rem;color:#b91c1c;margin-top:.75rem"></div>
            </div>

            <div class="paste-editor-footer">
              <button class="btn btn-light btn-sm" onclick="togglePasteEditor()">Tutup</button>
              <button class="btn-success-custom" id="paste-submit-btn" onclick="submitPasteJson()" disabled>
                <i class="fa fa-floppy-disk"></i> Simpan ke Paket
              </button>
            </div>
          </div>
        </div>
        <div class="panel-body">
          <div class="row g-4">
            <!-- Petunjuk format -->
            <div class="col-lg-5">
              <p class="fw-bold mb-2">Format JSON yang dibutuhkan:</p>
              <pre style="background:#1e293b;color:#e2e8f0;padding:1.25rem;border-radius:12px;font-size:.8rem;line-height:1.6;overflow-x:auto">[
  {
    "question_text": "2 + 2 = ?",
    "option_a": "3",
    "option_b": "4",
    "option_c": "5",
    "option_d": "6",
    "correct_option": "B",
    "explanation": "2 + 2 = 4"
  },
  ...
]</pre>
              <div class="mt-3" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:1rem;font-size:.85rem">
                <strong style="color:#15803d"><i class="fa fa-circle-check me-1"></i> Tips:</strong>
                <ul style="margin:.5rem 0 0 1rem;color:#166534">
                  <li><code>correct_option</code> harus huruf A, B, C, atau D</li>
                  <li><code>explanation</code> boleh kosong</li>
                  <li>Semua field lain wajib diisi</li>
                </ul>
              </div>
              <button class="btn-primary-custom mt-3" onclick="downloadTemplate()">
                <i class="fa fa-download"></i> Download Template JSON
              </button>
            </div>

            <!-- Form upload -->
            <div class="col-lg-7">
              <div class="mb-3">
                <label class="form-label">Pilih Paket Tujuan <span class="text-danger">*</span></label>
                <select class="form-select" id="import-package">
                  <option value="">— Pilih paket —</option>
                  <?php foreach($allPkgs as $p): ?>
                  <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['cat_name'].' — '.$p['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="import-dropzone" id="dropzone" onclick="document.getElementById('json-file').click()">
                <i class="fa fa-cloud-arrow-up"></i>
                <strong>Klik atau drag & drop file JSON di sini</strong>
                <p style="font-size:.82rem;margin-top:.25rem">Format: .json | Maks 2MB</p>
              </div>
              <input type="file" id="json-file" accept=".json" style="display:none" onchange="handleJsonFile(event)">

              <div id="import-preview" style="display:none;margin-top:1rem">
                <div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:1rem">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <strong id="import-filename" style="font-size:.9rem"></strong>
                    <span id="import-count" style="font-size:.8rem;color:var(--muted)"></span>
                  </div>
                  <div id="import-errors" style="display:none;background:#fef2f2;border-radius:8px;padding:.75rem;font-size:.82rem;color:#b91c1c;margin-bottom:.75rem"></div>
                  <button class="btn-success-custom w-100" id="import-submit-btn" onclick="submitImport()">
                    <i class="fa fa-upload"></i> Import Sekarang
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php if ($_SESSION['user_role'] === 'admin'): ?>
      <!-- ===================== USERS ===================== -->
      <div class="panel" id="panel-users">
        <div class="panel-header">
          <div class="panel-title">
            <i class="fa fa-users-gear"></i> Kelola Pengguna
          </div>
          <button class="btn-primary-custom" onclick="openUserModal()">
            <i class="fa fa-plus"></i> Tambah Pengguna
          </button>
        </div>
        <div class="panel-body">
          <table id="tbl-users" class="table table-hover w-100">
            <thead>
              <tr>
                <th>#</th>
                <th>Username</th>
                <th>Nama</th>
                <th>Role</th>
                <th>Level Akses</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody id="tbody-users">
              <!-- Users populated by JS -->
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /page-wrapper -->
  </div><!-- /main-content -->
</div><!-- /admin-layout -->

<!-- Toast -->
<div class="toast-container" id="toast-container"></div>

<?php if ($_SESSION['user_role'] === 'admin'): ?>
<!-- ===================== MODAL: USER ===================== -->
<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalTitle">Tambah Pengguna</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="user-id">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="user-username" placeholder="e.g. jhon.doe">
          </div>
          <div class="col-12">
            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="user-name" placeholder="e.g. Jhon Doe">
          </div>
          <div class="col-12">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" id="user-password" placeholder="Kosongkan jika tidak ingin diubah">
            <small class="text-muted" id="user-password-help" style="display:none">Biarkan kosong saat update untuk menjaga sandi lama.</small>
          </div>
          <div class="col-sm-6">
            <label class="form-label">Role</label>
            <select class="form-select" id="user-role">
              <option value="teacher">Guru / Teacher</option>
              <option value="admin">Administrator</option>
              <option value="student">Siswa / Student</option>
            </select>
          </div>
          <div class="col-sm-6">
            <label class="form-label">Level Akses</label>
            <select class="form-select" id="user-level">
              <option value="sd">SD</option>
              <option value="smp">SMP</option>
              <option value="sma">SMA</option>
              <option value="all">Semua (All)</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Status Aktif</label>
            <select class="form-select" id="user-active">
              <option value="1">Aktif</option>
              <option value="0">Diblokir / Tidak Aktif</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button class="btn-primary-custom" onclick="saveUser()">
          <i class="fa fa-save"></i> Simpan
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ===================== MODAL: PROFILE ===================== -->
<div class="modal fade" id="profileModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="profileModalTitle">Profil Saya</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-3">
          <img id="profile-avatar-preview" src="<?= htmlspecialchars(!empty($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : 'assets/png/avatar.png') ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid var(--border)">
          <div class="mt-2">
            <input type="file" id="profile-avatar" accept="image/*" class="form-control form-control-sm d-inline-block" style="max-width:200px">
          </div>
        </div>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="profile-username" value="<?= htmlspecialchars($_SESSION['user_username'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="profile-name" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Password Baru</label>
            <input type="password" class="form-control" id="profile-password" placeholder="Kosongkan jika tidak ingin diubah">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button class="btn-primary-custom" onclick="saveProfile()">
          <i class="fa fa-save"></i> Simpan
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ===================== MODAL: CATEGORY ===================== -->
<div class="modal fade" id="catModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="catModalTitle">Tambah Mata Pelajaran</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="cat-id">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Nama Mata Pelajaran <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="cat-name" placeholder="e.g. Matematika">
          </div>
          <div class="col-sm-6">
            <label class="form-label">Jenjang <span class="text-danger">*</span></label>
            <select class="form-select" id="cat-level">
              <option value="SD">SD</option>
              <option value="SMP">SMP</option>
              <option value="SMA">SMA</option>
            </select>
          </div>
          <div class="col-sm-6">
            <label class="form-label">Warna (HEX)</label>
            <div style="display:flex;gap:.5rem;align-items:center">
              <input type="color" class="form-control form-control-color" id="cat-color" value="#4f46e5" style="width:50px;padding:.3rem">
              <input type="text" class="form-control" id="cat-color-text" placeholder="#4f46e5" style="flex:1">
            </div>
          </div>
          <div class="col-sm-6">
            <label class="form-label">Ikon Emoji</label>
            <input type="text" class="form-control" id="cat-icon" placeholder="🔢">
          </div>
          <div class="col-sm-6">
            <label class="form-label">Urutan Tampil</label>
            <input type="number" class="form-control" id="cat-sort" value="0" min="0">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button class="btn-primary-custom" onclick="saveCat()">
          <i class="fa fa-save"></i> Simpan
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ===================== MODAL: PACKAGE ===================== -->
<div class="modal fade" id="pkgModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="pkgModalTitle">Tambah Paket Soal</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="pkg-id">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
            <select class="form-select" id="pkg-cat">
              <option value="">— Pilih —</option>
              <?php foreach($allCats as $c): ?>
              <option value="<?= $c['id'] ?>">[<?= $c['level'] ?>] <?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Nama Paket <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="pkg-name" placeholder="e.g. Latihan UTS Bab 1-3">
          </div>
          <div class="col-12">
            <label class="form-label">Deskripsi Paket</label>
            <textarea class="form-control" id="pkg-desc" rows="3"
              placeholder="Jelaskan isi paket ini untuk siswa, e.g. 'Soal campuran perkalian dan pembagian kelas 4'"></textarea>
          </div>
          <div class="col-sm-4">
            <label class="form-label">Tipe Akses</label>
            <select class="form-select" id="pkg-access">
              <option value="both">Semua (Guest & Internal)</option>
              <option value="guest">Guest (Terbuka)</option>
              <option value="internal">Internal (Wajib Login)</option>
            </select>
          </div>
          <div class="col-sm-4">
            <label class="form-label">Tipe Timer</label>
            <select class="form-select" id="pkg-timer-type">
              <option value="none">Tanpa Timer</option>
              <option value="per_packet">Per Paket (Menit)</option>
              <option value="per_question">Per Soal (Detik)</option>
            </select>
          </div>
          <div class="col-sm-4">
            <label class="form-label">Durasi <small>(Angka Saja)</small></label>
            <input type="number" class="form-control" id="pkg-timer" value="0" min="0" placeholder="0 = tanpa batas">
          </div>
          <div class="col-sm-4">
            <label class="form-label">Target Level</label>
            <select class="form-select" id="pkg-target-level">
              <option value="all">Semua Jenjang</option>
              <option value="sd">SD</option>
              <option value="smp">SMP</option>
              <option value="sma">SMA</option>
            </select>
          </div>
          <div class="col-sm-4">
            <label class="form-label">Acak Urutan Soal</label>
            <select class="form-select" id="pkg-shuffle-q">
              <option value="0">Tidak</option>
              <option value="1">Ya</option>
            </select>
          </div>
          <div class="col-sm-4">
            <label class="form-label">Acak Opsi Jawaban</label>
            <select class="form-select" id="pkg-shuffle-opt">
              <option value="0">Tidak</option>
              <option value="1">Ya</option>
            </select>
          </div>
          <div class="col-sm-6">
            <label class="form-label">Urutan Tampil</label>
            <input type="number" class="form-control" id="pkg-sort" value="0" min="0">
          </div>
          <div class="col-sm-6">
            <label class="form-label">Status</label>
            <select class="form-select" id="pkg-active">
              <option value="1">Aktif</option>
              <option value="0">Non-aktif</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button class="btn-primary-custom" onclick="savePkg()">
          <i class="fa fa-save"></i> Simpan
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ===================== MODAL: QUESTION ===================== -->
<div class="modal fade" id="qModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="qModalTitle">Tambah Soal</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="q-id">
        <div class="row g-3">
          <div class="col-sm-6">
            <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
            <select class="form-select" id="q-cat" onchange="filterPkgDropdown()">
              <option value="">— Pilih —</option>
              <?php foreach($allCats as $c): ?>
              <option value="<?= $c['id'] ?>">[<?= $c['level'] ?>] <?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-6">
            <label class="form-label">Paket <span class="text-danger">*</span></label>
            <select class="form-select" id="q-pkg">
              <option value="">— Pilih mata pelajaran dulu —</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Teks Soal <span class="text-danger">*</span></label>
            <textarea class="form-control" id="q-text" rows="3" placeholder="Tulis soal di sini..."></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Gambar Soal <small style="color:var(--muted)">(opsional)</small></label>
            <input type="file" class="form-control" id="q-image" accept="image/png, image/jpeg, image/jpg, image/webp">
          </div>

          <!-- Opsi A-D -->
          <div class="col-12">
            <label class="form-label">Pilihan Jawaban <span class="text-danger">*</span></label>
            <div class="option-row">
              <?php foreach(['a'=>'opt-a','b'=>'opt-b','c'=>'opt-c','d'=>'opt-d'] as $letter => $cls): ?>
              <div style="display:flex;align-items:center;gap:.5rem">
                <span class="option-label <?= $cls ?>"><?= strtoupper($letter) ?></span>
                <input type="text" class="form-control" id="q-opt-<?= $letter ?>"
                  placeholder="Opsi <?= strtoupper($letter) ?>">
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label">Jawaban Benar <span class="text-danger">*</span></label>
            <div class="correct-radios">
              <?php foreach(['A','B','C','D'] as $l): ?>
              <div>
                <input type="radio" name="q-correct" id="q-correct-<?= $l ?>" value="<?= $l ?>">
                <label for="q-correct-<?= $l ?>"><?= $l ?></label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label">Pembahasan <small style="color:var(--muted)">(opsional)</small></label>
            <textarea class="form-control" id="q-explanation" rows="2"
              placeholder="Jelaskan mengapa jawaban tersebut benar..."></textarea>
          </div>
          <div class="col-sm-4">
            <label class="form-label">Urutan</label>
            <input type="number" class="form-control" id="q-sort" value="0" min="0">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button class="btn-primary-custom" onclick="saveQ()">
          <i class="fa fa-save"></i> Simpan
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ===================== MODAL: ANALYTICS (STATISTIK) ===================== -->
<div class="modal fade" id="statModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="statModalTitle">Statistik Paket</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle" id="tbl-stats">
            <thead>
              <tr>
                <th width="50">No</th>
                <th>Soal</th>
                <th width="100">Menjawab</th>
                <th width="100">Benar</th>
                <th width="120">Success Rate</th>
              </tr>
            </thead>
            <tbody id="tbody-stats">
              <tr><td colspan="5" class="text-center">Memuat data...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ===================== MODAL: CONFIRM DELETE ===================== -->
<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header" style="background:#ef4444">
        <h5 class="modal-title">Konfirmasi Hapus</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center" style="padding:1.5rem">
        <i class="fa fa-triangle-exclamation" style="font-size:2.5rem;color:#ef4444;display:block;margin-bottom:.75rem"></i>
        <p id="confirm-msg" style="font-size:.95rem">Yakin ingin menghapus?</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-danger" id="confirm-ok-btn">Ya, Hapus</button>
      </div>
    </div>
  </div>
</div>

<!-- ===================== SCRIPTS ===================== -->
<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- jQuery (required by DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
// ================================================================
//  DATA
// ================================================================
const allPackages  = <?= json_encode($allPkgs) ?>;
const allCats      = <?= json_encode($allCats) ?>;
let   jsonImportData = null;

// Bootstrap modal instances
const catModal     = new bootstrap.Modal('#catModal');
const pkgModal     = new bootstrap.Modal('#pkgModal');
const qModal       = new bootstrap.Modal('#qModal');
const confirmModal = new bootstrap.Modal('#confirmModal');
const statModal    = new bootstrap.Modal('#statModal');
const userModalEl  = document.getElementById('userModal');
const userModal    = userModalEl ? new bootstrap.Modal(userModalEl) : null;
const profileModal = new bootstrap.Modal('#profileModal');

// DataTable instances
let dtCat, dtPkg, dtQ, dtUser;

// ================================================================
//  PANEL NAVIGATION
// ================================================================
function showPanel(name, btn) {
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));

  document.getElementById('panel-' + name).classList.add('active');
  btn.classList.add('active');

  const icons = {
    dashboard:'fa-gauge-high', categories:'fa-folder-tree',
    packages:'fa-layer-group', questions:'fa-circle-question', import:'fa-file-import',
    users:'fa-users-gear'
  };
  const labels = {
    dashboard:'Dashboard', categories:'Mata Pelajaran',
    packages:'Paket Soal', questions:'Bank Soal', import:'Import JSON',
    users:'Kelola Pengguna'
  };
  document.getElementById('topbar-icon').className = 'fa ' + (icons[name] || 'fa-circle');
  document.getElementById('topbar-label').textContent = labels[name] || name;

  // Lazy init DataTables
  if (name === 'categories' && !dtCat) initCatTable();
  if (name === 'packages'   && !dtPkg) loadPackages();
  if (name === 'questions'  && !dtQ)  loadQuestions();
  if (name === 'users'      && !dtUser) loadUsers();
}

// ================================================================
//  TOAST
// ================================================================
function toast(msg, type='success') {
  const el = document.createElement('div');
  el.className = `alert alert-${type === 'success' ? 'success' : 'danger'} d-flex align-items-center gap-2`;
  el.style.cssText = 'border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.12);min-width:260px;animation:fadeIn .3s';
  el.innerHTML = `<i class="fa ${type==='success'?'fa-circle-check':'fa-circle-xmark'}"></i> ${msg}`;
  document.getElementById('toast-container').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

// ================================================================
//  CATEGORIES
// ================================================================
function initCatTable() {
  dtCat = $('#tbl-categories').DataTable({
    language: { url: '' },
    pageLength: 10,
    columnDefs: [{ orderable: false, targets: 5 }],
    language: {
      search: 'Cari:',
      lengthMenu: 'Tampilkan _MENU_ data',
      info: 'Menampilkan _START_-_END_ dari _TOTAL_ data',
      paginate: { previous: '‹', next: '›' },
      emptyTable: 'Belum ada data'
    }
  });
}

function openCatModal(data=null) {
  document.getElementById('catModalTitle').textContent = data ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran';
  document.getElementById('cat-id').value    = data?.id    ?? '';
  document.getElementById('cat-name').value  = data?.name  ?? '';
  document.getElementById('cat-level').value = data?.level ?? 'SD';
  document.getElementById('cat-color').value = data?.color ?? '#4f46e5';
  document.getElementById('cat-color-text').value = data?.color ?? '#4f46e5';
  document.getElementById('cat-icon').value  = data?.icon  ?? '';
  document.getElementById('cat-sort').value  = data?.sort_order ?? 0;
  catModal.show();
}

function editCat(data) { openCatModal(data); }

// Sync color picker ↔ text
document.getElementById('cat-color').addEventListener('input', e => {
  document.getElementById('cat-color-text').value = e.target.value;
});
document.getElementById('cat-color-text').addEventListener('input', e => {
  if (/^#[0-9a-fA-F]{6}$/.test(e.target.value))
    document.getElementById('cat-color').value = e.target.value;
});

async function saveCat() {
  const id    = document.getElementById('cat-id').value;
  const name  = document.getElementById('cat-name').value.trim();
  const level = document.getElementById('cat-level').value;
  const color = document.getElementById('cat-color-text').value.trim() || document.getElementById('cat-color').value;
  const icon  = document.getElementById('cat-icon').value.trim();
  const sort  = document.getElementById('cat-sort').value;

  if (!name) { toast('Nama wajib diisi', 'error'); return; }

  const fd = new FormData();
  fd.append('action', id ? 'update' : 'create');
  if (id) fd.append('id', id);
  fd.append('name', name);
  fd.append('level', level);
  fd.append('color', color);
  fd.append('icon', icon);
  fd.append('sort_order', sort);

  const res = await fetch('api_categories.php', { method:'POST', body: fd });
  const json = await res.json();
  if (json.success) {
    toast(id ? 'Kategori diperbarui!' : 'Kategori ditambahkan!');
    catModal.hide();
    setTimeout(() => location.reload(), 800);
  } else {
    toast(json.message || 'Gagal menyimpan', 'error');
  }
}

function deleteCat(id, name) {
  document.getElementById('confirm-msg').textContent = `Hapus mata pelajaran "${name}"? Semua paket & soal di dalamnya akan terhapus.`;
  document.getElementById('confirm-ok-btn').onclick = async () => {
    const fd = new FormData();
    fd.append('action','delete'); fd.append('id', id);
    const res  = await fetch('api_categories.php', {method:'POST', body:fd});
    const json = await res.json();
    confirmModal.hide();
    if (json.success) { toast('Dihapus!'); setTimeout(() => location.reload(), 800); }
    else toast(json.message || 'Gagal menghapus', 'error');
  };
  confirmModal.show();
}

// ================================================================
//  PACKAGES
// ================================================================
async function loadPackages() {
  const res  = await fetch('api_packages.php');
  const json = await res.json();
  const tbody = document.getElementById('tbody-packages');
  tbody.innerHTML = '';
  json.data.forEach((p, i) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${i+1}</td>
      <td><strong>${esc(p.name)}</strong><br><small class="text-muted">Akses: ${esc(p.target_access)} | Timer: ${esc(p.timer_type)}</small></td>
      <td>${esc(p.category_name || '—')}</td>
      <td style="max-width:220px;white-space:normal;font-size:.82rem;color:var(--muted)">${esc(p.description || '—')}</td>
      <td>${p.time_limit > 0 ? p.time_limit+'<small> unit</small>' : '<span style="color:var(--muted)">—</span>'}</td>
      <td>${p.shuffle_q == 1 ? '<i class="fa fa-check" style="color:var(--success)"></i>' : '<span style="color:var(--muted)">—</span>'}</td>
      <td>
        <button class="btn-icon btn-edit" onclick='editPkg(${JSON.stringify(p)})'><i class="fa fa-pen"></i></button>
        <button class="btn-icon text-info" onclick="showStats(${p.id}, '${esc(p.name)}')"><i class="fa fa-chart-simple"></i></button>
        <button class="btn-icon btn-del"  onclick="deletePkg(${p.id}, '${esc(p.name)}')"><i class="fa fa-trash"></i></button>
      </td>`;
    tbody.appendChild(tr);
  });

  dtPkg = $('#tbl-packages').DataTable({
    pageLength: 10,
    columnDefs: [{ orderable: false, targets: 6 }],
    language: {
      search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data',
      info: 'Menampilkan _START_-_END_ dari _TOTAL_ data',
      paginate: { previous:'‹', next:'›' }, emptyTable:'Belum ada paket'
    }
  });
}

function openPkgModal(data=null) {
  document.getElementById('pkgModalTitle').textContent = data ? 'Edit Paket' : 'Tambah Paket Soal';
  document.getElementById('pkg-id').value         = data?.id          ?? '';
  document.getElementById('pkg-cat').value        = data?.category_id ?? '';
  document.getElementById('pkg-name').value       = data?.name        ?? '';
  document.getElementById('pkg-desc').value       = data?.description ?? '';
  document.getElementById('pkg-timer').value      = data?.time_limit  ?? 0;
  
  document.getElementById('pkg-access').value     = data?.target_access ?? 'both';
  document.getElementById('pkg-timer-type').value = data?.timer_type ?? 'none';
  document.getElementById('pkg-target-level').value = data?.target_level ?? 'all';

  document.getElementById('pkg-shuffle-q').value  = data?.shuffle_q   ?? 0;
  document.getElementById('pkg-shuffle-opt').value= data?.shuffle_opt ?? 0;
  document.getElementById('pkg-sort').value       = data?.sort_order  ?? 0;
  document.getElementById('pkg-active').value     = data?.is_active   ?? 1;
  pkgModal.show();
}

function editPkg(data) { openPkgModal(data); }

async function savePkg() {
  const id   = document.getElementById('pkg-id').value;
  const name = document.getElementById('pkg-name').value.trim();
  const cat  = document.getElementById('pkg-cat').value;
  if (!name || !cat) { toast('Nama & mata pelajaran wajib diisi', 'error'); return; }

  const fd = new FormData();
  fd.append('action', id ? 'update' : 'create');
  if (id) fd.append('id', id);
  fd.append('category_id',  cat);
  fd.append('name',         name);
  fd.append('description',  document.getElementById('pkg-desc').value);
  fd.append('time_limit',   document.getElementById('pkg-timer').value);
  
  fd.append('target_access',document.getElementById('pkg-access').value);
  fd.append('timer_type',   document.getElementById('pkg-timer-type').value);
  fd.append('target_level', document.getElementById('pkg-target-level').value);

  fd.append('shuffle_q',    document.getElementById('pkg-shuffle-q').value);
  fd.append('shuffle_opt',  document.getElementById('pkg-shuffle-opt').value);
  fd.append('sort_order',   document.getElementById('pkg-sort').value);
  fd.append('is_active',    document.getElementById('pkg-active').value);

  const res  = await fetch('api_packages.php', {method:'POST', body:fd});
  const json = await res.json();
  if (json.success) {
    toast(id ? 'Paket diperbarui!' : 'Paket ditambahkan!');
    pkgModal.hide();
    if (dtPkg) { dtPkg.destroy(); dtPkg = null; }
    document.getElementById('tbody-packages').innerHTML = '';
    loadPackages();
  } else toast(json.message || 'Gagal', 'error');
}

async function deletePkg(id, name) {
  document.getElementById('confirm-msg').textContent = `Hapus paket "${name}"? Semua soal di dalamnya akan terhapus.`;
  document.getElementById('confirm-ok-btn').onclick = async () => {
    const fd = new FormData();
    fd.append('action','delete'); fd.append('id', id);
    const res  = await fetch('api_packages.php', {method:'POST', body:fd});
    const json = await res.json();
    confirmModal.hide();
    if (json.success) {
      toast('Paket dihapus!');
      if (dtPkg) { dtPkg.destroy(); dtPkg = null; }
      document.getElementById('tbody-packages').innerHTML = '';
      loadPackages();
    } else toast(json.message || 'Gagal', 'error');
  };
  confirmModal.show();
}

async function showStats(pkgId, pkgName) {
  document.getElementById('statModalTitle').textContent = `Statistik Paket: ${pkgName}`;
  const tbody = document.getElementById('tbody-stats');
  tbody.innerHTML = '<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat...</td></tr>';
  statModal.show();

  try {
    const res = await fetch('api_analytics.php?action=questions_success_rate&package_id=' + pkgId);
    const json = await res.json();
    if (json.success) {
      tbody.innerHTML = '';
      if (json.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Belum ada data pertanyaan untuk paket ini.</td></tr>';
      } else {
        json.data.forEach((q, i) => {
          const successRate = parseFloat(q.success_rate);
          let badgeClass = 'bg-danger';
          if (successRate >= 80) badgeClass = 'bg-success';
          else if (successRate >= 50) badgeClass = 'bg-warning text-dark';
          
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${i+1}</td>
            <td style="max-width:300px;white-space:normal;font-size:0.9rem">${esc(q.question_text.length>60?q.question_text.substring(0,60)+'…':q.question_text)}</td>
            <td>${q.total_answers} kali</td>
            <td>${q.correct_answers} kali</td>
            <td>
              <span class="badge ${badgeClass}" style="font-size:0.85rem">${successRate}%</span>
            </td>
          `;
          tbody.appendChild(tr);
        });
      }
    } else {
      tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">${json.message}</td></tr>`;
    }
  } catch (e) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Gagal memuat statistik.</td></tr>';
  }
}

// ================================================================
//  QUESTIONS
// ================================================================
async function loadQuestions() {
  const res  = await fetch('api_questions.php');
  const json = await res.json();
  const tbody = document.getElementById('tbody-questions');
  tbody.innerHTML = '';
  json.data.forEach((q, i) => {
    const tr = document.createElement('tr');
    const preview = q.question_text.length > 70 ? q.question_text.substring(0,70)+'…' : q.question_text;
    tr.innerHTML = `
      <td>${i+1}</td>
      <td style="max-width:280px;white-space:normal">${esc(preview)}</td>
      <td>${esc(q.package_name || '—')}</td>
      <td>${esc(q.category_name || '—')}</td>
      <td><span class="badge-correct">${q.correct_option}</span></td>
      <td>
        <button class="btn-icon btn-edit" onclick='editQ(${JSON.stringify(q)})'><i class="fa fa-pen"></i></button>
        <button class="btn-icon btn-del"  onclick="deleteQ(${q.id})"><i class="fa fa-trash"></i></button>
      </td>`;
    tbody.appendChild(tr);
  });

  dtQ = $('#tbl-questions').DataTable({
    pageLength: 15,
    columnDefs: [{ orderable: false, targets: 5 }],
    language: {
      search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data',
      info: 'Menampilkan _START_-_END_ dari _TOTAL_ data',
      paginate: { previous:'‹', next:'›' }, emptyTable:'Belum ada soal'
    }
  });
}

function openQModal(data=null) {
  document.getElementById('qModalTitle').textContent = data ? 'Edit Soal' : 'Tambah Soal';
  document.getElementById('q-id').value          = data?.id           ?? '';
  document.getElementById('q-cat').value         = data?.category_id  ?? '';
  document.getElementById('q-text').value        = data?.question_text ?? '';
  document.getElementById('q-opt-a').value       = data?.option_a     ?? '';
  document.getElementById('q-opt-b').value       = data?.option_b     ?? '';
  document.getElementById('q-opt-c').value       = data?.option_c     ?? '';
  document.getElementById('q-opt-d').value       = data?.option_d     ?? '';
  document.getElementById('q-explanation').value = data?.explanation   ?? '';
  document.getElementById('q-sort').value        = data?.sort_order    ?? 0;
  document.getElementById('q-image').value       = ''; // reset file input

  // Set correct radio
  document.querySelectorAll('input[name="q-correct"]').forEach(r => r.checked = false);
  if (data?.correct_option) {
    const el = document.getElementById('q-correct-' + data.correct_option);
    if (el) el.checked = true;
  }

  // Filter paket dropdown
  filterPkgDropdown(data?.package_id);
  qModal.show();
}

function editQ(data) { openQModal(data); }

function filterPkgDropdown(selectedPkgId=null) {
  const catId  = document.getElementById('q-cat').value;
  const select = document.getElementById('q-pkg');
  select.innerHTML = '<option value="">— Pilih paket —</option>';
  allPackages
    .filter(p => !catId || p.category_id == catId)
    .forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.id;
      opt.textContent = p.name;
      if (selectedPkgId && p.id == selectedPkgId) opt.selected = true;
      select.appendChild(opt);
    });
}

async function saveQ() {
  const id      = document.getElementById('q-id').value;
  const catId   = document.getElementById('q-cat').value;
  const pkgId   = document.getElementById('q-pkg').value;
  const qtext   = document.getElementById('q-text').value.trim();
  const correct = document.querySelector('input[name="q-correct"]:checked')?.value;
  const imgFile = document.getElementById('q-image').files[0];

  if (!catId || !pkgId || !qtext || !correct) {
    toast('Isi semua field yang wajib diisi', 'error'); return;
  }

  const fd = new FormData();
  fd.append('action',       id ? 'update' : 'create');
  if (id) fd.append('id', id);
  fd.append('category_id',   catId);
  fd.append('package_id',    pkgId);
  fd.append('question_text', qtext);
  fd.append('option_a',      document.getElementById('q-opt-a').value);
  fd.append('option_b',      document.getElementById('q-opt-b').value);
  fd.append('option_c',      document.getElementById('q-opt-c').value);
  fd.append('option_d',      document.getElementById('q-opt-d').value);
  fd.append('correct_option',correct);
  fd.append('explanation',   document.getElementById('q-explanation').value);
  fd.append('sort_order',    document.getElementById('q-sort').value);
  
  if (imgFile) {
      fd.append('image', imgFile);
  }

  const res  = await fetch('api_questions.php', {method:'POST', body:fd});
  const json = await res.json();
  if (json.success) {
    toast(id ? 'Soal diperbarui!' : 'Soal ditambahkan!');
    qModal.hide();
    if (dtQ) { dtQ.destroy(); dtQ = null; }
    document.getElementById('tbody-questions').innerHTML = '';
    loadQuestions();
  } else toast(json.message || 'Gagal', 'error');
}

async function deleteQ(id) {
  document.getElementById('confirm-msg').textContent = 'Hapus soal ini secara permanen?';
  document.getElementById('confirm-ok-btn').onclick = async () => {
    const fd = new FormData();
    fd.append('action','delete'); fd.append('id', id);
    const res  = await fetch('api_questions.php', {method:'POST', body:fd});
    const json = await res.json();
    confirmModal.hide();
    if (json.success) {
      toast('Soal dihapus!');
      if (dtQ) { dtQ.destroy(); dtQ = null; }
      document.getElementById('tbody-questions').innerHTML = '';
      loadQuestions();
    } else toast(json.message || 'Gagal', 'error');
  };
  confirmModal.show();
}

// ================================================================
//  IMPORT JSON
// ================================================================
const dropzone = document.getElementById('dropzone');
dropzone.addEventListener('dragover',  e => { e.preventDefault(); dropzone.classList.add('drag-over'); });
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('drag-over'));
dropzone.addEventListener('drop', e => {
  e.preventDefault();
  dropzone.classList.remove('drag-over');
  const file = e.dataTransfer.files[0];
  if (file) processJsonFile(file);
});

function handleJsonFile(e) {
  const file = e.target.files[0];
  if (file) processJsonFile(file);
}

function processJsonFile(file) {
  if (!file.name.endsWith('.json')) { toast('File harus berformat .json', 'error'); return; }
  const reader = new FileReader();
  reader.onload = ev => {
    try {
      const data = JSON.parse(ev.target.result);
      if (!Array.isArray(data)) { toast('JSON harus berupa array', 'error'); return; }

      const errors = [];
      data.forEach((item, i) => {
        const required = ['question_text','option_a','option_b','option_c','option_d','correct_option'];
        required.forEach(f => { if (!item[f]) errors.push(`Baris ${i+1}: field "${f}" kosong`); });
        if (item.correct_option && !['A','B','C','D'].includes(item.correct_option.toUpperCase()))
          errors.push(`Baris ${i+1}: correct_option harus A/B/C/D`);
      });

      jsonImportData = data;
      document.getElementById('import-filename').textContent = file.name;
      document.getElementById('import-count').textContent    = data.length + ' soal ditemukan';
      const errDiv = document.getElementById('import-errors');
      if (errors.length) {
        errDiv.innerHTML = '<strong>⚠️ Ada kesalahan format:</strong><br>' + errors.join('<br>');
        errDiv.style.display = 'block';
        document.getElementById('import-submit-btn').disabled = true;
      } else {
        errDiv.style.display = 'none';
        document.getElementById('import-submit-btn').disabled = false;
      }
      document.getElementById('import-preview').style.display = 'block';
    } catch(err) {
      toast('File JSON tidak valid: ' + err.message, 'error');
    }
  };
  reader.readAsText(file);
}

async function submitImport() {
  const pkgId = document.getElementById('import-package').value;
  if (!pkgId)         { toast('Pilih paket tujuan terlebih dahulu', 'error'); return; }
  if (!jsonImportData){ toast('Belum ada file JSON yang dipilih', 'error'); return; }

  const pkg = allPackages.find(p => p.id == pkgId);
  const catId = pkg?.category_id ?? 0;

  const btn = document.getElementById('import-submit-btn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Mengimport...';

  let success = 0, fail = 0;
  for (const item of jsonImportData) {
    const fd = new FormData();
    fd.append('action',        'create');
    fd.append('category_id',   catId);
    fd.append('package_id',    pkgId);
    fd.append('question_text', item.question_text);
    fd.append('option_a',      item.option_a);
    fd.append('option_b',      item.option_b);
    fd.append('option_c',      item.option_c);
    fd.append('option_d',      item.option_d);
    fd.append('correct_option',item.correct_option.toUpperCase());
    fd.append('explanation',   item.explanation || '');
    fd.append('image_url',     item.image_url || '');
    fd.append('sort_order',    item.sort_order || 0);
    try {
      const res  = await fetch('api_questions.php', {method:'POST', body:fd});
      const json = await res.json();
      json.success ? success++ : fail++;
    } catch { fail++; }
  }

  btn.innerHTML = '<i class="fa fa-upload"></i> Import Sekarang';
  btn.disabled  = false;
  toast(`Import selesai: ${success} soal berhasil${fail ? ', '+fail+' gagal' : ''}!`, fail ? 'error' : 'success');
  if (success > 0) {
    document.getElementById('import-preview').style.display = 'none';
    jsonImportData = null;
    if (dtQ) { dtQ.destroy(); dtQ = null; }
    document.getElementById('tbody-questions').innerHTML = '';
  }
}

function downloadTemplate() {
  const tpl = [
    {"question_text":"Contoh soal 1?","option_a":"Opsi A","option_b":"Opsi B","option_c":"Opsi C","option_d":"Opsi D","correct_option":"B","explanation":"Karena B adalah jawaban yang benar."},
    {"question_text":"Contoh soal 2?","option_a":"Opsi A","option_b":"Opsi B","option_c":"Opsi C","option_d":"Opsi D","correct_option":"A","explanation":""}
  ];
  const blob = new Blob([JSON.stringify(tpl, null, 2)], {type:'application/json'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'template_soal.json';
  a.click();
}

// ================================================================
//  PASTE JSON EDITOR
// ================================================================
let pasteJsonData = null;

function togglePasteEditor() {
  const wrap = document.getElementById('paste-editor-wrap');
  const btn  = document.getElementById('paste-toggle-btn');
  const isOpen = wrap.classList.contains('open');

  wrap.classList.toggle('open');
  btn.classList.toggle('active');

  if (!isOpen) {
    // Focus textarea setelah animasi
    setTimeout(() => document.getElementById('paste-textarea').focus(), 420);
  }
}

function onPasteInput() {
  updateLineNumbers();
  validatePasteJson();
}

function syncLineScroll() {
  const ta = document.getElementById('paste-textarea');
  document.getElementById('line-numbers').scrollTop = ta.scrollTop;
}

function updateLineNumbers() {
  const ta    = document.getElementById('paste-textarea');
  const lines = ta.value.split('\n').length;
  const ln    = document.getElementById('line-numbers');
  let nums    = '';
  for (let i = 1; i <= Math.max(lines, 1); i++) nums += i + '\n';
  ln.textContent = nums;
}

function validatePasteJson() {
  const raw     = document.getElementById('paste-textarea').value.trim();
  const info    = document.getElementById('paste-status-info');
  const valid   = document.getElementById('paste-status-valid');
  const errDiv  = document.getElementById('paste-errors');
  const submitBtn = document.getElementById('paste-submit-btn');

  if (!raw) {
    info.textContent = 'Belum ada JSON';
    valid.textContent = '';
    valid.className  = '';
    errDiv.style.display = 'none';
    submitBtn.disabled   = true;
    pasteJsonData = null;
    return;
  }

  try {
    const data = JSON.parse(raw);
    if (!Array.isArray(data)) throw new Error('JSON harus berupa array [ ... ]');

    // Validasi setiap item
    const errors = [];
    const required = ['question_text','option_a','option_b','option_c','option_d','correct_option'];
    data.forEach((item, i) => {
      required.forEach(f => {
        if (!item[f]) errors.push(`Baris objek ke-${i+1}: field "<strong>${f}</strong>" kosong`);
      });
      if (item.correct_option && !['A','B','C','D'].includes(item.correct_option.toUpperCase()))
        errors.push(`Baris objek ke-${i+1}: correct_option "<strong>${item.correct_option}</strong>" tidak valid`);
    });

    info.textContent = `${data.length} soal terdeteksi`;

    if (errors.length) {
      valid.textContent = `⚠ ${errors.length} error`;
      valid.className   = 'invalid';
      errDiv.innerHTML  = errors.join('<br>');
      errDiv.style.display = 'block';
      submitBtn.disabled   = true;
      pasteJsonData = null;
    } else {
      valid.textContent = '✓ JSON valid';
      valid.className   = 'valid';
      errDiv.style.display = 'none';
      submitBtn.disabled   = false;
      pasteJsonData = data;
    }
  } catch(e) {
    info.textContent = 'JSON tidak valid';
    valid.textContent = '✗ ' + e.message;
    valid.className   = 'invalid';
    errDiv.style.display = 'none';
    submitBtn.disabled   = true;
    pasteJsonData = null;
  }
}

function formatPasteJson() {
  const ta = document.getElementById('paste-textarea');
  try {
    const parsed = JSON.parse(ta.value);
    ta.value = JSON.stringify(parsed, null, 2);
    onPasteInput();
    toast('JSON diformat!');
  } catch(e) {
    toast('Format gagal: JSON tidak valid', 'error');
  }
}

function clearPasteEditor() {
  document.getElementById('paste-textarea').value = '';
  onPasteInput();
}

async function submitPasteJson() {
  const pkgId = document.getElementById('paste-package').value;
  if (!pkgId)       { toast('Pilih paket tujuan terlebih dahulu', 'error'); return; }
  if (!pasteJsonData){ toast('JSON belum valid', 'error'); return; }

  const pkg   = allPackages.find(p => p.id == pkgId);
  const catId = pkg?.category_id ?? 0;

  const btn = document.getElementById('paste-submit-btn');
  btn.disabled  = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Menyimpan...';

  let success = 0, fail = 0;
  for (const item of pasteJsonData) {
    const fd = new FormData();
    fd.append('action',        'create');
    fd.append('category_id',   catId);
    fd.append('package_id',    pkgId);
    fd.append('question_text', item.question_text);
    fd.append('option_a',      item.option_a);
    fd.append('option_b',      item.option_b);
    fd.append('option_c',      item.option_c);
    fd.append('option_d',      item.option_d);
    fd.append('correct_option',item.correct_option.toUpperCase());
    fd.append('explanation',   item.explanation || '');
    fd.append('sort_order',    item.sort_order  || 0);
    try {
      const res  = await fetch('api_questions.php', {method:'POST', body:fd});
      const json = await res.json();
      json.success ? success++ : fail++;
    } catch { fail++; }
  }

  btn.innerHTML = '<i class="fa fa-floppy-disk"></i> Simpan ke Paket';
  btn.disabled  = false;

  if (success > 0) {
    toast(`${success} soal berhasil disimpan!${fail ? ' ('+fail+' gagal)' : ''}`);
    clearPasteEditor();
    // Refresh tabel soal jika sudah dimuat
    if (dtQ) { dtQ.destroy(); dtQ = null; }
    document.getElementById('tbody-questions').innerHTML = '';
  } else {
    toast('Semua soal gagal disimpan', 'error');
  }
}

// Support Tab key di textarea
document.getElementById('paste-textarea').addEventListener('keydown', function(e) {
  if (e.key === 'Tab') {
    e.preventDefault();
    const start = this.selectionStart;
    const end   = this.selectionEnd;
    this.value  = this.value.substring(0,start) + '  ' + this.value.substring(end);
    this.selectionStart = this.selectionEnd = start + 2;
    onPasteInput();
  }
});

// ================================================================
//  USERS (KELOLA PENGGUNA)
// ================================================================
async function loadUsers() {
  const res  = await fetch('api_users.php');
  const json = await res.json();
  const tbody = document.getElementById('tbody-users');
  tbody.innerHTML = '';
  if (json.success) {
    json.data.forEach((u, i) => {
      const tr = document.createElement('tr');
      let roleBadge = '';
      if (u.role === 'admin') roleBadge = '<span class="badge bg-danger">Admin</span>';
      else if (u.role === 'teacher') roleBadge = '<span class="badge bg-primary">Teacher</span>';
      else roleBadge = '<span class="badge bg-secondary">Student</span>';

      const statusBadge = parseInt(u.is_active) === 1 
        ? '<span class="badge bg-success">Aktif</span>' 
        : '<span class="badge bg-warning text-dark">Blokir</span>';

      tr.innerHTML = `
        <td>${i+1}</td>
        <td><strong>${esc(u.username)}</strong></td>
        <td>${esc(u.display_name || '—')}</td>
        <td>${roleBadge}</td>
        <td><span class="badge-level badge-${u.level}">${u.level.toUpperCase()}</span></td>
        <td>${statusBadge}</td>
        <td>
          <button class="btn-icon btn-edit" onclick='editUser(${JSON.stringify(u)})'><i class="fa fa-pen"></i></button>
          <button class="btn-icon btn-del"  onclick="deleteUser(${u.id}, '${esc(u.username)}')"><i class="fa fa-trash"></i></button>
        </td>`;
      tbody.appendChild(tr);
    });

    dtUser = $('#tbl-users').DataTable({
      pageLength: 10,
      columnDefs: [{ orderable: false, targets: 6 }],
      language: {
        search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data',
        info: 'Menampilkan _START_-_END_ dari _TOTAL_ data',
        paginate: { previous:'‹', next:'›' }, emptyTable:'Belum ada pengguna'
      }
    });
  }
}

function openUserModal(data=null) {
  document.getElementById('userModalTitle').textContent = data ? 'Edit Pengguna' : 'Tambah Pengguna';
  document.getElementById('user-id').value       = data?.id           ?? '';
  document.getElementById('user-username').value = data?.username     ?? '';
  document.getElementById('user-name').value     = data?.display_name ?? '';
  document.getElementById('user-password').value = '';
  document.getElementById('user-role').value     = data?.role         ?? 'teacher';
  document.getElementById('user-level').value    = data?.level        ?? 'sd';
  document.getElementById('user-active').value   = data?.is_active    ?? 1;

  if (data) {
    document.getElementById('user-password-help').style.display = 'block';
  } else {
    document.getElementById('user-password-help').style.display = 'none';
  }

  userModal.show();
}

function editUser(data) { openUserModal(data); }

async function saveUser() {
  const id = document.getElementById('user-id').value;
  const username = document.getElementById('user-username').value.trim();
  const password = document.getElementById('user-password').value;

  if (!username) { toast('Username wajib diisi', 'error'); return; }
  if (!id && !password) { toast('Password wajib diisi untuk user baru', 'error'); return; }

  const fd = new FormData();
  fd.append('action', id ? 'update' : 'create');
  if (id) fd.append('id', id);
  fd.append('username',     username);
  fd.append('display_name', document.getElementById('user-name').value.trim());
  fd.append('password',     password);
  fd.append('role',         document.getElementById('user-role').value);
  fd.append('level',        document.getElementById('user-level').value);
  fd.append('is_active',    document.getElementById('user-active').value);

  const res  = await fetch('api_users.php', {method:'POST', body:fd});
  const json = await res.json();
  if (json.success) {
    toast(id ? 'Pengguna diperbarui!' : 'Pengguna ditambahkan!');
    userModal.hide();
    if (dtUser) { dtUser.destroy(); dtUser = null; }
    document.getElementById('tbody-users').innerHTML = '';
    loadUsers();
  } else toast(json.message || 'Gagal menyimpan', 'error');
}

async function deleteUser(id, username) {
  document.getElementById('confirm-msg').textContent = `Hapus pengguna "${username}"? Sistem mungkin terputus dari rekaman log pengguna ini.`;
  document.getElementById('confirm-ok-btn').onclick = async () => {
    const fd = new FormData();
    fd.append('action','delete'); fd.append('id', id);
    const res  = await fetch('api_users.php', {method:'POST', body:fd});
    const json = await res.json();
    confirmModal.hide();
    if (json.success) {
      toast('Pengguna dihapus!');
      if (dtUser) { dtUser.destroy(); dtUser = null; }
      document.getElementById('tbody-users').innerHTML = '';
      loadUsers();
    } else toast(json.message || 'Gagal menghapus', 'error');
  };
  confirmModal.show();
}

// ================================================================
//  PROFILE SAYA
// ================================================================
function openProfileModal() {
  document.getElementById('profile-password').value = '';
  document.getElementById('profile-avatar').value = '';
  // Try to load current user avatar from PHP if we had it, but we can set it to default if empty
  profileModal.show();
}

async function saveProfile() {
  const username = document.getElementById('profile-username').value.trim();
  const name     = document.getElementById('profile-name').value.trim();
  const password = document.getElementById('profile-password').value;
  const avatarEl = document.getElementById('profile-avatar');

  if (!username) { toast('Username wajib diisi', 'error'); return; }

  const fd = new FormData();
  fd.append('action', 'update_profile');
  fd.append('username', username);
  fd.append('display_name', name);
  if (password) fd.append('password', password);
  if (avatarEl.files.length > 0) {
    fd.append('avatar', avatarEl.files[0]);
  }

  try {
    const res = await fetch('api_profile.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      toast('Profil berhasil diperbarui! Halaman akan dimuat ulang...');
      if (json.avatar_url) {
        document.getElementById('profile-avatar-preview').src = json.avatar_url;
      }
      profileModal.hide();
      setTimeout(() => location.reload(), 1500);
    } else {
      toast(json.message || 'Gagal menyimpan profil', 'error');
    }
  } catch (e) {
    toast('Gagal menghubungi server', 'error');
  }
}

// Preview avatar
document.getElementById('profile-avatar').addEventListener('change', function(e) {
  if (this.files && this.files[0]) {
    const reader = new FileReader();
    reader.onload = function(ev) {
      document.getElementById('profile-avatar-preview').src = ev.target.result;
    }
    reader.readAsDataURL(this.files[0]);
  }
});

// ================================================================
//  UTILS
// ================================================================
function esc(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Add style for toast animation
const style = document.createElement('style');
style.textContent = '@keyframes fadeIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}';
document.head.appendChild(style);
</script>
</body>
</html>
