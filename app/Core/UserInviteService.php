<?php

namespace App\Core;

use PDO;

class UserInviteService
{
    public static function sendPasswordSetupEmail(PDO $db, int $userId): void
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
                throw new \RuntimeException('Active user not found for password setup email.');
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
        $mail->Subject = 'Set your Royal Splash scanner password';

        $mail->isHTML(true);
        $mail->Body = self::emailHtml($user->full_name, $user->role, $setupLink);
        $mail->AltBody = self::emailText($user->full_name, $user->role, $setupLink);

        $mail->send();
    }

private static function emailHtml(string $fullName, string $role, string $setupLink): string
{
    $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
    $safeRole = htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($setupLink, ENT_QUOTES, 'UTF-8');

    return '
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset="UTF-8">
    <title>Royal Splash Password Setup</title>
    </head>
    <body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#0b1f44;">
    <table width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:30px 12px;">
    <tr>
    <td align="center">
    <table width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:22px;overflow:hidden;border:1px solid #e5ebf5;">
    <tr>
    <td style="background:#061d43;padding:28px;text-align:center;">
    <h1 style="margin:0;color:#ffffff;font-size:28px;">Royal Splash</h1>
    <p style="margin:8px 0 0;color:#d3a22c;font-weight:bold;">Scanner Access Setup</p>
    </td>
    </tr>

    <tr>
    <td style="padding:32px;">
    <h2 style="margin:0 0 12px;color:#061d43;">Hi ' . $safeName . ',</h2>

    <p style="font-size:16px;line-height:1.6;color:#52627d;margin:0 0 18px;">
    Your Royal Splash <strong>' . $safeRole . '</strong> account has been created.
    Please set your password to access the scanner system.
    </p>

    <p style="text-align:center;margin:28px 0;">
    <a href="' . $safeLink . '" style="display:inline-block;background:#d3a22c;color:#061d43;text-decoration:none;font-weight:bold;padding:15px 24px;border-radius:999px;">
    Set My Password
    </a>
    </p>

    <p style="font-size:14px;line-height:1.6;color:#6b7897;margin:0;">
    This link expires in 72 hours. If the button does not work, copy and paste this link into your browser:
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

private static function emailText(string $fullName, string $role, string $setupLink): string
{
    return "Hi {$fullName},\n\n"
    . "Your Royal Splash {$role} account has been created.\n\n"
    . "Set your password here:\n{$setupLink}\n\n"
    . "This link expires in 72 hours.\n\n"
    . "Royal Splash";
}
}
