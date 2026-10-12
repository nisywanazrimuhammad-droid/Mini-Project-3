<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

class Product
{
    public static function paginate(array $filters = [], int $page = 1, int $perPage = 10, string $sortBy = 'name', string $sortDir = 'ASC'): array
    {
        $db = Database::getConnection();

        // Allowlist sorting sesuai PRD 3.8 & 7.1
        $appConfig = require __DIR__ . '/../../config/app.php';
        $allowedSorts = $appConfig['allowed_sort_columns'] ?? [
            'name' => 'p.name',
            'sku' => 'p.sku',
            'price' => 'p.price',
            'stock' => 'p.stock',
            'category' => 'c.name',
            'created_at' => 'p.created_at',
        ];
        $sortColumn = $allowedSorts[$sortBy] ?? 'p.name';
        $direction = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

        $where = [];
        $params = [];

        // Search name atau SKU (PRD 7.1)
        if (!empty($filters['search'])) {
            $searchTerm = '%' . trim($filters['search']) . '%';
            $where[] = "(p.name LIKE :search_name OR p.sku LIKE :search_sku)";
            $params['search_name'] = $searchTerm;
            $params['search_sku'] = $searchTerm;
        }

        // Filter category_id
        if (!empty($filters['category_id'])) {
            $where[] = "p.category_id = :category_id";
            $params['category_id'] = (int)$filters['category_id'];
        }

        // Filter status stok rendah
        if (!empty($filters['low_stock'])) {
            $where[] = "p.stock <= p.minimum_stock";
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Hitung total data
        $countSql = "SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id {$whereSql}";
        $countStmt = $db->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue(":{$k}", $v);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        // Hitung pagination
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        // Query listing dengan JOIN kategori dan agregat supplier
        $sql = "
            SELECT p.*, c.name AS category_name,
                   COUNT(DISTINCT ps.supplier_id) AS supplier_count,
                   COUNT(DISTINCT sm.id) AS movement_count
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN product_suppliers ps ON ps.product_id = p.id
            LEFT JOIN stock_movements sm ON sm.product_id = p.id
            {$whereSql}
            GROUP BY p.id, c.name
            ORDER BY {$sortColumn} {$direction}
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

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT p.*, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findBySku(string $sku): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM products WHERE sku = :sku LIMIT 1");
        $stmt->execute(['sku' => $sku]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getSuppliers(int $productId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT s.*, ps.supplier_price
            FROM suppliers s
            JOIN product_suppliers ps ON ps.supplier_id = s.id
            WHERE ps.product_id = :product_id
            ORDER BY s.name ASC
        ");
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data, array $supplierData = []): int
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("
                INSERT INTO products (category_id, sku, name, price, stock, minimum_stock)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['category_id'],
                $data['sku'],
                $data['name'],
                $data['price'],
                $data['stock'] ?? 0,
                $data['minimum_stock'] ?? 5
            ]);
            $productId = (int)$db->lastInsertId();

            // Insert many-to-many product_suppliers
            if (!empty($supplierData)) {
                $stmtPS = $db->prepare("
                    INSERT INTO product_suppliers (product_id, supplier_id, supplier_price)
                    VALUES (?, ?, ?)
                ");
                foreach ($supplierData as $supp) {
                    if (!empty($supp['supplier_id'])) {
                        $stmtPS->execute([
                            $productId,
                            (int)$supp['supplier_id'],
                            !empty($supp['supplier_price']) ? (float)$supp['supplier_price'] : null
                        ]);
                    }
                }
            }

            // Catat stok awal jika ada > 0 ke stock_movements
            if (!empty($data['stock']) && (int)$data['stock'] > 0 && !empty($data['user_id'])) {
                $stmtSM = $db->prepare("
                    INSERT INTO stock_movements (product_id, user_id, type, quantity, notes)
                    VALUES (?, ?, 'IN', ?, 'Stok awal saat pendaftaran produk')
                ");
                $stmtSM->execute([$productId, $data['user_id'], (int)$data['stock']]);
            }

            $db->commit();
            return $productId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function update(int $id, array $data, array $supplierData = []): void
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("
                UPDATE products
                SET category_id = ?, sku = ?, name = ?, price = ?, minimum_stock = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['category_id'],
                $data['sku'],
                $data['name'],
                $data['price'],
                $data['minimum_stock'] ?? 5,
                $id
            ]);

            // Sync product_suppliers (Many-to-Many)
            $db->prepare("DELETE FROM product_suppliers WHERE product_id = ?")->execute([$id]);

            if (!empty($supplierData)) {
                $stmtPS = $db->prepare("
                    INSERT INTO product_suppliers (product_id, supplier_id, supplier_price)
                    VALUES (?, ?, ?)
                ");
                foreach ($supplierData as $supp) {
                    if (!empty($supp['supplier_id'])) {
                        $stmtPS->execute([
                            $id,
                            (int)$supp['supplier_id'],
                            !empty($supp['supplier_price']) ? (float)$supp['supplier_price'] : null
                        ]);
                    }
                }
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function delete(int $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function hasMovements(int $id): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM stock_movements WHERE product_id = ?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function getAllSimple(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT id, sku, name, stock, minimum_stock, price FROM products ORDER BY name ASC");
        return $stmt->fetchAll();
    }
}
