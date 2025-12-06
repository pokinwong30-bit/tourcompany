<?php
// booking_status_add.php (AJAX: add history + maybe change status)
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__.'/../includes/guard.php';
require_backoffice();
require_once __DIR__.'/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

function json_fail(int $code, string $err){ http_response_code($code); echo json_encode(['ok'=>false,'error'=>$err]); exit; }
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// CSRF (เปิดใช้เมื่อมี token)
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
  json_fail(400,'invalid_csrf');
}

$bookingId = (int)($_POST['booking_id'] ?? 0);
$note      = trim((string)($_POST['note'] ?? ''));

if ($bookingId<=0 || $note==='') json_fail(422,'invalid_input');

// โหลด booking
$st = $pdo->prepare("SELECT * FROM bookings WHERE id = :id LIMIT 1");
$st->execute([':id'=>$bookingId]);
$bk = $st->fetch(PDO::FETCH_ASSOC);
if (!$bk) json_fail(404,'not_found');

// ผู้กระทำ
$user = function_exists('current_user') ? current_user() : null;
$actorId   = $user['id']   ?? null;
$actorName = $user['full_name'] ?? ($user['email'] ?? 'Backoffice');

// ตรวจ trigger ปิดเคส => เปลี่ยนสถานะเป็น paid
$noteLower = mb_strtolower($note, 'UTF-8');
$newStatus = null;
// เผื่อข้อความใกล้เคียง
if (mb_strpos($noteLower, 'ชำระเงินเรียบร้อยแล้ว') !== false
 || mb_strpos($noteLower, 'โอนเงินแล้ว') !== false
) {
  $newStatus = 'paid';
}

$pdo->beginTransaction();
try {
  // 1) เพิ่มลง history (ถ้ามีตาราง)
  $histInserted = false;
  try {
    $sqlH = "INSERT INTO booking_status_history
            (booking_id, actor_id, actor_name, note, new_status, created_at)
             VALUES (:bid, :aid, :aname, :note, :ns, NOW())";
    $stH = $pdo->prepare($sqlH);
    $stH->execute([
      ':bid'=>$bookingId,
      ':aid'=>$actorId,
      ':aname'=>$actorName,
      ':note'=>$note,
      ':ns'=>$newStatus
    ]);
    $histInserted = true;
  } catch (Throwable $e) {
    // ถ้าไม่มีตาราง ก็ข้าม (ระบบยังอัปเดตสถานะได้)
  }

  // 2) ถ้ามี newStatus ให้ปรับใน bookings
  if ($newStatus !== null) {
    $stU = $pdo->prepare("UPDATE bookings SET status = :st WHERE id = :id");
    $stU->execute([':st'=>$newStatus, ':id'=>$bookingId]);
  }

  $pdo->commit();

  // ตอบกลับ JSON
  $createdAt = date('Y-m-d H:i:s');
  // แปลงเป็นรูปแบบไทยฝั่ง PHP เพื่อความสม่ำเสมอ
  $months=[1=>'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
  $ts = strtotime($createdAt);
  $th_created = (int)date('j',$ts).' '.$months[(int)date('n',$ts)].' '.((int)date('Y',$ts)+543).' '.date('H:i',$ts).' น.';

  echo json_encode([
    'ok'=>true,
    'entry'=>[
      'actor_name'=>$actorName,
      'note'=>$note,
      'created_at'=>$createdAt,
      'created_at_th'=>$th_created,
      'new_status'=>$newStatus
    ],
    'status'=>$newStatus ?? ($bk['status'] ?? null)
  ]);
  exit;

} catch (Throwable $e) {
  $pdo->rollBack();
  json_fail(500,'server_error');
}
