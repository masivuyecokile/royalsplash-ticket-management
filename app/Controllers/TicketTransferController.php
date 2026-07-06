<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\TicketTransferEmailService;
use PDO;

class TicketTransferController extends Controller
{
    public function showCreate(): void
    {
        $this->requireRole('customer');

        $token = trim($_GET['token'] ?? '');

        if ($token === '') {
            $_SESSION['ticket_error'] = 'Invalid ticket transfer request.';
            $this->redirect('/my-tickets');
        }

        $db = Database::connect();
        $ticket = $this->findCustomerTicketByToken($db, $token);

        if (!$ticket) {
            $_SESSION['ticket_error'] = 'Ticket not found or not owned by your account.';
            $this->redirect('/my-tickets');
        }

        if ($ticket->status !== 'valid' || !empty($ticket->scanned_at)) {
            $_SESSION['ticket_error'] = 'Only valid unused tickets can be transferred.';
            $this->redirect('/my-tickets');
        }

        $this->view('tickets/transfer', [
            'pageTitle' => 'Transfer Ticket',
            'ticket' => $ticket,
        ]);
    }

    public function store(): void
    {
        $this->requireRole('customer');

        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $recipientName = trim($_POST['recipient_name'] ?? '');
        $recipientEmail = strtolower(trim($_POST['recipient_email'] ?? ''));
        $recipientPhone = trim($_POST['recipient_phone'] ?? '');

        if ($ticketId <= 0) {
            $_SESSION['ticket_error'] = 'Invalid ticket transfer request.';
            $this->redirect('/my-tickets');
        }

        if ($recipientName === '' || $recipientEmail === '') {
            $_SESSION['ticket_error'] = 'Recipient name and email are required.';
            $this->redirect('/my-tickets');
        }

        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['ticket_error'] = 'Please enter a valid recipient email address.';
            $this->redirect('/my-tickets');
        }

        $currentUser = $this->authUser();

        if (strtolower($currentUser->email ?? '') === $recipientEmail) {
            $_SESSION['ticket_error'] = 'You cannot transfer a ticket to your own email address.';
            $this->redirect('/my-tickets');
        }

        $db = Database::connect();

        $ticket = $this->findCustomerTicketById($db, $ticketId);

        if (!$ticket) {
            $_SESSION['ticket_error'] = 'Ticket not found or not owned by your account.';
            $this->redirect('/my-tickets');
        }

        if ($ticket->status !== 'valid' || !empty($ticket->scanned_at)) {
            $_SESSION['ticket_error'] = 'Only valid unused tickets can be transferred.';
            $this->redirect('/my-tickets');
        }

        $existingUser = $this->findUserByEmail($db, $recipientEmail);

        if ($existingUser && $existingUser->role !== 'customer') {
            $_SESSION['ticket_error'] = 'This email address cannot receive customer ticket transfers.';
            $this->redirect('/my-tickets');
        }

        if ($existingUser && $existingUser->status !== 'active') {
            $_SESSION['ticket_error'] = 'The recipient customer account is not active.';
            $this->redirect('/my-tickets');
        }

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        $db->beginTransaction();

        try {
            $cancelOld = $db->prepare("
                UPDATE ticket_transfers
                SET 
                    status = 'cancelled',
                    cancelled_at = NOW(),
                    updated_at = NOW()
                WHERE ticket_id = :ticket_id
                AND status = 'pending'
            ");

            $cancelOld->execute([
                ':ticket_id' => $ticketId,
            ]);

            $insert = $db->prepare("
                INSERT INTO ticket_transfers (
                    ticket_id,
                    from_user_id,
                    recipient_name,
                    recipient_email,
                    recipient_phone,
                    token_hash,
                    status,
                    expires_at
                ) VALUES (
                    :ticket_id,
                    :from_user_id,
                    :recipient_name,
                    :recipient_email,
                    :recipient_phone,
                    :token_hash,
                    'pending',
                    DATE_ADD(NOW(), INTERVAL 72 HOUR)
                )
            ");

            $insert->execute([
                ':ticket_id' => $ticketId,
                ':from_user_id' => (int) $currentUser->id,
                ':recipient_name' => $recipientName,
                ':recipient_email' => $recipientEmail,
                ':recipient_phone' => $recipientPhone !== '' ? $recipientPhone : null,
                ':token_hash' => $tokenHash,
            ]);

            $transferId = (int) $db->lastInsertId();

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $_SESSION['ticket_error'] = 'Could not create transfer request.';
            $this->redirect('/my-tickets');
        }

        try {
            TicketTransferEmailService::sendTransferInvite($db, $transferId, $plainToken);

            $_SESSION['ticket_success'] = 'Ticket transfer invitation sent successfully.';
        } catch (\Throwable $e) {
            $_SESSION['ticket_error'] = 'Transfer was created, but email could not be sent: ' . $e->getMessage();
        }

        $this->redirect('/my-tickets');
    }

    public function showAccept(): void
    {
        $token = $this->cleanToken($_GET['token'] ?? '');

        if ($token === '') {
            $_SESSION['login_error'] = 'Invalid or expired transfer link.';
            $this->redirect('/login');
        }

        $db = Database::connect();

        $transfer = $this->findValidTransferByToken($db, $token);

        if (!$transfer) {
            $_SESSION['login_error'] = 'Invalid or expired transfer link.';
            $this->redirect('/login');
        }

        $existingUser = $this->findUserByEmail($db, $transfer->recipient_email);

        $this->view('tickets/accept-transfer', [
            'pageTitle' => 'Accept Ticket Transfer',
            'transfer' => $transfer,
            'token' => $token,
            'requiresPassword' => !$existingUser,
        ]);
    }

    public function accept(): void
    {
        $token = $this->cleanToken($_POST['token'] ?? '');

        if ($token === '') {
            $_SESSION['login_error'] = 'Invalid or expired transfer link.';
            $this->redirect('/login');
        }

        $db = Database::connect();

        $transfer = $this->findValidTransferByToken($db, $token);

        if (!$transfer) {
            $_SESSION['login_error'] = 'Invalid or expired transfer link.';
            $this->redirect('/login');
        }

        if ($transfer->ticket_status !== 'valid' || !empty($transfer->scanned_at)) {
            $_SESSION['login_error'] = 'This ticket can no longer be transferred.';
            $this->redirect('/login');
        }

        $existingUser = $this->findUserByEmail($db, $transfer->recipient_email);
        $createdNewUser = false;

        if ($existingUser && $existingUser->role !== 'customer') {
            $_SESSION['login_error'] = 'This transfer cannot be accepted by this account type.';
            $this->redirect('/login');
        }

        if ($existingUser && $existingUser->status !== 'active') {
            $_SESSION['login_error'] = 'This customer account is not active.';
            $this->redirect('/login');
        }

        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if (!$existingUser) {
            if ($password === '' || strlen($password) < 8) {
                $_SESSION['transfer_error'] = 'Please create a password of at least 8 characters.';
                $this->redirect('/ticket-transfer/accept?token=' . urlencode($token));
            }

            if ($password !== $confirmPassword) {
                $_SESSION['transfer_error'] = 'Passwords do not match.';
                $this->redirect('/ticket-transfer/accept?token=' . urlencode($token));
            }
        }

        $db->beginTransaction();

        try {
            $lockedTransfer = $this->findValidTransferByTokenForUpdate($db, $token);

            if (!$lockedTransfer) {
                throw new \RuntimeException('Transfer is no longer valid.');
            }

            if ($lockedTransfer->ticket_status !== 'valid' || !empty($lockedTransfer->scanned_at)) {
                throw new \RuntimeException('Ticket is no longer valid.');
            }

            if ($existingUser) {
                $recipientUserId = (int) $existingUser->id;
            } else {
                $insertUser = $db->prepare("
                    INSERT INTO users (
                        full_name,
                        email,
                        phone,
                        password,
                        role,
                        status
                    ) VALUES (
                        :full_name,
                        :email,
                        :phone,
                        :password,
                        'customer',
                        'active'
                    )
                ");

                $insertUser->execute([
                    ':full_name' => $lockedTransfer->recipient_name,
                    ':email' => $lockedTransfer->recipient_email,
                    ':phone' => $lockedTransfer->recipient_phone,
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                $recipientUserId = (int) $db->lastInsertId();
                $createdNewUser = true;
            }

            /*
             * Security fix:
             * Generate a fresh QR token when ticket ownership changes.
             * This invalidates the old owner's downloaded PDF / old QR code.
             */
            $newQrToken = $this->generateQrToken($db);

            $updateTicket = $db->prepare("
                UPDATE tickets
                SET
                    user_id = :user_id,
                    buyer_name = :buyer_name,
                    buyer_email = :buyer_email,
                    qr_token = :qr_token,
                    pdf_path = NULL,
                    pdf_generated_at = NULL,
                    updated_at = NOW()
                WHERE id = :ticket_id
                AND status = 'valid'
            ");

            $updateTicket->execute([
                ':user_id' => $recipientUserId,
                ':buyer_name' => $lockedTransfer->recipient_name,
                ':buyer_email' => $lockedTransfer->recipient_email,
                ':qr_token' => $newQrToken,
                ':ticket_id' => (int) $lockedTransfer->ticket_id,
            ]);

            if ($updateTicket->rowCount() <= 0) {
                throw new \RuntimeException('Ticket could not be updated.');
            }

            $updateTransfer = $db->prepare("
                UPDATE ticket_transfers
                SET
                    to_user_id = :to_user_id,
                    status = 'accepted',
                    accepted_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id
            ");

            $updateTransfer->execute([
                ':to_user_id' => $recipientUserId,
                ':id' => (int) $lockedTransfer->transfer_id,
            ]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $_SESSION['transfer_error'] = 'Could not accept transfer: ' . $e->getMessage();
            $this->redirect('/ticket-transfer/accept?token=' . urlencode($token));
        }

        if ($createdNewUser) {
            $_SESSION['user'] = (object) [
                'id' => $recipientUserId,
                'full_name' => $transfer->recipient_name,
                'email' => $transfer->recipient_email,
                'phone' => $transfer->recipient_phone,
                'role' => 'customer',
            ];

            $_SESSION['ticket_success'] = 'Ticket transfer accepted. Your account has been created.';
            $this->redirect('/my-tickets');
        }

        $_SESSION['login_success'] = 'Ticket transfer accepted. Please login to view your ticket.';
        $this->redirect('/login');
    }

    public function cancel(): void
    {
        $this->requireRole('customer');

        $transferId = (int) ($_POST['transfer_id'] ?? 0);
        $user = $this->authUser();

        if ($transferId <= 0) {
            $_SESSION['ticket_error'] = 'Invalid transfer cancellation request.';
            $this->redirect('/my-tickets');
        }

        $db = Database::connect();

        $stmt = $db->prepare("
            UPDATE ticket_transfers
            SET
                status = 'cancelled',
                cancelled_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
            AND from_user_id = :from_user_id
            AND status = 'pending'
        ");

        $stmt->execute([
            ':id' => $transferId,
            ':from_user_id' => (int) $user->id,
        ]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['ticket_success'] = 'Pending ticket transfer cancelled successfully.';
        } else {
            $_SESSION['ticket_error'] = 'Transfer could not be cancelled. It may already be accepted, expired, or cancelled.';
        }

        $this->redirect('/my-tickets');
    }

    private function findCustomerTicketByToken(PDO $db, string $token): ?object
    {
        $user = $this->authUser();

        $stmt = $db->prepare("
            SELECT 
                t.*,
                e.title AS event_title,
                e.event_date,
                e.start_time,
                e.end_time,
                e.venue_name,
                e.city
            FROM tickets t
            INNER JOIN events e ON e.id = t.event_id
            WHERE t.qr_token = :token
            AND t.user_id = :user_id
            LIMIT 1
        ");

        $stmt->execute([
            ':token' => $token,
            ':user_id' => (int) $user->id,
        ]);

        $ticket = $stmt->fetch();

        return $ticket ?: null;
    }

    private function findCustomerTicketById(PDO $db, int $ticketId): ?object
    {
        $user = $this->authUser();

        $stmt = $db->prepare("
            SELECT 
                t.*,
                e.title AS event_title,
                e.event_date,
                e.start_time,
                e.end_time,
                e.venue_name,
                e.city
            FROM tickets t
            INNER JOIN events e ON e.id = t.event_id
            WHERE t.id = :id
            AND t.user_id = :user_id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $ticketId,
            ':user_id' => (int) $user->id,
        ]);

        $ticket = $stmt->fetch();

        return $ticket ?: null;
    }

    private function findUserByEmail(PDO $db, string $email): ?object
    {
        $stmt = $db->prepare("
            SELECT *
            FROM users
            WHERE email = :email
            LIMIT 1
        ");

        $stmt->execute([
            ':email' => strtolower(trim($email)),
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    private function findValidTransferByToken(PDO $db, string $plainToken): ?object
    {
        return $this->findValidTransfer($db, $plainToken, false);
    }

    private function findValidTransferByTokenForUpdate(PDO $db, string $plainToken): ?object
    {
        return $this->findValidTransfer($db, $plainToken, true);
    }

    private function findValidTransfer(PDO $db, string $plainToken, bool $forUpdate = false): ?object
    {
        $tokenHash = hash('sha256', $plainToken);
        $lockSql = $forUpdate ? 'FOR UPDATE' : '';

        $stmt = $db->prepare("
            SELECT
                tt.id AS transfer_id,
                tt.ticket_id,
                tt.from_user_id,
                tt.recipient_name,
                tt.recipient_email,
                tt.recipient_phone,
                tt.expires_at,
                t.ticket_number,
                t.ticket_name,
                t.status AS ticket_status,
                t.scanned_at,
                e.title AS event_title,
                e.event_date,
                e.start_time,
                e.end_time,
                e.venue_name,
                e.city,
                sender.full_name AS sender_name
            FROM ticket_transfers tt
            INNER JOIN tickets t ON t.id = tt.ticket_id
            INNER JOIN events e ON e.id = t.event_id
            LEFT JOIN users sender ON sender.id = tt.from_user_id
            WHERE tt.token_hash = :token_hash
            AND tt.status = 'pending'
            AND tt.expires_at > NOW()
            LIMIT 1
            $lockSql
        ");

        $stmt->execute([
            ':token_hash' => $tokenHash,
        ]);

        $transfer = $stmt->fetch();

        return $transfer ?: null;
    }

    private function generateQrToken(PDO $db): string
    {
        do {
            $token = bin2hex(random_bytes(32));

            $stmt = $db->prepare("
                SELECT id
                FROM tickets
                WHERE qr_token = :qr_token
                LIMIT 1
            ");

            $stmt->execute([
                ':qr_token' => $token,
            ]);

            $exists = $stmt->fetch();
        } while ($exists);

        return $token;
    }

    private function cleanToken(string $token): string
    {
        $token = trim($token);
        $token = html_entity_decode($token, ENT_QUOTES, 'UTF-8');
        $token = urldecode($token);
        $token = preg_replace('/[^a-f0-9]/i', '', $token);

        return $token;
    }
}