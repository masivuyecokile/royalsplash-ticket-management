<section class="admin-page">
<div class="admin-header">
<div>
<span class="badge">Create Event</span>
<h1>New Royal Splash Event</h1>
<p>Add the event details that will later be shown on the public ticketing page.</p>
</div>

<a href="<?= $appUrl ?>/admin/events" class="btn secondary-dark">Back to Events</a>
</div>

<div class="admin-nav">
<a href="<?= $appUrl ?>/admin">Dashboard</a>
<a href="<?= $appUrl ?>/admin/events" class="active">Events</a>
<a href="#">Orders</a>
<a href="#">Tickets</a>
<a href="#">Scanner Users</a>
<a href="#">Reports</a>
</div>

<div class="form-panel">
<?php if (!empty($error)): ?>
<div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="<?= $appUrl ?>/admin/events/create" class="admin-form" enctype="multipart/form-data">
<div class="form-grid">
<label>
Event Title *
<input type="text" name="title" value="Royal Splash" required>
</label>

<label>
Subtitle
<input type="text" name="subtitle" value="Hosted by Ubambiswano">
</label>

<label>
Status
<select name="status">
<option value="draft">Draft</option>
<option value="published">Published</option>
<option value="on_sale">On Sale</option>
<option value="sold_out">Sold Out</option>
<option value="cancelled">Cancelled</option>
</select>
</label>

<label>
Event Date *
<input type="date" name="event_date" value="2026-10-31" required>
</label>

<label>
Start Time *
<input type="text" name="start_time" value="08:00" required>
</label>

<label>
End Time
<input type="text" name="end_time" value="Till late">
</label>

<label>
Venue Name *
<input type="text" name="venue_name" value="Bagzane Park" required>
</label>

<label>
Venue Address
<input type="text" name="venue_address" value="24/25 Section">
</label>

<label>
City *
<input type="text" name="city" value="Cape Town" required>
</label>

<label>
Main Artist
<input type="text" name="main_artist" value="KaeWax">
</label>

<label>
Total Capacity
<input type="number" name="total_capacity" value="780" min="0">
</label>

<label>
Feature Image
<input type="file" name="feature_image" accept="image/jpeg,image/png,image/webp">
<small>Upload JPG, PNG or WEBP. Recommended size: 1200px × 700px.</small>
</label>

<label class="full">
Description
<textarea name="description" rows="5">A premium social event with music, performances, food, royal energy and unforgettable summer vibes.</textarea>
</label>

<label class="full">
VIP Details
<textarea name="vip_details" rows="5">VIP includes fast-track gate entry, access to the VIP area, premium viewing section, welcome drink, VIP toilets and priority support.</textarea>
</label>
</div>

<button type="submit" class="btn primary">Save Event</button>
</form>
</div>
</section>
