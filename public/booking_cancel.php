<?php
// booking_cancel.php (AJAX) — ลบการจอง + คืนที่นั่งให้ tour_departures
require_once __DIR__ . '/../includes/guard.php';
require_backoffice();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/audit.php';

// ===== Hardening: ห้ามมี output ปะปนกับ JSON =====
@ini_set('display_errors', '0');           // ไม่แสดง error ออกจอ
@ini_set('html_errors', '0');
while (ob_get_level()) { @ob_end_clean(); } // ล้าง buffer ใด ๆ ที่ค้างอยู่

// helper ส่ง JSON เสมอ
function json_out(int $code, array $data) {
  while (ob_get_level()) { @ob_end_clean(); }     // กันทุกเคส
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

// ===== Method check =====
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  json_out(405, ['ok'=>false, 'error'=>'Method Not Allowed']);
}

// ===== CSRF =====
csrf_validate(); // จะไม่ echo อะไรถ้าผ่าน

// ===== สิทธิ์ยืนยันรหัสลับ =====
function require_password_confirm_if_needed(): bool {
  if (can('bypass_confirm')) return true; // Director ข้าม
  $secret = $_POST['secret_confirm'] ?? '';
  if ($secret === '') return false;
  return verify_shared_secret($secret);
}
if (!require_password_confirm_if_needed()) {
  json_out(403, ['ok'=>false, 'error'=>'ต้องยืนยันรหัสลับกลาง']);
}

// ===== รับค่า =====
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  json_out(400, ['ok'=>false, 'error'=>'ข้อมูลไม่ถูกต้อง: id']);
}

try {
  $pdo->beginTransaction();

  // Lock แถว booking
  $st = $pdo->prepare("
    SELECT id, ref_code, tour_id, departure_id, qty, status
    FROM bookings
    WHERE id = :id
    FOR UPDATE
  ");
  $st->execute([':id' => $id]);
  $bk = $st->fetch(PDO::FETCH_ASSOC);
  if (!$bk) {
    $pdo->rollBack();
    json_out(404, ['ok'=>false, 'error'=>'ไม่พบการจอง']);
  }

  // คืนที่นั่งถ้ามี departure
  if (!empty($bk['departure_id'])) {
    $st2 = $pdo->prepare("UPDATE tour_departures SET capacity = capacity + :q WHERE id = :dep");
    $st2->execute([
      ':q'   => (int)$bk['qty'],
      ':dep' => (int)$bk['departure_id'],
    ]);
  }

  // ลบประวัติ (ถ้ามีตาราง)
  try {
    $pdo->prepare("DELETE FROM booking_status_history WHERE booking_id = :id")
        ->execute([':id' => $bk['id']]);
  } catch (Throwable $e) {
    // ไม่มีตารางก็ข้าม
  }

  // ลบ booking
  $pdo->prepare("DELETE FROM bookings WHERE id = :id")
      ->execute([':id' => $bk['id']]);

  // audit log (ไม่ให้หลุด output)
  try {
    audit_log('booking_delete', [
      'booking_id'   => (int)$bk['id'],
      'ref_code'     => $bk['ref_code'],
      'tour_id'      => (int)$bk['tour_id'],
      'departure_id' => (int)$bk['departure_id'],
      'qty'          => (int)$bk['qty'],
      'status'       => $bk['status'],
      'note'         => 'Delete booking & return capacity',
    ]);
  } catch (Throwable $e) {
    // ข้ามข้อผิดพลาดของระบบ log
  }

  $pdo->commit();

  json_out(200, [
    'ok'           => true,
    'id'           => (int)$bk['id'],
    'ref_code'     => $bk['ref_code'],
    'returned_qty' => (int)$bk['qty'],
    'departure_id' => (int)$bk['departure_id'],
  ]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_out(500, ['ok'=>false, 'error'=>'ลบไม่สำเร็จ: '.$e->getMessage()]);
}
