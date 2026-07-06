<?php

namespace App\Core;

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\EventController;
use App\Controllers\TicketCategoryController;
use App\Controllers\PublicEventController;
use App\Controllers\CheckoutController;
use App\Controllers\TicketController;
use App\Controllers\ScannerController;
use App\Controllers\PasswordSetupController;
use App\Controllers\ReportController;
use App\Controllers\AdminOrderController;
use App\Controllers\AdminTicketController;
use App\Controllers\AdminScannerController;
use App\Controllers\ForgotPasswordController;
use App\Controllers\TicketTransferController;
use App\Controllers\GoogleWalletController;


class App
{
    public function run(): void
    {
        $router = new Router();

        // Public pages
        $router->get('/', [PublicEventController::class, 'index']);
        $router->get('/events', [PublicEventController::class, 'index']);
        $router->get('/event', [PublicEventController::class, 'show']);

        // Auth
        $router->get('/register', [AuthController::class, 'showRegister']);
        $router->post('/register', [AuthController::class, 'register']);

        $router->get('/login', [AuthController::class, 'showLogin']);
        $router->post('/login', [AuthController::class, 'login']);

        $router->get('/logout', [AuthController::class, 'logout']);

        // Password setup
        $router->get('/set-password', [PasswordSetupController::class, 'show']);
        $router->post('/set-password', [PasswordSetupController::class, 'update']);

        // Customer forgot password
        $router->get('/forgot-password', [ForgotPasswordController::class, 'showRequest']);
        $router->post('/forgot-password', [ForgotPasswordController::class, 'sendLink']);
        $router->get('/reset-password', [ForgotPasswordController::class, 'showReset']);
        $router->post('/reset-password', [ForgotPasswordController::class, 'updatePassword']);

        // Dashboards
        $router->get('/dashboard', [DashboardController::class, 'index']);
        $router->get('/admin', [DashboardController::class, 'admin']);

        // Admin orders
        $router->get('/admin/orders', [AdminOrderController::class, 'index']);
        $router->get('/admin/orders/view', [AdminOrderController::class, 'show']);
        $router->post('/admin/orders/resend-tickets', [AdminOrderController::class, 'resendTickets']);
        $router->post('/admin/orders/resend-password', [AdminOrderController::class, 'resendPasswordSetup']);

        // Admin tickets
        $router->get('/admin/tickets', [AdminTicketController::class, 'index']);
        $router->get('/admin/tickets/view', [AdminTicketController::class, 'show']);
        $router->post('/admin/tickets/status', [AdminTicketController::class, 'updateStatus']);

        // Admin reports
        $router->get('/admin/reports/scans', [ReportController::class, 'scanReports']);
        $router->get('/admin/reports/scans/export', [ReportController::class, 'exportScanReports']);


        // Admin scanner users
        $router->get('/admin/scanners', [AdminScannerController::class, 'index']);
        $router->get('/admin/scanners/edit', [AdminScannerController::class, 'edit']);
        $router->post('/admin/scanners/create', [AdminScannerController::class, 'store']);
        $router->post('/admin/scanners/edit', [AdminScannerController::class, 'update']);
        $router->post('/admin/scanners/status', [AdminScannerController::class, 'updateStatus']);
        $router->post('/admin/scanners/resend-password', [AdminScannerController::class, 'resendPasswordSetup']);


        // Scanner
        $router->get('/scanner', [ScannerController::class, 'index']);
        $router->post('/scanner/validate', [ScannerController::class, 'validate']);

        // Customer tickets
        $router->get('/my-tickets', [TicketController::class, 'index']);
        $router->get('/ticket', [TicketController::class, 'show']);
        $router->get('/ticket/download', [TicketController::class, 'download']);
        $router->get('/ticket/google-wallet', [GoogleWalletController::class, 'add']);

        // Ticket transfers
        $router->get('/ticket-transfer', [TicketTransferController::class, 'showCreate']);
        $router->post('/ticket-transfer/create', [TicketTransferController::class, 'store']);
        $router->get('/ticket-transfer/accept', [TicketTransferController::class, 'showAccept']);
        $router->post('/ticket-transfer/accept', [TicketTransferController::class, 'accept']);
        $router->post('/ticket-transfer/cancel', [TicketTransferController::class, 'cancel']);


        // Admin events
        $router->get('/admin/events', [EventController::class, 'index']);
        $router->get('/admin/events/create', [EventController::class, 'create']);
        $router->post('/admin/events/create', [EventController::class, 'store']);

        $router->get('/admin/events/edit', [EventController::class, 'edit']);
        $router->post('/admin/events/edit', [EventController::class, 'update']);
        $router->post('/admin/events/status', [EventController::class, 'updateStatus']);

        // Admin ticket categories
        $router->get('/admin/ticket-categories', [TicketCategoryController::class, 'index']);
        $router->post('/admin/ticket-categories', [TicketCategoryController::class, 'store']);
        $router->get('/admin/ticket-categories/edit', [TicketCategoryController::class, 'edit']);
        $router->post('/admin/ticket-categories/edit', [TicketCategoryController::class, 'update']);
        $router->post('/admin/ticket-categories/status', [TicketCategoryController::class, 'updateStatus']);

        // Checkout
        $router->post('/checkout/start', [CheckoutController::class, 'start']);
        $router->get('/checkout/start', [CheckoutController::class, 'startRedirect']);
        $router->get('/checkout', [CheckoutController::class, 'show']);
        $router->post('/checkout/create-order', [CheckoutController::class, 'createOrder']);
        $router->post('/checkout/check-duplicate', [CheckoutController::class, 'checkDuplicatePurchase']);
        $router->get('/checkout/order', [CheckoutController::class, 'order']);

        // PayFast
        $router->post('/checkout/payfast', [CheckoutController::class, 'payfast']);
        $router->get('/payment/return', [CheckoutController::class, 'paymentReturn']);
        $router->get('/payment/status', [CheckoutController::class, 'paymentStatus']);
        $router->get('/payment/cancel', [CheckoutController::class, 'paymentCancel']);
        $router->post('/payment/notify', [CheckoutController::class, 'paymentNotify']);

        $router->dispatch();
    }
}
