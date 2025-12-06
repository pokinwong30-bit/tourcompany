<?php
// booking_submit.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/db.php';

// ====== CONFIG (optional) ======
$SCHEMA_TOURS = getenv('DB_SCHEMA_TOURS') ?: '';
$SCHEMA_AUX   = getenv('DB_SCHEMA_AUX')   ?: $SCHEMA_TOURS;
$RECAPTCHA_SECRET = defined('RECAPTCHA_SECRET') ? RECAPTCHA_SECRET : (getenv('RECAPTCHA_SECRET') ?: ''); // ตั้งใน config/.env

// ====== Helpers ======
function tbl(string $schema, string $name): string {
  return ($schema ? ("`".$schema."`.") : "") . "`".$name."`";
}
$T_TOURS       = tbl($SCHEMA_TOURS, 'tours');
$T_DEPARTURES  = tbl($SCHEMA_AUX,   'tour_departures');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function pick(array $row, array $keys, $default=null) {
  foreach ($keys as $k) if (array_key_exists($k,$row) && $row[$k] !== null && $row[$k] !== '') return $row[$k];
  return $default;
}
function flash(string $type, string $msg, array $data=[]): void {
  $_SESSION['flash'] = ['type'=>$type, 'msg'=>$msg, 'data'=>$data];
}
function redirect_back(string $code): void {
  header('Location: tour.php?code='.urlencode($code).'#booking'); exit;
}
function ensure_csrf(): void {
  // เปิดใช้ถ้าในฟอร์มมีส่งมา (ถ้ายังไม่ใส่ hidden token ในฟอร์ม ให้ข้ามเช็คนี้ได้ชั่วคราว)
  if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    http_response_code(400);
    exit('Invalid CSRF token');
  }
}

// ====== รับค่า POST ======
$code          = trim((string)($_POST['tour_code'] ?? ''));
$tourIdReq     = (int)($_POST['tour_id'] ?? 0);
$depId         = (int)($_POST['departure_id'] ?? 0);
$fullName      = trim((string)($_POST['full_name'] ?? ''));
$email         = trim((string)($_POST['email'] ?? ''));
$phone         = trim((string)($_POST['phone'] ?? ''));
$contactTime   = (string)($_POST['contact_time'] ?? '');
$qty           = max(1, (int)($_POST['qty'] ?? 1));
$note          = trim((string)($_POST['note'] ?? ''));

// ====== (แนะนำ) เปิดใช้ CSRF ถ้าใส่ในฟอร์มแล้ว ======
// ensure_csrf();

// ====== reCAPTCHA verify (ถ้าตั้งค่าไว้) ======
if ($RECAPTCHA_SECRET) {
  $token = $_POST['g-recaptcha-response'] ?? '';
  if (!$token) {
    flash('danger', 'โปรดยืนยัน reCAPTCHA อีกครั้ง'); redirect_back($code);
  }
  // verify google
  $postData = http_build_query(['secret'=>$RECAPTCHA_SECRET,'response'=>$token,'remoteip'=>$_SERVER['REMOTE_ADDR'] ?? null]);
  $respRaw = null;

  if (function_exists('curl_init')) {
    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER=>true,
      CURLOPT_POST=>true,
      CURLOPT_POSTFIELDS=>$postData,
      CURLOPT_TIMEOUT=>10,
    ]);
    $respRaw = curl_exec($ch);
    curl_close($ch);
  } else {
    $opts = ['http'=>['method'=>'POST','header'=>"Content-type: application/x-www-form-urlencoded\r\n",'content'=>$postData,'timeout'=>10]];
    $respRaw = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, stream_context_create($opts));
  }
  $resp = @json_decode((string)$respRaw, true);
  if (!$resp || empty($resp['success'])) {
    flash('danger', 'reCAPTCHA ไม่ผ่านการตรวจสอบ'); redirect_back($code);
  }
}

// ====== ตรวจความถูกต้องเบื้องต้น ======
if ($code === '' || $depId <= 0 || $fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '') {
  flash('danger', 'กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง'); redirect_back($code ?: '');
}

// ====== ดึงข้อมูลทัวร์ (เพื่อหา base price + ตรวจความสัมพันธ์) ======
try {
  // พยายามดึง is_on_sale ถ้ามี
  try {
    $st = $pdo->prepare("SELECT * FROM $T_TOURS WHERE (code = :code OR id = :id) AND is_on_sale = 1 LIMIT 1");
    $st->execute([':code'=>$code, ':id'=>$tourIdReq]);
  } catch (Throwable $e) {
    $st = $pdo->prepare("SELECT * FROM $T_TOURS WHERE (code = :code OR id = :id) LIMIT 1");
    $st->execute([':code'=>$code, ':id'=>$tourIdReq]);
  }
  $tour = $st->fetch(PDO::FETCH_ASSOC);
  if (!$tour) { flash('danger','ไม่พบทัวร์ที่เลือก'); redirect_back($code); }
} catch (Throwable $e) {
  flash('danger','เกิดข้อผิดพลาดในการอ่านข้อมูลทัวร์'); redirect_back($code);
}

$tourId   = (int)pick($tour, ['id','tour_id','tours_id'], 0);
$tourCode = (string)pick($tour, ['code'], $code);
$basePrice = (float)pick($tour, ['price','base_price','sale_price'], 0.0);

// ====== เริ่มธุรกรรม ======
$pdo->beginTransaction();

try {
  // ล็อกแถว departure ที่เลือกไว้ (กัน oversell)
  $sql = "SELECT * FROM $T_DEPARTURES WHERE id = :id FOR UPDATE";
  $stDep = $pdo->prepare($sql);
  $stDep->execute([':id'=>$depId]);
  $dep = $stDep->fetch(PDO::FETCH_ASSOC);
  if (!$dep) {
    throw new RuntimeException('ไม่พบรอบที่เลือก');
  }

  // ตรวจว่า round นี้สัมพันธ์กับทัวร์ (ถ้ามีคอลัมน์ให้ตรวจ)
  $okRelation = true;
  if (array_key_exists('tour_id', $dep) && $dep['tour_id'] !== null) {
    $okRelation = $okRelation && ((int)$dep['tour_id'] === $tourId);
  }
  if (array_key_exists('tour_code', $dep) && $dep['tour_code'] !== null && $tourCode !== '') {
    $okRelation = $okRelation && ((string)$dep['tour_code'] === $tourCode);
  }
  if (!$okRelation) {
    throw new RuntimeException('รอบเดินทางไม่ตรงกับโปรแกรมทัวร์');
  }

  // ตรวจความพร้อมจอง: อดีต/เต็ม
  $today = date('Y-m-d');
  $start = (string)($dep['start_date'] ?? '');
  if ($start !== '' && $start < $today) {
    throw new RuntimeException('รอบเดินทางนี้ผ่านไปแล้ว');
  }
  $cap = isset($dep['capacity']) ? (int)$dep['capacity'] : null;
  if ($cap !== null && $cap < $qty) {
    throw new RuntimeException('จำนวนที่นั่งไม่พอในรอบที่เลือก');
  }

  // ราคา: ใช้ราคาของรอบถ้ามี, ไม่งั้นใช้ basePrice ของทัวร์
  $unitPrice = isset($dep['price']) && $dep['price'] !== null ? (float)$dep['price'] : (float)$basePrice;
  $totalPrice = $unitPrice > 0 ? $unitPrice * $qty : null;

  // สร้างรหัสอ้างอิง BK-YYYYMM-xxxx (สุ่ม/กันชน)
  $ym = date('Ym'); $ref = '';
  for ($i=0; $i<5; $i++) {
    $ref = sprintf('BK-%s-%04d', $ym, random_int(0, 9999));
    $chk = $pdo->prepare("SELECT 1 FROM bookings WHERE ref_code = :r LIMIT 1");
    $chk->execute([':r'=>$ref]);
    if (!$chk->fetch()) break;
    $ref = '';
  }
  if ($ref === '') { throw new RuntimeException('ไม่สามารถสร้างรหัสการจองได้'); }

  // member_id จาก session (ถ้ามี)
  $memberId = isset($_SESSION['member']['id']) ? (int)$_SESSION['member']['id'] : null;

  // INSERT bookings
  $ins = $pdo->prepare("
    INSERT INTO bookings
      (ref_code, tour_id, tour_code, departure_id, member_id,
       full_name, email, phone, contact_time, qty, unit_price, total_price, note,
       status, created_ip, user_agent)
    VALUES
      (:ref, :tour_id, :tour_code, :dep_id, :member_id,
       :full_name, :email, :phone, :contact_time, :qty, :unit_price, :total_price, :note,
       'pending', :ip, :ua)
  ");
  $ins->execute([
    ':ref'         => $ref,
    ':tour_id'     => $tourId ?: null,
    ':tour_code'   => $tourCode,
    ':dep_id'      => $depId,
    ':member_id'   => $memberId,
    ':full_name'   => $fullName,
    ':email'       => $email,
    ':phone'       => $phone,
    ':contact_time'=> $contactTime !== '' ? $contactTime : null,
    ':qty'         => $qty,
    ':unit_price'  => $unitPrice > 0 ? $unitPrice : null,
    ':total_price' => $totalPrice,
    ':note'        => $note !== '' ? $note : null,
    ':ip'          => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
    ':ua'          => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
  ]);

  // UPDATE capacity (ถ้าจำกัดที่นั่ง)
  if ($cap !== null) {
    $upd = $pdo->prepare("UPDATE $T_DEPARTURES SET capacity = capacity - :q WHERE id = :id");
    $upd->execute([':q'=>$qty, ':id'=>$depId]);
  }

  $pdo->commit();

  flash('success', 'บันทึกการจองเรียบร้อยแล้ว (รหัส: '.$ref.')', [
    'ref_code'=>$ref,
    'qty'=>$qty,
    'total'=>$totalPrice,
  ]);
  redirect_back($tourCode);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  // บันทึก error log ได้ตามต้องการ
  flash('danger', 'ไม่สามารถทำรายการได้: '.$e->getMessage());
  redirect_back($code);
}
