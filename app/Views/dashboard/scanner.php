<section class="dashboard-wrap">
<div class="dashboard-card">
<span class="badge">Scanner User</span>
<h1>Gate Scanner</h1>

<p>
Welcome, <strong><?= htmlspecialchars($user->full_name) ?></strong>.
This area will allow scanner users to scan QR tickets using a phone camera.
</p>

<div class="scanner-preview">
<strong>QR Scanner Coming Next</strong>
<span>Camera scanning will be added in the scanner phase.</span>
</div>

<a href="<?= $appUrl ?>/logout" class="btn secondary-dark">Logout</a>
</div>
</section>
