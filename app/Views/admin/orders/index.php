<section class="admin-orders-page">
<div class="admin-orders-shell">
<div class="admin-orders-hero">
<div>
<span class="badge">Admin</span>

<h1>Orders</h1>

<p>
View payments, buyers, generated tickets, and resend important emails.
</p>
</div>

<div class="admin-orders-actions">
<a href="<?= $appUrl ?>/admin" class="btn secondary-dark">
Dashboard
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

<form method="GET" action="<?= $appUrl ?>/admin/orders" class="admin-order-filter-panel">
<div class="admin-order-filter-grid">
<div class="form-group">
<label>Search</label>

<input
type="text"
name="q"
value="<?= htmlspecialchars($filters['q']) ?>"
placeholder="Order number, name, email, phone"
>
</div>

<div class="form-group">
<label>Event</label>

<select name="event_id">
<option value="0">All events</option>

<?php foreach ($events as $event): ?>
<option
value="<?= (int) $event->id ?>"
<?= (int) $filters['event_id'] === (int) $event->id ? 'selected' : '' ?>
>
<?= htmlspecialchars($event->title) ?>
— <?= htmlspecialchars(date('d M Y', strtotime($event->event_date))) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group">
<label>Status</label>

<select name="status">
<option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>>All statuses</option>
<option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
<option value="paid" <?= $filters['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
<option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
<option value="failed" <?= $filters['status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
</select>
</div>
</div>

<div class="admin-order-filter-actions">
<button type="submit" class="btn primary">
Apply Filters
</button>

<a href="<?= $appUrl ?>/admin/orders" class="btn secondary-dark">
Clear
</a>
</div>
</form>

<div class="admin-order-stat-grid">
<div class="admin-order-stat-card">
<span>Total Orders</span>
<strong><?= (int) $summary->total_orders ?></strong>
</div>

<div class="admin-order-stat-card success">
<span>Paid Orders</span>
<strong><?= (int) $summary->paid_orders ?></strong>
</div>

<div class="admin-order-stat-card warning">
<span>Pending Orders</span>
<strong><?= (int) $summary->pending_orders ?></strong>
</div>

<div class="admin-order-stat-card gold">
<span>Paid Revenue</span>
<strong>R<?= number_format((float) $summary->paid_revenue, 2) ?></strong>
</div>
</div>

<div class="admin-order-table-card">
<div class="admin-order-table-header">
<div>
<h2>Order Records</h2>

<p>
Showing latest matching orders.
</p>
</div>
</div>

<?php if (empty($orders)): ?>
<div class="empty-card compact">
<h3>No orders found</h3>

<p>
No orders match the selected filters.
</p>
</div>
<?php else: ?>
<div class="admin-table-wrap">
<table class="admin-table admin-orders-table">
<thead>
<tr>
<th>Order</th>
<th>Buyer</th>
<th>Event</th>
<th>Tickets</th>
<th>Total</th>
<th>Status</th>
<th></th>
</tr>
</thead>

<tbody>
<?php foreach ($orders as $order): ?>
<?php
$statusClass = 'status-' . strtolower(str_replace('_', '-', $order->payment_status));
?>

<tr>
<td>
<strong><?= htmlspecialchars($order->order_number) ?></strong>
<br>
<small><?= htmlspecialchars($order->created_at) ?></small>
</td>

<td>
<?= htmlspecialchars($order->buyer_name) ?>
<br>
<small><?= htmlspecialchars($order->buyer_email) ?></small>
</td>

<td>
<?= htmlspecialchars($order->event_title) ?>
<br>
<small><?= htmlspecialchars(date('d M Y', strtotime($order->event_date))) ?></small>
</td>

<td>
<?= (int) $order->ticket_count ?> generated
<br>
<small><?= (int) $order->total_qty ?> ordered</small>
</td>

<td>
<strong>R<?= number_format((float) $order->total_amount, 2) ?></strong>
</td>

<td>
<span class="status-pill <?= htmlspecialchars($statusClass) ?>">
<?= htmlspecialchars(ucfirst($order->payment_status)) ?>
</span>
</td>

<td>
<a
href="<?= $appUrl ?>/admin/orders/view?id=<?= (int) $order->id ?>"
class="btn secondary-dark small-btn"
>
View
</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
</div>
</section>
