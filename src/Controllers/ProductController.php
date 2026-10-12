<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use Throwable;

class ProductController
{
    public function index(Request $request): void
    {
        Auth::requireLogin();

        $page = $request->getInt('page', 1);
        $search = trim((string)$request->get('search', ''));
        $categoryId = $request->getInt('category_id', 0);
        $lowStock = $request->getInt('low_stock', 0);
        $sortBy = (string)$request->get('sort', 'name');
        $sortDir = (string)$request->get('dir', 'ASC');

        $filters = [
            'search' => $search,
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'low_stock' => $lowStock === 1,
        ];

        $pagination = Product::paginate($filters, $page, 10, $sortBy, $sortDir);
        $categories = Category::getAll();

        Response::view('products/index', [
            'title' => 'Daftar Produk - Inventory System',
            'pagination' => $pagination,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'low_stock' => $lowStock,
                'sort' => $sortBy,
                'dir' => $sortDir,
            ],
            'flash' => Response::getFlash()
        ]);
    }

    public function show(Request $request, array $params): void
    {
        Auth::requireLogin();
        $id = (int)($params['id'] ?? 0);
        $product = Product::findById($id);

        if (!$product) {
            Response::setFlash('danger', 'Produk tidak ditemukan.');
            Response::redirect('/products');
            return;
        }

        $suppliers = Product::getSuppliers($id);
        $history = StockMovement::getHistory(['product_id' => $id], 1, 10);

        Response::view('products/show', [
            'title' => "Detail Produk: {$product['name']}",
            'product' => $product,
            'suppliers' => $suppliers,
            'history' => $history,
            'flash' => Response::getFlash()
        ]);
    }

    public function create(Request $request): void
    {
        Auth::requireAdmin();

        $categories = Category::getAll();
        $suppliers = Supplier::getAll();

        Response::view('products/create', [
            'title' => 'Tambah Produk Baru',
            'categories' => $categories,
            'suppliers' => $suppliers,
            'old' => [],
            'flash' => Response::getFlash()
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requireAdmin();

        $sku = strtoupper(trim((string)$request->post('sku', '')));
        $name = trim((string)$request->post('name', ''));
        $categoryId = $request->postInt('category_id');
        $price = $request->postFloat('price');
        $stock = $request->postInt('stock');
        $minStock = $request->postInt('minimum_stock', 5);

        $validator = Validator::make([
            'sku' => $sku,
            'name' => $name,
            'category_id' => $categoryId,
            'price' => $price,
            'stock' => $stock,
            'minimum_stock' => $minStock
        ])
            ->required('sku', 'SKU')
            ->required('name', 'Nama Produk')
            ->positiveNumber('category_id', 'Kategori')
            ->positiveNumber('price', 'Harga Jual')
            ->positiveNumber('stock', 'Stok Awal', true)
            ->positiveNumber('minimum_stock', 'Stok Minimum', true);

        // Validasi keunikan SKU (PRD 3.1)
        if (Product::findBySku($sku)) {
            $validator->custom(false, 'sku', "SKU '{$sku}' sudah digunakan oleh produk lain.");
        }

        if ($validator->fails()) {
            Response::setFlash('danger', $validator->firstError());
            Response::view('products/create', [
                'title' => 'Tambah Produk Baru',
                'categories' => Category::getAll(),
                'suppliers' => Supplier::getAll(),
                'old' => $request->all(),
                'flash' => Response::getFlash()
            ]);
            return;
        }

        // Tangkap data supplier pilihan (Many-to-Many)
        $supplierIds = (array)$request->post('supplier_ids', []);
        $supplierPrices = (array)$request->post('supplier_prices', []);

        $supplierData = [];
        foreach ($supplierIds as $sId) {
            $sId = (int)$sId;
            if ($sId > 0) {
                $supplierData[] = [
                    'supplier_id' => $sId,
                    'supplier_price' => !empty($supplierPrices[$sId]) ? (float)$supplierPrices[$sId] : null
                ];
            }
        }

        try {
            $newId = Product::create([
                'category_id' => $categoryId,
                'sku' => $sku,
                'name' => $name,
                'price' => $price,
                'stock' => $stock,
                'minimum_stock' => $minStock,
                'user_id' => Auth::id()
            ], $supplierData);

            Response::setFlash('success', "Produk '{$name}' berhasil ditambahkan.");
            Response::redirect("/products/show/{$newId}");
        } catch (Throwable $e) {
            Response::setFlash('danger', "Gagal menambahkan produk: " . $e->getMessage());
            Response::redirect('/products/create');
        }
    }

    public function edit(Request $request, array $params): void
    {
        Auth::requireAdmin();
        $id = (int)($params['id'] ?? 0);
        $product = Product::findById($id);

        if (!$product) {
            Response::setFlash('danger', 'Produk tidak ditemukan.');
            Response::redirect('/products');
            return;
        }

        $categories = Category::getAll();
        $suppliers = Supplier::getAll();
        $assignedSuppliers = Product::getSuppliers($id);

        Response::view('products/edit', [
            'title' => "Edit Produk: {$product['name']}",
            'product' => $product,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'assignedSuppliers' => $assignedSuppliers,
            'flash' => Response::getFlash()
        ]);
    }

    public function update(Request $request, array $params): void
    {
        Auth::requireAdmin();
        $id = (int)($params['id'] ?? 0);
        $product = Product::findById($id);

        if (!$product) {
            Response::setFlash('danger', 'Produk tidak ditemukan.');
            Response::redirect('/products');
            return;
        }

        $sku = strtoupper(trim((string)$request->post('sku', '')));
        $name = trim((string)$request->post('name', ''));
        $categoryId = $request->postInt('category_id');
        $price = $request->postFloat('price');
        $minStock = $request->postInt('minimum_stock', 5);

        $validator = Validator::make([
            'sku' => $sku,
            'name' => $name,
            'category_id' => $categoryId,
            'price' => $price,
            'minimum_stock' => $minStock
        ])
            ->required('sku', 'SKU')
            ->required('name', 'Nama Produk')
            ->positiveNumber('category_id', 'Kategori')
            ->positiveNumber('price', 'Harga Jual')
            ->positiveNumber('minimum_stock', 'Stok Minimum', true);

        // Validasi SKU unik jika diubah
        $existing = Product::findBySku($sku);
        if ($existing && (int)$existing['id'] !== $id) {
            $validator->custom(false, 'sku', "SKU '{$sku}' sudah digunakan produk lain.");
        }

        if ($validator->fails()) {
            Response::setFlash('danger', $validator->firstError());
            Response::redirect("/products/edit/{$id}");
            return;
        }

        // Supplier data
        $supplierIds = (array)$request->post('supplier_ids', []);
        $supplierPrices = (array)$request->post('supplier_prices', []);

        $supplierData = [];
        foreach ($supplierIds as $sId) {
            $sId = (int)$sId;
            if ($sId > 0) {
                $supplierData[] = [
                    'supplier_id' => $sId,
                    'supplier_price' => !empty($supplierPrices[$sId]) ? (float)$supplierPrices[$sId] : null
                ];
            }
        }

        try {
            Product::update($id, [
                'category_id' => $categoryId,
                'sku' => $sku,
                'name' => $name,
                'price' => $price,
                'minimum_stock' => $minStock
            ], $supplierData);

            Response::setFlash('success', "Data produk '{$name}' berhasil diperbarui.");
            Response::redirect("/products/show/{$id}");
        } catch (Throwable $e) {
            Response::setFlash('danger', "Gagal memperbarui produk: " . $e->getMessage());
            Response::redirect("/products/edit/{$id}");
        }
    }

    public function delete(Request $request, array $params): void
    {
        Auth::requireAdmin();
        $id = (int)($params['id'] ?? 0);
        $product = Product::findById($id);

        if (!$product) {
            Response::setFlash('danger', 'Produk tidak ditemukan.');
            Response::redirect('/products');
            return;
        }

        // Aturan Bisnis PRD 3.5: Produk yang memiliki histori mutasi tidak boleh dihapus sembarangan
        if (Product::hasMovements($id)) {
            Response::setFlash('danger', "Produk '{$product['name']}' tidak dapat dihapus karena memiliki riwayat transaksi mutasi stok.");
            Response::redirect('/products');
            return;
        }

        try {
            Product::delete($id);
            Response::setFlash('success', "Produk '{$product['name']}' berhasil dihapus.");
        } catch (Throwable $e) {
            Response::setFlash('danger', "Gagal menghapus produk: " . $e->getMessage());
        }

        Response::redirect('/products');
    }
}
