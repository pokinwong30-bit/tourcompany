<?php
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function find_user_by_email($email) {
  global $pdo;
  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$email]);
  return $stmt->fetch(PDO::FETCH_ASSOC);
}

function register_user($email, $password, $full_name) {
  global $pdo;
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name) VALUES (?, ?, ?)");
  $stmt->execute([$email, $hash, $full_name]); // role จะเป็น non-admin อัตโนมัติ
  return $pdo->lastInsertId();
}

function login_user($email, $password) {
  $user = find_user_by_email($email);
  if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user'] = [
      'id' => $user['id'],
      'email' => $user['email'],
      'full_name' => $user['full_name'],
      'role' => $user['role']
    ];
    return true;
  }
  return false;
}

function logout_user() {
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
      $params["path"], $params["domain"],
      $params["secure"], $params["httponly"]
    );
  }
  session_destroy();
}

function current_user() {
  return $_SESSION['user'] ?? null;
}

function user_role() {
  return $_SESSION['user']['role'] ?? 'guest';
}

// สิทธิ์ตามสเปค:
// director: add/edit/delete NO password confirm
// manager: add/edit/delete WITH password confirm
// officer: add/edit only WITH password confirm (no delete)
// non-admin: ไม่ใช่แอดมิน (ไม่มีสิทธิ์เข้า backoffice จริง ๆ)
function can($action) {
  // $action: 'view_backoffice', 'create', 'edit', 'delete', 'bypass_confirm'
  $role = user_role();
  switch ($action) {
    case 'view_backoffice':
      return in_array($role, ['director','manager','officer']); // non-admin เข้าไม่ได้
    case 'create':
    case 'edit':
      return in_array($role, ['director','manager','officer']);
    case 'delete':
      return in_array($role, ['director','manager']); // officer ห้ามลบ
    case 'bypass_confirm':
      return $role === 'director';
    default:
      return false;
  }
}
// ===== Shared Secret (สำหรับ Manager/Officer) =====
// "Rati2025!" ถูกซ่อนไว้ด้วยการเข้ารหัสซ้อนสองชั้น (base64 -> base64)
function get_shared_secret() {
    // ชั้นนอก (อย่าใส่ "Rati2025!" ตรงๆ ในโค้ด)
    $outer = 'VW1GMGFUSXdNalVo'; // base64 ของ "UmF0aTIwMjUh"
    // ถอด 2 ชั้น => ได้ "Rati2025!"
    return base64_decode(base64_decode($outer));
  }
  
  function verify_shared_secret($input) {
    // ป้องกัน timing-attack ด้วย hash_equals
    return hash_equals(get_shared_secret(), (string)$input);
  }
  function auth_set_member(array $m): void {
    $_SESSION['member'] = [
      'id'        => (int)$m['id'],
      'email'     => (string)$m['email'],
      'full_name' => (string)$m['full_name'],
      'phone'     => (string)($m['phone'] ?? ''),
    ];
  }
  function auth_member(): ?array { return $_SESSION['member'] ?? null; }
  function auth_logout(): void { unset($_SESSION['member']); }