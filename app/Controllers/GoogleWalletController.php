<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\GoogleWalletService;

class GoogleWalletController extends Controller
{
    public function add(): void
    {
        $this->requireRole('customer');

        $ticketId = (int) ($_GET['ticket_id'] ?? 0);

        if ($ticketId <= 0) {
            $_SESSION['ticket_error'] = 'Invalid Google Wallet request.';
            $this->redirect('/my-tickets');
        }

        $user = $this->authUser();
        $db = Database::connect();

        try {
            $saveLink = GoogleWalletService::createSaveLinkForTicket(
                $db,
                $ticketId,
                (int) $user->id
            );

            header('Location: ' . $saveLink);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['ticket_error'] = $e->getMessage();
            $this->redirect('/my-tickets');
        }
    }
}