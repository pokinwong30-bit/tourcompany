<?php
// tours.php (Public) — ไม่พึ่ง country_iso, รองรับ JOIN countries ถ้ามี, มี fallback

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/public_layout.php';

$BASE = public_base_url();

// ---------- helpers ภายในไฟล์ ----------
$pick = function(array $row, array $keys, $default = null) {
  foreach ($keys as $k) {
    if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') return $row[$k];
  }
  return $default;
};

$get_int = function(string $key, int $default = 1): int {
  $v = $_GET[$key] ?? $default;
  if (!is_numeric($v)) return $default;
  $v = (int)$v;
  return $v > 0 ? $v : $default;
};

$paginate_meta = function(int $total, int $page, int $per_page): array {
  $pages = max(1, (int)ceil($total / $per_page));
  $page = max(1, min($page, $pages));
  $offset = ($page - 1) * $per_page;
  return compact('pages','page','per_page','offset','total');
};

$render_pagination = function(int $pages, int $page) {
  if ($pages <= 1) return '';
  $qs = function($p) {
    $params = $_GET;
    $params['page'] = $p;
    return '?' . http_build_query($params);
  };
  ob_start(); ?>
  <nav class="d-flex justify-content-center py-3">
    <ul class="pagination pagination-lg flex-wrap gap-2">
      <li class="page-item <?= $page<=1?'disabled':'' ?>">
        <a class="page-link" href="<?= $qs(max(1,$page-1)) ?>" aria-label="Previous">&laquo;</a>
      </li>
      <?php
      $window = 2;
      $start = max(1, $page - $window);
      $end   = min($pages, $page + $window);
      if ($start > 1) {
        echo '<li class="page-item"><a class="page-link" href="'.$qs(1).'">1</a></li>';
        if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
      }
      for ($i = $start; $i <= $end; $i++) {
        $active = $i == $page ? 'active' : '';
        echo '<li class="page-item '.$active.'"><a class="page-link" href="'.$qs($i).'">'.$i.'</a></li>';
      }
      if ($end < $pages) {
        if ($end < $pages-1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        echo '<li class="page-item"><a class="page-link" href="'.$qs($pages).'">'.$pages.'</a></li>';
      }
      ?>
      <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
        <a class="page-link" href="<?= $qs(min($pages,$page+1)) ?>" aria-label="Next">&raquo;</a>
      </li>
    </ul>
  </nav>
  <style>
    .pagination .page-link{line-height:1.4;padding:.6rem .9rem;min-width:44px}
    .pagination{row-gap:.5rem}
  </style>
  <?php return ob_get_clean();
};

// ---------- รับพารามิเตอร์ ----------
$per_page = 12;
$page   = $get_int('page', 1);
$scope  = $_GET['scope']   ?? null;   // 'domestic'
$isoReq = isset($_GET['country']) ? strtoupper((string)$_GET['country']) : null;
$q      = trim((string)($_GET['q'] ?? ''));

// ---------- เตรียม SQL เงื่อนไข ----------
$condsJoin  = [];  // สำหรับกรณี JOIN countries (c.iso2)
$condsPlain = [];  // สำหรับกรณีไม่ JOIN (ลองใช้ t.country_iso ถ้ามีไม่ได้ก็ลบทิ้ง)

if ($q !== '') {
  $condsJoin[]  = '(t.name LIKE :q OR t.title LIKE :q)';
  $condsPlain[] = '(t.name LIKE :q OR t.title LIKE :q)';
}
// โชว์เฉพาะทัวร์ที่เปิดขาย
$condsJoin[]  = 't.is_on_sale = 1';
$condsPlain[] = 't.is_on_sale = 1';

$params = [];
if ($q !== '') $params[':q'] = '%'.$q.'%';

$title = 'โปรแกรมทัวร์ทั้งหมด';
if ($scope === 'domestic') {
  // กรองเป็นไทย: ถ้ามี countries -> c.iso2='TH' / ถ้าไม่มี -> จะลอง t.country_iso='TH' แล้วค่อย fallback ลบทิ้ง
  $condsJoin[]  = "c.iso2 = 'TH'";
  $condsPlain[] = "t.country_iso = 'TH'";
  $title = 'ทัวร์ในประเทศ';
} elseif ($isoReq) {
  $condsJoin[]  = 'c.iso2 = :iso';
  $condsPlain[] = 't.country_iso = :iso';
  $params[':iso'] = $isoReq;
  $title = 'ทัวร์ต่างประเทศ';
}

// ---------- ดึงข้อมูลทั้งหมดแบบ fallback (แล้วค่อย paginate ด้วย PHP เพื่อความทนทาน) ----------
$rowsAll = [];
$usedMode = '';

try {
  // โหมด A: JOIN countries + ไม่แตะ is_on_sale (กันคอลัมน์ไม่ตรง)
  $where = $condsJoin ? ('WHERE '.implode(' AND ', $condsJoin)) : '';
  $sql = "
    SELECT t.*, COALESCE(c.name_th, c.name_en) AS country_name
    FROM tours t
    LEFT JOIN countries c ON c.id = t.country_id
    $where
    ORDER BY t.created_at DESC
  ";
  $stmt = $pdo->prepare($sql);
  foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
  $stmt->execute();
  $rowsAll = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $usedMode = 'join';
} catch (Throwable $e2) {
  // โหมด C: พยายามกรอง is_on_sale ก่อน หากไม่มีคอลัมน์ให้ fallback อีกชั้น
  try {
    $condsSafe = [];
    if ($q !== '') $condsSafe[] = '(t.name LIKE :q OR t.title LIKE :q)';
    $condsSafe[] = 't.is_on_sale = 1';
    $where = 'WHERE '.implode(' AND ', $condsSafe);
    $sql = "
      SELECT t.*
      FROM tours t
      $where
      ORDER BY t.id DESC
    ";
    $stmt = $pdo->prepare($sql);
    if ($q !== '') $stmt->bindValue(':q', '%'.$q.'%');
    $stmt->execute();
    $rowsAll = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $usedMode = 'nofilter';
  } catch (Throwable $e3) {
    // fallback ขั้นสุดท้ายจริง ๆ: ตัด is_on_sale ออกเพื่อไม่ให้หน้า error
    $condsSafe = [];
    if ($q !== '') $condsSafe[] = '(t.name LIKE :q OR t.title LIKE :q)';
    $where = $condsSafe ? ('WHERE '.implode(' AND ', $condsSafe)) : '';
    $sql = "
      SELECT t.*
      FROM tours t
      $where
      ORDER BY t.id DESC
    ";
    $stmt = $pdo->prepare($sql);
    if ($q !== '') $stmt->bindValue(':q', '%'.$q.'%');
    $stmt->execute();
    $rowsAll = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $usedMode = 'nofilter';
  }
}


// ---------- ตั้งชื่อหน้า (ถ้ามี country_name บ้าง) ----------
if ($isoReq && $usedMode === 'join') {
  // พยายามอ่านชื่อประเทศจากผลลัพธ์ชุดแรก
  foreach ($rowsAll as $r) {
    if (!empty($r['country_name'])) { $title = 'ทัวร์ต่างประเทศ - '.$r['country_name']; break; }
  }
} elseif ($scope === 'domestic') {
  $title = 'ทัวร์ในประเทศ';
}

// ---------- paginate ใน PHP ----------
$total = count($rowsAll);
$pg = $paginate_meta($total, $page, $per_page);
$rows = array_slice($rowsAll, $pg['offset'], $pg['per_page']);

public_start_page($title);
include __DIR__ . '/partials/public_navbar.php';
?>
<style>
  .line-clamp-3{
    display:-webkit-box;
    -webkit-line-clamp:3;
    -webkit-box-orient:vertical;
    overflow:hidden;
  }
</style>
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 m-0"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    <span class="text-muted small"><?= number_format($pg['total']) ?> รายการ</span>
  </div>

  <?php if ($q !== ''): ?>
    <div class="alert alert-light border d-flex justify-content-between">
      <div>ผลการค้นหาสำหรับ “<b><?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?></b>”</div>
      <div><a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($BASE.'/tours.php', ENT_QUOTES, 'UTF-8') ?>">ล้างการค้นหา</a></div>
    </div>
  <?php endif; ?>

  <?php if (($scope === 'domestic' || $isoReq) && $usedMode === 'nofilter'): ?>
    <div class="alert alert-warning">
      ไม่พบฟิลด์ประเทศในฐานข้อมูล จึงแสดงทุกรายการ (กดค้นหาเพื่อกรองได้)
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <?php foreach ($rows as $t):
      $code   = (string)($t['code'] ?? '');
      $name   = (string)$pick($t, ['name','title'], 'โปรแกรมทัวร์');
      $price  = (float)$pick($t, ['price','base_price','sale_price'], 0);
      $days   = (int)$pick($t, ['duration_days','days','duration'], 0);
      $window = (string)$pick($t, ['travel_window','date_range','travel_period'], '');
      $countryName = (string)$pick($t, ['country_name'], '');

      // คำอธิบายย่อ
      $descRaw   = (string)$pick($t, ['long_description','description','content','detail','details'], '');
      $descPlain = trim(preg_replace('/\s+/', ' ', strip_tags($descRaw)));
      if ($descPlain !== '') {
        if (function_exists('mb_strimwidth')) {
          $descPlain = mb_strimwidth($descPlain, 0, 220, '…', 'UTF-8');
        } else {
          if (strlen($descPlain) > 300) $descPlain = substr($descPlain, 0, 300).'…';
        }
      }

      // รูปปก: cover_image_path > thumbnail > cover_image > image > photo
      $imgFile = (string)$pick($t, ['cover_image_path','thumbnail','cover_image','image','photo'], '');
      if ($imgFile) {
        if (preg_match('#^https?://#', $imgFile)) {
          $imgUrl = $imgFile;
        } elseif ($imgFile[0] === '/') {
          $imgUrl = rtrim($BASE,'/') . $imgFile;
        } else {
          $imgUrl = rtrim($BASE,'/') . '/' . ltrim($imgFile, '/');
        }
      } else {
        $imgUrl = rtrim($BASE,'/') . '/assets/no-image.png';
      }
    ?>
      <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
        <div class="card h-100 shadow-sm">
          <img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>" class="card-img-top" alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
          <div class="card-body d-flex flex-column">
            <h3 class="h6 card-title mb-1"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h3>

            <?php if ($descPlain): ?>
              <p class="card-text text-secondary small line-clamp-3 mb-2"><?= htmlspecialchars($descPlain, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <div class="text-muted small mb-2">
              <?php if ($days > 0): ?>
                <span class="badge bg-warning text-dark" style="font-size:105%"><?= $days ?> วัน</span><?= $window ? ' · ' : '' ?>
              <?php endif; ?>
              <?= $window ? htmlspecialchars($window, ENT_QUOTES, 'UTF-8') : '' ?>
              <?= $countryName ? ' · '.htmlspecialchars($countryName, ENT_QUOTES, 'UTF-8') : '' ?>
            </div>

            <div class="mt-auto d-flex justify-content-between align-items-center">
              <div class="fw-bold"><?= $price > 0 ? number_format($price).' บาท' : 'ติดต่อสอบถาม' ?></div>
              <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars(rtrim($BASE,'/').'/tour.php?code='.urlencode($code ?: (string)($t['id'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">ดูรายละเอียด</a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if (!$rows): ?>
      <div class="col-12"><div class="alert alert-info">ไม่พบรายการที่ตรงเงื่อนไข</div></div>
    <?php endif; ?>
  </div>

  <?= $render_pagination($pg['pages'], $pg['page']) ?>
</div>
<?php public_end_page(); ?>
