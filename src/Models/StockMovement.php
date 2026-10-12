<?php

namespace App\Models;

use App\Core\Database;
use Exception;
use PDO;
use Throwable;

class StockMovement
{
    /**
     * Catat transaksi mutasi stok secara ATOMIK menggunakan DB Transaction & FOR UPDATE lock.
     * Sesuai aturan bisnis PRD Bagian 8.
     */
    public static function record(int $productId, int $userId, string $type, int $quantity, ?string $notes = null): int
    {
        if ($quantity <= 0) {
            throw new Exception("Jumlah transaksi (quantity) harus lebih besar dari 0.");
        }

        if (!in_array($type, ['IN', 'OUT'], true)) {
            throw new Exception("Tipe transaksi harus 'IN' (Barang Masuk) atau 'OUT' (Barang Keluar).");
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            // 1. SELECT stock ... FOR UPDATE (Kunci baris produk untuk mencegah race condition)
            $stmtLock = $db->prepare("SELECT id, name, sku, stock FROM products WHERE id = ? FOR UPDATE");
            $stmtLock->execute([$productId]);
            $product = $stmtLock->fetch();

            if (!$product) {
                throw new Exception("Produk tidak ditemukan.");
            }

            $currentStock = (int)$product['stock'];

            // 2. Validasi stok untuk barang keluar (OUT)
            if ($type === 'OUT') {
                if ($quantity > $currentStock) {
                    throw new Exception("Stok tidak mencukupi! Stok saat ini: {$currentStock}, permintaan keluar: {$quantity}.");
                }
                $newStock = $currentStock - $quantity;
            } else {
                $newStock = $currentStock + $quantity;
            }

            // 3. Update stok pada tabel products
            $stmtUpdate = $db->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $stmtUpdate->execute([$newStock, $productId]);

            // 4. Insert catatan mutasi ke stock_movements
            $stmtInsert = $db->prepare("
                INSERT INTO stock_movements (product_id, user_id, type, quantity, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtInsert->execute([$productId, $userId, $type, $quantity, $notes]);
            $movementId = (int)$db->lastInsertId();

            // 5. Commit transaksi jika semua sukses
            $db->commit();
            return $movementId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Mengambil daftar histori mutasi stok dengan filter dan pagination.
     */
    public static function getHistory(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $db = Database::getConnection();
        $where = [];
        $params = [];

        if (!empty($filters['product_id'])) {
            $where[] = "sm.product_id = :product_id";
            $params['product_id'] = (int)$filters['product_id'];
        }

        if (!empty($filters['type']) && in_array($filters['type'], ['IN', 'OUT'], true)) {
            $where[] = "sm.type = :type";
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = "sm.user_id = :user_id";
            $params['user_id'] = (int)$filters['user_id'];
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Hitung total data
        $countSql = "SELECT COUNT(*) FROM stock_movements sm {$whereSql}";
        $countStmt = $db->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue(":{$k}", $v);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT sm.*,
                   p.name AS product_name, p.sku AS product_sku, p.stock AS current_stock,
                   u.username, up.full_name AS user_full_name
            FROM stock_movements sm
            JOIN products p ON p.id = sm.product_id
            JOIN users u ON u.id = sm.user_id
            LEFT JOIN user_profiles up ON up.user_id = u.id
            {$whereSql}
            ORDER BY sm.created_at DESC, sm.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(":{$k}", $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Mengambil ringkasan data statistik untuk Dashboard.
     * Sesuai PRD Bagian 9.1
     */
    public static function getDashboardStats(): array
    {
        $db = Database::getConnection();

        // 1. Total produk & Nilai inventory
        $prodStats = $db->query("
            SELECT COUNT(id) AS total_products,
                   COALESCE(SUM(stock * price), 0) AS total_inventory_value
            FROM products
        ")->fetch();

        // 2. Produk stok rendah (stock <= minimum_stock)
        $lowStockCount = (int)$db->query("
            SELECT COUNT(id) FROM products WHERE stock <= minimum_stock
        ")->fetchColumn();

        // 3. Total barang masuk (IN)
        $totalIn = (int)$db->query("
            SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE type = 'IN'
        ")->fetchColumn();

        // 4. Total barang keluar (OUT)
        $totalOut = (int)$db->query("
            SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE type = 'OUT'
        ")->fetchColumn();

        // 5. Total supplier & kategori
        $totalCategories = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        $totalSuppliers = (int)$db->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();

        // 6. Produk dengan stok terendah untuk warning widget
        $lowStockProducts = $db->query("
            SELECT p.id, p.sku, p.name, p.stock, p.minimum_stock, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.stock <= p.minimum_stock
            ORDER BY p.stock ASC, p.name ASC
            LIMIT 5
        ")->fetchAll();

        // 7. Transaksi mutasi terbaru (Recent movements)
        $recentMovements = $db->query("
            SELECT sm.*, p.name AS product_name, p.sku AS product_sku,
                   u.username, up.full_name AS user_full_name
            FROM stock_movements sm
            JOIN products p ON p.id = sm.product_id
            JOIN users u ON u.id = sm.user_id
            LEFT JOIN user_profiles up ON up.user_id = u.id
            ORDER BY sm.created_at DESC, sm.id DESC
            LIMIT 5
        ")->fetchAll();

        return [
            'total_products' => (int)$prodStats['total_products'],
            'total_inventory_value' => (float)$prodStats['total_inventory_value'],
            'low_stock_count' => $lowStockCount,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'total_categories' => $totalCategories,
            'total_suppliers' => $totalSuppliers,
            'low_stock_products' => $lowStockProducts,
            'recent_movements' => $recentMovements,
        ];
    }

    /**
     * Laporan Inventory berbasis Agregat Query.
     * Sesuai PRD Bagian 9.2:
     * Formula: Stok Awal dihitung / Stok Akhir = (Total IN - Total OUT).
     */
    public static function getInventoryReport(?int $categoryId = null): array
    {
        $db = Database::getConnection();
        $where = "";
        $params = [];

        if (!empty($categoryId)) {
            $where = "WHERE p.category_id = :cat_id";
            $params['cat_id'] = $categoryId;
        }

        $sql = "
            SELECT p.id, p.sku, p.name, p.price, p.stock, p.minimum_stock,
                   c.name AS category_name,
                   COALESCE(SUM(CASE WHEN sm.type = 'IN' THEN sm.quantity ELSE 0 END), 0) AS total_in,
                   COALESCE(SUM(CASE WHEN sm.type = 'OUT' THEN sm.quantity ELSE 0 END), 0) AS total_out,
                   (p.stock * p.price) AS inventory_value
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN stock_movements sm ON sm.product_id = p.id
            {$where}
            GROUP BY p.id, p.sku, p.name, p.price, p.stock, p.minimum_stock, c.name
            ORDER BY c.name ASC, p.name ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
