<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'config.php';
    $uname = trim($_POST['username'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($uname && $pass) {
        $stmt = $pdo->prepare("SELECT id, username, fullname, password, role FROM mlite_users WHERE username = ? LIMIT 1");
        $stmt->execute([$uname]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role']     = $user['role'] ?? 'user';

            // Log aktivitas login
            try {
                $ip  = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '-')[0];
                $now = (new DateTime('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s');
                $lg  = $pdo->prepare("INSERT INTO log_tracker (user,fullname,aksi,keterangan,ip,created_at) VALUES (?,?,?,?,?,?)");
                $lg->execute([$user['username'], $user['fullname'], 'login', 'Login berhasil ke sistem', $ip, $now]);
            } catch (Exception $e) { /* silent */ }

            header('Location: index.php'); exit;
        }
        $error = 'Username atau password salah.';
    } else {
        $error = 'Isi username dan password.';
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="purple-rain">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Antrol BPJS</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script>
    // Apply saved theme before render
    const t = localStorage.getItem('antrol-theme');
    if (t) document.documentElement.setAttribute('data-theme', t);
  </script>
</head>
<body class="login-body">

<div class="login-page">
  <!-- LEFT PANEL -->
  <div class="login-left">
    <div class="login-left-content">
      <div class="login-logo">
        <div class="login-logo-icon"><i class="fas fa-hospital-alt"></i></div>
        <div>
          <h1>Antrol BPJS</h1>
          <p>Sistem Pengiriman Antrean Online</p>
        </div>
      </div>
      <div class="login-features">
        <div class="lf-item"><i class="fas fa-calendar-check"></i><span>Lihat antrean per tanggal</span></div>
        <div class="lf-item"><i class="fas fa-paper-plane"></i><span>Kirim task ID ke BPJS</span></div>
        <div class="lf-item"><i class="fas fa-chart-bar"></i><span>Dashboard & statistik</span></div>
        <div class="lf-item"><i class="fas fa-history"></i><span>Riwayat task terkirim</span></div>
      </div>
    </div>
    <div class="login-left-art">
      <i class="fas fa-calendar-check"></i>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div class="login-right">
    <div class="login-form-box">
      <div class="login-form-header">
        <h2>Selamat Datang 👋</h2>
        <p>Masuk ke akun Anda untuk melanjutkan</p>
      </div>

      <?php if ($error): ?>
      <div class="login-alert">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" class="login-form" autocomplete="off">
        <div class="lf-group">
          <label>Username</label>
          <div class="lf-input-wrap">
            <i class="fas fa-user"></i>
            <input type="text" name="username" placeholder="Masukkan username" required autofocus
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
          </div>
        </div>
        <div class="lf-group">
          <label>Password</label>
          <div class="lf-input-wrap">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="passInp" placeholder="Masukkan password" required>
            <button type="button" class="lf-eye" onclick="togglePass()">
              <i class="fas fa-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>
        <button type="submit" class="lf-submit">
          <i class="fas fa-sign-in-alt"></i> Masuk
        </button>
      </form>
    </div>
  </div>
</div>

<script>
function togglePass() {
  const inp = document.getElementById('passInp');
  const ico = document.getElementById('eyeIcon');
  inp.type = inp.type === 'password' ? 'text' : 'password';
  ico.className = inp.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
</script>
</body>
</html>