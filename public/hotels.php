<?php
// backoffice/hotels.php
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../includes/guard.php';
require_backoffice();
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/csrf.php';
require_once __DIR__.'/../includes/audit.php';

$user = current_user();
$role = strtolower(user_role());
$csrf = csrf_token();

function require_password_confirm_if_needed(): bool {
  if (can('bypass_confirm')) return true; // Director ข้าม
  $secret = $_POST['secret_confirm'] ?? '';
  if ($secret === '') return false;
  return verify_shared_secret($secret);
}

$errors = [];
$info   = '';

// ===== Handle POST (create/update/delete) =====
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  try {
    csrf_validate(); // ตรงกับ includes/csrf.php

    $action = $_POST['action'] ?? '';

    if (!require_password_confirm_if_needed()) {
      throw new Exception('ต้องยืนยันรหัสลับกลาง');
    }

    if ($action === 'create' || $action === 'update') {
      $id           = (int)($_POST['id'] ?? 0);
      $name_th      = trim((string)($_POST['name_th'] ?? ''));
      $name_en      = trim((string)($_POST['name_en'] ?? ''));
      $address      = trim((string)($_POST['address'] ?? ''));
      $contact_name = trim((string)($_POST['contact_name'] ?? ''));
      $phone        = trim((string)($_POST['phone'] ?? ''));
      $stars        = (int)($_POST['stars'] ?? 0);

      if ($name_th === '') throw new Exception('กรุณากรอกชื่อโรงแรมภาษาไทย');
      if ($stars < 0 || $stars > 5) throw new Exception('ระดับดาวต้องอยู่ระหว่าง 0-5');

      if ($action === 'create') {
        $st = $pdo->prepare("INSERT INTO hotels
          (name_th, name_en, address, contact_name, phone, stars, created_at, updated_at)
          VALUES (:th,:en,:addr,:contact,:phone,:stars, NOW(), NOW())");
        $st->execute([
          ':th'=>$name_th, ':en'=>$name_en, ':addr'=>$address,
          ':contact'=>$contact_name, ':phone'=>$phone, ':stars'=>$stars
        ]);
        $newId = (int)$pdo->lastInsertId();

        try { audit_log('hotel_create', ['hotel_id'=>$newId,'name_th'=>$name_th,'stars'=>$stars]); } catch(Throwable $e){}

        $info = 'เพิ่มโรงแรมเรียบร้อย';
      } else {
        if ($id<=0) throw new Exception('ไม่พบรหัสรายการ');
        $st = $pdo->prepare("UPDATE hotels SET
          name_th=:th, name_en=:en, address=:addr, contact_name=:contact,
          phone=:phone, stars=:stars, updated_at=NOW()
          WHERE id=:id");
        $st->execute([
          ':th'=>$name_th, ':en'=>$name_en, ':addr'=>$address,
          ':contact'=>$contact_name, ':phone'=>$phone, ':stars'=>$stars, ':id'=>$id
        ]);

        try { audit_log('hotel_update', ['hotel_id'=>$id,'name_th'=>$name_th,'stars'=>$stars]); } catch(Throwable $e){}

        $info = 'บันทึกการแก้ไขเรียบร้อย';
      }

    } elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id<=0) throw new Exception('ไม่พบรหัสรายการ');

      // ถ้าในอนาคตมี FK อ้างอิง (เช่น rooms/tours) ให้ตรวจเช็คก่อนลบ
      $pdo->prepare("DELETE FROM hotels WHERE id=:id")->execute([':id'=>$id]);

      try { audit_log('hotel_delete', ['hotel_id'=>$id]); } catch(Throwable $e){}

      $info = 'ลบโรงแรมเรียบร้อย';
    } else {
      throw new Exception('คำสั่งไม่ถูกต้อง');
    }

    // Redirect ป้องกัน Post-Resubmit (PRG)
    header("Location: ".$_SERVER['PHP_SELF']."?ok=1");
    exit;

  } catch (Throwable $e) {
    $errors[] = $e->getMessage();
  }
}

// ===== ดึงรายการทั้งหมด =====
$st = $pdo->query("SELECT * FROM hotels ORDER BY name_th ASC, id DESC");
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

render_header('Hotels');
?>
<meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
<script>window.USER_ROLE = <?= json_encode($role) ?>;</script>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">จัดการโรงแรม</h4>
  <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/dashboard.php">กลับแดชบอร์ด</a>
</div>

<?php if (isset($_GET['ok'])): ?>
  <div class="alert alert-success">ดำเนินการสำเร็จ</div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $er): ?>
        <li><?= htmlspecialchars($er) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
  <div class="card-body">
    <h5 class="card-title mb-3">เพิ่ม/แก้ไข โรงแรม</h5>
    <form method="post" id="hotelForm" class="row g-3">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create" id="formAction">
      <input type="hidden" name="id" value="" id="hotelId">

      <div class="col-md-6">
        <label class="form-label">ชื่อโรงแรม (ไทย) <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="name_th" id="name_th" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">ชื่อโรงแรม (อังกฤษ)</label>
        <input type="text" class="form-control" name="name_en" id="name_en">
      </div>

      <div class="col-md-12">
        <label class="form-label">ที่อยู่โรงแรม</label>
        <textarea class="form-control" name="address" id="address" rows="2"></textarea>
      </div>

      <div class="col-md-6">
        <label class="form-label">ชื่อผู้ติดต่อ</label>
        <input type="text" class="form-control" name="contact_name" id="contact_name">
      </div>

      <div class="col-md-3">
        <label class="form-label">เบอร์โทรโรงแรม</label>
        <input type="text" class="form-control" name="phone" id="phone">
      </div>

      <div class="col-md-3">
        <label class="form-label">ระดับดาวของโรงแรม</label>
        <select class="form-select" name="stars" id="stars">
          <?php for($i=0;$i<=5;$i++): ?>
            <option value="<?= $i ?>"><?= $i ?> ดาว</option>
          <?php endfor; ?>
        </select>
      </div>

      <?php if (!can('bypass_confirm')): ?>
        <div class="col-12">
          <label class="form-label">รหัสลับกลาง (สำหรับยืนยันการเพิ่ม/แก้ไข)</label>
          <input type="password" class="form-control" name="secret_confirm" placeholder="กรอกรหัสลับกลาง">
        </div>
      <?php endif; ?>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit" id="btnSubmit">บันทึก</button>
        <button class="btn btn-outline-secondary" type="button" id="btnResetForm">ล้างฟอร์ม</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:70px;">#</th>
            <th>ชื่อ (ไทย)</th>
            <th>ชื่อ (อังกฤษ)</th>
            <th>ติดต่อ</th>
            <th>โทร</th>
            <th class="text-center" style="width:100px;">ดาว</th>
            <th class="text-center" style="width:160px;">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">ยังไม่มีข้อมูล</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $i=>$h): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($h['name_th']) ?></td>
                <td><?= htmlspecialchars($h['name_en'] ?? '-') ?></td>
                <td><?= htmlspecialchars($h['contact_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($h['phone'] ?? '-') ?></td>
                <td class="text-center"><?= (int)$h['stars'] ?></td>
                <td class="text-center">
                  <button
                    class="btn btn-sm btn-outline-primary btn-edit"
                    type="button"
                    data-id="<?= (int)$h['id'] ?>"
                    data-name_th="<?= htmlspecialchars($h['name_th'], ENT_QUOTES, 'UTF-8') ?>"
                    data-name_en="<?= htmlspecialchars($h['name_en'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-address="<?= htmlspecialchars($h['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-contact_name="<?= htmlspecialchars($h['contact_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-phone="<?= htmlspecialchars($h['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-stars="<?= (int)$h['stars'] ?>"
                  >แก้ไข</button>

                  <form method="post" class="d-inline-block ms-1 frm-del" onsubmit="return confirmDelete(this)">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                    <?php if (!can('bypass_confirm')): ?>
                      <input type="password" class="form-control form-control-sm d-inline-block" name="secret_confirm" placeholder="รหัสลับ" style="width:110px;">
                    <?php endif; ?>
                    <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function confirmDelete(frm){
  return confirm('ยืนยันลบโรงแรมนี้?');
}

const form   = document.getElementById('hotelForm');
const action = document.getElementById('formAction');
const idFld  = document.getElementById('hotelId');

function fillForm(d){
  action.value = 'update';
  idFld.value  = d.id;
  document.getElementById('name_th').value = d.name_th || '';
  document.getElementById('name_en').value = d.name_en || '';
  document.getElementById('address').value = d.address || '';
  document.getElementById('contact_name').value = d.contact_name || '';
  document.getElementById('phone').value = d.phone || '';
  document.getElementById('stars').value = d.stars || 0;

  document.getElementById('btnSubmit').textContent = 'บันทึกการแก้ไข';
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm(){
  action.value = 'create';
  idFld.value  = '';
  form.reset();
  document.getElementById('btnSubmit').textContent = 'บันทึก';
}

document.getElementById('btnResetForm').addEventListener('click', resetForm);

document.querySelectorAll('.btn-edit').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    fillForm({
      id: btn.dataset.id,
      name_th: btn.dataset.name_th,
      name_en: btn.dataset.name_en,
      address: btn.dataset.address,
      contact_name: btn.dataset.contact_name,
      phone: btn.dataset.phone,
      stars: btn.dataset.stars
    });
  });
});
</script>

<?php render_footer(); ?>
