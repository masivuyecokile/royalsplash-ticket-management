<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class PublicEventController extends Controller
{
    public function index(): void
    {
        $db = Database::connect();

        $stmt = $db->query("
            SELECT
            e.*,
            COALESCE(MIN(tc.price), 0) AS starting_price,
            COALESCE(SUM(tc.quantity_available - tc.quantity_sold), 0) AS remaining_tickets
            FROM events e
            LEFT JOIN ticket_categories tc
            ON tc.event_id = e.id
            AND tc.status = 'active'
            WHERE e.status IN ('published', 'on_sale')
            GROUP BY e.id
            ORDER BY e.event_date ASC, e.id DESC
            ");

            $events = $stmt->fetchAll();

            $this->view('public/events/index', [
                    'pageTitle' => 'Events',
                    'events' => $events,
                    ]);
        }

    public function show(): void
    {
        $slug = trim($_GET['slug'] ?? '');
        $eventId = (int) ($_GET['eventID'] ?? ($_GET['event_id'] ?? 0));

        if ($slug === '' && $eventId <= 0) {
            $this->redirect('/events');
        }

    $db = Database::connect();

    if ($eventId > 0) {
        $eventStmt = $db->prepare("
            SELECT *
            FROM events
            WHERE id = :id
            AND status IN ('published', 'on_sale')
            LIMIT 1
            ");

            $eventStmt->execute([
                    ':id' => $eventId,
                    ]);
        } else {
        $eventStmt = $db->prepare("
            SELECT *
            FROM events
            WHERE slug = :slug
            AND status IN ('published', 'on_sale')
            LIMIT 1
            ");

            $eventStmt->execute([
                    ':slug' => $slug,
                    ]);
        }

    $event = $eventStmt->fetch();

    if (!$event) {
        $this->redirect('/events');
    }

$categoryStmt = $db->prepare("
    SELECT *
    FROM ticket_categories
    WHERE event_id = :event_id
    AND status = 'active'
    AND quantity_available > quantity_sold
    AND (
        sale_start IS NULL
        OR sale_start <= NOW()
    )
AND (
    sale_end IS NULL
    OR sale_end >= NOW()
)
ORDER BY price ASC, id ASC
");

$categoryStmt->execute([
        ':event_id' => (int) $event->id,
        ]);

$categories = $categoryStmt->fetchAll();

$this->view('public/events/show', [
        'pageTitle' => $event->title,
        'event' => $event,
        'categories' => $categories,
        ]);
}
}
