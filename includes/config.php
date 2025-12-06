<?php
// includes/config.php

// 1) โหลดไฟล์ .env แบบง่าย (ถ้ามี)
$__env_path = __DIR__ . '/../.env';
if (file_exists($__env_path)) {
    $lines = file($__env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '' || $t[0] === '#') { continue; }
        $parts = explode('=', $line, 2);
        $k = trim($parts[0]);
        $v = isset($parts[1]) ? trim($parts[1]) : '';
        putenv($k.'='.$v);
    }
}

// 2) helper env()
function env($key, $default = null) {
    $v = getenv($key);
    return ($v === false) ? $default : $v;
}
function env_bool($key, $default = false) {
    $v = strtolower((string) env($key, $default ? '1' : '0'));
    return in_array($v, array('1','true','on','yes'), true);
}

// 3) APP config
define('APP_ENV',   env('APP_ENV', 'local'));
define('APP_DEBUG', env_bool('APP_DEBUG', APP_ENV !== 'production'));
define('APP_URL',   env('APP_URL', ''));

// คำนวณ BASE_URL จาก path ของ APP_URL ถ้ามี; ไม่งั้นใช้ค่าที่คุณต้องการเองได้
$__base_url = '/tourcompany/public'; // ค่า fallback ของคุณ
if (APP_URL) {
    $parts = @parse_url(APP_URL);
    if (!empty($parts['path'])) {
        $__base_url = rtrim($parts['path'], '/');
    }
}
define('BASE_URL', $__base_url);

// 4) DB config (รองรับ DB_PORT)
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', (int) env('DB_PORT', 3306));
define('DB_NAME', env('DB_NAME', 'tour_company'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', 'root'));

// 5) Session / CSRF / Trusted origins
define('SESSION_NAME',   env('SESSION_NAME', 'TC_ADMIN_SESSID'));
define('CSRF_TOKEN_KEY', env('CSRF_TOKEN_KEY', 'change-me'));

// รองรับหลาย origin คั่นด้วย comma, และตัด '/' ท้ายออก
$__trusted = env('CSRF_TRUSTED_ORIGINS', '');
$__trusted_list = array();
if ($__trusted !== '') {
    foreach (explode(',', $__trusted) as $__item) {
        $__item = rtrim(trim($__item), '/');
        if ($__item !== '') { $__trusted_list[] = $__item; }
    }
}
define('CSRF_TRUSTED_ORIGINS', $__trusted_list);

// 6) แสดง/ซ่อน error ตาม DEBUG
if (!APP_DEBUG) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    ini_set('display_errors', '1');
}
