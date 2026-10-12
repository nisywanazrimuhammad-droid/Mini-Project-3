<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\Category;
use App\Models\StockMovement;

class ReportController
{
    public function inventory(Request $request): void
    {
        Auth::requireLogin();

        $categoryId = $request->getInt('category_id', 0);
        $categories = Category::getAll();

        $report = StockMovement::getInventoryReport($categoryId > 0 ? $categoryId : null);

        // Agregat total keseluruhan
        $totalVal = 0;
        $totalStock = 0;
        $totalIn = 0;
        $totalOut = 0;

        foreach ($report as $r) {
            $totalVal += (float)$r['inventory_value'];
            $totalStock += (int)$r['stock'];
            $totalIn += (int)$r['total_in'];
            $totalOut += (int)$r['total_out'];
        }

        Response::view('reports/inventory', [
            'title' => 'Laporan Inventory Agregat - Inventory System',
            'report' => $report,
            'categories' => $categories,
            'selectedCategoryId' => $categoryId,
            'summary' => [
                'total_val' => $totalVal,
                'total_stock' => $totalStock,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
            ],
            'flash' => Response::getFlash()
        ]);
    }
}
