<?php

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    public static function createMailer(): PHPMailer
    {
        $config = require __DIR__ . '/../../config/config.php';
        $mailConfig = $config['mail'];

        if (
            empty($mailConfig['host']) ||
            empty($mailConfig['username']) ||
            empty($mailConfig['password']) ||
            empty($mailConfig['from_address'])
        ) {
        throw new \RuntimeException('Mail SMTP is not configured correctly.');
    }

$mail = new PHPMailer(true);

$mail->isSMTP();
$mail->Host = trim($mailConfig['host']);
$mail->SMTPAuth = true;
$mail->Username = trim($mailConfig['username']);
$mail->Password = (string) $mailConfig['password'];
$mail->Port = (int) $mailConfig['port'];

$encryption = strtolower(trim($mailConfig['encryption'] ?? 'tls'));

if ($encryption === 'tls') {
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->SMTPAutoTLS = true;
} elseif ($encryption === 'ssl') {
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->SMTPAutoTLS = false;
} else {
$mail->SMTPSecure = false;
$mail->SMTPAutoTLS = false;
}

$mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';

$mail->setFrom(
    trim($mailConfig['from_address']),
    $mailConfig['from_name'] ?: 'Royal Splash'
);

if (!empty($mailConfig['reply_to'])) {
    $mail->addReplyTo(
        trim($mailConfig['reply_to']),
        $mailConfig['from_name'] ?: 'Royal Splash'
    );
}

return $mail;
}
}
