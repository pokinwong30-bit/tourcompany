<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// helper escape
if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
?>

<?php
// tour.php (Public, prefers tour_code, robust mapping, simple tables)

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/public_layout.php';

$BASE  = public_base_url();
$DEBUG = isset($_GET['debug']) && $_GET['debug'] == '1';

// ---------- Params ----------
$code = isset($_GET['code']) ? trim((string)$_GET['code']) : '';
$id   = isset($_GET['id'])   ? (int)$_GET['id'] : 0;
if ($code === '' && $id <= 0) {
  http_response_code(404);
  exit('Not found');
}

// ---------- Helpers ----------
$pick = function (array $row, array $keys, $default = null) {
  foreach ($keys as $k) if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') return $row[$k];
  return $default;
};
$dbg = []; // debug info holder

// ====== Cross-schema (ตั้ง ENV ถ้าตารางอยู่ต่าง DB): DB_SCHEMA_TOURS, DB_SCHEMA_AUX ======
$SCHEMA_TOURS = getenv('DB_SCHEMA_TOURS') ?: '';
$SCHEMA_AUX   = getenv('DB_SCHEMA_AUX')   ?: $SCHEMA_TOURS;

$tbl = function (string $schema, string $name): string {
  return ($schema ? ("`" . $schema . "`.") : "") . "`" . $name . "`";
};
$T_TOURS       = $tbl($SCHEMA_TOURS, 'tours');
$T_COUNTRIES   = $tbl($SCHEMA_TOURS, 'countries');
$T_TOUR_DAYS   = $tbl($SCHEMA_AUX,   'tour_days');
$T_DEPARTURES  = $tbl($SCHEMA_AUX,   'tour_departures');

// ตรวจว่าตารางมีคอลัมน์นี้ไหม (เร็ว/ปลอดภัย)
$table_has_col = function (PDO $pdo, string $table, string $col): bool {
  try {
    $pdo->query("SELECT `$col` FROM $table LIMIT 0");
    return true;
  } catch (Throwable $e) {
    return false;
  }
};

// ---------- Fetch main tour (prefer code/id, with fallbacks) ----------
$tour = null;
$bind = [];
$conds = [];
if ($code !== '') {
  $conds[] = 't.code = :code';
  $bind[':code'] = $code;
}
if ($id > 0) {
  $conds[] = 't.id   = :id';
  $bind[':id']   = $id;
}
$where = '(' . implode(' OR ', $conds) . ')';

try {
  $stmt = $pdo->prepare("
    SELECT t.*, COALESCE(c.name_th, c.name_en) AS country_name
    FROM $T_TOURS t
    LEFT JOIN $T_COUNTRIES c ON c.id = t.country_id
    WHERE $where AND t.is_on_sale = 1
    LIMIT 1
  ");
  $stmt->execute($bind);
  $tour = $stmt->fetch(PDO::FETCH_ASSOC);
  $dbg['tour.query'] = 'tours+countries+is_on_sale';
} catch (Throwable $e1) {
  try {
    $stmt = $pdo->prepare("
      SELECT t.*, COALESCE(c.name_th, c.name_en) AS country_name
      FROM $T_TOURS t
      LEFT JOIN $T_COUNTRIES c ON c.id = t.country_id
      WHERE $where
      LIMIT 1
    ");
    $stmt->execute($bind);
    $tour = $stmt->fetch(PDO::FETCH_ASSOC);
    $dbg['tour.query'] = 'tours+countries';
  } catch (Throwable $e2) {
    $stmt = $pdo->prepare("SELECT * FROM $T_TOURS t WHERE $where LIMIT 1");
    $stmt->execute($bind);
    $tour = $stmt->fetch(PDO::FETCH_ASSOC);
    $dbg['tour.query'] = 'tours only';
  }
}
if (!$tour) {
  http_response_code(404);
  exit('Not found');
}

// ---------- Prepare fields ----------
$title      = (string)$pick($tour, ['name', 'title'], 'โปรแกรมทัวร์');
$price      = (float)$pick($tour, ['price', 'base_price', 'sale_price'], 0);
$days       = (int)$pick($tour, ['duration_days', 'days', 'duration'], 0);
$window     = (string)$pick($tour, ['travel_window', 'date_range', 'travel_period'], '');
$airline    = (string)$pick($tour, ['airline'], '');
$origin     = (string)$pick($tour, ['origin_airport', 'origin'], '');
$dest       = (string)$pick($tour, ['dest_airport', 'destination', 'dest'], '');
$countryNm  = (string)$pick($tour, ['country_name'], '');
$tourCode   = (string)$pick($tour, ['code'], $code);

// map id เผื่อ fallback
$tourId = (int)$pick($tour, ['id', 'tour_id', 'tours_id'], 0);
if ($tourId <= 0 && $tourCode !== '') {
  try {
    $st = $pdo->prepare("SELECT tour_id, id FROM $T_TOURS WHERE code = :code LIMIT 1");
    $st->execute([':code' => $tourCode]);
    if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
      $tourId = (int)$pick($row, ['tour_id', 'id'], 0);
      $dbg['tourId.mapped_from_code'] = $tourId;
    }
  } catch (Throwable $e) {}
}

// รูปปก: cover_image_path > thumbnail > cover_image > image > photo
$imgFile = (string)$pick($tour, ['cover_image_path', 'thumbnail', 'cover_image', 'image', 'photo'], '');
if ($imgFile) {
  if (preg_match('#^https?://#', $imgFile))      $imgUrl = $imgFile;
  elseif ($imgFile[0] === '/')                   $imgUrl = rtrim($BASE, '/') . $imgFile;
  else                                           $imgUrl = rtrim($BASE, '/') . '/' . ltrim($imgFile, '/');
} else {
  $imgUrl = rtrim($BASE, '/') . '/assets/no-image.png';
}

// รายละเอียดยาว
$longRaw  = (string)$pick($tour, ['long_description', 'description', 'content', 'detail', 'details'], '');
$longText = nl2br(h($longRaw));

// ---------- tour_departures (PREFER tour_code, fallback tour_id) ----------
$nextDep = null;
$depList = [];
$depSrc  = '';

$dep_has_code = $table_has_col($pdo, $T_DEPARTURES, 'tour_code');
$dep_has_id   = $table_has_col($pdo, $T_DEPARTURES, 'tour_id');

// next (อนาคต) — ใช้ code ก่อน
if ($dep_has_code && $tourCode !== '' && !$nextDep) {
  try {
    $st = $pdo->prepare("SELECT start_date, end_date, capacity, price
                         FROM $T_DEPARTURES
                         WHERE tour_code = :x AND (start_date IS NULL OR start_date >= CURDATE())
                         ORDER BY start_date ASC LIMIT 1");
    $st->execute([':x' => $tourCode]);
    $nextDep = $st->fetch(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {}
}
if (!$nextDep && $dep_has_id && $tourId > 0) {
  try {
    $st = $pdo->prepare("SELECT start_date, end_date, capacity, price
                         FROM $T_DEPARTURES
                         WHERE tour_id = :x AND (start_date IS NULL OR start_date >= CURDATE())
                         ORDER BY start_date ASC LIMIT 1");
    $st->execute([':x' => $tourId]);
    $nextDep = $st->fetch(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {}
}

// list (ทั้งหมด) — ใช้ code ก่อน
if ($dep_has_code && $tourCode !== '') {
  try {
    $st = $pdo->prepare("SELECT id, start_date, end_date, capacity, price
                         FROM $T_DEPARTURES
                         WHERE tour_code = :x
                         ORDER BY start_date ASC LIMIT 500");
    $st->execute([':x' => $tourCode]);
    $depList = $st->fetchAll(PDO::FETCH_ASSOC);
    $depSrc  = 'tour_departures.by_code';
  } catch (Throwable $e) {}
}
if (!$depList && $dep_has_id && $tourId > 0) {
  try {
    $st = $pdo->prepare("SELECT id, start_date, end_date, capacity, price
                         FROM $T_DEPARTURES
                         WHERE tour_id = :x
                         ORDER BY start_date ASC LIMIT 500");
    $st->execute([':x' => $tourId]);
    $depList = $st->fetchAll(PDO::FETCH_ASSOC);
    $depSrc  = 'tour_departures.by_id';
  } catch (Throwable $e) {}
}
$dbg['deps.count']  = count($depList);
$dbg['deps.source'] = $depSrc ?: 'none';

// ---------- tour_days (PREFER tour_code, fallback tour_id, SELECT * + map) ----------
$daysPlan = [];
$daysSrc  = '';

// ฟังก์ชันเลือกค่าจากหนึ่งแถว
$pickRow = function (array $row, array $keys, $default = null) {
  foreach ($keys as $k) {
    if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
      return $row[$k];
    }
  }
  return $default;
};

$can_days_by_code = $table_has_col($pdo, $T_TOUR_DAYS, 'tour_code');
$can_days_by_id   = $table_has_col($pdo, $T_TOUR_DAYS, 'tour_id');

// เลือก WHERE key: code ก่อน, id รอง
$whereCol = null;
$whereVal = null;
$daysSrc = 'none';
if ($can_days_by_code && $tourCode !== '') {
  $whereCol = 'tour_code';
  $whereVal = $tourCode;
  $daysSrc = 'tour_days.by_code';
} elseif ($can_days_by_id && $tourId > 0) {
  $whereCol = 'tour_id';
  $whereVal = $tourId;
  $daysSrc = 'tour_days.by_id';
}

// หา order column ที่น่าจะใช่
$orderCandidates = ['day_no', 'day', 'sequence', 'seq', 'order_no', 'sort_order', 'id'];
$orderCol = 'id';
foreach ($orderCandidates as $c) {
  if ($table_has_col($pdo, $T_TOUR_DAYS, $c)) {
    $orderCol = $c;
    break;
  }
}

// ยิง SELECT * แล้ว map เป็น day_no/title/detail
if ($whereCol !== null) {
  try {
    $sql = "SELECT * FROM $T_TOUR_DAYS WHERE `$whereCol` = :x ORDER BY `$orderCol` ASC";
    $st  = $pdo->prepare($sql);
    $st->execute([':x' => $whereVal]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $i = 0;
    foreach ($rows as $r) {
      $i++;
      $dNo  = (int)$pickRow($r, ['day_no', 'day', 'sequence', 'seq', 'order_no', 'sort_order'], $i);
      $dTit = (string)$pickRow($r, ['title', 'day_title', 'name', 'headline', 'subject'], '');
      $dDet = (string)$pickRow($r, ['detail', 'day_detail', 'description', 'content', 'desc', 'details'], '');
      $daysPlan[] = ['day_no' => $dNo, 'title' => $dTit, 'detail' => $dDet];
    }
  } catch (Throwable $e) {
    $daysPlan = [];
    $daysSrc  = 'tour_days.query_error';
  }
}

// Fallback: parse จาก long_description
if (!$daysPlan && $longRaw !== '') {
  $txt = str_replace("\r\n", "\n", $longRaw);
  if (preg_match_all('/(?:^|\n)\s*(?:วันที่|วันที|Day)\s*(\d+)\s*(.*?)(?=(?:\n\s*(?:วันที่|วันที|Day)\s*\d+)|\z)/uis', $txt, $m, PREG_SET_ORDER)) {
    foreach ($m as $blk) {
      $n = (int)$blk[1];
      $t = trim($blk[2]);
      $pos = function_exists('mb_strpos') ? mb_strpos($t, "\n") : strpos($t, "\n");
      if ($pos !== false) {
        $titleDay  = trim(function_exists('mb_substr') ? mb_substr($t, 0, $pos) : substr($t, 0, $pos));
        $detailDay = trim(function_exists('mb_substr') ? mb_substr($t, $pos + 1) : substr($t, $pos + 1));
      } else {
        $titleDay = $t;
        $detailDay = '';
      }
      $daysPlan[] = ['day_no' => $n, 'title' => $titleDay, 'detail' => $detailDay];
    }
    $daysSrc = 'parsed-long_description';
  }
}
$dbg['days.count']  = count($daysPlan);
$dbg['days.source'] = $daysSrc ?: 'none';

// ---------- formatter วันไทยแบบ "12 ตุลาคม 2568" ----------
$fmt_th = function (?string $d) {
  if (!$d) return '';
  $ts = strtotime($d);
  if ($ts === false) return $d;

  $months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
  ];

  $day   = (int)date('j', $ts);
  $month = $months[(int)date('n', $ts)] ?? date('n', $ts);
  $year  = (int)date('Y', $ts) + 543; // พ.ศ.

  return $day . ' ' . $month . ' ' . $year;
};

// ===== เตรียมรายการรอบเดินทางที่ว่าง (capacity > 0 และไม่ใช่อดีต) =====
$today = date('Y-m-d');
$availableDeps = array_values(array_filter($depList ?: [], function ($d) use ($today) {
  $cap   = isset($d['capacity']) ? (int)$d['capacity'] : null;
  $start = (string)($d['start_date'] ?? '');
  if ($start && $start < $today) return false;        // ตัดอดีต
  if ($cap !== null && $cap <= 0) return false;       // เต็มแล้วไม่เอา
  return true;                                        // ถ้าไม่ระบุ cap ถือว่าอนุโลมให้จอง
}));

// default option (ลองใช้รอบถัดไปถ้าว่าง, ไม่งั้นใช้แถวแรกที่ว่าง)
$defaultDepId = null;
if (!empty($nextDep)) {
  $capN = isset($nextDep['capacity']) ? (int)$nextDep['capacity'] : null;
  $startN = (string)($nextDep['start_date'] ?? '');
  if ((!$startN || $startN >= $today) && ($capN === null || $capN > 0)) {
    // หา id ที่ตรง nextDep ใน $depList
    foreach ($depList as $d) {
      if (($d['start_date'] ?? null) === ($nextDep['start_date'] ?? null)
        && ($d['end_date'] ?? null) === ($nextDep['end_date'] ?? null)
      ) {
        $defaultDepId = (int)($d['id'] ?? 0);
        break;
      }
    }
  }
}
if ($defaultDepId === null && !empty($availableDeps)) {
  $defaultDepId = (int)($availableDeps[0]['id'] ?? 0);
}

// แปลงข้อมูลทั้งหมดสำหรับ JS
$depsForJs = [];
foreach ($depList as $d) {
  $depsForJs[] = [
    'id'       => (int)($d['id'] ?? 0),
    'start'    => (string)($d['start_date'] ?? ''),
    'end'      => (string)($d['end_date'] ?? ''),
    'price'    => isset($d['price']) ? (float)$d['price'] : null,
    'capacity' => isset($d['capacity']) ? (int)$d['capacity'] : null,
    'label'    => trim(($fmt_th((string)($d['start_date'] ?? ''))) .
      ((string)($d['end_date'] ?? '') !== '' ? (' – ' . $fmt_th((string)$d['end_date'])) : '')),
    'is_full'  => (isset($d['capacity']) && (int)$d['capacity'] <= 0),
    'is_past'  => ((string)($d['start_date'] ?? '') !== '' && (string)$d['start_date'] < $today)
  ];
}

// ราคาเริ่มต้นของทัวร์ (เผื่อรอบไม่มีราคาเฉพาะ)
$basePrice = (float)$price;

// Absolute endpoints (ป้องกัน 404 จาก path relative)
$AUTH_REGISTER_URL = rtrim($BASE, '/') . '/auth_register.php';
$AUTH_LOGIN_URL    = rtrim($BASE, '/') . '/auth_login.php';
$BOOKING_SUBMIT_URL= rtrim($BASE, '/') . '/booking_submit.php';

// ---------- Render ----------
public_start_page($title);
include __DIR__ . '/partials/public_navbar.php';
?>
<style>
  /* สไตล์เสริมสำหรับตารางใน card */
  .table-card .table thead th { position: sticky; top: 0; z-index: 2; }
  .table-card .table { --bs-table-bg: #fff; }
  .table-card .table tbody tr:hover { background: #f9fbff; }
  .table-card .table td, .table-card .table th { vertical-align: middle; }
  .table-card .card-header { background: linear-gradient(180deg, #fff, #f8f9fa); }
</style>

<div class="container py-4">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= h($BASE ?: '/') ?>">หน้าหลัก</a></li>
      <li class="breadcrumb-item"><a href="<?= h(rtrim($BASE,'/').'/tours.php') ?>">โปรแกรมทัวร์</a></li>
      <?php if ($countryNm): ?><li class="breadcrumb-item"><span><?= h($countryNm) ?></span></li><?php endif; ?>
      <li class="breadcrumb-item active" aria-current="page"><?= h($title) ?></li>
    </ol>
  </nav>

  <?php if ($DEBUG): ?>
    <div class="alert alert-info">
      <div><b>DEBUG</b> (prefer code)</div>
      <div>tourId: <?= (int)$tourId ?>, tourCode: <?= h($tourCode) ?></div>
      <?php if (isset($dbg['tourId.mapped_from_code'])): ?>
        <div>tourId (mapped from code): <?= (int)$dbg['tourId.mapped_from_code'] ?></div>
      <?php endif; ?>
      <div>deps.count: <?= (int)($dbg['deps.count'] ?? 0) ?> | source: <code><?= h($dbg['deps.source'] ?? '-') ?></code></div>
      <div>days.count: <?= (int)($dbg['days.count'] ?? 0) ?> | source: <code><?= h($dbg['days.source'] ?? '-') ?></code></div>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Left column -->
    <div class="col-12 col-lg-7">
      <img src="<?= h($imgUrl) ?>" class="img-fluid rounded shadow-sm mb-3" alt="<?= h($title) ?>">
      <h1 class="h4 mb-2"><?= h($title) ?></h1>
      <div class="text-muted small mb-3">
        <?php if ($days > 0): ?><span class="text-danger fw-semibold" style="font-size:105%"><?= $days ?> วัน</span><?= $window ? ' · ' : '' ?><?php endif; ?>
        <?= $window ? h($window) : '' ?>
        <?= $countryNm ? ' · '.h($countryNm) : '' ?>
      </div>

      <?php if ($longRaw): ?>
        <div class="mb-3">
          <h2 class="h6 mb-2">รายละเอียดเพิ่มเติม</h2>
          <div class="text-break"><?= $longText ?></div>
        </div>
      <?php endif; ?>

      <?php if ($depList): ?>
        <div class="card shadow-sm table-card mb-4">
          <div class="card-header fw-semibold">รอบเดินทาง</div>
          <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="text-nowrap">วันออกเดินทาง</th>
                  <th class="text-nowrap">วันสิ้นสุด</th>
                  <th class="text-end text-nowrap" style="width:140px;">ราคา (บาท)</th>
                  <th class="text-center" style="width:120px;">สถานะ</th>
                  <th class="text-center" style="width:140px;">เลือกจอง</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($depList as $d):
                  $id    = (int)($d['id'] ?? 0);
                  $start = $fmt_th((string)($d['start_date'] ?? ''));
                  $end   = $fmt_th((string)($d['end_date'] ?? ''));
                  $cap   = isset($d['capacity']) ? (int)$d['capacity'] : null;
                  $pDep  = isset($d['price']) ? (float)$d['price'] : null;

                  $isPast = ((string)($d['start_date'] ?? '') !== '' && (string)$d['start_date'] < date('Y-m-d'));
                  $isFull = ($cap !== null && $cap <= 0);

                  // badge สถานะ
                  $badge = '<span class="badge bg-secondary">ไม่ระบุ</span>';
                  if ($cap !== null) {
                    if ($cap <= 0)       $badge = '<span class="badge bg-danger">เต็ม</span>';
                    elseif ($cap <= 5)   $badge = '<span class="badge bg-warning text-dark">เหลือ ' . $cap . '</span>';
                    else                 $badge = '<span class="badge bg-success">เหลือ ' . $cap . '</span>';
                  }
                ?>
                  <tr>
                    <td class="text-nowrap"><?= h($start) ?></td>
                    <td class="text-nowrap"><?= h($end) ?></td>
                    <td class="text-end fw-semibold"><?= $pDep !== null ? number_format($pDep) : '—' ?></td>
                    <td class="text-center"><?= $badge ?></td>
                    <td class="text-center">
                      <?php if (!$isPast && !$isFull): ?>
                        <button class="btn btn-outline-primary btn-sm btn-pick-departure" type="button" data-dep-id="<?= $id ?>">จองรอบนี้</button>
                      <?php else: ?>
                        <span class="text-muted small">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($daysPlan): ?>
        <div class="card shadow-sm table-card">
          <div class="card-header fw-semibold">รายละเอียดรายวัน</div>
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:90px;" class="text-center">วัน</th>
                  <th>รายละเอียด</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($daysPlan as $i => $d):
                  $dayNo  = (int)($d['day_no'] ?? ($i + 1));
                  $dDet   = (string)($d['detail'] ?? '');
                ?>
                  <tr>
                    <td class="text-center fw-semibold"><?= $dayNo ?></td>
                    <td class="text-break lh-sm"><?= nl2br(h($dDet)) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php else: ?>
        <div class="alert alert-light border">ยังไม่มีรายละเอียดรายวัน</div>
      <?php endif; ?>
    </div>

    <!-- Right column: Booking Form -->
    <div class="col-12 col-lg-5" id="booking">
      <div class="card shadow-sm">
        <div class="card-body">
          <!-- หัวข้อ (จัดกลาง) -->
          <div class="fw-bold mb-2 text-center" style="font-size:140%;">จองโปรแกรมนี้</div>

          <!-- ราคาโปรแกรม (เต็มความกว้าง + จัดกึ่งกลาง) -->
          <div class="mb-3">
            <?php $__p = isset($basePrice) ? (float)$basePrice : (float)$price; ?>
            <span class="badge bg-warning text-dark w-100 d-block text-center py-2" style="font-size:150%;">
              <?= $__p > 0 ? 'ราคาโปรแกรม: '.number_format($__p).' บาท' : 'ราคาโปรแกรม: ติดต่อสอบถาม' ?>
            </span>
          </div>

          <!-- ข้อความสมัครสมาชิก + ปุ่ม -->
          <div class="card mb-3">
            <div class="card-body text-center">
              <p class="mb-0">
                คุณสามารถสมัครสมาชิกเพื่อรับสิทธิประโยชน์ต่างๆ และติดตามข้อมูลข่าวสารจากบริษัทฯ ได้อย่างง่ายดาย
                เพียงแค่คลิก <strong>สมัครสมาชิก</strong> ด้านล่าง
              </p>
            </div>
          </div>

          <div class="d-grid mb-3">
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#authModal">
              สมัครสมาชิก
            </button>
          </div>

          <?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
            <div class="alert alert-<?= $f['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
              <?= h($f['msg']) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <form id="bookingForm" class="needs-validation" method="post" action="<?= h($BOOKING_SUBMIT_URL) ?>" novalidate>
            <!-- ข้อมูลทัวร์สำหรับ backend -->
            <input type="hidden" name="tour_code" value="<?= h($tourCode) ?>">
            <input type="hidden" name="tour_id" value="<?= (int)$tourId ?>">
            <input type="hidden" id="selectedDepartureId" name="departure_id" value="<?= (int)$defaultDepId ?>">

            <div class="mb-3">
              <label class="form-label">ชื่อ-นามสกุล *</label>
              <input type="text" class="form-control" name="full_name" required value="<?= h($_SESSION['member']['full_name'] ?? '') ?>">
              <div class="invalid-feedback">กรุณากรอกชื่อ-นามสกุล</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Email *</label>
              <input type="email" class="form-control" name="email" required value="<?= h($_SESSION['member']['email'] ?? '') ?>">
              <div class="invalid-feedback">กรุณากรอกอีเมลให้ถูกต้อง</div>
            </div>

            <div class="mb-3">
              <label class="form-label">เบอร์โทรศัพท์ *</label>
              <input type="tel" class="form-control" name="phone" required value="<?= h($_SESSION['member']['phone'] ?? '') ?>">
              <div class="invalid-feedback">กรุณากรอกเบอร์โทรศัพท์</div>
            </div>

            <div class="mb-3">
              <label class="form-label">วันที่เดินทาง *</label>
              <select id="departureSelect" class="form-select" required>
                <?php if (empty($availableDeps)): ?>
                  <option value="">— ไม่มีรอบที่ว่าง —</option>
                <?php else: ?>
                  <?php foreach ($availableDeps as $ad):
                    $id = (int)($ad['id'] ?? 0);
                    $lbl = trim(($fmt_th((string)($ad['start_date'] ?? ''))) .
                      ((string)($ad['end_date'] ?? '') !== '' ? (' – ' . $fmt_th((string)$ad['end_date'])) : ''));
                    $p   = isset($ad['price']) ? (float)$ad['price'] : null;
                  ?>
                    <option value="<?= $id ?>" data-price="<?= $p !== null ? $p : '' ?>" <?= $defaultDepId === $id ? 'selected' : '' ?>>
                      <?= h($lbl) ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
              <div class="invalid-feedback">กรุณาเลือกวันที่เดินทาง</div>
            </div>

            <div class="mb-3">
              <label class="form-label">เวลาที่สะดวกให้เจ้าหน้าที่ติดต่อกลับ</label>
              <input type="time" class="form-control" name="contact_time">
            </div>

            <div class="mb-3">
              <label class="form-label d-flex align-items-center justify-content-between">
                <span>จำนวนคนเดินทาง *</span>
                <small class="text-muted">จะใช้คำนวณราคารวม</small>
              </label>
              <div class="input-group" style="max-width: 220px;">
                <button class="btn btn-outline-secondary" type="button" id="btnQtyMinus">−</button>
                <input type="number" class="form-control text-center" id="qtyInput" name="qty" min="1" value="1" required>
                <button class="btn btn-outline-secondary" type="button" id="btnQtyPlus">+</button>
                <div class="invalid-feedback">กรุณาใส่จำนวนคนอย่างน้อย 1</div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">รายละเอียดเพิ่มเติม</label>
              <textarea class="form-control" name="note" rows="3" placeholder="ต้องการออกใบกำกับภาษี, อาหารพิเศษ, ฯลฯ"></textarea>
            </div>

            <div class="d-flex align-items-baseline justify-content-between mb-3">
              <div class="text-muted">ราคารายบุคคล</div>
              <div id="unitPriceText" class="fw-semibold"></div>
            </div>

            <div class="d-flex align-items-center justify-content-between">
              <div class="text-muted">ราคารวม</div>
              <div id="totalPriceText" class="fw-bold fs-4"></div>
            </div>

            <!-- reCAPTCHA -->
            <div class="mt-3">
              <?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY): ?>
                <div class="g-recaptcha" data-sitekey="<?= h(RECAPTCHA_SITE_KEY) ?>" data-callback="onRecaptchaOK"></div>
              <?php else: ?>
                <div class="alert alert-info py-2">
                  <div class="small">* reCAPTCHA ยังไม่ได้ตั้งค่า (<code>RECAPTCHA_SITE_KEY</code>). ระบบจะให้กดส่งได้เลยสำหรับ dev.</div>
                </div>
              <?php endif; ?>
            </div>
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">

            <div class="d-grid mt-3">
              <button id="btnSubmitBooking" class="btn btn-primary btn-lg" type="submit">ตกลง</button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
  // Absolute endpoints for AJAX (กัน 404 จาก relative path)
  const AUTH_REGISTER_URL = "<?= h($AUTH_REGISTER_URL) ?>";
  const AUTH_LOGIN_URL    = "<?= h($AUTH_LOGIN_URL) ?>";

  // ===== ข้อมูลสำหรับ JS =====
  const DEPS = <?= json_encode($depsForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  const BASE_PRICE = <?= json_encode($basePrice) ?>;

  const fmtNumber = n => new Intl.NumberFormat('th-TH').format(n);

  const depSelect   = document.getElementById('departureSelect');
  const qtyInput    = document.getElementById('qtyInput');
  const unitText    = document.getElementById('unitPriceText');
  const totalText   = document.getElementById('totalPriceText');
  const hiddenDep   = document.getElementById('selectedDepartureId');
  const btnMinus    = document.getElementById('btnQtyMinus');
  const btnPlus     = document.getElementById('btnQtyPlus');
  const bookingForm = document.getElementById('bookingForm');
  const submitBtn   = document.getElementById('btnSubmitBooking');

  function getSelectedDep() {
    const id = Number(hiddenDep.value || depSelect?.value || 0);
    return DEPS.find(d => Number(d.id) === id) || null;
  }

  function getUnitPrice() {
    // ถ้าในรอบมีราคาเฉพาะ ใช้อันนั้น, ไม่งั้นใช้ราคา tour
    const opt = depSelect?.selectedOptions?.[0];
    if (opt && opt.dataset.price) {
      const p = parseFloat(opt.dataset.price);
      if (!Number.isNaN(p)) return p;
    }
    const dep = getSelectedDep();
    if (dep && dep.price != null) return Number(dep.price);
    return Number(BASE_PRICE || 0);
  }

  function updateTotal() {
    const unit = getUnitPrice();
    const qty  = Math.max(1, parseInt(qtyInput.value || '1', 10));
    unitText.textContent  = unit > 0 ? fmtNumber(unit) + ' บาท' : '—';
    totalText.textContent = unit > 0 ? fmtNumber(unit * qty) + ' บาท' : '—';
  }

  function selectDeparture(depId) {
    if (!depSelect) return;
    // หากมี option ใน select ให้เลือกตรง depId
    const opt = depSelect.querySelector(`option[value="${depId}"]`);
    if (opt) {
      depSelect.value = String(depId);
      hiddenDep.value = String(depId);
      depSelect.dispatchEvent(new Event('change'));
    } else {
      // หากจิ้มจากแถวที่ไม่ได้อยู่ใน availableDeps
      hiddenDep.value = String(depId);
      updateTotal();
    }
    bookingForm?.scrollIntoView({behavior: 'smooth', block: 'start'});
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (depSelect) hiddenDep.value = depSelect.value || hiddenDep.value || '';
    updateTotal();

    depSelect?.addEventListener('change', () => {
      hiddenDep.value = depSelect.value;
      updateTotal();
    });

    btnMinus?.addEventListener('click', () => {
      const n = Math.max(1, parseInt(qtyInput.value || '1', 10) - 1);
      qtyInput.value = n; updateTotal();
    });
    btnPlus?.addEventListener('click', () => {
      const n = Math.max(1, parseInt(qtyInput.value || '1', 10) + 1);
      qtyInput.value = n; updateTotal();
    });
    qtyInput?.addEventListener('input', updateTotal);

    // ปุ่ม "จองรอบนี้" ในตาราง
    document.querySelectorAll('.btn-pick-departure').forEach(btn => {
      btn.addEventListener('click', () => {
        const depId = btn.getAttribute('data-dep-id');
        if (depId) selectDeparture(depId);
      });
    });

    // Bootstrap validation
    bookingForm?.addEventListener('submit', function(e) {
      if (!bookingForm.checkValidity()) {
        e.preventDefault(); e.stopPropagation();
      }
      bookingForm.classList.add('was-validated');
    }, false);
  });

  // reCAPTCHA callback
  function onRecaptchaOK() { submitBtn?.removeAttribute('disabled'); }

  // ===== Modal: Register/Login via AJAX =====
  (function(){
    const regForm   = document.getElementById('auth-register-form');
    const loginForm = document.getElementById('auth-login-form');
    const regErr    = document.getElementById('regError');
    const logErr    = document.getElementById('loginError');

    function prefillBooking(member){
      if (!member) return;
      const f = document.getElementById('bookingForm');
      if (!f) return;
      if (member.full_name) f.querySelector('[name="full_name"]').value = member.full_name;
      if (member.email)     f.querySelector('[name="email"]').value     = member.email;
      if (member.phone)     f.querySelector('[name="phone"]').value     = member.phone;
    }

    function closeModal(){
      const modalEl = document.getElementById('authModal');
      const m = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
      m.hide();
    }

    async function postForm(url, data){
      const resp = await fetch(url, { method:'POST', body: data, headers: { 'X-Requested-With':'fetch' } });
      const json = await resp.json().catch(()=>({ok:false,error:'bad_json'}));
      if (!resp.ok || !json.ok) throw json;
      return json;
    }

    regForm?.addEventListener('submit', async (e)=>{
      e.preventDefault();
      regErr?.classList.add('d-none');
      const btn = regForm.querySelector('button[type="submit"]');
      btn.disabled = true;
      try{
        const fd = new FormData(regForm);
        const out = await postForm(AUTH_REGISTER_URL, fd);
        prefillBooking(out.member);
        closeModal();
      }catch(err){
        const msg = (err && err.error) ? err.error : 'server_error';
        regErr.textContent = (msg==='email_taken')?'อีเมลนี้ถูกใช้แล้ว':'สมัครไม่สำเร็จ';
        regErr.classList.remove('d-none');
      }finally{ btn.disabled = false; }
    });

    loginForm?.addEventListener('submit', async (e)=>{
      e.preventDefault();
      logErr?.classList.add('d-none');
      const btn = loginForm.querySelector('button[type="submit"]');
      btn.disabled = true;
      try{
        const fd = new FormData(loginForm);
        const out = await postForm(AUTH_LOGIN_URL, fd);
        prefillBooking(out.member);
        closeModal();
      }catch(err){
        const msg = (err && err.error) ? err.error : 'server_error';
        logErr.textContent = (msg==='auth_failed')?'อีเมลหรือรหัสผ่านไม่ถูกต้อง':'เข้าสู่ระบบไม่สำเร็จ';
        logErr.classList.remove('d-none');
      }finally{ btn.disabled = false; }
    });
  })();
</script>

<?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY): ?>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/auth.php'; $ME = auth_member(); ?>
<div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
          <li class="nav-item"><button class="nav-link active" id="tab-register" data-bs-toggle="tab" data-bs-target="#pane-register" type="button" role="tab">สมัครสมาชิก</button></li>
          <li class="nav-item"><button class="nav-link" id="tab-login" data-bs-toggle="tab" data-bs-target="#pane-login" type="button" role="tab">เข้าสู่ระบบ</button></li>
        </ul>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
      </div>

      <div class="tab-content">
        <!-- Register -->
        <div class="tab-pane fade show active p-3" id="pane-register" role="tabpanel">
          <form id="auth-register-form">
            <div class="mb-3">
              <label class="form-label">ชื่อ-นามสกุล *</label>
              <input type="text" class="form-control" name="full_name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">อีเมล *</label>
              <input type="email" class="form-control" name="email" required>
            </div>
            <div class="mb-3">
              <label class="form-label">เบอร์โทร</label>
              <input type="tel" class="form-control" name="phone">
            </div>
            <div class="mb-3">
              <label class="form-label">รหัสผ่าน (อย่างน้อย 8 ตัวอักษร) *</label>
              <input type="password" class="form-control" name="password" minlength="8" required>
            </div>
            <div class="d-grid">
              <button class="btn btn-primary" type="submit">สมัครสมาชิก</button>
            </div>
            <div class="small text-danger mt-2 d-none" id="regError"></div>
          </form>
        </div>

        <!-- Login -->
        <div class="tab-pane fade p-3" id="pane-login" role="tabpanel">
          <form id="auth-login-form">
            <div class="mb-3">
              <label class="form-label">อีเมล *</label>
              <input type="email" class="form-control" name="email" required>
            </div>
            <div class="mb-3">
              <label class="form-label">รหัสผ่าน *</label>
              <input type="password" class="form-control" name="password" required>
            </div>
            <div class="d-grid">
              <button class="btn btn-success" type="submit">เข้าสู่ระบบ</button>
            </div>
            <div class="small text-danger mt-2 d-none" id="loginError"></div>
          </form>
        </div>
      </div>

      <?php if ($ME): ?>
        <div class="p-3 border-top small text-muted">
          เข้าสู่ระบบแล้วในชื่อ <strong><?= h($ME['full_name']) ?></strong>
          <a href="<?= h(rtrim($BASE,'/').'/logout.php') ?>" class="ms-2">ออกจากระบบ</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php public_end_page(); ?>
