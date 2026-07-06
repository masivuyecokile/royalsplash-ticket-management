<?php

namespace App\Core;

use PDO;

class CustomerPasswordResetService
{
    public static function sendResetEmail(PDO $db, int $userId): string
    {
        $userStmt = $db->prepare("
            SELECT *
            FROM users
            WHERE id = :id
            AND role = 'customer'
            AND status = 'active'
            LIMIT 1
            ");

            $userStmt->execute([
                    ':id' => $userId,
                    ]);

            $user = $userStmt->fetch();

            if (!$user) {
                throw new \RuntimeException('Customer user not found for password reset.');
            }

        $config = require __DIR__ . '/../../config/config.php';
        $appUrl = rtrim($config['app_url'], '/');

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        $expireOld = $db->prepare("
            UPDATE password_setup_tokens
            SET used_at = NOW()
            WHERE user_id = :user_id
            AND purpose = 'reset_password'
            AND used_at IS NULL
            ");

            $expireOld->execute([
                    ':user_id' => (int) $user->id,
                    ]);

            $insertToken = $db->prepare("
                INSERT INTO password_setup_tokens (
                    user_id,
                    token_hash,
                    purpose,
                    expires_at,
                    used_at
                ) VALUES (
                :user_id,
                :token_hash,
                'reset_password',
                DATE_ADD(NOW(), INTERVAL 60 MINUTE),
                NULL
            )
        ");

        $insertToken->execute([
                ':user_id' => (int) $user->id,
                ':token_hash' => $tokenHash,
                ]);

        $resetLink = $appUrl . '/reset-password?token=' . urlencode($plainToken);

        $mail = MailService::createMailer();

        $mail->addAddress($user->email, $user->full_name);
        $mail->Subject = 'Reset your Royal Splash password';

        $mail->isHTML(true);
        $mail->Body = self::emailHtml($user->full_name, $resetLink);
        $mail->AltBody = self::emailText($user->full_name, $resetLink);

        $mail->send();

        return $resetLink;
    }

private static function emailHtml(string $fullName, string $resetLink): string
{
    $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

    return '
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    </head>
    <body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#061d43;">
    <table width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:30px 12px;">
    <tr>
    <td align="center">
    <table width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:22px;overflow:hidden;border:1px solid #e5ebf5;">
    <tr>
    <td style="background:#061d43;padding:28px;text-align:center;">
    <h1 style="margin:0;color:#ffffff;font-size:28px;">Royal Splash</h1>
    <p style="margin:8px 0 0;color:#d3a22c;font-weight:bold;">Password Reset</p>
    </td>
    </tr>

    <tr>
    <td style="padding:32px;">
    <h2 style="margin:0 0 12px;color:#061d43;">Hi ' . $safeName . ',</h2>

    <p style="font-size:16px;line-height:1.6;color:#52627d;margin:0 0 18px;">
    We received a request to reset your Royal Splash customer account password.
    </p>

    <p style="text-align:center;margin:28px 0;">
    <a href="' . $safeLink . '" style="display:inline-block;background:#d3a22c;color:#061d43;text-decoration:none;font-weight:bold;padding:15px 24px;border-radius:999px;">
    Reset My Password
    </a>
    </p>

    <p style="font-size:14px;line-height:1.6;color:#6b7897;margin:0;">
    This link expires in 60 minutes. If you did not request this reset, you can ignore this email.
    </p>

    <p style="font-size:13px;line-height:1.5;color:#061d43;word-break:break-all;background:#f8faff;border-radius:12px;padding:14px;margin-top:12px;">
    ' . $safeLink . '
    </p>
    </td>
    </tr>

    <tr>
    <td style="background:#f8faff;padding:18px;text-align:center;color:#6b7897;font-size:13px;">
    Royal Splash Ticketing System
    </td>
    </tr>
    </table>
    </td>
    </tr>
    </table>
    </body>
    </html>';
}

private static function emailText(string $fullName, string $resetLink): string
{
    return "Hi {$fullName},\n\n"
    . "We received a request to reset your Royal Splash customer account password.\n\n"
    . "Reset your password here:\n{$resetLink}\n\n"
    . "This link expires in 60 minutes.\n\n"
    . "If you did not request this reset, you can ignore this email.\n\n"
    . "Royal Splash";
}
}
