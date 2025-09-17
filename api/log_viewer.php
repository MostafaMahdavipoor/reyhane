<?php
// public_html/api/log_viewer.php

// نام فایل لاگ
$log_file = 'log.txt';

// تنظیمات ظاهری صفحه
echo '<!DOCTYPE html>';
echo '<html lang="fa" dir="rtl">';
echo '<head>';
echo '  <meta charset="UTF-8">';
echo '  <title>نمایشگر لاگ</title>';
echo '  <style>';
echo '    body { font-family: Vazirmatn, sans-serif; background-color: #f4f4f4; color: #333; line-height: 1.6; padding: 20px; }';
echo '    .container { max-width: 1000px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }';
echo '    h1 { color: #555; }';
echo '    pre { background: #2d2d2d; color: #f1f1f1; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-wrap: break-word; font-family: monospace; }';
echo '    .clear-btn { background-color: #e74c3c; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 10px; }';
echo '  </style>';
echo '</head>';
echo '<body>';
echo '  <div class="container">';
echo '    <h1>نمایشگر فایل لاگ (log.txt)</h1>';

// بررسی وجود فایل لاگ
if (file_exists($log_file)) {
    // خواندن محتوای فایل
    $content = file_get_contents($log_file);
    
    // نمایش محتوا
    echo '<pre>' . htmlspecialchars($content) . '</pre>';
    
    // دکمه برای پاک کردن لاگ
    echo '<form method="post"><button type="submit" name="clear_log" class="clear-btn">پاک کردن لاگ</button></form>';
} else {
    echo '<p>فایل لاگ هنوز ایجاد نشده است. لطفاً ابتدا صفحه اصلی سایت را یک بار باز کنید تا لاگ ثبت شود.</p>';
}

echo '  </div>';
echo '</body>';
echo '</html>';

// منطق پاک کردن فایل لاگ
if (isset($_POST['clear_log'])) {
    if (file_exists($log_file)) {
        unlink($log_file); // حذف فایل
        header("Location: " . $_SERVER['PHP_SELF']); // رفرش صفحه
        exit;
    }
}
?>