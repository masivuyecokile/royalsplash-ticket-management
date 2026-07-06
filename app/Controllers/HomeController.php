<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class HomeController extends Controller
{
    public function index(): void
    {
        $db = Database::connect();

        $stmt = $db->query("SELECT * FROM system_checks ORDER BY id DESC LIMIT 1");
        $check = $stmt->fetch();

        $this->view('home/index', [
                'pageTitle' => 'Royal Splash Ticketing',
                'dbStatus' => $check->check_value ?? 'Database connected, but no check record found.',
                ]);
    }
}
