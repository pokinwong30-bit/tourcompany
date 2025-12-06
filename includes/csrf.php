<?php
// includes/csrf.php
require_once __DIR__.'/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function _csrf_trusted($url) {
    if (!$url) return false;
    $parts = @parse_url($url);
    if (!$parts || empty($parts['scheme']) || empty($parts['host'])) return false;
  
    $origin = strtolower($parts['scheme'].'://'.$parts['host'].(isset($parts['port'])?':'.$parts['port']:''));
  
    // 1) ตรงกับรายการที่ตั้งไว้ใน .env
    foreach (CSRF_TRUSTED_ORIGINS as $t) {
      $t = strtolower(rtrim($t, '/'));
      if ($origin === $t) return true;
    }
  
    // 2) โหมด dev/local: ยอมรับ origin ของเซิร์ฟเวอร์ปัจจุบันอัตโนมัติ
    if (defined('APP_ENV') && APP_ENV !== 'production') {
      $sch = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
      $curr = strtolower($sch.'://'.($_SERVER['HTTP_HOST'] ?? 'localhost'));
      // HTTP_HOST รวมพอร์ตอยู่แล้ว (เช่น localhost:8888)
      if ($origin === $curr) return true;
    }
  
    return false;
  }
  
function _csrf_raw_token() {
  if (empty($_SESSION['_csrf_raw'])) {
    $_SESSION['_csrf_raw'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['_csrf_raw'];
}

function _csrf_sign($raw) {
  return hash_hmac('sha256', $raw, (string)CSRF_TOKEN_KEY);
}

// token รูปแบบ: raw.hmac (base64url)
function csrf_token() {
  $raw = _csrf_raw_token();
  $sig = _csrf_sign($raw);
  $token = $raw.'.'.$sig;
  return rtrim(strtr(base64_encode($token), '+/', '-_'), '=');
}

function csrf_field() {
  $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
  return "<input type=\"hidden\" name=\"_csrf\" value=\"$t\">";
}

function csrf_validate() {
  // ตรวจ Origin / Referer
  $origin  = $_SERVER['HTTP_ORIGIN']  ?? null;
  $referer = $_SERVER['HTTP_REFERER'] ?? null;
  if ($origin) {
    if (!_csrf_trusted($origin)) { http_response_code(400); die('Invalid CSRF origin'); }
  } elseif ($referer) {
    if (!_csrf_trusted($referer)) { http_response_code(400); die('Invalid CSRF referer'); }
  }
  // ตรวจ token
  $enc = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
  if (!$enc) { http_response_code(400); die('Missing CSRF token'); }
  $decoded = base64_decode(strtr($enc, '-_', '+/'), true);
  if ($decoded === false || !str_contains($decoded, '.')) { http_response_code(400); die('Bad CSRF token'); }
  [$raw, $sig] = explode('.', $decoded, 2);
  if (!hash_equals(_csrf_raw_token(), $raw)) { http_response_code(400); die('CSRF mismatch'); }
  if (!hash_equals(_csrf_sign($raw), $sig))  { http_response_code(400); die('CSRF invalid signature'); }
}
