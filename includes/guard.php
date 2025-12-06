<?php
require_once __DIR__ . '/auth.php';

function require_login() {
  if (!current_user()) {
    header('Location: '.BASE_URL.'/login.php');
    exit;
  }
}

function require_backoffice() {
    require_login();
    if (!can('view_backoffice')) {
      http_response_code(403);
  
      // โหลด layout สำหรับตกแต่งด้วย Bootstrap
      require_once __DIR__ . '/layout.php';
      render_header('รอการยืนยันสิทธิ์');
  
      ?>
      <div class="row justify-content-center mt-5">
        <div class="col-md-6">
          <div class="card shadow-sm border-warning">
            <div class="card-body text-center">
              <div class="mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#ffc107" class="bi bi-hourglass-split" viewBox="0 0 16 16">
                  <path d="M2.5 15a.5.5 0 0 1-.5-.5V14h12v.5a.5.5 0 0 1-.5.5h-11Z"/>
                  <path d="M14 1a1 1 0 0 1 1 1v1c0 .356-.154.676-.4.9A4.978 4.978 0 0 1 10 8a4.978 4.978 0 0 1 4.6 4.1c.246.224.4.544.4.9v1a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-1c0-.356.154-.676.4-.9A4.978 4.978 0 0 1 6 8a4.978 4.978 0 0 1-4.6-4.1A1.006 1.006 0 0 1 1 3V2a1 1 0 0 1 1-1h12ZM2 2v1c0 .276.224.5.5.5h11a.5.5 0 0 0 .5-.5V2H2Zm0 12h12v-1a.5.5 0 0 0-.5-.5h-11a.5.5 0 0 0-.5.5v1Z"/>
                </svg>
              </div>
              <h4 class="text-warning">คุณได้เป็น Admin เรียบร้อยแล้ว</h4>
              <p class="text-muted mb-4">กรุณารอ Director ทำการยืนยันสิทธิ์ก่อนเข้าใช้งานระบบหลังบ้าน</p>
              <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-secondary">
                ออกจากระบบ
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php
  
      render_footer();
      exit;
    }
  }
  

// ใช้เช็คสิทธิ์รายหน้า เช่น require_capability('delete')
function require_capability($cap) {
  require_backoffice();
  if (!can($cap)) {
    http_response_code(403);
    echo "Forbidden";
    exit;
  }
}
