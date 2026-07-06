document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('ticketSelectionForm');

    if (!form) {
        return;
    }

    const maxTickets = 4;
    const qtyInputs = form.querySelectorAll('.ticket-qty');

    const desktopCount = document.getElementById('desktopTicketCount');
    const desktopTotal = document.getElementById('desktopTicketTotal');
    const desktopContinue = document.getElementById('desktopContinueBtn');

    const mobileCount = document.getElementById('mobileTicketCount');
    const mobileTotal = document.getElementById('mobileTicketTotal');
    const mobileContinue = document.getElementById('mobileContinueBtn');

    function calculateTotals() {
        let totalQty = 0;
        let totalAmount = 0;

        qtyInputs.forEach(function (input) {
            const qty = parseInt(input.value || '0', 10);
            const price = parseFloat(input.dataset.price || '0');

            totalQty += qty;
            totalAmount += qty * price;
        });

        const countText = totalQty === 1 ? '1 ticket selected' : totalQty + ' tickets selected';
        const mobileCountText = totalQty === 1 ? '1 ticket' : totalQty + ' tickets';
        const totalText = 'R' + totalAmount.toFixed(2);

        desktopCount.textContent = countText;
        desktopTotal.textContent = totalText;

        mobileCount.textContent = mobileCountText;
        mobileTotal.textContent = totalText;

        desktopContinue.disabled = totalQty === 0;
        mobileContinue.disabled = totalQty === 0;

        document.body.classList.toggle('has-mobile-checkout', totalQty > 0);
    }

    function getTotalQty() {
        let totalQty = 0;

        qtyInputs.forEach(function (input) {
            totalQty += parseInt(input.value || '0', 10);
        });

        return totalQty;
    }

    form.addEventListener('click', function (event) {
        const button = event.target.closest('.qty-btn');

        if (!button) {
            return;
        }

        const ticketCard = button.closest('.selectable-ticket');
        const input = ticketCard.querySelector('.ticket-qty');

        const action = button.dataset.action;
        const currentQty = parseInt(input.value || '0', 10);
        const maxForCategory = parseInt(input.max || '0', 10);
        const currentTotal = getTotalQty();

        if (action === 'plus') {
            if (currentTotal >= maxTickets) {
                alert('Maximum 4 tickets per transaction.');
                return;
            }

            if (currentQty >= maxForCategory) {
                alert('No more tickets available for this ticket type.');
                return;
            }

            input.value = currentQty + 1;
        }

        if (action === 'minus') {
            input.value = Math.max(0, currentQty - 1);
        }

        calculateTotals();
    });

    form.addEventListener('submit', function (event) {
        const totalQty = getTotalQty();

        if (totalQty <= 0) {
            event.preventDefault();
            alert('Please select at least one ticket.');
            return;
        }

        if (totalQty > maxTickets) {
            event.preventDefault();
            alert('Maximum 4 tickets per transaction.');
        }
    });

    calculateTotals();
});