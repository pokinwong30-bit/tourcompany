<?php
// backoffice/cost_view.php
require_once __DIR__.'/../includes/guard.php';
require_backoffice();
require_once __DIR__.'/../includes/db.php';

$depId = (int)($_GET['departure_id'] ?? 0);
if ($depId<=0) { http_response_code(400); echo 'Invalid'; exit; }

$sql = "
SELECT d.id dep_id, d.start_date, d.end_date, d.capacity,
       t.code tour_code, t.name tour_name,
       c.*
FROM tour_departures d
JOIN tours t ON t.id = d.tour_id
LEFT JOIN tour_costs c ON c.departure_id = d.id
WHERE d.id = :id
";
$st = $pdo->prepare($sql);
$st->execute([':id'=>$depId]);
$R = $st->fetch(PDO::FETCH_ASSOC);
if (!$R) { http_response_code(404); echo '<div class="alert alert-warning">ไม่พบข้อมูล</div>'; exit; }

$days   = max(1, (int)ceil((strtotime($R['end_date']) - strtotime($R['start_date']))/86400) + 1);
$nights = max(0, $days - 1);

// ฟังก์ชันไทยวันที่ย่อ
function th_date($d){
  if(!$d) return '';
  $ts = strtotime($d); if($ts===false) return $d;
  $m = [1=>'ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
  return (int)date('j',$ts).' '.$m[(int)date('n',$ts)].' '.((int)date('Y',$ts)+543);
}

// คำนวณซ้ำเพื่อแสดง (กันกรณีเปลี่ยน assumed_pax etc.)
$hotel=$R['hotel_per_night']??0; $car=$R['car_per_day']??0; $guide=$R['guide_per_day']??0;
$tickets=$R['tickets_per_person']??0; $food=$R['food_per_person']??0; $tips=$R['tips_per_person']??0;
$others=$R['others_group']??0; $pax=$R['assumed_pax']??0; $price=$R['price_per_person']??0;

$group_cost = ($car*$days)+($guide*$days)+$others;
$pp_cost    = ($hotel*$nights)+$tickets+$food+$tips;
$total_for_assumed = $group_cost + ($pp_cost * max(0,(int)$pax));
$cpp = $pax>0 ? $total_for_assumed/$pax : 0;
$margin = $price - $pp_cost;
$be_pax = $margin>0 ? (int)ceil($group_cost/$margin) : null;
?>
<div class="mb-3">
  <div><strong>โปรแกรม:</strong> <?= htmlspecialchars($R['tour_code'].' — '.$R['tour_name']) ?></div>
  <div><strong>รอบ:</strong> <?= htmlspecialchars(th_date($R['start_date']).' — '.th_date($R['end_date'])) ?> (<?= (int)$days ?> วัน <?= (int)$nights ?> คืน) • ความจุ: <?= (int)$R['capacity'] ?> คน</div>
</div>

<div class="table-responsive">
  <table class="table table-sm table-bordered mb-3">
    <thead class="table-light"><tr><th colspan="4">รายการค่าใช้จ่ายที่ตั้งค่าไว้</th></tr></thead>
    <tbody>
      <tr><th style="width:35%;">โรงแรม/คืน (ต่อคน) × คืน (<?= (int)$nights ?>)</th><td><?= number_format((float)$hotel,2) ?></td><th>รวมฝั่งต่อหัว (variable)</th><td><?= number_format((float)$pp_cost,2) ?></td></tr>
      <tr><th>รถ/วัน (ต่อกรุ๊ป) × วัน (<?= (int)$days ?>)</th><td><?= number_format((float)$car,2) ?></td><th>อื่น ๆ ต่อกรุ๊ป (fixed)</th><td><?= number_format((float)$others,2) ?></td></tr>
      <tr><th>ไกด์/วัน (ต่อกรุ๊ป) × วัน (<?= (int)$days ?>)</th><td><?= number_format((float)$guide,2) ?></td><th>รวมฝั่งกรุ๊ป (fixed)</th><td><?= number_format((float)$group_cost,2) ?></td></tr>
      <tr><th>ตั๋ว (ต่อคน)</th><td><?= number_format((float)$tickets,2) ?></td><th>Assumed pax (ใช้คำนวณ)</th><td><?= (int)$pax ?></td></tr>
      <tr><th>อาหาร (ต่อคน)</th><td><?= number_format((float)$food,2) ?></td><th>ราคาขายต่อคน</th><td><?= number_format((float)$price,2) ?></td></tr>
      <tr><th>ทิป (ต่อคน)</th><td><?= number_format((float)$tips,2) ?></td><th>ต้นทุนต่อคน (calc)</th><td><strong><?= number_format((float)$cpp,2) ?></strong></td></tr>
      <tr><th>อื่น ๆ (ต่อกรุ๊ป)</th><td><?= number_format((float)$others,2) ?></td><th>จุดคุ้มทุน (pax)</th><td><strong><?= $be_pax!==null ? (int)$be_pax : 'คำนวณไม่ได้' ?></strong></td></tr>
    </tbody>
  </table>
</div>
