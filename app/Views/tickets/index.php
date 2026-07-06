<section class="dashboard-wrap">
    <div class="dashboard-card admin-dashboard-card">
        <span class="badge">My Tickets</span>

        <h1>My Tickets</h1>

        <p class="muted">
            These are your confirmed Royal Splash tickets. Open each ticket to show the QR code at the gate.
        </p>

        <?php if (!empty($_SESSION['ticket_success'])): ?>
            <div class="alert success">
                <?= htmlspecialchars($_SESSION['ticket_success']) ?>
            </div>
            <?php unset($_SESSION['ticket_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['ticket_error'])): ?>
            <div class="alert error">
                <?= htmlspecialchars($_SESSION['ticket_error']) ?>
            </div>
            <?php unset($_SESSION['ticket_error']); ?>
        <?php endif; ?>

        <?php if (empty($tickets)): ?>
            <div class="empty-card">
                <h3>No tickets yet</h3>

                <p class="muted">
                    Once your payment is confirmed, your tickets will appear here.
                </p>

                <a href="<?= $appUrl ?>/events" class="btn primary">
                    Browse Events
                </a>
            </div>
        <?php else: ?>
            <div class="ticket-list-grid">
                <?php foreach ($tickets as $ticket): ?>
                    <?php
                        $isValid = ($ticket->status ?? '') === 'valid';
                        $isUsed = !empty($ticket->scanned_at);
                        $hasPendingTransfer = !empty($ticket->pending_transfer_id);
                        $canTransfer = $isValid && !$isUsed && !$hasPendingTransfer;
                    ?>

                    <article class="my-ticket-card">
                        <div>
                            <span class="status-pill status-<?= htmlspecialchars($ticket->status) ?>">
                                <?= htmlspecialchars(ucfirst($ticket->status)) ?>
                            </span>

                            <?php if ($isUsed): ?>
                                <span class="status-pill status-used">
                                    Used
                                </span>
                            <?php endif; ?>

                            <?php if ($hasPendingTransfer): ?>
                                <span class="status-pill status-pending-transfer">
                                    Transfer Pending
                                </span>
                            <?php endif; ?>

                            <h2><?= htmlspecialchars($ticket->event_title) ?></h2>

                            <p class="muted">
                                <?= date('d M Y', strtotime($ticket->event_date)) ?>

                                <?php if (!empty($ticket->start_time)): ?>
                                    · <?= htmlspecialchars($ticket->start_time) ?>
                                <?php endif; ?>
                            </p>

                            <p>
                                <strong><?= htmlspecialchars($ticket->ticket_name) ?></strong>
                                <br>
                                <?= htmlspecialchars($ticket->venue_name) ?>, <?= htmlspecialchars($ticket->city) ?>
                            </p>

                            <small>
                                Ticket No: <?= htmlspecialchars($ticket->ticket_number) ?>
                            </small>

                            <?php if ($hasPendingTransfer): ?>
                                <div class="pending-transfer-box">
                                    <strong>Transfer pending</strong>

                                    <p>
                                        Sent to:
                                        <?= htmlspecialchars($ticket->pending_transfer_name) ?>
                                        ·
                                        <?= htmlspecialchars($ticket->pending_transfer_email) ?>
                                    </p>

                                    <?php if (!empty($ticket->pending_transfer_expires_at)): ?>
                                        <small>
                                            Expires:
                                            <?= htmlspecialchars(date('d M Y H:i', strtotime($ticket->pending_transfer_expires_at))) ?>
                                        </small>
                                    <?php endif; ?>

                                    <form 
                                        method="POST" 
                                        action="<?= $appUrl ?>/ticket-transfer/cancel"
                                        onsubmit="return confirm('Cancel this pending ticket transfer?');"
                                    >
                                        <input 
                                            type="hidden" 
                                            name="transfer_id" 
                                            value="<?= (int) $ticket->pending_transfer_id ?>"
                                        >

                                        <button type="submit" class="btn danger-outline">
                                            Cancel Transfer
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="ticket-card-actions">
                            <a
                                href="<?= $appUrl ?>/ticket?token=<?= urlencode($ticket->qr_token) ?>"
                                class="btn primary"
                            >
                                Open Ticket
                            </a>

                            <a
                                href="<?= $appUrl ?>/ticket/download?token=<?= urlencode($ticket->qr_token) ?>"
                                class="btn gold"
                            >
                                Download PDF
                            </a>

                            <?php if ($canTransfer || (($ticket->status ?? '') === 'valid' && empty($ticket->scanned_at))): ?>
                                <a
                                    href="<?= $appUrl ?>/ticket/google-wallet?ticket_id=<?= (int) $ticket->id ?>"
                                    class="btn google-wallet"
                                >
                                    Add to Google Wallet
                                </a>
                            <?php endif; ?>

                            <?php if ($canTransfer): ?>
                                <a
                                    href="<?= $appUrl ?>/ticket-transfer?token=<?= urlencode($ticket->qr_token) ?>"
                                    class="btn secondary-dark"
                                >
                                    Transfer Ticket
                                </a>
                            <?php elseif ($hasPendingTransfer): ?>
                                <button type="button" class="btn secondary-dark" disabled>
                                    Transfer Pending
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn secondary-dark" disabled>
                                    Transfer Unavailable
                                </button>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>