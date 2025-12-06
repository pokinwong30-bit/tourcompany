<?php
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../includes/auth.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  if (!login_user($email, $password)) {
    $errors[] = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
  } else {
    header('Location: dashboard.php');
    exit;
  }
}

render_header('Login');
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="mb-3">เข้าสู่ระบบ</h4>
        <?php if (isset($_GET['registered'])): ?>
          <div class="alert alert-success">สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ</div>
        <?php endif; ?>
        <?php if ($errors): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($errors[0]) ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">อีเมล</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">รหัสผ่าน</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button class="btn btn-primary w-100">เข้าสู่ระบบ</button>
        </form>
        <hr>
        <a href="register.php">ยังไม่มีบัญชี? สมัครสมาชิก</a>
      </div>
    </div>
  </div>
</div>
<?php render_footer(); ?>
