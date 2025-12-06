<?php
// backoffice/costsheets.php
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../includes/guard.php';
require_backoffice();
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/csrf.php';

$csrf = csrf_token();

// ดึงโปรแกรมทัวร์ทั้งหมด
$sql = "SELECT t.id, t.code AS tour_code, t.name AS tour_name
        FROM tours t
        ORDER BY t.code ASC, t.id DESC";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

render_header('Cost Sheets');
?>
<meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Cost Sheets — โปรแกรมทัวร์</h4>
  <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline-secondary">กลับแดชบอร์ด</a>
</div>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="toursTable">
        <thead class="table-light">
          <tr>
            <th style="width:180px;">รหัสโปรแกรม</th>
            <th>ชื่อโปรแกรม</th>
            <th style="width:140px;"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="tour-row" data-tour-id="<?= (int)$r['id'] ?>">
              <td>
                <button class="btn btn-link p-0 tour-toggle" type="button" data-tour-id="<?= (int)$r['id'] ?>">
                  <strong><?= htmlspecialchars($r['tour_code'] ?? '—') ?></strong>
                </button>
              </td>
              <td><?= htmlspecialchars($r['tour_name'] ?? '-') ?></td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-primary tour-toggle" type="button" data-tour-id="<?= (int)$r['id'] ?>">
                  แสดงรอบเดินทาง
                </button>
              </td>
            </tr>
            <!-- แถวซ่อน sub-table -->
            <tr class="tour-sub d-none" id="tour-sub-<?= (int)$r['id'] ?>">
              <td colspan="3">
                <div class="p-3">
                  <div class="text-muted small">กำลังโหลดรอบเดินทาง...</div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (!$rows): ?>
            <tr><td colspan="3" class="text-center text-muted py-4">ยังไม่มีโปรแกรมทัวร์</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal: ฟอร์มบันทึกต้นทุน -->
<div class="modal fade" id="costFormModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="costForm">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">กำหนดต้นทุนรอบเดินทาง</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="tour_id" id="cf_tour_id">
          <input type="hidden" name="departure_id" id="cf_departure_id">

          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">โรงแรม / คืน (ต่อคน)</label>
              <input type="number" step="0.01" class="form-control" name="hotel_per_night" id="cf_hotel">
            </div>
            <div class="col-md-3">
              <label class="form-label">รถ / วัน (ต่อกรุ๊ป)</label>
              <input type="number" step="0.01" class="form-control" name="car_per_day" id="cf_car">
            </div>
            <div class="col-md-3">
              <label class="form-label">ไกด์ / วัน (ต่อกรุ๊ป)</label>
              <input type="number" step="0.01" class="form-control" name="guide_per_day" id="cf_guide">
            </div>
            <div class="col-md-3">
              <label class="form-label">ตั๋ว (ต่อคน)</label>
              <input type="number" step="0.01" class="form-control" name="tickets_per_person" id="cf_tickets">
            </div>

            <div class="col-md-3">
              <label class="form-label">อาหาร (ต่อคน)</label>
              <input type="number" step="0.01" class="form-control" name="food_per_person" id="cf_food">
            </div>
            <div class="col-md-3">
              <label class="form-label">ทิป (ต่อคน)</label>
              <input type="number" step="0.01" class="form-control" name="tips_per_person" id="cf_tips">
            </div>
            <div class="col-md-3">
              <label class="form-label">อื่น ๆ (ต่อกรุ๊ป)</label>
              <input type="number" step="0.01" class="form-control" name="others_group" id="cf_others">
            </div>
            <div class="col-md-3">
              <label class="form-label">จำนวนคน (ใช้คำนวน)</label>
              <input type="number" min="1" class="form-control" name="assumed_pax" id="cf_pax">
            </div>

            <div class="col-md-3">
              <label class="form-label">ราคาขายต่อคน</label>
              <input type="number" step="0.01" class="form-control" name="price_per_person" id="cf_price">
            </div>
          </div>

          <div class="alert alert-info mt-3" id="cf_hint" style="display:none;"></div>
          <div class="alert alert-danger mt-3 d-none" id="cf_error"></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" type="submit">บันทึก</button>
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">ปิด</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: ตารางสรุปต้นทุน -->
<div class="modal fade" id="costViewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">ต้นทุนรอบเดินทาง</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="costViewBody">
        <div class="text-center text-muted py-4">กำลังโหลด...</div>
      </div>
    </div>
  </div>
</div>

<script>
// ==== CSRF helpers (ตามแนว bookings.php) ====
function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}
function isJsonResponse(resp) {
  const ct = resp.headers.get('content-type') || '';
  return ct.includes('application/json');
}

// Toggle sub table (รอบเดินทาง)
document.querySelectorAll('.tour-toggle').forEach(btn=>{
  btn.addEventListener('click', async ()=>{
    const tourId = btn.getAttribute('data-tour-id');
    const trSub  = document.getElementById('tour-sub-'+tourId);
    if (!trSub) return;

    const isShown = !trSub.classList.contains('d-none');
    if (isShown) { trSub.classList.add('d-none'); return; }

    trSub.classList.remove('d-none');
    const wrap = trSub.querySelector('td > div');
    wrap.innerHTML = '<div class="text-muted small">กำลังโหลดรอบเดินทาง...</div>';

    try{
      const resp = await fetch('costs_departures.php?tour_id='+encodeURIComponent(tourId), {
        headers: {'X-Requested-With':'fetch'},
        credentials: 'same-origin'
      });
      const html = await resp.text();
      wrap.innerHTML = html;

      bindDepartureButtons(); // bind ปุ่มใน sub-table
    }catch(e){
      wrap.innerHTML = '<div class="alert alert-danger">โหลดรอบเดินทางไม่สำเร็จ</div>';
    }
  });
});

function bindDepartureButtons(){
  // ปุ่มเปิดฟอร์มกำหนดต้นทุน
  document.querySelectorAll('.btn-cost-set').forEach(b=>{
    b.addEventListener('click', ()=>{
      const m = new bootstrap.Modal(document.getElementById('costFormModal'));

      // เติมค่าเริ่มต้น
      document.getElementById('cf_tour_id').value = b.dataset.tour_id;
      document.getElementById('cf_departure_id').value = b.dataset.dep_id;

      // ===== ใส่จำนวนคน (assumed_pax) ให้ชัวร์ =====
      // ลำดับ: data-pax → data-assumed → data-booked → data-capacity
      const paxFromBtn =
        (b.dataset.pax && Number(b.dataset.pax)) ||
        (b.dataset.assumed && Number(b.dataset.assumed)) ||
        (b.dataset.booked && Number(b.dataset.booked)) ||
        (b.dataset.capacity && Number(b.dataset.capacity)) || '';

      document.getElementById('cf_pax').value = paxFromBtn;

      document.getElementById('cf_error').classList.add('d-none');
      document.getElementById('cf_hint').style.display = 'none';

      // prefill ถ้ามีค่าเดิม
      const map = {
        hotel: 'cf_hotel', car: 'cf_car', guide:'cf_guide', tickets:'cf_tickets',
        food:'cf_food', tips:'cf_tips', others:'cf_others', price:'cf_price'
      };
      Object.entries(map).forEach(([k,id])=>{
        const v = b.dataset[k];
        if (typeof v !== 'undefined') document.getElementById(id).value = v;
      });

      m.show();
    });
  });

  // ปุ่มดูต้นทุน (เหมือนเดิม)
  document.querySelectorAll('.btn-cost-view').forEach(b=>{
    b.addEventListener('click', async ()=>{
      const depId = b.dataset.dep_id;
      const body  = document.getElementById('costViewBody');
      body.innerHTML = '<div class="text-center text-muted py-4">กำลังโหลด...</div>';

      const m = new bootstrap.Modal(document.getElementById('costViewModal'));
      m.show();

      try{
        const resp = await fetch('cost_view.php?departure_id='+encodeURIComponent(depId), {
          headers: {'X-Requested-With':'fetch'},
          credentials: 'same-origin'
        });
        const html = await resp.text();
        body.innerHTML = html;
      }catch(e){
        body.innerHTML = '<div class="alert alert-danger">โหลดข้อมูลไม่สำเร็จ</div>';
      }
    });
  });
}

// submit ฟอร์มบันทึกต้นทุน (POST + CSRF เช่นเดียวกับ bookings.php)
document.getElementById('costForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData(e.currentTarget);
  const err = document.getElementById('cf_error');
  const hint= document.getElementById('cf_hint');
  err.classList.add('d-none');
  hint.style.display = 'none';

  // แนวเดียวกับ bookings.php: ถ้า form ไม่มีแฝง _csrf ให้เติมเอง
  if (!fd.get('_csrf')) fd.append('_csrf', getCsrfToken());

  try{
    const resp = await fetch('cost_save.php', {
      method: 'POST',
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
      throw new Error(json && json.error ? json.error : 'บันทึกไม่สำเร็จ');
    }

    // แสดง hint คำนวณคร่าว ๆ
    hint.innerHTML = `
      บันทึกแล้ว • ต้นทุนต่อคนโดยประมาณ: <strong>${Number(json.cost_per_person).toLocaleString()}</strong>
      ${json.break_even_pax !== null ? ` • จุดคุ้มทุน ~ <strong>${json.break_even_pax}</strong> คน` : ` • ไม่สามารถคำนวณจุดคุ้มทุน (ราคาขาย ≤ ต้นทุนผันแปรต่อคน)` }
    `;
    hint.style.display = '';

    // ปิด modal หลัง 1.2s
    setTimeout(()=>{ bootstrap.Modal.getInstance(document.getElementById('costFormModal'))?.hide(); }, 1200);

    // รีโหลด sub-table ของทัวร์นั้นเพื่ออัปเดตปุ่ม/สถานะ
    const tourId = document.getElementById('cf_tour_id').value;
    const wrap = document.querySelector('#tour-sub-'+tourId+' td > div');
    if (wrap) {
      const resp2 = await fetch('costs_departures.php?tour_id='+encodeURIComponent(tourId), {
        headers: {'X-Requested-With':'fetch'},
        credentials: 'same-origin'
      });
      wrap.innerHTML = await resp2.text();
      bindDepartureButtons();
    }

  }catch(e2){
    err.textContent = e2.message || 'บันทึกไม่สำเร็จ';
    err.classList.remove('d-none');
  }
});

// bind ครั้งแรก (ปุ่ม toggle)
document.addEventListener('DOMContentLoaded', ()=> {
  // ไม่มีปุ่มอื่นต้อง bind ตอนโหลด นอกจาก tour-toggle ซึ่ง bind ด้านบนแล้ว
});
</script>

<?php render_footer(); ?>
