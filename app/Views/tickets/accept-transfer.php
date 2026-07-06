<section class="ticket-transfer-page">
    <div class="ticket-transfer-shell">
        <div class="ticket-transfer-card">
            <span class="badge">Royal Splash</span>

            <h1>Accept Ticket</h1>

            <p class="muted">
                <?= htmlspecialchars($transfer->sender_name ?: 'A Royal Splash customer') ?> has sent you a ticket transfer.
            </p>

            <?php if (!empty($_SESSION['transfer_error'])): ?>
                <div class="alert error">
                    <?= htmlspecialchars($_SESSION['transfer_error']) ?>
                </div>
                <?php unset($_SESSION['transfer_error']); ?>
            <?php endif; ?>

            <div class="transfer-ticket-summary">
                <div>
                    <span>Ticket</span>
                    <strong><?= htmlspecialchars($transfer->ticket_number) ?></strong>
                </div>

                <div>
                    <span>Type</span>
                    <strong><?= htmlspecialchars($transfer->ticket_name) ?></strong>
                </div>

                <div>
                    <span>Event</span>
                    <strong><?= htmlspecialchars($transfer->event_title) ?></strong>
                </div>

                <div>
                    <span>Date</span>
                    <strong><?= htmlspecialchars(date('D, d M Y', strtotime($transfer->event_date))) ?></strong>
                </div>

                <div class="wide">
                    <span>Recipient</span>
                    <strong><?= htmlspecialchars($transfer->recipient_name) ?> · <?= htmlspecialchars($transfer->recipient_email) ?></strong>
                </div>

                <div class="wide">
                    <span>Venue</span>
                    <strong><?= htmlspecialchars($transfer->venue_name) ?>, <?= htmlspecialchars($transfer->city) ?></strong>
                </div>
            </div>

            <form method="POST" action="<?= $appUrl ?>/ticket-transfer/accept" class="auth-form">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <?php if ($requiresPassword): ?>
                    <div class="alert success">
                        A customer account will be created for you when you accept this ticket.
                    </div>

                    <div class="form-group">
                        <label>Create Password</label>

                        <input 
                            type="password" 
                            name="password" 
                            minlength="8"
                            placeholder="Minimum 8 characters"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Confirm Password</label>

                        <input 
                            type="password" 
                            name="confirm_password" 
                            minlength="8"
                            placeholder="Repeat password"
                            required
                        >
                    </div>
                <?php else: ?>
                    <div class="alert success">
                        This ticket will be added to your existing Royal Splash customer account.
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn primary full-width">
                    Accept Ticket Transfer
                </button>
            </form>
        </div>
    </div>
</section>