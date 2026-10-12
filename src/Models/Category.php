<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Category
{
    public static function getAll(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT c.id, c.name, c.description, c.created_at,
                   COUNT(p.id) AS total_products,
                   COALESCE(SUM(p.stock), 0) AS total_stock,
                   COALESCE(AVG(p.price), 0) AS average_price
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            GROUP BY c.id, c.name, c.description, c.created_at
            ORDER BY c.name ASC
        ");
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name, ?string $description): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, string $name, ?string $description): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $id]);
    }

    public static function delete(int $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function countProducts(int $id): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn();
    }
}
