<section class="admin-report-page">
<div class="admin-report-shell">
<div class="admin-report-hero">
<div>
<span class="badge">Admin Reports</span>

<h1>Scan Reports</h1>

<p>
Track attendance, ticket usage, scan times, and scanner activity.
</p>
</div>

<div class="admin-report-actions">
<a href="<?= $appUrl ?>/scanner" class="btn gold">
Open Scanner
</a>

<a href="<?= $appUrl ?>/admin/reports/scans/export?<?= htmlspecialchars(http_build_query($_GET)) ?>" class="btn secondary-dark">
Export CSV
</a>
</div>
</div>

<form method="GET" action="<?= $appUrl ?>/admin/reports/scans" class="report-filter-panel">
<div class="report-filter-grid">
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
<option value="used" <?= $filters['status'] === 'used' ? 'selected' : '' ?>>Scanned / Used</option>
<option value="valid" <?= $filters['status'] === 'valid' ? 'selected' : '' ?>>Valid / Not Scanned</option>
<option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
<option value="transferred" <?= $filters['status'] === 'transferred' ? 'selected' : '' ?>>Transferred</option>
</select>
</div>

<div class="form-group">
<label>Scanned From</label>

<input
type="date"
name="date_from"
value="<?= htmlspecialchars($filters['date_from']) ?>"
>
</div>

<div class="form-group">
<label>Scanned To</label>

<input
type="date"
name="date_to"
value="<?= htmlspecialchars($filters['date_to']) ?>"
>
</div>
</div>

<div class="report-filter-actions">
<button type="submit" class="btn primary">
Apply Filters
</button>

<a href="<?= $appUrl ?>/admin/reports/scans" class="btn secondary-dark">
Clear
</a>
</div>
</form>

<div class="report-stat-grid">
<div class="report-stat-card">
<span>Total Tickets</span>
<strong><?= (int) $summary->total_tickets ?></strong>
</div>

<div class="report-stat-card success">
<span>Scanned</span>
<strong><?= (int) $summary->scanned_tickets ?></strong>
</div>

<div class="report-stat-card warning">
<span>Not Scanned</span>
<strong><?= (int) $summary->unscanned_tickets ?></strong>
</div>

<div class="report-stat-card gold">
<span>Scan Rate</span>
<strong><?= (int) $summary->scan_rate ?>%</strong>
</div>
</div>

<div class="scan-report-table-card">
<div class="scan-report-table-header">
<div>
<h2>Ticket Scan Records</h2>

<p>
Showing latest matching records.
</p>
</div>
</div>

<?php if (empty($tickets)): ?>
<div class="empty-card compact">
<h3>No records found</h3>

<p>
No tickets match the selected filters.
</p>
</div>
<?php else: ?>
<div class="admin-table-wrap">
<table class="admin-table scan-report-table">
<thead>
<tr>
<th>Ticket</th>
<th>Buyer</th>
<th>Event</th>
<th>Status</th>
<th>Scanned</th>
<th>Scanner</th>
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
<span class="status-pill <?= htmlspecialchars($statusClass) ?>">
<?= htmlspecialchars(ucfirst($ticket->status)) ?>
</span>
</td>

<td>
<?php if (!empty($ticket->scanned_at)): ?>
<?= htmlspecialchars($ticket->scanned_at) ?>
<?php else: ?>
<span class="muted">Not scanned</span>
<?php endif; ?>
</td>

<td>
<?= !empty($ticket->scanned_by_name)
? htmlspecialchars($ticket->scanned_by_name)
: '<span class="muted">—</span>'
?>
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
