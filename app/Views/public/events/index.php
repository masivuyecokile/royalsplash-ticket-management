<section class="public-hero">
<div>
<span class="badge">Official Online Ticketing</span>
<h1>Book Royal Splash Tickets Online</h1>
<p>
Browse upcoming events, choose your ticket type and get ready for secure QR ticket access.
</p>
</div>
</section>

<section class="events-section">
<div class="section-title">
<div>
<h2>Upcoming Events</h2>
<p>Choose an event and view available ticket options.</p>
</div>
</div>

<?php if (empty($events)): ?>
<div class="empty-card">
<h3>No events available yet</h3>
<p>Published Royal Splash events will appear here once ticket sales open.</p>
</div>
<?php else: ?>
<div class="public-event-grid">
<?php foreach ($events as $event): ?>
<?php
$image = !empty($event->feature_image)
? $appUrl . '/' . $event->feature_image
: $appUrl . '/assets/images/royal-splash-logo.png';

$startingPrice = $event->starting_price !== null
? 'R' . number_format((float) $event->starting_price, 2)
: 'Coming Soon';

$sold = (int) ($event->tickets_sold ?? 0);
$capacity = (int) ($event->ticket_capacity ?? 0);
?>

<article class="public-event-card">
<a href="<?= $appUrl ?>/event?slug=<?= urlencode($event->slug) ?>" class="public-event-image">
<img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($event->title) ?>">

<div class="event-date-badge">
<strong><?= htmlspecialchars(date('d', strtotime($event->event_date))) ?></strong>
<span><?= htmlspecialchars(date('M', strtotime($event->event_date))) ?></span>
</div>

<div class="public-status">
<?= htmlspecialchars(str_replace('_', ' ', ucfirst($event->status))) ?>
</div>
</a>

<div class="public-event-body">
<div class="public-event-meta">
<span><?= htmlspecialchars(date('D, d M Y', strtotime($event->event_date))) ?></span>
<span><?= htmlspecialchars($event->start_time) ?></span>
</div>

<h3>
<a href="<?= $appUrl ?>/event?slug=<?= urlencode($event->slug) ?>">
<?= htmlspecialchars($event->title) ?>
</a>
</h3>

<p>
<?= htmlspecialchars($event->venue_name) ?>,
<?= htmlspecialchars($event->city) ?>
</p>

<?php if (!empty($event->main_artist)): ?>
<p>Artist: <?= htmlspecialchars($event->main_artist) ?></p>
<?php endif; ?>

<div class="public-event-footer">
<div>
<span>From</span>
<strong><?= $startingPrice ?></strong>
</div>

<a href="<?= $appUrl ?>/event?slug=<?= urlencode($event->slug) ?>" class="btn primary">
View Tickets
</a>
</div>

<?php if ($capacity > 0): ?>
<div class="availability-text">
<?= $sold ?> / <?= $capacity ?> tickets sold
</div>
<?php endif; ?>
</div>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>
