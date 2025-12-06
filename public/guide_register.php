<?php
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/csrf.php';
require_once __DIR__.'/../includes/mailer_smtp.php';

$errors = [];
$info   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();

  $first_name = trim($_POST['first_name'] ?? '');
  $last_name  = trim($_POST['last_name'] ?? '');
  $phone      = trim($_POST['phone'] ?? '');
  $email      = trim($_POST['email'] ?? '');
  $alt_phone  = trim($_POST['alt_phone'] ?? '');
  $address    = trim($_POST['address'] ?? '');
  $line_id    = trim($_POST['line_id'] ?? '');

  // validate เบื้องต้น
  if ($first_name === '') $errors[] = 'กรุณากรอกชื่อ';
  if ($last_name  === '') $errors[] = 'กรุณากรอกนามสกุล';
  if ($phone      === '') $errors[] = 'กรุณากรอกเบอร์โทรศัพท์';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'อีเมลไม่ถูกต้อง';

  // อัปโหลดรูปบัตร (พื้นฐาน)
  $idcard_path = null;
  if (!empty($_FILES['idcard']['name'])) {
    $f = $_FILES['idcard'];
    if ($f['error'] === UPLOAD_ERR_OK) {
      $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime  = finfo_file($finfo, $f['tmp_name']);
      finfo_close($finfo);
      if (!isset($allowed[$mime])) {
        $errors[] = 'ชนิดไฟล์ไม่รองรับ (อนุญาต jpg, png, webp)';
      } else {
        $ext = $allowed[$mime];
        $dir = __DIR__ . '/../uploads/ids';
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        $basename = 'id_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $dir . '/' . $basename;
        if (move_uploaded_file($f['tmp_name'], $dest)) {
          $idcard_path = 'uploads/ids/' . $basename; // path สำหรับ public
        } else {
          $errors[] = 'อัปโหลดไฟล์ไม่สำเร็จ';
        }
      }
    } else {
      $errors[] = 'อัปโหลดไฟล์ผิดพลาด (code: '.$f['error'].')';
    }
  }

  if (!$errors) {
    // สร้าง token อนุมัติ (สำหรับ director)
    $token = bin2hex(random_bytes(24));
    $expires = (new DateTime('+3 days'))->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare("
      INSERT INTO guides (first_name,last_name,phone,email,alt_phone,address,line_id,idcard_path,status,approval_token,approval_token_expires)
      VALUES (?,?,?,?,?,?,?,?, 'pending', ?, ?)
    ");
    $stmt->execute([$first_name,$last_name,$phone,$email,$alt_phone,$address,$line_id,$idcard_path,$token,$expires]);
    $guideId = $pdo->lastInsertId();

    // ===== ส่งอีเมลไปคนเดียวตามที่กำหนด =====
    $notifyEmail = 'pokin.wong30@gmail.com';

    // ลิงก์เปิดหน้า guides และดีด modal
    $openUrl = defined('APP_URL') && APP_URL
      ? APP_URL
      : (($_SERVER['REQUEST_SCHEME'] ?? 'http').'://'.($_SERVER['HTTP_HOST'] ?? 'localhost').BASE_URL);
    $approveLink = rtrim($openUrl, '/').'/guides.php?approve_token='.urlencode($token);

    $subject = "มีการลงทะเบียน Guide ใหม่: {$first_name} {$last_name}";
    $html  = '<div style="font-family:Arial,Helvetica,sans-serif; line-height:1.6">';
    $html .= '<h2 style="margin-bottom:10px;color:#0d6efd;">มีการลงทะเบียน Guide ใหม่</h2>';
    $html .= '<p>ชื่อ: <strong>'.htmlspecialchars($first_name.' '.$last_name).'</strong></p>';
    $html .= '<p>อีเมล: '.htmlspecialchars($email).'</p>';
    $html .= '<p>โทรศัพท์: '.htmlspecialchars($phone).($alt_phone ? ' / '.htmlspecialchars($alt_phone) : '').'</p>';
    if ($line_id) {
      $html .= '<p>Line ID: '.htmlspecialchars($line_id).'</p>';
    }
    if ($address) {
      $html .= '<p>ที่อยู่: '.nl2br(htmlspecialchars($address)).'</p>';
    }
    $html .= '<p style="margin-top:20px;">เปิดดูรายละเอียดและอนุมัติได้จากลิงก์นี้:</p>';
    $html .= '<p><a href="'.htmlspecialchars($approveLink).'" style="background:#0d6efd;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px;">เปิดรายละเอียด Guide</a></p>';
    $html .= '</div>';

    @send_mail_html($notifyEmail, $subject, $html);

    $info = 'ส่งคำขอลงทะเบียนเรียบร้อย! กรุณารอการอนุมัติจากผู้ดูแลระบบ';
  }
}

render_header('ลงทะเบียนไกด์');
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="mb-3">ลงทะเบียน Guide</h4>

        <?php if ($info): ?>
          <div class="alert alert-success"><?= htmlspecialchars($info) ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
          <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">ชื่อ</label>
              <input type="text" name="first_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">นามสกุล</label>
              <input type="text" name="last_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">เบอร์โทรศัพท์</label>
              <input type="text" name="phone" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">เบอร์โทรศัพท์ (สำรอง)</label>
              <input type="text" name="alt_phone" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Line ID</label>
              <input type="text" name="line_id" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">ที่อยู่</label>
              <textarea name="address" rows="3" class="form-control"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">อัปโหลดรูปบัตรประชาชน (jpg, png, webp)</label>
              <input type="file" name="idcard" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/*">
            </div>
          </div>
          <div class="d-flex justify-content-end mt-4">
            <button class="btn btn-primary">ส่งข้อมูล</button>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>
<?php render_footer(); ?>
