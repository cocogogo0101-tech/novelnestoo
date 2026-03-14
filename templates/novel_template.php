<!-- template for generating novel index.html -->
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{NOVEL_TITLE}} — NovelNest</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
  <header class="topbar"><div class="brand"><h1>NovelNest</h1></div></header>
  <main class="container">
    <nav class="back"><a href="../index.php">⬅ العودة للمكتبة</a></nav>
    <div class="novel-card">
      {{COVER_TAG}}
      <h2>{{NOVEL_TITLE}}</h2>
      <p class="small">{{NOVEL_SUMMARY}}</p>
      <h3>الفصول</h3>
      <ol>
        {{CHAPTER_LIST}}
      </ol>
    </div>
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