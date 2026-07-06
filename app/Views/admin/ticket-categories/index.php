<section class="admin-category-page">
<div class="admin-category-shell">
<div class="admin-category-hero">
<div>
<span class="badge">Admin</span>

<h1>Ticket Categories</h1>

<p>
Create, edit, publish, unpublish, and manage ticket categories for your events.
</p>
</div>

<div class="admin-category-hero-actions">
<a href="<?= $appUrl ?>/admin" class="btn secondary">Dashboard</a>
<a href="<?= $appUrl ?>/admin/events" class="btn secondary">Events</a>
<a href="<?= $appUrl ?>/scanner" class="btn gold">Scanner</a>
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

<div class="admin-category-grid">
<aside class="category-create-panel">
<div class="panel-heading">
<span class="panel-icon">+</span>

<div>
<h2>Create Category</h2>
<p>Add a new ticket type to an event.</p>
</div>
</div>

<?php if (empty($events)): ?>
<div class="empty-card compact">
<p>Please create an event first before adding ticket categories.</p>

<a href="<?= $appUrl ?>/admin/events/create" class="btn primary full-width">
Create Event
</a>
</div>
<?php else: ?>
<form method="POST" action="<?= $appUrl ?>/admin/ticket-categories" class="premium-admin-form">
<div class="form-group">
<label>Event</label>

<select name="event_id" required>
<option value="">Select event</option>

<?php foreach ($events as $event): ?>
<option value="<?= (int) $event->id ?>">
<?= htmlspecialchars($event->title) ?>
— <?= htmlspecialchars(date('d M Y', strtotime($event->event_date))) ?>
(<?= htmlspecialchars($event->status) ?>)
</option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group">
<label>Category Name</label>

<input
type="text"
name="name"
placeholder="Example: General Ticket"
required
>
</div>

<div class="form-group">
<label>Description</label>

<textarea
name="description"
rows="4"
placeholder="Short description shown to buyers"
></textarea>
</div>

<div class="form-grid-2">
<div class="form-group">
<label>Price</label>

<div class="money-input">
<span>R</span>

<input
type="number"
name="price"
min="0"
step="0.01"
placeholder="120.00"
required
>
</div>
</div>

<div class="form-group">
<label>Quantity</label>

<input
type="number"
name="quantity_available"
min="0"
step="1"
placeholder="600"
required
>
</div>
</div>

<div class="form-grid-2">
<div class="form-group">
<label>Max Per Order</label>

<input
type="number"
name="max_per_order"
min="1"
step="1"
value="4"
required
>
</div>

<div class="form-group">
<label>Status</label>

<select name="status" required>
<option value="active">Active / Published</option>
<option value="inactive">Inactive / Unpublished</option>
<option value="sold_out">Sold Out</option>
</select>
</div>
</div>

<div class="form-grid-2">
<div class="form-group">
<label>Sale Start</label>

<input type="datetime-local" name="sale_start">
</div>

<div class="form-group">
<label>Sale End</label>

<input type="datetime-local" name="sale_end">
</div>
</div>

<button type="submit" class="btn primary full-width">
Create Category
</button>
</form>
<?php endif; ?>
</aside>

<section class="category-list-panel">
<div class="panel-title-row">
<div>
<h2>All Categories</h2>

<p>
Manage public availability, pricing, and stock levels.
</p>
</div>

<span class="category-count">
<?= count($categories) ?> total
</span>
</div>

<?php if (empty($categories)): ?>
<div class="empty-card">
<p>No ticket categories created yet.</p>
</div>
<?php else: ?>
<div class="category-admin-list premium">
<?php foreach ($categories as $category): ?>
<?php
$remaining = (int) $category->quantity_available - (int) $category->quantity_sold;

if ($remaining < 0) {
    $remaining = 0;
}

$statusClass = strtolower(str_replace('_', '-', $category->status));
$soldPercent = 0;

if ((int) $category->quantity_available > 0) {
    $soldPercent = round(((int) $category->quantity_sold / (int) $category->quantity_available) * 100);
}

if ($soldPercent > 100) {
    $soldPercent = 100;
}
?>

<article class="premium-category-card">
<div class="premium-category-main">
<div class="premium-category-info">
<span class="status-pill <?= htmlspecialchars($statusClass) ?>">
<?= htmlspecialchars(str_replace('_', ' ', ucfirst($category->status))) ?>
</span>

<h3><?= htmlspecialchars($category->name) ?></h3>

<p>
<?= htmlspecialchars($category->event_title) ?>
· <?= htmlspecialchars(date('d M Y', strtotime($category->event_date))) ?>
</p>

<?php if (!empty($category->description)): ?>
<div class="premium-category-desc">
<?= htmlspecialchars($category->description) ?>
</div>
<?php endif; ?>
</div>

<div class="premium-category-price">
<span>Price</span>
<strong>R<?= number_format((float) $category->price, 2) ?></strong>
</div>
</div>

<div class="category-progress-wrap">
<div class="category-progress-top">
<span><?= (int) $category->quantity_sold ?> sold</span>
<span><?= $remaining ?> remaining</span>
</div>

<div class="category-progress">
<span style="width: <?= (int) $soldPercent ?>%;"></span>
</div>
</div>

<div class="premium-category-metrics">
<div>
<span>Available</span>
<strong><?= (int) $category->quantity_available ?></strong>
</div>

<div>
<span>Sold</span>
<strong><?= (int) $category->quantity_sold ?></strong>
</div>

<div>
<span>Remaining</span>
<strong><?= $remaining ?></strong>
</div>

<div>
<span>Max/order</span>
<strong><?= (int) $category->max_per_order ?></strong>
</div>
</div>

<div class="premium-category-actions">
<a
href="<?= $appUrl ?>/admin/ticket-categories/edit?id=<?= (int) $category->id ?>"
class="btn secondary"
>
Edit
</a>

<?php if ($category->status !== 'active'): ?>
<form method="POST" action="<?= $appUrl ?>/admin/ticket-categories/status">
<input type="hidden" name="id" value="<?= (int) $category->id ?>">
<input type="hidden" name="status" value="active">

<button type="submit" class="btn primary">
Publish
</button>
</form>
<?php endif; ?>

<?php if ($category->status !== 'inactive'): ?>
<form method="POST" action="<?= $appUrl ?>/admin/ticket-categories/status">
<input type="hidden" name="id" value="<?= (int) $category->id ?>">
<input type="hidden" name="status" value="inactive">

<button type="submit" class="btn secondary">
Unpublish
</button>
</form>
<?php endif; ?>

<?php if ($category->status !== 'sold_out'): ?>
<form method="POST" action="<?= $appUrl ?>/admin/ticket-categories/status">
<input type="hidden" name="id" value="<?= (int) $category->id ?>">
<input type="hidden" name="status" value="sold_out">

<button type="submit" class="btn gold">
Sold Out
</button>
</form>
<?php endif; ?>
</div>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>
</div>
</div>
</section>
