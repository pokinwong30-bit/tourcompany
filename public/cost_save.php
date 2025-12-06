<?php
// backoffice/cost_save.php
require_once __DIR__.'/../includes/guard.php';
require_backoffice();
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

// ตรวจ CSRF
csrf_validate();

$tid   = (int)($_POST['tour_id'] ?? 0);
$depId = (int)($_POST['departure_id'] ?? 0);
if ($tid<=0 || $depId<=0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'ข้อมูลไม่ถูกต้อง']); exit; }

// รับค่า
$hotel   = (float)($_POST['hotel_per_night'] ?? 0);
$car     = (float)($_POST['car_per_day'] ?? 0);
$guide   = (float)($_POST['guide_per_day'] ?? 0);
$tickets = (float)($_POST['tickets_per_person'] ?? 0);
$food    = (float)($_POST['food_per_person'] ?? 0);
$tips    = (float)($_POST['tips_per_person'] ?? 0);
$others  = (float)($_POST['others_group'] ?? 0);
$pax     = (int)($_POST['assumed_pax'] ?? 0);
$price   = (float)($_POST['price_per_person'] ?? 0);

// ดึงวัน/คืน และ capacity
$st = $pdo->prepare("SELECT start_date, end_date, capacity FROM tour_departures WHERE id=:id AND tour_id=:tid");
$st->execute([':id'=>$depId, ':tid'=>$tid]);
$dep = $st->fetch(PDO::FETCH_ASSOC);
if (!$dep) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'ไม่พบรอบ']); exit; }

$days = max(1, (int)ceil((strtotime($dep['end_date']) - strtotime($dep['start_date']))/86400) + 1);
$nights = max(0, $days - 1);

if ($pax <= 0) $pax = (int)$dep['capacity'];

// คำนวน
$group_cost = ($car * $days) + ($guide * $days) + $others;
$pp_cost    = ($hotel * $nights) + $tickets + $food + $tips;
$total_for_assumed = $group_cost + ($pp_cost * $pax);
$cost_per_person   = $pax > 0 ? $total_for_assumed / $pax : 0;

// break-even pax
$margin = $price - $pp_cost; // กำไรต่อหัวหลังหัก variable
if ($margin > 0) {
  $break_even_pax = (int)ceil($group_cost / $margin);
} else {
  $break_even_pax = null; // คำนวณไม่ได้
}

// upsert
$sql = "
INSERT INTO tour_costs
(tour_id, departure_id, hotel_per_night, car_per_day, guide_per_day,
 tickets_per_person, food_per_person, tips_per_person, others_group,
 assumed_pax, price_per_person, computed_cost_per_person, computed_break_even_pax,
 created_at, updated_at)
VALUES
(:tid, :dep, :hotel, :car, :guide, :tickets, :food, :tips, :others, :pax, :price, :cpp, :bep, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  hotel_per_night=VALUES(hotel_per_night),
  car_per_day=VALUES(car_per_day),
  guide_per_day=VALUES(guide_per_day),
  tickets_per_person=VALUES(tickets_per_person),
  food_per_person=VALUES(food_per_person),
  tips_per_person=VALUES(tips_per_person),
  others_group=VALUES(others_group),
  assumed_pax=VALUES(assumed_pax),
  price_per_person=VALUES(price_per_person),
  computed_cost_per_person=VALUES(computed_cost_per_person),
  computed_break_even_pax=VALUES(computed_break_even_pax),
  updated_at=NOW()
";
$st2 = $pdo->prepare($sql);
$st2->execute([
  ':tid'=>$tid, ':dep'=>$depId,
  ':hotel'=>$hotel, ':car'=>$car, ':guide'=>$guide, ':tickets'=>$tickets, ':food'=>$food, ':tips'=>$tips,
  ':others'=>$others, ':pax'=>$pax, ':price'=>$price,
  ':cpp'=>$cost_per_person, ':bep'=>$break_even_pax
]);

echo json_encode([
  'ok'=>true,
  'cost_per_person'=>round($cost_per_person,2),
  'break_even_pax'=>$break_even_pax
], JSON_UNESCAPED_UNICODE);
