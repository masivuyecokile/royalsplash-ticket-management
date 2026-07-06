<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\PayFast;
use App\Core\TicketPdfService;
use App\Core\TicketEmailService;
use App\Core\AccountSetupService;
use PDO;

class CheckoutController extends Controller
{
    public function startRedirect(): void
    {
        $this->redirect('/events');
    }

public function start(): void
{
    $eventId = (int) ($_POST['event_id'] ?? 0);
    $postedTickets = $_POST['tickets'] ?? [];

    if ($eventId <= 0 || empty($postedTickets) || !is_array($postedTickets)) {
        $this->redirect('/events');
    }

$db = Database::connect();

$eventStmt = $db->prepare("
    SELECT *
    FROM events
    WHERE id = :id
    AND status IN ('published', 'on_sale')
    LIMIT 1
    ");

    $eventStmt->execute([
            ':id' => $eventId,
            ]);

    $event = $eventStmt->fetch();

    if (!$event) {
        $this->redirect('/events');
    }

$categoryIds = array_keys($postedTickets);
$categoryIds = array_map('intval', $categoryIds);
$categoryIds = array_filter($categoryIds);

if (empty($categoryIds)) {
    $this->redirect('/event?slug=' . urlencode($event->slug) . '&eventID=' . (int) $event->id);
}

$placeholders = implode(',', array_fill(0, count($categoryIds), '?'));

$categoryStmt = $db->prepare("
    SELECT *
    FROM ticket_categories
    WHERE event_id = ?
    AND status = 'active'
    AND id IN ($placeholders)
    ");

    $categoryStmt->execute(array_merge([$eventId], $categoryIds));
    $categories = $categoryStmt->fetchAll();

    $items = [];
    $totalQty = 0;
    $totalAmount = 0.00;

    foreach ($categories as $category) {
        $qty = (int) ($postedTickets[$category->id] ?? 0);

        if ($qty <= 0) {
            continue;
        }

    $remaining = (int) $category->quantity_available - (int) $category->quantity_sold;

    if ($remaining < 0) {
        $remaining = 0;
    }

if ($qty > $remaining) {
    $_SESSION['checkout_error'] = 'Some selected tickets are no longer available.';
    $this->redirect('/event?slug=' . urlencode($event->slug) . '&eventID=' . (int) $event->id);
}

if ($qty > (int) $category->max_per_order) {
    $_SESSION['checkout_error'] = 'You selected more than allowed for ' . $category->name . '.';
    $this->redirect('/event?slug=' . urlencode($event->slug) . '&eventID=' . (int) $event->id);
}

$lineTotal = $qty * (float) $category->price;

$items[] = (object) [
    'category_id' => $category->id,
    'name' => $category->name,
    'description' => $category->description,
    'qty' => $qty,
    'unit_price' => (float) $category->price,
    'line_total' => $lineTotal,
    ];

$totalQty += $qty;
$totalAmount += $lineTotal;
}

if ($totalQty <= 0) {
    $_SESSION['checkout_error'] = 'Please select at least one ticket.';
    $this->redirect('/event?slug=' . urlencode($event->slug) . '&eventID=' . (int) $event->id);
}

if ($totalQty > 4) {
    $_SESSION['checkout_error'] = 'You can buy a maximum of 4 tickets per order.';
    $this->redirect('/event?slug=' . urlencode($event->slug) . '&eventID=' . (int) $event->id);
}

$_SESSION['checkout'] = (object) [
    'event_id' => $event->id,
    'event_slug' => $event->slug,
    'event_title' => $event->title,
    'event_date' => $event->event_date,
    'start_time' => $event->start_time,
    'venue_name' => $event->venue_name,
    'venue_address' => $event->venue_address,
    'city' => $event->city,
    'items' => $items,
    'total_qty' => $totalQty,
    'total_amount' => $totalAmount,
    'created_at' => date('Y-m-d H:i:s'),
    ];

$this->redirect('/checkout');
}

public function show(): void
{
    if (empty($_SESSION['checkout'])) {
        $this->redirect('/events');
    }

$this->view('checkout/show', [
        'pageTitle' => 'Checkout',
        'checkout' => $_SESSION['checkout'],
        ]);
}

public function createOrder(): void
{
    if (empty($_SESSION['checkout'])) {
        $this->redirect('/events');
    }

$checkout = $_SESSION['checkout'];

$buyerName = trim($_POST['buyer_name'] ?? '');
$buyerEmail = strtolower(trim($_POST['buyer_email'] ?? ''));
$buyerPhone = trim($_POST['buyer_phone'] ?? '');

if ($buyerName === '' || $buyerEmail === '' || !filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['checkout_form_error'] = 'Please enter a valid name and email address.';
    $this->redirect('/checkout');
}

$db = Database::connect();

$eventStmt = $db->prepare("
    SELECT *
    FROM events
    WHERE id = :id
    AND status IN ('published', 'on_sale')
    LIMIT 1
    ");

    $eventStmt->execute([
            ':id' => (int) $checkout->event_id,
            ]);

    $event = $eventStmt->fetch();

    if (!$event) {
        $_SESSION['checkout_form_error'] = 'This event is no longer available.';
        $this->redirect('/checkout');
    }

        /*
         * Duplicate purchase protection.
         * This must happen before creating a new order.
         */
$confirmDuplicatePurchase = ($_POST['confirm_duplicate_purchase'] ?? '') === '1';

$recentPaidOrder = $this->findRecentPaidOrder(
    $db,
    (int) $checkout->event_id,
    $buyerEmail
);

if ($recentPaidOrder && !$confirmDuplicatePurchase) {
    $_SESSION['checkout_form_error'] = 'You already purchased tickets for this event recently. Please confirm if you want to buy more.';
    $this->redirect('/checkout');
}

try {
    $db->beginTransaction();

    foreach ($checkout->items as $item) {
        $catStmt = $db->prepare("
            SELECT *
            FROM ticket_categories
            WHERE id = :id
            AND event_id = :event_id
            AND status = 'active'
            LIMIT 1
            FOR UPDATE
            ");

            $catStmt->execute([
                    ':id' => (int) $item->category_id,
                    ':event_id' => (int) $checkout->event_id,
                    ]);

            $category = $catStmt->fetch();

            if (!$category) {
                throw new \RuntimeException('Ticket category not available.');
            }

        $remaining = (int) $category->quantity_available - (int) $category->quantity_sold;

        if ((int) $item->qty > $remaining) {
            throw new \RuntimeException('Some selected tickets are no longer available.');
        }
}

$orderNumber = $this->generateOrderNumber($db);
$loggedInUserId = $_SESSION['user']->id ?? null;

$orderStmt = $db->prepare("
    INSERT INTO orders (
        order_number,
        user_id,
        event_id,
        buyer_name,
        buyer_email,
        buyer_phone,
        total_qty,
        total_amount,
        payment_method,
        payment_status
    ) VALUES (
    :order_number,
    :user_id,
    :event_id,
    :buyer_name,
    :buyer_email,
    :buyer_phone,
    :total_qty,
    :total_amount,
    'payfast',
    'pending'
)
");

$orderStmt->execute([
        ':order_number' => $orderNumber,
        ':user_id' => $loggedInUserId,
        ':event_id' => (int) $checkout->event_id,
        ':buyer_name' => $buyerName,
        ':buyer_email' => $buyerEmail,
        ':buyer_phone' => $buyerPhone,
        ':total_qty' => (int) $checkout->total_qty,
        ':total_amount' => number_format((float) $checkout->total_amount, 2, '.', ''),
        ]);

$orderId = (int) $db->lastInsertId();

foreach ($checkout->items as $item) {
    $itemStmt = $db->prepare("
        INSERT INTO order_items (
            order_id,
            event_id,
            ticket_category_id,
            ticket_name,
            quantity,
            unit_price,
            line_total
        ) VALUES (
        :order_id,
        :event_id,
        :ticket_category_id,
        :ticket_name,
        :quantity,
        :unit_price,
        :line_total
    )
");

$itemStmt->execute([
        ':order_id' => $orderId,
        ':event_id' => (int) $checkout->event_id,
        ':ticket_category_id' => (int) $item->category_id,
        ':ticket_name' => $item->name,
        ':quantity' => (int) $item->qty,
        ':unit_price' => number_format((float) $item->unit_price, 2, '.', ''),
        ':line_total' => number_format((float) $item->line_total, 2, '.', ''),
        ]);
}

$db->commit();

unset($_SESSION['checkout']);
$_SESSION['current_order_id'] = $orderId;

$this->redirect('/checkout/order');
} catch (\Throwable $e) {
if ($db->inTransaction()) {
    $db->rollBack();
}

$_SESSION['checkout_form_error'] = $e->getMessage();
$this->redirect('/checkout');
}
}

public function order(): void
{
    if (empty($_SESSION['current_order_id'])) {
        $this->redirect('/events');
    }

$db = Database::connect();

$stmt = $db->prepare("
    SELECT
    o.*,
    e.title AS event_title,
    e.event_date,
    e.start_time,
    e.venue_name,
    e.venue_address,
    e.city
    FROM orders o
    INNER JOIN events e ON e.id = o.event_id
    WHERE o.id = :id
    LIMIT 1
    ");

    $stmt->execute([
            ':id' => (int) $_SESSION['current_order_id'],
            ]);

    $order = $stmt->fetch();

    if (!$order) {
        $this->redirect('/events');
    }

$itemsStmt = $db->prepare("
    SELECT *
    FROM order_items
    WHERE order_id = :order_id
    ");

    $itemsStmt->execute([
            ':order_id' => (int) $order->id,
            ]);

    $items = $itemsStmt->fetchAll();

    $this->view('checkout/order', [
            'pageTitle' => 'Order Summary',
            'order' => $order,
            'items' => $items,
            ]);
}

public function payfast(): void
{
    $orderId = (int) ($_POST['order_id'] ?? 0);

    if ($orderId <= 0) {
        $this->redirect('/events');
    }

$db = Database::connect();

$orderStmt = $db->prepare("
    SELECT
    o.*,
    e.title AS event_title,
    e.slug AS event_slug,
    e.event_date
    FROM orders o
    INNER JOIN events e ON e.id = o.event_id
    WHERE o.id = :id
    LIMIT 1
    ");

    $orderStmt->execute([
            ':id' => $orderId,
            ]);

    $order = $orderStmt->fetch();

    if (!$order) {
        $this->redirect('/events');
    }

if ($order->payment_status !== 'pending') {
    $_SESSION['current_order_id'] = $order->id;
    $this->redirect('/checkout/order');
}

$payfast = PayFast::settings();

if ($payfast->merchant_id === '' || $payfast->merchant_key === '' || $payfast->process_url === '') {
    $_SESSION['payment_error'] = 'PayFast is not configured correctly.';
    $_SESSION['current_order_id'] = $order->id;
    $this->redirect('/checkout/order');
}

$config = require __DIR__ . '/../../config/config.php';
$appUrl = rtrim($config['app_url'], '/');

$nameParts = preg_split('/\s+/', trim($order->buyer_name));
$firstName = $nameParts[0] ?? $order->buyer_name;
$lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

$data = [
    'merchant_id' => $payfast->merchant_id,
    'merchant_key' => $payfast->merchant_key,
    'return_url' => $appUrl . '/payment/return',
    'cancel_url' => $appUrl . '/payment/cancel',
    'notify_url' => $appUrl . '/payment/notify',

    'name_first' => $firstName,
    'name_last' => $lastName,
    'email_address' => $order->buyer_email,
    'cell_number' => $order->buyer_phone,

    'm_payment_id' => $order->order_number,
    'amount' => number_format((float) $order->total_amount, 2, '.', ''),
    'item_name' => 'Royal Splash Tickets - ' . $order->order_number,
    'item_description' => $order->event_title,

    'custom_int1' => $order->id,
    'custom_int2' => $order->event_id,
    'custom_str1' => $order->order_number,
    ];

$data['signature'] = PayFast::generateSignature($data, $payfast->passphrase);

$this->view('checkout/payfast-redirect', [
        'pageTitle' => 'Redirecting to PayFast',
        'payfast' => $payfast,
        'order' => $order,
        'data' => $data,
        ]);
}

public function paymentReturn(): void
{
    $order = null;
    $autoLoggedIn = false;

    if (!empty($_SESSION['current_order_id'])) {
        $db = Database::connect();

        $stmt = $db->prepare("
            SELECT
            o.*,
            e.title AS event_title,
            e.event_date,
            e.venue_name
            FROM orders o
            INNER JOIN events e ON e.id = o.event_id
            WHERE o.id = :id
            LIMIT 1
            ");

            $stmt->execute([
                    ':id' => (int) $_SESSION['current_order_id'],
                    ]);

            $order = $stmt->fetch();

            if ($order && $order->payment_status === 'paid' && !empty($order->user_id)) {
                $shouldLoginBuyer = empty($_SESSION['user']) || (($_SESSION['user']->role ?? '') === 'customer');

                if ($shouldLoginBuyer) {
                    $userStmt = $db->prepare("
                        SELECT id, full_name, email, phone, role
                        FROM users
                        WHERE id = :id
                        AND status = 'active'
                        LIMIT 1
                        ");

                        $userStmt->execute([
                                ':id' => (int) $order->user_id,
                                ]);

                        $user = $userStmt->fetch();

                        if ($user) {
                            $_SESSION['user'] = (object) [
                                'id' => $user->id,
                                'full_name' => $user->full_name,
                                'email' => $user->email,
                                'phone' => $user->phone,
                                'role' => $user->role,
                                ];

                            $autoLoggedIn = true;
                        }
                }
        }
}

$this->view('checkout/payment-return', [
        'pageTitle' => 'Payment Status',
        'order' => $order,
        'autoLoggedIn' => $autoLoggedIn,
        ]);
}

public function paymentStatus(): void
{
    header('Content-Type: application/json');

    if (empty($_SESSION['current_order_id'])) {
        echo json_encode([
                'status' => 'error',
                'message' => 'No active order found.',
                'payment_status' => null,
                'redirect_url' => null,
                ]);
        exit;
    }

$db = Database::connect();

$stmt = $db->prepare("
    SELECT *
    FROM orders
    WHERE id = :id
    LIMIT 1
    ");

    $stmt->execute([
            ':id' => (int) $_SESSION['current_order_id'],
            ]);

    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode([
                'status' => 'error',
                'message' => 'Order not found.',
                'payment_status' => null,
                'redirect_url' => null,
                ]);
        exit;
    }

$config = require __DIR__ . '/../../config/config.php';
$appUrl = rtrim($config['app_url'], '/');

if ($order->payment_status === 'paid' && !empty($order->user_id)) {
    $shouldLoginBuyer = empty($_SESSION['user']) || (($_SESSION['user']->role ?? '') === 'customer');

    if ($shouldLoginBuyer) {
        $userStmt = $db->prepare("
            SELECT id, full_name, email, phone, role
            FROM users
            WHERE id = :id
            AND status = 'active'
            LIMIT 1
            ");

            $userStmt->execute([
                    ':id' => (int) $order->user_id,
                    ]);

            $user = $userStmt->fetch();

            if ($user) {
                $_SESSION['user'] = (object) [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    ];
            }
    }

echo json_encode([
        'status' => 'success',
        'message' => 'Payment confirmed.',
        'payment_status' => 'paid',
        'redirect_url' => $appUrl . '/my-tickets',
        ]);
exit;
}

if ($order->payment_status === 'cancelled') {
    echo json_encode([
            'status' => 'cancelled',
            'message' => 'Payment was cancelled.',
            'payment_status' => 'cancelled',
            'redirect_url' => $appUrl . '/payment/cancel',
            ]);
    exit;
}

if ($order->payment_status === 'failed') {
    echo json_encode([
            'status' => 'failed',
            'message' => 'Payment failed.',
            'payment_status' => 'failed',
            'redirect_url' => null,
            ]);
    exit;
}

echo json_encode([
        'status' => 'pending',
        'message' => 'Payment is still being confirmed.',
        'payment_status' => $order->payment_status,
        'redirect_url' => null,
        ]);
exit;
}

public function paymentCancel(): void
{
    $this->view('checkout/payment-cancel', [
            'pageTitle' => 'Payment Cancelled',
            ]);
}

public function paymentNotify(): void
{
    http_response_code(200);

    $pfData = $_POST;

    foreach ($pfData as $key => $value) {
        $pfData[$key] = stripslashes((string) $value);
    }

$db = Database::connect();
$payfast = PayFast::settings();

$orderNumber = $pfData['m_payment_id'] ?? '';
$pfPaymentId = $pfData['pf_payment_id'] ?? '';
$paymentStatus = $pfData['payment_status'] ?? '';
$amountGross = isset($pfData['amount_gross']) ? (float) $pfData['amount_gross'] : 0.00;

try {
    $orderStmt = $db->prepare("
        SELECT *
        FROM orders
        WHERE order_number = :order_number
        LIMIT 1
        ");

        $orderStmt->execute([
                ':order_number' => $orderNumber,
                ]);

        $order = $orderStmt->fetch();

        if (!$order) {
            $this->logPayFast($db, null, $orderNumber, $pfPaymentId, $paymentStatus, $amountGross, 'failed', 'Order not found.', $pfData);
            echo 'OK';
            exit;
        }

    $expectedSignature = PayFast::generateNotifySignature($pfData, $payfast->passphrase);
    $expectedSignatureNoPassphrase = PayFast::generateNotifySignature($pfData, '');
    $receivedSignature = $pfData['signature'] ?? '';

    if (
        $receivedSignature === '' ||
        (
            !hash_equals($expectedSignature, $receivedSignature) &&
            !hash_equals($expectedSignatureNoPassphrase, $receivedSignature)
        )
) {
$debugPayload = $pfData;
$debugPayload['_signature_debug'] = [
    'received' => $receivedSignature,
    'expected_with_passphrase' => $expectedSignature,
    'expected_without_passphrase' => $expectedSignatureNoPassphrase,
    'payfast_mode' => $payfast->mode,
    'used_passphrase' => $payfast->passphrase !== '' ? 'yes' : 'no',
    ];

$this->logPayFast(
    $db,
    $order->id,
    $orderNumber,
    $pfPaymentId,
    $paymentStatus,
    $amountGross,
    'failed',
    'Invalid PayFast signature.',
    $debugPayload
);

echo 'OK';
exit;
}

if (($pfData['merchant_id'] ?? '') !== $payfast->merchant_id) {
    $this->logPayFast($db, $order->id, $orderNumber, $pfPaymentId, $paymentStatus, $amountGross, 'failed', 'Merchant ID mismatch.', $pfData);
    echo 'OK';
    exit;
}

$expectedAmount = number_format((float) $order->total_amount, 2, '.', '');
$receivedAmount = number_format($amountGross, 2, '.', '');

if ($expectedAmount !== $receivedAmount) {
    $this->logPayFast($db, $order->id, $orderNumber, $pfPaymentId, $paymentStatus, $amountGross, 'failed', 'Payment amount mismatch.', $pfData);
    echo 'OK';
    exit;
}

$validWithPayFast = PayFast::validateItnWithPayFast($pfData);

if (!$validWithPayFast) {
    $this->logPayFast($db, $order->id, $orderNumber, $pfPaymentId, $paymentStatus, $amountGross, 'failed', 'PayFast server validation failed.', $pfData);
    echo 'OK';
    exit;
}

if ($paymentStatus === 'COMPLETE') {
    $this->markOrderPaid($db, (int) $order->id, $pfPaymentId);
    $this->logPayFast($db, $order->id, $orderNumber, $pfPaymentId, $paymentStatus, $amountGross, 'verified', 'Payment completed and order marked as paid.', $pfData);
} elseif ($paymentStatus === 'CANCELLED') {
$this->markOrderCancelled($db, (int) $order->id, $pfPaymentId);
$this->logPayFast($db, $order->id, $orderNumber, $pfPaymentId, $paymentStatus, $amountGross, 'verified', 'Payment cancelled.', $pfData);
} else {
$this->logPayFast($db, $order->id, $orderNumber, $pfPaymentId, $paymentStatus, $amountGross, 'received', 'Payment status received but not completed.', $pfData);
}
} catch (\Throwable $e) {
$this->logPayFast($db, null, $orderNumber, $pfPaymentId, $paymentStatus, $amountGross, 'error', $e->getMessage(), $pfData);
}

echo 'OK';
exit;
}

public function checkDuplicatePurchase(): void
{
    header('Content-Type: application/json');

    $checkout = $_SESSION['checkout'] ?? null;

    $email = strtolower(trim($_POST['buyer_email'] ?? $_POST['email'] ?? ''));

    $postedEventId = (int) ($_POST['event_id'] ?? 0);
    $sessionEventId = (int) ($checkout->event_id ?? 0);

    $eventId = $postedEventId > 0 ? $postedEventId : $sessionEventId;

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $eventId <= 0) {
        echo json_encode([
                'duplicate' => false,
                'message' => '',
                'order' => null,
                ]);
        exit;
    }

$db = Database::connect();

$recentOrder = $this->findRecentPaidOrder($db, $eventId, $email);

if (!$recentOrder) {
    echo json_encode([
            'duplicate' => false,
            'message' => '',
            'order' => null,
            ]);
    exit;
}

echo json_encode([
        'duplicate' => true,
        'message' => 'You already purchased tickets for this event recently. Are you sure you want to buy more?',
        'order' => [
            'order_number' => $recentOrder->order_number,
            'total_qty' => (int) $recentOrder->total_qty,
            'total_amount' => 'R' . number_format((float) $recentOrder->total_amount, 2),
            'created_at' => $recentOrder->created_at,
            ],
        ]);
exit;
}

private function findRecentPaidOrder(PDO $db, int $eventId, string $buyerEmail, int $minutes = 30): ?object
{
    $minutes = max(1, (int) $minutes);

    $stmt = $db->prepare("
        SELECT
        id,
        order_number,
        buyer_email,
        total_qty,
        total_amount,
        payment_status,
        payfast_payment_id,
        created_at,
        paid_at
        FROM orders
        WHERE event_id = :event_id
        AND LOWER(buyer_email) = LOWER(:buyer_email)
        AND payment_status = 'paid'
        AND COALESCE(paid_at, created_at) >= DATE_SUB(NOW(), INTERVAL $minutes MINUTE)
        ORDER BY id DESC
        LIMIT 1
        ");

        $stmt->execute([
                ':event_id' => $eventId,
                ':buyer_email' => $buyerEmail,
                ]);

        $order = $stmt->fetch();

        return $order ?: null;
    }

private function markOrderPaid(PDO $db, int $orderId, string $pfPaymentId): void
{
    $db->beginTransaction();

    try {
        $orderStmt = $db->prepare("
            SELECT *
            FROM orders
            WHERE id = :id
            LIMIT 1
            FOR UPDATE
            ");

            $orderStmt->execute([
                    ':id' => $orderId,
                    ]);

            $order = $orderStmt->fetch();

            if (!$order) {
                throw new \RuntimeException('Order not found while marking paid.');
            }

        if ($order->payment_status === 'paid') {
            $db->commit();
            $this->processTicketFilesAndEmail($db, $orderId);
            return;
        }

    $userId = $this->ensureCustomerAccountForOrder($db, $order);

    $itemsStmt = $db->prepare("
        SELECT *
        FROM order_items
        WHERE order_id = :order_id
        ");

        $itemsStmt->execute([
                ':order_id' => $orderId,
                ]);

        $items = $itemsStmt->fetchAll();

        foreach ($items as $item) {
            $updateCategory = $db->prepare("
                UPDATE ticket_categories
                SET quantity_sold = quantity_sold + :qty
                WHERE id = :category_id
                AND event_id = :event_id
                ");

                $updateCategory->execute([
                        ':qty' => (int) $item->quantity,
                        ':category_id' => (int) $item->ticket_category_id,
                        ':event_id' => (int) $item->event_id,
                        ]);
            }

        $updateOrder = $db->prepare("
            UPDATE orders
            SET
            user_id = :user_id,
            payment_status = 'paid',
            payfast_payment_id = :pf_payment_id,
            paid_at = NOW()
            WHERE id = :id
            ");

            $updateOrder->execute([
                    ':user_id' => $userId,
                    ':pf_payment_id' => $pfPaymentId,
                    ':id' => $orderId,
                    ]);

            $this->generateTicketsForPaidOrder($db, (int) $order->id, $userId);

            $db->commit();
    } catch (\Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

throw $e;
}

$this->processTicketFilesAndEmail($db, $orderId);
}

private function processTicketFilesAndEmail(PDO $db, int $orderId): void
{
    try {
        $this->generateMissingTicketPdfsForOrder($db, $orderId);
        TicketEmailService::sendTicketsForOrder($db, $orderId);
} catch (\Throwable $e) {
$this->saveTicketEmailError($db, $orderId, $e->getMessage());
}
}

private function saveTicketEmailError(PDO $db, int $orderId, string $error): void
{
    $stmt = $db->prepare("
        UPDATE orders
        SET tickets_email_error = :error
        WHERE id = :id
        ");

        $stmt->execute([
                ':error' => $error,
                ':id' => $orderId,
                ]);
    }

private function markOrderCancelled(PDO $db, int $orderId, string $pfPaymentId): void
{
    $stmt = $db->prepare("
        UPDATE orders
        SET
        payment_status = 'cancelled',
        payfast_payment_id = :pf_payment_id
        WHERE id = :id
        AND payment_status = 'pending'
        ");

        $stmt->execute([
                ':pf_payment_id' => $pfPaymentId,
                ':id' => $orderId,
                ]);
    }

private function ensureCustomerAccountForOrder(PDO $db, object $order): int
{
    if (!empty($order->user_id)) {
        return (int) $order->user_id;
    }

$email = strtolower(trim($order->buyer_email));

$stmt = $db->prepare("
    SELECT *
    FROM users
    WHERE email = :email
    LIMIT 1
    ");

    $stmt->execute([
            ':email' => $email,
            ]);

    $user = $stmt->fetch();

    if ($user) {
        return (int) $user->id;
    }

$randomPassword = bin2hex(random_bytes(32));
$passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);

$insert = $db->prepare("
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

$insert->execute([
        ':full_name' => $order->buyer_name,
        ':email' => $email,
        ':phone' => $order->buyer_phone,
        ':password' => $passwordHash,
        ]);

$userId = (int) $db->lastInsertId();

try {
    AccountSetupService::sendSetPasswordEmail($db, $userId);
} catch (\Throwable $e) {
// Do not break payment confirmation if account setup email fails.
}

return $userId;
}

private function logPayFast(
    PDO $db,
    ?int $orderId,
    ?string $orderNumber,
    ?string $pfPaymentId,
    ?string $paymentStatus,
    ?float $amountGross,
    string $verificationStatus,
    string $message,
    array $payload
): void {
$stmt = $db->prepare("
    INSERT INTO payfast_logs (
        order_id,
        order_number,
        pf_payment_id,
        payment_status,
        amount_gross,
        verification_status,
        message,
        raw_payload
    ) VALUES (
    :order_id,
    :order_number,
    :pf_payment_id,
    :payment_status,
    :amount_gross,
    :verification_status,
    :message,
    :raw_payload
)
");

$stmt->execute([
        ':order_id' => $orderId,
        ':order_number' => $orderNumber,
        ':pf_payment_id' => $pfPaymentId,
        ':payment_status' => $paymentStatus,
        ':amount_gross' => $amountGross,
        ':verification_status' => $verificationStatus,
        ':message' => $message,
        ':raw_payload' => json_encode($payload),
        ]);
}

private function generateTicketsForPaidOrder(PDO $db, int $orderId, int $userId): void
{
    $existingStmt = $db->prepare("
        SELECT COUNT(*) AS total
        FROM tickets
        WHERE order_id = :order_id
        ");

        $existingStmt->execute([
                ':order_id' => $orderId,
                ]);

        $existing = (int) $existingStmt->fetch()->total;

        if ($existing > 0) {
            return;
        }

    $orderStmt = $db->prepare("
        SELECT *
        FROM orders
        WHERE id = :id
        LIMIT 1
        ");

        $orderStmt->execute([
                ':id' => $orderId,
                ]);

        $order = $orderStmt->fetch();

        if (!$order) {
            throw new \RuntimeException('Order not found while generating tickets.');
        }

    $itemsStmt = $db->prepare("
        SELECT *
        FROM order_items
        WHERE order_id = :order_id
        ");

        $itemsStmt->execute([
                ':order_id' => $orderId,
                ]);

        $items = $itemsStmt->fetchAll();

        foreach ($items as $item) {
            for ($i = 1; $i <= (int) $item->quantity; $i++) {
                $ticketNumber = $this->generateTicketNumber($db);
                $qrToken = $this->generateQrToken($db);

                $ticketStmt = $db->prepare("
                    INSERT INTO tickets (
                        ticket_number,
                        order_id,
                        order_item_id,
                        event_id,
                        ticket_category_id,
                        user_id,
                        buyer_name,
                        buyer_email,
                        ticket_name,
                        qr_token,
                        status
                    ) VALUES (
                    :ticket_number,
                    :order_id,
                    :order_item_id,
                    :event_id,
                    :ticket_category_id,
                    :user_id,
                    :buyer_name,
                    :buyer_email,
                    :ticket_name,
                    :qr_token,
                    'valid'
                )
            ");

            $ticketStmt->execute([
                    ':ticket_number' => $ticketNumber,
                    ':order_id' => $orderId,
                    ':order_item_id' => $item->id,
                    ':event_id' => $item->event_id,
                    ':ticket_category_id' => $item->ticket_category_id,
                    ':user_id' => $userId,
                    ':buyer_name' => $order->buyer_name,
                    ':buyer_email' => $order->buyer_email,
                    ':ticket_name' => $item->ticket_name,
                    ':qr_token' => $qrToken,
                    ]);
        }
}
}

private function generateTicketNumber(PDO $db): string
{
    do {
        $ticketNumber = 'RS-TKT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

        $stmt = $db->prepare("
            SELECT id
            FROM tickets
            WHERE ticket_number = :ticket_number
            LIMIT 1
            ");

            $stmt->execute([
                    ':ticket_number' => $ticketNumber,
                    ]);

            $exists = $stmt->fetch();
    } while ($exists);

return $ticketNumber;
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

private function generateMissingTicketPdfsForOrder(PDO $db, int $orderId): void
{
    $stmt = $db->prepare("
        SELECT id
        FROM tickets
        WHERE order_id = :order_id
        AND (pdf_path IS NULL OR pdf_path = '')
        ");

        $stmt->execute([
                ':order_id' => $orderId,
                ]);

        $tickets = $stmt->fetchAll();

        foreach ($tickets as $ticket) {
            TicketPdfService::generateForTicketId($db, (int) $ticket->id);
        }
}

private function generateOrderNumber(PDO $db): string
{
    do {
        $orderNumber = 'RS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $stmt = $db->prepare("
            SELECT id
            FROM orders
            WHERE order_number = :order_number
            LIMIT 1
            ");

            $stmt->execute([
                    ':order_number' => $orderNumber,
                    ]);

            $exists = $stmt->fetch();
    } while ($exists);

return $orderNumber;
}
}
