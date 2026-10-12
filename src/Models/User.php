<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

class User
{
    public static function findByUsername(string $username): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.password_hash, u.role, u.created_at,
                   up.full_name, up.phone, up.address
            FROM users u
            LEFT JOIN user_profiles up ON up.user_id = u.id
            WHERE u.username = :username
            LIMIT 1
        ");
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.role, u.created_at,
                   up.full_name, up.phone, up.address
            FROM users u
            LEFT JOIN user_profiles up ON up.user_id = u.id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getAll(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT u.id, u.username, u.role, u.created_at,
                   up.full_name, up.phone, up.address,
                   COUNT(sm.id) AS movement_count
            FROM users u
            LEFT JOIN user_profiles up ON up.user_id = u.id
            LEFT JOIN stock_movements sm ON sm.user_id = u.id
            GROUP BY u.id, up.id
            ORDER BY u.id ASC
        ");
        return $stmt->fetchAll();
    }

    public static function create(string $username, string $password, string $role, string $fullName, ?string $phone, ?string $address): int
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmtUser = $db->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
            $stmtUser->execute([$username, $hash, $role]);
            $userId = (int)$db->lastInsertId();

            $stmtProfile = $db->prepare("INSERT INTO user_profiles (user_id, full_name, phone, address) VALUES (?, ?, ?, ?)");
            $stmtProfile->execute([$userId, $fullName, $phone, $address]);

            $db->commit();
            return $userId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function update(int $id, string $username, string $role, string $fullName, ?string $phone, ?string $address, ?string $password = null): void
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmtUser = $db->prepare("UPDATE users SET username = ?, password_hash = ?, role = ? WHERE id = ?");
                $stmtUser->execute([$username, $hash, $role, $id]);
            } else {
                $stmtUser = $db->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                $stmtUser->execute([$username, $role, $id]);
            }

            // Update or insert profile
            $stmtCheck = $db->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
            $stmtCheck->execute([$id]);
            if ($stmtCheck->fetch()) {
                $stmtProfile = $db->prepare("UPDATE user_profiles SET full_name = ?, phone = ?, address = ? WHERE user_id = ?");
                $stmtProfile->execute([$fullName, $phone, $address, $id]);
            } else {
                $stmtProfile = $db->prepare("INSERT INTO user_profiles (user_id, full_name, phone, address) VALUES (?, ?, ?, ?)");
                $stmtProfile->execute([$id, $fullName, $phone, $address]);
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
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function hasMovements(int $id): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM stock_movements WHERE user_id = ?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
