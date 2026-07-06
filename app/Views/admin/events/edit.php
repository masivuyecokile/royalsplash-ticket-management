<?php
$imageUrl = !empty($event->feature_image)
? $appUrl . '/' . ltrim($event->feature_image, '/')
: '';

$publicUrl = $appUrl . '/event?slug=' . urlencode($event->slug) . '&eventID=' . (int) $event->id;
?>

<section class="admin-event-edit-page">
<div class="admin-event-edit-shell">
<div class="admin-event-edit-hero">
<div>
<span class="badge">Edit Event</span>

<h1><?= htmlspecialchars($event->title) ?></h1>

<p>
Update event information, status, feature image, and public event details.
</p>
</div>

<div class="admin-events-actions">
<a href="<?= $appUrl ?>/admin/events" class="btn secondary-dark">
Back to Events
</a>

<a href="<?= $publicUrl ?>" class="btn gold" target="_blank">
Preview Public Event
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

<div class="admin-event-edit-grid">
<div class="admin-event-edit-main">
<form
method="POST"
action="<?= $appUrl ?>/admin/events/edit"
enctype="multipart/form-data"
class="admin-event-edit-card"
>
<input type="hidden" name="event_id" value="<?= (int) $event->id ?>">

<h2>Event Details</h2>

<div class="admin-event-form-grid">
<div class="form-group wide">
<label>Event Title *</label>

<input
type="text"
name="title"
value="<?= htmlspecialchars($event->title) ?>"
required
>
</div>

<div class="form-group wide">
<label>Slug</label>

<input
type="text"
name="slug"
value="<?= htmlspecialchars($event->slug) ?>"
required
>

<small>
Public URL: <?= htmlspecialchars($publicUrl) ?>
</small>
</div>

<div class="form-group wide">
<label>Subtitle</label>

<input
type="text"
name="subtitle"
value="<?= htmlspecialchars($event->subtitle ?? '') ?>"
>
</div>

<div class="form-group">
<label>Status</label>

<select name="status">
<option value="draft" <?= $event->status === 'draft' ? 'selected' : '' ?>>Draft</option>
<option value="published" <?= $event->status === 'published' ? 'selected' : '' ?>>Published</option>
<option value="on_sale" <?= $event->status === 'on_sale' ? 'selected' : '' ?>>On Sale</option>
<option value="sold_out" <?= $event->status === 'sold_out' ? 'selected' : '' ?>>Sold Out</option>
<option value="cancelled" <?= $event->status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
</select>
</div>

<div class="form-group">
<label>Total Capacity</label>

<input
type="number"
name="total_capacity"
value="<?= (int) $event->total_capacity ?>"
min="0"
>
</div>

<div class="form-group">
<label>Event Date *</label>

<input
type="date"
name="event_date"
value="<?= htmlspecialchars($event->event_date) ?>"
required
>
</div>

<div class="form-group">
<label>Start Time *</label>

<input
type="text"
name="start_time"
value="<?= htmlspecialchars($event->start_time) ?>"
placeholder="8am"
required
>
</div>

<div class="form-group">
<label>End Time</label>

<input
type="text"
name="end_time"
value="<?= htmlspecialchars($event->end_time ?? '') ?>"
placeholder="Till late"
>
</div>

<div class="form-group">
<label>Main Artist</label>

<input
type="text"
name="main_artist"
value="<?= htmlspecialchars($event->main_artist ?? '') ?>"
>
</div>

<div class="form-group wide">
<label>Venue Name *</label>

<input
type="text"
name="venue_name"
value="<?= htmlspecialchars($event->venue_name) ?>"
required
>
</div>

<div class="form-group">
<label>City *</label>

<input
type="text"
name="city"
value="<?= htmlspecialchars($event->city) ?>"
required
>
</div>

<div class="form-group">
<label>Feature Image</label>

<input
type="file"
name="feature_image"
accept=".jpg,.jpeg,.png,.webp"
>
</div>

<div class="form-group wide">
<label>Venue Address</label>

<input
type="text"
name="venue_address"
value="<?= htmlspecialchars($event->venue_address ?? '') ?>"
>
</div>

<div class="form-group wide">
<label>Description</label>

<textarea name="description" rows="7"><?= htmlspecialchars($event->description ?? '') ?></textarea>
</div>

<div class="form-group wide">
<label>VIP Details</label>

<textarea name="vip_details" rows="5"><?= htmlspecialchars($event->vip_details ?? '') ?></textarea>
</div>

<?php if ($imageUrl): ?>
<div class="form-group wide">
<label class="checkbox-line">
<input type="checkbox" name="remove_image" value="1">
Remove current feature image
</label>
</div>
<?php endif; ?>
</div>

<button type="submit" class="btn primary full-width">
Save Event Changes
</button>
</form>
</div>

<aside class="admin-event-edit-side">
<div class="admin-event-edit-card sticky-card">
<h2>Current Image</h2>

<div class="admin-event-current-image">
<?php if ($imageUrl): ?>
<img
src="<?= htmlspecialchars($imageUrl) ?>"
alt="<?= htmlspecialchars($event->title) ?>"
>
<?php else: ?>
<div class="admin-event-current-image-placeholder">
No Image
</div>
<?php endif; ?>
</div>

<hr>

<h2>Quick Status</h2>

<div class="event-status-button-grid">
<?php
$statuses = [
    'draft' => 'Draft',
    'published' => 'Published',
    'on_sale' => 'On Sale',
    'sold_out' => 'Sold Out',
    'cancelled' => 'Cancelled',
    ];
?>

<?php foreach ($statuses as $value => $label): ?>
<form method="POST" action="<?= $appUrl ?>/admin/events/status">
<input type="hidden" name="event_id" value="<?= (int) $event->id ?>">
<input type="hidden" name="status" value="<?= htmlspecialchars($value) ?>">

<button
type="submit"
class="btn <?= $event->status === $value ? 'primary' : 'secondary-dark' ?> full-width"
>
<?= htmlspecialchars($label) ?>
</button>
</form>
<?php endforeach; ?>
</div>

<hr>

<div class="admin-event-stats-box">
<div>
<span>Orders</span>
<strong><?= (int) ($stats->order_count ?? 0) ?></strong>
</div>

<div>
<span>Tickets</span>
<strong><?= (int) ($stats->ticket_count ?? 0) ?></strong>
</div>

<div>
<span>Categories</span>
<strong><?= (int) ($stats->category_count ?? 0) ?></strong>
</div>

<div>
<span>Revenue</span>
<strong>R<?= number_format((float) ($stats->paid_revenue ?? 0), 2) ?></strong>
</div>
</div>

<a href="<?= $appUrl ?>/admin/ticket-categories" class="btn gold full-width">
Manage Categories
</a>
</div>
</aside>
</div>
</div>
</section>
