<?php
function category_datetime_value(?string $value): string
{
    if (empty($value)) {
        return '';
    }

return date('Y-m-d\TH:i', strtotime($value));
}
?>

<section class="admin-page">
<div class="admin-header">
<div>
<span class="badge">Admin</span>
<h1>Edit Ticket Category</h1>
<p class="muted">
Update pricing, quantity, sale dates, and publish status.
</p>
</div>

<div class="admin-actions">
<a href="<?= $appUrl ?>/admin/ticket-categories" class="btn secondary">
Back to Categories
</a>

<a href="<?= $appUrl ?>/scanner" class="btn gold">
Scanner
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

<div class="form-panel wide-form-panel">
<h2><?= htmlspecialchars($category->name) ?></h2>

<p class="muted">
Currently linked to
<strong><?= htmlspecialchars($category->event_title) ?></strong>
on <?= htmlspecialchars(date('d M Y', strtotime($category->event_date))) ?>.
</p>

<form method="POST" action="<?= $appUrl ?>/admin/ticket-categories/edit" class="admin-form">
<input type="hidden" name="id" value="<?= (int) $category->id ?>">

<div class="form-group">
<label>Event</label>
<select name="event_id" required>
<?php foreach ($events as $event): ?>
<option
value="<?= (int) $event->id ?>"
<?= (int) $event->id === (int) $category->event_id ? 'selected' : '' ?>
>
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
value="<?= htmlspecialchars($category->name) ?>"
required
>
</div>

<div class="form-group">
<label>Description</label>
<textarea name="description" rows="4"><?= htmlspecialchars($category->description ?? '') ?></textarea>
</div>

<div class="grid-two-mobile">
<div class="form-group">
<label>Price</label>
<input
type="number"
name="price"
min="0"
step="0.01"
value="<?= htmlspecialchars(number_format((float) $category->price, 2, '.', '')) ?>"
required
>
</div>

<div class="form-group">
<label>Quantity Available</label>
<input
type="number"
name="quantity_available"
min="<?= (int) $category->quantity_sold ?>"
step="1"
value="<?= (int) $category->quantity_available ?>"
required
>

<small class="field-help">
<?= (int) $category->quantity_sold ?> ticket(s) already sold. Quantity cannot be less than sold.
</small>
</div>
</div>

<div class="grid-two-mobile">
<div class="form-group">
<label>Max Per Order</label>
<input
type="number"
name="max_per_order"
min="1"
step="1"
value="<?= (int) $category->max_per_order ?>"
required
>
</div>

<div class="form-group">
<label>Status</label>
<select name="status" required>
<option value="active" <?= $category->status === 'active' ? 'selected' : '' ?>>
Active / Published
</option>

<option value="inactive" <?= $category->status === 'inactive' ? 'selected' : '' ?>>
Inactive / Unpublished
</option>

<option value="sold_out" <?= $category->status === 'sold_out' ? 'selected' : '' ?>>
Sold Out
</option>
</select>
</div>
</div>

<div class="grid-two-mobile">
<div class="form-group">
<label>Sale Start</label>
<input
type="datetime-local"
name="sale_start"
value="<?= htmlspecialchars(category_datetime_value($category->sale_start ?? null)) ?>"
>
</div>

<div class="form-group">
<label>Sale End</label>
<input
type="datetime-local"
name="sale_end"
value="<?= htmlspecialchars(category_datetime_value($category->sale_end ?? null)) ?>"
>
</div>
</div>

<div class="edit-actions">
<button type="submit" class="btn primary">
Save Changes
</button>

<a href="<?= $appUrl ?>/admin/ticket-categories" class="btn secondary">
Cancel
</a>
</div>
</form>
</div>
</section>
