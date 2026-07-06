<section class="checkout-page">
<div class="admin-header">
<div>
<span class="badge">Pending Order</span>
<h1>Order Created</h1>
<p>Your order is ready. Please continue make payment</p>
</div>

<a href="<?= $appUrl ?>/events" class="btn secondary-dark">
Back to Events
</a>
</div>

<div class="checkout-layout">
<div class="form-panel">
<h2><?= htmlspecialchars($order->event_title) ?></h2>

<div class="checkout-event-meta">
<div>
<strong>Order Number</strong>
<span><?= htmlspecialchars($order->order_number) ?></span>
</div>

<div>
<strong>Status</strong>
<span><?= htmlspecialchars(ucfirst($order->payment_status)) ?></span>
</div>

<div>
<strong>Buyer</strong>
<span>
<?= htmlspecialchars($order->buyer_name) ?><br>
<?= htmlspecialchars($order->buyer_email) ?>
</span>
</div>

<div>
<strong>Event Date</strong>
<span><?= htmlspecialchars(date('D, d M Y', strtotime($order->event_date))) ?></span>
</div>

<div>
<strong>Venue</strong>
<span>
<?= htmlspecialchars($order->venue_name) ?>,
<?= htmlspecialchars($order->venue_address ?: $order->city) ?>
</span>
</div>
</div>

<div class="checkout-ticket-list">
<?php foreach ($items as $item): ?>
<article class="checkout-ticket-row">
<div>
<h3><?= htmlspecialchars($item->ticket_name) ?></h3>
<span>
<?= (int) $item->quantity ?> × R<?= number_format((float) $item->unit_price, 2) ?>
</span>
</div>

<strong>R<?= number_format((float) $item->line_total, 2) ?></strong>
</article>
<?php endforeach; ?>
</div>
</div>

<aside class="form-panel checkout-side">
<h2>Payment Summary</h2>

<div class="summary-line">
<span>Total tickets</span>
<strong><?= (int) $order->total_qty ?></strong>
</div>

<div class="summary-total-line">
<span>Total</span>
<strong>R<?= number_format((float) $order->total_amount, 2) ?></strong>
</div>

<?php if (!empty($_SESSION['payment_error'])): ?>
<div class="alert error">
<?= htmlspecialchars($_SESSION['payment_error']) ?>
</div>
<?php unset($_SESSION['payment_error']); ?>
<?php endif; ?>

<form method="POST" action="<?= $appUrl ?>/checkout/payfast">
<input type="hidden" name="order_id" value="<?= (int) $order->id ?>">

<button type="submit" class="btn primary full-width">
Pay Now
</button>
</form>
</aside>
</div>
</section>
