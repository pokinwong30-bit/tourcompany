<?php
// booking_view.php (AJAX modal content + history list)
require_once __DIR__.'/../includes/guard.php';
require_backoffice();
require_once __DIR__.'/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf_token'];

$id = (int)($_GET['id'] ?? 0);
if ($id<=0) { http_response_code(400); exit('Invalid ID'); }

$sql = "
  SELECT b.*,
         t.name AS tour_name,
         d.start_date, d.end_date
  FROM bookings b
  LEFT JOIN tours t ON t.id = b.tour_id
  LEFT JOIN tour_departures d ON d.id = b.departure_id
  WHERE b.id = :id
  LIMIT 1
";
$st = $pdo->prepare($sql);
$st->execute([':id'=>$id]);
$b = $st->fetch(PDO::FETCH_ASSOC);
if (!$b) { http_response_code(404); exit('Not found'); }

function th_date($d){
  if(!$d) return '';
  $ts=strtotime($d); if($ts===false) return $d;
  $m=[1=>'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
  return (int)date('j',$ts).' '.$m[(int)date('n',$ts)].' '.((int)date('Y',$ts)+543).' '.date('H:i',$ts).' น.';
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// โหลดประวัติ (ถ้ามีตาราง)
$hist = [];
try {
  $stH = $pdo->prepare("SELECT * FROM booking_status_history WHERE booking_id = :id ORDER BY created_at ASC, id ASC");
  $stH->execute([':id'=>$id]);
  $hist = $stH->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
  $hist = []; // ไม่มีตารางก็ข้าม
}

// map badge
$badgeMap = [
  'pending'   => 'bg-warning text-dark',
  'confirmed' => 'bg-info text-dark',
  'paid'      => 'bg-success',
  'cancelled' => 'bg-secondary',
];
$badgeClass = $badgeMap[$b['status']] ?? 'bg-secondary';
?>
<div class="row g-3">
  <div class="col-12 col-lg-7">
    <div class="card border-0">
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <tbody>
            <tr>
              <th class="text-muted">รหัสการจอง</th>
              <td><strong><?= h($b['ref_code']) ?></strong></td>
            </tr>
            <tr>
              <th class="text-muted">สถานะ</th>
              <td>
                <span id="bookingStatusBadge"
                      class="badge <?= $badgeClass ?>"
                      data-status="<?= h($b['status']) ?>">
                  <?= h($b['status']) ?>
                </span>
              </td>
            </tr>
            <tr><th class="text-muted">สร้างเมื่อ</th><td><?= h(th_date($b['created_at'])) ?></td></tr>
            <tr><th class="text-muted">โปรแกรม</th><td><?= h($b['tour_code']) ?> — <span class="text-muted"><?= h($b['tour_name'] ?: '-') ?></span></td></tr>
            <tr><th class="text-muted">ช่วงเดินทาง</th>
              <td>
                <?php $sd=$b['start_date']?th_date($b['start_date']):'-'; $ed=$b['end_date']?th_date($b['end_date']):'-'; ?>
                <?= h($sd.' — '.$ed) ?>
              </td>
            </tr>
            <tr><th class="text-muted">จำนวน</th><td><?= (int)$b['qty'] ?> คน</td></tr>
            <tr>
              <th class="text-muted">ราคา</th>
              <td>
                รายบุคคล: <?= $b['unit_price']!==null ? number_format((float)$b['unit_price'],2) : '—' ?><br>
                รวม: <strong><?= $b['total_price']!==null ? number_format((float)$b['total_price'],2) : '—' ?></strong>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-5">
    <div class="card border-0">
      <div class="card-header bg-light fw-semibold">ข้อมูลผู้จอง</div>
      <div class="card-body">
        <div class="mb-1"><strong><?= h($b['full_name']) ?></strong></div>
        <div class="text-muted small">อีเมล: <?= h($b['email']) ?></div>
        <div class="text-muted small">โทร: <?= h($b['phone']) ?></div>
        <?php if (!empty($b['contact_time'])): ?>
          <div class="text-muted small">เวลาติดต่อ: <?= h(substr($b['contact_time'],0,5)) ?></div>
        <?php endif; ?>
        <?php if (!empty($b['note'])): ?>
          <div class="mt-2 small"><span class="text-muted">หมายเหตุลูกค้า:</span> <?= nl2br(h($b['note'])) ?></div>
        <?php endif; ?>
        <?php if (!empty($b['member_id'])): ?>
          <div class="mt-2 small text-muted">สมาชิก #<?= (int)$b['member_id'] ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- บันทึกสถานะ + ฟอร์ม -->
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-light fw-semibold">บันทึกสถานะ</div>
      <div class="card-body">
        <ul id="statusHistoryList" class="list-group mb-3">
          <?php if ($hist): foreach ($hist as $hrow): ?>
            <li class="list-group-item">
              <div class="d-flex justify-content-between">
                <div>
                  <div class="fw-semibold"><?= h($hrow['actor_name'] ?: 'System') ?></div>
                  <div class="text-muted small"><?= h(th_date($hrow['created_at'])) ?></div>
                </div>
                <?php if (!empty($hrow['new_status'])):
                  $bc = $badgeMap[$hrow['new_status']] ?? 'bg-secondary'; ?>
                  <span class="badge <?= $bc ?> align-self-start"><?= h($hrow['new_status']) ?></span>
                <?php endif; ?>
              </div>
              <div class="mt-2"><?= nl2br(h($hrow['note'])) ?></div>
            </li>
          <?php endforeach; else: ?>
            <li class="list-group-item text-muted">ยังไม่มีบันทึก</li>
          <?php endif; ?>
        </ul>

        <form id="bookingStatusForm" class="mt-2">
          <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
          <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
          <label class="form-label">เพิ่มบันทึก (พิมพ์ “ชำระเงินเรียบร้อยแล้ว” เพื่อปิดเคสและเปลี่ยนสถานะเป็นโอนเงิน)</label>
          <textarea class="form-control" name="note" rows="3" required></textarea>
          <div class="text-end mt-2">
            <button class="btn btn-primary" type="submit">บันทึก</button>
          </div>
          <div class="small text-danger mt-2 d-none" id="statusFormError"></div>
        </form>
      </div>
    </div>
  </div>
</div>
