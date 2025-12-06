<?php
// public/transportations.php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/guard.php';
require_backoffice();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/audit.php';

$user = current_user();
$role = strtolower(user_role());
$csrf = csrf_token();

function require_password_confirm_if_needed(): bool
{
    if (can('bypass_confirm')) return true; // Director ข้าม
    $secret = $_POST['secret_confirm'] ?? '';
    if ($secret === '') return false;
    return verify_shared_secret($secret);
}

// โฟลเดอร์อัปโหลดจริง (อยู่นอก public/)
$UPLOAD_DIR = __DIR__ . '/../uploads/transportations';
if (!is_dir($UPLOAD_DIR)) {
    @mkdir($UPLOAD_DIR, 0775, true);
}

// BASE URL ที่ใช้ลิงก์กลับ dashboard ได้
$BASE = function_exists('public_base_url') ? public_base_url() : (defined('BASE_URL') ? BASE_URL : '');

// ===== NOTE สำคัญเรื่องพาธไฟล์ =====
// ผู้ใช้กำหนดให้ "ลิงก์ในตาราง" ใช้รูปแบบ ../../uploads/... จากหน้า public/ นี้
// เราจึงกำหนด PREFIX ตรงนี้เพื่อใช้ซ้ำทั้ง 'ลิงก์' และ 'popup'
$PUBLIC_PREFIX = '../../';

$errors = [];
$info   = '';

// ---------- Handle POST ----------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        csrf_validate();

        if (!require_password_confirm_if_needed()) {
            throw new Exception('ต้องยืนยันรหัสลับกลาง');
        }

        $action         = $_POST['action'] ?? '';
        $id             = (int)($_POST['id'] ?? 0);
        $license_plate  = trim((string)($_POST['license_plate'] ?? ''));
        $vehicle_type   = trim((string)($_POST['vehicle_type'] ?? ''));
        $brand          = trim((string)($_POST['brand'] ?? ''));
        $owner_fullname = trim((string)($_POST['owner_fullname'] ?? ''));

        if (in_array($action, ['create', 'update'], true)) {
            if ($license_plate === '') throw new Exception('กรุณากรอกทะเบียนรถ');
            if ($vehicle_type === '')  throw new Exception('กรุณากรอกรูปแบบ Vehicle');

            // อัปโหลดไฟล์เล่มทะเบียน (ไม่บังคับ)
            $uploadedPath = null;
            if (isset($_FILES['reg_book_file']) && is_uploaded_file($_FILES['reg_book_file']['tmp_name'])) {
                $tmp  = $_FILES['reg_book_file']['tmp_name'];
                $name = $_FILES['reg_book_file']['name'];
                $size = (int)$_FILES['reg_book_file']['size'];

                if ($size > 10 * 1024 * 1024) { // 10MB
                    throw new Exception('ไฟล์ใหญ่เกินไป (สูงสุด 10MB)');
                }

                // ตรวจชนิดไฟล์เบื้องต้น
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($tmp) ?: 'application/octet-stream';
                $allowed = [
                    'application/pdf' => 'pdf',
                    'image/jpeg'      => 'jpg',
                    'image/png'       => 'png',
                    'image/webp'      => 'webp'
                ];
                if (!isset($allowed[$mime])) {
                    throw new Exception('ชนิดไฟล์ไม่รองรับ (pdf, jpg, png, webp)');
                }

                // สร้างชื่อไฟล์ปลอดภัย
                $slugPlate = preg_replace('/[^A-Za-z0-9\-]+/u', '-', $license_plate);
                $ext = $allowed[$mime];
                $newName = 'regbook_' . $slugPlate . '_' . date('Ymd_His') . '.' . $ext;
                $dest = rtrim($UPLOAD_DIR, '/') . '/' . $newName;

                if (!move_uploaded_file($tmp, $dest)) {
                    throw new Exception('อัปโหลดไฟล์ไม่สำเร็จ');
                }

                // เก็บ path แบบ 'uploads/transportations/ชื่อไฟล์.ext' ตามที่ใช้อยู่
                $uploadedPath = 'uploads/transportations/' . $newName;
            }

            if ($action === 'create') {
                $sql = "INSERT INTO transportations
          (license_plate, vehicle_type, brand, owner_fullname, reg_book_file, created_at, updated_at)
          VALUES (:lp, :vt, :bd, :own, :file, NOW(), NOW())";
                $st = $pdo->prepare($sql);
                $st->execute([
                    ':lp' => $license_plate,
                    ':vt' => $vehicle_type,
                    ':bd' => $brand,
                    ':own' => $owner_fullname,
                    ':file' => $uploadedPath
                ]);

                $newId = (int)$pdo->lastInsertId();
                try {
                    audit_log('transport_create', ['transport_id' => $newId, 'license_plate' => $license_plate]);
                } catch (Throwable $e) {
                }
                header("Location: " . $_SERVER['PHP_SELF'] . "?ok=1");
                exit;
            } else { // update
                if ($id <= 0) throw new Exception('ไม่พบรหัสรายการ');

                // ดึงของเดิมเพื่อเช็ค/ลบไฟล์เก่าเมื่ออัปใหม่
                $cur = $pdo->prepare("SELECT reg_book_file FROM transportations WHERE id=:id");
                $cur->execute([':id' => $id]);
                $prev = $cur->fetch(PDO::FETCH_ASSOC);

                $fileToSave = $prev['reg_book_file'] ?? null;
                if ($uploadedPath !== null) {
                    // ลบไฟล์เดิมถ้ามี
                    if (!empty($fileToSave)) {
                        $old = __DIR__ . '/../' . ltrim($fileToSave, '/');
                        if (is_file($old)) @unlink($old);
                    }
                    $fileToSave = $uploadedPath;
                }

                $sql = "UPDATE transportations SET
                  license_plate=:lp, vehicle_type=:vt, brand=:bd, owner_fullname=:own,
                  reg_book_file=:file, updated_at=NOW()
                WHERE id=:id";
                $st = $pdo->prepare($sql);
                $st->execute([
                    ':lp' => $license_plate,
                    ':vt' => $vehicle_type,
                    ':bd' => $brand,
                    ':own' => $owner_fullname,
                    ':file' => $fileToSave,
                    ':id' => $id
                ]);

                try {
                    audit_log('transport_update', ['transport_id' => $id, 'license_plate' => $license_plate]);
                } catch (Throwable $e) {
                }
                header("Location: " . $_SERVER['PHP_SELF'] . "?ok=1");
                exit;
            }
        } elseif ($action === 'delete') {
            if ($id <= 0) throw new Exception('ไม่พบรหัสรายการ');

            // ลบไฟล์แนบถ้ามี
            $cur = $pdo->prepare("SELECT reg_book_file FROM transportations WHERE id=:id");
            $cur->execute([':id' => $id]);
            $prev = $cur->fetch(PDO::FETCH_ASSOC);
            if ($prev && !empty($prev['reg_book_file'])) {
                $old = __DIR__ . '/../' . ltrim($prev['reg_book_file'], '/');
                if (is_file($old)) @unlink($old);
            }

            $pdo->prepare("DELETE FROM transportations WHERE id=:id")->execute([':id' => $id]);

            try {
                audit_log('transport_delete', ['transport_id' => $id]);
            } catch (Throwable $e) {
            }
            header("Location: " . $_SERVER['PHP_SELF'] . "?ok=1");
            exit;
        } else {
            throw new Exception('คำสั่งไม่ถูกต้อง');
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

// ---------- ดึงรายการทั้งหมด ----------
$rows = $pdo->query("SELECT * FROM transportations ORDER BY license_plate ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

render_header('Transportations');
?>
<meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
<script>
    window.USER_ROLE = <?= json_encode($role) ?>;
</script>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">จัดการข้อมูล Transportation</h4>
        <a class="btn btn-outline-secondary" href="<?= $BASE ?>/dashboard.php">กลับแดชบอร์ด</a>
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
            <h5 class="card-title mb-3">เพิ่ม/แก้ไข Transportation</h5>
            <form method="post" enctype="multipart/form-data" id="transportForm" class="row g-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create" id="formAction">
                <input type="hidden" name="id" value="" id="transportId">

                <div class="col-md-4">
                    <label class="form-label">ทะเบียนรถ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="license_plate" id="license_plate" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">รูปแบบ Vehicle <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="vehicle_type" id="vehicle_type" placeholder="เช่น รถตู้ 10 ที่นั่ง / รถบัส ฯลฯ" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">ยี่ห้อรถ</label>
                    <input type="text" class="form-control" name="brand" id="brand" placeholder="เช่น Toyota, Hino">
                </div>

                <div class="col-md-6">
                    <label class="form-label">ชื่อเต็มเจ้าของรถ</label>
                    <input type="text" class="form-control" name="owner_fullname" id="owner_fullname">
                </div>

                <div class="col-md-6">
                    <label class="form-label">ไฟล์เล่มทะเบียนรถ (PDF/JPG/PNG/WEBP) <small class="text-muted">(ไม่บังคับ, สูงสุด 10MB)</small></label>
                    <input type="file" class="form-control" name="reg_book_file" id="reg_book_file" accept=".pdf,.jpg,.jpeg,.png,.webp">
                </div>

                <?php if (!can('bypass_confirm')): ?>
                    <div class="col-12">
                        <label class="form-label">รหัสลับกลาง (ยืนยันการเพิ่ม/แก้ไข/ลบ)</label>
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
                            <th>ทะเบียนรถ</th>
                            <th>Vehicle</th>
                            <th>ยี่ห้อ</th>
                            <th>เจ้าของรถ</th>
                            <th style="width:180px;">เล่มทะเบียน</th>
                            <th class="text-center" style="width:160px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">ยังไม่มีข้อมูล</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $i => $t): ?>
                                <?php
                                $storedPath = (string)($t['reg_book_file'] ?? '');
                                // ใช้พาธ "ตามเดิม" ที่เก็บไว้ โดยแสดงเป็นลิงก์ ../../uploads/... ตามที่ผู้ใช้ต้องการ
                                $fileUrl = $storedPath !== '' ? ($PUBLIC_PREFIX . ltrim($storedPath, '/')) : '';
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <button type="button"
                                            class="btn btn-link p-0 text-decoration-none transport-view"
                                            data-id="<?= (int)$t['id'] ?>"
                                            data-license_plate="<?= htmlspecialchars($t['license_plate'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-vehicle_type="<?= htmlspecialchars($t['vehicle_type'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-brand="<?= htmlspecialchars($t['brand'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-owner_fullname="<?= htmlspecialchars($t['owner_fullname'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-file="<?= htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8') ?>">
                                            <strong><?= htmlspecialchars($t['license_plate']) ?></strong>
                                        </button>
                                    </td>
                                    <td><?= htmlspecialchars($t['vehicle_type']) ?></td>
                                    <td><?= htmlspecialchars($t['brand'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($t['owner_fullname'] ?? '-') ?></td>
                                    <td>
                                        <?php if (!empty($fileUrl)): ?>
                                            <button class="btn btn-sm btn-outline-secondary btn-open-file"
                                                type="button"
                                                data-file="<?= htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8') ?>">
                                                เปิดไฟล์
                                            </button>
                                            <!-- ถ้าต้องมีลิงก์เปิดแท็บเดิมที่คุณทำไว้ ก็เก็บเสริมไว้ได้ -->
                                            <!-- <a class="btn btn-sm btn-outline-secondary ms-1" target="_blank" href="<?= htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8') ?>">เปิดแท็บใหม่</a> -->
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button
                                            class="btn btn-sm btn-outline-dark ms-1 btn-view-schedule"
                                            type="button"
                                            data-id="<?= (int)$t['id'] ?>"
                                            data-license_plate="<?= htmlspecialchars($t['license_plate'], ENT_QUOTES, 'UTF-8') ?>">ตารางใช้รถ</button>

                                        <button
                                            class="btn btn-sm btn-outline-primary btn-edit"
                                            type="button"
                                            data-id="<?= (int)$t['id'] ?>"
                                            data-license_plate="<?= htmlspecialchars($t['license_plate'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-vehicle_type="<?= htmlspecialchars($t['vehicle_type'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-brand="<?= htmlspecialchars($t['brand'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-owner_fullname="<?= htmlspecialchars($t['owner_fullname'] ?? '', ENT_QUOTES, 'UTF-8') ?>">แก้ไข</button>

                                        <form method="post" class="d-inline-block ms-1" onsubmit="return confirmDelete(this)">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
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
</div>

<!-- Modal: พรีวิวไฟล์เล่มทะเบียน -->
<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เล่มทะเบียนรถ</h5>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="filePreviewBody" style="min-height:70vh;">
                <div class="p-4 text-center text-muted">กำลังโหลดไฟล์…</div>
            </div>
            <div class="modal-footer">
                <a id="fileDownloadLink" href="#" class="btn btn-outline-secondary" target="_blank" rel="noopener">เปิดในแท็บใหม่</a>
                <button class="btn btn-primary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: รายละเอียด Transportation -->
<div class="modal fade" id="transportDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">รายละเอียดรถ</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0" id="transportDetailList">
                    <!-- will be filled by JS -->
                </dl>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" id="detailOpenFileBtn" type="button" style="display:none;">เปิดเล่มทะเบียน</button>
                <button class="btn btn-primary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal: ตารางการใช้รถ -->
<div class="modal fade" id="transportScheduleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">ตารางการใช้รถ</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="transportScheduleBody">
        <div class="text-center text-muted py-4">กำลังโหลด...</div>
      </div>
    </div>
  </div>
</div>

<script>
    function confirmDelete(frm) {
        return confirm('ยืนยันลบข้อมูลคันนี้?');
    }

    const form = document.getElementById('transportForm');
    const action = document.getElementById('formAction');
    const idFld = document.getElementById('transportId');

    function fillForm(d) {
        action.value = 'update';
        idFld.value = d.id;
        document.getElementById('license_plate').value = d.license_plate || '';
        document.getElementById('vehicle_type').value = d.vehicle_type || '';
        document.getElementById('brand').value = d.brand || '';
        document.getElementById('owner_fullname').value = d.owner_fullname || '';
        document.getElementById('btnSubmit').textContent = 'บันทึกการแก้ไข';
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    function resetForm() {
        action.value = 'create';
        idFld.value = '';
        form.reset();
        document.getElementById('btnSubmit').textContent = 'บันทึก';
    }

    document.getElementById('btnResetForm').addEventListener('click', resetForm);

    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            fillForm({
                id: btn.dataset.id,
                license_plate: btn.dataset.license_plate,
                vehicle_type: btn.dataset.vehicle_type,
                brand: btn.dataset.brand,
                owner_fullname: btn.dataset.owner_fullname
            });
        });
    });

    // ========== Popup: พรีวิวไฟล์ (ใช้พาธตรงตามที่เก็บ/แสดง) ==========
    function openFilePreview(url) {
        if (!url) return;
        const m = new bootstrap.Modal(document.getElementById('filePreviewModal'));
        const body = document.getElementById('filePreviewBody');
        const aDl = document.getElementById('fileDownloadLink');

        aDl.href = url;
        body.innerHTML = '<div class="p-4 text-center text-muted">กำลังโหลดไฟล์…</div>';

        const ext = (url.split('.').pop() || '').toLowerCase();
        if (ext === 'pdf') {
            const iframe = document.createElement('iframe');
            iframe.src = url;
            iframe.style.width = '100%';
            iframe.style.height = '70vh';
            iframe.setAttribute('title', 'พรีวิว PDF');
            iframe.setAttribute('loading', 'lazy');
            body.innerHTML = '';
            body.appendChild(iframe);
        } else {
            const img = document.createElement('img');
            img.src = url;
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
            img.alt = 'เล่มทะเบียนรถ';
            const wrap = document.createElement('div');
            wrap.className = 'p-3 text-center';
            wrap.appendChild(img);
            body.innerHTML = '';
            body.appendChild(wrap);
        }

        m.show();
    }

    document.querySelectorAll('.btn-open-file').forEach(btn => {
        btn.addEventListener('click', () => {
            const url = btn.getAttribute('data-file');
            openFilePreview(url);
        });
    });

    // ========== Popup: รายละเอียด (คลิกทะเบียนรถ) ==========
    document.querySelectorAll('.transport-view').forEach(btn => {
        btn.addEventListener('click', () => {
            const d = {
                license_plate: btn.dataset.license_plate || '',
                vehicle_type: btn.dataset.vehicle_type || '',
                brand: btn.dataset.brand || '',
                owner_fullname: btn.dataset.owner_fullname || '',
                file: btn.dataset.file || ''
            };

            const list = document.getElementById('transportDetailList');
            list.innerHTML = `
      <dt class="col-sm-4">ทะเบียนรถ</dt><dd class="col-sm-8">${escapeHtml(d.license_plate)}</dd>
      <dt class="col-sm-4">รูปแบบ Vehicle</dt><dd class="col-sm-8">${escapeHtml(d.vehicle_type)}</dd>
      <dt class="col-sm-4">ยี่ห้อรถ</dt><dd class="col-sm-8">${d.brand ? escapeHtml(d.brand) : '-'}</dd>
      <dt class="col-sm-4">ชื่อเต็มเจ้าของรถ</dt><dd class="col-sm-8">${d.owner_fullname ? escapeHtml(d.owner_fullname) : '-'}</dd>
      <dt class="col-sm-4">เล่มทะเบียน</dt><dd class="col-sm-8">${d.file ? 'มีไฟล์แนบ' : '—'}</dd>
    `;

            const btnOpen = document.getElementById('detailOpenFileBtn');
            if (d.file) {
                btnOpen.style.display = '';
                btnOpen.onclick = () => openFilePreview(d.file); // ใช้พาธเดิม
            } else {
                btnOpen.style.display = 'none';
                btnOpen.onclick = null;
            }

            const modal = new bootstrap.Modal(document.getElementById('transportDetailModal'));
            modal.show();
        });
    });

    // escapeHtml ป้องกัน XSS เวลาแสดงข้อมูลใน modal
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>

<?php render_footer(); ?>