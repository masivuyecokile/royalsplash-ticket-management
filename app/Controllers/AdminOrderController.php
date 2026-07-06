<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\TicketEmailService;
use App\Core\AccountSetupService;
use PDO;

class AdminOrderController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin');

        $db = Database::connect();

        $events = $this->getEvents($db);
        [$whereSql, $params, $filters] = $this->buildOrderFilters();

        $summary = $this->getOrderSummary($db, $whereSql, $params);
        $orders = $this->getOrders($db, $whereSql, $params);

        $this->view('admin/orders/index', [
                'pageTitle' => 'Orders',
                'events' => $events,
                'filters' => $filters,
                'summary' => $summary,
                'orders' => $orders,
                ]);
    }

public function show(): void
{
    $this->requireRole('admin');

    $orderId = (int) ($_GET['id'] ?? 0);

    if ($orderId <= 0) {
        $this->redirect('/admin/orders');
    }

$db = Database::connect();

$orderStmt = $db->prepare("
    SELECT
    o.*,
    e.title AS event_title,
    e.slug AS event_slug,
    e.event_date,
    e.start_time,
    e.end_time,
    e.venue_name,
    e.venue_address,
    e.city,
    u.full_name AS account_name,
    u.email AS account_email
    FROM orders o
    INNER JOIN events e ON e.id = o.event_id
    LEFT JOIN users u ON u.id = o.user_id
    WHERE o.id = :id
    LIMIT 1
    ");

    $orderStmt->execute([
            ':id' => $orderId,
            ]);

    $order = $orderStmt->fetch();

    if (!$order) {
        $_SESSION['admin_error'] = 'Order not found.';
        $this->redirect('/admin/orders');
    }

$itemsStmt = $db->prepare("
    SELECT *
    FROM order_items
    WHERE order_id = :order_id
    ORDER BY id ASC
    ");

    $itemsStmt->execute([
            ':order_id' => $orderId,
            ]);

    $items = $itemsStmt->fetchAll();

    $ticketsStmt = $db->prepare("
        SELECT
        t.*,
        scanner.full_name AS scanned_by_name
        FROM tickets t
        LEFT JOIN users scanner ON scanner.id = t.scanned_by
        WHERE t.order_id = :order_id
        ORDER BY t.id ASC
        ");

        $ticketsStmt->execute([
                ':order_id' => $orderId,
                ]);

        $tickets = $ticketsStmt->fetchAll();

        $logsStmt = $db->prepare("
            SELECT *
            FROM payfast_logs
            WHERE order_id = :order_id
            OR order_number = :order_number
            ORDER BY id DESC
            LIMIT 10
            ");

            $logsStmt->execute([
                    ':order_id' => $orderId,
                    ':order_number' => $order->order_number,
                    ]);

            $logs = $logsStmt->fetchAll();

            $this->view('admin/orders/show', [
                    'pageTitle' => 'View Order',
                    'order' => $order,
                    'items' => $items,
                    'tickets' => $tickets,
                    'logs' => $logs,
                    ]);
        }

    public function resendTickets(): void
    {
        $this->requireRole('admin');

        $orderId = (int) ($_POST['order_id'] ?? 0);

        if ($orderId <= 0) {
            $_SESSION['admin_error'] = 'Invalid order.';
            $this->redirect('/admin/orders');
        }

    $db = Database::connect();

    $order = $this->findOrder($db, $orderId);

    if (!$order) {
        $_SESSION['admin_error'] = 'Order not found.';
        $this->redirect('/admin/orders');
    }

if ($order->payment_status !== 'paid') {
    $_SESSION['admin_error'] = 'Tickets can only be resent for paid orders.';
    $this->redirect('/admin/orders/view?id=' . $orderId);
}

try {
    TicketEmailService::sendTicketsForOrder($db, $orderId);
    $_SESSION['admin_success'] = 'Ticket email resent successfully.';
} catch (\Throwable $e) {
$_SESSION['admin_error'] = 'Could not resend ticket email: ' . $e->getMessage();
}

$this->redirect('/admin/orders/view?id=' . $orderId);
}

public function resendPasswordSetup(): void
{
    $this->requireRole('admin');

    $orderId = (int) ($_POST['order_id'] ?? 0);

    if ($orderId <= 0) {
        $_SESSION['admin_error'] = 'Invalid order.';
        $this->redirect('/admin/orders');
    }

$db = Database::connect();

$order = $this->findOrder($db, $orderId);

if (!$order) {
    $_SESSION['admin_error'] = 'Order not found.';
    $this->redirect('/admin/orders');
}

if (empty($order->user_id)) {
    $_SESSION['admin_error'] = 'This order is not linked to a customer account.';
    $this->redirect('/admin/orders/view?id=' . $orderId);
}

try {
    AccountSetupService::sendSetPasswordEmail($db, (int) $order->user_id);
    $_SESSION['admin_success'] = 'Password setup email sent successfully.';
} catch (\Throwable $e) {
$_SESSION['admin_error'] = 'Could not send password setup email: ' . $e->getMessage();
}

$this->redirect('/admin/orders/view?id=' . $orderId);
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

private function buildOrderFilters(): array
{
    $eventId = (int) ($_GET['event_id'] ?? 0);
    $status = trim($_GET['status'] ?? 'all');
    $search = trim($_GET['q'] ?? '');

    $allowedStatuses = ['all', 'pending', 'paid', 'failed', 'cancelled'];

    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'all';
    }

$where = [];
$params = [];

if ($eventId > 0) {
    $where[] = 'o.event_id = :event_id';
    $params[':event_id'] = $eventId;
}

if ($status !== 'all') {
    $where[] = 'o.payment_status = :status';
    $params[':status'] = $status;
}

if ($search !== '') {
    $where[] = '(
    o.order_number LIKE :search
    OR o.buyer_name LIKE :search
    OR o.buyer_email LIKE :search
    OR o.buyer_phone LIKE :search
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

private function getOrderSummary(PDO $db, string $whereSql, array $params): object
{
    $stmt = $db->prepare("
        SELECT
        COUNT(o.id) AS total_orders,
        SUM(CASE WHEN o.payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_orders,
        SUM(CASE WHEN o.payment_status = 'pending' THEN 1 ELSE 0 END) AS pending_orders,
        SUM(CASE WHEN o.payment_status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_orders,
        SUM(CASE WHEN o.payment_status = 'paid' THEN o.total_amount ELSE 0 END) AS paid_revenue
        FROM orders o
        INNER JOIN events e ON e.id = o.event_id
        $whereSql
        ");

        $stmt->execute($params);
        $row = $stmt->fetch();

        return (object) [
            'total_orders' => (int) ($row->total_orders ?? 0),
            'paid_orders' => (int) ($row->paid_orders ?? 0),
            'pending_orders' => (int) ($row->pending_orders ?? 0),
            'cancelled_orders' => (int) ($row->cancelled_orders ?? 0),
            'paid_revenue' => (float) ($row->paid_revenue ?? 0),
            ];
    }

private function getOrders(PDO $db, string $whereSql, array $params): array
{
    $stmt = $db->prepare("
        SELECT
        o.*,
        e.title AS event_title,
        e.event_date,
        (
            SELECT COUNT(*)
            FROM tickets t
            WHERE t.order_id = o.id
        ) AS ticket_count
    FROM orders o
    INNER JOIN events e ON e.id = o.event_id
    $whereSql
    ORDER BY o.id DESC
    LIMIT 300
    ");

    $stmt->execute($params);

    return $stmt->fetchAll();
}

private function findOrder(PDO $db, int $orderId): ?object
{
    $stmt = $db->prepare("
        SELECT *
        FROM orders
        WHERE id = :id
        LIMIT 1
        ");

        $stmt->execute([
                ':id' => $orderId,
                ]);

        $order = $stmt->fetch();

        return $order ?: null;
    }
}
