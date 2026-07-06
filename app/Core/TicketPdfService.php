<?php

namespace App\Core;

use Dompdf\Dompdf;
use Dompdf\Options;
use PDO;

class TicketPdfService
{
    public static function generateForTicketId(PDO $db, int $ticketId): string
    {
        $stmt = $db->prepare("
            SELECT
            t.*,
            e.title AS event_title,
            e.event_date,
            e.start_time,
            e.venue_name,
            e.venue_address,
            e.city,
            o.order_number
            FROM tickets t
            INNER JOIN events e ON e.id = t.event_id
            INNER JOIN orders o ON o.id = t.order_id
            WHERE t.id = :id
            LIMIT 1
            ");

            $stmt->execute([
                    ':id' => $ticketId,
                    ]);

            $ticket = $stmt->fetch();

            if (!$ticket) {
                throw new \RuntimeException('Ticket not found for PDF generation.');
            }

        $config = require __DIR__ . '/../../config/config.php';
        $appUrl = rtrim($config['app_url'], '/');

        $ticketUrl = $appUrl . '/ticket?token=' . urlencode($ticket->qr_token);

        $qrDataUri = QrCodeService::ticketDataUri($ticketUrl);

        $projectRoot = dirname(__DIR__, 2);
        $storageDir = $projectRoot . '/storage/tickets';

        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

    $safeTicketNumber = preg_replace('/[^A-Za-z0-9\-]/', '-', $ticket->ticket_number);
    $fileName = $safeTicketNumber . '.pdf';
    $fullPath = $storageDir . '/' . $fileName;
    $relativePath = 'storage/tickets/' . $fileName;

    $logoDataUri = self::logoDataUri($projectRoot);

    $html = self::buildHtml($ticket, $ticketUrl, $qrDataUri, $logoDataUri);

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    file_put_contents($fullPath, $dompdf->output());

    $update = $db->prepare("
        UPDATE tickets
        SET
        pdf_path = :pdf_path,
        pdf_generated_at = NOW()
        WHERE id = :id
        ");

        $update->execute([
                ':pdf_path' => $relativePath,
                ':id' => $ticket->id,
                ]);

        return $relativePath;
    }

private static function logoDataUri(string $projectRoot): ?string
{
    $possiblePaths = [
        $projectRoot . '/assets/images/royal-splash-logo.png',
        $projectRoot . '/assets/images/logo.png',
        ];

    foreach ($possiblePaths as $path) {
        if (is_file($path)) {
            $mime = mime_content_type($path) ?: 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        }
}

return null;
}

private static function buildHtml(
    object $ticket,
    string $ticketUrl,
    string $qrDataUri,
    ?string $logoDataUri
): string {
$eventDate = date('d M Y', strtotime($ticket->event_date));
$startTime = $ticket->start_time ? htmlspecialchars($ticket->start_time) : '';
$venue = htmlspecialchars($ticket->venue_name . ', ' . $ticket->city);

$logoHtml = $logoDataUri
? '<img src="' . $logoDataUri . '" class="logo" alt="Royal Splash">'
: '<div class="text-logo">Royal Splash</div>';

return '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Ticket ' . htmlspecialchars($ticket->ticket_number) . '</title>

<style>
@page {
    margin: 18px;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 0;
    font-family: DejaVu Sans, Arial, sans-serif;
    background: #ffffff;
    color: #10294f;
}

.ticket {
    border: 2px solid #10294f;
    border-radius: 18px;
    padding: 20px 22px;
    min-height: 100%;
}

.top {
    text-align: center;
    border-bottom: 1px solid #d9e1ef;
    padding-bottom: 12px;
    margin-bottom: 14px;
}

.logo {
    max-width: 110px;
    max-height: 65px;
    margin-bottom: 6px;
}

.text-logo {
    font-size: 24px;
    font-weight: bold;
    color: #10294f;
    margin-bottom: 6px;
}

.badge {
    display: inline-block;
    background: #e7c15f;
    color: #10294f;
    font-size: 10px;
    font-weight: bold;
    padding: 5px 10px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

h1 {
    margin: 10px 0 5px;
    font-size: 24px;
    line-height: 1.15;
    color: #10294f;
}

.muted {
    color: #60708f;
    font-size: 12px;
    line-height: 1.35;
    margin: 4px 0;
}

.info-grid {
    width: 100%;
    margin: 14px 0;
    border-collapse: collapse;
}

.info-grid td {
    width: 50%;
    padding: 9px 10px;
    border: 1px solid #e1e7f2;
    vertical-align: top;
}

.label {
    display: block;
    color: #6b7897;
    font-size: 9px;
    font-weight: bold;
    text-transform: uppercase;
    margin-bottom: 3px;
    letter-spacing: 0.04em;
}

.value {
    font-size: 12px;
    font-weight: bold;
    color: #10294f;
    line-height: 1.25;
}

.qr-wrap {
    text-align: center;
    margin: 14px auto;
    padding: 12px;
    border: 1px solid #d9e1ef;
    border-radius: 16px;
    background: #f8fbff;
}

.qr-wrap img {
    width: 210px;
    height: 210px;
}

.ticket-number {
    font-size: 13px;
    font-weight: bold;
    margin-top: 6px;
    color: #10294f;
}

.instructions {
    background: #fff8df;
    border: 1px solid #ead58b;
    padding: 11px 13px;
    border-radius: 12px;
    margin-top: 12px;
    color: #4e421d;
    font-size: 11px;
    line-height: 1.35;
}

.footer {
    text-align: center;
    margin-top: 10px;
    color: #7a86a0;
    font-size: 9px;
}
</style>
</head>

<body>
<div class="ticket">
<div class="top">
' . $logoHtml . '
<br>
<span class="badge">Valid Ticket</span>

<h1>' . htmlspecialchars($ticket->event_title) . '</h1>

<p class="muted">
' . $eventDate . ($startTime ? ' · ' . $startTime : '') . '<br>
' . $venue . '
</p>
</div>

<table class="info-grid">
<tr>
<td>
<span class="label">Ticket Type</span>
<span class="value">' . htmlspecialchars($ticket->ticket_name) . '</span>
</td>

<td>
<span class="label">Order Number</span>
<span class="value">' . htmlspecialchars($ticket->order_number) . '</span>
</td>
</tr>

<tr>
<td>
<span class="label">Buyer Name</span>
<span class="value">' . htmlspecialchars($ticket->buyer_name) . '</span>
</td>

<td>
<span class="label">Ticket Number</span>
<span class="value">' . htmlspecialchars($ticket->ticket_number) . '</span>
</td>
</tr>
</table>

<div class="qr-wrap">
<img src="' . $qrDataUri . '" alt="QR Code">

<div class="ticket-number">
' . htmlspecialchars($ticket->ticket_number) . '
</div>

<p class="muted">
Scan this QR code at the gate.
</p>
</div>

<div class="instructions">
<strong>Important:</strong>
Keep this ticket safe. Each QR code can only be scanned once.
If you share this PDF with someone else, do not also use the same ticket at the gate.
</div>

<div class="footer">
Royal Splash Ticketing System
</div>
</div>
</body>
</html>';
}
}
