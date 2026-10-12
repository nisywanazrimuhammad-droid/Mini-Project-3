<?php
/**
 * Database Setup & Seeder Script
 * Menginisialisasi skema database dan data awal yang realistis.
 * Dapat dijalankan via CLI: php database/setup.php
 * atau via Web browser jika dibutuhkan.
 */

$isCli = (php_sapi_name() === 'cli');

function out($msg) {
    global $isCli;
    if ($isCli) {
        echo $msg . PHP_EOL;
    } else {
        echo htmlspecialchars($msg) . "<br>";
    }
}

try {
    $dbConfig = require __DIR__ . '/../config/database.php';
    
    out("Menghubungkan ke MySQL Server ({$dbConfig['host']}:{$dbConfig['port']})...");
    
    // Koneksi ke server tanpa nama database terlebih dahulu
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['options']
    );

    out("1. Membuat database '{$dbConfig['database']}' jika belum ada...");
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbConfig['database']}`");

    out("2. Menjalankan DDL Skema Relasional...");
    $schemaFile = __DIR__ . '/schema.sql';
    $schemaSql = file_get_contents($schemaFile);
    
    // Eksekusi skema
    $pdo->exec($schemaSql);
    out("   -> Skema berhasil diterapkan.");

    out("3. Memeriksa data seeder...");
    $userCountStmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = (int) $userCountStmt->fetchColumn();

    if ($userCount > 0) {
        out("   -> Database sudah memiliki data ({$userCount} users ditemukan). Melakukan verifikasi data akun...");
    } else {
        out("   -> Melakukan seeding data awal...");
        
        $pdo->beginTransaction();

        // 1. Seed Users & Profiles
        $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
        $staffHash = password_hash('staff123', PASSWORD_DEFAULT);

        $stmtUser = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
        $stmtProfile = $pdo->prepare("INSERT INTO user_profiles (user_id, full_name, phone, address) VALUES (?, ?, ?, ?)");

        // Admin
        $stmtUser->execute(['admin', $adminHash, 'admin']);
        $adminId = (int)$pdo->lastInsertId();
        $stmtProfile->execute([$adminId, 'Administrator Sistem', '081234567890', 'Gedung Pusat Operasional Lantai 3']);

        // Staff 1
        $stmtUser->execute(['staff', $staffHash, 'staff']);
        $staff1Id = (int)$pdo->lastInsertId();
        $stmtProfile->execute([$staff1Id, 'Budi Santoso (Staff Gudang)', '082198765432', 'Jl. Pergudangan Logistik Blok B No. 4']);

        // Staff 2
        $stmtUser->execute(['siti', $staffHash, 'staff']);
        $staff2Id = (int)$pdo->lastInsertId();
        $stmtProfile->execute([$staff2Id, 'Siti Rahma (Staff Operasional)', '085712348765', 'Jl. Melati Raya No. 12']);

        // 2. Seed Categories
        $categories = [
            ['Komponen Komputer', 'Perangkat keras internal komputer dan laptop'],
            ['Aksesoris & Periferal', 'Perangkat input/output seperti keyboard, mouse, webcam'],
            ['Gadget & Audio', 'Perangkat audio, speaker, headphone nirkabel'],
            ['Peralatan Ergonomis', 'Kursi dan meja kerja pendukung produktivitas'],
            ['Jaringan & Konektivitas', 'Router, switch, adaptor dan kabel jaringan']
        ];
        $catIds = [];
        $stmtCat = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        foreach ($categories as $cat) {
            $stmtCat->execute($cat);
            $catIds[] = (int)$pdo->lastInsertId();
        }

        // 3. Seed Suppliers
        $suppliers = [
            ['PT Nusantara Tech Logistik', 'Ir. Hendra Gunawan', '021-5551234', 'sales@nusantaratech.id', 'Kawasan Industri Pulogadung Kav 18, Jakarta'],
            ['CV Maju Jaya Elektronik', 'Dewi Lestari', '022-7778901', 'order@majujaya.co.id', 'Jl. Soekarno Hatta No. 102, Bandung'],
            ['Global Peripherals Supply', 'Michael Tan', '031-8884321', 'support@globalperipherals.com', 'Rungkut Industri III No. 45, Surabaya'],
            ['Mitra Ergonomis Indonesia', 'Rian Hidayat', '021-9993322', 'info@mitraergonomis.id', 'Sentra Niaga Puri Indah Blok C, Jakarta Barat']
        ];
        $suppIds = [];
        $stmtSupp = $pdo->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)");
        foreach ($suppliers as $supp) {
            $stmtSupp->execute($supp);
            $suppIds[] = (int)$pdo->lastInsertId();
        }

        // 4. Seed Products
        $products = [
            // catId, sku, name, price, stock, minimum_stock
            [$catIds[1], 'SKU-KB-001', 'Mechanical Keyboard RGB TKL Brown Switch', 650000.00, 28, 10],
            [$catIds[1], 'SKU-MS-002', 'Wireless Gaming Mouse 16000 DPI Ultra-Light', 380000.00, 4, 10], // Stok rendah
            [$catIds[0], 'SKU-MN-003', 'Monitor Gaming 24 Inch IPS 144Hz 1ms', 1950000.00, 15, 5],
            [$catIds[2], 'SKU-HS-004', 'Noise-Cancelling Wireless Headset Bluetooth', 750000.00, 3, 8],  // Stok rendah
            [$catIds[1], 'SKU-WC-005', 'Webcam Full HD 1080p with Dual Microphone', 290000.00, 35, 12],
            [$catIds[2], 'SKU-SP-006', 'Portable Bluetooth Speaker Waterproof IPX7', 420000.00, 0, 5],   // Stok habis / sangat rendah
            [$catIds[3], 'SKU-CH-007', 'Kursi Kerja Ergonomis Mesh Backrest Lumbar Support', 1450000.00, 12, 5],
            [$catIds[3], 'SKU-DK-008', 'Meja Kerja Minimalis Adjustable 120x60cm', 950000.00, 18, 5],
            [$catIds[4], 'SKU-RT-009', 'Wi-Fi 6 Dual-Band Gigabit Router AX1800', 580000.00, 22, 8],
            [$catIds[0], 'SKU-SSD-010', 'NVMe M.2 SSD 1TB PCIe Gen4 x4 High Speed', 1100000.00, 6, 10], // Stok rendah
        ];

        $prodIds = [];
        $stmtProd = $pdo->prepare("INSERT INTO products (category_id, sku, name, price, stock, minimum_stock) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($products as $p) {
            $stmtProd->execute($p);
            $prodIds[] = (int)$pdo->lastInsertId();
        }

        // 5. Seed Many-to-Many: product_suppliers
        $productSuppliers = [
            // [prodIdx, suppIdx, supplier_price]
            [$prodIds[0], $suppIds[0], 520000.00],
            [$prodIds[0], $suppIds[2], 510000.00],
            [$prodIds[1], $suppIds[2], 295000.00],
            [$prodIds[2], $suppIds[0], 1600000.00],
            [$prodIds[2], $suppIds[1], 1580000.00],
            [$prodIds[3], $suppIds[1], 600000.00],
            [$prodIds[3], $suppIds[2], 590000.00],
            [$prodIds[4], $suppIds[0], 220000.00],
            [$prodIds[5], $suppIds[1], 330000.00],
            [$prodIds[6], $suppIds[3], 1150000.00],
            [$prodIds[7], $suppIds[3], 720000.00],
            [$prodIds[8], $suppIds[0], 460000.00],
            [$prodIds[9], $suppIds[0], 880000.00],
            [$prodIds[9], $suppIds[1], 875000.00],
        ];

        $stmtPS = $pdo->prepare("INSERT INTO product_suppliers (product_id, supplier_id, supplier_price) VALUES (?, ?, ?)");
        foreach ($productSuppliers as $ps) {
            $stmtPS->execute($ps);
        }

        // 6. Seed Stock Movements (Initial stock transactions)
        $movements = [
            // prodIdx, userId, type, qty, notes
            [$prodIds[0], $adminId, 'IN', 35, 'Penerimaan stok awal batch 1'],
            [$prodIds[0], $staff1Id, 'OUT', 7, 'Pengiriman pesanan internal B2B'],
            [$prodIds[1], $adminId, 'IN', 10, 'Stok awal mouse'],
            [$prodIds[1], $staff1Id, 'OUT', 6, 'Penjualan offline store'],
            [$prodIds[2], $adminId, 'IN', 20, 'Penerimaan monitor dari supplier'],
            [$prodIds[2], $staff2Id, 'OUT', 5, 'Instalasi lab komputer kantor'],
            [$prodIds[3], $staff1Id, 'IN', 10, 'Penerimaan headset'],
            [$prodIds[3], $staff1Id, 'OUT', 7, 'Distribusi divisi call center'],
            [$prodIds[4], $adminId, 'IN', 40, 'Penerimaan webcam Full HD'],
            [$prodIds[4], $staff2Id, 'OUT', 5, 'Penjualan retail'],
            [$prodIds[5], $staff1Id, 'IN', 15, 'Penerimaan batch speaker awal'],
            [$prodIds[5], $staff1Id, 'OUT', 15, 'Penjualan habis promosi flash sale'],
            [$prodIds[6], $adminId, 'IN', 15, 'Penerimaan kursi ergonomis'],
            [$prodIds[6], $staff2Id, 'OUT', 3, 'Pengadaan ruang meeting'],
            [$prodIds[7], $adminId, 'IN', 20, 'Penerimaan meja kerja'],
            [$prodIds[7], $staff1Id, 'OUT', 2, 'Kebutuhan workstation baru'],
            [$prodIds[8], $staff2Id, 'IN', 25, 'Penerimaan router AX1800'],
            [$prodIds[8], $staff2Id, 'OUT', 3, 'Upgrade jaringan cabang'],
            [$prodIds[9], $adminId, 'IN', 15, 'Penerimaan SSD M.2 NVMe'],
            [$prodIds[9], $staff1Id, 'OUT', 9, 'Perakitan PC server internal'],
        ];

        $stmtSM = $pdo->prepare("INSERT INTO stock_movements (product_id, user_id, type, quantity, notes) VALUES (?, ?, ?, ?, ?)");
        foreach ($movements as $m) {
            $stmtSM->execute($m);
        }

        $pdo->commit();
        out("   -> Data seeder berhasil disimpan ke database!");
    }

    out("");
    out("==================================================");
    out("Setup Selesai! Detail Akun Default:");
    out("- Admin: username = 'admin', password = 'admin123'");
    out("- Staff: username = 'staff', password = 'staff123'");
    out("==================================================");

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    out("ERROR: " . $e->getMessage());
    if ($isCli) {
        exit(1);
    }
}
