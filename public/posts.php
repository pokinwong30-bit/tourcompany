<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/guard.php';
require_backoffice();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';

$errors = [];
$info = '';

function require_password_confirm_if_needed() {
  // Director ข้ามการยืนยัน
  if (can('bypass_confirm')) return true;

  // Manager/Officer ต้องยืนยันด้วย "รหัสลับส่วนกลาง"
  $secret = $_POST['secret_confirm'] ?? '';
  if ($secret === '') return false;

  return verify_shared_secret($secret);
}

// Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
  require_capability('create');
  if (!require_password_confirm_if_needed()) $errors[] = 'ต้องยืนยันรหัสลับให้ถูกต้อง';
  $title = trim($_POST['title'] ?? '');
  $body  = trim($_POST['body'] ?? '');
  if (!$errors) {
    $stmt = $pdo->prepare("INSERT INTO posts (title, body, created_by) VALUES (?,?,?)");
    $stmt->execute([$title, $body, current_user()['id']]);
    $postId = $pdo->lastInsertId();
    $info = 'สร้างโพสต์สำเร็จ';

    // Audit log
    audit_log('post_create', 'posts', $postId, [
      'title' => $title,
      'by'    => current_user()['full_name'] ?? null,
    ]);
  }
}

// Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
  require_capability('edit');
  if (!require_password_confirm_if_needed()) $errors[] = 'ต้องยืนยันรหัสลับให้ถูกต้อง';
  $id = (int)($_POST['id'] ?? 0);
  $title = trim($_POST['title'] ?? '');
  $body  = trim($_POST['body'] ?? '');
  if (!$errors) {
    $stmt = $pdo->prepare("UPDATE posts SET title=?, body=? WHERE id=?");
    $stmt->execute([$title, $body, $id]);
    $info = 'แก้ไขโพสต์สำเร็จ';

    // Audit log
    audit_log('post_edit', 'posts', $id, [
      'title' => $title,
      'by'    => current_user()['full_name'] ?? null,
    ]);
  }
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
  require_capability('delete'); // Officer ไม่มีสิทธิ์
  if (!require_password_confirm_if_needed()) $errors[] = 'ต้องยืนยันรหัสลับให้ถูกต้อง';
  $id = (int)($_POST['id'] ?? 0);
  if (!$errors) {
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id=?");
    $stmt->execute([$id]);
    $info = 'ลบโพสต์สำเร็จ';

    // Audit log
    audit_log('post_delete', 'posts', $id, [
      'by' => current_user()['full_name'] ?? null,
    ]);
  }
}

$posts = $pdo->query("
  SELECT p.*, u.full_name
  FROM posts p
  JOIN users u ON u.id = p.created_by
  ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

render_header('Posts');
?>
<div class="row">
  <div class="col-lg-6">
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h5 class="mb-3">สร้างโพสต์ใหม่</h5>

        <?php if ($info): ?>
          <div class="alert alert-success"><?= htmlspecialchars($info) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
          </div>
        <?php endif; ?>

        <form method="post">
          <input type="hidden" name="action" value="create">
          <div class="mb-3">
            <label class="form-label">หัวข้อ</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">เนื้อหา</label>
            <textarea name="body" class="form-control" rows="4" required></textarea>
          </div>

          <?php if (!can('bypass_confirm')): ?>
            <div class="mb-3">
              <label class="form-label">รหัสลับสำหรับการยืนยัน</label>
              <input type="password" name="secret_confirm" class="form-control" placeholder="กรอกรหัสลับ" required>
              <div class="form-text">สำหรับ Manager/Officer เท่านั้น (Director ไม่ต้องกรอก)</div>
            </div>
          <?php endif; ?>

          <button class="btn btn-primary">บันทึก</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="mb-3">รายการโพสต์</h5>

        <?php foreach ($posts as $p): ?>
          <div class="border rounded p-3 mb-3 bg-white">
            <h6 class="mb-1"><?= htmlspecialchars($p['title']) ?></h6>
            <small class="text-muted">โดย <?= htmlspecialchars($p['full_name']) ?> • <?= htmlspecialchars($p['created_at']) ?></small>
            <p class="mt-2 mb-2"><?= nl2br(htmlspecialchars($p['body'])) ?></p>

            <details class="mb-2">
              <summary>แก้ไข</summary>
              <form method="post" class="mt-2">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <div class="mb-2">
                  <label class="form-label">หัวข้อ</label>
                  <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($p['title']) ?>" required>
                </div>
                <div class="mb-2">
                  <label class="form-label">เนื้อหา</label>
                  <textarea name="body" class="form-control" rows="3" required><?= htmlspecialchars($p['body']) ?></textarea>
                </div>

                <?php if (!can('bypass_confirm')): ?>
                  <div class="mb-2">
                    <input type="password" name="secret_confirm" placeholder="รหัสลับสำหรับการยืนยัน" class="form-control" required>
                  </div>
                <?php endif; ?>

                <button class="btn btn-outline-primary btn-sm">บันทึกการแก้ไข</button>
              </form>
            </details>

            <?php if (can('delete')): ?>
              <form method="post" onsubmit="return confirm('ยืนยันลบโพสต์นี้?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">

                <?php if (!can('bypass_confirm')): ?>
                  <div class="mb-2">
                    <input type="password" name="secret_confirm" placeholder="รหัสลับสำหรับการยืนยัน" class="form-control" required>
                  </div>
                <?php endif; ?>

                <button class="btn btn-danger btn-sm">ลบ</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

      </div>
    </div>
  </div>
</div>
<?php render_footer(); ?>
