<?php
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../includes/guard.php';
require_backoffice();
require_once __DIR__.'/../includes/db.php'; // ต้องมีเพื่อใช้ $pdo

$user = current_user();
$role = strtolower(user_role());

// ===== Helper: แปลงวันที่เป็น "2 ตุลาคม 2568" =====
function thai_date(string $dateStr): string {
  if (!$dateStr) return '';
  try {
    if (class_exists('IntlDateFormatter')) {
      $dt = new DateTime($dateStr, new DateTimeZone('Asia/Bangkok'));
      $fmt = new IntlDateFormatter(
        'th_TH@calendar=buddhist;numbers=latn',
        IntlDateFormatter::LONG,
        IntlDateFormatter::NONE,
        'Asia/Bangkok',
        IntlDateFormatter::GREGORIAN,
        'd MMMM y'
      );
      $out = $fmt->format($dt);
      if ($out !== false) return $out;
    }
  } catch (Throwable $e) {}
  $ts = strtotime($dateStr);
  if ($ts === false) return $dateStr;
  $months = [1=>'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
  $d = (int)date('j', $ts);
  $m = (int)date('n', $ts);
  $y = (int)date('Y', $ts) + 543;
  return $d.' '.$months[$m].' '.$y;
}

// นับจำนวนไกด์ที่ยังรออนุมัติ (pending) — เฉพาะ director เห็นการ์ดนี้
$pendingGuides = 0;
if ($role === 'director') {
  $stmt = $pdo->query("SELECT COUNT(*) FROM guides WHERE status='pending'");
  $pendingGuides = (int)$stmt->fetchColumn();
}

// นับจำนวนทัวร์ทั้งหมด
$totalTours = 0;
$stmt = $pdo->query("SELECT COUNT(*) FROM tours");
$totalTours = (int)$stmt->fetchColumn();

/* ===== รายการรอบเดินทางที่เต็ม (capacity <= 0) ===== */
$fullRows = [];
try {
  $q = $pdo->query("
      SELECT d.id AS depart_id, d.start_date, d.end_date, d.capacity,
             t.id AS tour_id, t.code, t.name
      FROM tour_departures d
      INNER JOIN tours t ON t.id = d.tour_id
      WHERE d.capacity <= 0
      ORDER BY d.start_date ASC, t.code ASC, d.id ASC
  ");
  $fullRows = $q->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $fullRows = [];
}

render_header('Dashboard');
?>

<div class="row g-3">
  <!-- Header card -->
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <h4 class="mb-1">แดชบอร์ด</h4>
          <p class="text-muted mb-0">
            สถานะ:
            <span class="badge bg-secondary"><?= htmlspecialchars($user['role']) ?></span>
          </p>
        </div>
        <div class="d-flex align-items-center gap-2">
          <?php if (can('delete')): ?>
            <span class="badge bg-info text-dark">คุณมีสิทธิ์ลบข้อมูล</span>
          <?php endif; ?>
          <?php if (can('bypass_confirm')): ?>
            <span class="badge bg-success">Director: ข้ามการยืนยันรหัสผ่าน</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick links -->
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="mb-3">ลิงก์ด่วน</h5>
        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-outline-primary" href="users.php">จัดการ Admin</a>
          <a class="btn btn-outline-primary" href="guides.php">จัดการไกด์</a>
          <a class="btn btn-outline-primary" href="tours_create.php">จัดการทัวร์</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Metrics cards -->
  <div class="col-12">
    <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3">
    <?php
    // ==== สรุปการจองสำหรับการ์ดแรก ====
    $todayBookings   = 0;
    $pendingBookings = 0;
    $latestBooking   = null;

    try {
      $todayBookings   = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at)=CURRENT_DATE")->fetchColumn();
      $pendingBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
      $st = $pdo->query("SELECT id, ref_code, full_name, created_at FROM bookings ORDER BY created_at DESC LIMIT 1");
      $latestBooking = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {}
    ?>
     <!-- Bookings summary (การ์ดแรก) -->
     <div class="col">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <h5 class="card-title mb-0">การจอง</h5>
              <small class="text-muted">Bookings summary</small>
            </div>
            <span class="badge bg-<?= $pendingBookings>0 ? 'warning text-dark' : 'success' ?>">
              <?= $pendingBookings>0 ? ('Pending '.number_format($pendingBookings)) : 'No pending' ?>
            </span>
          </div>
          <div class="d-flex gap-3 my-2">
            <div>
              <div class="small text-muted">วันนี้</div>
              <div class="h4 mb-0"><?= number_format($todayBookings) ?></div>
            </div>
            <div>
              <div class="small text-muted">ทั้งหมด</div>
              <div class="h4 mb-0"><?= number_format((int)($pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn())) ?></div>
            </div>
          </div>
          <?php if ($latestBooking): ?>
            <div class="mt-2 small">
              ล่าสุด: <strong><?= htmlspecialchars($latestBooking['ref_code']) ?></strong>
              <span class="text-muted">โดย</span> <?= htmlspecialchars($latestBooking['full_name'] ?: '-') ?>
            </div>
          <?php endif; ?>
          <div class="mt-auto d-grid">
            <a href="<?= BASE_URL ?>/bookings.php" class="btn btn-primary">ดูรายการจอง</a>
          </div>
        </div>
      </div>
    </div>

      <?php if ($role === 'director'): ?>
      <!-- Pending Guides -->
      <div class="col">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <h5 class="card-title mb-0">ไกด์ใหม่รออนุมัติ</h5>
                <small class="text-muted">Pending guides</small>
              </div>
              <span class="badge rounded-pill <?= $pendingGuides>0 ? 'bg-warning text-dark' : 'bg-success' ?>">
                <?= $pendingGuides>0 ? 'Pending' : 'No queue' ?>
              </span>
            </div>
            <div class="display-5 fw-semibold my-2"><?= number_format($pendingGuides) ?></div>
            <div class="mt-auto d-grid">
              <a href="<?= BASE_URL ?>/guides.php" class="btn btn-primary">จัดการไกด์</a>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Total Tours -->
      <div class="col">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <h5 class="card-title mb-0">จำนวนทัวร์ทั้งหมด</h5>
                <small class="text-muted">Total tours</small>
              </div>
              <span class="badge rounded-pill <?= $totalTours>0 ? 'bg-primary' : 'bg-secondary' ?>">
                <?= $totalTours>0 ? 'Active' : 'Empty' ?>
              </span>
            </div>
            <div class="display-5 fw-semibold my-2"><?= number_format($totalTours) ?></div>
            <div class="mt-auto d-grid">
              <a href="<?= BASE_URL ?>/tours_create.php" class="btn btn-primary">จัดการทัวร์</a>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

<?php
/* ===== Top 5 Best-Selling Tours + 60-day booking trend (ยึด bookings.tour_code) ===== */
$topTours  = [];
$trendDays = 60; // จำนวนวันย้อนหลัง
$labels    = [];
$datasets  = [];

try {
  // 1) Top 5 จาก tour_code (ไม่นับยกเลิก/void/rejected)
  $sqlTop = "
    SELECT b.tour_code AS code,
           COUNT(b.id)  AS total_bookings
    FROM bookings b
    WHERE (b.status IS NULL OR b.status NOT IN ('cancelled','rejected','void'))
      AND b.tour_code IS NOT NULL AND b.tour_code <> ''
    GROUP BY b.tour_code
    ORDER BY total_bookings DESC
    LIMIT 5
  ";
  $topTours = $pdo->query($sqlTop)->fetchAll(PDO::FETCH_ASSOC);

  if (!empty($topTours)) {
    $topCodes = array_column($topTours, 'code');

    // 2) เตรียม labels รายวันย้อนหลัง
    $tz = new DateTimeZone('Asia/Bangkok');
    $today = new DateTime('today', $tz);
    $start = (clone $today)->modify('-'.($trendDays-1).' days');

    $cursor = clone $start;
    while ($cursor <= $today) {
      $labels[] = $cursor->format('Y-m-d');
      $cursor->modify('+1 day');
    }

    // 3) ดึง booking ต่อวันต่อโปรแกรม
    $inPlaceholders = implode(',', array_fill(0, count($topCodes), '?'));
    $sqlTrend = "
      SELECT b.tour_code AS code,
             DATE(b.created_at) AS d,
             COUNT(*) AS c
      FROM bookings b
      WHERE b.tour_code IN ($inPlaceholders)
        AND b.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL {$trendDays} DAY)
        AND (b.status IS NULL OR b.status NOT IN ('cancelled','rejected','void'))
      GROUP BY b.tour_code, d
      ORDER BY d ASC
    ";
    $st = $pdo->prepare($sqlTrend);
    $st->execute($topCodes);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    // 4) map [tour_code][date] => count
    $map = [];
    foreach ($rows as $r) {
      $code = (string)$r['code'];
      $d    = $r['d'];
      $c    = (int)$r['c'];
      $map[$code][$d] = $c;
    }

    // 5) datasets
    foreach ($topTours as $i => $t) {
      $code = (string)$t['code'];
      $series = [];
      foreach ($labels as $d) {
        $series[] = isset($map[$code][$d]) ? (int)$map[$code][$d] : 0;
      }
      $datasets[] = [
        'label' => $code,
        'data'  => $series,
        'tension' => 0.25,
        'borderWidth' => 2,
        'pointRadius' => 0
      ];
    }
  }
} catch (Throwable $e) {
  // จะบันทึก log ก็ได้
}
?>

<?php if (!empty($topTours)): ?>
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="mb-0">ทัวร์ขายดีที่สุด 5 อันดับ</h5>
            <small class="text-muted">ช่วงเวลาการจองย้อนหลัง <?= (int)$trendDays ?> วัน</small>
          </div>
          <span class="badge bg-primary">Top 5</span>
        </div>

        <div class="row g-4 align-items-start">
          <!-- ตาราง Top 5 -->
          <div class="col-12 col-lg-4">
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th style="width:55px">อันดับ</th>
                    <th style="min-width:160px">โปรแกรม (tour_code)</th>
                    <th class="text-end" style="min-width:90px">จองรวม</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($topTours as $i => $t): ?>
                    <tr>
                      <td><span class="badge bg-dark"><?= $i+1 ?></span></td>
                      <td class="text-truncate" style="max-width:260px"><?= htmlspecialchars($t['code'] ?: '-') ?></td>
                      <td class="text-end fw-semibold"><?= number_format((int)$t['total_bookings']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- กราฟเส้น -->
          <div class="col-12 col-lg-8">
            <div style="height: 320px">
              <canvas id="topToursTrend"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="mb-1">ทัวร์ขายดีที่สุด 5 อันดับ</h5>
        <small class="text-muted">ยังไม่มีข้อมูลการจองเพียงพอ</small>
      </div>
    </div>
  </div>
<?php endif; ?>

  <!-- การ์ด "รอบเดินทางที่เต็ม" -->
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div>
            <h5 class="mb-0">รอบเดินทางที่จองเต็ม</h5>
            <small class="text-muted">Capacity = 0</small>
          </div>
          <?php if (!empty($fullRows)): ?>
            <span class="badge bg-warning text-dark">พบ <?= number_format(count($fullRows)) ?> รอบ</span>
          <?php else: ?>
            <span class="badge bg-success">ไม่มีรอบที่เต็ม</span>
          <?php endif; ?>
        </div>

        <?php if (!empty($fullRows)): ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th style="min-width:140px">รหัสทัวร์</th>
                  <th>ชื่อโปรแกรม</th>
                  <th style="min-width:260px">ช่วงวัน</th>
                  <th style="min-width:120px">สถานะ</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($fullRows as $r): ?>
                  <tr>
                    <td><?= htmlspecialchars($r['code']) ?></td>
                    <td><?= htmlspecialchars($r['name'] ?: '-') ?></td>
                    <td>
                      <?php
                        $sd = $r['start_date'] ? thai_date($r['start_date']) : '-';
                        $ed = $r['end_date']   ? thai_date($r['end_date'])   : '-';
                        echo htmlspecialchars($sd . ' — ' . $ed);
                      ?>
                    </td>
                    <td><span class="badge bg-warning text-dark">เต็ม</span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-muted">ยังไม่พบรอบเดินทางที่เต็ม</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<?php /* ====== สคริปต์ก่อน footer ====== */ ?>
<!-- Chart.js (ไม่ใส่ integrity เพื่อเลี่ยงบล็อค SRI mismatch) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" crossorigin="anonymous"></script>

<script>
(function(){
  <?php if (!empty($topTours)): ?>
  const labels   = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
  const datasets = <?= json_encode($datasets, JSON_UNESCAPED_UNICODE) ?>;

  const el = document.getElementById('topToursTrend');
  if (el && window.Chart) {
    new Chart(el, {
      type: 'line',
      data: { labels, datasets },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { position: 'top' },
          tooltip: {
            callbacks: {
              title: items => {
                if (!items?.length) return '';
                const d = items[0].label; // 'YYYY-MM-DD'
                const [y,m,dd]=d.split('-');
                const months = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
                const mm = parseInt(m,10);
                const by = parseInt(y,10)+543;
                return `${parseInt(dd,10)} ${months[mm]} ${by}`;
              }
            }
          }
        },
        scales: {
          x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
          y: { beginAtZero: true, ticks: { precision: 0 } }
        },
        elements: { line: { borderWidth: 2 }, point: { radius: 0, hitRadius: 6 } }
      }
    });
  } else {
    console.warn('Chart.js not loaded or canvas missing.');
  }
  <?php endif; ?>
})();
</script>

<?php render_footer(); ?>
