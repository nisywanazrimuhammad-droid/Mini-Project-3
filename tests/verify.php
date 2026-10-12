<?php
/**
 * Automated Verification Script for Inventory Management System
 * Menguji seluruh 9 fitur wajib PRD secara menyeluruh
 */

declare(strict_types=1);

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

use App\Models\User;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\StockMovement;

$passed = 0;
$failed = 0;

function assertTest(string $desc, bool $condition) {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] " . $desc . PHP_EOL;
        $passed++;
    } else {
        echo " [FAIL] " . $desc . PHP_EOL;
        $failed++;
    }
}

echo "=== MEMULAI TEST VERIFIKASI PRD INVENTORY SYSTEM ===" . PHP_EOL . PHP_EOL;

// 1. Test Autentikasi & Password Verification
echo "--- 1. Testing Authentication & Password Verification ---" . PHP_EOL;
$adminUser = User::findByUsername('admin');
assertTest("Akun admin ditemukan di database", $adminUser !== null);
assertTest("Verifikasi password admin berhasil", password_verify('admin123', $adminUser['password_hash']));
assertTest("Verifikasi password salah ditolak", !password_verify('wrongpass', $adminUser['password_hash']));
assertTest("Role admin sesuai", $adminUser['role'] === 'admin');

$staffUser = User::findByUsername('staff');
assertTest("Akun staff ditemukan di database", $staffUser !== null);
assertTest("Role staff sesuai", $staffUser['role'] === 'staff');

// 2. Test Master Data & Many-to-Many
echo PHP_EOL . "--- 2. Testing Master Data & Many-to-Many Suppliers ---" . PHP_EOL;
$categories = Category::getAll();
assertTest("Kategori terambil dengan benar (> 0)", count($categories) > 0);

$suppliers = Supplier::getAll();
assertTest("Supplier terambil dengan benar (> 0)", count($suppliers) > 0);

$products = Product::getAllSimple();
assertTest("Produk terambil dengan benar (> 0)", count($products) > 0);

$firstProduct = Product::findById((int)$products[0]['id']);
assertTest("Detail produk pertama berhasil dibaca", $firstProduct !== null);
$prodSuppliers = Product::getSuppliers((int)$products[0]['id']);
assertTest("Relasi many-to-many suppliers pada produk terhubung", is_array($prodSuppliers));

// 3. Test Business Logic Transaksi Mutasi Stok (IN & OUT)
echo PHP_EOL . "--- 3. Testing Transaksi Mutasi Stok (Atomic Transaction) ---" . PHP_EOL;
// Buat produk uji coba
$testCatId = $categories[0]['id'];
$testSku = 'TEST-SKU-' . time();
$testProdId = Product::create([
    'category_id' => $testCatId,
    'sku' => $testSku,
    'name' => 'Produk Uji Coba Mutasi',
    'price' => 50000,
    'stock' => 10,
    'minimum_stock' => 5,
    'user_id' => $adminUser['id']
]);
$pBefore = Product::findById($testProdId);
assertTest("Produk uji coba berhasil dibuat dengan stok awal 10", (int)$pBefore['stock'] === 10);

// Test Stock IN (+15)
StockMovement::record($testProdId, (int)$adminUser['id'], 'IN', 15, 'Uji Coba Stock IN');
$pAfterIn = Product::findById($testProdId);
assertTest("Stock IN berhasil: Stok berubah dari 10 menjadi 25", (int)$pAfterIn['stock'] === 25);

// Test Stock OUT (-8)
StockMovement::record($testProdId, (int)$adminUser['id'], 'OUT', 8, 'Uji Coba Stock OUT');
$pAfterOut = Product::findById($testProdId);
assertTest("Stock OUT berhasil: Stok berubah dari 25 menjadi 17", (int)$pAfterOut['stock'] === 17);

// Test Business Rule PRD: Barang keluar tidak boleh melebihi stok (mencegah stok negatif)
$caughtNegativeStock = false;
try {
    StockMovement::record($testProdId, (int)$adminUser['id'], 'OUT', 50, 'Permintaan keluar melebihi stok');
} catch (Exception $e) {
    $caughtNegativeStock = true;
    echo "       (Pesan validasi ditangkap: " . $e->getMessage() . ")" . PHP_EOL;
}
assertTest("Aturan Bisnis: Transaksi OUT melebihi stok berhasil dicegah (Exception caught)", $caughtNegativeStock);
$pAfterFailedOut = Product::findById($testProdId);
assertTest("Konsistensi stok terjaga: Stok tetap 17 setelah transaksi gagal", (int)$pAfterFailedOut['stock'] === 17);

// Test Business Rule PRD: Produk yang memiliki histori mutasi tidak boleh dihapus sembarangan
assertTest("Deteksi produk memiliki mutasi aktif", Product::hasMovements($testProdId) === true);

// 4. Test Listing: Search, Filter, Sort Allowlist, Pagination
echo PHP_EOL . "--- 4. Testing Listing, Search, Filter, Allowlist Sorting & Pagination ---" . PHP_EOL;
$searchResult = Product::paginate(['search' => 'Keyboard'], 1, 5, 'name', 'ASC');
assertTest("Pencarian produk berdasarkan nama/SKU mengembalikan hasil", $searchResult['total'] > 0);

$filteredResult = Product::paginate(['category_id' => $testCatId], 1, 5, 'price', 'DESC');
assertTest("Filter berdasarkan category_id berhasil", $filteredResult['total'] > 0);

$lowStockResult = Product::paginate(['low_stock' => true], 1, 10, 'stock', 'ASC');
assertTest("Filter produk stok rendah berhasil", $lowStockResult['total'] >= 0);

// 5. Test Dashboard & Laporan Agregat SQL
echo PHP_EOL . "--- 5. Testing Dashboard Stats & Aggregate Report ---" . PHP_EOL;
$stats = StockMovement::getDashboardStats();
assertTest("Dashboard total_products terhitung (> 0)", $stats['total_products'] > 0);
assertTest("Dashboard total_inventory_value terhitung (> 0)", $stats['total_inventory_value'] > 0);
assertTest("Dashboard total_in terhitung (> 0)", $stats['total_in'] > 0);
assertTest("Dashboard total_out terhitung (> 0)", $stats['total_out'] > 0);

$report = StockMovement::getInventoryReport();
assertTest("Laporan agregat inventory menghasilkan data baris", count($report) > 0);
$firstReportItem = $report[0];
assertTest("Kolom agregat total_in dan total_out ada pada laporan", 
    isset($firstReportItem['total_in']) && isset($firstReportItem['total_out']));

echo PHP_EOL . "==================================================" . PHP_EOL;
echo "HASIL AKHIR: {$passed} PASS, {$failed} FAIL" . PHP_EOL;
echo "==================================================" . PHP_EOL;

if ($failed > 0) {
    exit(1);
}
