<?php
// index.php — صفحة المكتبة الرئيسية (ديناميكية)
// تعرض كل المجلدات داخل /novels بناء على info.json إن وُجد

function slug_to_display($s){ return htmlspecialchars($s); }

$base = __DIR__;
$novels_dir = $base . '/novels';
$novels = [];

// scan novels directory
if (is_dir($novels_dir)) {
    $entries = array_diff(scandir($novels_dir), ['.','..']);
    foreach ($entries as $e) {
        $path = $novels_dir . '/' . $e;
        if (is_dir($path)) {
            $infoFile = $path . '/info.json';
            $info = ['title' => $e, 'summary' => '', 'cover' => '', 'slug' => $e, 'chapters' => []];
            if (file_exists($infoFile)) {
                $raw = file_get_contents($infoFile);
                $json = json_decode($raw, true);
                if (is_array($json)) $info = array_merge($info, $json);
            }
            // count chapters if folder exists
            $chapDir = $path . '/chapters';
            if (is_dir($chapDir)) {
                $files = array_diff(scandir($chapDir), ['.','..']);
                $ch = [];
                foreach ($files as $f) if (preg_match('/\.(html|htm)$/i', $f)) $ch[] = $f;
                sort($ch);
                $info['chapters'] = $ch;
            }
            $novels[] = $info;
        }
    }
    // sort by title
    usort($novels, function($a,$b){ return strcmp(mb_strtolower($a['title']), mb_strtolower($b['title'])); });
}
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>NovelNest — المكتبة</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="topbar">
    <div class="brand">
      <h1>NovelNest</h1>
    </div>
    <div class="controls">
      <button id="theme-toggle" aria-label="تبديل الثيم">☀️</button>
      <!-- لوحة التحكم مخفية عمداً -->
    </div>
  </header>

  <main class="container">
    <section id="hero" class="hero">
      <h2>NovelNest</h2>
      <p class="muted">أهلاً بك</p>
    </section>

    <section id="novel-list" class="list grid-desktop">
      <?php if (empty($novels)): ?>
        <p class="empty">لا توجد روايات بعد. افتح لوحة التحكم لإضافة أول رواية.</p>
      <?php else: foreach ($novels as $n): 
          $coverPath = '';
          if (!empty($n['cover'])) {
              // if cover is stored as dataURI — skip, but we stored path relative
              $maybe = 'novels/'.$n['slug'].'/'.$n['cover'];
              if (file_exists(__DIR__.'/'.$maybe)) $coverPath = $maybe;
          }
          $chapCount = count($n['chapters'] ?? []);
        ?>
        <article class="card">
          <?php if ($coverPath): ?>
            <div class="cover"><img src="<?php echo htmlspecialchars($coverPath); ?>" alt="<?php echo htmlspecialchars($n['title']); ?> cover"></div>
          <?php else: ?>
            <div class="cover placeholder"></div>
          <?php endif; ?>
          <div class="card-body">
            <h3><?php echo slug_to_display($n['title']); ?></h3>
            <p class="summary"><?php echo nl2br(htmlspecialchars($n['summary'] ?? '')); ?></p>
            <div class="meta">
              <span class="small">فصول: <?php echo $chapCount; ?></span>
              <a class="btn" href="novels/<?php echo rawurlencode($n['slug']); ?>/index.html">اقرأ</a>
            </div>
          </div>
        </article>
      <?php endforeach; endif; ?>
    </section>
  </main>

  <footer class="footer">
    <small class="maker">صنع بواسطة: <span id="maker-label">Sun King</span></small>
  </footer>

  <script src="script.js"></script>
  <script>document.addEventListener('DOMContentLoaded', ()=>{ if (window.App) App.init(); });</script>
</body>
</html>