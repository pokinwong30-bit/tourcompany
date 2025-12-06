<?php
// includes/audit.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

function _ip_to_bin($ip) {
  if (!$ip) return null;
  $bin = @inet_pton($ip);
  return $bin === false ? null : $bin;
}

function _bin_to_ip($bin) {
  if ($bin === null) return null;
  $ip = @inet_ntop($bin);
  return $ip ?: null;
}

/**
 * บันทึก Audit Log
 * @param string $action       เช่น 'change_role','post_create','post_edit','post_delete','login_success','login_failed'
 * @param string|null $table   เช่น 'users','posts'
 * @param string|int|null $targetId ไอดีแถวเป้าหมาย
 * @param array $meta          รายละเอียดเพิ่มเติม จะถูก json_encode เช่น ['old_role'=>'officer','new_role'=>'manager']
 */
function audit_log($action, $table=null, $targetId=null, array $meta=[]) {
  global $pdo;

  $actor = current_user();
  $actorId = $actor['id'] ?? null;
  $actorRole = $actor['role'] ?? 'guest';

  $ip = $_SERVER['REMOTE_ADDR'] ?? null;
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

  $stmt = $pdo->prepare("
    INSERT INTO audit_logs (action, actor_id, actor_role, target_table, target_id, details, ip, user_agent)
    VALUES (:action, :actor_id, :actor_role, :target_table, :target_id, :details, :ip, :user_agent)
  ");
  $stmt->bindValue(':action', $action);
  $stmt->bindValue(':actor_id', $actorId, PDO::PARAM_INT);
  $stmt->bindValue(':actor_role', $actorRole);
  $stmt->bindValue(':target_table', $table);
  $stmt->bindValue(':target_id', $targetId);
  $stmt->bindValue(':details', $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null);
  $stmt->bindValue(':ip', _ip_to_bin($ip), PDO::PARAM_LOB);
  $stmt->bindValue(':user_agent', $ua);
  $stmt->execute();
}

/** helper สำหรับหน้าแสดงผล */
function audit_format_row(array $row) {
  // แปลงไอพีจากไบต์ -> ข้อความ
  if (isset($row['ip'])) {
    $row['ip_human'] = _bin_to_ip($row['ip']) ?: '';
  }
  // decode JSON details
  if (!empty($row['details'])) {
    $decoded = json_decode($row['details'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
      $row['details_array'] = $decoded;
    }
  }
  return $row;
}
