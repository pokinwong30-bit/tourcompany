<?php
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../includes/auth.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $full_name = trim($_POST['full_name'] ?? '');
  $password = $_POST['password'] ?? '';
  $password2 = $_POST['password2'] ?? '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'อีเมลไม่ถูกต้อง';
  if ($full_name === '') $errors[] = 'กรุณากรอกชื่อ';
  if (strlen($password) < 6) $errors[] = 'รหัสผ่านอย่างน้อย 6 ตัวอักษร';
  if ($password !== $password2) $errors[] = 'รหัสผ่านไม่ตรงกัน';

  if (!$errors) {
    if (find_user_by_email($email)) {
      $errors[] = 'อีเมลนี้ถูกใช้แล้ว';
    } else {
      register_user($email, $password, $full_name); // role = non-admin
      header('Location: login.php?registered=1');
      exit;
    }
  }
}

render_header('Register');
?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="mb-3">ลงทะเบียน</h4>
        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
          </div>
        <?php endif; ?>
        <form method="post" class="needs-validation" novalidate>
          <div class="mb-3">
            <label class="form-label">อีเมล</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">ชื่อ-นามสกุล</label>
            <input type="text" name="full_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">รหัสผ่าน</label>
            <input type="password" name="password" class="form-control" required minlength="6">
          </div>
          <div class="mb-3">
            <label class="form-label">ยืนยันรหัสผ่าน</label>
            <input type="password" name="password2" class="form-control" required minlength="6">
          </div>
          <button class="btn btn-primary w-100">สมัครสมาชิก</button>
        </form>
        <hr>
        <a href="login.php">มีบัญชีแล้ว? เข้าสู่ระบบ</a>
      </div>
    </div>
  </div>
</div>
<?php render_footer(); ?>
