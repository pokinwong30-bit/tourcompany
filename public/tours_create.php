<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/guard.php';
require_backoffice(); // ต้องเป็น admin
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/audit.php';

$errors = [];
$info = '';
$role = strtolower(user_role());
$autoOpenModalTourId = null;     // เปิด Tour Detail modal หลัง POST
$autoOpenDepartTourId = null;    // เปิด Departure modal หลัง POST

// -------- Helper: require shared secret for Manager/Officer --------
function require_password_confirm_if_needed(): bool
{
    if (can('bypass_confirm')) return true; // Director ข้าม
    $secret = $_POST['secret_confirm'] ?? '';
    if ($secret === '') return false;
    return verify_shared_secret($secret);   // ใช้รหัสลับกลางของระบบ
}

// -------- Helper: reindex day_no 1..N --------
function reindex_day_no(PDO $pdo, int $tour_id): void
{
    $pdo->beginTransaction();
    try {
        $rows = $pdo->prepare("SELECT id FROM tour_days WHERE tour_id=? ORDER BY day_no ASC, id ASC");
        $rows->execute([$tour_id]);
        $i = 1;
        $upd = $pdo->prepare("UPDATE tour_days SET day_no=? WHERE id=?");
        foreach ($rows->fetchAll(PDO::FETCH_COLUMN) as $day_id) {
            $upd->execute([$i++, $day_id]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
    }
}

// -------- Helper: ensure cost table + upsert cost breakdown --------
function ensure_tour_costs_table(PDO $pdo): void
{
    $sql = "CREATE TABLE IF NOT EXISTS tour_costs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tour_id INT NOT NULL UNIQUE,
        pax INT NOT NULL DEFAULT 0,
        profit_percent DECIMAL(10,2) NOT NULL DEFAULT 0,
        day_count INT NOT NULL DEFAULT 1,
        base_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
        manual_profit DECIMAL(12,2) NOT NULL DEFAULT 0,
        percent_profit_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        selling_total DECIMAL(12,2) NOT NULL DEFAULT 0,
        per_pax DECIMAL(12,2) NOT NULL DEFAULT 0,
        per_day DECIMAL(12,2) NOT NULL DEFAULT 0,
        payload LONGTEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    try {
        $pdo->exec($sql);
    } catch (Throwable $e) {
        error_log('tour_costs table create failed: ' . $e->getMessage());
        throw $e;
    }
}

function upsert_tour_costs(PDO $pdo, int $tour_id, array $post): void
{
    $hasCostFields = false;
    foreach (['cost_payload', 'cost_pax', 'cost_profit_percent', 'cost_day_count', 'cost_base_cost', 'cost_manual_profit', 'cost_profit_amount', 'cost_selling_total', 'cost_per_pax', 'cost_per_day'] as $key) {
        if (isset($post[$key])) {
            $hasCostFields = true;
            break;
        }
    }
    if (!$hasCostFields) return;

    ensure_tour_costs_table($pdo);

    $payload = trim($post['cost_payload'] ?? '') ?: null;
    $pax = max(0, (int)($post['cost_pax'] ?? 0));
    $profit_percent = max(0.0, (float)($post['cost_profit_percent'] ?? 0));
    $day_count = max(1, (int)($post['cost_day_count'] ?? 1));
    $base_cost = max(0.0, (float)($post['cost_base_cost'] ?? 0));
    $manual_profit = max(0.0, (float)($post['cost_manual_profit'] ?? 0));
    $profit_amount = max(0.0, (float)($post['cost_profit_amount'] ?? 0));
    $selling_total = max(0.0, (float)($post['cost_selling_total'] ?? 0));
    $per_pax = max(0.0, (float)($post['cost_per_pax'] ?? 0));
    $per_day = max(0.0, (float)($post['cost_per_day'] ?? 0));

    $sql = "INSERT INTO tour_costs 
            (tour_id, pax, profit_percent, day_count, base_cost, manual_profit, percent_profit_amount, selling_total, per_pax, per_day, payload)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
              pax=VALUES(pax),
              profit_percent=VALUES(profit_percent),
              day_count=VALUES(day_count),
              base_cost=VALUES(base_cost),
              manual_profit=VALUES(manual_profit),
              percent_profit_amount=VALUES(percent_profit_amount),
              selling_total=VALUES(selling_total),
              per_pax=VALUES(per_pax),
              per_day=VALUES(per_day),
              payload=VALUES(payload)";
    $params = [
        $tour_id,
        $pax,
        $profit_percent,
        $day_count,
        $base_cost,
        $manual_profit,
        $profit_amount,
        $selling_total,
        $per_pax,
        $per_day,
        $payload
    ];

    try {
        $pdo->prepare($sql)->execute($params);
    } catch (Throwable $e) {
        // retry once in case the table was dropped after initial creation
        try {
            ensure_tour_costs_table($pdo);
            $pdo->prepare($sql)->execute($params);
        } catch (Throwable $inner) {
            error_log('upsert_tour_costs failed: ' . $inner->getMessage());
            throw $inner;
        }
    }
}

// --------- Preload dropdown data ---------
$countries = $pdo->query("SELECT id, COALESCE(name_th,name_en) AS name, name_en FROM countries ORDER BY name_en")->fetchAll(PDO::FETCH_ASSOC);
$airlines  = $pdo->query("SELECT id, name, iata FROM airlines ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$airports  = $pdo->query("SELECT id, name, iata FROM airports ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// หา ID ประเทศไทยสำหรับใช้ล็อกใน Domestic / Inbound
$thailand_id = null;
foreach ($countries as $c) {
    $nameLower = mb_strtolower($c['name'] ?? '');
    if (in_array($nameLower, ['thailand', 'ไทย', 'ราชอาณาจักรไทย'], true)) {
        $thailand_id = (int)$c['id'];
        break;
    }
}

/* ====================== TOGGLE SALE (เปิด/ปิดขาย) ====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_sale') {
    csrf_validate();
    if (!require_password_confirm_if_needed()) {
        $errors[] = 'ต้องยืนยันรหัสลับให้ถูกต้อง';
    } else {
        $tour_id = (int)($_POST['tour_id'] ?? 0);
        $on_sale = (int)($_POST['on_sale'] ?? 0) ? 1 : 0;
        if ($tour_id <= 0) {
            $errors[] = 'ทัวร์ไม่ถูกต้อง';
        } else {
            $stmt = $pdo->prepare("UPDATE tours SET is_on_sale=? WHERE id=?");
            $stmt->execute([$on_sale, $tour_id]);
            audit_log('tour_toggle_sale', 'tours', $tour_id, [
                'by' => current_user()['id'],
                'by_role' => $role,
                'is_on_sale' => $on_sale
            ]);
            $info = $on_sale ? 'เปิดขายโปรแกรมแล้ว' : 'ปิดขายโปรแกรมแล้ว';
        }
    }
}

/* ====================== CREATE TOUR ====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    csrf_validate();
    if (!require_password_confirm_if_needed()) $errors[] = 'ต้องยืนยันรหัสลับให้ถูกต้อง';

    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $long_description = trim($_POST['long_description'] ?? '');
    $country_id = (int)($_POST['country_id'] ?? 0);
    $city_id = (int)($_POST['city_id'] ?? 0);
    $airline_id = (int)($_POST['airline_id'] ?? 0);
    $travel_window = trim($_POST['travel_window'] ?? '');
    $duration_days = (int)($_POST['duration_days'] ?? 0);
    $origin_airport_id = (int)($_POST['origin_airport_id'] ?? 0);
    $dest_airport_id   = (int)($_POST['dest_airport_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $tour_type = $_POST['tour_type'] ?? 'group';

    // ตลาดทัวร์: domestic / inbound / outbound
    $market_type = $_POST['market_type'] ?? 'outbound';
    if (!in_array($market_type, ['domestic', 'inbound', 'outbound'], true)) {
        $market_type = 'outbound';
    }

    // หากเป็น Domestic หรือ Inbound และรู้ id ประเทศไทย -> ล็อกประเทศเป็นไทยในฝั่ง server
    if (in_array($market_type, ['domestic', 'inbound'], true) && $thailand_id) {
        $country_id = $thailand_id;
    }

    if ($code === '') $errors[] = 'กรอกรหัสทัวร์';
    if ($name === '') $errors[] = 'กรอกชื่อโปรแกรมทัวร์';
    if ($country_id <= 0) $errors[] = 'เลือกประเทศ';
    if ($travel_window === '') $errors[] = 'กรอกช่วงเวลาเดินทาง (ข้อความ)';
    if ($duration_days <= 0) $errors[] = 'ระยะเวลาไม่ถูกต้อง';
    if (!in_array($tour_type, ['group', 'private'], true)) $tour_type = 'group';

    // Validation สนามบินตามประเภทตลาด
    if ($market_type === 'outbound') {
        // Outbound: ต้องมีต้นทาง/ปลายทางชัดเจน
        if ($origin_airport_id <= 0 || $dest_airport_id <= 0) {
            $errors[] = 'ทัวร์ Outbound ต้องเลือกสนามบินต้นทางและปลายทาง';
        }
    } else {
        // Domestic / Inbound: สนามบินไม่บังคับ แต่ถ้ากรอกต้องครบทั้งคู่
        if (($origin_airport_id > 0 && $dest_airport_id <= 0) || ($origin_airport_id <= 0 && $dest_airport_id > 0)) {
            $errors[] = 'หากระบุสนามบิน ต้องกรอกทั้งต้นทางและปลายทางให้ครบ';
        }
    }

    // โลโก้สายการบิน
    $airline_logo_path = null;
    if (!empty($_FILES['airline_logo']['name'])) {
        $f = $_FILES['airline_logo'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            if (!isset($allowed[$mime])) {
                $errors[] = 'ไฟล์โลโก้ต้องเป็น jpg/png/webp/svg';
            } else {
                $ext = $allowed[$mime];
                $dir = __DIR__ . '/../uploads/airlines';
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $basename = 'al_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $dest = $dir . '/' . $basename;
                if (move_uploaded_file($f['tmp_name'], $dest)) $airline_logo_path = 'uploads/airlines/' . $basename;
                else $errors[] = 'อัปโหลดโลโก้ไม่สำเร็จ';
            }
        } elseif ($f['error'] !== UPLOAD_ERR_NO_FILE) $errors[] = 'อัปโหลดโลโก้ผิดพลาด';
    }

    // PDF ทัวร์
    $pdf_path = null;
    if (!empty($_FILES['tour_pdf']['name'])) {
        $f = $_FILES['tour_pdf'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            if ($mime !== 'application/pdf') {
                $errors[] = 'ไฟล์โปรแกรมต้องเป็น PDF';
            } else {
                $dir = __DIR__ . '/../uploads/tours';
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $basename = 'tour_' . bin2hex(random_bytes(8)) . '.pdf';
                $dest = $dir . '/' . $basename;
                if (move_uploaded_file($f['tmp_name'], $dest)) $pdf_path = 'uploads/tours/' . $basename;
                else $errors[] = 'อัปโหลด PDF ไม่สำเร็จ';
            }
        } elseif ($f['error'] !== UPLOAD_ERR_NO_FILE) $errors[] = 'อัปโหลด PDF ผิดพลาด';
    }

    // รูปปกโปรแกรม
    $cover_image_path = null;
    if (!empty($_FILES['cover_image']['name'])) {
        $f = $_FILES['cover_image'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            if (!isset($allowed[$mime])) {
                $errors[] = 'รูปปกต้องเป็น JPG/PNG/WEBP';
            } else {
                $ext = $allowed[$mime];
                $dir = __DIR__ . '/../uploads/tours/covers';
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $basename = 'cover_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $dest = $dir . '/' . $basename;
                if (move_uploaded_file($f['tmp_name'], $dest)) $cover_image_path = 'uploads/tours/covers/' . $basename;
                else $errors[] = 'อัปโหลดรูปปกไม่สำเร็จ';
            }
        } elseif ($f['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = 'อัปโหลดรูปปกผิดพลาด';
        }
    }

    // รายวัน (จากฟอร์มสร้าง)
    $day_no = $_POST['day_no'] ?? [];
    $day_desc = $_POST['day_desc'] ?? [];

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO tours
            (code,name,long_description,country_id,city_id,airline_id,airline_logo_path,depart_date,travel_window,duration_days,origin_airport_id,dest_airport_id,price,tour_type,market_type,pdf_path,is_on_sale,cover_image_path,created_by)
            VALUES (?,?,?, ?,?, ?,?, NULL, ?,?,?,?,?,?, ?, ?, 1, ?, ?)");
        $stmt->execute([
            $code,
            $name,
            $long_description ?: null,
            $country_id,
            $city_id ?: null,
            $airline_id ?: null,
            $airline_logo_path,
            $travel_window,
            $duration_days,
            $origin_airport_id ?: null,
            $dest_airport_id ?: null,
            $price,
            $tour_type,
            $market_type,
            $pdf_path,
            $cover_image_path,
            current_user()['id']
        ]);
        $tour_id = (int)$pdo->lastInsertId();

        if ($day_no && $day_desc) {
            $ins = $pdo->prepare("INSERT INTO tour_days (tour_id, day_no, description) VALUES (?,?,?)");
            for ($i = 0; $i < count($day_no); $i++) {
                $dn = (int)$day_no[$i];
                $dd = trim($day_desc[$i] ?? '');
                if ($dn > 0 && $dd !== '') $ins->execute([$tour_id, $dn, $dd]);
            }
            reindex_day_no($pdo, $tour_id);
        }

        // บันทึกข้อมูลต้นทุน/ราคาขาย (ถ้ามี)
        try {
            upsert_tour_costs($pdo, $tour_id, $_POST);
        } catch (Throwable $e) {
            error_log('upsert_tour_costs(create) failed: ' . $e->getMessage());
        }

        audit_log('tour_create', 'tours', $tour_id, [
            'code' => $code,
            'name' => $name,
            'created_by' => current_user()['id'],
            'by_role' => $role
        ]);

        $info = 'บันทึกโปรแกรมทัวร์สำเร็จ';
    }
}

/* ====================== EDIT TOUR ====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    csrf_validate();
    if (!require_password_confirm_if_needed()) $errors[] = 'ต้องยืนยันรหัสลับให้ถูกต้อง';

    $id = (int)($_POST['id'] ?? 0);
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $long_description = trim($_POST['long_description'] ?? '');
    $country_id = (int)($_POST['country_id'] ?? 0);
    $city_id = (int)($_POST['city_id'] ?? 0);
    $airline_id = (int)($_POST['airline_id'] ?? 0);
    $travel_window = trim($_POST['travel_window'] ?? '');
    $duration_days = (int)($_POST['duration_days'] ?? 0);
    $origin_airport_id = (int)($_POST['origin_airport_id'] ?? 0);
    $dest_airport_id   = (int)($_POST['dest_airport_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $tour_type = $_POST['tour_type'] ?? 'group';

    $market_type = $_POST['market_type'] ?? 'outbound';
    if (!in_array($market_type, ['domestic', 'inbound', 'outbound'], true)) {
        $market_type = 'outbound';
    }

    // ถ้าเลือก domestic / inbound ระหว่างแก้ไข -> บังคับประเทศเป็นไทย
    if (in_array($market_type, ['domestic', 'inbound'], true) && $thailand_id) {
        $country_id = $thailand_id;
    }

    if ($id <= 0) $errors[] = 'ทัวร์ไม่ถูกต้อง';
    if ($code === '') $errors[] = 'กรอกรหัสทัวร์';
    if ($name === '') $errors[] = 'กรอกชื่อโปรแกรมทัวร์';
    if ($country_id <= 0) $errors[] = 'เลือกประเทศ';
    if ($travel_window === '') $errors[] = 'กรอกช่วงเวลาเดินทาง (ข้อความ)';
    if ($duration_days <= 0) $errors[] = 'ระยะเวลาไม่ถูกต้อง';
    if (!in_array($tour_type, ['group', 'private'], true)) $tour_type = 'group';

    // Validation สนามบินตามตลาด
    if ($market_type === 'outbound') {
        if ($origin_airport_id <= 0 || $dest_airport_id <= 0) {
            $errors[] = 'ทัวร์ Outbound ต้องเลือกสนามบินต้นทางและปลายทาง';
        }
    } else {
        if (($origin_airport_id > 0 && $dest_airport_id <= 0) || ($origin_airport_id <= 0 && $dest_airport_id > 0)) {
            $errors[] = 'หากระบุสนามบิน ต้องกรอกทั้งต้นทางและปลายทางให้ครบ';
        }
    }

    // อัปเดตฟิลด์หลัก
    $sets = [];
    $params = [];
    $fields = [
        'code' => $code,
        'name' => $name,
        'long_description' => ($long_description ?: null),
        'country_id' => $country_id,
        'city_id' => $city_id ?: null,
        'airline_id' => $airline_id ?: null,
        'travel_window' => $travel_window,
        'duration_days' => $duration_days,
        'origin_airport_id' => $origin_airport_id ?: null,
        'dest_airport_id' => $dest_airport_id ?: null,
        'price' => $price,
        'tour_type' => $tour_type,
        'market_type' => $market_type
    ];
    foreach ($fields as $k => $v) {
        $sets[] = "$k=?";
        $params[] = $v;
    }
    $sets[] = "depart_date=NULL"; // ไม่ใช้แล้ว

    // โลโก้สายการบิน (อัปโหลดทับ)
    if (!empty($_FILES['airline_logo']['name'])) {
        $f = $_FILES['airline_logo'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            if (isset($allowed[$mime])) {
                $ext = $allowed[$mime];
                $dir = __DIR__ . '/../uploads/airlines';
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $basename = 'al_' . bin2hex(random_bytes(8)) . '.' . $ext;
                if (move_uploaded_file($f['tmp_name'], $dir . '/' . $basename)) {
                    $sets[] = "airline_logo_path=?";
                    $params[] = 'uploads/airlines/' . $basename;
                }
            } else $errors[] = 'ไฟล์โลโก้ต้องเป็น jpg/png/webp/svg';
        } elseif ($f['error'] !== UPLOAD_ERR_NO_FILE) $errors[] = 'อัปโหลดโลโก้ผิดพลาด';
    }

    // PDF (อัปโหลดทับ)
    if (!empty($_FILES['tour_pdf']['name'])) {
        $f = $_FILES['tour_pdf'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            if ($mime === 'application/pdf') {
                $dir = __DIR__ . '/../uploads/tours';
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $basename = 'tour_' . bin2hex(random_bytes(8)) . '.pdf';
                if (move_uploaded_file($f['tmp_name'], $dir . '/' . $basename)) {
                    $sets[] = "pdf_path=?";
                    $params[] = 'uploads/tours/' . $basename;
                }
            } else $errors[] = 'ไฟล์โปรแกรมต้องเป็น PDF';
        } elseif ($f['error'] !== UPLOAD_ERR_NO_FILE) $errors[] = 'อัปโหลด PDF ผิดพลาด';
    }

    // รูปปกโปรแกรม (อัปโหลดทับ)
    if (!empty($_FILES['cover_image']['name'])) {
        $f = $_FILES['cover_image'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            if (isset($allowed[$mime])) {
                $ext = $allowed[$mime];
                $dir = __DIR__ . '/../uploads/tours/covers';
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $basename = 'cover_' . bin2hex(random_bytes(8)) . '.' . $ext;
                if (move_uploaded_file($f['tmp_name'], $dir . '/' . $basename)) {
                    $sets[] = "cover_image_path=?";
                    $params[] = 'uploads/tours/covers/' . $basename;
                }
            } else {
                $errors[] = 'รูปปกต้องเป็น JPG/PNG/WEBP';
            }
        } elseif ($f['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = 'อัปโหลดรูปปกผิดพลาด';
        }
    }

    if (!$errors) {
        $params[] = $id;
        $sql = "UPDATE tours SET " . implode(',', $sets) . " WHERE id=?";
        $pdo->prepare($sql)->execute($params);

        audit_log('tour_edit', 'tours', $id, [
            'by' => current_user()['id'],
            'by_role' => $role,
            'code' => $code,
            'name' => $name
        ]);

        // อัปเดตข้อมูลต้นทุน/ราคาขาย (ถ้ามี)
        try {
            upsert_tour_costs($pdo, $id, $_POST);
        } catch (Throwable $e) {
            error_log('upsert_tour_costs(edit) failed: ' . $e->getMessage());
        }

        $info = 'แก้ไขโปรแกรมทัวร์สำเร็จ';
    }

    if (isset($_POST['keep_modal'])) {
        $autoOpenModalTourId = (int)$_POST['keep_modal'];
    }
}

/* ====================== DELETE TOUR ====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_validate();
    if (!require_password_confirm_if_needed()) $errors[] = 'ต้องยืนยันรหัสลับให้ถูกต้อง';

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) $errors[] = 'ทัวร์ไม่ถูกต้อง';

    if (!$errors) {
        $pdo->prepare("DELETE FROM tours WHERE id=?")->execute([$id]);

        audit_log('tour_delete', 'tours', $id, [
            'by' => current_user()['id'],
            'by_role' => $role
        ]);

        $info = 'ลบโปรแกรมทัวร์สำเร็จ';
    }
}

/* ====================== DEPARTURES CRUD ====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $depAction = $_POST['action'] ?? '';
    if (in_array($depAction, ['dep_bulk_create', 'dep_update', 'dep_delete'], true)) {
        csrf_validate();
        if (!require_password_confirm_if_needed()) $errors[] = 'ต้องยืนยันรหัสลับให้ถูกต้อง';

        $tour_id = (int)($_POST['tour_id'] ?? 0);
        if ($tour_id <= 0) $errors[] = 'ทัวร์ไม่ถูกต้อง';

        if (!$errors) {
            if ($depAction === 'dep_bulk_create') {
                // เพิ่มหลายช่วงในครั้งเดียว
                $starts = $_POST['dep_start'] ?? [];
                $ends   = $_POST['dep_end'] ?? [];
                $caps   = $_POST['dep_capacity'] ?? [];
                $prices = $_POST['dep_price'] ?? [];
                $ins = $pdo->prepare("INSERT INTO tour_departures (tour_id, start_date, end_date, capacity, price) VALUES (?,?,?,?,?)");

                $added = 0;
                for ($i = 0; $i < count($starts); $i++) {
                    $sd = trim($starts[$i] ?? '');
                    $ed = trim($ends[$i] ?? '');
                    $cp = (int)($caps[$i] ?? 0);
                    $pr = (float)($prices[$i] ?? 0);
                    if (!$sd || !$ed) continue;
                    if ($cp < 0) $cp = 0;
                    if (strtotime($sd) === false || strtotime($ed) === false) continue;
                    if (strtotime($sd) > strtotime($ed)) continue;
                    $ins->execute([$tour_id, $sd, $ed, $cp, $pr]);
                    $added++;
                }
                if ($added > 0) {
                    audit_log('depart_create_bulk', 'tour_departures', null, ['tour_id' => $tour_id, 'count' => $added]);
                    $info = "เพิ่มรอบเดินทาง $added รายการ";
                } else {
                    $errors[] = 'ไม่มีข้อมูลที่เพิ่มได้';
                }
            } elseif ($depAction === 'dep_update') {
                $dep_id = (int)($_POST['dep_id'] ?? 0);
                $sd = trim($_POST['start_date'] ?? '');
                $ed = trim($_POST['end_date'] ?? '');
                $cp = (int)($_POST['capacity'] ?? 0);
                $pr = (float)($_POST['price'] ?? 0);

                if ($dep_id <= 0) $errors[] = 'รอบเดินทางไม่ถูกต้อง';
                if (!$sd || !$ed || strtotime($sd) === false || strtotime($ed) === false || strtotime($sd) > strtotime($ed)) $errors[] = 'ช่วงวันไม่ถูกต้อง';

                if (!$errors) {
                    $pdo->prepare("UPDATE tour_departures SET start_date=?, end_date=?, capacity=?, price=? WHERE id=? AND tour_id=?")
                        ->execute([$sd, $ed, $cp, $pr, $dep_id, $tour_id]);
                    audit_log('depart_update', 'tour_departures', $dep_id, ['tour_id' => $tour_id]);
                    $info = 'อัปเดตรอบเดินทางสำเร็จ';
                }
            } elseif ($depAction === 'dep_delete') {
                $dep_id = (int)($_POST['dep_id'] ?? 0);
                if ($dep_id <= 0) $errors[] = 'รอบเดินทางไม่ถูกต้อง';

                if (!$errors) {
                    $pdo->prepare("DELETE FROM tour_departures WHERE id=? AND tour_id=?")->execute([$dep_id, $tour_id]);
                    audit_log('depart_delete', 'tour_departures', $dep_id, ['tour_id' => $tour_id]);
                    $info = 'ลบรอบเดินทางสำเร็จ';
                }
            }
        }

        // เปิด Departure modal ต่อ
        if (isset($_POST['keep_depart_modal'])) {
            $autoOpenDepartTourId = (int)$_POST['keep_depart_modal'];
        }
    }
}

/* ====================== DAY-BY-DAY CRUD (คงของเดิม) ====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dayAction = $_POST['action'] ?? '';
    if (in_array($dayAction, ['day_create', 'day_update', 'day_delete'], true)) {
        csrf_validate();
        if (!require_password_confirm_if_needed()) $errors[] = 'ต้องยืนยันรหัสลับให้ถูกต้อง';
        $tour_id = (int)($_POST['tour_id'] ?? 0);
        if ($tour_id <= 0) $errors[] = 'ทัวร์ไม่ถูกต้อง';

        if (!$errors) {
            if ($dayAction === 'day_create') {
                $day_no = (int)($_POST['day_no'] ?? 0);
                $description = trim($_POST['description'] ?? '');
                if ($day_no <= 0) $errors[] = 'ระบุหมายเลขวันให้ถูกต้อง';
                if ($description === '') $errors[] = 'กรอกรายละเอียดกิจกรรม';

                if (!$errors) {
                    $pdo->beginTransaction();
                    try {
                        $pdo->prepare("UPDATE tour_days SET day_no = day_no + 1 WHERE tour_id=? AND day_no >= ?")
                            ->execute([$tour_id, $day_no]);

                        $ins = $pdo->prepare("INSERT INTO tour_days (tour_id, day_no, description) VALUES (?,?,?)");
                        $ins->execute([$tour_id, $day_no, $description]);

                        $pdo->commit();

                        audit_log('tour_days.create', 'tour_days', (int)$pdo->lastInsertId(), [
                            'tour_id' => $tour_id,
                            'day_no' => $day_no
                        ]);
                        $info = 'เพิ่มรายละเอียดรายวันสำเร็จ';
                    } catch (Throwable $e) {
                        $pdo->rollBack();
                        $errors[] = 'บันทึกไม่สำเร็จ';
                    }
                }
            } elseif ($dayAction === 'day_update') {
                $day_id = (int)($_POST['day_id'] ?? 0);
                $new_no = (int)($_POST['day_no'] ?? 0);
                $description = trim($_POST['description'] ?? '');

                if ($day_id <= 0) $errors[] = 'ระบุรายการวันไม่ถูกต้อง';
                if ($new_no <= 0) $errors[] = 'หมายเลขวันไม่ถูกต้อง';
                if ($description === '') $errors[] = 'กรอกรายละเอียดกิจกรรม';

                if (!$errors) {
                    $pdo->beginTransaction();
                    try {
                        $q = $pdo->prepare("SELECT day_no FROM tour_days WHERE id=? AND tour_id=? FOR UPDATE");
                        $q->execute([$day_id, $tour_id]);
                        $row = $q->fetch(PDO::FETCH_ASSOC);
                        if (!$row) throw new RuntimeException('ไม่พบบรรทัดวัน');

                        $old_no = (int)$row['day_no'];

                        if ($new_no !== $old_no) {
                            if ($new_no < $old_no) {
                                $pdo->prepare("UPDATE tour_days SET day_no = day_no + 1 WHERE tour_id=? AND day_no >= ? AND day_no < ? AND id <> ?")
                                    ->execute([$tour_id, $new_no, $old_no, $day_id]);
                            } else {
                                $pdo->prepare("UPDATE tour_days SET day_no = day_no - 1 WHERE tour_id=? AND day_no > ? AND day_no <= ? AND id <> ?")
                                    ->execute([$tour_id, $old_no, $new_no, $day_id]);
                            }
                        }

                        $pdo->prepare("UPDATE tour_days SET day_no=?, description=? WHERE id=? AND tour_id=?")
                            ->execute([$new_no, $description, $day_id, $tour_id]);

                        $pdo->commit();
                        audit_log('tour_days.update', 'tour_days', $day_id, [
                            'tour_id' => $tour_id,
                            'before' => ['day_no' => $old_no],
                            'after' => ['day_no' => $new_no]
                        ]);
                        $info = 'อัปเดตรายละเอียดรายวันสำเร็จ';
                    } catch (Throwable $e) {
                        $pdo->rollBack();
                        $errors[] = 'อัปเดตไม่สำเร็จ';
                    }
                }
            } elseif ($dayAction === 'day_delete') {
                $day_id = (int)($_POST['day_id'] ?? 0);
                if ($day_id <= 0) $errors[] = 'ระบุรายการวันไม่ถูกต้อง';

                if (!$errors) {
                    $pdo->beginTransaction();
                    try {
                        $old = $pdo->prepare("SELECT day_no FROM tour_days WHERE id=? AND tour_id=? FOR UPDATE");
                        $old->execute([$day_id, $tour_id]);
                        $before = $old->fetch(PDO::FETCH_ASSOC);
                        if (!$before) throw new RuntimeException('ไม่พบวันเป้าหมาย');

                        $old_no = (int)$before['day_no'];

                        $pdo->prepare("DELETE FROM tour_days WHERE id=? AND tour_id=?")->execute([$day_id, $tour_id]);

                        $pdo->prepare("UPDATE tour_days SET day_no = day_no - 1 WHERE tour_id=? AND day_no > ?")
                            ->execute([$tour_id, $old_no]);

                        $pdo->commit();

                        audit_log('tour_days.delete', 'tour_days', $day_id, [
                            'tour_id' => $tour_id,
                            'before' => ['day_no' => $old_no]
                        ]);
                        $info = 'ลบรายละเอียดรายวันสำเร็จ';
                    } catch (Throwable $e) {
                        $pdo->rollBack();
                        $errors[] = 'ลบไม่สำเร็จ';
                    }
                }
            }

            if (isset($_POST['keep_modal'])) {
                $autoOpenModalTourId = (int)$_POST['keep_modal'];
            }
        }
    }
}

// ====================== Pagination (list under form) ======================
$perPageChoices = [5, 10, 20, 50];
$per_page = (int)($_GET['per_page'] ?? ($_POST['per_page'] ?? 10));
if (!in_array($per_page, $perPageChoices, true)) $per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$total = (int)$pdo->query("SELECT COUNT(*) FROM tours")->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

$stmt = $pdo->prepare("
     SELECT t.*, 
           COALESCE(cn.name_th,cn.name_en) AS country_name,
           COALESCE(ci.name_th,ci.name_en) AS city_name,
           al.name AS airline_name, al.iata AS airline_iata,
           ap1.name AS origin_name, ap1.iata AS origin_iata,
           ap2.name AS dest_name,   ap2.iata AS dest_iata
    FROM tours t
    LEFT JOIN countries cn ON cn.id=t.country_id
    LEFT JOIN cities    ci ON ci.id=t.city_id
    LEFT JOIN airlines  al ON al.id=t.airline_id
    LEFT JOIN airports ap1 ON ap1.id=t.origin_airport_id
    LEFT JOIN airports ap2 ON ap2.id=t.dest_airport_id
    ORDER BY t.id DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$tours = $stmt->fetchAll(PDO::FETCH_ASSOC);

render_header('เพิ่มโปรแกรมทัวร์');
?>
<style>
    /* iOS-like switch */
    .switch-ios {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 28px;
    }

    .switch-ios input {
        display: none;
    }

    .switch-slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background: #cfd4da;
        border-radius: 999px;
        transition: .25s;
    }

    .switch-slider:before {
        position: absolute;
        content: "";
        height: 22px;
        width: 22px;
        left: 3px;
        top: 3px;
        background: #fff;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .3);
        transition: .25s;
    }

    .switch-ios input:checked+.switch-slider {
        background: #0d6efd;
    }

    .switch-ios input:checked+.switch-slider:before {
        transform: translateX(20px);
    }

    /* thumb */
    .table-thumb {
        width: 64px;
        height: 48px;
        object-fit: cover;
        border-radius: .375rem;
        border: 1px solid rgba(0, 0, 0, .1);
    }

    /* clickable code link */
    .code-link {
        text-decoration: underline;
        cursor: pointer;
    }

    /* ปรับขนาดปุ่ม/บรรทัด กันตัวอักษรชนและซ้อนกัน โดยเฉพาะฟอนต์ไทย */
    .pagination .page-link {
        line-height: 1.4;
        padding: .6rem .9rem;
        min-width: 44px;
    }

    .pagination {
        row-gap: .5rem;
    }
</style>

<div class="card shadow-sm">
    <div class="card-body">
        <h4 class="mb-3">เพิ่มโปรแกรมทัวร์</h4>

        <?php if ($info): ?><div class="alert alert-success"><?= htmlspecialchars($info) ?></div><?php endif; ?>
        <?php if ($errors): ?><div class="alert alert-danger">
                <ul class="mb-0"><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
            </div><?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="mb-4" id="tourCreateForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">รหัสทัวร์</label>
                    <input type="text" name="code" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">ชื่อโปรแกรมทัวร์</label>
                    <input type="text" name="name" class="form-control" placeholder="เช่น ซากุระฟูจิ 5 วัน 3 คืน" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ช่วงเวลาเดินทาง (ข้อความ)</label>
                    <input type="text" name="travel_window" class="form-control" required placeholder="เช่น ก.พ. 25 - ต.ค. 25">
                </div>

                <div class="col-md-12">
                    <label class="form-label">รายละเอียดโปรแกรม (แบบยาว)</label>
                    <textarea name="long_description" rows="5" class="form-control" placeholder="เขียนรายละเอียดโปรแกรมโดยรวม จุดเด่น เงื่อนไขเบื้องต้น ฯลฯ"></textarea>
                    <div class="form-text">รองรับข้อความยาวหลายบรรทัด</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">ประเทศ</label>
                    <select name="country_id" id="country" class="form-select" required>
                        <option value="">-- เลือกประเทศ --</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">เมือง</label>
                    <select name="city_id" id="city" class="form-select">
                        <option value="">-- เลือกเมือง --</option>
                    </select>
                    <div class="form-text">เลือกประเทศก่อนเพื่อโหลดเมือง</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">สายการบิน (เลือกจากรายการ)</label>
                    <select name="airline_id" class="form-select">
                        <option value="">-- เลือกสายการบิน --</option>
                        <?php foreach ($airlines as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?><?= $a['iata'] ? ' (' . $a['iata'] . ')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">ระยะเวลา (วัน)</label>
                    <input type="number" min="1" name="duration_days" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">สนามบินต้นทาง</label>
                    <select name="origin_airport_id" class="form-select">
                        <option value="">-- เลือกสนามบิน --</option>
                        <?php foreach ($airports as $ap): ?>
                            <option value="<?= $ap['id'] ?>"><?= htmlspecialchars($ap['name']) ?><?= $ap['iata'] ? ' (' . $ap['iata'] . ')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">สนามบินปลายทาง</label>
                    <select name="dest_airport_id" class="form-select">
                        <option value="">-- เลือกสนามบิน --</option>
                        <?php foreach ($airports as $ap): ?>
                            <option value="<?= $ap['id'] ?>"><?= htmlspecialchars($ap['name']) ?><?= $ap['iata'] ? ' (' . $ap['iata'] . ')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text" id="airportHelpText">
                        ทัวร์ Outbound: ต้องเลือกสนามบินต้นทางและปลายทาง
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">ราคาทัวร์ (THB)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">รูปแบบทัวร์</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tour_type" value="group" id="tt1" checked>
                            <label class="form-check-label" for="tt1">Group</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tour_type" value="private" id="tt2">
                            <label class="form-check-label" for="tt2">Private</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">ตลาดทัวร์</label>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="market_type" value="domestic" id="mk_domestic">
                            <label class="form-check-label" for="mk_domestic">Domestic (คนไทยเที่ยวไทย)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="market_type" value="inbound" id="mk_inbound">
                            <label class="form-check-label" for="mk_inbound">Inbound (ต่างชาติเที่ยวไทย)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="market_type" value="outbound" id="mk_outbound" checked>
                            <label class="form-check-label" for="mk_outbound">Outbound (คนไทยเที่ยวต่างประเทศ)</label>
                        </div>
                    </div>
                    <div class="form-text">ใช้แยกรีพอร์ต และกำหนดเงื่อนไขสนามบินอัตโนมัติ</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">โลโก้สายการบิน (อัปโหลด)</label>
                    <input type="file" name="airline_logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg,image/*">
                </div>

                <div class="col-md-4">
                    <label class="form-label">ไฟล์โปรแกรม (PDF)</label>
                    <input type="file" name="tour_pdf" class="form-control" accept=".pdf,application/pdf">
                </div>

                <div class="col-md-8">
                    <label class="form-label">รูปปกโปรแกรมทัวร์ (JPG/PNG/WEBP)</label>
                    <input type="file" name="cover_image" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/*">
                    <div class="form-text">แนะนำอัตราส่วน 16:9 หรือ 4:3</div>
                </div>

                <?php if (!can('bypass_confirm')): ?>
                    <div class="col-md-4">
                        <label class="form-label">รหัสลับสำหรับการยืนยัน (Manager/Officer)</label>
                        <input type="password" name="secret_confirm" class="form-control" placeholder="กรอกรหัสลับ" required>
                    </div>
                <?php endif; ?>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">รายละเอียดรายวัน</h5>
            <div id="days-wrap"></div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddDay">+ เพิ่มวัน</button>

            <hr class="my-4">

            <div class="card mb-4" id="costCalcCard">
                <div class="card-body">
                    <div class="d-flex justify-content-between flex-wrap gap-2 align-items-start">
                        <div>
                            <h5 class="mb-1">คำนวณต้นทุนและราคาขาย</h5>
                            <p class="text-muted small mb-0">ส่วนคำนวณนี้ใช้สำหรับวางแผนค่าใช้จ่ายตามวันของโปรแกรมและราคาเสนอขาย ระบบจะบันทึกค่านี้ให้พร้อมโปรแกรม</p>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="costReset">ล้างค่า</button>
                    </div>

                    <div class="row g-3 mt-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">จำนวนวันในโปรแกรม</label>
                            <input type="number" id="costDayCount" class="form-control" min="1" value="1">
                            <div class="form-text">ดึงจากช่อง "ระยะเวลา" อัตโนมัติ</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">จำนวนผู้เดินทาง (คน)</label>
                            <input type="number" id="costPax" class="form-control" min="1" value="20">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">กำไรที่ต้องการ (%)</label>
                            <input type="number" id="profitPercent" class="form-control" min="0" step="0.1" value="15">
                        </div>
                    </div>

                    <div class="accordion mt-3" id="costDayAccordion"></div>

                    <div class="mt-4">
                        <h6 class="mb-2">สรุปต้นทุนตามหมวด</h6>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">หมวดค่าใช้จ่าย</th>
                                        <th scope="col" class="text-end">รวมทั้งหมด (THB)</th>
                                    </tr>
                                </thead>
                                <tbody id="costTotalsBody"></tbody>
                            </table>
                        </div>
                        <div class="alert alert-primary mt-3 mb-0" id="costSummaryBox"></div>
                    </div>
                </div>
            </div>

            <!-- Hidden fields for cost calculator submission -->
            <input type="hidden" name="cost_payload" id="costPayload">
            <input type="hidden" name="cost_pax" id="costPaxField">
            <input type="hidden" name="cost_profit_percent" id="costProfitPercentField">
            <input type="hidden" name="cost_day_count" id="costDayCountField">
            <input type="hidden" name="cost_base_cost" id="costBaseCost">
            <input type="hidden" name="cost_manual_profit" id="costManualProfit">
            <input type="hidden" name="cost_profit_amount" id="costProfitAmount">
            <input type="hidden" name="cost_selling_total" id="costSellingTotal">
            <input type="hidden" name="cost_per_pax" id="costPerPax">
            <input type="hidden" name="cost_per_day" id="costPerDay">

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary">บันทึกโปรแกรมทัวร์</button>
            </div>
        </form>

        <!-- ตารางรายการทัวร์ -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">รายการโปรแกรมทัวร์</h5>
            <form method="get" class="d-flex align-items-center gap-2">
                <label class="form-label mb-0">แสดง</label>
                <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($perPageChoices as $pp): ?>
                        <option value="<?= $pp ?>" <?= $pp === $per_page ? 'selected' : '' ?>><?= $pp ?></option>
                    <?php endforeach; ?>
                </select>
                <span>แถว / หน้า</span>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Thumb</th>
                        <th>#</th>
                        <th>รหัส</th>
                        <th>ชื่อโปรแกรม</th>
                        <th>ปลายทาง</th>
                        <th>ช่วงเวลาเดินทาง</th>
                        <th>ระยะเวลา</th>
                        <th>สายการบิน</th>
                        <th>ราคา</th>
                        <th>ประเภท</th>
                        <th>ตลาด</th>
                        <th>ขาย?</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tours as $t):
                        $onSale = isset($t['is_on_sale']) ? (int)$t['is_on_sale'] : 1;
                        $thumb = $t['cover_image_path'] ? (BASE_URL . '../../' . $t['cover_image_path']) : null;
                    ?>
                        <tr>
                            <td>
                                <?php if ($thumb): ?>
                                    <img src="<?= htmlspecialchars($thumb) ?>" class="table-thumb" alt="thumb">
                                <?php else: ?>
                                    <span class="badge bg-light text-muted">ไม่มีรูป</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$t['id'] ?></td>
                            <td>
                                <a class="code-link" data-bs-toggle="modal" data-bs-target="#departModal<?= (int)$t['id'] ?>">
                                    <?= htmlspecialchars($t['code']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($t['name'] ?: '-') ?></td>
                            <td>
                                <?= htmlspecialchars($t['country_name'] ?: '-') ?>
                                <?php if ($t['city_name']): ?> • <small class="text-muted"><?= htmlspecialchars($t['city_name']) ?></small><?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($t['travel_window']) ?></td>
                            <td><?= (int)$t['duration_days'] ?> วัน</td>
                            <td><?= htmlspecialchars(trim(($t['airline_name'] ?: '-') . ' ' . ($t['airline_iata'] ? '(' . $t['airline_iata'] . ')' : ''))) ?></td>
                            <td><?= number_format((float)$t['price'], 2) ?></td>
                            <td><span class="badge bg-<?= $t['tour_type'] === 'group' ? 'primary' : 'success' ?>"><?= htmlspecialchars(ucfirst($t['tour_type'])) ?></span></td>
                            <td>
                                <?php
                                $market = $t['market_type'] ?? 'outbound';
                                if ($market === 'domestic') {
                                    echo '<span class="badge bg-warning text-dark">Domestic</span>';
                                } elseif ($market === 'inbound') {
                                    echo '<span class="badge bg-info text-dark">Inbound</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">Outbound</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <form method="post" class="d-inline toggle-sale-form" id="tsf-<?= (int)$t['id'] ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle_sale">
                                    <input type="hidden" name="tour_id" value="<?= (int)$t['id'] ?>">
                                    <input type="hidden" name="on_sale" value="<?= $onSale ?>">
                                    <?php if (!can('bypass_confirm')): ?>
                                        <input type="hidden" name="secret_confirm" value="">
                                    <?php endif; ?>
                                    <label class="switch-ios" title="<?= $onSale ? 'กำลังเปิดขาย' : 'ปิดขาย' ?>">
                                        <input type="checkbox" class="sale-toggle" data-tid="<?= (int)$t['id'] ?>" <?= $onSale ? 'checked' : '' ?>>
                                        <span class="switch-slider"></span>
                                    </label>
                                </form>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#tourModal<?= (int)$t['id'] ?>">ดู</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$tours): ?>
                        <tr>
                            <td colspan="13" class="text-center text-muted py-4">ยังไม่มีข้อมูล</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Tours pagination">
                <ul class="pagination pagination-lg flex-wrap gap-3">
                    <?php
                    $base = strtok($_SERVER['REQUEST_URI'], '?');
                    $qs = $_GET;
                    $qs['per_page'] = $per_page;
                    $make = function ($p) use ($base, $qs) {
                        $qs['page'] = $p;
                        return htmlspecialchars($base . '?' . http_build_query($qs));
                    };
                    ?>
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $page <= 1 ? '#' : $make($page - 1) ?>" aria-label="Previous">&laquo;</a>
                    </li>
                    <?php
                    $window = 2;
                    for ($p = max(1, $page - $window); $p <= min($total_pages, $page + $window); $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $make($p) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $page >= $total_pages ? '#' : $make($page + 1) ?>" aria-label="Next">&raquo;</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    </div>
</div>

<!-- ========== Modals (รายละเอียด + แก้ไข/ลบ/เพิ่มวัน) ========== -->
<?php foreach ($tours as $t): ?>
    <?php
    // day-by-day
    $days = $pdo->prepare("SELECT id, day_no, description FROM tour_days WHERE tour_id=? ORDER BY day_no ASC");
    $days->execute([$t['id']]);
    $dayrows = $days->fetchAll(PDO::FETCH_ASSOC);

    // departures
    $deps = $pdo->prepare("SELECT id, start_date, end_date, capacity, price FROM tour_departures WHERE tour_id=? ORDER BY start_date ASC, id ASC");
    $deps->execute([$t['id']]);
    $deprows = $deps->fetchAll(PDO::FETCH_ASSOC);

    $market = $t['market_type'] ?? 'outbound';
    $marketLabel = $market === 'domestic'
        ? 'Domestic (คนไทยเที่ยวไทย)'
        : ($market === 'inbound'
            ? 'Inbound (ต่างชาติเที่ยวไทย)'
            : 'Outbound (คนไทยเที่ยวต่างประเทศ)');
    $marketClass = $market === 'domestic'
        ? 'bg-warning text-dark'
        : ($market === 'inbound'
            ? 'bg-info text-dark'
            : 'bg-secondary');
    ?>

    <!-- Departure Modal -->
    <div class="modal fade" id="departModal<?= (int)$t['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">รอบเดินทาง • <?= htmlspecialchars($t['code']) ?> — <?= htmlspecialchars($t['name'] ?: '-') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="mb-2">รายการรอบที่มี</h6>
                    <?php if ($deprows): ?>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>เริ่ม</th>
                                        <th>สิ้นสุด</th>
                                        <th class="text-end">จำนวนรับ</th>
                                        <th class="depStatus">สถานะ</th>
                                        <th class="text-end">ราคา (THB)</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deprows as $d): ?>
                                        <tr>
                                            <td>
                                                <form method="post" class="d-flex gap-2 align-items-center">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="dep_update">
                                                    <input type="hidden" name="tour_id" value="<?= (int)$t['id'] ?>">
                                                    <input type="hidden" name="dep_id" value="<?= (int)$d['id'] ?>">
                                                    <input type="hidden" name="keep_depart_modal" value="<?= (int)$t['id'] ?>">
                                                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($d['start_date']) ?>" required>
                                            </td>
                                            <td>
                                                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($d['end_date']) ?>" required>
                                            </td>
                                            <td class="text-end" style="min-width:110px">
                                                <input
                                                    type="number"
                                                    name="capacity"
                                                    min="0"
                                                    class="form-control form-control-sm text-end dep-cap"
                                                    data-target="#depStatus<?= (int)$d['id'] ?>"
                                                    value="<?= (int)$d['capacity'] ?>">
                                            </td>
                                            <td id="depStatus<?= (int)$d['id'] ?>">
                                                <?php if ((int)$d['capacity'] <= 0): ?>
                                                    <span class="badge bg-warning text-dark">เต็ม</span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="text-end" style="min-width:140px">
                                                <input type="number" name="price" step="0.01" min="0" class="form-control form-control-sm text-end" value="<?= number_format((float)$d['price'], 2, '.', '') ?>">
                                            </td>
                                            <td class="d-flex gap-1">
                                                <?php if (!can('bypass_confirm')): ?>
                                                    <input type="password" name="secret_confirm" class="form-control form-control-sm" placeholder="รหัสลับ" required style="max-width:140px">
                                                <?php endif; ?>
                                                <button class="btn btn-outline-primary btn-sm">บันทึก</button>
                                                </form>

                                                <form method="post" onsubmit="return confirm('ลบรอบนี้?')" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="dep_delete">
                                                    <input type="hidden" name="tour_id" value="<?= (int)$t['id'] ?>">
                                                    <input type="hidden" name="dep_id" value="<?= (int)$d['id'] ?>">
                                                    <input type="hidden" name="keep_depart_modal" value="<?= (int)$t['id'] ?>">
                                                    <?php if (!can('bypass_confirm')): ?>
                                                        <input type="hidden" name="secret_confirm" value="">
                                                    <?php endif; ?>
                                                    <button class="btn btn-outline-danger btn-sm ms-1" onclick="<?php if (!can('bypass_confirm')): ?>return depAskSecret(this)<?php else: ?>return true<?php endif; ?>">ลบ</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-muted mb-3">ยังไม่มีรอบเดินทาง</div>
                    <?php endif; ?>

                    <hr>
                    <h6 class="mb-2">+ เพิ่มหลายช่วง (Add หลายแถวแล้วบันทึกครั้งเดียว)</h6>
                    <form method="post" id="depBulkForm<?= (int)$t['id'] ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="dep_bulk_create">
                        <input type="hidden" name="tour_id" value="<?= (int)$t['id'] ?>">
                        <input type="hidden" name="keep_depart_modal" value="<?= (int)$t['id'] ?>">

                        <div class="table-responsive">
                            <table class="table table-sm align-middle" id="depTable<?= (int)$t['id'] ?>">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width:150px">เริ่ม</th>
                                        <th style="min-width:150px">สิ้นสุด</th>
                                        <th class="text-end" style="min-width:120px">จำนวนรับ</th>
                                        <th class="text-end" style="min-width:140px">ราคา (THB)</th>
                                        <th style="width:70px"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <?php if (!can('bypass_confirm')): ?>
                            <div class="mb-2" style="max-width:260px">
                                <input type="password" name="secret_confirm" class="form-control form-control-sm" placeholder="รหัสลับยืนยัน" required>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="depAddRow(<?= (int)$t['id'] ?>)">+ เพิ่มแถว</button>
                            <button type="submit" class="btn btn-primary btn-sm">บันทึกชุดที่เพิ่ม</button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tour Detail Modal -->
    <div class="modal fade" id="tourModal<?= (int)$t['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        รายละเอียดโปรแกรม • <?= htmlspecialchars($t['code']) ?> — <?= htmlspecialchars($t['name'] ?: '-') ?>
                        <span class="badge bg-<?= $t['tour_type'] === 'group' ? 'primary' : 'success' ?> ms-2"><?= htmlspecialchars(ucfirst($t['tour_type'])) ?></span>
                        <span class="badge <?= $marketClass ?> ms-1"><?= htmlspecialchars($marketLabel) ?></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="md-2"><strong>โลโก้สายการบิน:</strong>
                                <?php if (!empty($t['airline_logo_path'])): ?>
                                    <img src="<?= BASE_URL . '../../' . htmlspecialchars($t['airline_logo_path']) ?>" class="img-fluid rounded border" alt="airline logo" width="20%">
                                <?php else: ?>
                                    <div class="text-muted">ไม่มีโลโก้</div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-2"><strong>ชื่อโปรแกรม:</strong> <?= htmlspecialchars($t['name'] ?: '-') ?></div>
                            <div class="mb-2"><strong>ตลาด:</strong> <?= htmlspecialchars($marketLabel) ?></div>
                            <div class="mb-2"><strong>ประเทศ/เมือง:</strong> <?= htmlspecialchars($t['country_name'] ?: '-') ?><?= $t['city_name'] ? ' • ' . htmlspecialchars($t['city_name']) : '' ?></div>
                            <div class="mb-2"><strong>ช่วงเวลาเดินทาง:</strong> <?= htmlspecialchars($t['travel_window']) ?> • <strong>ระยะเวลา:</strong> <?= (int)$t['duration_days'] ?> วัน</div>
                            <div class="mb-2"><strong>สายการบิน:</strong> <?= htmlspecialchars(trim(($t['airline_name'] ?: '-') . ' ' . ($t['airline_iata'] ? '(' . $t['airline_iata'] . ')' : ''))) ?></div>
                            <div class="mb-2"><strong>ต้นทาง:</strong> <?= htmlspecialchars(trim(($t['origin_name'] ?: '-') . ' ' . ($t['origin_iata'] ? '(' . $t['origin_iata'] . ')' : ''))) ?></div>
                            <div class="mb-2"><strong>ปลายทาง:</strong> <?= htmlspecialchars(trim(($t['dest_name'] ?: '-') . ' ' . ($t['dest_iata'] ? '(' . $t['dest_iata'] . ')' : ''))) ?></div>
                            <div class="mb-2"><strong>ราคา:</strong> <?= number_format((float)$t['price'], 2) ?> THB</div>

                            <?php if (!empty($t['long_description'])): ?>
                                <div class="mt-3">
                                    <strong>รายละเอียดโปรแกรม (แบบยาว):</strong>
                                    <div class="border rounded p-2 mt-1 bg-light" style="white-space:pre-wrap"><?= nl2br(htmlspecialchars($t['long_description'])) ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($t['pdf_path'])): ?>
                                <div class="mt-3">
                                    <a class="btn btn-outline-secondary btn-sm" target="_blank" href="<?= BASE_URL . '../../' . htmlspecialchars($t['pdf_path']) ?>">เปิดไฟล์โปรแกรม (PDF)</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($t['cover_image_path'])): ?>
                                <div class="mb-2"><strong>รูปปกโปรแกรม:</strong></div>
                                <img src="<?= BASE_URL . '../../' . htmlspecialchars($t['cover_image_path']) ?>" class="img-fluid rounded border" alt="cover image">
                            <?php else: ?>
                                <div class="text-muted">ไม่มีรูปปก</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr>
                    <h6 class="mb-3">รายละเอียดรายวัน (แก้ไข/ลบได้ทีละวัน)</h6>

                    <?php if ($dayrows): foreach ($dayrows as $d): ?>
                            <div class="border rounded p-3 mb-2">
                                <form method="post" class="row g-2 align-items-start">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="day_update">
                                    <input type="hidden" name="tour_id" value="<?= (int)$t['id'] ?>">
                                    <input type="hidden" name="day_id" value="<?= (int)$d['id'] ?>">
                                    <input type="hidden" name="keep_modal" value="<?= (int)$t['id'] ?>">

                                    <div class="col-12 col-md-2">
                                        <label class="form-label">วัน</label>
                                        <input type="number" min="1" name="day_no" class="form-control" value="<?= (int)$d['day_no'] ?>" required>
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <label class="form-label">รายละเอียดกิจกรรม</label>
                                        <textarea name="description" rows="3" class="form-control" required><?= htmlspecialchars($d['description']) ?></textarea>
                                    </div>
                                    <div class="col-12 col-md-2 d-flex gap-2 align-items-end">
                                        <?php if (!can('bypass_confirm')): ?>
                                            <input type="password" name="secret_confirm" class="form-control form-control-sm" placeholder="รหัสลับ" required>
                                        <?php endif; ?>
                                        <button type="submit" class="btn btn-outline-primary btn-sm">บันทึก</button>
                                    </div>
                                </form>

                                <form method="post" onsubmit="return confirm('ยืนยันลบ Day <?= (int)$d['day_no'] ?> ของทัวร์นี้?')" class="mt-2 d-flex gap-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="day_delete">
                                    <input type="hidden" name="tour_id" value="<?= (int)$t['id'] ?>">
                                    <input type="hidden" name="day_id" value="<?= (int)$d['id'] ?>">
                                    <input type="hidden" name="keep_modal" value="<?= (int)$t['id'] ?>">
                                    <?php if (!can('bypass_confirm')): ?>
                                        <input type="password" name="secret_confirm" class="form-control form-control-sm" placeholder="รหัสลับ" required style="max-width:220px">
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-outline-danger btn-sm">ลบวันนี้</button>
                                </form>
                            </div>
                        <?php endforeach;
                    else: ?>
                        <div class="text-muted">ยังไม่มีรายละเอียดรายวัน</div>
                    <?php endif; ?>

                    <div class="mt-3 p-3 border rounded bg-light">
                        <h6 class="mb-2">+ เพิ่มวันใหม่</h6>
                        <form method="post" class="row g-2 align-items-start">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="day_create">
                            <input type="hidden" name="tour_id" value="<?= (int)$t['id'] ?>">
                            <input type="hidden" name="keep_modal" value="<?= (int)$t['id'] ?>">

                            <div class="col-12 col-md-2">
                                <label class="form-label">วัน</label>
                                <input type="number" name="day_no" min="1" class="form-control" required>
                            </div>
                            <div class="col-12 col-md-8">
                                <label class="form-label">รายละเอียดกิจกรรม</label>
                                <textarea name="description" rows="3" class="form-control" required></textarea>
                            </div>
                            <div class="col-12 col-md-2 d-flex gap-2 align-items-end">
                                <?php if (!can('bypass_confirm')): ?>
                                    <input type="password" name="secret_confirm" class="form-control form-control-sm" placeholder="รหัสลับ" required>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-primary btn-sm">เพิ่มวัน</button>
                            </div>
                        </form>
                    </div>

                    <hr class="my-4">

                    <!-- แก้ไขด่วน (ข้อมูลหลักของทัวร์) -->
                    <details>
                        <summary class="mb-2">แก้ไขโปรแกรมทัวร์ (Quick edit)</summary>
                        <form method="post" enctype="multipart/form-data" class="border rounded p-3 mt-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                            <input type="hidden" name="keep_modal" value="<?= (int)$t['id'] ?>">

                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">รหัสทัวร์</label>
                                    <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($t['code']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ชื่อโปรแกรมทัวร์</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($t['name']) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">ช่วงเวลาเดินทาง (ข้อความ)</label>
                                    <input type="text" name="travel_window" class="form-control" value="<?= htmlspecialchars($t['travel_window']) ?>" required>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">รายละเอียดโปรแกรม (แบบยาว)</label>
                                    <textarea name="long_description" rows="5" class="form-control" placeholder="รายละเอียดโปรแกรมโดยรวม"><?= htmlspecialchars($t['long_description'] ?? '') ?></textarea>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">ระยะเวลา (วัน)</label>
                                    <input type="number" min="1" name="duration_days" class="form-control" value="<?= (int)$t['duration_days'] ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">ประเทศ</label>
                                    <select name="country_id" class="form-select" required>
                                        <?php foreach ($countries as $c): ?>
                                            <option value="<?= $c['id'] ?>" <?= (int)$c['id'] === (int)$t['country_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">เมือง (ตามประเทศ)</label>
                                    <select name="city_id" class="form-select">
                                        <option value="">-- เลือกเมือง --</option>
                                        <?php
                                        $city_stmt = $pdo->prepare("SELECT id, COALESCE(name_th,name_en) AS name FROM cities WHERE country_id=? ORDER BY name");
                                        $city_stmt->execute([$t['country_id']]);
                                        $city_opts = $city_stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($city_opts as $ci): ?>
                                            <option value="<?= $ci['id'] ?>" <?= (int)$ci['id'] === (int)$t['city_id'] ? 'selected' : '' ?>><?= htmlspecialchars($ci['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">* หากเปลี่ยนประเทศ ต้องเซฟก่อนแล้วค่อยแก้เมือง</div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">สายการบิน</label>
                                    <select name="airline_id" class="form-select">
                                        <option value="">-- เลือกสายการบิน --</option>
                                        <?php foreach ($airlines as $a): ?>
                                            <option value="<?= $a['id'] ?>" <?= (int)$a['id'] === (int)$t['airline_id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?><?= $a['iata'] ? ' (' . $a['iata'] . ')' : '' ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">สนามบินต้นทาง</label>
                                    <select name="origin_airport_id" class="form-select">
                                        <?php foreach ($airports as $ap): ?>
                                            <option value="<?= $ap['id'] ?>" <?= (int)$ap['id'] === (int)$t['origin_airport_id'] ? 'selected' : '' ?>><?= htmlspecialchars($ap['name']) ?><?= $ap['iata'] ? ' (' . $ap['iata'] . ')' : '' ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">สนามบินปลายทาง</label>
                                    <select name="dest_airport_id" class="form-select">
                                        <?php foreach ($airports as $ap): ?>
                                            <option value="<?= $ap['id'] ?>" <?= (int)$ap['id'] === (int)$t['dest_airport_id'] ? 'selected' : '' ?>><?= htmlspecialchars($ap['name']) ?><?= $ap['iata'] ? ' (' . $ap['iata'] . ')' : '' ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">ราคา (THB)</label>
                                    <input type="number" step="0.01" name="price" class="form-control" value="<?= (float)$t['price'] ?>" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">ประเภท</label>
                                    <select name="tour_type" class="form-select">
                                        <option value="group" <?= $t['tour_type'] === 'group' ? 'selected' : '' ?>>Group</option>
                                        <option value="private" <?= $t['tour_type'] === 'private' ? 'selected' : '' ?>>Private</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">ตลาดทัวร์</label>
                                    <select name="market_type" class="form-select">
                                        <option value="domestic" <?= ($t['market_type'] ?? 'outbound') === 'domestic' ? 'selected' : '' ?>>Domestic – คนไทยเที่ยวไทย</option>
                                        <option value="inbound" <?= ($t['market_type'] ?? 'outbound') === 'inbound' ? 'selected' : '' ?>>Inbound – ต่างชาติเที่ยวไทย</option>
                                        <option value="outbound" <?= ($t['market_type'] ?? 'outbound') === 'outbound' ? 'selected' : '' ?>>Outbound – คนไทยเที่ยวต่างประเทศ</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">โลโก้สายการบิน (อัปโหลดทับ)</label>
                                    <input type="file" name="airline_logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg,image/*">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">ไฟล์โปรแกรม (PDF) (อัปโหลดทับ)</label>
                                    <input type="file" name="tour_pdf" class="form-control" accept=".pdf,application/pdf">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">รูปปกโปรแกรมทัวร์ (อัปโหลดทับ)</label>
                                    <input type="file" name="cover_image" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/*">
                                    <div class="form-text">ปล่อยว่างหากไม่ต้องการเปลี่ยน</div>
                                </div>

                                <?php if (!can('bypass_confirm')): ?>
                                    <div class="col-md-4">
                                        <label class="form-label">รหัสลับสำหรับการยืนยัน</label>
                                        <input type="password" name="secret_confirm" class="form-control" required>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mt-3 d-flex gap-2">
                                <button type="submit" class="btn btn-outline-primary btn-sm">บันทึกการแก้ไข</button>
                            </div>
                        </form>
                    </details>

                    <hr>

                    <!-- ลบทัวร์ทั้งโปรแกรม -->
                    <form method="post" onsubmit="return confirm('ยืนยันลบโปรแกรมทัวร์นี้?')" class="d-flex align-items-center gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                        <?php if (!can('bypass_confirm')): ?>
                            <input type="password" name="secret_confirm" class="form-control form-control-sm" placeholder="รหัสลับยืนยัน" required style="max-width:220px">
                        <?php endif; ?>
                        <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
                    </form>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
    (function() {
        // โหลดเมืองตามประเทศ (เฉพาะในฟอร์มสร้าง)
        const country = document.getElementById('country');
        const city = document.getElementById('city');
        country?.addEventListener('change', async () => {
            const cid = country.value || '';
            if (!city) return;
            city.innerHTML = '<option value="">-- เลือกเมือง --</option>';
            if (!cid) return;
            const res = await fetch('<?= BASE_URL ?>/api/cities.php?country_id=' + encodeURIComponent(cid));
            const data = await res.json();
            for (const i of data) {
                const opt = document.createElement('option');
                opt.value = i.id;
                opt.textContent = i.name_th || i.name_en;
                city.appendChild(opt);
            }
        });

        // Lock ประเทศไทย + ช่วยอธิบายสนามบิน ตาม market_type
        const marketRadios = document.querySelectorAll('input[name="market_type"]');
        const THAILAND_ID = <?= $thailand_id ? (int)$thailand_id : 'null' ?>;
        const airportHelp = document.getElementById('airportHelpText');
        const originAirport = document.querySelector('select[name="origin_airport_id"]');
        const destAirport = document.querySelector('select[name="dest_airport_id"]');

        function getMarketValue() {
            const checked = document.querySelector('input[name="market_type"]:checked');
            return checked ? checked.value : 'outbound';
        }

        function applyMarketCountryLock() {
            if (!country || !THAILAND_ID) return;
            const val = getMarketValue();
            if (val === 'domestic' || val === 'inbound') {
                if (String(country.value) !== String(THAILAND_ID)) {
                    country.value = String(THAILAND_ID);
                    country.dispatchEvent(new Event('change'));
                }
            }
        }

        function applyAirportRequired() {
            if (!originAirport || !destAirport) return;
            const val = getMarketValue();
            if (val === 'outbound') {
                originAirport.setAttribute('required', 'required');
                destAirport.setAttribute('required', 'required');
            } else {
                originAirport.removeAttribute('required');
                destAirport.removeAttribute('required');
            }
        }

        function updateAirportHelp() {
            if (!airportHelp) return;
            const val = getMarketValue();
            if (val === 'outbound') {
                airportHelp.textContent = 'ทัวร์ Outbound: ต้องเลือกสนามบินต้นทาง (ออกจากไทย) และปลายทาง (ประเทศปลายทาง)';
            } else if (val === 'inbound') {
                airportHelp.textContent = 'ทัวร์ Inbound: สนามบินเป็นตัวเลือก (ส่วนใหญ่ Land only) แต่ถ้ามีไฟลต์ภายในไทยแนะนำให้กรอกเพื่ออ้างอิง';
            } else {
                airportHelp.textContent = 'ทัวร์ Domestic: อาจไม่ใช้สนามบิน (รถทัวร์/รถไฟ) หากมีไฟลต์ภายในประเทศสามารถกรอกต้นทาง/ปลายทางได้';
            }
        }

        marketRadios.forEach(r => {
            r.addEventListener('change', () => {
                applyMarketCountryLock();
                applyAirportRequired();
                updateAirportHelp();
            });
        });

        // Day-by-day dynamic (ฟอร์มสร้างทัวร์)
        const wrap = document.getElementById('days-wrap');
        const addBtn = document.getElementById('btnAddDay');

        function addDayRow(dayNo = '', desc = '') {
            const row = document.createElement('div');
            row.className = 'border rounded p-3 mb-2 bg-white';
            row.innerHTML = `
      <div class="row g-2 align-items-start">
        <div class="col-12 col-md-2">
          <label class="form-label">วันที่</label>
          <input type="number" name="day_no[]" class="form-control" min="1" value="${dayNo||''}" required>
        </div>
        <div class="col-12 col-md-9">
          <label class="form-label">รายละเอียดกิจกรรม</label>
          <textarea name="day_desc[]" class="form-control" rows="3" required>${desc||''}</textarea>
        </div>
        <div class="col-12 col-md-1 d-flex align-items-end">
          <button type="button" class="btn btn-outline-danger btn-sm w-100 btnRemove">ลบ</button>
        </div>
      </div>`;
            row.querySelector('.btnRemove').addEventListener('click', () => row.remove());
            wrap.appendChild(row);
        }
        addBtn?.addEventListener('click', () => addDayRow());
        addDayRow(1, ''); // แถวแรก

        // ===== Cost calculator (ต้นทุน/ราคาขาย) =====
        const costCategories = [
            { key: 'airfare', label: 'Airfare (ตั๋วเครื่องบิน + Tax + Fuel)' },
            { key: 'hotel', label: 'Hotel (ห้องพัก + อาหารเช้า + SGL Supp)' },
            { key: 'transport', label: 'Transport (รถทัวร์ + น้ำมัน + ทางด่วน + OT + คนขับ)' },
            { key: 'guide', label: 'Guide (ค่าไกด์ + ทิป + ค่าอาหาร)' },
            { key: 'meals', label: 'Meals (ราคา/หัว/มื้อ)' },
            { key: 'entrance', label: 'Entrance (สถานที่ทุกจุด)' },
            { key: 'insurance', label: 'Insurance (ประกันเดินทาง)' },
            { key: 'documents', label: 'Documents (ป้าย + เอกสารทัวร์)' },
            { key: 'operating', label: 'Operating (ค่าดำเนินงานบริษัท)' },
            { key: 'marketing', label: 'Marketing (ค่าโฆษณาและคอมมิชชั่น)' },
            { key: 'fx', label: 'FX Risk (กันค่าเงิน)' },
            { key: 'contingency', label: 'Contingency (สำรองฉุกเฉิน)' },
            { key: 'profit', label: 'Profit (กำไรบริษัท)' }
        ];

        const costDayCount = document.getElementById('costDayCount');
        const costPax = document.getElementById('costPax');
        const profitPercent = document.getElementById('profitPercent');
        const costAccordion = document.getElementById('costDayAccordion');
        const costTotalsBody = document.getElementById('costTotalsBody');
        const costSummaryBox = document.getElementById('costSummaryBox');
        const costResetBtn = document.getElementById('costReset');
        const durationInput = document.querySelector('input[name="duration_days"]');
        const costPayloadField = document.getElementById('costPayload');
        const costPaxField = document.getElementById('costPaxField');
        const costProfitPercentField = document.getElementById('costProfitPercentField');
        const costDayCountField = document.getElementById('costDayCountField');
        const costBaseField = document.getElementById('costBaseCost');
        const costManualField = document.getElementById('costManualProfit');
        const costProfitAmountField = document.getElementById('costProfitAmount');
        const costSellingField = document.getElementById('costSellingTotal');
        const costPerPaxField = document.getElementById('costPerPax');
        const costPerDayField = document.getElementById('costPerDay');

        const formatNumber = (n) => (n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        function readCurrentCostState() {
            const state = {};
            costAccordion?.querySelectorAll('.cost-input').forEach((input) => {
                const day = parseInt(input.dataset.day, 10) || 0;
                const cat = input.dataset.category;
                const val = parseFloat(input.value) || 0;
                if (!day || !cat) return;
                if (!state[day]) state[day] = {};
                state[day][cat] = val;
            });
            return state;
        }

        function buildCostRows() {
            if (!costTotalsBody) return;
            costTotalsBody.innerHTML = '';
            costCategories.forEach((cat) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
          <th scope="row">${cat.label}</th>
          <td class="text-end" data-total-cat="${cat.key}">0.00</td>
        `;
                costTotalsBody.appendChild(tr);
            });
        }

        function createCostDayCard(dayNo) {
            const card = document.createElement('div');
            card.className = 'accordion-item mb-2';
            card.innerHTML = `
        <h2 class="accordion-header" id="costHead${dayNo}">
          <button class="accordion-button collapsed d-flex justify-content-between" type="button" data-bs-toggle="collapse" data-bs-target="#costDay${dayNo}" aria-expanded="false" aria-controls="costDay${dayNo}">
            <span>Day ${dayNo}</span>
            <span class="badge bg-light text-dark ms-2" data-day-total-badge="${dayNo}">0.00 THB</span>
          </button>
        </h2>
        <div id="costDay${dayNo}" class="accordion-collapse collapse" aria-labelledby="costHead${dayNo}" data-bs-parent="#costDayAccordion">
          <div class="accordion-body">
            <div class="row g-3">
            </div>
            <div class="text-end small mt-2">รวมวัน Day ${dayNo}: <strong data-day-total="${dayNo}">0.00</strong> THB</div>
          </div>
        </div>
      `;

            const rowWrap = card.querySelector('.row');
            costCategories.forEach((cat) => {
                const col = document.createElement('div');
                col.className = 'col-12 col-md-6 col-lg-4';
                col.innerHTML = `
          <label class="form-label">${cat.label}</label>
          <input type="number" class="form-control form-control-sm cost-input" data-day="${dayNo}" data-category="${cat.key}" min="0" step="0.01" value="0">
        `;
                rowWrap?.appendChild(col);
            });
            return card;
        }

        function rebuildCostDays(count) {
            if (!costAccordion) return;
            const prev = readCurrentCostState();
            costAccordion.innerHTML = '';
            buildCostRows();

            for (let i = 1; i <= count; i++) {
                const card = createCostDayCard(i);
                costAccordion.appendChild(card);
            }

            costAccordion.querySelectorAll('.cost-input').forEach((input) => {
                const day = parseInt(input.dataset.day, 10);
                const cat = input.dataset.category;
                if (prev[day] && typeof prev[day][cat] !== 'undefined') {
                    input.value = prev[day][cat];
                }
            });
        }

        function recalcCosts() {
            if (!costAccordion) return;
            const totalsPerCat = {};
            const totalsPerDay = {};

            costAccordion.querySelectorAll('.cost-input').forEach((input) => {
                const cat = input.dataset.category;
                const day = parseInt(input.dataset.day, 10) || 0;
                const val = parseFloat(input.value) || 0;
                if (!cat || !day) return;
                totalsPerCat[cat] = (totalsPerCat[cat] || 0) + val;
                totalsPerDay[day] = (totalsPerDay[day] || 0) + val;
            });

            const baseCost = Object.entries(totalsPerCat)
                .filter(([k]) => k !== 'profit')
                .reduce((sum, [, v]) => sum + v, 0);
            const manualProfit = totalsPerCat.profit || 0;
            const pax = Math.max(parseInt(costPax?.value || '0', 10), 0) || 0;
            const dayCount = Math.max(parseInt(costDayCount?.value || '0', 10), 1);
            const profitRate = Math.max(parseFloat(profitPercent?.value || '0'), 0);

            const profitByPercent = baseCost * (profitRate / 100);
            const sellingTotal = baseCost + manualProfit + profitByPercent;
            const perPax = pax > 0 ? sellingTotal / pax : 0;
            const perDay = dayCount > 0 ? sellingTotal / dayCount : 0;

            // Update totals per category table
            costTotalsBody?.querySelectorAll('[data-total-cat]').forEach((cell) => {
                const key = cell.getAttribute('data-total-cat');
                cell.textContent = formatNumber(totalsPerCat[key] || 0);
            });

            // Update per-day badges
            costAccordion.querySelectorAll('[data-day-total]').forEach((el) => {
                const day = parseInt(el.getAttribute('data-day-total'), 10);
                el.textContent = formatNumber(totalsPerDay[day] || 0);
            });
            costAccordion.querySelectorAll('[data-day-total-badge]').forEach((el) => {
                const day = parseInt(el.getAttribute('data-day-total-badge'), 10);
                el.textContent = `${formatNumber(totalsPerDay[day] || 0)} THB`;
            });

            if (costSummaryBox) {
                costSummaryBox.innerHTML = `
          <div><strong>ต้นทุนรวม (ไม่รวมกำไร %):</strong> ${formatNumber(baseCost)} THB</div>
          <div><strong>กำไรที่กรอกเป็นตัวเลข:</strong> ${formatNumber(manualProfit)} THB</div>
          <div><strong>กำไรตามเปอร์เซ็นต์:</strong> ${formatNumber(profitByPercent)} THB (${formatNumber(profitRate)}%)</div>
          <div class="mt-2"><strong>ราคาขายรวมโดยประมาณ:</strong> ${formatNumber(sellingTotal)} THB</div>
          <div class="small text-muted mb-0">เฉลี่ยต่อคน: ${formatNumber(perPax)} THB • เฉลี่ยต่อวัน: ${formatNumber(perDay)} THB</div>
        `;
            }

            const payload = {
                pax,
                profitPercent: profitRate,
                dayCount,
                totalsPerCategory: totalsPerCat,
                totalsPerDay,
                baseCost,
                manualProfit,
                profitByPercent,
                sellingTotal,
                perPax,
                perDay
            };

            if (costPayloadField) costPayloadField.value = JSON.stringify(payload);
            if (costPaxField) costPaxField.value = pax;
            if (costProfitPercentField) costProfitPercentField.value = profitRate;
            if (costDayCountField) costDayCountField.value = dayCount;
            if (costBaseField) costBaseField.value = baseCost;
            if (costManualField) costManualField.value = manualProfit;
            if (costProfitAmountField) costProfitAmountField.value = profitByPercent;
            if (costSellingField) costSellingField.value = sellingTotal;
            if (costPerPaxField) costPerPaxField.value = perPax;
            if (costPerDayField) costPerDayField.value = perDay;
        }

        function syncDayCountFromDuration() {
            if (!durationInput || !costDayCount) return;
            const n = parseInt(durationInput.value, 10);
            if (n > 0) costDayCount.value = n;
        }

        costDayCount?.addEventListener('change', () => {
            const n = Math.max(parseInt(costDayCount.value, 10) || 1, 1);
            costDayCount.value = n;
            rebuildCostDays(n);
            recalcCosts();
        });
        costPax?.addEventListener('input', recalcCosts);
        profitPercent?.addEventListener('input', recalcCosts);
        costResetBtn?.addEventListener('click', () => {
            costAccordion?.querySelectorAll('.cost-input').forEach((input) => {
                input.value = '0';
            });
            recalcCosts();
        });
        document.addEventListener('input', (e) => {
            if (e.target.classList && e.target.classList.contains('cost-input')) recalcCosts();
        });

        const createForm = document.getElementById('tourCreateForm');
        createForm?.addEventListener('submit', () => recalcCosts());

        durationInput?.addEventListener('input', () => {
            syncDayCountFromDuration();
            const n = Math.max(parseInt(costDayCount?.value || '1', 10), 1);
            rebuildCostDays(n);
            recalcCosts();
        });

        // เรียกครั้งแรกตอนโหลดหน้า
        applyMarketCountryLock();
        applyAirportRequired();
        updateAirportHelp();
        syncDayCountFromDuration();
        rebuildCostDays(Math.max(parseInt(costDayCount?.value || '1', 10), 1));
        recalcCosts();

        // Toggle sale switch
        const isDirector = <?= can('bypass_confirm') ? 'true' : 'false' ?>;
        document.querySelectorAll('.sale-toggle').forEach(chk => {
            chk.addEventListener('change', (e) => {
                const el = e.currentTarget;
                const tid = el.getAttribute('data-tid');
                const form = document.getElementById('tsf-' + tid);
                if (!form) return;

                form.querySelector('input[name="on_sale"]').value = el.checked ? '1' : '0';

                if (!isDirector) {
                    const pass = prompt('กรอกรหัสลับเพื่อยืนยันการเปลี่ยนสถานะขาย:');
                    if (!pass) {
                        el.checked = !el.checked; // revert
                        return;
                    }
                    const secretInput = form.querySelector('input[name="secret_confirm"]');
                    if (secretInput) secretInput.value = pass;
                }

                form.submit();
            });
        });
    })();

    // ===== Departure dynamic add (หลายแถว) =====
    function depAddRow(tid) {
        const tbody = document.querySelector('#depTable' + tid + ' tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
      <td><input type="date" name="dep_start[]" class="form-control form-control-sm" required></td>
      <td><input type="date" name="dep_end[]" class="form-control form-control-sm" required></td>
      <td><input type="number" name="dep_capacity[]" min="0" class="form-control form-control-sm text-end" value="0"></td>
      <td><input type="number" name="dep_price[]" step="0.01" min="0" class="form-control form-control-sm text-end" value="0.00"></td>
      <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('tr').remove()">ลบ</button></td>
    `;
        tbody.appendChild(tr);
    }
    // เติมแถวเริ่มต้น 1 แถวเมื่อ modal เปิด
    document.addEventListener('shown.bs.modal', function(e) {
        const m = e.target;
        if (m.id && m.id.startsWith('departModal')) {
            const tid = m.id.replace('departModal', '');
            const tbody = document.querySelector('#depTable' + tid + ' tbody');
            if (tbody && tbody.children.length === 0) depAddRow(tid);
        }
    });

    // ยืนยันรหัสลับตอนลบ (กรณีไม่ใช่ Director)
    function depAskSecret(btn) {
        const form = btn.closest('form');
        const input = form.querySelector('input[name="secret_confirm"]');
        if (input) {
            const pass = prompt('กรอกรหัสลับเพื่อยืนยันการลบ:');
            if (!pass) return false;
            input.value = pass;
        }
        return true;
    }

    // อัปเดต badge "เต็ม" ทันทีเมื่อแก้จำนวนรับในตารางรายการรอบ
    document.addEventListener('input', function(e) {
        const el = e.target;
        if (!el.classList || !el.classList.contains('dep-cap')) return;
        const targetSel = el.getAttribute('data-target');
        const box = targetSel ? document.querySelector(targetSel) : null;
        if (!box) return;

        const n = parseInt(el.value, 10);
        if (!isNaN(n) && n <= 0) {
            box.innerHTML = '<span class="badge bg-warning text-dark">เต็ม</span>';
        } else {
            box.innerHTML = '';
        }
    });
</script>

<?php if ($autoOpenModalTourId): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var el = document.getElementById('tourModal<?= (int)$autoOpenModalTourId ?>');
            if (el) {
                new bootstrap.Modal(el).show();
            }
        });
    </script>
<?php endif; ?>

<?php if ($autoOpenDepartTourId): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var el = document.getElementById('departModal<?= (int)$autoOpenDepartTourId ?>');
            if (el) {
                new bootstrap.Modal(el).show();
            }
        });
    </script>
<?php endif; ?>

<?php render_footer(); ?>
