<section class="admin-order-detail-page">
<div class="admin-order-detail-shell">
<div class="admin-order-detail-hero">
<div>
<span class="badge">Order</span>

<h1><?= htmlspecialchars($order->order_number) ?></h1>

<p>
View buyer information, payment status, tickets, and PayFast activity.
</p>
</div>

<div class="admin-orders-actions">
<a href="<?= $appUrl ?>/admin/orders" class="btn secondary-dark">
Back to Orders
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

<div class="admin-order-detail-grid">
<div class="admin-order-main-column">
<div class="admin-order-card">
<div class="admin-order-card-header">
<h2>Order Summary</h2>

<span class="status-pill status-<?= htmlspecialchars($order->payment_status) ?>">
<?= htmlspecialchars(ucfirst($order->payment_status)) ?>
</span>
</div>

<div class="admin-order-info-grid">
<div>
<span>Buyer Name</span>
<strong><?= htmlspecialchars($order->buyer_name) ?></strong>
</div>

<div>
<span>Buyer Email</span>
<strong><?= htmlspecialchars($order->buyer_email) ?></strong>
</div>

<div>
<span>Phone</span>
<strong><?= htmlspecialchars($order->buyer_phone ?: '—') ?></strong>
</div>

<div>
<span>Amount</span>
<strong>R<?= number_format((float) $order->total_amount, 2) ?></strong>
</div>

<div>
<span>Tickets Ordered</span>
<strong><?= (int) $order->total_qty ?></strong>
</div>

<div>
<span>Payment ID</span>
<strong><?= htmlspecialchars($order->payfast_payment_id ?: '—') ?></strong>
</div>

<div>
<span>Created</span>
<strong><?= htmlspecialchars($order->created_at) ?></strong>
</div>

<div>
<span>Paid At</span>
<strong><?= htmlspecialchars($order->paid_at ?: '—') ?></strong>
</div>
</div>
</div>

<div class="admin-order-card">
<h2>Event</h2>

<div class="admin-order-info-grid">
<div>
<span>Event</span>
<strong><?= htmlspecialchars($order->event_title) ?></strong>
</div>

<div>
<span>Date</span>
<strong><?= htmlspecialchars(date('d M Y', strtotime($order->event_date))) ?></strong>
</div>

<div>
<span>Time</span>
<strong>
<?= htmlspecialchars($order->start_time) ?>
<?php if (!empty($order->end_time)): ?>
- <?= htmlspecialchars($order->end_time) ?>
<?php endif; ?>
</strong>
</div>

<div>
<span>Venue</span>
<strong><?= htmlspecialchars($order->venue_name) ?></strong>
</div>

<div class="wide">
<span>Address</span>
<strong><?= htmlspecialchars($order->venue_address ?: $order->city) ?></strong>
</div>
</div>
</div>

<div class="admin-order-card">
<h2>Order Items</h2>

<?php if (empty($items)): ?>
<p class="muted">No order items found.</p>
<?php else: ?>
<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
<th>Ticket Type</th>
<th>Qty</th>
<th>Unit Price</th>
<th>Total</th>
</tr>
</thead>

<tbody>
<?php foreach ($items as $item): ?>
<tr>
<td><?= htmlspecialchars($item->ticket_name) ?></td>
<td><?= (int) $item->quantity ?></td>
<td>R<?= number_format((float) $item->unit_price, 2) ?></td>
<td><strong>R<?= number_format((float) $item->line_total, 2) ?></strong></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>

<div class="admin-order-card">
<h2>Generated Tickets</h2>

<?php if (empty($tickets)): ?>
<p class="muted">No tickets generated yet.</p>
<?php else: ?>
<div class="admin-ticket-list">
<?php foreach ($tickets as $ticket): ?>
<div class="admin-ticket-row">
<div>
<span class="status-pill status-<?= htmlspecialchars($ticket->status) ?>">
<?= htmlspecialchars(ucfirst($ticket->status)) ?>
</span>

<h3><?= htmlspecialchars($ticket->ticket_number) ?></h3>

<p>
<?= htmlspecialchars($ticket->ticket_name) ?>
· <?= htmlspecialchars($ticket->buyer_name) ?>
</p>

<?php if (!empty($ticket->scanned_at)): ?>
<small>
Scanned at <?= htmlspecialchars($ticket->scanned_at) ?>
<?= !empty($ticket->scanned_by_name) ? 'by ' . htmlspecialchars($ticket->scanned_by_name) : '' ?>
</small>
<?php endif; ?>
</div>

<div class="admin-ticket-actions">
<a
href="<?= $appUrl ?>/ticket?token=<?= urlencode($ticket->qr_token) ?>"
class="btn secondary-dark"
target="_blank"
>
Open
</a>

<a
href="<?= $appUrl ?>/ticket/download?token=<?= urlencode($ticket->qr_token) ?>"
class="btn gold"
>
PDF
</a>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<div class="admin-order-card">
<h2>PayFast Logs</h2>

<?php if (empty($logs)): ?>
<p class="muted">No PayFast logs found.</p>
<?php else: ?>
<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
<th>Status</th>
<th>Payment</th>
<th>Message</th>
<th>Date</th>
</tr>
</thead>

<tbody>
<?php foreach ($logs as $log): ?>
<tr>
<td>
<span class="status-pill">
<?= htmlspecialchars($log->verification_status) ?>
</span>
</td>

<td>
<?= htmlspecialchars($log->payment_status ?: '—') ?>
<br>
<small><?= htmlspecialchars($log->pf_payment_id ?: '—') ?></small>
</td>

<td><?= htmlspecialchars($log->message ?: '—') ?></td>

<td><?= htmlspecialchars($log->created_at) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
</div>

<aside class="admin-order-side-column">
<div class="admin-order-card sticky-card">
<h2>Actions</h2>

<form method="POST" action="<?= $appUrl ?>/admin/orders/resend-tickets">
<input type="hidden" name="order_id" value="<?= (int) $order->id ?>">

<button
type="submit"
class="btn primary full-width"
<?= $order->payment_status !== 'paid' ? 'disabled' : '' ?>
>
Resend Ticket Email
</button>
</form>

<form method="POST" action="<?= $appUrl ?>/admin/orders/resend-password">
<input type="hidden" name="order_id" value="<?= (int) $order->id ?>">

<button
type="submit"
class="btn gold full-width"
<?= empty($order->user_id) ? 'disabled' : '' ?>
>
Resend Password Setup
</button>
</form>

<a
href="<?= $appUrl ?>/event?slug=<?= urlencode($order->event_slug) ?>&eventID=<?= (int) $order->event_id ?>"
class="btn secondary-dark full-width"
target="_blank"
>
View Public Event
</a>

<hr>

<div class="admin-order-mini-info">
<span>Linked Account</span>

<?php if (!empty($order->user_id)): ?>
<strong><?= htmlspecialchars($order->account_name ?: 'Customer') ?></strong>
<small><?= htmlspecialchars($order->account_email ?: $order->buyer_email) ?></small>
<?php else: ?>
<strong>Not linked</strong>
<small>Order kept for records.</small>
<?php endif; ?>
</div>

<div class="admin-order-mini-info">
<span>Tickets Email Sent</span>
<strong><?= htmlspecialchars($order->tickets_emailed_at ?: 'Not yet / unknown') ?></strong>
</div>

<?php if (!empty($order->tickets_email_error)): ?>
<div class="admin-order-email-error">
<?= htmlspecialchars($order->tickets_email_error) ?>
</div>
<?php endif; ?>
</div>
</aside>
</div>
</div>
</section>
