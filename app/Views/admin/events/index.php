<section class="admin-events-page">
<div class="admin-events-shell">
<div class="admin-events-hero">
<div>
<span class="badge">Admin</span>

<h1>Events</h1>

<p>
Manage event details, publishing status, tickets, and public visibility.
</p>
</div>

<div class="admin-events-actions">
<a href="<?= $appUrl ?>/admin/events/create" class="btn primary">
Create Event
</a>

<a href="<?= $appUrl ?>/admin/ticket-categories" class="btn secondary-dark">
Categories
</a>
</div>
</div>

<?php if (!empty($_SESSION['admin_success'])): ?>
<div class="alert success">
<?= htmlspecialchars($_SESSION['admin_success']) ?>
</div>
<?php unset($_SESSION['admin_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['admin_error'])): ?>
<div class="alert error">
<?= htmlspecialchars($_SESSION['admin_error']) ?>
</div>
<?php unset($_SESSION['admin_error']); ?>
<?php endif; ?>

<?php if (empty($events)): ?>
<div class="empty-card">
<h3>No events yet</h3>

<p>
Create your first event to start selling tickets.
</p>

<a href="<?= $appUrl ?>/admin/events/create" class="btn primary">
Create Event
</a>
</div>
<?php else: ?>
<div class="admin-event-grid">
<?php foreach ($events as $event): ?>
<?php
$imageUrl = !empty($event->feature_image)
? $appUrl . '/' . ltrim($event->feature_image, '/')
: '';

$publicUrl = $appUrl . '/event?slug=' . urlencode($event->slug) . '&eventID=' . (int) $event->id;
$statusClass = 'event-status-' . strtolower(str_replace('_', '-', $event->status));
?>

<article class="admin-event-card">
<div class="admin-event-image">
<?php if ($imageUrl): ?>
<img
src="<?= htmlspecialchars($imageUrl) ?>"
alt="<?= htmlspecialchars($event->title) ?>"
>
<?php else: ?>
<div class="admin-event-image-placeholder">
Royal Splash
</div>
<?php endif; ?>

<span class="admin-event-status <?= htmlspecialchars($statusClass) ?>">
<?= htmlspecialchars(str_replace('_', ' ', ucfirst($event->status))) ?>
</span>
</div>

<div class="admin-event-body">
<h2><?= htmlspecialchars($event->title) ?></h2>

<p>
<?= htmlspecialchars(date('D, d M Y', strtotime($event->event_date))) ?>
· <?= htmlspecialchars($event->venue_name) ?>
</p>

<div class="admin-event-mini-grid">
<div>
<span>Categories</span>
<strong><?= (int) $event->category_count ?></strong>
</div>

<div>
<span>Tickets</span>
<strong><?= (int) $event->ticket_count ?></strong>
</div>

<div>
<span>Revenue</span>
<strong>R<?= number_format((float) $event->paid_revenue, 2) ?></strong>
</div>
</div>

<div class="admin-event-actions-row">
<a
href="<?= $appUrl ?>/admin/events/edit?id=<?= (int) $event->id ?>"
class="btn primary"
>
Edit
</a>

<a
href="<?= $publicUrl ?>"
class="btn secondary-dark"
target="_blank"
>
Preview
</a>
</div>
</div>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</section>
