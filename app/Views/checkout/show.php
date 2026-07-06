<section class="checkout-page">
<div class="admin-header">
<div>
<span class="badge">Checkout</span>
<h1>Review Your Tickets</h1>
<p>Confirm your selected Royal Splash tickets before moving to payment.</p>
</div>

<a
href="<?= $appUrl ?>/event?slug=<?= urlencode($checkout->event_slug) ?>&eventID=<?= (int) $checkout->event_id ?>"
class="btn secondary-dark"
>
Change Tickets
</a>
</div>

<div class="checkout-layout">
<div class="form-panel">
<h2><?= htmlspecialchars($checkout->event_title) ?></h2>

<div class="checkout-event-meta">
<div>
<strong>Date</strong>
<span><?= htmlspecialchars(date('D, d M Y', strtotime($checkout->event_date))) ?></span>
</div>

<div>
<strong>Time</strong>
<span><?= htmlspecialchars($checkout->start_time) ?></span>
</div>

<div>
<strong>Venue</strong>
<span>
<?= htmlspecialchars($checkout->venue_name) ?>,
<?= htmlspecialchars($checkout->venue_address ?: $checkout->city) ?>
</span>
</div>
</div>

<div class="checkout-ticket-list">
<?php foreach ($checkout->items as $item): ?>
<article class="checkout-ticket-row">
<div>
<h3><?= htmlspecialchars($item->name) ?></h3>

<?php if (!empty($item->description)): ?>
<p><?= htmlspecialchars($item->description) ?></p>
<?php endif; ?>

<span>
<?= (int) $item->qty ?> × R<?= number_format((float) $item->unit_price, 2) ?>
</span>
</div>

<strong>R<?= number_format((float) $item->line_total, 2) ?></strong>
</article>
<?php endforeach; ?>
</div>
</div>

<aside class="form-panel checkout-side">
<h2>Order Summary</h2>

<div class="summary-line">
<span>Total tickets</span>
<strong><?= (int) $checkout->total_qty ?></strong>
</div>

<div class="summary-line">
<span>Transaction limit</span>
<strong><?= (int) $checkout->total_qty ?>/4</strong>
</div>

<div class="summary-total-line">
<span>Total</span>
<strong>R<?= number_format((float) $checkout->total_amount, 2) ?></strong>
</div>

<?php if (!empty($_SESSION['checkout_form_error'])): ?>
<div class="alert error">
<?= htmlspecialchars($_SESSION['checkout_form_error']) ?>
</div>
<?php unset($_SESSION['checkout_form_error']); ?>
<?php endif; ?>

<form
method="POST"
action="<?= $appUrl ?>/checkout/create-order"
class="checkout-buyer-form"
id="checkoutForm"
>
<input
type="hidden"
name="event_id"
id="checkoutEventId"
value="<?= (int) $checkout->event_id ?>"
>

<input
type="hidden"
name="confirm_duplicate_purchase"
id="confirmDuplicatePurchase"
value="0"
>

<label>
Full Name *
<input
type="text"
name="buyer_name"
value="<?= htmlspecialchars($_SESSION['user']->full_name ?? '') ?>"
required
>
</label>

<label>
Email Address *
<input
type="email"
name="buyer_email"
value="<?= htmlspecialchars($_SESSION['user']->email ?? '') ?>"
required
>
</label>

<label>
Phone Number
<input
type="text"
name="buyer_phone"
value="<?= htmlspecialchars($_SESSION['user']->phone ?? '') ?>"
placeholder="+27 60 000 0000"
>
</label>

<button type="submit" class="btn primary full-width" id="checkoutSubmitBtn">
Order Tickets
</button>
</form>
</aside>
</div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function () {
        const checkoutForm = document.getElementById('checkoutForm');
        const confirmDuplicateInput = document.getElementById('confirmDuplicatePurchase');

        if (!checkoutForm) {
            return;
        }

    checkoutForm.addEventListener('submit', async function (event) {
            const alreadyConfirmed = confirmDuplicateInput && confirmDuplicateInput.value === '1';

            if (alreadyConfirmed) {
                disableCheckoutButton();
                return;
            }

        event.preventDefault();

        const emailInput = checkoutForm.querySelector('[name="buyer_email"]');
        const eventInput = checkoutForm.querySelector('[name="event_id"]');

        const buyerEmail = emailInput ? emailInput.value.trim() : '';
        const eventId = eventInput ? eventInput.value.trim() : '';

        if (!buyerEmail) {
            checkoutForm.reportValidity();
            return;
        }

    try {
        const formData = new FormData();
        formData.append('buyer_email', buyerEmail);
        formData.append('event_id', eventId);

        const response = await fetch('<?= $appUrl ?>/checkout/check-duplicate', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
    });

const data = await response.json();

if (data.duplicate) {
    let htmlMessage = data.message;

    if (data.order) {
        htmlMessage += '<br><br>';
        htmlMessage += '<strong>Recent order:</strong><br>';
        htmlMessage += data.order.order_number + '<br>';
        htmlMessage += data.order.total_qty + ' ticket(s) · ' + data.order.total_amount + '<br>';
        htmlMessage += data.order.created_at;
    }

if (typeof Swal !== 'undefined') {
    Swal.fire({
            icon: 'warning',
            title: 'Existing Purchase Found',
            html: htmlMessage,
            showCancelButton: true,
            confirmButtonText: 'Yes, buy more',
            cancelButtonText: 'Cancel',
            showCloseButton: true,
            confirmButtonColor: '#061d43',
            cancelButtonColor: '#9b1c1c'
}).then(function (result) {
if (result.isConfirmed) {
    confirmDuplicateInput.value = '1';
    disableCheckoutButton();
    checkoutForm.submit();
}
});
} else {
const confirmed = window.confirm(
    'You already purchased tickets for this event recently. Are you sure you want to buy more?'
);

if (confirmed) {
    confirmDuplicateInput.value = '1';
    disableCheckoutButton();
    checkoutForm.submit();
}
}

return;
}

disableCheckoutButton();
checkoutForm.submit();
} catch (error) {
disableCheckoutButton();
checkoutForm.submit();
}
});

function disableCheckoutButton() {
    const submitButtons = checkoutForm.querySelectorAll('button[type="submit"]');

    submitButtons.forEach(function (button) {
            button.disabled = true;

            if (!button.dataset.originalText) {
                button.dataset.originalText = button.textContent;
            }

        button.textContent = 'Processing...';
});
}
})();
</script>
