<?php
// partials/public_navbar.php
require_once __DIR__ . '/../includes/db.php';

// สร้างรายการประเทศต่างประเทศที่มีโปรแกรม (JOIN กับตาราง countries)
// ปรับชื่อ table/คอลัมน์ตามสคีมาที่คุณใช้จริง: tours.country_id -> countries.id
$countryRows = [];
try {
    $sql = "
        SELECT DISTINCT c.iso2, COALESCE(c.name_th, c.name_en) AS name_th
        FROM tours t
        JOIN countries c ON c.id = t.country_id
        WHERE t.is_on_sale = 1
          AND c.iso2 <> 'TH'
        ORDER BY name_th ASC
    ";
    $stmt = $pdo->query($sql);
    $countryRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // ถ้าไม่มีตาราง countries หรือคอลัมน์ไม่ตรง จะไม่ล้มหน้า — แค่ไม่แสดงรายการ
    $countryRows = [];
}

$BASE = public_base_url();
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= h($BASE ?: '/') ?>">บริษัททัวร์</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= h($BASE ?: '/') ?>">หน้าหลัก</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= h($BASE.'/tours.php?scope=domestic') ?>">ทัวร์ในประเทศ</a></li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navForeign" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            ทัวร์ต่างประเทศ
          </a>
          <ul class="dropdown-menu" aria-labelledby="navForeign" style="max-height:60vh;overflow:auto">
            <?php if (!$countryRows): ?>
              <li><span class="dropdown-item-text text-muted">ยังไม่มีโปรแกรม</span></li>
            <?php else: ?>
              <?php foreach ($countryRows as $c): ?>
                <li>
                  <a class="dropdown-item" href="<?= h($BASE.'/tours.php?country='.$c['iso2']) ?>">
                    <?= h($c['name_th'] ?: $c['iso2']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </li>
      </ul>

      <form class="d-flex" action="<?= h($BASE.'/tours.php') ?>" method="get">
        <input class="form-control me-2" type="search" name="q" placeholder="ค้นหาชื่อโปรแกรม..." aria-label="Search">
        <button class="btn btn-outline-primary" type="submit">ค้นหา</button>
      </form>
    </div>
  </div>
</nav>
