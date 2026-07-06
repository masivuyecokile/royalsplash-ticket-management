<section class="admin-scanner-edit-page">
<div class="admin-scanner-edit-shell">
<div class="admin-scanners-hero">
<div>
<span class="badge">Scanner User</span>

<h1><?= htmlspecialchars($scanner->full_name) ?></h1>

<p>
Edit scanner account details, status, and manually reset their password.
</p>
</div>

<div class="admin-scanners-actions">
<a href="<?= $appUrl ?>/admin/scanners" class="btn secondary-dark">
Back to Scanners
</a>

<a href="<?= $appUrl ?>/scanner" class="btn gold">
Open Scanner
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

<div class="admin-scanner-edit-grid">
<div class="admin-scanner-edit-main">
<form method="POST" action="<?= $appUrl ?>/admin/scanners/edit" class="admin-scanner-card">
<input type="hidden" name="scanner_id" value="<?= (int) $scanner->id ?>">

<h2>Account Details</h2>

<div class="admin-scanner-form-grid">
<div class="form-group">
<label>Full Name *</label>

<input
type="text"
name="full_name"
value="<?= htmlspecialchars($scanner->full_name) ?>"
required
>
</div>

<div class="form-group">
<label>Email *</label>

<input
type="email"
name="email"
value="<?= htmlspecialchars($scanner->email) ?>"
required
>
</div>

<div class="form-group">
<label>Phone</label>

<input
type="text"
name="phone"
value="<?= htmlspecialchars($scanner->phone ?? '') ?>"
>
</div>

<div class="form-group">
<label>Status</label>

<select name="status">
<option value="active" <?= $scanner->status === 'active' ? 'selected' : '' ?>>Active</option>
<option value="disabled" <?= $scanner->status === 'disabled' ? 'selected' : '' ?>>Disabled</option>
</select>
</div>
</div>

<hr>

<h2>Manual Password Reset</h2>

<p class="muted">
Leave these fields empty if you do not want to change this scanner's password.
</p>

<div class="admin-scanner-form-grid">
<div class="form-group">
<label>New Password</label>

<input
type="password"
name="password"
minlength="8"
placeholder="Minimum 8 characters"
>
</div>

<div class="form-group">
<label>Confirm New Password</label>

<input
type="password"
name="confirm_password"
minlength="8"
placeholder="Repeat new password"
>
</div>
</div>

<button type="submit" class="btn primary full-width">
Save Scanner
</button>
</form>
</div>

<aside class="admin-scanner-edit-side">
<div class="admin-scanner-card sticky-card">
<h2>Scanner Stats</h2>

<div class="admin-scanner-stats-box">
<div>
<span>Total Scans</span>
<strong><?= (int) $scanner->scan_count ?></strong>
</div>

<div>
<span>Last Scan</span>
<strong>
<?= !empty($scanner->last_scan_at)
? htmlspecialchars($scanner->last_scan_at)
: 'No scans yet'
?>
</strong>
</div>

<div>
<span>Status</span>
<strong><?= htmlspecialchars(ucfirst($scanner->status)) ?></strong>
</div>

<div>
<span>Created</span>
<strong><?= htmlspecialchars($scanner->created_at) ?></strong>
</div>
</div>

<hr>

<?php if ($scanner->status === 'active'): ?>
<form method="POST" action="<?= $appUrl ?>/admin/scanners/status">
<input type="hidden" name="scanner_id" value="<?= (int) $scanner->id ?>">
<input type="hidden" name="status" value="disabled">

<button type="submit" class="btn danger full-width">
Disable Scanner
</button>
</form>
<?php else: ?>
<form method="POST" action="<?= $appUrl ?>/admin/scanners/status">
<input type="hidden" name="scanner_id" value="<?= (int) $scanner->id ?>">
<input type="hidden" name="status" value="active">

<button type="submit" class="btn primary full-width">
Enable Scanner
</button>
</form>
<?php endif; ?>

<form method="POST" action="<?= $appUrl ?>/admin/scanners/resend-password">
<input type="hidden" name="scanner_id" value="<?= (int) $scanner->id ?>">

<button
type="submit"
class="btn gold full-width"
<?= $scanner->status !== 'active' ? 'disabled' : '' ?>
>
Send Password Setup Link
</button>
</form>

<a href="<?= $appUrl ?>/admin/reports/scans" class="btn secondary-dark full-width">
View Scan Reports
</a>
</div>
</aside>
</div>
</div>
</section>
