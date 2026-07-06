<section class="ticket-transfer-page">
    <div class="ticket-transfer-shell">
        <div class="ticket-transfer-card">
            <span class="badge">Transfer Ticket</span>

            <h1>Transfer Ticket</h1>

            <p class="muted">
                Send this ticket to another person. The ticket will only move to them once they accept the transfer.
            </p>

            <?php if (!empty($_SESSION['ticket_error'])): ?>
                <div class="alert error">
                    <?= htmlspecialchars($_SESSION['ticket_error']) ?>
                </div>
                <?php unset($_SESSION['ticket_error']); ?>
            <?php endif; ?>

            <div class="transfer-ticket-summary">
                <div>
                    <span>Ticket</span>
                    <strong><?= htmlspecialchars($ticket->ticket_number) ?></strong>
                </div>

                <div>
                    <span>Type</span>
                    <strong><?= htmlspecialchars($ticket->ticket_name) ?></strong>
                </div>

                <div>
                    <span>Event</span>
                    <strong><?= htmlspecialchars($ticket->event_title) ?></strong>
                </div>

                <div>
                    <span>Date</span>
                    <strong><?= htmlspecialchars(date('D, d M Y', strtotime($ticket->event_date))) ?></strong>
                </div>

                <div class="wide">
                    <span>Venue</span>
                    <strong><?= htmlspecialchars($ticket->venue_name) ?>, <?= htmlspecialchars($ticket->city) ?></strong>
                </div>
            </div>

            <form method="POST" action="<?= $appUrl ?>/ticket-transfer/create" class="auth-form">
                <input type="hidden" name="ticket_id" value="<?= (int) $ticket->id ?>">

                <div class="form-group">
                    <label>Recipient Full Name</label>

                    <input 
                        type="text" 
                        name="recipient_name" 
                        placeholder="Enter recipient name"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Recipient Email</label>

                    <input 
                        type="email" 
                        name="recipient_email" 
                        placeholder="Enter recipient email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Recipient Phone</label>

                    <input 
                        type="text" 
                        name="recipient_phone" 
                        placeholder="Optional"
                    >
                </div>

                <button type="submit" class="btn primary full-width">
                    Send Transfer Invitation
                </button>
            </form>

            <p class="auth-footer-text">
                <a href="<?= $appUrl ?>/my-tickets">Back to My Tickets</a>
            </p>
        </div>
    </div>
</section>