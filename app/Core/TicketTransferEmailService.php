<?php

namespace App\Core;

use PDO;

class TicketTransferEmailService
{
    public static function sendTransferInvite(PDO $db, int $transferId, string $plainToken): void
    {
        $stmt = $db->prepare("
            SELECT 
                tt.*,
                t.ticket_number,
                t.ticket_name,
                e.title AS event_title,
                e.event_date,
                e.start_time,
                e.end_time,
                e.venue_name,
                e.city,
                sender.full_name AS sender_name
            FROM ticket_transfers tt
            INNER JOIN tickets t ON t.id = tt.ticket_id
            INNER JOIN events e ON e.id = t.event_id
            LEFT JOIN users sender ON sender.id = tt.from_user_id
            WHERE tt.id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $transferId,
        ]);

        $transfer = $stmt->fetch();

        if (!$transfer) {
            throw new \RuntimeException('Ticket transfer record not found.');
        }

        $config = require __DIR__ . '/../../config/config.php';
        $appUrl = rtrim($config['app_url'], '/');

        $acceptLink = $appUrl . '/ticket-transfer/accept?token=' . urlencode($plainToken);

        $mail = MailService::createMailer();

        $mail->addAddress($transfer->recipient_email, $transfer->recipient_name);
        $mail->Subject = 'Royal Splash ticket transfer invitation';

        $mail->isHTML(true);
        $mail->Body = self::htmlBody($transfer, $acceptLink);
        $mail->AltBody = self::textBody($transfer, $acceptLink);

        $mail->send();
    }

    private static function htmlBody(object $transfer, string $acceptLink): string
    {
        $recipientName = htmlspecialchars($transfer->recipient_name, ENT_QUOTES, 'UTF-8');
        $senderName = htmlspecialchars($transfer->sender_name ?: 'A Royal Splash customer', ENT_QUOTES, 'UTF-8');
        $eventTitle = htmlspecialchars($transfer->event_title, ENT_QUOTES, 'UTF-8');
        $ticketName = htmlspecialchars($transfer->ticket_name, ENT_QUOTES, 'UTF-8');
        $ticketNumber = htmlspecialchars($transfer->ticket_number, ENT_QUOTES, 'UTF-8');
        $venue = htmlspecialchars($transfer->venue_name . ', ' . $transfer->city, ENT_QUOTES, 'UTF-8');
        $date = htmlspecialchars(date('D, d M Y', strtotime($transfer->event_date)), ENT_QUOTES, 'UTF-8');
        $time = htmlspecialchars($transfer->start_time . (!empty($transfer->end_time) ? ' - ' . $transfer->end_time : ''), ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($acceptLink, ENT_QUOTES, 'UTF-8');

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Royal Splash Ticket Transfer</title>
        </head>
        <body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#061d43;">
            <table width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:30px 12px;">
                <tr>
                    <td align="center">
                        <table width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #e4e9f2;">
                            <tr>
                                <td style="background:#061d43;padding:30px;text-align:center;">
                                    <h1 style="margin:0;color:#ffffff;font-size:30px;">Royal Splash</h1>
                                    <p style="margin:8px 0 0;color:#d3a22c;font-weight:bold;">Ticket Transfer Invitation</p>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:32px;">
                                    <h2 style="margin:0 0 12px;color:#061d43;">Hi ' . $recipientName . ',</h2>

                                    <p style="font-size:16px;line-height:1.6;color:#52627d;margin:0 0 18px;">
                                        ' . $senderName . ' wants to transfer a Royal Splash ticket to you.
                                    </p>

                                    <table width="100%" cellspacing="0" cellpadding="0" style="background:#f8faff;border-radius:18px;border:1px solid #e5ebf5;margin:22px 0;">
                                        <tr>
                                            <td style="padding:18px;">
                                                <p style="margin:0 0 7px;color:#6b7897;font-size:12px;font-weight:bold;text-transform:uppercase;">Event</p>
                                                <p style="margin:0 0 16px;color:#061d43;font-size:20px;font-weight:bold;">' . $eventTitle . '</p>

                                                <p style="margin:0 0 7px;color:#6b7897;font-size:12px;font-weight:bold;text-transform:uppercase;">Ticket</p>
                                                <p style="margin:0 0 16px;color:#061d43;font-size:16px;font-weight:bold;">' . $ticketName . ' · ' . $ticketNumber . '</p>

                                                <p style="margin:0 0 7px;color:#6b7897;font-size:12px;font-weight:bold;text-transform:uppercase;">Date / Time</p>
                                                <p style="margin:0 0 16px;color:#061d43;font-size:16px;">' . $date . ' · ' . $time . '</p>

                                                <p style="margin:0 0 7px;color:#6b7897;font-size:12px;font-weight:bold;text-transform:uppercase;">Venue</p>
                                                <p style="margin:0;color:#061d43;font-size:16px;">' . $venue . '</p>
                                            </td>
                                        </tr>
                                    </table>

                                    <p style="text-align:center;margin:28px 0;">
                                        <a href="' . $safeLink . '" style="display:inline-block;background:#d3a22c;color:#061d43;text-decoration:none;font-weight:bold;padding:15px 25px;border-radius:999px;">
                                            Accept Ticket Transfer
                                        </a>
                                    </p>

                                    <p style="font-size:14px;line-height:1.6;color:#6b7897;margin:0;">
                                        This transfer link expires in 72 hours. If you do not accept it, the ticket remains with the original owner.
                                    </p>

                                    <p style="font-size:13px;line-height:1.5;color:#061d43;word-break:break-all;background:#f8faff;border-radius:12px;padding:14px;margin-top:14px;">
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

    private static function textBody(object $transfer, string $acceptLink): string
    {
        return "Hi {$transfer->recipient_name},\n\n"
            . "{$transfer->sender_name} wants to transfer a Royal Splash ticket to you.\n\n"
            . "Event: {$transfer->event_title}\n"
            . "Ticket: {$transfer->ticket_name} - {$transfer->ticket_number}\n"
            . "Date: {$transfer->event_date}\n"
            . "Venue: {$transfer->venue_name}, {$transfer->city}\n\n"
            . "Accept the ticket here:\n{$acceptLink}\n\n"
            . "Royal Splash";
    }
}