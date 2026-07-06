<section class="admin-scanners-page">
<div class="admin-scanners-shell">
<div class="admin-scanners-hero">
<div>
<span class="badge">Admin</span>

<h1>Scanner Users</h1>

<p>
Create and manage scanner accounts for gate staff and ticket validation teams.
</p>
</div>

<div class="admin-scanners-actions">
<a href="<?= $appUrl ?>/scanner" class="btn gold">
Open Scanner
</a>

<a href="<?= $appUrl ?>/admin/reports/scans" class="btn secondary-dark">
Scan Reports
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

<div class="admin-scanners-grid">
<div class="admin-scanner-form-card">
<h2>Create Scanner</h2>

<form method="POST" action="<?= $appUrl ?>/admin/scanners/create">
<div class="form-group">
<label>Full Name *</label>
<input type="text" name="full_name" required>
</div>

<div class="form-group">
<label>Email *</label>
<input type="email" name="email" required>
</div>

<div class="form-group">
<label>Phone</label>
<input type="text" name="phone">
</div>

<div class="form-group">
<label>Status</label>

<select name="status">
<option value="active">Active</option>
<option value="disabled">Disabled</option>
</select>
</div>

<div class="form-group">
<label>Password *</label>

<input
type="password"
name="password"
minlength="8"
placeholder="Minimum 8 characters"
required
>
</div>

<div class="form-group">
<label>Confirm Password *</label>

<input
type="password"
name="confirm_password"
minlength="8"
placeholder="Repeat password"
required
>
</div>

<button type="submit" class="btn primary full-width">
Create Scanner User
</button>
</form>
</div>

<div class="admin-scanner-list-card">
<div class="admin-scanner-list-header">
<div>
<h2>Scanner Accounts</h2>
<p>Manage scanner access and monitor scan activity.</p>
</div>
</div>

<?php if (empty($scanners)): ?>
<div class="empty-card compact">
<h3>No scanner users yet</h3>

<p>
Create your first scanner user using the form.
</p>
</div>
<?php else: ?>
<div class="admin-table-wrap">
<table class="admin-table admin-scanners-table">
<thead>
<tr>
<th>Scanner</th>
<th>Status</th>
<th>Scans</th>
<th>Last Scan</th>
<th></th>
</tr>
</thead>

<tbody>
<?php foreach ($scanners as $scanner): ?>
<tr>
<td>
<strong><?= htmlspecialchars($scanner->full_name) ?></strong>
<br>
<small><?= htmlspecialchars($scanner->email) ?></small>
</td>

<td>
<span class="status-pill status-<?= htmlspecialchars($scanner->status) ?>">
<?= htmlspecialchars(ucfirst($scanner->status)) ?>
</span>
</td>

<td>
<strong><?= (int) $scanner->scan_count ?></strong>
</td>

<td>
<?= !empty($scanner->last_scan_at)
? htmlspecialchars($scanner->last_scan_at)
: '<span class="muted">No scans yet</span>'
?>
</td>

<td>
<a
href="<?= $appUrl ?>/admin/scanners/edit?id=<?= (int) $scanner->id ?>"
class="btn secondary-dark small-btn"
>
Edit
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
</div>
</section>
