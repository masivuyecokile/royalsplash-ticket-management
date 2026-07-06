<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class ScannerController extends Controller
{
    public function index(): void
    {
        $this->requireAnyRole(['admin', 'scanner']);

        $this->view('scanner/index', [
                'pageTitle' => 'Ticket Scanner',
                'user' => $this->authUser(),
                ]);
    }

public function validate(): void
{
    header('Content-Type: application/json');

    $user = $this->authUser();

    if (!$user || !in_array(($user->role ?? ''), ['admin', 'scanner'], true)) {
        http_response_code(403);

        echo json_encode([
                'status' => 'error',
                'message' => 'You are not allowed to scan tickets.',
                ]);
        exit;
    }

$rawCode = trim($_POST['code'] ?? '');
$token = $this->extractToken($rawCode);

if ($token === '') {
    http_response_code(422);

    echo json_encode([
            'status' => 'error',
            'message' => 'Invalid QR code. No ticket token found.',
            ]);
    exit;
}

$db = Database::connect();

$stmt = $db->prepare("
    SELECT
    t.*,
    e.title AS event_title,
    e.event_date,
    e.start_time,
    e.venue_name,
    e.venue_address,
    e.city,
    o.order_number,
    scanner.full_name AS scanned_by_name
    FROM tickets t
    INNER JOIN events e ON e.id = t.event_id
    INNER JOIN orders o ON o.id = t.order_id
    LEFT JOIN users scanner ON scanner.id = t.scanned_by
    WHERE t.qr_token = :qr_token
    LIMIT 1
    ");

    $stmt->execute([
            ':qr_token' => $token,
            ]);

    $ticket = $stmt->fetch();

    if (!$ticket) {
        echo json_encode([
                'status' => 'error',
                'result_type' => 'not_found',
                'message' => 'Ticket not found. This QR code is not recognised.',
                ]);
        exit;
    }

if ($ticket->status === 'used') {
    echo json_encode([
            'status' => 'warning',
            'result_type' => 'used',
            'message' => 'Ticket already used.',
            'ticket' => $this->ticketPayload($ticket),
            ]);
    exit;
}

if ($ticket->status !== 'valid') {
    echo json_encode([
            'status' => 'error',
            'result_type' => $ticket->status,
            'message' => 'Ticket cannot be accepted. Current status: ' . ucfirst($ticket->status),
            'ticket' => $this->ticketPayload($ticket),
            ]);
    exit;
}

$update = $db->prepare("
    UPDATE tickets
    SET
    status = 'used',
    scanned_at = NOW(),
    scanned_by = :scanned_by
    WHERE id = :id
    AND status = 'valid'
    ");

    $update->execute([
            ':scanned_by' => (int) $user->id,
            ':id' => (int) $ticket->id,
            ]);

    $refreshStmt = $db->prepare("
        SELECT
        t.*,
        e.title AS event_title,
        e.event_date,
        e.start_time,
        e.venue_name,
        e.venue_address,
        e.city,
        o.order_number,
        scanner.full_name AS scanned_by_name
        FROM tickets t
        INNER JOIN events e ON e.id = t.event_id
        INNER JOIN orders o ON o.id = t.order_id
        LEFT JOIN users scanner ON scanner.id = t.scanned_by
        WHERE t.id = :id
        LIMIT 1
        ");

        $refreshStmt->execute([
                ':id' => (int) $ticket->id,
                ]);

        $ticket = $refreshStmt->fetch();

        echo json_encode([
                'status' => 'success',
                'result_type' => 'accepted',
                'message' => 'Ticket accepted. Entry allowed.',
                'ticket' => $this->ticketPayload($ticket),
                ]);
        exit;
    }

private function extractToken(string $rawCode): string
{
    if ($rawCode === '') {
        return '';
    }

if (str_contains($rawCode, 'token=')) {
    $parts = parse_url($rawCode);

    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);

        if (!empty($query['token'])) {
            return trim((string) $query['token']);
        }
}
}

if (preg_match('/^[a-f0-9]{64}$/i', $rawCode)) {
    return $rawCode;
}

return '';
}

private function ticketPayload(object $ticket): array
{
    return [
        'ticket_number' => $ticket->ticket_number,
        'ticket_name' => $ticket->ticket_name,
        'status' => $ticket->status,
        'buyer_name' => $ticket->buyer_name,
        'buyer_email' => $ticket->buyer_email,
        'event_title' => $ticket->event_title,
        'event_date' => date('d M Y', strtotime($ticket->event_date)),
        'start_time' => $ticket->start_time,
        'venue_name' => $ticket->venue_name,
        'city' => $ticket->city,
        'order_number' => $ticket->order_number,
        'scanned_at' => $ticket->scanned_at,
        'scanned_by_name' => $ticket->scanned_by_name,
        ];
}
}
