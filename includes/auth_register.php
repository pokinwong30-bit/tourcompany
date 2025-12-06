<?php
// auth_register.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// ---------- helpers ----------
function is_ajax(): bool {
  $h = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
  return $h === 'xmlhttprequest' || $h === 'fetch';
}
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function flash(string $type, string $msg): void {
  $_SESSION['flash'] = ['type'=>$type, 'msg'=>$msg];
}
function back_to(string $url): void {
  header('Location: '.$url);
  exit;
}

// (แนะนำ) เปิดใช้ถ้าคุณใส่ CSRF token ในฟอร์มแล้ว
// if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
//   if (is_ajax()) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'invalid_csrf']); exit; }
//   flash('danger','Invalid CSRF token'); back_to('register.php');
// }

// ---------- inputs ----------
$full_name = trim((string)($_POST['full_name'] ?? ''));
$email     = trim((string)($_POST['email'] ?? ''));
$phone     = trim((string)($_POST['phone'] ?? ''));
$password  = (string)($_POST['password'] ?? '');
$return_to = trim((string)($_POST['return_to'] ?? ''));

// ---------- validate ----------
if ($full_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
  if (is_ajax()) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>'invalid_input']); exit;
  }
  flash('danger','กรอกข้อมูลไม่ครบถ้วนหรือรหัสผ่านสั้นเกินไป (อย่างน้อย 8 ตัวอักษร)');
  back_to('register.php'.($return_to ? ('?return_to='.rawurlencode($return_to)) : ''));
}

try {
  // dup email
  $st = $pdo->prepare("SELECT id FROM members WHERE email = :e LIMIT 1");
  $st->execute([':e'=>$email]);
  if ($st->fetch()) {
    if (is_ajax()) {
      http_response_code(409);
      echo json_encode(['ok'=>false,'error'=>'email_taken']); exit;
    }
    flash('danger','อีเมลนี้มีอยู่ในระบบแล้ว');
    back_to('register.php'.($return_to ? ('?return_to='.rawurlencode($return_to)) : ''));
  }

  $hash = password_hash($password, PASSWORD_DEFAULT);
  $ins = $pdo->prepare("INSERT INTO members (email,password_hash,full_name,phone,created_at) VALUES (:e,:h,:n,:p,NOW())");
  $ins->execute([':e'=>$email, ':h'=>$hash, ':n'=>$full_name, ':p'=>$phone]);
  $id = (int)$pdo->lastInsertId();

  auth_set_member(['id'=>$id,'email'=>$email,'full_name'=>$full_name,'phone'=>$phone]);

  if (is_ajax()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>true,'member'=>auth_member()]); exit;
  }

  flash('success','สมัครสมาชิกสำเร็จ');
  back_to($return_to ?: 'index.php');

} catch (Throwable $e) {
  if (is_ajax()) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'server_error']); exit;
  }
  flash('danger','เกิดข้อผิดพลาดจากเซิร์ฟเวอร์');
  back_to('register.php'.($return_to ? ('?return_to='.rawurlencode($return_to)) : ''));
}
