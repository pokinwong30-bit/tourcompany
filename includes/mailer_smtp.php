<?php
/**
 * mailer_smtp.php
 * ----------------
 * ใช้ PHPMailer ส่งอีเมลผ่าน SMTP
 * อ่านค่าจาก .env:
 *   MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD,
 *   MAIL_ENCRYPTION, MAIL_FROM_ADDRESS, MAIL_FROM_NAME
 */

require_once __DIR__ . '/config.php';

// ฟังก์ชันส่งอีเมลด้วย SMTP ผ่าน PHPMailer
function send_mail_html($to, $subject, $html, $from = null)
{
    // โหลด autoloader จาก Composer (ต้องมี phpmailer/phpmailer)
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) {
        throw new Exception("PHPMailer library not found. Please run: composer require phpmailer/phpmailer");
    }
    require_once $autoload;

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        // ตั้งค่าพื้นฐาน
        $mail->isSMTP();
        $mail->Host       = env('MAIL_HOST', 'smtp.gmail.com');
        $mail->Port       = (int)env('MAIL_PORT', 587);
        $mail->SMTPAuth   = true;
        $mail->Username   = env('MAIL_USERNAME', '');
        $mail->Password   = env('MAIL_PASSWORD', '');
        $mail->CharSet    = 'UTF-8';

        // การเข้ารหัส TLS/SSL
        $enc = strtolower(env('MAIL_ENCRYPTION', 'tls'));
        if ($enc === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($enc === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }

        // จาก (From)
        // ===== ปรับให้ Gmail รองรับและปลอดภัย =====
$fromAddr = $from ?: env('MAIL_FROM_ADDRESS', env('MAIL_USERNAME', 'pokin.wong30@gmail.com'));
$fromName = env('MAIL_FROM_NAME', 'Tour Company System');

// ถ้าไม่มี @ ให้เพิ่ม @gmail.com อัตโนมัติ (กันพิมพ์ผิด)
if (strpos($fromAddr, '@') === false) {
    $fromAddr .= '@gmail.com';
}

// ตั้งค่า From address
$mail->setFrom($fromAddr, $fromName);


        // ผู้รับ (รองรับหลายคน)
        $recipients = array_filter(array_map('trim', explode(',', $to)));
        foreach ($recipients as $email) {
            $mail->addAddress($email);
        }

        // หัวเรื่องและเนื้อหา
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));

        // ส่ง
        $mail->send();
        return true;

    } catch (Throwable $e) {
        // ถ้ามีปัญหา เขียน log
        $logFile = env('MAIL_LOG_PATH', __DIR__ . '/../storage/mail_error.log');
        if (!is_dir(dirname($logFile))) { @mkdir(dirname($logFile), 0775, true); }
        $msg = date('Y-m-d H:i:s') . ' [MAIL ERROR] ' . $e->getMessage() . PHP_EOL;
        file_put_contents($logFile, $msg, FILE_APPEND);
        return false;
    }
}
