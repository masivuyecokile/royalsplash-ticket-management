<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

class AdminTicketController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin');

        $db = Database::connect();

        $events = $this->getEvents($db);
        [$whereSql, $params, $filters] = $this->buildTicketFilters();

        $summary = $this->getTicketSummary($db, $whereSql, $params);
        $tickets = $this->getTickets($db, $whereSql, $params);

        $this->view('admin/tickets/index', [
                'pageTitle' => 'Tickets',
                'events' => $events,
                'filters' => $filters,
                'summary' => $summary,
                'tickets' => $tickets,
                ]);
    }

public function show(): void
{
    $this->requireRole('admin');

    $ticketId = (int) ($_GET['id'] ?? 0);

    if ($ticketId <= 0) {
        $this->redirect('/admin/tickets');
    }

$db = Database::connect();

$ticket = $this->findTicket($db, $ticketId);

if (!$ticket) {
    $_SESSION['admin_error'] = 'Ticket not found.';
    $this->redirect('/admin/tickets');
}

$logsStmt = $db->prepare("
    SELECT *
    FROM payfast_logs
    WHERE order_id = :order_id
    OR order_number = :order_number
    ORDER BY id DESC
    LIMIT 10
    ");

    $logsStmt->execute([
            ':order_id' => (int) $ticket->order_id,
            ':order_number' => $ticket->order_number,
            ]);

    $logs = $logsStmt->fetchAll();

    $this->view('admin/tickets/show', [
            'pageTitle' => 'View Ticket',
            'ticket' => $ticket,
            'logs' => $logs,
            ]);
}

public function updateStatus(): void
{
    $this->requireRole('admin');

    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');

    if ($ticketId <= 0 || $action === '') {
        $_SESSION['admin_error'] = 'Invalid ticket status request.';
        $this->redirect('/admin/tickets');
    }

$db = Database::connect();
$ticket = $this->findTicket($db, $ticketId);

if (!$ticket) {
    $_SESSION['admin_error'] = 'Ticket not found.';
    $this->redirect('/admin/tickets');
}

$user = $this->authUser();

try {
    if ($action === 'mark_valid') {
        $stmt = $db->prepare("
            UPDATE tickets
            SET
            status = 'valid',
            scanned_at = NULL,
            scanned_by = NULL
            WHERE id = :id
            ");

            $stmt->execute([
                    ':id' => $ticketId,
                    ]);

            $_SESSION['admin_success'] = 'Ticket reactivated and scan status reset.';
    } elseif ($action === 'mark_used') {
    $stmt = $db->prepare("
        UPDATE tickets
        SET
        status = 'used',
        scanned_at = COALESCE(scanned_at, NOW()),
        scanned_by = :scanned_by
        WHERE id = :id
        ");

        $stmt->execute([
                ':scanned_by' => (int) $user->id,
                ':id' => $ticketId,
                ]);

        $_SESSION['admin_success'] = 'Ticket marked as used.';
} elseif ($action === 'cancel') {
$stmt = $db->prepare("
    UPDATE tickets
    SET status = 'cancelled'
    WHERE id = :id
    ");

    $stmt->execute([
            ':id' => $ticketId,
            ]);

    $_SESSION['admin_success'] = 'Ticket cancelled successfully.';
} elseif ($action === 'mark_transferred') {
$stmt = $db->prepare("
    UPDATE tickets
    SET status = 'transferred'
    WHERE id = :id
    ");

    $stmt->execute([
            ':id' => $ticketId,
            ]);

    $_SESSION['admin_success'] = 'Ticket marked as transferred.';
} else {
$_SESSION['admin_error'] = 'Unknown ticket action.';
}
} catch (\Throwable $e) {
$_SESSION['admin_error'] = 'Could not update ticket: ' . $e->getMessage();
}

$this->redirect('/admin/tickets/view?id=' . $ticketId);
}

private function getEvents(PDO $db): array
{
    $stmt = $db->query("
        SELECT id, title, event_date, status
        FROM events
        ORDER BY event_date DESC, id DESC
        ");

        return $stmt->fetchAll();
    }

private function buildTicketFilters(): array
{
    $eventId = (int) ($_GET['event_id'] ?? 0);
    $status = trim($_GET['status'] ?? 'all');
    $search = trim($_GET['q'] ?? '');

    $allowedStatuses = ['all', 'valid', 'used', 'cancelled', 'transferred'];

    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'all';
    }

$where = [];
$params = [];

if ($eventId > 0) {
    $where[] = 't.event_id = :event_id';
    $params[':event_id'] = $eventId;
}

if ($status !== 'all') {
    $where[] = 't.status = :status';
    $params[':status'] = $status;
}

if ($search !== '') {
    $where[] = '(
    t.ticket_number LIKE :search
    OR t.buyer_name LIKE :search
    OR t.buyer_email LIKE :search
    OR t.ticket_name LIKE :search
    OR o.order_number LIKE :search
)';

$params[':search'] = '%' . $search . '%';
}

$whereSql = '';

if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

return [
    $whereSql,
    $params,
    [
        'event_id' => $eventId,
        'status' => $status,
        'q' => $search,
        ],
    ];
}

private function getTicketSummary(PDO $db, string $whereSql, array $params): object
{
    $stmt = $db->prepare("
        SELECT
        COUNT(t.id) AS total_tickets,
        SUM(CASE WHEN t.status = 'valid' THEN 1 ELSE 0 END) AS valid_tickets,
        SUM(CASE WHEN t.status = 'used' THEN 1 ELSE 0 END) AS used_tickets,
        SUM(CASE WHEN t.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_tickets,
        SUM(CASE WHEN t.status = 'transferred' THEN 1 ELSE 0 END) AS transferred_tickets
        FROM tickets t
        INNER JOIN orders o ON o.id = t.order_id
        INNER JOIN events e ON e.id = t.event_id
        $whereSql
        ");

        $stmt->execute($params);
        $row = $stmt->fetch();

        return (object) [
            'total_tickets' => (int) ($row->total_tickets ?? 0),
            'valid_tickets' => (int) ($row->valid_tickets ?? 0),
            'used_tickets' => (int) ($row->used_tickets ?? 0),
            'cancelled_tickets' => (int) ($row->cancelled_tickets ?? 0),
            'transferred_tickets' => (int) ($row->transferred_tickets ?? 0),
            ];
    }

private function getTickets(PDO $db, string $whereSql, array $params): array
{
    $stmt = $db->prepare("
        SELECT
        t.*,
        e.title AS event_title,
        e.event_date,
        o.order_number,
        o.payment_status,
        scanner.full_name AS scanned_by_name
        FROM tickets t
        INNER JOIN events e ON e.id = t.event_id
        INNER JOIN orders o ON o.id = t.order_id
        LEFT JOIN users scanner ON scanner.id = t.scanned_by
        $whereSql
        ORDER BY t.id DESC
        LIMIT 300
        ");

        $stmt->execute($params);

        return $stmt->fetchAll();
    }

private function findTicket(PDO $db, int $ticketId): ?object
{
    $stmt = $db->prepare("
        SELECT
        t.*,
        e.title AS event_title,
        e.slug AS event_slug,
        e.event_date,
        e.start_time,
        e.end_time,
        e.venue_name,
        e.venue_address,
        e.city,
        o.order_number,
        o.payment_status,
        o.total_amount,
        o.total_qty,
        o.buyer_phone,
        o.created_at AS order_created_at,
        o.paid_at,
        oi.quantity AS item_quantity,
        oi.unit_price,
        oi.line_total,
        scanner.full_name AS scanned_by_name,
        account.full_name AS account_name,
        account.email AS account_email
        FROM tickets t
        INNER JOIN events e ON e.id = t.event_id
        INNER JOIN orders o ON o.id = t.order_id
        INNER JOIN order_items oi ON oi.id = t.order_item_id
        LEFT JOIN users scanner ON scanner.id = t.scanned_by
        LEFT JOIN users account ON account.id = t.user_id
        WHERE t.id = :id
        LIMIT 1
        ");

        $stmt->execute([
                ':id' => $ticketId,
                ]);

        $ticket = $stmt->fetch();

        return $ticket ?: null;
    }
}
