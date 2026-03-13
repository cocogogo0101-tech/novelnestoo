<?php
require_once __DIR__ . '/config.php';

// إذا مصفوفة الجلسة موجودة يدخل تلقائياً
if (isset($_SESSION['novelnest_admin']) && $_SESSION['novelnest_admin'] === true) {
    header('Location: panel.php'); exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';
    if ($pw === ADMIN_PASSWORD) {
        $_SESSION['novelnest_admin'] = true;
        header('Location: panel.php'); exit;
    } else {
        $err = 'الرمز غير صحيح';
    }
}
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>دخول لوحة NovelNest</title>
  <link rel="stylesheet" href="../style.css">
  <style>
    .login-box{ max-width:400px; margin:80px auto; padding:20px; background:var(--card-bg); border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,0.06); }
    .row{ margin-bottom:12px }
    input[type=password]{ width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; }
    button{ padding:10px 14px; border-radius:8px; background:var(--accent); color:#fff; border:none }
    .err{ color:#c33; margin-bottom:8px }
  </style>
</head>
<body>
  <main class="container">
    <div class="login-box">
      <h2>دخول لوحة التحكم</h2>
      <?php if ($err): ?><div class="err"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
      <form method="post">
        <div class="row"><input type="password" name="password" placeholder="الرمز السري"></div>
        <div class="row"><button type="submit">دخول</button></div>
      </form>
      <p class="small muted">اللوحة مخفية — استخدم الرابط المباشر فقط.</p>
    </div>
  </main>
</body>
</html>