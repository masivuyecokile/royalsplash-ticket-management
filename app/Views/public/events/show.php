<?php
$eventImage = !empty($event->feature_image)
? $appUrl . '/' . ltrim($event->feature_image, '/')
: '';

$eventUrl = $appUrl . '/event?slug=' . urlencode($event->slug) . '&eventID=' . (int) $event->id;
?>

<section class="public-event-detail-page">
<div class="public-event-detail-shell">
<div class="public-event-detail-hero">
<div>
<span class="badge">Royal Splash Event</span>

<h1><?= htmlspecialchars($event->title) ?></h1>

<?php if (!empty($event->subtitle)): ?>
<p><?= htmlspecialchars($event->subtitle) ?></p>
<?php else: ?>
<p>
View event details, ticket options, and secure your Royal Splash ticket.
</p>
<?php endif; ?>
</div>

<a href="<?= $appUrl ?>/events" class="btn secondary-dark">
Back to Events
</a>
</div>

<?php if (!empty($_SESSION['checkout_error'])): ?>
<div class="alert error">
<?= htmlspecialchars($_SESSION['checkout_error']) ?>
</div>
<?php unset($_SESSION['checkout_error']); ?>
<?php endif; ?>

<div class="public-event-detail-grid">
<div class="event-details-column">
<div class="event-detail-image-card">
<?php if ($eventImage): ?>
<img
src="<?= htmlspecialchars($eventImage) ?>"
alt="<?= htmlspecialchars($event->title) ?>"
>
<?php else: ?>
<div class="event-detail-image-placeholder">
Royal Splash
</div>
<?php endif; ?>
</div>

<div class="event-info-card">
<div class="event-info-header">
<span class="event-status-public">
<?= htmlspecialchars(str_replace('_', ' ', ucfirst($event->status))) ?>
</span>

<h2>Event Details</h2>
</div>

<div class="event-info-grid">
<div>
<span>Date</span>
<strong><?= htmlspecialchars(date('D, d M Y', strtotime($event->event_date))) ?></strong>
</div>

<div>
<span>Time</span>
<strong>
<?= htmlspecialchars($event->start_time) ?>

<?php if (!empty($event->end_time)): ?>
- <?= htmlspecialchars($event->end_time) ?>
<?php endif; ?>
</strong>
</div>

<div>
<span>Venue</span>
<strong><?= htmlspecialchars($event->venue_name) ?></strong>
</div>

<div>
<span>City</span>
<strong><?= htmlspecialchars($event->city) ?></strong>
</div>

<?php if (!empty($event->venue_address)): ?>
<div class="wide">
<span>Address</span>
<strong><?= htmlspecialchars($event->venue_address) ?></strong>
</div>
<?php endif; ?>

<?php if (!empty($event->main_artist)): ?>
<div class="wide highlight">
<span>Main Artist</span>
<strong><?= htmlspecialchars($event->main_artist) ?></strong>
</div>
<?php endif; ?>
</div>
</div>

<?php if (!empty($event->description)): ?>
<div class="event-info-card">
<h2>About This Event</h2>

<div class="event-description">
<?= nl2br(htmlspecialchars($event->description)) ?>
</div>
</div>
<?php endif; ?>

<?php if (!empty($event->vip_details)): ?>
<div class="event-vip-card">
<h2>VIP Experience</h2>

<div>
<?= nl2br(htmlspecialchars($event->vip_details)) ?>
</div>
</div>
<?php endif; ?>

<div class="event-share-card">
<h2>Event Link</h2>

<p>
Use this link when sharing or referencing this event:
</p>

<div class="event-link-box">
<?= htmlspecialchars($eventUrl) ?>
</div>
</div>
</div>

<aside class="event-ticket-column">
<div class="event-ticket-panel">
<div class="ticket-panel-header">
<span class="badge">Tickets</span>

<h2>Choose Tickets</h2>

<p>
Select your ticket type and quantity below. Maximum 4 tickets per order.
</p>
</div>

<?php if (empty($categories)): ?>
<div class="empty-card compact">
<h3>No tickets available</h3>

<p>
Tickets are currently unavailable or sold out.
</p>
</div>
<?php else: ?>
<form method="POST" action="<?= $appUrl ?>/checkout/start" id="ticketSelectionForm">
<input type="hidden" name="event_id" value="<?= (int) $event->id ?>">

<div class="public-ticket-list">
<?php foreach ($categories as $category): ?>
<?php
$remaining = (int) $category->quantity_available - (int) $category->quantity_sold;

if ($remaining < 0) {
    $remaining = 0;
}

$maxQty = min((int) $category->max_per_order, $remaining);
?>

<div class="public-ticket-card selectable-ticket">
<div class="ticket-info-side">
<h3><?= htmlspecialchars($category->name) ?></h3>

<?php if (!empty($category->description)): ?>
<p><?= htmlspecialchars($category->description) ?></p>
<?php endif; ?>

<span>
<?= $remaining ?> ticket(s) remaining
</span>
</div>

<div class="ticket-buy-side">
<strong>
R<?= number_format((float) $category->price, 2) ?>
</strong>

<div
class="qty-control"
data-max="<?= (int) $maxQty ?>"
data-ticket-name="<?= htmlspecialchars($category->name) ?>"
>
<button type="button" class="qty-btn minus" aria-label="Decrease quantity">
-
</button>

<input
type="number"
name="tickets[<?= (int) $category->id ?>]"
class="ticket-qty"
value="0"
min="0"
max="<?= (int) $maxQty ?>"
readonly
>

<button type="button" class="qty-btn plus" aria-label="Increase quantity">
+
</button>
</div>
</div>
</div>
<?php endforeach; ?>
</div>

<div class="desktop-checkout-summary" id="desktopCheckoutSummary">
<div>
<span>Total Tickets</span>
<strong id="selectedTicketCount">0</strong>
</div>

<div>
<span>Total Amount</span>
<strong id="selectedTicketTotal">R0.00</strong>
</div>

<button type="submit" class="btn primary" id="desktopContinueBtn" disabled>
Continue
</button>
</div>

<div class="mobile-checkout-bar" id="mobileCheckoutBar">
<div>
<span id="mobileTicketCount">0 tickets</span>
<strong id="mobileTicketTotal">R0.00</strong>
</div>

<button type="submit" class="btn primary" id="mobileContinueBtn" disabled>
Continue
</button>
</div>
</form>
<?php endif; ?>
</div>
</aside>
</div>
</div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const qtyControls = document.querySelectorAll('.qty-control');
const ticketCountEl = document.getElementById('selectedTicketCount');
const ticketTotalEl = document.getElementById('selectedTicketTotal');
const mobileTicketCountEl = document.getElementById('mobileTicketCount');
const mobileTicketTotalEl = document.getElementById('mobileTicketTotal');
const desktopContinueBtn = document.getElementById('desktopContinueBtn');
const mobileContinueBtn = document.getElementById('mobileContinueBtn');

const MAX_ORDER_TICKETS = 4;

function money(value) {
    return 'R' + Number(value).toFixed(2);
}

function showTicketAlert(title, message, icon = 'info') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
                icon: icon,
                title: title,
                text: message,
                confirmButtonText: 'OK',
                showCloseButton: true
        });
} else {
alert(title + "\n\n" + message);
}
}

function getCurrentTotalQty() {
    let total = 0;

    document.querySelectorAll('.ticket-qty').forEach(function (qtyInput) {
            total += parseInt(qtyInput.value || '0', 10);
    });

return total;
}

function updateTotals() {
    let totalQty = 0;
    let totalAmount = 0;

    document.querySelectorAll('.selectable-ticket').forEach(function (card) {
            const qtyInput = card.querySelector('.ticket-qty');
            const priceText = card.querySelector('.ticket-buy-side strong').textContent.replace('R', '').replace(',', '').trim();

            const qty = parseInt(qtyInput.value || '0', 10);
            const price = parseFloat(priceText || '0');

            totalQty += qty;
            totalAmount += qty * price;
    });

if (ticketCountEl) {
    ticketCountEl.textContent = totalQty;
}

if (ticketTotalEl) {
    ticketTotalEl.textContent = money(totalAmount);
}

if (mobileTicketCountEl) {
    mobileTicketCountEl.textContent = totalQty + (totalQty === 1 ? ' ticket' : ' tickets');
}

if (mobileTicketTotalEl) {
    mobileTicketTotalEl.textContent = money(totalAmount);
}

const canContinue = totalQty > 0 && totalQty <= MAX_ORDER_TICKETS;

if (desktopContinueBtn) {
    desktopContinueBtn.disabled = !canContinue;
}

if (mobileContinueBtn) {
    mobileContinueBtn.disabled = !canContinue;
}

if (totalQty > 0) {
    document.body.classList.add('has-mobile-checkout');
} else {
document.body.classList.remove('has-mobile-checkout');
}
}

qtyControls.forEach(function (control) {
        const minus = control.querySelector('.minus');
        const plus = control.querySelector('.plus');
        const input = control.querySelector('.ticket-qty');
        const max = parseInt(control.dataset.max || '0', 10);
        const ticketName = control.dataset.ticketName || 'this ticket type';

        minus.addEventListener('click', function () {
                let value = parseInt(input.value || '0', 10);

                if (value > 0) {
                    value--;
                }

            input.value = value;
            updateTotals();
    });

plus.addEventListener('click', function () {
        let currentTotal = getCurrentTotalQty();
        let value = parseInt(input.value || '0', 10);

        if (currentTotal >= MAX_ORDER_TICKETS) {
            showTicketAlert(
                'Maximum Reached',
                'You can only buy a maximum of ' + MAX_ORDER_TICKETS + ' tickets per order.',
                'info'
            );
        return;
    }

if (value >= max) {
    showTicketAlert(
        'Ticket Limit Reached',
        'You cannot select more ' + ticketName + ' tickets. The available or allowed limit for this ticket type has been reached.',
        'warning'
    );
return;
}

value++;
input.value = value;
updateTotals();
});
});

updateTotals();
</script>
