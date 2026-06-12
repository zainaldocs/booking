<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
require __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';

function send_email($pdo, $to_email, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = get_setting($pdo, 'smtp_host');
        $mail->SMTPAuth   = true;
        $mail->Username   = get_setting($pdo, 'smtp_user');
        $mail->Password   = get_setting($pdo, 'smtp_pass');
        $mail->Port       = get_setting($pdo, 'smtp_port');

        // Pengaturan Enkripsi SSL/TLS
        $secure_mode = get_setting($pdo, 'smtp_secure');
        if ($secure_mode == 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($secure_mode == 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPAutoTLS = false;
            $mail->SMTPSecure = ''; // none
        }

        $mail->setFrom(get_setting($pdo, 'smtp_user'), 'Sistem Booking Ruangan');
        
        // Memisahkan multiple email dengan koma atau titik koma
        $emails = preg_split('/[,;]+/', $to_email);
        foreach ($emails as $em) {
            $em = trim($em);
            if (!empty($em)) {
                $mail->addAddress($em);
            }
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}
?>
