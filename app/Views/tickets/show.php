<section class="checkout-page">
<div class="form-panel ticket-view-panel">
<span class="badge status-<?= htmlspecialchars($ticket->status) ?>">
<?= htmlspecialchars(ucfirst($ticket->status)) ?> Ticket
</span>

<h1><?= htmlspecialchars($ticket->event_title) ?></h1>

<p class="muted">
<?= date('d M Y', strtotime($ticket->event_date)) ?>
<?php if (!empty($ticket->start_time)): ?>
· <?= htmlspecialchars($ticket->start_time) ?>
<?php endif; ?>
</p>

<div class="ticket-detail-box">
<div>
<span>Ticket Type</span>
<strong><?= htmlspecialchars($ticket->ticket_name) ?></strong>
</div>

<div>
<span>Ticket Number</span>
<strong><?= htmlspecialchars($ticket->ticket_number) ?></strong>
</div>

<div>
<span>Buyer</span>
<strong><?= htmlspecialchars($ticket->buyer_name) ?></strong>
</div>

<div>
<span>Venue</span>
<strong>
<?= htmlspecialchars($ticket->venue_name) ?>,
<?= htmlspecialchars($ticket->city) ?>
</strong>
</div>
</div>

<div class="ticket-qr-box">
<img
src="<?= htmlspecialchars($qrDataUri) ?>"
alt="QR Code for <?= htmlspecialchars($ticket->ticket_number) ?>"
>

<p class="muted small-note">
Scan this QR code at the gate.
</p>
</div>

<div class="ticket-online-link">
<span>Online Ticket Link</span>
<a href="<?= htmlspecialchars($ticketUrl) ?>">
<?= htmlspecialchars($ticketUrl) ?>
</a>
</div>

<p class="muted small-note">
Keep this ticket safe. Each ticket can only be scanned once at the gate.
</p>

<a href="<?= htmlspecialchars($downloadUrl) ?>" class="btn gold">
Download PDF Ticket
</a>

<a href="<?= $appUrl ?>/my-tickets" class="btn primary">
Back to My Tickets
</a>
</div>
</section>
