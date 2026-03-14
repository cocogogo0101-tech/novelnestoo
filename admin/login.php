<?php
require_once __DIR__.'/config.php';
if (is_admin()) { header('Location: panel.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_password($_POST['password'] ?? '')) {
        $_SESSION['novelnest_admin'] = true;
        $_SESSION['login_time'] = time();
        header('Location: panel.php'); exit;
    }
    $err = 'الرمز غير صحيح، حاول مجدداً';
    sleep(1); // brute-force slowdown
}
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>دخول — NovelNest Admin</title>
  <link rel="stylesheet" href="../style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔐</text></svg>">
</head>
<body>
<div id="reading-progress"><div id="reading-progress-bar"></div></div>
<header class="topbar">
  <a href="../index.php" class="topbar-brand">
    <div class="topbar-logo">📚</div>
    <h1>Novel<span>Nest</span></h1>
  </a>
  <div class="topbar-actions">
    <button class="btn-icon" id="theme-toggle" title="تبديل الثيم">☀️</button>
  </div>
</header>

<div class="login-page">
  <div class="login-card">
    <div class="login-card-header">
      <div class="login-logo">🔐</div>
      <h2>لوحة التحكم</h2>
      <p>أدخل الرمز السري للدخول</p>
    </div>
    <div class="login-card-body">
      <?php if ($err): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($err); ?></div>
      <?php endif; ?>
      <form method="post">
        <?php echo csrf_field(); ?>
        <div class="form-row">
          <label class="form-label">الرمز السري</label>
          <input type="password" name="password" class="form-input"
                 placeholder="••••••••" autocomplete="current-password" autofocus>
        </div>
        <div class="form-row" style="margin-top:8px">
          <button type="submit" class="btn btn-gold" style="width:100%;justify-content:center">
            دخول ←
          </button>
        </div>
      </form>
      <p class="small text-muted text-center" style="margin-top:16px">
        اللوحة مخفية — استخدم الرابط المباشر فقط
      </p>
    </div>
  </div>
</div>
<div id="toast-container"></div>
<script src="../script.js"></script>
</body>
</html>
