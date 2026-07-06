<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

class ReportController extends Controller
{
    public function scanReports(): void
    {
        $this->requireRole('admin');

        $db = Database::connect();

        $events = $this->getEvents($db);
        [$whereSql, $params, $filters] = $this->buildScanFilters();

        $summary = $this->getScanSummary($db, $whereSql, $params);
        $tickets = $this->getScanTickets($db, $whereSql, $params);

        $this->view('admin/reports/scans', [
                'pageTitle' => 'Scan Reports',
                'events' => $events,
                'filters' => $filters,
                'summary' => $summary,
                'tickets' => $tickets,
                ]);
    }

public function exportScanReports(): void
{
    $this->requireRole('admin');

    $db = Database::connect();

    [$whereSql, $params] = $this->buildScanFilters();
    $tickets = $this->getScanTickets($db, $whereSql, $params, 5000);

    $fileName = 'royal-splash-scan-report-' . date('Y-m-d-H-i-s') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');

    $output = fopen('php://output', 'w');

    fputcsv($output, [
            'Ticket Number',
            'Status',
            'Ticket Type',
            'Buyer Name',
            'Buyer Email',
            'Event',
            'Event Date',
            'Order Number',
            'Scanned At',
            'Scanned By',
            ]);

    foreach ($tickets as $ticket) {
        fputcsv($output, [
                $ticket->ticket_number,
                $ticket->status,
                $ticket->ticket_name,
                $ticket->buyer_name,
                $ticket->buyer_email,
                $ticket->event_title,
                $ticket->event_date,
                $ticket->order_number,
                $ticket->scanned_at,
                $ticket->scanned_by_name,
                ]);
    }

fclose($output);
exit;
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

private function buildScanFilters(): array
{
    $eventId = (int) ($_GET['event_id'] ?? 0);
    $status = trim($_GET['status'] ?? 'all');
    $dateFrom = trim($_GET['date_from'] ?? '');
    $dateTo = trim($_GET['date_to'] ?? '');

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

if ($dateFrom !== '') {
    $where[] = 't.scanned_at >= :date_from';
    $params[':date_from'] = $dateFrom . ' 00:00:00';
}

if ($dateTo !== '') {
    $where[] = 't.scanned_at <= :date_to';
    $params[':date_to'] = $dateTo . ' 23:59:59';
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
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        ],
    ];
}

private function getScanSummary(PDO $db, string $whereSql, array $params): object
{
    $stmt = $db->prepare("
        SELECT
        COUNT(t.id) AS total_tickets,
        SUM(CASE WHEN t.status = 'used' THEN 1 ELSE 0 END) AS scanned_tickets,
        SUM(CASE WHEN t.status = 'valid' THEN 1 ELSE 0 END) AS unscanned_tickets,
        SUM(CASE WHEN t.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_tickets,
        SUM(CASE WHEN t.status = 'transferred' THEN 1 ELSE 0 END) AS transferred_tickets
        FROM tickets t
        INNER JOIN events e ON e.id = t.event_id
        INNER JOIN orders o ON o.id = t.order_id
        $whereSql
        ");

        $stmt->execute($params);
        $row = $stmt->fetch();

        $total = (int) ($row->total_tickets ?? 0);
        $scanned = (int) ($row->scanned_tickets ?? 0);

        $scanRate = 0;

        if ($total > 0) {
            $scanRate = round(($scanned / $total) * 100);
        }

    return (object) [
        'total_tickets' => $total,
        'scanned_tickets' => $scanned,
        'unscanned_tickets' => (int) ($row->unscanned_tickets ?? 0),
        'cancelled_tickets' => (int) ($row->cancelled_tickets ?? 0),
        'transferred_tickets' => (int) ($row->transferred_tickets ?? 0),
        'scan_rate' => $scanRate,
        ];
}

private function getScanTickets(PDO $db, string $whereSql, array $params, int $limit = 300): array
{
    $sql = "
    SELECT
    t.ticket_number,
    t.ticket_name,
    t.buyer_name,
    t.buyer_email,
    t.status,
    t.scanned_at,
    e.title AS event_title,
    e.event_date,
    o.order_number,
    scanner.full_name AS scanned_by_name
    FROM tickets t
    INNER JOIN events e ON e.id = t.event_id
    INNER JOIN orders o ON o.id = t.order_id
    LEFT JOIN users scanner ON scanner.id = t.scanned_by
    $whereSql
    ORDER BY
    CASE WHEN t.scanned_at IS NULL THEN 1 ELSE 0 END,
    t.scanned_at DESC,
    t.id DESC
    LIMIT $limit
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}
}
