<?php
// backoffice/costs_departures.php
require_once __DIR__.'/../includes/guard.php';
require_backoffice();
require_once __DIR__.'/../includes/db.php';

$tid = (int)($_GET['tour_id'] ?? 0);
if ($tid <= 0) { http_response_code(400); echo 'Invalid tour'; exit; }

// ดึงรอบ + cost + จำนวนคนที่จองแล้ว (ยกเว้น cancelled)
$sql = "
SELECT
  d.id AS dep_id,
  d.start_date, d.end_date, d.capacity,
  c.id AS cost_id,
  c.hotel_per_night, c.car_per_day, c.guide_per_day,
  c.tickets_per_person, c.food_per_person, c.tips_per_person,
  c.others_group, c.assumed_pax, c.price_per_person,
  c.computed_cost_per_person, c.computed_break_even_pax,
  COALESCE(bk.booked_pax, 0) AS booked_pax
FROM tour_departures d
LEFT JOIN tour_costs c
  ON c.departure_id = d.id
LEFT JOIN (
  SELECT departure_id, SUM(qty) AS booked_pax
  FROM bookings
  WHERE status <> 'cancelled'
  GROUP BY departure_id
) bk ON bk.departure_id = d.id
WHERE d.tour_id = :tid
ORDER BY d.start_date ASC, d.id ASC
";
$st = $pdo->prepare($sql);
$st->execute([':tid'=>$tid]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// helper ไทยวันที่
function th_date($d){
  if(!$d) return '';
  $ts = strtotime($d); if($ts===false) return $d;
  $m = [1=>'ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
  return (int)date('j',$ts).' '.$m[(int)date('n',$ts)].' '.((int)date('Y',$ts)+543);
}
?>
<div class="table-responsive">
  <table class="table table-sm align-middle mb-0">
    <thead class="table-secondary">
      <tr>
        <th>รอบวันที่</th>
        <th class="text-center" style="width:110px;">ที่นั่ง</th>
        <th class="text-center" style="width:130px;">จองแล้ว</th>
        <th class="text-center" style="width:160px;">ต้นทุน/คน (ล่าสุด)</th>
        <th class="text-center" style="width:180px;">จุดคุ้มทุน (pax)</th>
        <th class="text-end" style="width:260px;">จัดการ</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="6" class="text-center text-muted py-3">ยังไม่มีรอบเดินทาง</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r):
          $sd = th_date($r['start_date']); $ed = th_date($r['end_date']);
          $costTxt = $r['computed_cost_per_person'] !== null ? number_format((float)$r['computed_cost_per_person'],2) : '—';
          $beTxt   = $r['computed_break_even_pax'] ? (int)$r['computed_break_even_pax'] : '—';

          // คำนวณ default pax ให้แน่นอน:
          // 1) ถ้ามี assumed_pax > 0 ใช้ค่านั้น
          // 2) ถ้าไม่มีก็ใช้ booked_pax (จาก bookings)
          // 3) ถ้าไม่มีอีก ใช้ capacity
          $assumed = (int)($r['assumed_pax'] ?? 0);
          $booked  = (int)($r['booked_pax'] ?? 0);
          $cap     = (int)($r['capacity'] ?? 0);

          $default_pax = $assumed > 0 ? $assumed : ($booked > 0 ? $booked : $cap);
        ?>
          <tr>
            <td><?= htmlspecialchars("$sd — $ed") ?></td>
            <td class="text-center"><?= (int)$r['capacity'] ?></td>
            <td class="text-center"><?= (int)$r['booked_pax'] ?></td>
            <td class="text-center"><?= $costTxt ?></td>
            <td class="text-center"><?= $beTxt ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary btn-cost-set"
                type="button"
                data-tour_id="<?= (int)$tid ?>"
                data-dep_id="<?= (int)$r['dep_id'] ?>"

                data-hotel="<?= htmlspecialchars((string)($r['hotel_per_night'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                data-car="<?= htmlspecialchars((string)($r['car_per_day'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                data-guide="<?= htmlspecialchars((string)($r['guide_per_day'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                data-tickets="<?= htmlspecialchars((string)($r['tickets_per_person'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                data-food="<?= htmlspecialchars((string)($r['food_per_person'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                data-tips="<?= htmlspecialchars((string)($r['tips_per_person'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                data-others="<?= htmlspecialchars((string)($r['others_group'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                data-price="<?= htmlspecialchars((string)($r['price_per_person'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"

                data-booked="<?= $booked ?>"
                data-assumed="<?= $assumed ?>"
                data-capacity="<?= $cap ?>"
                data-pax="<?= $default_pax ?>"
              >กำหนดต้นทุน</button>

              <button class="btn btn-sm btn-outline-secondary btn-cost-view ms-1"
                type="button"
                data-dep_id="<?= (int)$r['dep_id'] ?>"
              >ดูต้นทุน</button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
