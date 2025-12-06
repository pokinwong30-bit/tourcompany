<?php
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../includes/guard.php';
require_backoffice();

require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/csrf.php';
require_once __DIR__.'/../includes/audit.php';

$errors = [];
$info   = '';
$autoOpenGuideId = null;

// ---------------------- Pagination Config ----------------------
$ALLOWED_PER_PAGE = [10, 25, 50, 100];
$DEFAULT_PER_PAGE = 25;

// GET -> page, per_page
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : $DEFAULT_PER_PAGE;
if (!in_array($per_page, $ALLOWED_PER_PAGE, true)) {
  $per_page = $DEFAULT_PER_PAGE;
}
$offset = ($page - 1) * $per_page;

// helper
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function page_url($page, $per_page){
  $qs = ['page'=>$page, 'per_page'=>$per_page];
  return '?' . http_build_query($qs);
}

/* 1) ถ้ามี approve_token มาจากอีเมล: หาไกด์เพื่อเปิด modal อัตโนมัติ */
if (isset($_GET['approve_token'])) {
  $token = trim($_GET['approve_token']);
  if ($token !== '') {
    $stmt = $pdo->prepare("SELECT id FROM guides
      WHERE approval_token = ?
        AND status = 'pending'
        AND (approval_token_expires IS NULL OR approval_token_expires >= NOW())");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
      $autoOpenGuideId = (int)$row['id'];
    } else {
      $errors[] = 'ลิงก์อนุมัติไม่ถูกต้องหรือหมดอายุ';
    }
  }
}

/* 2) การอนุมัติ (Director เท่านั้น) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve') {
  csrf_validate();
  if (!can('bypass_confirm')) { http_response_code(403); echo "Forbidden"; exit; }

  $gid = (int)($_POST['guide_id'] ?? 0);

  // อัปเดตสถานะ
  $stmt = $pdo->prepare("UPDATE guides
                         SET status='approved',
                             approved_at=NOW(),
                             approved_by=?,
                             approval_token=NULL,
                             approval_token_expires=NULL
                         WHERE id=? AND status='pending'");
  $stmt->execute([ current_user()['id'], $gid ]);

  if ($stmt->rowCount() > 0) {
    $info = 'อนุมัติเรียบร้อย';
    // Audit
    audit_log('guide_approve', 'guides', $gid, [
      'by' => current_user()['full_name'] ?? null,
      'by_role' => user_role()
    ]);
  } else {
    $errors[] = 'ไม่สามารถอนุมัติได้ (อาจอนุมัติไปแล้วหรือไม่มีข้อมูล)';
  }
}

/* 3) นับจำนวนทั้งหมดเพื่อคำนวณหน้า */
$stmt = $pdo->query("SELECT COUNT(*) FROM guides");
$total_rows = (int)$stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_rows / $per_page));
if ($page > $total_pages) {
  $page = $total_pages;
  $offset = ($page - 1) * $per_page;
}

/* 4) ดึงรายการ guides เฉพาะหน้าปัจจุบัน */
$sql = "SELECT id, first_name, last_name, email, phone, alt_phone, line_id,
               address, idcard_path, status, approved_at, approved_by, created_at
        FROM guides
        ORDER BY id DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
$stmt->execute();
$guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 5) ถ้ามี $autoOpenGuideId แต่ไม่ได้อยู่ในหน้านี้ ให้ดึงแถวของมันมาเพื่อสร้าง modal เพิ่มต่างหาก */
$extraModalGuide = null;
if ($autoOpenGuideId) {
  $found = false;
  foreach ($guides as $g) { if ((int)$g['id'] === $autoOpenGuideId) { $found = true; break; } }
  if (!$found) {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, alt_phone, line_id,
                                  address, idcard_path, status, approved_at, approved_by, created_at
                           FROM guides WHERE id = ?");
    $stmt->execute([$autoOpenGuideId]);
    $extraModalGuide = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
  }
}

render_header('Guides');
?>
<div class="card shadow-sm">
  <div class="card-body">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
      <h4 class="mb-0">รายชื่อ Guides</h4>
      <div class="d-flex align-items-center gap-2">
        <form method="get" class="d-flex align-items-center gap-2">
          <label class="text-muted small">แสดงต่อหน้า</label>
          <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach ($ALLOWED_PER_PAGE as $opt): ?>
              <option value="<?= (int)$opt ?>" <?= $opt===$per_page?'selected':''; ?>><?= (int)$opt ?></option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="page" value="1">
        </form>
        <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>/guide_register.php" target="_blank">
          เปิดฟอร์มลงทะเบียน (สาธารณะ)
        </a>
      </div>
    </div>

    <div class="text-muted small mb-2">
      ทั้งหมด <?= number_format($total_rows) ?> รายการ • หน้า <?= number_format($page) ?>/<?= number_format($total_pages) ?>
    </div>

    <?php if ($info): ?>
      <div class="alert alert-success"><?= h($info) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e) echo '<li>'.h($e).'</li>'; ?></ul></div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:100px;">#</th>
            <th>ชื่อ-นามสกุล</th>
            <th>ติดต่อ</th>
            <th style="width:140px;">สถานะ</th>
            <th style="width:120px;">ดูรายละเอียด</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($guides as $g): ?>
            <tr>
              <td><?= (int)$g['id'] ?></td>
              <td><?= h($g['first_name'].' '.$g['last_name']) ?></td>
              <td class="small">
                <div>Email: <?= h($g['email']) ?></div>
                <div>Phone: <?= h($g['phone']) ?><?= $g['alt_phone'] ? ' / '.h($g['alt_phone']) : '' ?></div>
                <div>Line: <?= h($g['line_id'] ?: '-') ?></div>
              </td>
              <td>
                <?php if ($g['status'] === 'approved'): ?>
                  <span class="badge bg-success">Approved</span>
                <?php else: ?>
                  <span class="badge bg-warning text-dark">Pending</span>
                <?php endif; ?>
              </td>
              <td>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#guideModal<?= (int)$g['id'] ?>">ดู</button>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$guides): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีข้อมูล</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <?php
        $window = 2;
        $start  = max(1, $page - $window);
        $end    = min($total_pages, $page + $window);
        $show_first_ellipsis = ($start > 2);
        $show_last_ellipsis  = ($end < $total_pages - 1);
      ?>
      <nav aria-label="Guides pagination">
        <ul class="pagination justify-content-center">
          <li class="page-item <?= $page<=1?'disabled':'' ?>">
            <a class="page-link" href="<?= $page<=1?'#':h(page_url(1,$per_page)) ?>" aria-label="First">
              <span aria-hidden="true">&laquo;&laquo;</span>
            </a>
          </li>
          <li class="page-item <?= $page<=1?'disabled':'' ?>">
            <a class="page-link" href="<?= $page<=1?'#':h(page_url($page-1,$per_page)) ?>" aria-label="Previous">
              <span aria-hidden="true">&laquo;</span>
            </a>
          </li>

          <?php if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= h(page_url(1,$per_page)) ?>">1</a></li>
          <?php endif; ?>
          <?php if ($show_first_ellipsis): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
          <?php endif; ?>

          <?php for ($p = $start; $p <= $end; $p++): ?>
            <li class="page-item <?= $p===$page?'active':'' ?>">
              <a class="page-link" href="<?= h(page_url($p,$per_page)) ?>"><?= (int)$p ?></a>
            </li>
          <?php endfor; ?>

          <?php if ($show_last_ellipsis): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
          <?php endif; ?>
          <?php if ($end < $total_pages): ?>
            <li class="page-item"><a class="page-link" href="<?= h(page_url($total_pages,$per_page)) ?>"><?= (int)$total_pages ?></a></li>
          <?php endif; ?>

          <li class="page-item <?= $page>=$total_pages?'disabled':'' ?>">
            <a class="page-link" href="<?= $page>=$total_pages?'#':h(page_url($page+1,$per_page)) ?>" aria-label="Next">
              <span aria-hidden="true">&raquo;</span>
            </a>
          </li>
          <li class="page-item <?= $page>=$total_pages?'disabled':'' ?>">
            <a class="page-link" href="<?= $page>=$total_pages?'#':h(page_url($total_pages,$per_page)) ?>" aria-label="Last">
              <span aria-hidden="true">&raquo;&raquo;</span>
            </a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>

  </div>
</div>

<?php
// -------- Render modals for current page --------
foreach ($guides as $g):
  $gid = (int)$g['id'];
?>
<div class="modal fade" id="guideModal<?= $gid ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          รายละเอียด Guide • <?= h($g['first_name'].' '.$g['last_name']) ?>
          <?php if ($g['status'] === 'approved'): ?>
            <span class="badge bg-success ms-2">Approved</span>
          <?php else: ?>
            <span class="badge bg-warning text-dark ms-2">Pending</span>
          <?php endif; ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="mb-2"><strong>ชื่อ:</strong> <?= h($g['first_name']) ?></div>
            <div class="mb-2"><strong>นามสกุล:</strong> <?= h($g['last_name']) ?></div>
            <div class="mb-2"><strong>Email:</strong> <?= h($g['email']) ?></div>
            <div class="mb-2"><strong>เบอร์โทร:</strong> <?= h($g['phone']) ?></div>
            <div class="mb-2"><strong>เบอร์สำรอง:</strong> <?= h($g['alt_phone'] ?: '-') ?></div>
            <div class="mb-2"><strong>Line ID:</strong> <?= h($g['line_id'] ?: '-') ?></div>
            <div class="mb-2"><strong>ที่อยู่:</strong><br><?= nl2br(h($g['address'] ?: '-')) ?></div>
          </div>
          <div class="col-md-6">
            <div class="mb-2"><strong>รูปบัตรประชาชน:</strong></div>
            <?php if (!empty($g['idcard_path'])): ?>
              <img src="<?= h(rtrim(BASE_URL,'/').'../../'.ltrim($g['idcard_path'],'/')) ?>" alt="ID Card" class="img-fluid rounded border">
            <?php else: ?>
              <div class="text-muted small">ไม่ได้อัปโหลด</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <?php if ($g['status'] === 'pending' && can('bypass_confirm')): ?>
          <form method="post" class="ms-auto">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="guide_id" value="<?= $gid ?>">
            <button class="btn btn-success">อนุมัติ</button>
          </form>
        <?php else: ?>
          <button class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<?php
// -------- Render "extra modal" กรณีมี approve_token แต่ไม่อยู่ในหน้าปัจจุบัน --------
if ($extraModalGuide):
  $g = $extraModalGuide;
  $gid = (int)$g['id'];
?>
<div class="modal fade" id="guideModal<?= $gid ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          รายละเอียด Guide • <?= h($g['first_name'].' '.$g['last_name']) ?>
          <?php if ($g['status'] === 'approved'): ?>
            <span class="badge bg-success ms-2">Approved</span>
          <?php else: ?>
            <span class="badge bg-warning text-dark ms-2">Pending</span>
          <?php endif; ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="mb-2"><strong>ชื่อ:</strong> <?= h($g['first_name']) ?></div>
            <div class="mb-2"><strong>นามสกุล:</strong> <?= h($g['last_name']) ?></div>
            <div class="mb-2"><strong>Email:</strong> <?= h($g['email']) ?></div>
            <div class="mb-2"><strong>เบอร์โทร:</strong> <?= h($g['phone']) ?></div>
            <div class="mb-2"><strong>เบอร์สำรอง:</strong> <?= h($g['alt_phone'] ?: '-') ?></div>
            <div class="mb-2"><strong>Line ID:</strong> <?= h($g['line_id'] ?: '-') ?></div>
            <div class="mb-2"><strong>ที่อยู่:</strong><br><?= nl2br(h($g['address'] ?: '-')) ?></div>
          </div>
          <div class="col-md-6">
            <div class="mb-2"><strong>รูปบัตรประชาชน:</strong></div>
            <?php if (!empty($g['idcard_path'])): ?>
              <img src="<?= h(rtrim(BASE_URL,'/').'/'.ltrim($g['idcard_path'],'/')) ?>" alt="ID Card" class="img-fluid rounded border">
            <?php else: ?>
              <div class="text-muted small">ไม่ได้อัปโหลด</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <?php if ($g['status'] === 'pending' && can('bypass_confirm')): ?>
          <form method="post" class="ms-auto">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="guide_id" value="<?= $gid ?>">
            <button class="btn btn-success">อนุมัติ</button>
          </form>
        <?php else: ?>
          <button class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($autoOpenGuideId): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
  var m = document.getElementById('guideModal<?= (int)$autoOpenGuideId ?>');
  if (m) {
    var modal = new bootstrap.Modal(m);
    modal.show();
  }
});
</script>
<?php endif; ?>

<?php render_footer(); ?>
