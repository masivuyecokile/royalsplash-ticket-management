<section class="checkout-page">
<div class="form-panel payfast-redirect-panel">
<span class="badge">PayFast <?= htmlspecialchars(ucfirst($payfast->mode)) ?></span>

<h1>Redirecting to PayFast</h1>

<p class="muted">
You are being redirected to PayFast to complete payment for order
<strong><?= htmlspecialchars($order->order_number) ?></strong>.
</p>

<div class="summary-total-line">
<span>Total</span>
<strong>R<?= number_format((float) $order->total_amount, 2) ?></strong>
</div>

<form method="POST" action="<?= htmlspecialchars($payfast->process_url) ?>" id="payfastRedirectForm">
<?php foreach ($data as $key => $value): ?>
<?php if ($value !== null && $value !== ''): ?>
<input
type="hidden"
name="<?= htmlspecialchars($key) ?>"
value="<?= htmlspecialchars((string) $value) ?>"
>
<?php endif; ?>
<?php endforeach; ?>

<button type="submit" class="btn primary full-width">
Continue to PayFast
</button>
</form>

<p class="muted small-note">
Mode: <?= htmlspecialchars($payfast->mode) ?>.
Use sandbox while testing. Switch to live only when ready.
</p>
</div>
</section>

<script>
setTimeout(function () {
        document.getElementById('payfastRedirectForm').submit();
}, 1200);
</script>
