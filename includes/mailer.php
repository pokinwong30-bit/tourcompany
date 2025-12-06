<?php
require_once __DIR__ . '/config.php';

function send_mail_html($to, $subject, $html, $from = null) {
  $mailer = env('MAIL_MAILER', 'mail'); // mail | log
  $fromAddr = $from ?: env('MAIL_FROM_ADDRESS', 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
  $fromName = env('MAIL_FROM_NAME', 'System');

  if ($mailer === 'log') {
    $logFile = env('MAIL_LOG_PATH', __DIR__ . '/../storage/mail_log.html');
    if (!is_dir(dirname($logFile))) { @mkdir(dirname($logFile), 0775, true); }
    $stamp = date('Y-m-d H:i:s');
    $entry = "<hr><b>DATE:</b> {$stamp}<br><b>TO:</b> ".htmlspecialchars($to)."<br><b>SUBJECT:</b> ".htmlspecialchars($subject)."<hr>\n".$html."\n";
    file_put_contents($logFile, $entry, FILE_APPEND);
    return true; // ถือว่าสำเร็จ เพื่อให้ flow เดินต่อ
  }

  // โหมดปกติ: PHP mail()
  $headers  = "MIME-Version: 1.0\r\n";
  $headers .= "Content-type: text/html; charset=UTF-8\r\n";
  $headers .= "From: {$fromName} <{$fromAddr}>\r\n";
  return @mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $html, $headers);
}
