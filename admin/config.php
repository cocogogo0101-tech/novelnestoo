<?php
// config.php — إعدادات لوحة التحكم
// ضع هذا الملف داخل /admin

session_start();

// ضع هنا الكلمة السرية السيرفرية (سيتم تشفيرها لاحقًا حسب رغبتك)
define('ADMIN_PASSWORD', 'Dark_Sun'); // غيّر لاحقاً

// مسارات مهمة
define('SITE_BASE', dirname(__DIR__)); // جذر المشروع
define('NOVELS_DIR', SITE_BASE . '/novels');
define('TEMPLATES_DIR', SITE_BASE . '/templates');

// Ensure novels dir exists
if (!file_exists(NOVELS_DIR)) @mkdir(NOVELS_DIR, 0755, true);

// وظيفة مساعدة: عمل slug من عنوان
function slugify($text){
  $text = iconv('utf-8','us-ascii//TRANSLIT',$text);
  $text = preg_replace('~[^\pL\d]+~u', '-', $text);
  $text = preg_replace('~[^-\w]+~', '', $text);
  $text = trim($text, '-');
  $text = preg_replace('~-+~', '-', $text);
  $text = strtolower($text);
  if (empty($text)) return 'novel-'.time();
  return $text;
}