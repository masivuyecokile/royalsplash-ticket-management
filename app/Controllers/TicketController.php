<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\QrCodeService;
use App\Core\TicketPdfService;

class TicketController extends Controller
{
    public function index(): void
{
    $this->requireRole('customer');

    $user = $this->authUser();
    $db = Database::connect();

    $stmt = $db->prepare("
        SELECT 
            t.*,
            e.title AS event_title,
            e.event_date,
            e.start_time,
            e.venue_name,
            e.city,

            tt.id AS pending_transfer_id,
            tt.recipient_name AS pending_transfer_name,
            tt.recipient_email AS pending_transfer_email,
            tt.recipient_phone AS pending_transfer_phone,
            tt.expires_at AS pending_transfer_expires_at

        FROM tickets t
        INNER JOIN events e ON e.id = t.event_id

        LEFT JOIN ticket_transfers tt 
            ON tt.ticket_id = t.id
            AND tt.from_user_id = :user_id
            AND tt.status = 'pending'
            AND tt.expires_at > NOW()

        WHERE t.user_id = :user_id
        ORDER BY e.event_date ASC, t.id DESC
    ");

    $stmt->execute([
        ':user_id' => (int) $user->id,
    ]);

    $tickets = $stmt->fetchAll();

    $this->view('tickets/index', [
        'pageTitle' => 'My Tickets',
        'tickets' => $tickets,
    ]);
}
    public function show(): void
    {
        $token = trim($_GET['token'] ?? '');

        if ($token === '') {
            $this->redirect('/my-tickets');
        }

    $db = Database::connect();

    $ticket = $this->findTicketByToken($db, $token);

    if (!$ticket) {
        http_response_code(404);
        $this->view('errors/404', [
                'pageTitle' => 'Ticket Not Found',
                ]);
        return;
    }

$config = require __DIR__ . '/../../config/config.php';
$appUrl = rtrim($config['app_url'], '/');

$ticketUrl = $appUrl . '/ticket?token=' . urlencode($ticket->qr_token);
$downloadUrl = $appUrl . '/ticket/download?token=' . urlencode($ticket->qr_token);
$qrDataUri = QrCodeService::ticketDataUri($ticketUrl);

$this->view('tickets/show', [
        'pageTitle' => 'Ticket ' . $ticket->ticket_number,
        'ticket' => $ticket,
        'ticketUrl' => $ticketUrl,
        'downloadUrl' => $downloadUrl,
        'qrDataUri' => $qrDataUri,
        ]);
}

public function download(): void
{
    $token = trim($_GET['token'] ?? '');

    if ($token === '') {
        http_response_code(404);
        echo 'Ticket not found.';
        exit;
    }

$db = Database::connect();

$ticket = $this->findTicketByToken($db, $token);

if (!$ticket) {
    http_response_code(404);
    echo 'Ticket not found.';
    exit;
}

$projectRoot = dirname(__DIR__, 2);

if (empty($ticket->pdf_path) || !is_file($projectRoot . '/' . $ticket->pdf_path)) {
    TicketPdfService::generateForTicketId($db, (int) $ticket->id);
    $ticket = $this->findTicketByToken($db, $token);
}

$fullPath = $projectRoot . '/' . $ticket->pdf_path;

if (!is_file($fullPath)) {
    http_response_code(500);
    echo 'Ticket PDF could not be generated.';
    exit;
}

$safeFileName = preg_replace('/[^A-Za-z0-9\-]/', '-', $ticket->ticket_number) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $safeFileName . '"');
header('Content-Length: ' . filesize($fullPath));

readfile($fullPath);
exit;
}

private function findTicketByToken(object $db, string $token): ?object
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
        WHERE t.qr_token = :qr_token
        LIMIT 1
        ");

        $stmt->execute([
                ':qr_token' => $token,
                ]);

        $ticket = $stmt->fetch();

        return $ticket ?: null;
    }
}
