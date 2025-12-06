<?php
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../includes/guard.php';
require_backoffice();

// อนุญาตเฉพาะ Director
if (!can('bypass_confirm')) { http_response_code(403); echo "Forbidden"; exit; }

require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/audit.php';

$roles = ['director','manager','officer','non-admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $uid = (int)($_POST['user_id'] ?? 0);
  $role = $_POST['role'] ?? 'non-admin';

  if (in_array($role, $roles, true)) {
    // ดึง role เดิมก่อนอัปเดต
    $old = $pdo->prepare("SELECT role, full_name, email FROM users WHERE id=?");
    $old->execute([$uid]);
    $oldRow = $old->fetch(PDO::FETCH_ASSOC);
    $oldRole = $oldRow['role'] ?? null;

    // อัปเดต role
    $stmt = $pdo->prepare("UPDATE users SET role=? WHERE id=?");
    $stmt->execute([$role, $uid]);

    // บันทึก Audit Log
    audit_log('change_role', 'users', $uid, [
      'old_role'     => $oldRole,
      'new_role'     => $role,
      'target_name'  => $oldRow['full_name'] ?? null,
      'target_email' => $oldRow['email'] ?? null,
    ]);
  }
  header('Location: users.php');
  exit;
}

$users = $pdo->query("SELECT id, email, full_name, role, created_at FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

render_header('Manage Users');
?>
<div class="card shadow-sm">
  <div class="card-body">
    <h4 class="mb-3">กำหนดระดับผู้ใช้ (Director เท่านั้น)</h4>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead><tr><th>ID</th><th>ชื่อ</th><th>อีเมล</th><th>บทบาท</th><th>จัดการ</th></tr></thead>
        <tbody>
          <?php foreach($users as $u): ?>
            <tr>
              <td><?= $u['id'] ?></td>
              <td><?= htmlspecialchars($u['full_name']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><span class="badge bg-secondary"><?= htmlspecialchars($u['role']) ?></span></td>
              <td>
                <form method="post" class="d-flex gap-2">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <select name="role" class="form-select form-select-sm" style="max-width:200px">
                    <?php foreach($roles as $r): ?>
                      <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= $r ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-sm btn-primary">อัปเดต</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="text-muted mt-2">
      ผู้ใช้ใหม่ทุกคนจะเป็น <code>non-admin</code> โดยอัตโนมัติ ต้องให้ Director มาเปลี่ยนสิทธิ์ที่นี่
    </p>
  </div>
</div>
<?php render_footer(); ?>
