<section class="dashboard-wrap">
<div class="dashboard-card">
<span class="badge">Customer Account</span>
<h1>My Tickets</h1>

<p>
Welcome, <strong><?= htmlspecialchars($user->full_name) ?></strong>.
This is where your purchased Royal Splash tickets will appear.
</p>

<div class="dashboard-grid">
<div class="mini-card">
<strong>0</strong>
<span>Total Tickets</span>
</div>

<div class="mini-card">
<strong>0</strong>
<span>Upcoming Events</span>
</div>

<div class="mini-card">
<strong>0</strong>
<span>Transfers</span>
</div>
</div>

<a href="<?= $appUrl ?>/logout" class="btn secondary-dark">Logout</a>
</div>
</section>
