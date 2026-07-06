<section class="admin-ticket-detail-page">
<div class="admin-ticket-detail-shell">
<div class="admin-ticket-detail-hero">
<div>
<span class="badge">Ticket</span>

<h1><?= htmlspecialchars($ticket->ticket_number) ?></h1>

<p>
Manage this ticket status, QR access, PDF, and scan record.
</p>
</div>

<div class="admin-tickets-actions">
<a href="<?= $appUrl ?>/admin/tickets" class="btn secondary-dark">
Back to Tickets
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

<div class="admin-ticket-detail-grid">
<div class="admin-ticket-main-column">
<div class="admin-ticket-card">
<div class="admin-ticket-card-header">
<h2>Ticket Summary</h2>

<span class="status-pill status-<?= htmlspecialchars($ticket->status) ?>">
<?= htmlspecialchars(ucfirst($ticket->status)) ?>
</span>
</div>

<div class="admin-ticket-info-grid">
<div>
<span>Ticket Type</span>
<strong><?= htmlspecialchars($ticket->ticket_name) ?></strong>
</div>

<div>
<span>Buyer</span>
<strong><?= htmlspecialchars($ticket->buyer_name) ?></strong>
</div>

<div>
<span>Email</span>
<strong><?= htmlspecialchars($ticket->buyer_email) ?></strong>
</div>

<div>
<span>Phone</span>
<strong><?= htmlspecialchars($ticket->buyer_phone ?: '—') ?></strong>
</div>

<div>
<span>Order</span>
<strong><?= htmlspecialchars($ticket->order_number) ?></strong>
</div>

<div>
<span>Payment</span>
<strong><?= htmlspecialchars(ucfirst($ticket->payment_status)) ?></strong>
</div>

<div>
<span>Created</span>
<strong><?= htmlspecialchars($ticket->created_at) ?></strong>
</div>

<div>
<span>PDF</span>
<strong><?= !empty($ticket->pdf_path) ? 'Generated' : 'Not generated' ?></strong>
</div>
</div>
</div>

<div class="admin-ticket-card">
<h2>Event</h2>

<div class="admin-ticket-info-grid">
<div>
<span>Event</span>
<strong><?= htmlspecialchars($ticket->event_title) ?></strong>
</div>

<div>
<span>Date</span>
<strong><?= htmlspecialchars(date('d M Y', strtotime($ticket->event_date))) ?></strong>
</div>

<div>
<span>Time</span>
<strong>
<?= htmlspecialchars($ticket->start_time) ?>
<?php if (!empty($ticket->end_time)): ?>
- <?= htmlspecialchars($ticket->end_time) ?>
<?php endif; ?>
</strong>
</div>

<div>
<span>Venue</span>
<strong><?= htmlspecialchars($ticket->venue_name) ?></strong>
</div>

<div class="wide">
<span>Address</span>
<strong><?= htmlspecialchars($ticket->venue_address ?: $ticket->city) ?></strong>
</div>
</div>
</div>

<div class="admin-ticket-card">
<h2>Scan Record</h2>

<div class="admin-ticket-info-grid">
<div>
<span>Scanned At</span>
<strong><?= htmlspecialchars($ticket->scanned_at ?: 'Not scanned') ?></strong>
</div>

<div>
<span>Scanned By</span>
<strong><?= htmlspecialchars($ticket->scanned_by_name ?: '—') ?></strong>
</div>
</div>
</div>

<div class="admin-ticket-card">
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

<aside class="admin-ticket-side-column">
<div class="admin-ticket-card sticky-card">
<h2>Actions</h2>

<a
href="<?= $appUrl ?>/ticket?token=<?= urlencode($ticket->qr_token) ?>"
class="btn secondary-dark full-width"
target="_blank"
>
Open Ticket
</a>

<a
href="<?= $appUrl ?>/ticket/download?token=<?= urlencode($ticket->qr_token) ?>"
class="btn gold full-width"
>
Download PDF
</a>

<a
href="<?= $appUrl ?>/admin/orders/view?id=<?= (int) $ticket->order_id ?>"
class="btn secondary-dark full-width"
>
View Order
</a>

<a
href="<?= $appUrl ?>/event?slug=<?= urlencode($ticket->event_slug) ?>&eventID=<?= (int) $ticket->event_id ?>"
class="btn secondary-dark full-width"
target="_blank"
>
View Public Event
</a>

<hr>

<?php if ($ticket->status !== 'valid'): ?>
<form method="POST" action="<?= $appUrl ?>/admin/tickets/status">
<input type="hidden" name="ticket_id" value="<?= (int) $ticket->id ?>">
<input type="hidden" name="action" value="mark_valid">

<button type="submit" class="btn primary full-width">
Reactivate / Reset Scan
</button>
</form>
<?php endif; ?>

<?php if ($ticket->status !== 'used'): ?>
<form method="POST" action="<?= $appUrl ?>/admin/tickets/status">
<input type="hidden" name="ticket_id" value="<?= (int) $ticket->id ?>">
<input type="hidden" name="action" value="mark_used">

<button type="submit" class="btn gold full-width">
Mark As Used
</button>
</form>
<?php endif; ?>

<?php if ($ticket->status !== 'cancelled'): ?>
<form method="POST" action="<?= $appUrl ?>/admin/tickets/status">
<input type="hidden" name="ticket_id" value="<?= (int) $ticket->id ?>">
<input type="hidden" name="action" value="cancel">

<button type="submit" class="btn danger full-width">
Cancel Ticket
</button>
</form>
<?php endif; ?>

<?php if ($ticket->status !== 'transferred'): ?>
<form method="POST" action="<?= $appUrl ?>/admin/tickets/status">
<input type="hidden" name="ticket_id" value="<?= (int) $ticket->id ?>">
<input type="hidden" name="action" value="mark_transferred">

<button type="submit" class="btn secondary-dark full-width">
Mark Transferred
</button>
</form>
<?php endif; ?>

<hr>

<div class="admin-ticket-mini-info">
<span>Linked Account</span>
<strong><?= htmlspecialchars($ticket->account_name ?: 'Customer') ?></strong>
<small><?= htmlspecialchars($ticket->account_email ?: $ticket->buyer_email) ?></small>
</div>

<div class="admin-ticket-mini-info">
<span>QR Token</span>
<strong class="token-text"><?= htmlspecialchars($ticket->qr_token) ?></strong>
</div>
</div>
</aside>
</div>
</div>
</section>
