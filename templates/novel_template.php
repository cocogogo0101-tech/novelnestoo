<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{NOVEL_TITLE}} — NovelNest</title>
  <link rel="stylesheet" href="../../style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📖</text></svg>">
</head>
<body>
<div id="reading-progress"><div id="reading-progress-bar"></div></div>
<header class="topbar">
  <a href="../../index.php" class="topbar-brand">
    <div class="topbar-logo">📚</div>
    <h1>Novel<span>Nest</span></h1>
  </a>
  <div class="topbar-actions">
    <button class="btn-icon" id="theme-toggle" title="تبديل الثيم">☀️</button>
  </div>
</header>
<main>
  <div class="container">
    <a href="../../index.php" class="back-nav">→ العودة للمكتبة</a>
    <section class="novel-page-hero">
      <div class="novel-cover-wrap {{COVER_CLASS}}">
        {{COVER_TAG}}
      </div>
      <h1 class="novel-page-title">{{NOVEL_TITLE}}</h1>
      <p class="novel-page-summary">{{NOVEL_SUMMARY}}</p>
      <div class="novel-meta-row">
        <div class="novel-meta-item">📚 <strong>{{CHAPTER_COUNT}}</strong> فصل</div>
      </div>
    </section>
    <div class="chapter-list-wrap">
      <div class="chapter-list-header">
        <h3 class="chapter-list-title">قائمة الفصول</h3>
        <span class="tag tag-gold">{{CHAPTER_COUNT}} فصل</span>
      </div>
      <ol class="chapter-list">
        {{CHAPTER_LIST}}
      </ol>
    </div>
  </div>
</main>
<footer class="footer">
  <div class="footer-inner">
    <a href="../../index.php">← المكتبة</a>
    <span>صنع بواسطة: <span class="maker-label">Sun King</span></span>
  </div>
</footer>
<button id="back-to-top" title="للأعلى">↑</button>
<div id="toast-container"></div>
<script src="../../script.js"></script>
</body>
</html>
