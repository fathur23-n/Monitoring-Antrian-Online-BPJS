<?php
session_start();

// Proteksi ketat
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin' ||
    ($_SESSION['username'] ?? '') !== 'rsdev'
) {
    header('Location: index.php');
    exit;
}

$fullname = $_SESSION['fullname'] ?? 'User';
$username = $_SESSION['username'] ?? '';
$initials = mb_strtoupper(mb_substr($fullname, 0, 2));
?>
<!DOCTYPE html>
<html lang="id" data-theme="purple-rain">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log Aktivitas — Antrol BPJS</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <script>
    const _t = localStorage.getItem('antrol-theme');
    if (_t) document.documentElement.setAttribute('data-theme', _t);
  </script>
</head>
<body>
<div class="app">

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><i class="fas fa-hospital-alt"></i></div>
      <span class="brand-text">Antrol BPJS</span>
    </div>
    <nav class="sidebar-nav">
      <a href="index.php" class="nav-item"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a>
      <a href="index.php?view=antrean" class="nav-item"><i class="fas fa-calendar-check"></i><span>Antrean</span></a>
      <a href="#" class="nav-item nav-disabled" title="Segera hadir">
        <i class="fas fa-ban"></i><span>Riwayat Batal</span>
        <small class="badge-soon">Soon</small>
      </a>
      <a href="tracker.php" class="nav-item active"><i class="fas fa-list-alt"></i><span>Log Aktivitas</span></a>
    </nav>
    <div class="sidebar-about">
      <div class="sa-version">v 1.0</div>
      <div class="sa-name">Antrol BPJS</div>
      <div class="sa-desc">Sistem pengiriman antrean online BPJS Kesehatan untuk manajemen antrian pasien rawat jalan.</div>
      <div class="sa-donate">
        <div class="sa-donate-label"><i class="fas fa-heart"></i> Dukung Developer</div>
        <div class="sa-donate-bank">
          <span class="sa-bank-name">BNI</span>
          <span class="sa-bank-rek">1635501500</span>
          <button class="sa-copy-btn" onclick="navigator.clipboard.writeText('1635501500').then(()=>{this.innerHTML='<i class=\'fas fa-check\'></i>';setTimeout(()=>{this.innerHTML='<i class=\'fas fa-copy\'></i>';},1500)})" title="Salin nomor rekening">
            <i class="fas fa-copy"></i>
          </button>
        </div>
        <div class="sa-bank-name-owner">a.n Mohammad Fathur Roziq</div>
      </div>
    </div>
    <button class="sidebar-toggle" id="sidebarToggle">
      <i class="fas fa-chevron-left" id="toggleIcon"></i>
    </button>
  </aside>

  <!-- MAIN -->
  <main class="main" id="main">
    <div class="topbar">
      <h1 class="page-title"><i class="fas fa-list-alt" style="color:var(--p500);margin-right:8px;"></i>Log Aktivitas</h1>
      <div class="topbar-right">
        <div class="clock-wrap"><i class="fas fa-clock"></i><span id="liveClock">--:--:--</span></div>
        <div class="topbar-user">
          <div class="su-avatar" style="width:34px;height:34px;font-size:12px;"><?= $initials ?></div>
          <div class="su-info" style="line-height:1.3;">
            <div class="su-name" style="color:var(--text-primary);"><?= htmlspecialchars($fullname) ?></div>
            <div class="su-username">@<?= htmlspecialchars($username) ?></div>
          </div>
          <a href="logout.php" class="su-logout" style="color:var(--text-muted);" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
        </div>
        <button class="btn-theme-toggle" id="btnThemeToggle" title="Ganti Tema">
          <i class="fas fa-palette"></i>
        </button>
      </div>
    </div>

    <div class="content-area">
      <!-- FILTER -->
      <div class="card" style="margin-bottom:16px;">
        <div class="tracker-filter-row">
          <div class="date-range-wrap">
            <div class="date-input-wrap">
              <i class="fas fa-calendar-day"></i>
              <input type="date" id="trStart">
            </div>
            <span class="date-range-sep"><i class="fas fa-arrow-right"></i></span>
            <div class="date-input-wrap">
              <i class="fas fa-calendar-day"></i>
              <input type="date" id="trEnd">
            </div>
          </div>
          <select id="trUser" class="tr-select">
            <option value="">Semua User</option>
          </select>
          <select id="trAksi" class="tr-select">
            <option value="">Semua Aksi</option>
            <option value="login">Login</option>
            <option value="kirim_task">Kirim Task</option>
            <option value="batal_antrean">Batal Antrean</option>
          </select>
          <button class="btn btn-primary" id="btnLoadTracker">
            <i class="fas fa-search"></i> Tampilkan
          </button>
        </div>
      </div>

      <!-- STAT MINI -->
      <div class="tracker-stats" id="trackerStats" style="display:none;">
        <div class="tr-stat-card">
          <div class="tr-stat-icon" style="background:var(--p50);color:var(--p500);"><i class="fas fa-list"></i></div>
          <div><div class="tr-stat-val" id="tr-total">0</div><div class="tr-stat-label">Total Log</div></div>
        </div>
        <div class="tr-stat-card">
          <div class="tr-stat-icon" style="background:#e0f2fe;color:#0ea5e9;"><i class="fas fa-sign-in-alt"></i></div>
          <div><div class="tr-stat-val" id="tr-login">0</div><div class="tr-stat-label">Login</div></div>
        </div>
        <div class="tr-stat-card">
          <div class="tr-stat-icon" style="background:var(--green-100);color:var(--green-500);"><i class="fas fa-paper-plane"></i></div>
          <div><div class="tr-stat-val" id="tr-task">0</div><div class="tr-stat-label">Kirim Task</div></div>
        </div>
        <div class="tr-stat-card">
          <div class="tr-stat-icon" style="background:var(--red-100);color:var(--red-500);"><i class="fas fa-ban"></i></div>
          <div><div class="tr-stat-val" id="tr-batal">0</div><div class="tr-stat-label">Batal Antrean</div></div>
        </div>
      </div>

      <!-- TABLE -->
      <div class="card">
        <div class="table-wrap">
          <table id="tblTracker">
            <thead>
              <tr>
                <th>#</th>
                <th>Waktu</th>
                <th>User</th>
                <th>Nama</th>
                <th>Aksi</th>
                <th>Keterangan</th>
                <th>IP</th>
              </tr>
            </thead>
            <tbody id="tblTrackerBody">
              <tr><td colspan="7" class="state-empty">
                <i class="fas fa-list-alt"></i>
                <p>Pilih periode dan klik Tampilkan</p>
              </td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- FOOTER -->
<div class="app-footer">
  <span>© <?= date('Y') ?> Antrol BPJS — v1.0</span>
  <span>Made with <i class="fas fa-heart" style="color:#f87171;"></i> by <strong>Mohammad Fathur Roziq</strong></span>
</div>

<!-- THEME PANEL -->
<div class="theme-backdrop" id="themeBackdrop"></div>
<div class="theme-panel" id="themePanel">
  <div class="theme-panel-header">
    <span><i class="fas fa-palette"></i> Pilih Tema</span>
    <button class="theme-close" id="themeClose"><i class="fas fa-times"></i></button>
  </div>
  <div class="theme-grid" id="themeGrid"></div>
</div>

<div id="toast-container"></div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="js/tracker.js"></script>
</body>
</html>