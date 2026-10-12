<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\StockMovement;

class DashboardController
{
    public function index(Request $request): void
    {
        Auth::requireLogin();

        $stats = StockMovement::getDashboardStats();

        Response::view('dashboard/index', [
            'title' => 'Dashboard - Inventory System',
            'stats' => $stats,
            'user' => Auth::user(),
            'flash' => Response::getFlash()
        ]);
    }
}
