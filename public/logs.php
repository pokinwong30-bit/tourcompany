<?php
// public/logs.php
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../includes/guard.php';
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/audit.php';

require_backoffice();
$role = strtolower(trim(user_role()));
if (!in_array($role, ['director','manager'], true)) {
  http_response_code(403);
  echo "Forbidden";
  exit;
}

/* ---------- รับพารามิเตอร์ค้นหา/กรอง ---------- */
$action   = trim($_GET['action'] ?? '');
$q        = trim($_GET['q'] ?? '');

/* ---------- ตั้งค่า Pagination ---------- */
$perPageAllowed = [20, 50, 100];
$perPage = (int)($_GET['per_page'] ?? 20);
if (!in_array($perPage, $perPageAllowed, true)) $perPage = 20;

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$where = [];
$params = [];

if ($action !== '') {
  $where[] = "a.action = :action";
  $params[':action'] = $action;
}
if ($q !== '') {
  $where[] = "(a.target_table LIKE :kw OR a.target_id LIKE :kw OR u.full_name LIKE :kw OR u.email LIKE :kw OR a.details LIKE :kw)";
  $params[':kw'] = '%'.$q.'%';
}

/* ---------- นับจำนวนรวม ---------- */
$countSql = "
  SELECT COUNT(a.id) AS total_rows
  FROM audit_logs a
  LEFT JOIN users u ON u.id = a.actor_id
";
if ($where) $countSql .= " WHERE ".implode(' AND ', $where);

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

/* ---------- ดึงข้อมูลตามหน้า ---------- */
$listSql = "
  SELECT a.*, u.full_name AS actor_name, u.email AS actor_email
  FROM audit_logs a
  LEFT JOIN users u ON u.id = a.actor_id
";
if ($where) $listSql .= " WHERE ".implode(' AND ', $where);
$listSql .= " ORDER BY a.id DESC LIMIT :limit OFFSET :offset";

$listStmt = $pdo->prepare($listSql);
foreach ($params as $k => $v) $listStmt->bindValue($k, $v);
$listStmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$listStmt->execute();
$rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);
$rows = array_map('audit_format_row', $rows);

/* ---------- helper ทำลิงก์เพจ โดยคง action/q/per_page ---------- */
function build_query(array $extra = []) {
  $base = [
    'action'   => $_GET['action'] ?? '',
    'q'        => $_GET['q'] ?? '',
    'per_page' => $_GET['per_page'] ?? 20,
  ];
  $query = http_build_query(array_merge($base, $extra));
  return '?'.$query;
}

render_header('Audit Logs');
?>
<div class="card shadow-sm">
  <div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <h4 class="mb-0">Audit Logs</h4>
      <form class="d-flex flex-wrap gap-2" method="get">
        <select name="action" class="form-select form-select-sm" style="max-width:200px">
          <option value="">ทุกเหตุการณ์</option>
          <?php
            $options = ['change_role','post_create','post_edit','post_delete','login_success','login_failed'];
            foreach ($options as $opt) {
              $sel = $action === $opt ? 'selected' : '';
              echo "<option value=\"$opt\" $sel>$opt</option>";
            }
          ?>
        </select>
        <input type="text" name="q" class="form-control form-control-sm" placeholder="ค้นหา..." value="<?= htmlspecialchars($q) ?>" style="max-width:260px">
        <select name="per_page" class="form-select form-select-sm" style="max-width:120px">
          <?php foreach ([20,50,100] as $pp): ?>
            <option value="<?= $pp ?>" <?= $perPage===$pp?'selected':'' ?>><?= $pp ?>/หน้า</option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-primary">ใช้ตัวกรอง</button>
      </form>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="small text-muted">
        <?php
          $from = $totalRows ? ($offset + 1) : 0;
          $to   = $totalRows ? min($offset + $perPage, $totalRows) : 0;
        ?>
        แสดง <?= $from ?>–<?= $to ?> จากทั้งหมด <?= number_format($totalRows) ?> รายการ
      </div>

      <!-- Pagination (บน) -->
      <?php if ($totalPages > 1): ?>
      <nav aria-label="Logs pagination top">
        <ul class="pagination pagination-sm mb-0">
          <?php
            $prevDisabled = $page <= 1 ? ' disabled' : '';
            $nextDisabled = $page >= $totalPages ? ' disabled' : '';
          ?>
          <li class="page-item<?= $prevDisabled ?>">
            <a class="page-link" href="<?= $page>1 ? build_query(['page'=>$page-1]) : '#' ?>" aria-label="Previous">
              <span aria-hidden="true">&laquo;</span>
            </a>
          </li>
          <?php
            // สร้างเลขหน้า (ทำ window รอบ ๆ หน้าปัจจุบัน)
            $window = 2; // จำนวนหน้าซ้าย/ขวา
            $start = max(1, $page - $window);
            $end   = min($totalPages, $page + $window);

            if ($start > 1) {
              echo '<li class="page-item"><a class="page-link" href="'.build_query(['page'=>1]).'">1</a></li>';
              if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }

            for ($i=$start; $i<=$end; $i++) {
              $active = $i === $page ? ' active' : '';
              echo '<li class="page-item'.$active.'"><a class="page-link" href="'.build_query(['page'=>$i]).'">'.$i.'</a></li>';
            }

            if ($end < $totalPages) {
              if ($end < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
              echo '<li class="page-item"><a class="page-link" href="'.build_query(['page'=>$totalPages]).'">'.$totalPages.'</a></li>';
            }
          ?>
          <li class="page-item<?= $nextDisabled ?>">
            <a class="page-link" href="<?= $page<$totalPages ? build_query(['page'=>$page+1]) : '#' ?>" aria-label="Next">
              <span aria-hidden="true">&raquo;</span>
            </a>
          </li>
        </ul>
      </nav>
      <?php endif; ?>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>วันที่/เวลา</th>
            <th>Action</th>
            <th>Actor</th>
            <th>Target</th>
            <th>รายละเอียด</th>
            <th>IP</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">ยังไม่มีบันทึก</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><span class="text-muted small"><?= htmlspecialchars($r['created_at']) ?></span></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($r['action']) ?></span></td>
                <td>
                  <?php if (!empty($r['actor_name'])): ?>
                    <div class="fw-semibold"><?= htmlspecialchars($r['actor_name']) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($r['actor_email']) ?></div>
                  <?php else: ?>
                    <span class="text-muted">system/guest</span>
                  <?php endif; ?>
                  <div class="small"><span class="badge rounded-pill bg-light text-dark"><?= htmlspecialchars($r['actor_role'] ?? '') ?></span></div>
                </td>
                <td>
                  <div class="small text-uppercase text-muted"><?= htmlspecialchars($r['target_table'] ?? '-') ?></div>
                  <div class="fw-semibold"><?= htmlspecialchars($r['target_id'] ?? '-') ?></div>
                </td>
                <td style="max-width:360px;">
                  <?php if (!empty($r['details_array'])): ?>
                    <pre class="small bg-light p-2 rounded mb-0" style="white-space:pre-wrap;"><?= htmlspecialchars(json_encode($r['details_array'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) ?></pre>
                  <?php else: ?>
                    <span class="text-muted small">-</span>
                  <?php endif; ?>
                </td>
                <td class="small"><?= htmlspecialchars($r['ip_human'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination (ล่าง) -->
    <?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-2">
      <div class="small text-muted">
        หน้า <?= $page ?> / <?= $totalPages ?> • <?= $perPage ?> แถวต่อหน้า
      </div>
      <nav aria-label="Logs pagination bottom">
        <ul class="pagination pagination-sm mb-0">
          <?php
            $prevDisabled = $page <= 1 ? ' disabled' : '';
            $nextDisabled = $page >= $totalPages ? ' disabled' : '';
          ?>
          <li class="page-item<?= $prevDisabled ?>">
            <a class="page-link" href="<?= $page>1 ? build_query(['page'=>$page-1]) : '#' ?>" aria-label="Previous">
              <span aria-hidden="true">&laquo;</span>
            </a>
          </li>
          <?php
            $window = 2;
            $start = max(1, $page - $window);
            $end   = min($totalPages, $page + $window);

            if ($start > 1) {
              echo '<li class="page-item"><a class="page-link" href="'.build_query(['page'=>1]).'">1</a></li>';
              if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }

            for ($i=$start; $i<=$end; $i++) {
              $active = $i === $page ? ' active' : '';
              echo '<li class="page-item'.$active.'"><a class="page-link" href="'.build_query(['page'=>$i]).'">'.$i.'</a></li>';
            }

            if ($end < $totalPages) {
              if ($end < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
              echo '<li class="page-item"><a class="page-link" href="'.build_query(['page'=>$totalPages]).'">'.$totalPages.'</a></li>';
            }
          ?>
          <li class="page-item<?= $nextDisabled ?>">
            <a class="page-link" href="<?= $page<$totalPages ? build_query(['page'=>$page+1]) : '#' ?>" aria-label="Next">
              <span aria-hidden="true">&raquo;</span>
            </a>
          </li>
        </ul>
      </nav>
    </div>
    <?php endif; ?>

  </div>
</div>
<?php render_footer(); ?>
