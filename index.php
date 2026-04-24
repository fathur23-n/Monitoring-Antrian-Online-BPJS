<?php
require 'auth.php';
$fullname = $_SESSION['fullname'] ?? 'User';
$username = $_SESSION['username'] ?? '';
$initials = mb_strtoupper(mb_substr($fullname, 0, 2));
$role     = $_SESSION['role']     ?? 'user';
$isAdmin  = ($role === 'admin' && $username === 'rsdev');
?>
<!DOCTYPE html>
<html lang="id" data-theme="purple-rain">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Antrol BPJS — Dashboard</title>
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
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><i class="fas fa-hospital-alt"></i></div>
      <span class="brand-text">Antrol BPJS</span>
    </div>
    <nav class="sidebar-nav">
      <a href="#" class="nav-item active" data-view="dashboard">
        <i class="fas fa-chart-pie"></i><span>Dashboard</span>
      </a>
      <a href="#" class="nav-item" data-view="antrean">
        <i class="fas fa-calendar-check"></i><span>Antrean</span>
      </a>
      <a href="#" class="nav-item nav-disabled" title="Segera hadir">
        <i class="fas fa-ban"></i><span>Riwayat Batal</span>
        <small class="badge-soon">Soon</small>
      </a>
      <?php if ($isAdmin): ?>
      <a href="tracker.php" class="nav-item">
        <i class="fas fa-list-alt"></i><span>Log Aktivitas</span>
      </a>
      <?php endif; ?>
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

  <main class="main" id="main">
    <div class="topbar">
      <h1 class="page-title" id="pageTitle">Dashboard</h1>
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

    <!-- VIEW: DASHBOARD -->
    <section id="view-dashboard" class="view active">
      <div class="content-area">
        <div class="dash-toprow">
          <div class="date-range-wrap">
            <div class="date-input-wrap">
              <i class="fas fa-calendar-day"></i>
              <input type="date" id="dashStart">
            </div>
            <span class="date-range-sep"><i class="fas fa-arrow-right"></i></span>
            <div class="date-input-wrap">
              <i class="fas fa-calendar-day"></i>
              <input type="date" id="dashEnd">
            </div>
          </div>
          <button class="btn btn-primary" id="btnDashLoad">
            <i class="fas fa-sync-alt"></i> Tampilkan
          </button>
          <div class="dash-loading" id="dashLoading" style="display:none;">
            <div class="load-progress">
              <div class="load-bar" id="loadBar"></div>
            </div>
            <span id="loadLabel">Memuat...</span>
          </div>
        </div>

        <div class="stat-cards" id="statCards" style="display:none;">
          <div class="stat-card sc-total">
            <div class="sc-icon"><i class="fas fa-users"></i></div>
            <div class="sc-body"><div class="sc-val" id="sc-total">0</div><div class="sc-label">Total Antrean</div></div>
          </div>
          <div class="stat-card sc-selesai">
            <div class="sc-icon"><i class="fas fa-check-circle"></i></div>
            <div class="sc-body"><div class="sc-val" id="sc-selesai">0</div><div class="sc-label">Selesai</div></div>
          </div>
          <div class="stat-card sc-belum">
            <div class="sc-icon"><i class="fas fa-clock"></i></div>
            <div class="sc-body"><div class="sc-val" id="sc-belum">0</div><div class="sc-label">Belum Dilayani</div></div>
          </div>
          <div class="stat-card sc-batal">
            <div class="sc-icon"><i class="fas fa-times-circle"></i></div>
            <div class="sc-body"><div class="sc-val" id="sc-batal">0</div><div class="sc-label">Dibatalkan</div></div>
          </div>
          <div class="stat-card sc-sep">
            <div class="sc-icon"><i class="fas fa-file-medical"></i></div>
            <div class="sc-body"><div class="sc-val" id="sc-sep">—</div><div class="sc-label">SEP Terbit</div></div>
          </div>
        </div>

        <div class="chart-grid" id="chartGrid" style="display:none;">
          <div class="chart-card">
            <div class="chart-card-title"><i class="fas fa-chart-bar"></i> JKN vs Bridging — Status</div>
            <div class="chart-wrap" style="height:260px;"><canvas id="chartJkn"></canvas></div>
          </div>
          <div class="chart-card">
            <div class="chart-card-title"><i class="fas fa-chart-donut"></i> Status Antrean</div>
            <div class="chart-wrap" style="height:260px;"><canvas id="chartStatus"></canvas></div>
          </div>
        </div>

        <!-- PERSENTASE SECTION -->
        <div id="pctSection" style="display:none;">
          <div class="pct-grid">
            <div class="pct-card">
              <div class="pct-header">
                <div class="pct-icon pct-icon-all"><i class="fas fa-layer-group"></i></div>
                <div>
                  <div class="pct-title">Semua Sumber</div>
                  <div class="pct-total" id="pct-all-total">0 pasien</div>
                </div>
              </div>
              <div class="pct-rows">
                <div class="pct-row">
                  <span class="pct-label pct-selesai">Selesai</span>
                  <div class="pct-bar-wrap"><div class="pct-bar pct-bar-green" id="pct-all-s-bar"></div></div>
                  <span class="pct-num" id="pct-all-s-num">0%</span>
                </div>
                <div class="pct-row">
                  <span class="pct-label pct-belum">Belum</span>
                  <div class="pct-bar-wrap"><div class="pct-bar pct-bar-orange" id="pct-all-b-bar"></div></div>
                  <span class="pct-num" id="pct-all-b-num">0%</span>
                </div>
                <div class="pct-row">
                  <span class="pct-label pct-batal">Batal</span>
                  <div class="pct-bar-wrap"><div class="pct-bar pct-bar-red" id="pct-all-x-bar"></div></div>
                  <span class="pct-num" id="pct-all-x-num">0%</span>
                </div>
              </div>
            </div>
            <div class="pct-card">
              <div class="pct-header">
                <div class="pct-icon pct-icon-jkn"><i class="fas fa-mobile-alt"></i></div>
                <div>
                  <div class="pct-title">Mobile JKN</div>
                  <div class="pct-total" id="pct-jkn-total">0 pasien</div>
                </div>
              </div>
              <div class="pct-rows">
                <div class="pct-row">
                  <span class="pct-label pct-selesai">Selesai</span>
                  <div class="pct-bar-wrap"><div class="pct-bar pct-bar-green" id="pct-jkn-s-bar"></div></div>
                  <span class="pct-num" id="pct-jkn-s-num">0%</span>
                </div>
                <div class="pct-row">
                  <span class="pct-label pct-belum">Belum</span>
                  <div class="pct-bar-wrap"><div class="pct-bar pct-bar-orange" id="pct-jkn-b-bar"></div></div>
                  <span class="pct-num" id="pct-jkn-b-num">0%</span>
                </div>
                <div class="pct-row">
                  <span class="pct-label pct-batal">Batal</span>
                  <div class="pct-bar-wrap"><div class="pct-bar pct-bar-red" id="pct-jkn-x-bar"></div></div>
                  <span class="pct-num" id="pct-jkn-x-num">0%</span>
                </div>
              </div>
            </div>
            <div class="pct-card">
              <div class="pct-header">
                <div class="pct-icon pct-icon-bridging"><i class="fas fa-network-wired"></i></div>
                <div>
                  <div class="pct-title">Bridging</div>
                  <div class="pct-total" id="pct-brg-total">0 pasien</div>
                </div>
              </div>
              <div class="pct-rows">
                <div class="pct-row">
                  <span class="pct-label pct-selesai">Selesai</span>
                  <div class="pct-bar-wrap"><div class="pct-bar pct-bar-green" id="pct-brg-s-bar"></div></div>
                  <span class="pct-num" id="pct-brg-s-num">0%</span>
                </div>
                <div class="pct-row">
                  <span class="pct-label pct-belum">Belum</span>
                  <div class="pct-bar-wrap"><div class="pct-bar pct-bar-orange" id="pct-brg-b-bar"></div></div>
                  <span class="pct-num" id="pct-brg-b-num">0%</span>
                </div>
                <div class="pct-row">
                  <span class="pct-label pct-batal">Batal</span>
                  <div class="pct-bar-wrap"><div class="pct-bar pct-bar-red" id="pct-brg-x-bar"></div></div>
                  <span class="pct-num" id="pct-brg-x-num">0%</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card" id="belumCard" style="display:none;">
          <div class="belum-header">
            <div>
              <h3 class="belum-title"><i class="fas fa-user-clock"></i> Pasien Belum Dilayani</h3>
              <p class="belum-sub">Pasien yang statusnya belum selesai dilayani</p>
            </div>
            <span class="belum-count-badge" id="belumCount">0 pasien</span>
          </div>
          <div class="table-wrap">
            <table id="tblBelum">
              <thead>
                <tr><th>#</th><th>No RM</th><th>Nama Pasien</th><th>Kode Booking</th><th>Poli</th><th>Jam Praktek</th><th>User SEP</th></tr>
              </thead>
              <tbody id="tblBelumBody"></tbody>
            </table>
          </div>
        </div>

        <div id="dashEmpty" class="dash-empty-state">
          <i class="fas fa-chart-pie"></i>
          <p>Data dashboard akan muncul setelah halaman dimuat</p>
        </div>
      </div>
    </section>

    <!-- VIEW: ANTREAN -->
    <section id="view-antrean" class="view">
      <div class="sticky-controls" id="stickyControls">
        <div class="card sticky-card">
          <div class="date-row">
            <div class="date-input-wrap">
              <i class="fas fa-calendar-day"></i>
              <input type="date" id="tglAntrean">
            </div>
            <button class="btn btn-primary" id="btnLoad">
              <i class="fas fa-search"></i> Tampilkan
            </button>
            <button class="btn btn-ghost" id="btnForceRefresh" title="Paksa sync dari BPJS" style="display:none;">
              <i class="fas fa-sync-alt"></i> Sync BPJS
            </button>
          </div>
          <div class="filter-row" id="filterRow" style="display:none;">
            <div class="filter-chips">
              <button class="chip chip-all active" data-filter="semua">Semua <span class="chip-count" id="cnt-semua">0</span></button>
              <button class="chip chip-done" data-filter="selesai"><i class="fas fa-check-circle"></i> Selesai <span class="chip-count" id="cnt-selesai">0</span></button>
              <button class="chip chip-pending" data-filter="belum"><i class="fas fa-clock"></i> Belum <span class="chip-count" id="cnt-belum">0</span></button>
              <button class="chip chip-cancel" data-filter="batal"><i class="fas fa-times-circle"></i> Batal <span class="chip-count" id="cnt-batal">0</span></button>
            </div>
            <div class="total-badge">Total: <strong id="cnt-total">0</strong></div>
          </div>
          <div id="statsBar" style="display:none;">
            <div class="stats-bar">
              <div class="stat-group">
                <div class="stat-icon stat-icon-jkn"><i class="fas fa-mobile-alt"></i></div>
                <div class="stat-detail">
                  <div class="stat-label">Mobile JKN</div>
                  <div class="stat-counts">
                    <span class="stat-pill pill-green">Selesai <strong id="jkn-selesai">0</strong></span>
                    <span class="stat-sep-line"></span>
                    <span class="stat-pill pill-orange">Belum <strong id="jkn-belum">0</strong></span>
                  </div>
                </div>
              </div>
              <div class="stat-divider"></div>
              <div class="stat-group">
                <div class="stat-icon stat-icon-bridging"><i class="fas fa-network-wired"></i></div>
                <div class="stat-detail">
                  <div class="stat-label">Bridging</div>
                  <div class="stat-counts">
                    <span class="stat-pill pill-green">Selesai <strong id="bridging-selesai">0</strong></span>
                    <span class="stat-sep-line"></span>
                    <span class="stat-pill pill-orange">Belum <strong id="bridging-belum">0</strong></span>
                  </div>
                </div>
              </div>
              <div class="stat-divider"></div>
              <div class="stat-group">
                <div class="stat-icon stat-icon-sep"><i class="fas fa-file-medical"></i></div>
                <div class="stat-detail">
                  <div class="stat-label">SEP Terbit</div>
                  <div class="stat-counts">
                    <span class="stat-pill pill-purple">Total <strong id="sep-total">—</strong></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="content-area" style="padding-top:12px;">
        <div class="card">
          <div class="table-wrap">
            <table id="tblAntrean">
              <thead>
                <tr>
                  <th>#</th><th>Kode Booking</th><th>Tanggal</th>
                  <th>Poli</th><th>Dokter</th><th>Jam Praktek</th>
                  <th>Status</th><th>Estimasi Dilayani</th><th>Aksi</th>
                </tr>
              </thead>
              <tbody id="tblBody">
                <tr><td colspan="9" class="state-empty">
                  <i class="fas fa-calendar-times"></i>
                  <p>Pilih tanggal dan klik "Tampilkan"</p>
                </td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
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

<!-- MODAL: ATUR WAKTU -->
<div class="modal-overlay" id="modalWaktu">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-icon"><i class="fas fa-clock"></i></div>
      <div>
        <h2 class="modal-title" id="modalWaktuTitle">Atur Waktu • TASK 3</h2>
        <p class="modal-sub">Sesuaikan waktu pelayanan</p>
      </div>
    </div>
    <div class="modal-body">
      <div class="info-pill"><i class="fas fa-calendar"></i><span id="modalTanggal">—</span></div>
      <div class="time-inputs">
        <div class="time-field"><label>Jam</label><input type="number" id="inpJam" min="0" max="23" placeholder="HH"></div>
        <div class="time-sep">:</div>
        <div class="time-field"><label>Menit</label><input type="number" id="inpMenit" min="0" max="59" placeholder="MM"></div>
        <div class="time-sep">:</div>
        <div class="time-field"><label>Detik</label><input type="number" id="inpDetik" min="0" max="59" placeholder="SS"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" id="btnBatalWaktu"><i class="fas fa-times"></i> Batal</button>
      <button class="btn btn-primary" id="btnKirimWaktu"><i class="fas fa-paper-plane"></i> Kirim</button>
    </div>
  </div>
</div>

<!-- MODAL: TASK TERKIRIM -->
<div class="modal-overlay" id="modalRiwayat">
  <div class="modal-box modal-wide">
    <div class="modal-header">
      <div class="modal-icon modal-icon-green"><i class="fas fa-history"></i></div>
      <div>
        <h2 class="modal-title" id="modalRiwayatTitle">Task Terkirim</h2>
        <p class="modal-sub">Riwayat pengiriman task ID</p>
      </div>
    </div>
    <div class="modal-body" style="padding:16px 24px;">
      <div id="timelineContainer" style="max-height:420px;overflow-y:auto;"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" id="btnTutupRiwayat"><i class="fas fa-times"></i> Tutup</button>
    </div>
  </div>
</div>

<!-- MODAL: BATAL ANTREAN -->
<div class="modal-overlay" id="modalBatal">
  <div class="modal-box" style="max-width:480px;">
    <div class="modal-header" style="background:linear-gradient(135deg,#7f1d1d,#dc2626);">
      <div class="modal-icon" style="background:rgba(255,255,255,.15);"><i class="fas fa-ban"></i></div>
      <div>
        <h2 class="modal-title" id="modalBatalTitle">Batal Antrean</h2>
        <p class="modal-sub">Tindakan ini tidak dapat dibatalkan</p>
      </div>
    </div>
    <div class="modal-body">
      <div class="batal-info-box">
        <div class="batal-info-row">
          <span class="batal-info-label"><i class="fas fa-ticket-alt"></i> Kode Booking</span>
          <span class="batal-info-val" id="batalKode">—</span>
        </div>
        <div class="batal-info-row">
          <span class="batal-info-label"><i class="fas fa-id-card"></i> No RM</span>
          <span class="batal-info-val" id="batalRM">—</span>
        </div>
        <div class="batal-info-row">
          <span class="batal-info-label"><i class="fas fa-hospital"></i> Poli</span>
          <span class="batal-info-val" id="batalPoli">—</span>
        </div>
      </div>
      <div class="batal-alasan-wrap">
        <label class="batal-alasan-label">
          <i class="fas fa-comment-alt"></i> Alasan Pembatalan <span style="color:var(--red-500);">*</span>
        </label>
        <textarea id="batalAlasan" class="batal-textarea" rows="3" maxlength="200"
          placeholder="Tuliskan alasan pembatalan antrean..."></textarea>
        <div class="batal-char-count"><span id="batalCharCount">0</span>/200 karakter</div>
      </div>
      <div class="batal-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <span>Data akan dikirim ke BPJS dan tidak dapat dipulihkan.</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" id="btnBatalCancel"><i class="fas fa-times"></i> Batal</button>
      <button class="btn btn-danger" id="btnBatalKirim"><i class="fas fa-ban"></i> Konfirmasi Batal</button>
    </div>
  </div>
</div>

<button id="btnScrollTop" class="btn-scroll-top" title="Kembali ke atas">
  <i class="fas fa-chevron-up"></i>
</button>
<div id="toast-container"></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>