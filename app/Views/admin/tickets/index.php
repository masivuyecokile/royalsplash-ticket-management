<section class="admin-tickets-page">
<div class="admin-tickets-shell">
<div class="admin-tickets-hero">
<div>
<span class="badge">Admin</span>

<h1>Tickets</h1>

<p>
Manage individual tickets, scan status, cancellations, and ticket records.
</p>
</div>

<div class="admin-tickets-actions">
<a href="<?= $appUrl ?>/admin/orders" class="btn secondary-dark">
Orders
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

<form method="GET" action="<?= $appUrl ?>/admin/tickets" class="admin-ticket-filter-panel">
<div class="admin-ticket-filter-grid">
<div class="form-group">
<label>Search</label>

<input
type="text"
name="q"
value="<?= htmlspecialchars($filters['q']) ?>"
placeholder="Ticket number, buyer, email, order"
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
<option value="valid" <?= $filters['status'] === 'valid' ? 'selected' : '' ?>>Valid</option>
<option value="used" <?= $filters['status'] === 'used' ? 'selected' : '' ?>>Used</option>
<option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
<option value="transferred" <?= $filters['status'] === 'transferred' ? 'selected' : '' ?>>Transferred</option>
</select>
</div>
</div>

<div class="admin-ticket-filter-actions">
<button type="submit" class="btn primary">
Apply Filters
</button>

<a href="<?= $appUrl ?>/admin/tickets" class="btn secondary-dark">
Clear
</a>
</div>
</form>

<div class="admin-ticket-stat-grid">
<div class="admin-ticket-stat-card">
<span>Total Tickets</span>
<strong><?= (int) $summary->total_tickets ?></strong>
</div>

<div class="admin-ticket-stat-card success">
<span>Valid</span>
<strong><?= (int) $summary->valid_tickets ?></strong>
</div>

<div class="admin-ticket-stat-card used">
<span>Used</span>
<strong><?= (int) $summary->used_tickets ?></strong>
</div>

<div class="admin-ticket-stat-card danger">
<span>Cancelled</span>
<strong><?= (int) $summary->cancelled_tickets ?></strong>
</div>
</div>

<div class="admin-ticket-table-card">
<div class="admin-ticket-table-header">
<div>
<h2>Ticket Records</h2>

<p>
Showing latest matching tickets.
</p>
</div>
</div>

<?php if (empty($tickets)): ?>
<div class="empty-card compact">
<h3>No tickets found</h3>

<p>No tickets match the selected filters.</p>
</div>
<?php else: ?>
<div class="admin-table-wrap">
<table class="admin-table admin-tickets-table">
<thead>
<tr>
<th>Ticket</th>
<th>Buyer</th>
<th>Event</th>
<th>Order</th>
<th>Status</th>
<th>Scanned</th>
<th></th>
</tr>
</thead>

<tbody>
<?php foreach ($tickets as $ticket): ?>
<?php
$statusClass = 'status-' . strtolower(str_replace('_', '-', $ticket->status));
?>

<tr>
<td>
<strong><?= htmlspecialchars($ticket->ticket_number) ?></strong>
<br>
<small><?= htmlspecialchars($ticket->ticket_name) ?></small>
</td>

<td>
<?= htmlspecialchars($ticket->buyer_name) ?>
<br>
<small><?= htmlspecialchars($ticket->buyer_email) ?></small>
</td>

<td>
<?= htmlspecialchars($ticket->event_title) ?>
<br>
<small><?= htmlspecialchars(date('d M Y', strtotime($ticket->event_date))) ?></small>
</td>

<td>
<?= htmlspecialchars($ticket->order_number) ?>
<br>
<small><?= htmlspecialchars($ticket->payment_status) ?></small>
</td>

<td>
<span class="status-pill <?= htmlspecialchars($statusClass) ?>">
<?= htmlspecialchars(ucfirst($ticket->status)) ?>
</span>
</td>

<td>
<?php if (!empty($ticket->scanned_at)): ?>
<?= htmlspecialchars($ticket->scanned_at) ?>
<br>
<small><?= htmlspecialchars($ticket->scanned_by_name ?: 'Unknown scanner') ?></small>
<?php else: ?>
<span class="muted">Not scanned</span>
<?php endif; ?>
</td>

<td>
<a
href="<?= $appUrl ?>/admin/tickets/view?id=<?= (int) $ticket->id ?>"
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
