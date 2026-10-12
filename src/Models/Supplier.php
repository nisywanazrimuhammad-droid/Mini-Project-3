<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Supplier
{
    public static function getAll(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT s.*, COUNT(ps.product_id) AS total_supplied_products
            FROM suppliers s
            LEFT JOIN product_suppliers ps ON ps.supplier_id = s.id
            GROUP BY s.id
            ORDER BY s.name ASC
        ");
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name, ?string $contactPerson, ?string $phone, ?string $email, ?string $address): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO suppliers (name, contact_person, phone, email, address)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $contactPerson, $phone, $email, $address]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, string $name, ?string $contactPerson, ?string $phone, ?string $email, ?string $address): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE suppliers
            SET name = ?, contact_person = ?, phone = ?, email = ?, address = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $contactPerson, $phone, $email, $address, $id]);
    }

    public static function delete(int $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function countProducts(int $id): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM product_suppliers WHERE supplier_id = ?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn();
    }

    public static function getSuppliedProducts(int $supplierId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT p.*, ps.supplier_price, c.name AS category_name
            FROM products p
            JOIN product_suppliers ps ON ps.product_id = p.id
            JOIN categories c ON c.id = p.category_id
            WHERE ps.supplier_id = ?
            ORDER BY p.name ASC
        ");
        $stmt->execute([$supplierId]);
        return $stmt->fetchAll();
    }
}
