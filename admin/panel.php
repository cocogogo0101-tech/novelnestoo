<?php
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['novelnest_admin']) || $_SESSION['novelnest_admin'] !== true) {
    header('Location: login.php'); exit;
}

$messages = [];
$errors = [];

// Helper to write novel index page (HTML) — uses template file
function build_novel_index($novel_dir, $info) {
    $tplFile = TEMPLATES_DIR . '/novel_template.php';
    $outFile = $novel_dir . '/index.html';
    $chapterList = '';
    $chapDir = $novel_dir . '/chapters';
    if (is_dir($chapDir)) {
        $files = array_diff(scandir($chapDir), ['.','..']);
        foreach ($files as $f) {
            if (preg_match('/\.(html|htm)$/i',$f)) {
                $title = pathinfo($f, PATHINFO_FILENAME);
                $chapterList .= "<li><a href=\"chapters/".rawurlencode($f)."\">".htmlspecialchars($title)."</a></li>\n";
            }
        }
    }
    $coverTag = '';
    if (!empty($info['cover'])) {
        $coverTag = "<img src=\"" . htmlspecialchars($info['cover']) . "\" alt=\"".htmlspecialchars($info['title'])."\">";
    }
    if (file_exists($tplFile)) {
        $tpl = file_get_contents($tplFile);
        $html = str_replace(['{{NOVEL_TITLE}}','{{NOVEL_SUMMARY}}','{{COVER_TAG}}','{{CHAPTER_LIST}}'], [
            htmlspecialchars($info['title']), htmlspecialchars($info['summary'] ?? ''), $coverTag, $chapterList
        ], $tpl);
        file_put_contents($outFile, $html);
    }
}

// Helper to build chapter HTML from template
function build_chapter_file($novel_dir, $info, $chapter_filename, $chapter_title, $chapter_content) {
    $tpl = file_get_contents(TEMPLATES_DIR . '/chapter_template.php');
    $html = str_replace(['{{NOVEL_TITLE}}','{{CHAPTER_TITLE}}','{{CHAPTER_CONTENT}}','{{PREV_LINK}}','{{NEXT_LINK}}','{{PREV_DISABLED}}','{{NEXT_DISABLED}}'],
        [htmlspecialchars($info['title']), htmlspecialchars($chapter_title), nl2br(htmlspecialchars($chapter_content)), '#','','#disabled','#disabled'],
        $tpl);
    $target = $novel_dir . '/chapters/' . $chapter_filename;
    file_put_contents($target, $html);
    return true;
}

// Create novel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_novel') {
    $title = trim($_POST['title'] ?? '');
    $slugRaw = trim($_POST['slug'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    if ($title === '' || $slugRaw === '') $errors[] = "اكتب عنوان الرواية واسم المجلد (slug).";
    else {
        $slug = slugify($slugRaw);
        $novel_dir = NOVELS_DIR . '/' . $slug;
        if (file_exists($novel_dir)) $errors[] = "مجلد الرواية موجود بالفعل.";
        else {
            if (!mkdir($novel_dir . '/chapters', 0755, true)) $errors[] = "فشل في إنشاء مجلد الرواية.";
            else {
                // handle cover upload
                $coverName = '';
                if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                    $tmp = $_FILES['cover']['tmp_name'];
                    $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg','jpeg','png','webp','svg'];
                    if (!in_array($ext, $allowed)) $errors[] = "امتداد الصورة غير مدعوم.";
                    else {
                        $coverName = 'cover.' . $ext;
                        $destRel = 'novels/' . $slug . '/' . $coverName;
                        $dest = SITE_BASE . '/' . $destRel;
                        if (!move_uploaded_file($tmp, $dest)) $errors[] = "فشل رفع الغلاف.";
                    }
                }
                // write info.json
                $info = ['title' => $title, 'slug' => $slug, 'summary' => $summary, 'cover' => ($coverName ? $coverName : '')];
                file_put_contents($novel_dir . '/info.json', json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                // create blank index.html via template
                build_novel_index($novel_dir, $info);
                $messages[] = "تم إنشاء الرواية بنجاح.";
            }
        }
    }
}

// Create chapter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_chapter') {
    $slug = trim($_POST['novel_slug'] ?? '');
    $title = trim($_POST['chapter_title'] ?? '');
    $content = trim($_POST['chapter_content'] ?? '');
    if ($slug === '' || $title === '' || $content === '') $errors[] = "اختر رواية وادخل عنوان الفصل ومحتواه.";
    else {
        $novel_dir = NOVELS_DIR . '/' . $slug;
        $infoFile = $novel_dir . '/info.json';
        if (!file_exists($novel_dir) || !file_exists($infoFile)) $errors[] = "الرواية غير موجودة.";
        else {
            $info = json_decode(file_get_contents($infoFile), true);
            // determine next chapter filename
            $chapDir = $novel_dir . '/chapters';
            if (!is_dir($chapDir)) mkdir($chapDir,0755,true);
            $existing = array_values(array_filter(scandir($chapDir), function($f){ return preg_match('/\.(html|htm)$/i',$f); }));
            $count = count($existing) + 1;
            $num = str_pad($count, 2, '0', STR_PAD_LEFT);
            // safe filename from title
            $safeTitle = preg_replace('/[^\p{Arabic}\p{L}\d\s\-]+/u', '', $title);
            $safeTitle = preg_replace('/\s+/', '-', trim($safeTitle));
            $chapFile = $num . '-' . ($safeTitle ?: 'chapter') . '.html';
            // create chapter file
            build_chapter_file($novel_dir, $info, $chapFile, $title, $content);
            // update novel index
            build_novel_index($novel_dir, $info);
            $messages[] = "تم إنشاء الفصل $chapFile";
        }
    }
}

// read available novels for select
$availableNovels = [];
$dirs = array_diff(scandir(NOVELS_DIR), ['.','..']);
foreach ($dirs as $d) {
    $path = NOVELS_DIR . '/' . $d;
    if (is_dir($path) && file_exists($path . '/info.json')) {
        $info = json_decode(file_get_contents($path . '/info.json'), true);
        $availableNovels[] = $info;
    }
}
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>لوحة تحكم NovelNest</title>
  <link rel="stylesheet" href="../style.css">
  <style>
    .admin { max-width:1100px; margin:20px auto; padding:18px; }
    .row{ margin-bottom:12px }
    label{ display:block; margin-bottom:6px; font-weight:600 }
    input[type=text], textarea, select{ width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; background:var(--card-bg) }
    .actions{ display:flex; gap:8px; flex-wrap:wrap; margin-top:10px }
    .msg{ color:green; margin-bottom:8px }
    .err{ color:#c33; margin-bottom:8px }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="brand"><h1>لوحة تحكم NovelNest</h1></div>
    <div class="controls"><a href="logout.php" class="btn">خروج</a></div>
  </header>

  <main class="container admin">
    <?php foreach($messages as $m): ?><div class="msg"><?php echo htmlspecialchars($m); ?></div><?php endforeach; ?>
    <?php foreach($errors as $e): ?><div class="err"><?php echo htmlspecialchars($e); ?></div><?php endforeach; ?>

    <section class="card">
      <h3>إنشاء رواية جديدة</h3>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create_novel">
        <div class="row">
          <label>عنوان الرواية</label>
          <input type="text" name="title" placeholder="مثال: عالم الظلال">
        </div>
        <div class="row">
          <label>اسم المجلد (slug) — لاتيني بدون مسافات</label>
          <input type="text" name="slug" placeholder="مثال: Shadow-World">
        </div>
        <div class="row">
          <label>ملخص قصير (اختياري)</label>
          <input type="text" name="summary" placeholder="سطر أو سطرين">
        </div>
        <div class="row">
          <label>غلاف (اختياري)</label>
          <input type="file" name="cover" accept="image/*">
        </div>
        <div class="actions">
          <button class="btn" type="submit">إنشاء الرواية</button>
        </div>
      </form>
    </section>

    <section class="card" style="margin-top:18px;">
      <h3>إضافة فصل</h3>
      <form method="post">
        <input type="hidden" name="action" value="create_chapter">
        <div class="row">
          <label>اختر رواية</label>
          <select name="novel_slug">
            <option value="">— اختر —</option>
            <?php foreach($availableNovels as $av): ?>
              <option value="<?php echo htmlspecialchars($av['slug']); ?>"><?php echo htmlspecialchars($av['title']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row">
          <label>عنوان الفصل</label>
          <input type="text" name="chapter_title" placeholder="مثال: البداية">
        </div>
        <div class="row">
          <label>محتوى الفصل</label>
          <textarea name="chapter_content" rows="8" placeholder="الصق النص هنا..."></textarea>
        </div>
        <div class="actions">
          <button class="btn" type="submit">إضافة فصل</button>
        </div>
      </form>
    </section>

    <section style="margin-top:18px;">
      <h3>قائمة الروايات الحالية</h3>
      <div>
        <?php if (empty($availableNovels)): ?><p class="small muted">لا توجد روايات.</p><?php else: ?>
          <ul>
            <?php foreach($availableNovels as $av): ?>
              <li><?php echo htmlspecialchars($av['title']); ?> — <a href="../novels/<?php echo rawurlencode($av['slug']); ?>/index.html" target="_blank">عرض</a></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </section>
  </main>
</body>
</html>