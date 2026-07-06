<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $this->view('dashboard/customer', [
                'pageTitle' => 'My Account',
                'user' => $this->authUser(),
                ]);
    }

public function admin(): void
{
    $this->requireRole('admin');

    $db = Database::connect();

    $eventsCount = (int) $db->query("
        SELECT COUNT(*) AS total
        FROM events
        ")->fetch()->total;

        $ticketsSoldResult = $db->query("
            SELECT COALESCE(SUM(oi.quantity), 0) AS total
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            WHERE o.payment_status = 'paid'
            ")->fetch();

            $ticketsSold = (int) $ticketsSoldResult->total;

            $revenueResult = $db->query("
                SELECT COALESCE(SUM(total_amount), 0) AS total
                FROM orders
                WHERE payment_status = 'paid'
                ")->fetch();

                $revenue = (float) $revenueResult->total;

                $ordersStmt = $db->query("
                    SELECT
                    o.id,
                    o.order_number,
                    o.buyer_name,
                    o.buyer_email,
                    o.total_qty,
                    o.total_amount,
                    o.payment_status,
                    o.created_at,
                    e.title AS event_title
                    FROM orders o
                    INNER JOIN events e ON e.id = o.event_id
                    ORDER BY o.id DESC
                    LIMIT 5
                    ");

                    $recentOrders = $ordersStmt->fetchAll();

                    $this->view('dashboard/admin', [
                            'pageTitle' => 'Admin Dashboard',
                            'user' => $this->authUser(),
                            'eventsCount' => $eventsCount,
                            'ticketsSold' => $ticketsSold,
                            'revenue' => $revenue,
                            'recentOrders' => $recentOrders,
                            ]);
                }

            public function scanner(): void
            {
                $this->requireRole('scanner');

                $this->view('dashboard/scanner', [
                        'pageTitle' => 'Scanner Dashboard',
                        'user' => $this->authUser(),
                        ]);
            }
    }
