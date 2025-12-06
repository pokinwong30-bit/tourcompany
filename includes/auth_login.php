<?php
// auth_login.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$email    = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
  http_response_code(422);
  echo json_encode(['ok'=>false,'error'=>'invalid_input']); exit;
}

try {
  $st = $pdo->prepare("SELECT * FROM members WHERE email = :e AND status = 'active' LIMIT 1");
  $st->execute([':e'=>$email]);
  $m = $st->fetch(PDO::FETCH_ASSOC);

  if (!$m || !password_verify($password, (string)$m['password_hash'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'auth_failed']); exit;
  }

  // update last login
  $pdo->prepare("UPDATE members SET last_login_at = NOW() WHERE id = :id")->execute([':id'=>(int)$m['id']]);

  auth_set_member($m);
  echo json_encode(['ok'=>true,'member'=>auth_member()]); exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'server_error']); exit;
}
