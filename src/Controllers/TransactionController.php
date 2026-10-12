<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use Throwable;

class TransactionController
{
    public function index(Request $request): void
    {
        Auth::requireLogin();

        $page = $request->getInt('page', 1);
        $productId = $request->getInt('product_id');
        $type = strtoupper((string)$request->get('type', ''));

        $filters = [
            'product_id' => $productId > 0 ? $productId : null,
            'type' => in_array($type, ['IN', 'OUT'], true) ? $type : null,
        ];

        $history = StockMovement::getHistory($filters, $page, 15);
        $products = Product::getAllSimple();

        Response::view('transactions/index', [
            'title' => 'Riwayat Mutasi Stok - Inventory System',
            'history' => $history,
            'products' => $products,
            'filters' => [
                'product_id' => $productId,
                'type' => $type,
            ],
            'flash' => Response::getFlash()
        ]);
    }

    public function showInForm(Request $request): void
    {
        Auth::requireLogin();

        $productId = $request->getInt('product_id');
        $products = Product::getAllSimple();

        Response::view('transactions/in', [
            'title' => 'Catat Barang Masuk (Stock In)',
            'products' => $products,
            'selectedProductId' => $productId,
            'flash' => Response::getFlash()
        ]);
    }

    public function storeIn(Request $request): void
    {
        Auth::requireLogin();

        $productId = $request->postInt('product_id');
        $quantity = $request->postInt('quantity');
        $notes = trim((string)$request->post('notes', ''));

        $validator = Validator::make([
            'product_id' => $productId,
            'quantity' => $quantity,
        ])
            ->positiveNumber('product_id', 'Produk')
            ->positiveNumber('quantity', 'Jumlah Barang Masuk');

        if ($validator->fails()) {
            Response::setFlash('danger', $validator->firstError());
            Response::redirect('/transactions/in');
            return;
        }

        try {
            StockMovement::record(
                $productId,
                Auth::id(),
                'IN',
                $quantity,
                $notes ?: 'Barang Masuk reguler'
            );

            Response::setFlash('success', "Berhasil menambahkan {$quantity} unit barang masuk!");
            Response::redirect('/transactions');
        } catch (Throwable $e) {
            Response::setFlash('danger', "Gagal mencatat transaksi: " . $e->getMessage());
            Response::redirect('/transactions/in');
        }
    }

    public function showOutForm(Request $request): void
    {
        Auth::requireLogin();

        $productId = $request->getInt('product_id');
        $products = Product::getAllSimple();

        Response::view('transactions/out', [
            'title' => 'Catat Barang Keluar (Stock Out)',
            'products' => $products,
            'selectedProductId' => $productId,
            'flash' => Response::getFlash()
        ]);
    }

    public function storeOut(Request $request): void
    {
        Auth::requireLogin();

        $productId = $request->postInt('product_id');
        $quantity = $request->postInt('quantity');
        $notes = trim((string)$request->post('notes', ''));

        $validator = Validator::make([
            'product_id' => $productId,
            'quantity' => $quantity,
        ])
            ->positiveNumber('product_id', 'Produk')
            ->positiveNumber('quantity', 'Jumlah Barang Keluar');

        if ($validator->fails()) {
            Response::setFlash('danger', $validator->firstError());
            Response::redirect('/transactions/out');
            return;
        }

        try {
            StockMovement::record(
                $productId,
                Auth::id(),
                'OUT',
                $quantity,
                $notes ?: 'Barang Keluar / Distribusi'
            );

            Response::setFlash('success', "Berhasil mengeluarkan {$quantity} unit barang!");
            Response::redirect('/transactions');
        } catch (Throwable $e) {
            // Menangkap pesan validasi stok tidak cukup dari model
            Response::setFlash('danger', "Gagal mencatat barang keluar: " . $e->getMessage());
            Response::redirect('/transactions/out');
        }
    }
}
