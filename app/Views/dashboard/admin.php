<section class="dashboard-wrap">
<div class="dashboard-card admin-dashboard-card">
<span class="badge">Admin</span>

<h1>Admin Dashboard</h1>

<p class="muted">
Welcome, <strong><?= htmlspecialchars($user->full_name) ?></strong>.
Manage events, ticket categories, orders, revenue and scanner users.
</p>

<div class="dashboard-stats">
<div class="stat-card">
<strong><?= number_format((int) $eventsCount) ?></strong>
<span>Events</span>
</div>

<div class="stat-card">
<strong><?= number_format((int) $ticketsSold) ?></strong>
<span>Tickets Sold</span>
</div>

<div class="stat-card">
<strong>R<?= number_format((float) $revenue, 2) ?></strong>
<span>Revenue</span>
</div>
</div>

<div class="dashboard-actions">
<a href="<?= $appUrl ?>/admin/events" class="btn gold">
Manage Events
</a>

<a href="<?= $appUrl ?>/admin/events/create" class="btn primary">
Create Event
</a>

<a href="<?= $appUrl ?>/scanner" class="btn gold">
Open Scanner
</a>

<a href="<?= $appUrl ?>/logout" class="btn primary">
Logout
</a>


</div>

<div class="admin-section">
<div class="section-heading">
<h2>Recent Orders</h2>
<p class="muted">Latest orders created through Royal Splash.</p>
</div>

<?php if (empty($recentOrders)): ?>
<div class="empty-card">
No orders yet.
</div>
<?php else: ?>
<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
<th>Order</th>
<th>Buyer</th>
<th>Event</th>
<th>Qty</th>
<th>Total</th>
<th>Status</th>
</tr>
</thead>

<tbody>
<?php foreach ($recentOrders as $order): ?>
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

<td><?= htmlspecialchars($order->event_title) ?></td>

<td><?= (int) $order->total_qty ?></td>

<td>R<?= number_format((float) $order->total_amount, 2) ?></td>

<td>
<span class="status-pill status-<?= htmlspecialchars($order->payment_status) ?>">
<?= htmlspecialchars(ucfirst($order->payment_status)) ?>
</span>
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
