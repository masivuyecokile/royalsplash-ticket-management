<?php

namespace App\Core;

use PDO;

class AccountSetupService
{
    public static function sendSetPasswordEmail(PDO $db, int $userId): void
    {
        $userStmt = $db->prepare("
            SELECT *
            FROM users
            WHERE id = :id
            AND status = 'active'
            LIMIT 1
            ");

            $userStmt->execute([
                    ':id' => $userId,
                    ]);

            $user = $userStmt->fetch();

            if (!$user) {
                throw new \RuntimeException('User not found for password setup email.');
            }

        $config = require __DIR__ . '/../../config/config.php';
        $appUrl = rtrim($config['app_url'], '/');

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        $expireStmt = $db->prepare("
            UPDATE password_setup_tokens
            SET used_at = NOW()
            WHERE user_id = :user_id
            AND purpose = 'set_password'
            AND used_at IS NULL
            ");

            $expireStmt->execute([
                    ':user_id' => $userId,
                    ]);

            $insertStmt = $db->prepare("
                INSERT INTO password_setup_tokens (
                    user_id,
                    token_hash,
                    purpose,
                    expires_at
                ) VALUES (
                :user_id,
                :token_hash,
                'set_password',
                DATE_ADD(NOW(), INTERVAL 72 HOUR)
            )
        ");

        $insertStmt->execute([
                ':user_id' => $userId,
                ':token_hash' => $tokenHash,
                ]);

        $setupLink = $appUrl . '/set-password?token=' . urlencode($plainToken);

        $mail = MailService::createMailer();

        $mail->addAddress($user->email, $user->full_name);
        $mail->Subject = 'Set your Royal Splash password';

        $mail->isHTML(true);
        $mail->Body = self::emailHtml($user->full_name, $setupLink);
        $mail->AltBody = self::emailText($user->full_name, $setupLink);

        $mail->send();
    }

private static function emailHtml(string $fullName, string $setupLink): string
{
    $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($setupLink, ENT_QUOTES, 'UTF-8');

    return '
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset="UTF-8">
    <title>Set Your Password</title>
    </head>
    <body style="margin:0;padding:0;background:#f5f8ff;font-family:Arial,sans-serif;color:#002660;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f8ff;padding:30px 15px;">
    <tr>
    <td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #dbe5f5;">
    <tr>
    <td style="background:#002660;padding:30px;text-align:center;color:#ffffff;">
    <h1 style="margin:0;font-size:30px;letter-spacing:-0.5px;">Royal Splash</h1>
    <p style="margin:10px 0 0;color:#f3c542;font-weight:bold;">Your account is ready</p>
    </td>
    </tr>

    <tr>
    <td style="padding:34px;">
    <h2 style="margin:0 0 14px;font-size:24px;color:#002660;">Hi ' . $safeName . ',</h2>

    <p style="font-size:16px;line-height:1.6;color:#42557d;margin:0 0 18px;">
    Your Royal Splash ticket account has been created successfully.
    Please set your password so you can login later and access your tickets.
    </p>

    <p style="font-size:16px;line-height:1.6;color:#42557d;margin:0 0 24px;">
    This secure link will expire in <strong>72 hours</strong>.
    </p>

    <div style="text-align:center;margin:30px 0;">
    <a href="' . $safeLink . '" style="display:inline-block;background:#d9a520;color:#002660;text-decoration:none;font-weight:bold;padding:15px 28px;border-radius:999px;">
    Set My Password
    </a>
    </div>

    <p style="font-size:13px;line-height:1.6;color:#6b7897;margin:24px 0 0;">
    If the button does not work, copy and paste this link into your browser:<br>
    <a href="' . $safeLink . '" style="color:#002660;word-break:break-all;">' . $safeLink . '</a>
    </p>
    </td>
    </tr>

    <tr>
    <td style="background:#f8faff;padding:18px 30px;text-align:center;color:#6b7897;font-size:13px;">
    Royal Splash Digital Ticketing
    </td>
    </tr>
    </table>
    </td>
    </tr>
    </table>
    </body>
    </html>';
}

private static function emailText(string $fullName, string $setupLink): string
{
    return "Hi {$fullName},\n\n"
    . "Your Royal Splash ticket account has been created successfully.\n\n"
    . "Set your password here:\n{$setupLink}\n\n"
    . "This link expires in 72 hours.\n\n"
    . "Royal Splash";
}
}
