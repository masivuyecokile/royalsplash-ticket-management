<?php

namespace App\Core;

use PDO;

class TicketEmailService
{
    public static function sendTicketsForOrder(PDO $db, int $orderId): bool
    {
        $orderStmt = $db->prepare("
            SELECT
            o.*,
            e.title AS event_title,
            e.event_date,
            e.start_time,
            e.venue_name,
            e.city
            FROM orders o
            INNER JOIN events e ON e.id = o.event_id
            WHERE o.id = :id
            LIMIT 1
            ");

            $orderStmt->execute([
                    ':id' => $orderId,
                    ]);

            $order = $orderStmt->fetch();

            if (!$order) {
                throw new \RuntimeException('Order not found for ticket email.');
            }

        if ($order->payment_status !== 'paid') {
            throw new \RuntimeException('Cannot email tickets for unpaid order.');
        }

    if (!empty($order->tickets_emailed_at)) {
        return true;
    }

$ticketStmt = $db->prepare("
    SELECT
    t.*,
    e.title AS event_title,
    e.event_date,
    e.start_time,
    e.venue_name,
    e.city
    FROM tickets t
    INNER JOIN events e ON e.id = t.event_id
    WHERE t.order_id = :order_id
    ORDER BY t.id ASC
    ");

    $ticketStmt->execute([
            ':order_id' => $orderId,
            ]);

    $tickets = $ticketStmt->fetchAll();

    if (empty($tickets)) {
        throw new \RuntimeException('No tickets found for this order.');
    }

$projectRoot = dirname(__DIR__, 2);
$attachments = [];

foreach ($tickets as $ticket) {
    if (empty($ticket->pdf_path) || !is_file($projectRoot . '/' . $ticket->pdf_path)) {
        TicketPdfService::generateForTicketId($db, (int) $ticket->id);

        $refreshStmt = $db->prepare("
            SELECT *
            FROM tickets
            WHERE id = :id
            LIMIT 1
            ");

            $refreshStmt->execute([
                    ':id' => (int) $ticket->id,
                    ]);

            $ticket = $refreshStmt->fetch();
        }

    $fullPath = $projectRoot . '/' . $ticket->pdf_path;

    if (!is_file($fullPath)) {
        throw new \RuntimeException('Ticket PDF missing: ' . $ticket->ticket_number);
    }

$safeTicketNumber = preg_replace('/[^A-Za-z0-9\-]/', '-', $ticket->ticket_number);

$attachments[] = [
    'path' => $fullPath,
    'name' => 'Royal-Splash-' . $safeTicketNumber . '.pdf',
    ];
}

$config = require __DIR__ . '/../../config/config.php';
$appUrl = rtrim($config['app_url'], '/');
$myTicketsUrl = $appUrl . '/my-tickets';

$eventDate = date('d M Y', strtotime($order->event_date));

$mail = MailService::createMailer();

$mail->addAddress($order->buyer_email, $order->buyer_name);
$mail->isHTML(true);

$mail->Subject = 'Your Royal Splash Tickets - Order ' . $order->order_number;

$mail->Body = self::buildHtmlEmail($order, $eventDate, $myTicketsUrl, count($tickets));

$mail->AltBody =
"Hi {$order->buyer_name},\n\n" .
"Thank you for your payment. Your Royal Splash ticket PDFs are attached.\n\n" .
"Order: {$order->order_number}\n" .
"Event: {$order->event_title}\n" .
"Date: {$eventDate}\n" .
"Venue: {$order->venue_name}, {$order->city}\n\n" .
"You can also view your tickets online here:\n" .
"{$myTicketsUrl}\n\n" .
"Each QR code can only be scanned once at the gate.\n\n" .
"Royal Splash Team";

foreach ($attachments as $attachment) {
    $mail->addAttachment($attachment['path'], $attachment['name']);
}

try {
    $mail->send();

    $update = $db->prepare("
        UPDATE orders
        SET
        tickets_emailed_at = NOW(),
        tickets_email_error = NULL
        WHERE id = :id
        ");

        $update->execute([
                ':id' => $orderId,
                ]);

        return true;
} catch (\Throwable $e) {
$update = $db->prepare("
    UPDATE orders
    SET tickets_email_error = :error
    WHERE id = :id
    ");

    $update->execute([
            ':error' => $e->getMessage(),
            ':id' => $orderId,
            ]);

    throw $e;
}
}

private static function buildHtmlEmail(object $order, string $eventDate, string $myTicketsUrl, int $ticketCount): string
{
    $ticketWord = $ticketCount === 1 ? 'ticket' : 'tickets';

    return '
    <div style="font-family: Arial, sans-serif; background:#f5f8ff; padding:24px;">
    <div style="max-width:640px; margin:0 auto; background:#ffffff; border-radius:18px; padding:28px; border:1px solid #d9e1ef;">
    <h1 style="margin:0 0 12px; color:#10294f;">Your Royal Splash Tickets</h1>

    <p style="color:#50617f; font-size:15px; line-height:1.6;">
    Hi ' . htmlspecialchars($order->buyer_name) . ',
    </p>

    <p style="color:#50617f; font-size:15px; line-height:1.6;">
    Thank you for your payment. Your ' . $ticketCount . ' Royal Splash ' . $ticketWord . ' are attached as separate PDF files.
    </p>

    <div style="background:#f5f8ff; border:1px solid #d9e1ef; border-radius:14px; padding:16px; margin:20px 0;">
    <p style="margin:0 0 8px;"><strong>Order:</strong> ' . htmlspecialchars($order->order_number) . '</p>
    <p style="margin:0 0 8px;"><strong>Event:</strong> ' . htmlspecialchars($order->event_title) . '</p>
    <p style="margin:0 0 8px;"><strong>Date:</strong> ' . htmlspecialchars($eventDate) . '</p>
    <p style="margin:0;"><strong>Venue:</strong> ' . htmlspecialchars($order->venue_name . ', ' . $order->city) . '</p>
    </div>

    <p style="color:#50617f; font-size:15px; line-height:1.6;">
    You can also view your tickets online from your Royal Splash account.
    </p>

    <p>
    <a href="' . htmlspecialchars($myTicketsUrl) . '" style="display:inline-block; background:#10294f; color:#ffffff; text-decoration:none; padding:13px 20px; border-radius:999px; font-weight:bold;">
    View My Tickets
    </a>
    </p>

    <p style="color:#8b6500; background:#fff8df; border:1px solid #ead58b; padding:14px; border-radius:14px; font-size:14px; line-height:1.6;">
    Please keep your QR codes safe. Each QR code can only be scanned once at the gate.
    </p>

    <p style="color:#7a86a0; font-size:13px; margin-top:24px;">
    Royal Splash Team
    </p>
    </div>
    </div>
    ';
}
}
