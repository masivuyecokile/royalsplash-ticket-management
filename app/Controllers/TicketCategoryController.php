<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class TicketCategoryController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin');

        $db = Database::connect();

        $eventsStmt = $db->query("
            SELECT id, title, event_date, status
            FROM events
            ORDER BY event_date DESC, id DESC
            ");

            $events = $eventsStmt->fetchAll();

            $categoriesStmt = $db->query("
                SELECT
                tc.*,
                e.title AS event_title,
                e.event_date,
                e.status AS event_status
                FROM ticket_categories tc
                INNER JOIN events e ON e.id = tc.event_id
                ORDER BY e.event_date DESC, tc.id DESC
                ");

                $categories = $categoriesStmt->fetchAll();

                $this->view('admin/ticket-categories/index', [
                        'pageTitle' => 'Ticket Categories',
                        'events' => $events,
                        'categories' => $categories,
                        ]);
            }

        public function store(): void
        {
            $this->requireRole('admin');

            $eventId = (int) ($_POST['event_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = (float) ($_POST['price'] ?? 0);
            $quantityAvailable = (int) ($_POST['quantity_available'] ?? 0);
            $maxPerOrder = (int) ($_POST['max_per_order'] ?? 4);
            $status = $_POST['status'] ?? 'active';
            $saleStart = $this->cleanDateTime($_POST['sale_start'] ?? '');
            $saleEnd = $this->cleanDateTime($_POST['sale_end'] ?? '');

            if ($eventId <= 0 || $name === '') {
                $_SESSION['admin_error'] = 'Please select an event and enter a category name.';
                $this->redirect('/admin/ticket-categories');
            }

        if ($price < 0) {
            $_SESSION['admin_error'] = 'Ticket price cannot be less than zero.';
            $this->redirect('/admin/ticket-categories');
        }

    if ($quantityAvailable < 0) {
        $_SESSION['admin_error'] = 'Quantity cannot be less than zero.';
        $this->redirect('/admin/ticket-categories');
    }

if ($maxPerOrder <= 0) {
    $maxPerOrder = 1;
}

if (!in_array($status, ['active', 'inactive', 'sold_out'], true)) {
    $status = 'active';
}

$db = Database::connect();

$eventStmt = $db->prepare("
    SELECT id
    FROM events
    WHERE id = :id
    LIMIT 1
    ");

    $eventStmt->execute([
            ':id' => $eventId,
            ]);

    if (!$eventStmt->fetch()) {
        $_SESSION['admin_error'] = 'Selected event was not found.';
        $this->redirect('/admin/ticket-categories');
    }

$stmt = $db->prepare("
    INSERT INTO ticket_categories (
        event_id,
        name,
        description,
        price,
        quantity_available,
        quantity_sold,
        max_per_order,
        status,
        sale_start,
        sale_end
    ) VALUES (
    :event_id,
    :name,
    :description,
    :price,
    :quantity_available,
    0,
    :max_per_order,
    :status,
    :sale_start,
    :sale_end
)
");

$stmt->execute([
        ':event_id' => $eventId,
        ':name' => $name,
        ':description' => $description !== '' ? $description : null,
        ':price' => number_format($price, 2, '.', ''),
        ':quantity_available' => $quantityAvailable,
        ':max_per_order' => $maxPerOrder,
        ':status' => $status,
        ':sale_start' => $saleStart,
        ':sale_end' => $saleEnd,
        ]);

$_SESSION['admin_success'] = 'Ticket category created successfully.';
$this->redirect('/admin/ticket-categories');
}

public function edit(): void
{
    $this->requireRole('admin');

    $id = (int) ($_GET['id'] ?? 0);

    if ($id <= 0) {
        $this->redirect('/admin/ticket-categories');
    }

$db = Database::connect();

$categoryStmt = $db->prepare("
    SELECT
    tc.*,
    e.title AS event_title,
    e.event_date,
    e.status AS event_status
    FROM ticket_categories tc
    INNER JOIN events e ON e.id = tc.event_id
    WHERE tc.id = :id
    LIMIT 1
    ");

    $categoryStmt->execute([
            ':id' => $id,
            ]);

    $category = $categoryStmt->fetch();

    if (!$category) {
        $_SESSION['admin_error'] = 'Ticket category was not found.';
        $this->redirect('/admin/ticket-categories');
    }

$eventsStmt = $db->query("
    SELECT id, title, event_date, status
    FROM events
    ORDER BY event_date DESC, id DESC
    ");

    $events = $eventsStmt->fetchAll();

    $this->view('admin/ticket-categories/edit', [
            'pageTitle' => 'Edit Ticket Category',
            'category' => $category,
            'events' => $events,
            ]);
}

public function update(): void
{
    $this->requireRole('admin');

    $id = (int) ($_POST['id'] ?? 0);
    $eventId = (int) ($_POST['event_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $quantityAvailable = (int) ($_POST['quantity_available'] ?? 0);
    $maxPerOrder = (int) ($_POST['max_per_order'] ?? 4);
    $status = $_POST['status'] ?? 'active';
    $saleStart = $this->cleanDateTime($_POST['sale_start'] ?? '');
    $saleEnd = $this->cleanDateTime($_POST['sale_end'] ?? '');

    if ($id <= 0 || $eventId <= 0 || $name === '') {
        $_SESSION['admin_error'] = 'Please complete all required fields.';
        $this->redirect('/admin/ticket-categories');
    }

if ($price < 0) {
    $_SESSION['admin_error'] = 'Ticket price cannot be less than zero.';
    $this->redirect('/admin/ticket-categories/edit?id=' . $id);
}

if ($quantityAvailable < 0) {
    $_SESSION['admin_error'] = 'Quantity cannot be less than zero.';
    $this->redirect('/admin/ticket-categories/edit?id=' . $id);
}

if ($maxPerOrder <= 0) {
    $maxPerOrder = 1;
}

if (!in_array($status, ['active', 'inactive', 'sold_out'], true)) {
    $status = 'active';
}

$db = Database::connect();

$currentStmt = $db->prepare("
    SELECT *
    FROM ticket_categories
    WHERE id = :id
    LIMIT 1
    ");

    $currentStmt->execute([
            ':id' => $id,
            ]);

    $current = $currentStmt->fetch();

    if (!$current) {
        $_SESSION['admin_error'] = 'Ticket category was not found.';
        $this->redirect('/admin/ticket-categories');
    }

if ($quantityAvailable < (int) $current->quantity_sold) {
    $_SESSION['admin_error'] = 'Quantity available cannot be less than tickets already sold.';
    $this->redirect('/admin/ticket-categories/edit?id=' . $id);
}

$eventStmt = $db->prepare("
    SELECT id
    FROM events
    WHERE id = :id
    LIMIT 1
    ");

    $eventStmt->execute([
            ':id' => $eventId,
            ]);

    if (!$eventStmt->fetch()) {
        $_SESSION['admin_error'] = 'Selected event was not found.';
        $this->redirect('/admin/ticket-categories/edit?id=' . $id);
    }

$stmt = $db->prepare("
    UPDATE ticket_categories
    SET
    event_id = :event_id,
    name = :name,
    description = :description,
    price = :price,
    quantity_available = :quantity_available,
    max_per_order = :max_per_order,
    status = :status,
    sale_start = :sale_start,
    sale_end = :sale_end
    WHERE id = :id
    ");

    $stmt->execute([
            ':event_id' => $eventId,
            ':name' => $name,
            ':description' => $description !== '' ? $description : null,
            ':price' => number_format($price, 2, '.', ''),
            ':quantity_available' => $quantityAvailable,
            ':max_per_order' => $maxPerOrder,
            ':status' => $status,
            ':sale_start' => $saleStart,
            ':sale_end' => $saleEnd,
            ':id' => $id,
            ]);

    $_SESSION['admin_success'] = 'Ticket category updated successfully.';
    $this->redirect('/admin/ticket-categories');
}

public function updateStatus(): void
{
    $this->requireRole('admin');

    $id = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($id <= 0 || !in_array($status, ['active', 'inactive', 'sold_out'], true)) {
        $_SESSION['admin_error'] = 'Invalid category status request.';
        $this->redirect('/admin/ticket-categories');
    }

$db = Database::connect();

$stmt = $db->prepare("
    UPDATE ticket_categories
    SET status = :status
    WHERE id = :id
    ");

    $stmt->execute([
            ':status' => $status,
            ':id' => $id,
            ]);

    if ($status === 'active') {
        $_SESSION['admin_success'] = 'Ticket category published/activated.';
} elseif ($status === 'inactive') {
$_SESSION['admin_success'] = 'Ticket category unpublished/inactivated.';
} else {
$_SESSION['admin_success'] = 'Ticket category marked as sold out.';
}

$this->redirect('/admin/ticket-categories');
}

private function cleanDateTime(string $value): ?string
{
    $value = trim($value);

    if ($value === '') {
        return null;
    }

$value = str_replace('T', ' ', $value);

if (strlen($value) === 16) {
    $value .= ':00';
}

return $value;
}
}
