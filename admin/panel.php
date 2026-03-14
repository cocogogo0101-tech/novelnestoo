<?php
require_once __DIR__.'/config.php';
require_admin();

$messages = [];
$errors   = [];

/* ============================================================
   AJAX ENDPOINTS
   ============================================================ */
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $ajaxSlug = preg_replace('/[^a-z0-9\-]/', '', $_GET['slug'] ?? '');
    $ajaxDir  = NOVELS_DIR . '/' . $ajaxSlug;

    if ($_GET['action'] === 'get_chapters') {
        $chaps = get_chapters_meta($ajaxDir);
        echo json_encode(['chapters' => $chaps]);
        exit;
    }

    if ($_GET['action'] === 'get_chapter_content') {
        $ch = preg_replace('/[^a-z0-9\-]/', '', $_GET['ch'] ?? '');
        $data = get_chapter_content($ajaxDir, $ch);
        echo json_encode($data);
        exit;
    }
}

/* ============================================================
   POST ACTIONS
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'خطأ أمني: انتهت صلاحية الجلسة، حاول مجدداً.';
    } else {
        $action = $_POST['action'] ?? '';

        /* ── CREATE NOVEL ── */
        if ($action === 'create_novel') {
            $title   = trim($_POST['title']   ?? '');
            $slugRaw = trim($_POST['slug']    ?? '');
            $summary = trim($_POST['summary'] ?? '');

            if (!$title || !$slugRaw) {
                $errors[] = 'العنوان واسم المجلد مطلوبان.';
            } else {
                $slug      = slugify($slugRaw);
                $novelDir  = NOVELS_DIR . '/' . $slug;
                if (is_dir($novelDir)) {
                    $errors[] = 'يوجد مجلد برواية بنفس الاسم.';
                } else {
                    mkdir($novelDir . '/chapters', 0755, true);
                    $coverName = '';
                    if (!empty($_FILES['cover']['tmp_name']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                            $dest = $novelDir . '/cover.' . $ext;
                            if (move_uploaded_file($_FILES['cover']['tmp_name'], $dest)) {
                                $coverName = 'cover.' . $ext;
                            }
                        } else {
                            $errors[] = 'امتداد الصورة غير مدعوم (jpg/png/webp).';
                        }
                    }
                    if (empty($errors)) {
                        $info = ['title'=>$title,'slug'=>$slug,'summary'=>$summary,'cover'=>$coverName];
                        save_novel_info($novelDir, $info);
                        save_chapters_meta($novelDir, []);
                        build_novel_index($novelDir, $info, []);
                        $messages[] = "✅ تم إنشاء رواية «{$title}» بنجاح.";
                    }
                }
            }
        }

        /* ── EDIT NOVEL ── */
        elseif ($action === 'edit_novel') {
            $slug    = preg_replace('/[^a-z0-9\-]/', '', $_POST['novel_slug'] ?? '');
            $title   = trim($_POST['title']   ?? '');
            $summary = trim($_POST['summary'] ?? '');
            $novelDir = NOVELS_DIR . '/' . $slug;
            if (!$slug || !is_dir($novelDir)) {
                $errors[] = 'الرواية غير موجودة.';
            } else {
                $info = get_novel_info($novelDir);
                $info['title']   = $title;
                $info['summary'] = $summary;
                // handle cover update
                if (!empty($_FILES['cover']['tmp_name']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                        $dest = $novelDir . '/cover.' . $ext;
                        if (move_uploaded_file($_FILES['cover']['tmp_name'], $dest)) {
                            $info['cover'] = 'cover.' . $ext;
                        }
                    }
                }
                save_novel_info($novelDir, $info);
                rebuild_novel($novelDir);
                $messages[] = "✅ تم تحديث رواية «{$title}».";
            }
        }

        /* ── DELETE NOVEL ── */
        elseif ($action === 'delete_novel') {
            $slug     = preg_replace('/[^a-z0-9\-]/', '', $_POST['novel_slug'] ?? '');
            $novelDir = NOVELS_DIR . '/' . $slug;
            if (!$slug || !is_dir($novelDir)) {
                $errors[] = 'الرواية غير موجودة.';
            } else {
                $info = get_novel_info($novelDir);
                delete_dir($novelDir);
                $messages[] = "🗑️ تم حذف رواية «" . htmlspecialchars($info['title'] ?? $slug) . "».";
            }
        }

        /* ── CREATE CHAPTER ── */
        elseif ($action === 'create_chapter') {
            $slug         = preg_replace('/[^a-z0-9\-]/', '', $_POST['novel_slug']    ?? '');
            $chapterTitle = trim($_POST['chapter_title']   ?? '');
            $chapterContent = trim($_POST['chapter_content'] ?? '');
            $novelDir     = NOVELS_DIR . '/' . $slug;

            if (!$slug || !$chapterTitle || !$chapterContent) {
                $errors[] = 'اختر الرواية وأدخل عنوان الفصل ومحتواه.';
            } elseif (!is_dir($novelDir)) {
                $errors[] = 'الرواية غير موجودة.';
            } else {
                $info     = get_novel_info($novelDir);
                $chapters = get_chapters_meta($novelDir);
                $num      = str_pad(count($chapters) + 1, 2, '0', STR_PAD_LEFT);
                $chSlug   = $num . '-' . slugify($chapterTitle);

                // prevent duplicates
                foreach ($chapters as $ch) {
                    if ($ch['slug'] === $chSlug) $chSlug .= '-' . time();
                }

                $chapters[] = ['slug' => $chSlug, 'title' => $chapterTitle];
                save_chapter_content($novelDir, $chSlug, $chapterTitle, $chapterContent);
                save_chapters_meta($novelDir, $chapters);
                build_novel_index($novelDir, $info, $chapters);
                build_all_chapters($novelDir, $info, $chapters);
                $messages[] = "✅ تم إضافة فصل «{$chapterTitle}».";
            }
        }

        /* ── EDIT CHAPTER ── */
        elseif ($action === 'edit_chapter') {
            $slug         = preg_replace('/[^a-z0-9\-]/', '', $_POST['novel_slug'] ?? '');
            $chSlug       = preg_replace('/[^a-z0-9\-]/', '', $_POST['chapter_slug'] ?? '');
            $chapterTitle = trim($_POST['chapter_title']   ?? '');
            $chapterContent = trim($_POST['chapter_content'] ?? '');
            $novelDir     = NOVELS_DIR . '/' . $slug;

            if (!$slug || !$chSlug || !$chapterTitle) {
                $errors[] = 'بيانات غير مكتملة.';
            } elseif (!is_dir($novelDir)) {
                $errors[] = 'الرواية غير موجودة.';
            } else {
                $info     = get_novel_info($novelDir);
                $chapters = get_chapters_meta($novelDir);
                foreach ($chapters as &$ch) {
                    if ($ch['slug'] === $chSlug) {
                        $ch['title'] = $chapterTitle;
                        break;
                    }
                }
                unset($ch);
                save_chapter_content($novelDir, $chSlug, $chapterTitle, $chapterContent);
                save_chapters_meta($novelDir, $chapters);
                build_novel_index($novelDir, $info, $chapters);
                build_all_chapters($novelDir, $info, $chapters);
                $messages[] = "✅ تم تحديث الفصل «{$chapterTitle}».";
            }
        }

        /* ── DELETE CHAPTER ── */
        elseif ($action === 'delete_chapter') {
            $slug     = preg_replace('/[^a-z0-9\-]/', '', $_POST['novel_slug']   ?? '');
            $chSlug   = preg_replace('/[^a-z0-9\-]/', '', $_POST['chapter_slug'] ?? '');
            $novelDir = NOVELS_DIR . '/' . $slug;

            if (!$slug || !$chSlug || !is_dir($novelDir)) {
                $errors[] = 'بيانات غير صحيحة.';
            } else {
                $info     = get_novel_info($novelDir);
                $chapters = get_chapters_meta($novelDir);
                $chapters = array_values(array_filter($chapters, fn($c) => $c['slug'] !== $chSlug));
                // rename slugs to keep numbering correct? — preserve slugs, just remove
                @unlink($novelDir . '/chapters/' . $chSlug . '.json');
                @unlink($novelDir . '/chapters/' . $chSlug . '.html');
                save_chapters_meta($novelDir, $chapters);
                build_novel_index($novelDir, $info, $chapters);
                build_all_chapters($novelDir, $info, $chapters);
                $messages[] = "🗑️ تم حذف الفصل.";
            }
        }

        /* ── REORDER CHAPTER (up/down) ── */
        elseif ($action === 'reorder_chapter') {
            $slug      = preg_replace('/[^a-z0-9\-]/', '', $_POST['novel_slug']   ?? '');
            $chSlug    = preg_replace('/[^a-z0-9\-]/', '', $_POST['chapter_slug'] ?? '');
            $direction = $_POST['direction'] === 'down' ? 'down' : 'up';
            $novelDir  = NOVELS_DIR . '/' . $slug;

            if ($slug && $chSlug && is_dir($novelDir)) {
                $info     = get_novel_info($novelDir);
                $chapters = get_chapters_meta($novelDir);
                $idx      = array_search($chSlug, array_column($chapters, 'slug'));
                if ($idx !== false) {
                    $swapWith = $direction === 'up' ? $idx - 1 : $idx + 1;
                    if ($swapWith >= 0 && $swapWith < count($chapters)) {
                        [$chapters[$idx], $chapters[$swapWith]] = [$chapters[$swapWith], $chapters[$idx]];
                        save_chapters_meta($novelDir, $chapters);
                        build_novel_index($novelDir, $info, $chapters);
                        build_all_chapters($novelDir, $info, $chapters);
                        $messages[] = "✅ تم إعادة الترتيب.";
                    }
                }
            }
        }
    }
}

/* ============================================================
   READ DATA
   ============================================================ */
$availableNovels = [];
if (is_dir(NOVELS_DIR)) {
    foreach (array_diff(scandir(NOVELS_DIR), ['.','..']) as $d) {
        $path = NOVELS_DIR . '/' . $d;
        if (is_dir($path) && file_exists($path . '/info.json')) {
            $info     = get_novel_info($path);
            $chapters = get_chapters_meta($path);
            $info['chapter_count'] = count($chapters);
            $info['chapters_list'] = $chapters;
            // cover path for admin display
            $info['cover_path'] = '';
            if (!empty($info['cover']) && file_exists($path . '/' . $info['cover']))
                $info['cover_path'] = '../novels/' . $info['slug'] . '/' . $info['cover'];
            $availableNovels[] = $info;
        }
    }
}
$totalNovels   = count($availableNovels);
$totalChapters = array_sum(array_column($availableNovels, 'chapter_count'));
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>لوحة تحكم — NovelNest</title>
  <link rel="stylesheet" href="../style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚙️</text></svg>">
</head>
<body>
<div id="reading-progress"><div id="reading-progress-bar"></div></div>
<header class="topbar">
  <a href="../index.php" class="topbar-brand">
    <div class="topbar-logo">📚</div>
    <h1>Novel<span>Nest</span></h1>
  </a>
  <div class="topbar-actions">
    <span style="font-size:0.85rem;color:var(--muted);font-family:var(--font-sans)">لوحة التحكم</span>
    <button class="btn-icon" id="theme-toggle" title="تبديل الثيم">☀️</button>
    <a href="logout.php" class="btn btn-ghost btn-sm">خروج</a>
  </div>
</header>

<main>
  <div class="admin-wrap" style="padding-top:28px">
    <!-- Alerts -->
    <?php foreach($messages as $m): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($m); ?></div>
    <?php endforeach; ?>
    <?php foreach($errors as $e): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <!-- Stats row -->
    <div style="display:flex;gap:16px;margin-bottom:28px;flex-wrap:wrap">
      <div class="admin-card" style="flex:1;min-width:140px">
        <div class="admin-card-body" style="text-align:center;padding:20px">
          <div style="font-size:2rem;font-weight:900;color:var(--gold)"><?php echo $totalNovels; ?></div>
          <div style="font-size:0.85rem;color:var(--muted);margin-top:4px;font-family:var(--font-sans)">رواية</div>
        </div>
      </div>
      <div class="admin-card" style="flex:1;min-width:140px">
        <div class="admin-card-body" style="text-align:center;padding:20px">
          <div style="font-size:2rem;font-weight:900;color:var(--accent)"><?php echo $totalChapters; ?></div>
          <div style="font-size:0.85rem;color:var(--muted);margin-top:4px;font-family:var(--font-sans)">فصل</div>
        </div>
      </div>
      <div class="admin-card" style="flex:2;min-width:200px">
        <div class="admin-card-body" style="display:flex;align-items:center;gap:12px;padding:20px">
          <span style="font-size:1.5rem">🌐</span>
          <div>
            <div style="font-weight:700;font-size:0.95rem;margin-bottom:4px">عرض الموقع</div>
            <a href="../index.php" target="_blank" style="font-size:0.85rem;color:var(--gold)">فتح المكتبة ←</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="admin-tabs" id="admin-tabs">
      <button class="admin-tab active" data-tab="0">📖 الروايات</button>
      <button class="admin-tab" data-tab="1">➕ إنشاء رواية</button>
      <button class="admin-tab" data-tab="2">📄 الفصول</button>
      <button class="admin-tab" data-tab="3">✏️ تعديل / حذف</button>
    </div>

    <!-- TAB 0: Novels List -->
    <div class="tab-panel active" id="tab-0">
      <div class="admin-card">
        <div class="admin-card-header">
          <h3><span class="icon">📚</span> قائمة الروايات (<?php echo $totalNovels; ?>)</h3>
        </div>
        <div class="admin-card-body">
          <?php if (empty($availableNovels)): ?>
            <div class="empty-state" style="padding:40px 0">
              <div class="empty-icon">📭</div>
              <h3>لا توجد روايات</h3>
              <p>انتقل لتبويب «إنشاء رواية» لإضافة أولى رواية</p>
            </div>
          <?php else: ?>
          <div class="novel-admin-list">
            <?php foreach ($availableNovels as $n): ?>
            <div class="novel-admin-item">
              <div class="novel-admin-thumb">
                <?php if ($n['cover_path']): ?>
                  <img src="<?php echo htmlspecialchars($n['cover_path']); ?>" alt="">
                <?php else: ?>
                  📖
                <?php endif; ?>
              </div>
              <div class="novel-admin-info">
                <div class="novel-admin-title"><?php echo htmlspecialchars($n['title']); ?></div>
                <div class="novel-admin-meta"><?php echo $n['chapter_count']; ?> فصل — <?php echo htmlspecialchars($n['slug']); ?></div>
              </div>
              <div class="novel-admin-actions">
                <a href="../novels/<?php echo rawurlencode($n['slug']); ?>/index.html"
                   class="btn btn-sm btn-ghost" target="_blank" title="عرض">👁️</a>
                <button class="btn btn-sm btn-success"
                  onclick="openEditNovel('<?php echo htmlspecialchars(json_encode($n), ENT_QUOTES); ?>')"
                  title="تعديل">✏️</button>
                <form method="post" style="display:inline" id="del-novel-<?php echo htmlspecialchars($n['slug']); ?>">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="delete_novel">
                  <input type="hidden" name="novel_slug" value="<?php echo htmlspecialchars($n['slug']); ?>">
                  <button type="button" class="btn btn-sm btn-danger" title="حذف"
                    onclick="confirmDelete('هل تريد حذف رواية «<?php echo htmlspecialchars($n['title'],ENT_QUOTES); ?>» وجميع فصولها؟ لا يمكن التراجع.', 'del-novel-<?php echo htmlspecialchars($n['slug']); ?>')">
                    🗑️
                  </button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- TAB 1: Create Novel -->
    <div class="tab-panel" id="tab-1">
      <div class="admin-card">
        <div class="admin-card-header">
          <h3><span class="icon">✨</span> إنشاء رواية جديدة</h3>
        </div>
        <div class="admin-card-body">
          <form method="post" enctype="multipart/form-data" data-validate>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="create_novel">
            <div class="form-grid">
              <div class="form-row">
                <label class="form-label"><span class="req">*</span> عنوان الرواية</label>
                <input type="text" name="title" class="form-input" placeholder="مثال: عالم الظلال" required>
              </div>
              <div class="form-row">
                <label class="form-label">
                  <span class="req">*</span> اسم المجلد (slug)
                  <span class="hint">— لاتيني بدون مسافات</span>
                </label>
                <input type="text" name="slug" class="form-input" placeholder="Shadow-World" required>
              </div>
            </div>
            <div class="form-row">
              <label class="form-label">ملخص الرواية <span class="hint">(اختياري)</span></label>
              <textarea name="summary" class="form-textarea" style="min-height:100px"
                placeholder="أكتب ملخصاً قصيراً للرواية..."></textarea>
            </div>
            <div class="form-row">
              <label class="form-label">صورة الغلاف <span class="hint">(اختياري — jpg/png/webp)</span></label>
              <div class="form-file-wrap">
                <label class="form-file-label" for="cover-input-new">
                  🖼️ <span>اختر صورة الغلاف</span>
                  <span class="file-name" style="font-size:0.82rem;color:var(--muted);margin-right:auto"></span>
                </label>
                <input type="file" id="cover-input-new" name="cover" accept="image/*">
              </div>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn btn-gold">✨ إنشاء الرواية</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- TAB 2: Chapters -->
    <div class="tab-panel" id="tab-2">
      <!-- Add chapter -->
      <div class="admin-card">
        <div class="admin-card-header">
          <h3><span class="icon">➕</span> إضافة فصل جديد</h3>
        </div>
        <div class="admin-card-body">
          <?php if (empty($availableNovels)): ?>
            <div class="alert alert-error">⚠️ أنشئ رواية أولاً قبل إضافة فصول.</div>
          <?php else: ?>
          <form method="post" data-validate>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="create_chapter">
            <div class="form-row">
              <label class="form-label"><span class="req">*</span> الرواية</label>
              <select name="novel_slug" class="form-select" required>
                <option value="">— اختر الرواية —</option>
                <?php foreach($availableNovels as $av): ?>
                  <option value="<?php echo htmlspecialchars($av['slug']); ?>">
                    <?php echo htmlspecialchars($av['title']); ?> (<?php echo $av['chapter_count']; ?> فصل)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-row">
              <label class="form-label"><span class="req">*</span> عنوان الفصل</label>
              <input type="text" name="chapter_title" class="form-input" placeholder="مثال: البداية" required>
            </div>
            <div class="form-row">
              <label class="form-label"><span class="req">*</span> محتوى الفصل</label>
              <textarea name="chapter_content" class="form-textarea" rows="12"
                placeholder="الصق نص الفصل هنا..." required></textarea>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn btn-primary">➕ إضافة الفصل</button>
            </div>
          </form>
          <?php endif; ?>
        </div>
      </div>

      <!-- Chapters list per novel -->
      <?php foreach ($availableNovels as $av): ?>
      <?php if (empty($av['chapters_list'])) continue; ?>
      <div class="admin-card" style="margin-top:16px">
        <div class="admin-card-header">
          <h3><span class="icon">📖</span> فصول: <?php echo htmlspecialchars($av['title']); ?></h3>
          <span class="tag tag-gold"><?php echo $av['chapter_count']; ?> فصل</span>
        </div>
        <div class="admin-card-body">
          <div class="chapter-admin-list">
            <?php foreach ($av['chapters_list'] as $i => $ch): ?>
            <div class="chapter-admin-item">
              <div class="chapter-admin-num"><?php echo $i+1; ?></div>
              <div class="chapter-admin-title"><?php echo htmlspecialchars($ch['title']); ?></div>
              <div class="chapter-admin-actions">
                <!-- Move up -->
                <?php if ($i > 0): ?>
                <form method="post" style="display:inline">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="reorder_chapter">
                  <input type="hidden" name="novel_slug" value="<?php echo htmlspecialchars($av['slug']); ?>">
                  <input type="hidden" name="chapter_slug" value="<?php echo htmlspecialchars($ch['slug']); ?>">
                  <input type="hidden" name="direction" value="up">
                  <button class="btn btn-sm btn-ghost" title="تحريك لأعلى">↑</button>
                </form>
                <?php endif; ?>
                <!-- Move down -->
                <?php if ($i < count($av['chapters_list'])-1): ?>
                <form method="post" style="display:inline">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="reorder_chapter">
                  <input type="hidden" name="novel_slug" value="<?php echo htmlspecialchars($av['slug']); ?>">
                  <input type="hidden" name="chapter_slug" value="<?php echo htmlspecialchars($ch['slug']); ?>">
                  <input type="hidden" name="direction" value="down">
                  <button class="btn btn-sm btn-ghost" title="تحريك لأسفل">↓</button>
                </form>
                <?php endif; ?>
                <!-- Edit -->
                <button class="btn btn-sm btn-success" title="تعديل"
                  onclick="openEditChapter('<?php echo htmlspecialchars($av['slug'],ENT_QUOTES); ?>',
                  '<?php echo htmlspecialchars($ch['slug'],ENT_QUOTES); ?>',
                  '<?php echo htmlspecialchars($ch['title'],ENT_QUOTES); ?>')">✏️</button>
                <!-- Delete -->
                <form method="post" style="display:inline" id="del-ch-<?php echo htmlspecialchars($ch['slug']); ?>">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="delete_chapter">
                  <input type="hidden" name="novel_slug" value="<?php echo htmlspecialchars($av['slug']); ?>">
                  <input type="hidden" name="chapter_slug" value="<?php echo htmlspecialchars($ch['slug']); ?>">
                  <button type="button" class="btn btn-sm btn-danger" title="حذف"
                    onclick="confirmDelete('هل تريد حذف الفصل «<?php echo htmlspecialchars($ch['title'],ENT_QUOTES); ?>»؟', 'del-ch-<?php echo htmlspecialchars($ch['slug']); ?>')">
                    🗑️
                  </button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- TAB 3: Edit / Delete -->
    <div class="tab-panel" id="tab-3">
      <!-- Edit Novel Form -->
      <div class="admin-card" id="edit-novel-card" style="display:none">
        <div class="admin-card-header">
          <h3><span class="icon">✏️</span> تعديل الرواية</h3>
          <button class="btn btn-sm btn-ghost" onclick="closeEditNovel()">إغلاق ✕</button>
        </div>
        <div class="admin-card-body">
          <form method="post" enctype="multipart/form-data" id="edit-novel-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit_novel">
            <input type="hidden" name="novel_slug" id="edit-novel-slug">
            <div class="form-grid">
              <div class="form-row">
                <label class="form-label">عنوان الرواية</label>
                <input type="text" name="title" class="form-input" id="edit-novel-title">
              </div>
              <div class="form-row">
                <label class="form-label">الغلاف الجديد <span class="hint">(اختياري)</span></label>
                <div class="form-file-wrap">
                  <label class="form-file-label" for="edit-cover-input">
                    🖼️ تغيير الغلاف
                    <span class="file-name" style="font-size:0.82rem;color:var(--muted);margin-right:auto"></span>
                  </label>
                  <input type="file" id="edit-cover-input" name="cover" accept="image/*">
                </div>
              </div>
            </div>
            <div class="form-row">
              <label class="form-label">ملخص الرواية</label>
              <textarea name="summary" class="form-textarea" style="min-height:100px" id="edit-novel-summary"></textarea>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn btn-gold">💾 حفظ التغييرات</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Chapter Form -->
      <div class="admin-card" id="edit-chapter-card" style="display:none">
        <div class="admin-card-header">
          <h3><span class="icon">✏️</span> تعديل الفصل</h3>
          <button class="btn btn-sm btn-ghost" onclick="closeEditChapter()">إغلاق ✕</button>
        </div>
        <div class="admin-card-body">
          <form method="post" id="edit-chapter-form" data-validate>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit_chapter">
            <input type="hidden" name="novel_slug"   id="edit-ch-novel">
            <input type="hidden" name="chapter_slug" id="edit-ch-slug">
            <div class="form-row">
              <label class="form-label">عنوان الفصل</label>
              <input type="text" name="chapter_title" class="form-input" id="edit-ch-title">
            </div>
            <div class="form-row">
              <label class="form-label">محتوى الفصل <span id="edit-ch-loading" style="display:none"><span class="spinner"></span></span></label>
              <textarea name="chapter_content" class="form-textarea" rows="14" id="edit-ch-content"></textarea>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn btn-gold">💾 حفظ الفصل</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Instructions when nothing open -->
      <div id="edit-instructions" class="admin-card">
        <div class="admin-card-body" style="text-align:center;padding:48px 24px">
          <div style="font-size:3rem;margin-bottom:12px">✏️</div>
          <h3 style="margin-bottom:8px;font-size:1.05rem">التعديل والحذف</h3>
          <p class="text-muted small">
            انتقل لتبويب «الفصول» وانقر على أيقونة التعديل بجانب أي رواية أو فصل،
            أو انتقل لتبويب «الروايات» للحذف والتعديل.
          </p>
        </div>
      </div>
    </div>

  </div><!-- /admin-wrap -->
</main>

<!-- Modals for confirm -->
<div class="modal-backdrop" id="confirm-modal">
  <div class="modal">
    <div class="modal-icon">⚠️</div>
    <h3>تأكيد</h3>
    <p id="confirm-msg"></p>
    <div class="modal-actions">
      <button class="btn btn-ghost" onclick="closeConfirm()">إلغاء</button>
      <button class="btn btn-danger" id="confirm-ok">تأكيد الحذف</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<button id="back-to-top" title="للأعلى">↑</button>

<script src="../script.js"></script>
<script>
// ── Admin-specific JS ─────────────────────────────────────
var _confirmFormId = null;

function confirmDelete(msg, formId) {
  document.getElementById('confirm-msg').textContent = msg;
  document.getElementById('confirm-modal').classList.add('open');
  _confirmFormId = formId;
}
function closeConfirm() {
  document.getElementById('confirm-modal').classList.remove('open');
  _confirmFormId = null;
}
document.getElementById('confirm-ok').addEventListener('click', function() {
  if (_confirmFormId) {
    document.getElementById(_confirmFormId).submit();
  }
  closeConfirm();
});
document.getElementById('confirm-modal').addEventListener('click', function(e) {
  if (e.target === this) closeConfirm();
});

// ── Tabs ─────────────────────────────────────────────────
document.querySelectorAll('.admin-tab').forEach(function(tab) {
  tab.addEventListener('click', function() {
    var idx = this.dataset.tab;
    document.querySelectorAll('.admin-tab').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.remove('active'); });
    this.classList.add('active');
    document.getElementById('tab-' + idx).classList.add('active');
  });
});

// ── File name display ─────────────────────────────────────
document.querySelectorAll('input[type="file"]').forEach(function(inp) {
  inp.addEventListener('change', function() {
    var fn = this.parentElement.querySelector('.file-name');
    if (fn && this.files[0]) fn.textContent = this.files[0].name;
  });
});

// ── Edit Novel ────────────────────────────────────────────
function openEditNovel(jsonStr) {
  var n = JSON.parse(jsonStr);
  document.getElementById('edit-novel-slug').value    = n.slug    || '';
  document.getElementById('edit-novel-title').value   = n.title   || '';
  document.getElementById('edit-novel-summary').value = n.summary || '';
  document.getElementById('edit-novel-card').style.display = '';
  document.getElementById('edit-chapter-card').style.display = 'none';
  document.getElementById('edit-instructions').style.display = 'none';
  // switch to tab 3
  document.querySelectorAll('.admin-tab').forEach(function(t) { t.classList.remove('active'); });
  document.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.remove('active'); });
  document.querySelector('.admin-tab[data-tab="3"]').classList.add('active');
  document.getElementById('tab-3').classList.add('active');
  window.scrollTo({top:0, behavior:'smooth'});
}
function closeEditNovel() {
  document.getElementById('edit-novel-card').style.display = 'none';
  document.getElementById('edit-instructions').style.display = '';
}

// ── Edit Chapter ──────────────────────────────────────────
function openEditChapter(novelSlug, chSlug, chTitle) {
  document.getElementById('edit-ch-novel').value = novelSlug;
  document.getElementById('edit-ch-slug').value  = chSlug;
  document.getElementById('edit-ch-title').value = chTitle;
  document.getElementById('edit-ch-content').value = '';
  document.getElementById('edit-novel-card').style.display = 'none';
  document.getElementById('edit-chapter-card').style.display = '';
  document.getElementById('edit-instructions').style.display = 'none';
  // switch to tab 3
  document.querySelectorAll('.admin-tab').forEach(function(t) { t.classList.remove('active'); });
  document.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.remove('active'); });
  document.querySelector('.admin-tab[data-tab="3"]').classList.add('active');
  document.getElementById('tab-3').classList.add('active');
  window.scrollTo({top:0, behavior:'smooth'});
  // Load chapter content via AJAX
  var loading = document.getElementById('edit-ch-loading');
  if (loading) loading.style.display = 'inline';
  fetch('?action=get_chapter_content&slug=' + encodeURIComponent(novelSlug) + '&ch=' + encodeURIComponent(chSlug))
    .then(function(r){ return r.json(); })
    .then(function(d){
      document.getElementById('edit-ch-content').value = d.content || '';
      if (loading) loading.style.display = 'none';
    })
    .catch(function(){
      if (loading) loading.style.display = 'none';
    });
}
function closeEditChapter() {
  document.getElementById('edit-chapter-card').style.display = 'none';
  document.getElementById('edit-instructions').style.display = '';
}
</script>
<?php
// Handle AJAX get_chapter_content at top of file
if (isset($_GET['action']) && $_GET['action'] === 'get_chapter_content') {
    // already handled at top — but needs to be before HTML output
}
?>
</body>
</html>
