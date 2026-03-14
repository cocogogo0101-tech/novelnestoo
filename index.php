<?php
/* ============================================================
   index.php — Main Library Page (Dynamic)
   ============================================================ */

$base       = __DIR__;
$novels_dir = $base . '/novels';
$novels     = [];

if (is_dir($novels_dir)) {
    $entries = array_diff(scandir($novels_dir), ['.', '..']);
    foreach ($entries as $e) {
        $path = $novels_dir . '/' . $e;
        if (!is_dir($path)) continue;
        $infoFile = $path . '/info.json';
        $info = ['title' => $e, 'summary' => '', 'cover' => '', 'slug' => $e];
        if (file_exists($infoFile)) {
            $json = json_decode(file_get_contents($infoFile), true);
            if (is_array($json)) $info = array_merge($info, $json);
        }
        // count chapters
        $chapFile = $path . '/chapters.json';
        $info['chapter_count'] = 0;
        if (file_exists($chapFile)) {
            $chaps = json_decode(file_get_contents($chapFile), true);
            $info['chapter_count'] = is_array($chaps) ? count($chaps) : 0;
        } else {
            $chapDir = $path . '/chapters';
            if (is_dir($chapDir)) {
                $files = array_diff(scandir($chapDir), ['.', '..']);
                $info['chapter_count'] = count(array_filter($files, fn($f) => preg_match('/\.html$/i', $f)));
            }
        }
        $novels[] = $info;
    }
    usort($novels, fn($a, $b) => strcmp(mb_strtolower($a['title']), mb_strtolower($b['title'])));
}

$total_chapters = array_sum(array_column($novels, 'chapter_count'));
$total_novels   = count($novels);
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <meta name="description" content="NovelNest — مكتبة روايات عربية إلكترونية"/>
  <title>NovelNest — المكتبة</title>
  <link rel="stylesheet" href="style.css"/>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📚</text></svg>"/>
</head>
<body>

<!-- Reading Progress -->
<div id="reading-progress"><div id="reading-progress-bar"></div></div>

<!-- Topbar -->
<header class="topbar">
  <a href="index.php" class="topbar-brand">
    <div class="topbar-logo">📚</div>
    <h1>Novel<span>Nest</span></h1>
  </a>
  <div class="topbar-actions">
    <button class="btn-icon" id="theme-toggle" title="تبديل الثيم">☀️</button>
  </div>
</header>

<!-- Main Content -->
<main>
  <!-- Hero -->
  <section class="hero">
    <div class="hero-badge">✨ مكتبتك الأدبية</div>
    <h2>اكتشف عالم <span>الروايات</span></h2>
    <p class="hero-sub">مكتبة إلكترونية للروايات العربية — اقرأ، استمتع، واستكشف</p>
    <?php if ($total_novels > 0): ?>
    <div class="hero-stats">
      <div class="stat-item">
        <span class="stat-num"><?php echo $total_novels; ?></span>
        <span class="stat-label">رواية</span>
      </div>
      <div class="stat-item">
        <span class="stat-num"><?php echo $total_chapters; ?></span>
        <span class="stat-label">فصل</span>
      </div>
    </div>
    <?php endif; ?>
  </section>

  <!-- Search -->
  <div class="container">
    <?php if ($total_novels > 0): ?>
    <div class="search-wrap">
      <div class="search-box">
        <input type="text" id="search-input" placeholder="ابحث عن رواية..." autocomplete="off"/>
        <span class="search-icon">🔍</span>
        <button class="search-clear" id="search-clear" title="مسح">✕</button>
      </div>
      <p id="search-count"></p>
    </div>
    <?php endif; ?>

    <!-- Section header -->
    <?php if ($total_novels > 0): ?>
    <div class="section-header">
      <h3 class="section-title">جميع الروايات</h3>
      <span class="tag tag-gold"><?php echo $total_novels; ?> رواية</span>
    </div>
    <?php endif; ?>

    <!-- Novels Grid -->
    <div class="novels-grid page-content" style="padding-top:0">
      <?php if (empty($novels)): ?>
        <div class="empty-state">
          <div class="empty-icon">📖</div>
          <h3>المكتبة فارغة حتى الآن</h3>
          <p>افتح لوحة التحكم لإضافة أول رواية</p>
        </div>
      <?php else: ?>
        <?php foreach ($novels as $n):
          $slug       = htmlspecialchars($n['slug'] ?? $n['title']);
          $title      = htmlspecialchars($n['title']);
          $summary    = htmlspecialchars($n['summary'] ?? '');
          $chapCount  = (int)($n['chapter_count'] ?? 0);
          $coverPath  = '';
          if (!empty($n['cover'])) {
              $maybe = 'novels/' . $n['slug'] . '/' . $n['cover'];
              if (file_exists($base . '/' . $maybe)) $coverPath = $maybe;
          }
        ?>
        <article class="novel-card"
          data-title="<?php echo strtolower($title); ?>"
          data-summary="<?php echo strtolower($summary); ?>">
          <div class="novel-card-cover">
            <?php if ($coverPath): ?>
              <img src="<?php echo $coverPath; ?>" alt="<?php echo $title; ?>" loading="lazy"/>
            <?php else: ?>
              <div class="novel-card-cover-placeholder">
                <span class="ph-icon">📖</span>
                <span class="ph-text">لا يوجد غلاف</span>
              </div>
            <?php endif; ?>
            <?php if ($chapCount > 0): ?>
              <span class="novel-card-badge"><?php echo $chapCount; ?> فصل</span>
            <?php endif; ?>
          </div>
          <div class="novel-card-body">
            <h3 class="novel-card-title"><?php echo $title; ?></h3>
            <?php if ($summary): ?>
              <p class="novel-card-summary"><?php echo nl2br($summary); ?></p>
            <?php endif; ?>
            <div class="novel-card-meta">
              <div class="novel-card-chapters">
                📚 <strong><?php echo $chapCount; ?></strong> فصل
              </div>
              <a class="btn btn-primary btn-sm"
                 href="novels/<?php echo rawurlencode($n['slug']); ?>/index.html">
                اقرأ الآن ←
              </a>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
        <div class="no-results">
          <p>😔 لا توجد روايات تطابق بحثك</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- Footer -->
<footer class="footer">
  <div class="footer-inner">
    <span>© <?php echo date('Y'); ?> NovelNest</span>
    <span>صنع بواسطة: <span class="maker-label">Sun King</span></span>
  </div>
</footer>

<!-- Back to top -->
<button id="back-to-top" title="للأعلى">↑</button>

<!-- Toast container -->
<div id="toast-container"></div>

<script src="script.js"></script>
</body>
</html>
