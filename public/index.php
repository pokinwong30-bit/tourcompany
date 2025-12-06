<?php
require_once __DIR__.'/../includes/layout.php';
render_header('Welcome');
?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">เริ่มต้นใช้งานระบบหลังบ้าน</h5>
        <a class="btn btn-primary w-100 mb-2" href="login.php">เข้าสู่ระบบ (Login)</a>
        <a class="btn btn-outline-secondary w-100" href="register.php">ลงทะเบียน (Register)</a>
      </div>
    </div>
  </div>
</div>
<?php render_footer(); ?>
