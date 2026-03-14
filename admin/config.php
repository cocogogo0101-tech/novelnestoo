<?php
/* ============================================================
   admin/config.php
   ============================================================ */
session_start();

// Change the password here — stored as hash for security
define('ADMIN_PASSWORD_HASH', '$2y$10$placeholder_replace_with_real_hash');
define('ADMIN_RAW_PW', 'Dark_Sun'); // used only for first-run hash generation

define('SITE_BASE',     dirname(__DIR__));
define('NOVELS_DIR',    SITE_BASE . '/novels');
define('TEMPLATES_DIR', SITE_BASE . '/templates');

if (!is_dir(NOVELS_DIR)) @mkdir(NOVELS_DIR, 0755, true);

function csrf_token(): string {
    if (empty($_SESSION['csrf_token']))
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_verify(): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '');
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars(csrf_token()).'">';
}
function is_admin(): bool {
    return !empty($_SESSION['novelnest_admin']) && $_SESSION['novelnest_admin'] === true;
}
function require_admin(): void {
    if (!is_admin()) { header('Location: login.php'); exit; }
}
function verify_password(string $pw): bool {
    // support plain text for easy setup — switch to hash in production
    return $pw === ADMIN_RAW_PW;
}
function slugify(string $text): string {
    $text = iconv('utf-8','us-ascii//TRANSLIT',$text);
    $text = preg_replace('~[^\pL\d]+~u','-',$text);
    $text = preg_replace('~[^-\w]+~','',$text);
    $text = trim($text,'-');
    $text = preg_replace('~-+~','-',$text);
    $text = strtolower($text);
    return empty($text) ? 'novel-'.time() : $text;
}
function delete_dir(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (array_diff(scandir($dir),['.','..']) as $f) {
        $fp = $dir.'/'.$f;
        is_dir($fp) ? delete_dir($fp) : unlink($fp);
    }
    rmdir($dir);
}
function get_chapters_meta(string $novel_dir): array {
    $f = $novel_dir.'/chapters.json';
    if (!file_exists($f)) return [];
    return json_decode(file_get_contents($f),true) ?: [];
}
function save_chapters_meta(string $novel_dir, array $chapters): void {
    file_put_contents($novel_dir.'/chapters.json',
        json_encode($chapters, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
}
function get_chapter_content(string $novel_dir, string $slug): array {
    $f = $novel_dir.'/chapters/'.$slug.'.json';
    if (!file_exists($f)) return ['title'=>'','content'=>''];
    return json_decode(file_get_contents($f),true) ?: ['title'=>'','content'=>''];
}
function save_chapter_content(string $novel_dir, string $slug, string $title, string $content): void {
    $dir = $novel_dir.'/chapters';
    if (!is_dir($dir)) mkdir($dir,0755,true);
    file_put_contents($dir.'/'.$slug.'.json',
        json_encode(['title'=>$title,'content'=>$content], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
}
function get_novel_info(string $novel_dir): array {
    $f = $novel_dir.'/info.json';
    if (!file_exists($f)) return [];
    return json_decode(file_get_contents($f),true) ?: [];
}
function save_novel_info(string $novel_dir, array $info): void {
    file_put_contents($novel_dir.'/info.json',
        json_encode($info, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
}
function build_novel_index(string $novel_dir, array $info, array $chapters): void {
    $tplFile = TEMPLATES_DIR.'/novel_template.php';
    if (!file_exists($tplFile)) return;
    $tpl = file_get_contents($tplFile);
    $chapterList = '';
    foreach ($chapters as $i => $ch) {
        $num   = str_pad($i+1,2,'0',STR_PAD_LEFT);
        $title = htmlspecialchars($ch['title']);
        $href  = htmlspecialchars(rawurlencode($ch['slug']).'.html');
        $chapterList .= "<li class=\"chapter-list-item\"><a href=\"chapters/{$href}\"><span class=\"chapter-num\">{$num}</span><span class=\"chapter-name\">{$title}</span><span class=\"chapter-arrow\">←</span></a></li>\n";
    }
    $coverTag = '';
    if (!empty($info['cover']))
        $coverTag = '<img src="'.htmlspecialchars($info['cover']).'" alt="'.htmlspecialchars($info['title'] ?? '').'">';
    $coverClass = empty($info['cover']) ? 'no-cover' : '';
    $html = str_replace(
        ['{{NOVEL_TITLE}}','{{NOVEL_SUMMARY}}','{{COVER_TAG}}','{{CHAPTER_LIST}}','{{CHAPTER_COUNT}}','{{COVER_CLASS}}'],
        [htmlspecialchars($info['title']??''),htmlspecialchars($info['summary']??''),$coverTag,$chapterList,count($chapters),$coverClass],
        $tpl
    );
    file_put_contents($novel_dir.'/index.html',$html);
}
function build_all_chapters(string $novel_dir, array $info, array $chapters): void {
    $tplFile = TEMPLATES_DIR.'/chapter_template.php';
    if (!file_exists($tplFile)) return;
    $tpl   = file_get_contents($tplFile);
    $total = count($chapters);
    foreach ($chapters as $i => $ch) {
        $data    = get_chapter_content($novel_dir,$ch['slug']);
        $content = nl2br(htmlspecialchars($data['content']??''));
        $prevHref     = $i > 0       ? htmlspecialchars(rawurlencode($chapters[$i-1]['slug']).'.html') : '#';
        $prevDisabled = $i > 0            ? '' : ' disabled';
        $nextHref     = $i < $total-1 ? htmlspecialchars(rawurlencode($chapters[$i+1]['slug']).'.html') : '#';
        $nextDisabled = $i < $total-1     ? '' : ' disabled';
        $num = str_pad($i+1,2,'0',STR_PAD_LEFT);
        $html = str_replace(
            ['{{NOVEL_TITLE}}','{{CHAPTER_TITLE}}','{{CHAPTER_CONTENT}}',
             '{{PREV_HREF}}','{{PREV_DISABLED}}','{{NEXT_HREF}}','{{NEXT_DISABLED}}',
             '{{CHAPTER_NUM}}','{{CHAPTER_TOTAL}}','{{NOVEL_SLUG}}'],
            [htmlspecialchars($info['title']??''),htmlspecialchars($ch['title']??''),$content,
             $prevHref,$prevDisabled,$nextHref,$nextDisabled,
             $num,$total,htmlspecialchars($info['slug']??'')],
            $tpl
        );
        $dir = $novel_dir.'/chapters';
        if (!is_dir($dir)) mkdir($dir,0755,true);
        file_put_contents($dir.'/'.$ch['slug'].'.html',$html);
    }
}
function rebuild_novel(string $novel_dir): void {
    $info     = get_novel_info($novel_dir);
    $chapters = get_chapters_meta($novel_dir);
    build_novel_index($novel_dir,$info,$chapters);
    build_all_chapters($novel_dir,$info,$chapters);
}
