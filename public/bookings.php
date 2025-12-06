<?php
// bookings.php (Backoffice)
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../includes/guard.php';
require_backoffice();
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/csrf.php';

$user  = current_user();
$role  = strtolower(user_role());
$csrf  = csrf_token();

// -------- รับตัวกรอง --------
$status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$q      = isset($_GET['q'])      ? trim((string)$_GET['q'])      : '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 20;
$off    = ($page-1)*$per;

// -------- where --------
$where = [];
$bind  = [];

if ($status !== '' && in_array($status, ['pending','confirmed','paid','cancelled'], true)) {
  $where[] = "b.status = :status";
  $bind[':status'] = $status;
}
if ($q !== '') {
  $where[] = "(b.ref_code LIKE :kw OR b.tour_code LIKE :kw OR b.full_name LIKE :kw OR b.email LIKE :kw OR b.phone LIKE :kw)";
  $bind[':kw'] = "%".$q."%";
}
$whereSql = $where ? ("WHERE ".implode(" AND ", $where)) : "";

// -------- นับรวม --------
$stCnt = $pdo->prepare("SELECT COUNT(*) FROM bookings b $whereSql");
$stCnt->execute($bind);
$total = (int)$stCnt->fetchColumn();

// -------- ดึงหน้า --------
$sql = "
  SELECT b.*,
         t.name AS tour_name,
         d.start_date, d.end_date
  FROM bookings b
  LEFT JOIN tours t ON t.id = b.tour_id
  LEFT JOIN tour_departures d ON d.id = b.departure_id
  $whereSql
  ORDER BY b.created_at DESC
  LIMIT :per OFFSET :off
";
$st = $pdo->prepare($sql);
foreach ($bind as $k=>$v) { $st->bindValue($k, $v); }
$st->bindValue(':per', $per, PDO::PARAM_INT);
$st->bindValue(':off', $off, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// -------- ฟังก์ชันวันที่ไทย --------
function th_date($d){
  if(!$d) return '';
  $ts = strtotime($d);
  if($ts===false) return $d;
  $m = [1=>'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
  return (int)date('j',$ts).' '.$m[(int)date('n',$ts)].' '.((int)date('Y',$ts)+543);
}

render_header('Bookings');
?>
<meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
<script>window.USER_ROLE = <?= json_encode($role) ?>;</script>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">รายการจอง (<?= number_format($total) ?>)</h4>
  <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline-secondary">กลับแดชบอร์ด</a>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-sm-3">
    <select class="form-select" name="status">
      <option value="">ทุกสถานะ</option>
      <?php foreach (['pending'=>'รอดำเนินการ','confirmed'=>'ยืนยันแล้ว','paid'=>'ชำระแล้ว','cancelled'=>'ยกเลิก'] as $k=>$label): ?>
        <option value="<?= $k ?>" <?= $status===$k?'selected':'' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-sm-5">
    <input type="text" class="form-control" name="q" placeholder="ค้นหา: รหัสการจอง / โปรแกรม / ชื่อ / อีเมล / โทร"
           value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="col-sm-2 d-grid">
    <button class="btn btn-primary" type="submit">ค้นหา</button>
  </div>
</form>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:160px;">วันที่สร้าง</th>
            <th style="width:150px;">รหัสการจอง</th>
            <th>โปรแกรม</th>
            <th style="width:220px;">ช่วงเดินทาง</th>
            <th class="text-end" style="width:120px;">จำนวน/คน</th>
            <th class="text-end" style="width:140px;">ราคารวม</th>
            <th style="width:120px;">สถานะ</th>
            <th class="text-center" style="width:140px;">ยกเลิก/ลบ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r):
            $badge = [
              'pending'   => 'bg-warning text-dark',
              'confirmed' => 'bg-info text-dark',
              'paid'      => 'bg-success',
              'cancelled' => 'bg-secondary'
            ][$r['status']] ?? 'bg-secondary';
          ?>
            <tr data-row-id="<?= (int)$r['id'] ?>">
              <td><?= htmlspecialchars(th_date($r['created_at'])) ?></td>
              <td>
                <button class="btn btn-link p-0 booking-view" data-id="<?= (int)$r['id'] ?>" type="button">
                  <strong><?= htmlspecialchars($r['ref_code']) ?></strong>
                </button>
              </td>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($r['tour_code']) ?></div>
                <div class="text-muted small"><?= htmlspecialchars($r['tour_name'] ?: '-') ?></div>
              </td>
              <td>
                <?php
                  $sd = $r['start_date'] ? th_date($r['start_date']) : '-';
                  $ed = $r['end_date']   ? th_date($r['end_date'])   : '-';
                ?>
                <span><?= htmlspecialchars($sd.' — '.$ed) ?></span>
              </td>
              <td class="text-end"><?= (int)$r['qty'] ?></td>
              <td class="text-end"><?= $r['total_price']!==null ? number_format((float)$r['total_price'],2) : '—' ?></td>
              <td>
                <span id="status-badge-<?= (int)$r['id'] ?>" class="badge <?= $badge ?>" data-status="<?= htmlspecialchars($r['status']) ?>">
                  <?= htmlspecialchars($r['status']) ?>
                </span>
              </td>
              <td class="text-center">
                <button
                  class="btn btn-sm btn-outline-danger btn-cancel-booking"
                  type="button"
                  data-id="<?= (int)$r['id'] ?>"
                  data-ref="<?= htmlspecialchars($r['ref_code']) ?>"
                >
                  ลบการจอง
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">ไม่พบรายการ</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php
  // -------- pagination --------
  $pages = (int)ceil($total/$per);
  if ($pages>1):
  ?>
    <div class="card-footer">
      <nav aria-label="page">
        <ul class="pagination mb-0">
          <?php for($i=1;$i<=$pages;$i++):
            $u = '?'.http_build_query(['status'=>$status,'q'=>$q,'page'=>$i]);
          ?>
            <li class="page-item <?= $i===$page?'active':'' ?>">
              <a class="page-link" href="<?= $u ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>

<!-- Modal แสดงรายละเอียดการจอง -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">รายละเอียดการจอง</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="bookingModalBody">
        <div class="text-center text-muted py-4">กำลังโหลด...</div>
      </div>
    </div>
  </div>
</div>

<script>
// ===== Utilities =====
const STATUS_BADGE = {
  pending:   'badge bg-warning text-dark',
  confirmed: 'badge bg-info text-dark',
  paid:      'badge bg-success',
  cancelled: 'badge bg-secondary'
};

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}
function isJsonResponse(resp) {
  const ct = resp.headers.get('content-type') || '';
  return ct.includes('application/json');
}

// ===== ฟอร์มเพิ่มประวัติสถานะใน modal =====
function bindBookingStatusForm(){
  const form = document.getElementById('bookingStatusForm');
  if (!form) return;

  const bodyErr = document.getElementById('statusFormError');
  const list    = document.getElementById('statusHistoryList');
  const badge   = document.getElementById('bookingStatusBadge');

  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    bodyErr?.classList.add('d-none');

    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;

    try{
      const fd = new FormData(form);
      // ใส่ _csrf ถ้า form ไม่มี (กันไว้)
      if (!fd.get('_csrf')) fd.append('_csrf', getCsrfToken());

      const resp = await fetch('booking_status_add.php', {
        method:'POST',
        body: fd,
        headers: {
          'X-Requested-With':'fetch',
          'X-CSRF-Token': getCsrfToken()
        },
        credentials: 'same-origin'
      });

      let json;
      if (isJsonResponse(resp)) {
        json = await resp.json();
      } else {
        const text = await resp.text();
        throw new Error(text || 'Server returned non-JSON response');
      }
      if (!resp.ok || !json.ok) throw new Error(json && json.error ? json.error : 'บันทึกไม่สำเร็จ');

      // สร้าง element ประวัติใหม่
      const li = document.createElement('li'); li.className = 'list-group-item';
      const top = document.createElement('div'); top.className = 'd-flex justify-content-between';
      const left = document.createElement('div');
      const nm = document.createElement('div'); nm.className = 'fw-semibold'; nm.textContent = json.entry.actor_name || 'Backoffice';
      const tm = document.createElement('div'); tm.className = 'text-muted small'; tm.textContent = json.entry.created_at_th || '';
      left.appendChild(nm); left.appendChild(tm); top.appendChild(left);

      if (json.entry.new_status) {
        const b = document.createElement('span');
        b.className = (STATUS_BADGE[json.entry.new_status] || 'badge bg-secondary') + ' align-self-start';
        b.textContent = json.entry.new_status;
        top.appendChild(b);
      }
      const txt = document.createElement('div'); txt.className = 'mt-2';
      txt.textContent = json.entry.note || '';
      li.appendChild(top); li.appendChild(txt);

      // ลบ "ยังไม่มีบันทึก" ถ้ามี แล้วเพิ่มรายการใหม่
      const emptyItem = list.querySelector('.list-group-item.text-muted');
      if (emptyItem) emptyItem.remove();
      list.appendChild(li);

      // อัปเดต badge สถานะใน modal
      if (json.entry.new_status) {
        badge.textContent = json.entry.new_status;
        badge.className = STATUS_BADGE[json.entry.new_status] || 'badge bg-secondary';
        badge.setAttribute('data-status', json.entry.new_status);
      }

      form.note.value = '';
    }catch(err){
      const msg = (err && err.message) ? err.message : 'บันทึกไม่สำเร็จ';
      if (bodyErr){ bodyErr.textContent = msg; bodyErr.classList.remove('d-none'); }
    }finally{
      if (btn) btn.disabled = false;
    }
  }, { once: true });
}

// ===== ปุ่มยกเลิก/ลบ Booking =====
function bindCancelButtons(){
  document.querySelectorAll('.btn-cancel-booking').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id  = btn.getAttribute('data-id');
      const ref = btn.getAttribute('data-ref') || id;

      if (!confirm(`ยืนยันลบการจองหมายเลข ${ref} ?\nระบบจะคืนที่นั่งให้รอบเดินทางโดยอัตโนมัติ`)) return;

      let secret_confirm = '';
      if (window.USER_ROLE !== 'director') {
        secret_confirm = prompt('กรอกรหัสลับกลางเพื่อยืนยันการลบ:') || '';
        if (!secret_confirm) return;
      }

      btn.disabled = true;

      try{
        const fd = new FormData();
        fd.append('id', id);
        // IMPORTANT: ชื่อต้องเป็น "_csrf" ให้ตรงกับ csrf.php
        fd.append('_csrf', getCsrfToken());
        if (secret_confirm) fd.append('secret_confirm', secret_confirm);

        const resp = await fetch('booking_cancel.php', {
          method:'POST',
          body: fd,
          headers: {
            'X-Requested-With':'fetch',
            'X-CSRF-Token': getCsrfToken()
          },
          credentials: 'same-origin'
        });

        let json;
        if (isJsonResponse(resp)) {
          json = await resp.json();
        } else {
          const text = await resp.text();
          throw new Error(text || 'Server returned non-JSON response');
        }
        if (!resp.ok || !json.ok) {
          throw new Error((json && json.error) ? json.error : 'ลบไม่สำเร็จ');
        }

        // เอา row ออก
        const tr = btn.closest('tr');
        if (tr) tr.remove();

        // หากตารางว่าง แสดงแถว "ไม่พบรายการ"
        const tbody = document.querySelector('table tbody');
        if (tbody && tbody.querySelectorAll('tr').length === 0) {
          const empty = document.createElement('tr');
          empty.innerHTML = '<td colspan="8" class="text-center text-muted py-4">ไม่พบรายการ</td>';
          tbody.appendChild(empty);
        }

        // อัปเดตตัวเลขหัวข้อ (ลดลง 1 แบบเร็ว)
        const h = document.querySelector('h4.mb-0');
        if (h) {
          const m = h.textContent.match(/\(([\d,]+)\)/);
          if (m) {
            const cur = parseInt(m[1].replace(/,/g,''), 10) || 0;
            const next = cur > 0 ? cur - 1 : 0;
            h.textContent = `รายการจอง (${next.toLocaleString()})`;
          }
        }

        // แจ้งผล
        const toast = document.createElement('div');
        toast.className = 'alert alert-success mt-3';
        toast.textContent = `ลบการจอง ${ref} เรียบร้อย (คืนที่นั่งให้รอบเดินทางแล้ว)`;
        const card = document.querySelector('.card');
        if (card) card.before(toast);
        setTimeout(()=>toast.remove(), 3500);

      } catch(err){
        alert(err?.message || 'เกิดข้อผิดพลาดขณะลบ');
      } finally {
        btn.disabled = false;
      }
    });
  });
}

document.addEventListener('DOMContentLoaded', ()=>{
  bindCancelButtons();
});

document.querySelectorAll('.booking-view').forEach(btn => {
  btn.addEventListener('click', async () => {
    const id = btn.getAttribute('data-id');
    const body = document.getElementById('bookingModalBody');
    body.innerHTML = '<div class="text-center text-muted py-4">กำลังโหลด...</div>';
    const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
    modal.show();
    try{
      const resp = await fetch('booking_view.php?id='+encodeURIComponent(id), {
        headers: { 'X-Requested-With':'fetch' },
        credentials: 'same-origin'
      });
      const html = await resp.text();
      body.innerHTML = html;
      bindBookingStatusForm(); // ผูกฟอร์มหลังโหลด HTML
    }catch(e){
      body.innerHTML = '<div class="alert alert-danger">โหลดข้อมูลไม่สำเร็จ</div>';
    }
  });
});
</script>

<?php render_footer(); ?>
