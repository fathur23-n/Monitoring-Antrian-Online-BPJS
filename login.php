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
            // Log login
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
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Antrol BPJS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --ink:    #0a0e1a;
      --ink2:   #1c2333;
      --ink3:   #2d3748;
      --muted:  #64748b;
      --line:   rgba(255,255,255,.07);
      --accent: #4f8ef7;
      --accent2:#7c3aed;
      --glow:   rgba(79,142,247,.25);
      --white:  #ffffff;
      --surface:rgba(255,255,255,.04);
    }

    html, body {
      height: 100%;
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--ink);
      color: var(--white);
      overflow: hidden;
    }

    /* ── BACKGROUND ── */
    .bg {
      position: fixed; inset: 0; z-index: 0;
      background:
        radial-gradient(ellipse 80% 60% at 20% 10%, rgba(79,142,247,.12) 0%, transparent 60%),
        radial-gradient(ellipse 60% 50% at 80% 90%, rgba(124,58,237,.12) 0%, transparent 60%),
        linear-gradient(160deg, #0a0e1a 0%, #0f1623 50%, #0a0e1a 100%);
    }

    /* Grid lines */
    .bg::before {
      content: '';
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
      background-size: 48px 48px;
    }

    /* Floating orbs */
    .orb {
      position: fixed; border-radius: 50%;
      filter: blur(80px); pointer-events: none; z-index: 0;
      animation: drift 12s ease-in-out infinite;
    }
    .orb-1 { width:400px; height:400px; top:-100px; left:-100px; background:rgba(79,142,247,.08); animation-delay:0s; }
    .orb-2 { width:300px; height:300px; bottom:-80px; right:-80px; background:rgba(124,58,237,.1); animation-delay:-4s; }
    .orb-3 { width:200px; height:200px; top:50%; left:50%; background:rgba(79,142,247,.05); animation-delay:-8s; }

    @keyframes drift {
      0%,100% { transform: translate(0,0) scale(1); }
      33%      { transform: translate(30px,-20px) scale(1.05); }
      66%      { transform: translate(-20px,30px) scale(.95); }
    }

    /* ── LAYOUT ── */
    .page {
      position: relative; z-index: 1;
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1fr 480px;
    }

    /* ── LEFT PANEL ── */
    .left {
      display: flex; flex-direction: column;
      justify-content: center; align-items: flex-start;
      padding: 60px 80px;
      position: relative;
    }

    .brand {
      display: flex; align-items: center; gap: 14px;
      margin-bottom: 64px;
    }
    .brand-mark {
      width: 46px; height: 46px;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 20px;
      box-shadow: 0 0 32px var(--glow);
    }
    .brand-name {
      font-size: 18px; font-weight: 800;
      letter-spacing: -.3px;
      color: var(--white);
    }
    .brand-sub {
      font-size: 11px; color: var(--muted);
      font-family: 'Space Mono', monospace;
      margin-top: 2px; letter-spacing: .5px;
    }

    .hero-tag {
      display: inline-flex; align-items: center; gap: 7px;
      background: rgba(79,142,247,.1);
      border: 1px solid rgba(79,142,247,.2);
      padding: 6px 14px; border-radius: 99px;
      font-size: 11.5px; font-weight: 600;
      color: var(--accent);
      letter-spacing: .3px;
      margin-bottom: 24px;
    }
    .hero-tag::before {
      content: ''; width: 6px; height: 6px;
      background: var(--accent); border-radius: 50%;
      animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse {
      0%,100% { opacity:1; transform:scale(1); }
      50%      { opacity:.4; transform:scale(.7); }
    }

    .hero-title {
      font-size: clamp(36px, 4vw, 54px);
      font-weight: 800; line-height: 1.08;
      letter-spacing: -1.5px;
      margin-bottom: 20px;
    }
    .hero-title .grad {
      background: linear-gradient(135deg, var(--accent) 0%, #a78bfa 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .hero-desc {
      font-size: 15px; color: var(--muted); line-height: 1.7;
      max-width: 420px; margin-bottom: 48px;
    }

    .feat-list {
      display: flex; flex-direction: column; gap: 14px;
    }
    .feat-item {
      display: flex; align-items: center; gap: 12px;
      font-size: 13.5px; color: rgba(255,255,255,.65);
    }
    .feat-dot {
      width: 32px; height: 32px; border-radius: 8px;
      background: var(--surface);
      border: 1px solid var(--line);
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; flex-shrink: 0;
      color: var(--accent);
    }

    /* ── RIGHT PANEL ── */
    .right {
      display: flex; align-items: center; justify-content: center;
      padding: 40px 48px;
      background: rgba(255,255,255,.02);
      border-left: 1px solid var(--line);
      backdrop-filter: blur(20px);
    }

    .form-box {
      width: 100%; max-width: 360px;
    }

    .form-head {
      margin-bottom: 32px;
    }
    .form-head h2 {
      font-size: 26px; font-weight: 800;
      letter-spacing: -.5px; margin-bottom: 6px;
    }
    .form-head p {
      font-size: 13.5px; color: var(--muted);
    }

    .alert {
      display: flex; align-items: center; gap: 10px;
      background: rgba(239,68,68,.1);
      border: 1px solid rgba(239,68,68,.25);
      color: #fca5a5;
      padding: 12px 14px; border-radius: 10px;
      font-size: 13px; font-weight: 500;
      margin-bottom: 20px;
      animation: shake .4s ease;
    }
    @keyframes shake {
      0%,100% { transform:translateX(0); }
      20%,60%  { transform:translateX(-6px); }
      40%,80%  { transform:translateX(6px); }
    }

    .field { margin-bottom: 18px; }
    .field label {
      display: block; font-size: 12px; font-weight: 700;
      color: rgba(255,255,255,.5);
      text-transform: uppercase; letter-spacing: .8px;
      margin-bottom: 8px;
    }
    .input-wrap {
      position: relative;
    }
    .input-icon {
      position: absolute; left: 14px; top: 50%;
      transform: translateY(-50%);
      color: var(--muted); font-size: 14px;
      pointer-events: none;
      transition: color .2s;
    }
    .input-wrap input {
      width: 100%;
      padding: 13px 16px 13px 42px;
      background: rgba(255,255,255,.05);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 10px;
      color: var(--white);
      font-size: 14px; font-family: 'Plus Jakarta Sans', sans-serif;
      outline: none;
      transition: all .2s;
    }
    .input-wrap input::placeholder { color: rgba(255,255,255,.2); }
    .input-wrap input:focus {
      background: rgba(79,142,247,.07);
      border-color: rgba(79,142,247,.4);
      box-shadow: 0 0 0 3px rgba(79,142,247,.1);
    }
    .input-wrap input:focus + .input-icon,
    .input-wrap:focus-within .input-icon { color: var(--accent); }
    .eye-btn {
      position: absolute; right: 12px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      color: var(--muted); font-size: 14px;
      padding: 4px; transition: color .2s;
    }
    .eye-btn:hover { color: var(--white); }

    .submit-btn {
      width: 100%; padding: 14px;
      background: linear-gradient(135deg, var(--accent) 0%, var(--accent2) 100%);
      border: none; border-radius: 10px;
      color: var(--white);
      font-size: 14.5px; font-weight: 700;
      font-family: 'Plus Jakarta Sans', sans-serif;
      cursor: pointer; margin-top: 6px;
      position: relative; overflow: hidden;
      transition: all .25s;
      box-shadow: 0 8px 24px rgba(79,142,247,.25);
    }
    .submit-btn::before {
      content: '';
      position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,.15), transparent);
      opacity: 0; transition: opacity .25s;
    }
    .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(79,142,247,.35); }
    .submit-btn:hover::before { opacity: 1; }
    .submit-btn:active { transform: translateY(0); }
    .btn-inner { display: flex; align-items: center; justify-content: center; gap: 8px; }

    .form-footer {
      margin-top: 28px;
      padding-top: 20px;
      border-top: 1px solid var(--line);
      text-align: center;
      font-size: 11.5px;
      color: rgba(255,255,255,.2);
      font-family: 'Space Mono', monospace;
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 900px) {
      .page { grid-template-columns: 1fr; }
      .left  { display: none; }
      .right {
        border-left: none;
        background: var(--ink);
        min-height: 100vh;
      }
    }
  </style>
</head>
<body>

<div class="bg"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="page">

  <!-- LEFT -->
  <div class="left">
    <div class="brand">
      <div class="brand-mark"><i class="fas fa-hospital-alt"></i></div>
      <div>
        <div class="brand-name">Antrol BPJS</div>
        <div class="brand-sub">v1.0 · RS Emma Mojokerto</div>
      </div>
    </div>

    <div class="hero-tag">Sistem Antrean Digital</div>

    <h1 class="hero-title">
      Kelola Antrean<br>
      <span class="grad">BPJS Lebih Efisien</span>
    </h1>

    <p class="hero-desc">
      Platform terintegrasi untuk pengiriman task ID antrean online BPJS Kesehatan,
      monitoring real-time, dan analitik berbasis data.
    </p>

    <div class="feat-list">
      <div class="feat-item">
        <div class="feat-dot"><i class="fas fa-calendar-check"></i></div>
        <span>Kirim task ID antrean secara real-time</span>
      </div>
      <div class="feat-item">
        <div class="feat-dot"><i class="fas fa-chart-pie"></i></div>
        <span>Dashboard analitik JKN vs Bridging</span>
      </div>
      <div class="feat-item">
        <div class="feat-dot"><i class="fas fa-shield-alt"></i></div>
        <span>Log aktivitas & keamanan terpadu</span>
      </div>
      <div class="feat-item">
        <div class="feat-dot"><i class="fas fa-database"></i></div>
        <span>Cache cerdas — hemat kuota API</span>
      </div>
    </div>
  </div>

  <!-- RIGHT -->
  <div class="right">
    <div class="form-box">

      <div class="form-head">
        <h2>Selamat Datang 👋</h2>
        <p>Masuk untuk mengakses dashboard</p>
      </div>

      <?php if ($error): ?>
      <div class="alert">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <div class="field">
          <label>Username</label>
          <div class="input-wrap">
            <input type="text" name="username" placeholder="Masukkan username"
              value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
            <i class="fas fa-user input-icon"></i>
          </div>
        </div>

        <div class="field">
          <label>Password</label>
          <div class="input-wrap">
            <input type="password" name="password" id="passInp" placeholder="Masukkan password" required>
            <i class="fas fa-lock input-icon"></i>
            <button type="button" class="eye-btn" onclick="togglePass()">
              <i class="fas fa-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="submit-btn">
          <span class="btn-inner">
            <i class="fas fa-arrow-right-to-bracket"></i>
            Masuk ke Dashboard
          </span>
        </button>
      </form>

      <div class="form-footer">
        © <?= date('Y') ?> Antrol BPJS · Mohammad Fathur Roziq
      </div>
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