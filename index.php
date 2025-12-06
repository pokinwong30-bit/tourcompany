<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/public_layout.php';

$BASE = public_base_url();

// 1) ดึงรายการทัวร์: พยายามกรอง is_on_sale ก่อน ถ้าไม่มีคอลัมน์นี้จะ fallback เป็น select ทั้งหมด
try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM tours
        WHERE is_on_sale = 1
        ORDER BY created_at DESC
        LIMIT 6
    ");
    $stmt->execute();
} catch (Throwable $e) {
    // ไม่มีคอลัมน์ is_on_sale หรือชื่อ created_at? -> fallback แบบปลอดภัย
    try {
        $stmt = $pdo->prepare("
            SELECT *
            FROM tours
            ORDER BY id DESC
            LIMIT 6
        ");
        $stmt->execute();
    } catch (Throwable $e2) {
        // fallback ขั้นสุดท้าย
        $stmt = $pdo->query("SELECT * FROM tours LIMIT 6");
    }
}

$tours = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2) ฟังก์ชันช่วยเลือกค่าจากหลายคอลัมน์ (เผื่อสคีมาไม่ตรง)
$pick = function (array $row, array $keys, $default = null) {
    foreach ($keys as $k) {
        if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
            return $row[$k];
        }
    }
    return $default;
};

public_start_page('หน้าหลัก');
include __DIR__ . '/partials/public_navbar.php';
?>
<style>
    :root {
        --bs-body-font-family: 'Poppins', 'Kanit', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans Thai', sans-serif;
        --bs-font-sans-serif: var(--bs-body-font-family);
    }

    html,
    body {
        font-family: var(--bs-body-font-family);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-rendering: optimizeLegibility;
        font-variant-numeric: tabular-nums;
    }

    /* >>> เพิ่มคลาสนี้ <<< */
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
<div class="container py-4">
    <div class="p-4 p-md-5 mb-4 bg-light rounded-3">
        <div class="container-fluid py-3">
            <h1 class="display-6 fw-bold">วางแผนเที่ยวกับเรา</h1>
            <p class="col-md-8 fs-5">คัดสรรโปรแกรมทัวร์คุณภาพ ทั้งในประเทศและต่างประเทศ</p>
            <a class="btn btn-success btn-lg" href="<?= h($BASE . '/tours.php?scope=domestic') ?>">Inbound Tours</a>
        </div>
    </div>

    <h2 class="h4 mb-3">โปรแกรมล่าสุด</h2>
    <div class="row g-3">
        <?php foreach ($tours as $t):
            // อ่านค่าตามคอลัมน์ที่มีจริง
            $code   = (string)($t['code'] ?? '');
            $name   = (string)$pick($t, ['name', 'title'], 'โปรแกรมทัวร์');
            $price  = (float)$pick($t, ['price', 'base_price', 'sale_price'], 0);
            $days   = (int)$pick($t, ['duration_days', 'days', 'duration'], 0);
            $window = (string)$pick($t, ['travel_window', 'date_range', 'travel_period'], '');
            $descRaw   = (string)$pick($t, ['long_description'], '');
            $descPlain = trim(preg_replace('/\s+/', ' ', strip_tags($descRaw)));
            if ($descPlain !== '') {
                if (function_exists('mb_strimwidth')) {
                    $descPlain = mb_strimwidth($descPlain, 0, 220, '…', 'UTF-8'); // กันข้อความดิบยาวเกิน
                } else {
                    if (strlen($descPlain) > 300) $descPlain = substr($descPlain, 0, 300) . '…';
                }
            }
            // เลือกรูป: cover_image_path > thumbnail > cover_image > image > photo
            $imgFile = (string)$pick($t, ['cover_image_path', 'thumbnail', 'cover_image', 'image', 'photo'], '');

            if ($imgFile) {
                if (preg_match('#^https?://#', $imgFile)) {
                    // เป็น URL เต็มแล้ว
                    $imgUrl = $imgFile;
                } elseif ($imgFile[0] === '/') {
                    // เป็นพาธสัมบูรณ์ใต้เว็บ เช่น /uploads/tours/xxx.jpg
                    $imgUrl = $BASE . $imgFile;
                } else {
                    // เป็นชื่อไฟล์เฉย ๆ
                    $imgUrl = rtrim($BASE, '/') . '/' . ltrim($imgFile, '/');
                }
            } else {
                // ไม่มีข้อมูลรูป → ใช้ภาพ placeholder
                $imgUrl = $BASE . '/assets/no-image.png';
            }

        ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <img src="<?= h($imgUrl) ?>" class="card-img-top" alt="<?= h($name) ?>" loading="lazy">
                    <div class="card-body d-flex flex-column">
                        <h3 class="h6 card-title mb-1"><?= h($name) ?></h3>
                        <div class="text-muted small mb-2">
                            <?php if ($days > 0): ?>
                                <span class="badge bg-warning text-dark" style="font-size:105%"><?= $days ?> วัน</span><?= $window ? ' · ' : '' ?>
                            <?php endif; ?>
                            <?= $window ? h($window) : '' ?>
                        </div>

                        <?php if ($descPlain): ?>
                            <p class="card-text text-secondary small line-clamp-3 mb-2"><?= h($descPlain) ?></p>
                        <?php endif; ?>

                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <div class="fw-bold"><?= $price > 0 ? number_format($price) . ' บาท' : 'ติดต่อสอบถาม' ?></div>
                            <a class="btn btn-sm btn-primary" href="<?= h($BASE . '/tour.php?code=' . urlencode($code ?: (string)($t['id'] ?? ''))) ?>">ดูรายละเอียด</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!$tours): ?>
            <div class="col-12">
                <div class="alert alert-info">ยังไม่มีโปรแกรม</div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php public_end_page(); ?>