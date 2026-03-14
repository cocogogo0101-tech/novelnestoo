<!-- template for generating chapter files -->
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{NOVEL_TITLE}} — {{CHAPTER_TITLE}}</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
  <header class="topbar"><div class="brand"><h1>NovelNest</h1></div></header>
  <main class="container">
    <nav class="back"><a href="../index.html">⬅ العودة للرواية</a></nav>
    <article class="chapter">
      <h2>{{NOVEL_TITLE}} — {{CHAPTER_TITLE}}</h2>
      <article><p>{{CHAPTER_CONTENT}}</p></article>
    </article>
    <nav class="chapter-nav" style="text-align:center; margin-top:18px;">
      <a class="btn" href="../index.html">📚 الفصول</a>
    </nav>
  </main>
  <footer class="footer"><small class="maker">صنع بواسطة: Sun King</small></footer>
  <script>
    (function(){
      var theme = localStorage.getItem('novelnest_theme_v1') || 'light';
      var maker = theme === 'dark' ? 'Dark King' : 'Sun King';
      var sm = document.querySelector('.maker');
      if (sm) sm.textContent = 'صنع بواسطة: ' + maker;
    })();
  </script>
</body>
</html>