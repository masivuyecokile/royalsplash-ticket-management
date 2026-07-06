<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

class EventController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin');

        $db = Database::connect();

        $stmt = $db->query("
            SELECT
            e.*,
            (
                SELECT COUNT(*)
                FROM ticket_categories tc
                WHERE tc.event_id = e.id
            ) AS category_count,
        (
            SELECT COUNT(*)
            FROM tickets t
            WHERE t.event_id = e.id
        ) AS ticket_count,
    (
        SELECT COALESCE(SUM(o.total_amount), 0)
        FROM orders o
        WHERE o.event_id = e.id
        AND o.payment_status = 'paid'
    ) AS paid_revenue
FROM events e
ORDER BY e.event_date DESC, e.id DESC
");

$events = $stmt->fetchAll();

$this->view('admin/events/index', [
        'pageTitle' => 'Events',
        'events' => $events,
        ]);
}

public function create(): void
{
    $this->requireRole('admin');

    $this->view('admin/events/create', [
            'pageTitle' => 'Create Event',
            ]);
}

public function store(): void
{
    $this->requireRole('admin');

    $db = Database::connect();

    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $status = trim($_POST['status'] ?? 'draft');
    $eventDate = trim($_POST['event_date'] ?? '');
    $startTime = trim($_POST['start_time'] ?? '');
    $endTime = trim($_POST['end_time'] ?? '');
    $venueName = trim($_POST['venue_name'] ?? '');
    $venueAddress = trim($_POST['venue_address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $mainArtist = trim($_POST['main_artist'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $vipDetails = trim($_POST['vip_details'] ?? '');
    $totalCapacity = (int) ($_POST['total_capacity'] ?? 0);

    $allowedStatuses = ['draft', 'published', 'on_sale', 'sold_out', 'cancelled'];

    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'draft';
    }

if (
    $title === '' ||
    $eventDate === '' ||
    $startTime === '' ||
    $venueName === '' ||
    $city === ''
) {
$_SESSION['admin_error'] = 'Please complete all required event fields.';
$this->redirect('/admin/events/create');
}

$slug = $this->makeUniqueSlug($db, $title);
$featureImage = $this->uploadFeatureImage();

$user = $this->authUser();

$stmt = $db->prepare("
    INSERT INTO events (
        title,
        slug,
        subtitle,
        status,
        event_date,
        start_time,
        end_time,
        venue_name,
        venue_address,
        city,
        main_artist,
        feature_image,
        description,
        vip_details,
        total_capacity,
        created_by
    ) VALUES (
    :title,
    :slug,
    :subtitle,
    :status,
    :event_date,
    :start_time,
    :end_time,
    :venue_name,
    :venue_address,
    :city,
    :main_artist,
    :feature_image,
    :description,
    :vip_details,
    :total_capacity,
    :created_by
)
");

$stmt->execute([
        ':title' => $title,
        ':slug' => $slug,
        ':subtitle' => $subtitle !== '' ? $subtitle : null,
        ':status' => $status,
        ':event_date' => $eventDate,
        ':start_time' => $startTime,
        ':end_time' => $endTime !== '' ? $endTime : null,
        ':venue_name' => $venueName,
        ':venue_address' => $venueAddress !== '' ? $venueAddress : null,
        ':city' => $city,
        ':main_artist' => $mainArtist !== '' ? $mainArtist : null,
        ':feature_image' => $featureImage,
        ':description' => $description !== '' ? $description : null,
        ':vip_details' => $vipDetails !== '' ? $vipDetails : null,
        ':total_capacity' => $totalCapacity,
        ':created_by' => $user ? (int) $user->id : null,
        ]);

$_SESSION['admin_success'] = 'Event created successfully.';
$this->redirect('/admin/events');
}

public function edit(): void
{
    $this->requireRole('admin');

    $eventId = (int) ($_GET['id'] ?? 0);

    if ($eventId <= 0) {
        $this->redirect('/admin/events');
    }

$db = Database::connect();

$event = $this->findEvent($db, $eventId);

if (!$event) {
    $_SESSION['admin_error'] = 'Event not found.';
    $this->redirect('/admin/events');
}

$statsStmt = $db->prepare("
    SELECT
    (
        SELECT COUNT(*)
        FROM ticket_categories tc
        WHERE tc.event_id = :event_id
    ) AS category_count,
(
    SELECT COUNT(*)
    FROM tickets t
    WHERE t.event_id = :event_id
) AS ticket_count,
(
    SELECT COUNT(*)
    FROM orders o
    WHERE o.event_id = :event_id
) AS order_count,
(
    SELECT COALESCE(SUM(o.total_amount), 0)
    FROM orders o
    WHERE o.event_id = :event_id
    AND o.payment_status = 'paid'
) AS paid_revenue
");

$statsStmt->execute([
        ':event_id' => $eventId,
        ]);

$stats = $statsStmt->fetch();

$this->view('admin/events/edit', [
        'pageTitle' => 'Edit Event',
        'event' => $event,
        'stats' => $stats,
        ]);
}

public function update(): void
{
    $this->requireRole('admin');

    $eventId = (int) ($_POST['event_id'] ?? 0);

    if ($eventId <= 0) {
        $this->redirect('/admin/events');
    }

$db = Database::connect();

$event = $this->findEvent($db, $eventId);

if (!$event) {
    $_SESSION['admin_error'] = 'Event not found.';
    $this->redirect('/admin/events');
}

$title = trim($_POST['title'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$subtitle = trim($_POST['subtitle'] ?? '');
$status = trim($_POST['status'] ?? 'draft');
$eventDate = trim($_POST['event_date'] ?? '');
$startTime = trim($_POST['start_time'] ?? '');
$endTime = trim($_POST['end_time'] ?? '');
$venueName = trim($_POST['venue_name'] ?? '');
$venueAddress = trim($_POST['venue_address'] ?? '');
$city = trim($_POST['city'] ?? '');
$mainArtist = trim($_POST['main_artist'] ?? '');
$description = trim($_POST['description'] ?? '');
$vipDetails = trim($_POST['vip_details'] ?? '');
$totalCapacity = (int) ($_POST['total_capacity'] ?? 0);
$removeImage = !empty($_POST['remove_image']);

$allowedStatuses = ['draft', 'published', 'on_sale', 'sold_out', 'cancelled'];

if (!in_array($status, $allowedStatuses, true)) {
    $status = 'draft';
}

if (
    $title === '' ||
    $eventDate === '' ||
    $startTime === '' ||
    $venueName === '' ||
    $city === ''
) {
$_SESSION['admin_error'] = 'Please complete all required event fields.';
$this->redirect('/admin/events/edit?id=' . $eventId);
}

if ($slug === '') {
    $slug = $title;
}

$slug = $this->makeUniqueSlug($db, $slug, $eventId);

$featureImage = $event->feature_image;

if ($removeImage) {
    $featureImage = null;
}

$newImage = $this->uploadFeatureImage();

if ($newImage !== null) {
    $featureImage = $newImage;
}

$stmt = $db->prepare("
    UPDATE events
    SET
    title = :title,
    slug = :slug,
    subtitle = :subtitle,
    status = :status,
    event_date = :event_date,
    start_time = :start_time,
    end_time = :end_time,
    venue_name = :venue_name,
    venue_address = :venue_address,
    city = :city,
    main_artist = :main_artist,
    feature_image = :feature_image,
    description = :description,
    vip_details = :vip_details,
    total_capacity = :total_capacity,
    updated_at = NOW()
    WHERE id = :id
    ");

    $stmt->execute([
            ':title' => $title,
            ':slug' => $slug,
            ':subtitle' => $subtitle !== '' ? $subtitle : null,
            ':status' => $status,
            ':event_date' => $eventDate,
            ':start_time' => $startTime,
            ':end_time' => $endTime !== '' ? $endTime : null,
            ':venue_name' => $venueName,
            ':venue_address' => $venueAddress !== '' ? $venueAddress : null,
            ':city' => $city,
            ':main_artist' => $mainArtist !== '' ? $mainArtist : null,
            ':feature_image' => $featureImage,
            ':description' => $description !== '' ? $description : null,
            ':vip_details' => $vipDetails !== '' ? $vipDetails : null,
            ':total_capacity' => $totalCapacity,
            ':id' => $eventId,
            ]);

    $_SESSION['admin_success'] = 'Event updated successfully.';
    $this->redirect('/admin/events/edit?id=' . $eventId);
}

public function updateStatus(): void
{
    $this->requireRole('admin');

    $eventId = (int) ($_POST['event_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    $allowedStatuses = ['draft', 'published', 'on_sale', 'sold_out', 'cancelled'];

    if ($eventId <= 0 || !in_array($status, $allowedStatuses, true)) {
        $_SESSION['admin_error'] = 'Invalid event status request.';
        $this->redirect('/admin/events');
    }

$db = Database::connect();

$event = $this->findEvent($db, $eventId);

if (!$event) {
    $_SESSION['admin_error'] = 'Event not found.';
    $this->redirect('/admin/events');
}

$stmt = $db->prepare("
    UPDATE events
    SET
    status = :status,
    updated_at = NOW()
    WHERE id = :id
    ");

    $stmt->execute([
            ':status' => $status,
            ':id' => $eventId,
            ]);

    $_SESSION['admin_success'] = 'Event status updated successfully.';
    $this->redirect('/admin/events/edit?id=' . $eventId);
}

private function findEvent(PDO $db, int $eventId): ?object
{
    $stmt = $db->prepare("
        SELECT *
        FROM events
        WHERE id = :id
        LIMIT 1
        ");

        $stmt->execute([
                ':id' => $eventId,
                ]);

        $event = $stmt->fetch();

        return $event ?: null;
    }

private function makeUniqueSlug(PDO $db, string $text, int $ignoreEventId = 0): string
{
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim($slug, '-');

    if ($slug === '') {
        $slug = 'event';
    }

$baseSlug = $slug;
$counter = 2;

while ($this->slugExists($db, $slug, $ignoreEventId)) {
    $slug = $baseSlug . '-' . $counter;
    $counter++;
}

return $slug;
}

private function slugExists(PDO $db, string $slug, int $ignoreEventId = 0): bool
{
    if ($ignoreEventId > 0) {
        $stmt = $db->prepare("
            SELECT id
            FROM events
            WHERE slug = :slug
            AND id != :id
            LIMIT 1
            ");

            $stmt->execute([
                    ':slug' => $slug,
                    ':id' => $ignoreEventId,
                    ]);
        } else {
        $stmt = $db->prepare("
            SELECT id
            FROM events
            WHERE slug = :slug
            LIMIT 1
            ");

            $stmt->execute([
                    ':slug' => $slug,
                    ]);
        }

    return (bool) $stmt->fetch();
}

private function uploadFeatureImage(): ?string
{
    if (empty($_FILES['feature_image']) || empty($_FILES['feature_image']['name'])) {
        return null;
    }

if ($_FILES['feature_image']['error'] !== UPLOAD_ERR_OK) {
    return null;
}

$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
$originalName = $_FILES['feature_image']['name'];
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($extension, $allowedExtensions, true)) {
    $_SESSION['admin_error'] = 'Feature image must be JPG, PNG, or WEBP.';
    return null;
}

$uploadDir = __DIR__ . '/../../uploads/events';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$fileName = 'event-' . date('YmdHis') . '-' . bin2hex(random_bytes(5)) . '.' . $extension;
$targetPath = $uploadDir . '/' . $fileName;

if (!move_uploaded_file($_FILES['feature_image']['tmp_name'], $targetPath)) {
    $_SESSION['admin_error'] = 'Could not upload feature image.';
    return null;
}

return 'uploads/events/' . $fileName;
}
}
