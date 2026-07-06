<section class="payment-return-page">
<div class="payment-return-card">
<?php if ($order && $order->payment_status === 'paid'): ?>
<span class="badge status-valid">Payment Confirmed</span>

<h1>Payment Successful</h1>

<p class="muted">
Your Royal Splash payment has been confirmed. Your tickets are ready.
</p>

<div class="payment-success-actions">
<a href="<?= $appUrl ?>/my-tickets" class="btn primary">
View My Tickets
</a>

<a href="<?= $appUrl ?>/events" class="btn secondary-dark">
Back to Events
</a>
</div>

<?php else: ?>
<span class="badge payment-processing-badge">Payment Processing</span>

<h1>Confirming Payment</h1>

<p class="muted" id="paymentStatusText">
Please wait while we confirm your payment with PayFast. This usually takes a few seconds.
</p>

<div class="payment-loader-wrap">
<div class="payment-loader"></div>
</div>

<div class="payment-status-box">
<span>Order</span>
<strong>
<?= $order ? htmlspecialchars($order->order_number) : 'Pending' ?>
</strong>
</div>

<div class="payment-return-actions">
<a href="<?= $appUrl ?>/events" class="btn secondary-dark">
Back to Events
</a>
</div>
<?php endif; ?>
</div>
</section>

<?php if (!$order || $order->payment_status !== 'paid'): ?>
<script>
const paymentStatusUrl = "<?= $appUrl ?>/payment/status";
const statusText = document.getElementById('paymentStatusText');

let checkCount = 0;
const maxChecks = 40;

async function checkPaymentStatus() {
    checkCount++;

    try {
        const response = await fetch(paymentStatusUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
    });

const data = await response.json();

if (data.status === 'success' && data.redirect_url) {
    statusText.textContent = 'Payment confirmed. Redirecting you to your tickets...';

    setTimeout(function () {
            window.location.href = data.redirect_url;
    }, 1200);

return;
}

if (data.status === 'cancelled' && data.redirect_url) {
    statusText.textContent = 'Payment was cancelled. Redirecting...';

    setTimeout(function () {
            window.location.href = data.redirect_url;
    }, 1200);

return;
}

if (data.status === 'failed') {
    statusText.textContent = 'Payment failed. Please contact support if money was deducted.';
    return;
}

if (checkCount >= maxChecks) {
    statusText.textContent = 'Payment is taking longer than expected. Your ticket email will still be sent once PayFast confirms payment.';
    return;
}

statusText.textContent = 'Still confirming payment with PayFast...';

setTimeout(checkPaymentStatus, 3000);
} catch (error) {
if (checkCount >= maxChecks) {
    statusText.textContent = 'We could not confirm payment right now. Please check your email or My Tickets shortly.';
    return;
}

statusText.textContent = 'Checking payment status again...';
setTimeout(checkPaymentStatus, 3000);
}
}

setTimeout(checkPaymentStatus, 1500);
</script>
<?php endif; ?>
