<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{NOVEL_TITLE}} — {{CHAPTER_TITLE}}</title>
  <link rel="stylesheet" href="../../../style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📄</text></svg>">
</head>
<body>
<div id="reading-progress"><div id="reading-progress-bar"></div></div>
<header class="topbar">
  <a href="../../../index.php" class="topbar-brand">
    <div class="topbar-logo">📚</div>
    <h1>Novel<span>Nest</span></h1>
  </a>
  <div class="topbar-actions">
    <a href="../index.html" class="btn btn-ghost btn-sm">📖 الفصول</a>
    <button class="btn-icon" id="theme-toggle" title="تبديل الثيم">☀️</button>
  </div>
</header>
<main>
  <div class="chapter-reader">
    <header class="chapter-reader-header">
      <a href="../index.html" class="chapter-novel-link">→ {{NOVEL_TITLE}}</a>
      <h1 class="chapter-reader-title">{{CHAPTER_TITLE}}</h1>
      <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:10px">
        <span style="font-size:0.83rem;color:var(--muted);font-family:var(--font-sans)">
          الفصل {{CHAPTER_NUM}} من {{CHAPTER_TOTAL}}
        </span>
      </div>
      <div class="chapter-reader-divider"></div>
    </header>
    <div class="chapter-content">
      <p>{{CHAPTER_CONTENT}}</p>
    </div>
    <nav class="chapter-nav-bar">
      <a href="{{PREV_HREF}}" class="chapter-nav-btn prev{{PREV_DISABLED}}">
        ← الفصل السابق
      </a>
      <div class="chapter-nav-center">
        <a href="../index.html">📚 كل الفصول</a>
      </div>
      <a href="{{NEXT_HREF}}" class="chapter-nav-btn next{{NEXT_DISABLED}}">
        الفصل التالي →
      </a>
    </nav>
  </div>
</main>
<div class="reading-toolbar" id="reading-toolbar">
  <button id="font-down" title="تصغير الخط">أ−</button>
  <div class="sep"></div>
  <button id="font-reset" title="الحجم الافتراضي">أ</button>
  <div class="sep"></div>
  <button id="font-up" title="تكبير الخط">أ+</button>
  <div class="sep"></div>
  <button id="theme-toggle" title="الثيم">☀️</button>
</div>
<footer class="footer">
  <div class="footer-inner">
    <a href="../index.html">← {{NOVEL_TITLE}}</a>
    <span>صنع بواسطة: <span class="maker-label">Sun King</span></span>
  </div>
</footer>
<button id="back-to-top" title="للأعلى">↑</button>
<div id="toast-container"></div>
<script src="../../../script.js"></script>
<script>
window.addEventListener('scroll',function(){
  var tb=document.getElementById('reading-toolbar');
  if(tb) tb.classList.toggle('visible',window.scrollY>300);
},{passive:true});
</script>
</body>
</html>
