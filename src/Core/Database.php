<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/database.php';
            $dsn = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                // Log and show user-friendly message
                error_log("Database Connection Error: " . $e->getMessage());
                die("Koneksi database gagal. Silakan pastikan server database MySQL aktif dan telah diinisialisasi melalui database/setup.php.");
            }
        }

        return self::$instance;
    }
}
