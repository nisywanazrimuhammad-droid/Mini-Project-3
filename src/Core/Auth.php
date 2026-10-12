<?php

namespace App\Core;

class Auth
{
    public static function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $appConfig = require __DIR__ . '/../../config/app.php';
            session_name($appConfig['session_name'] ?? 'INV_SESS_ID');
            session_start();
        }
    }

    public static function check(): bool
    {
        self::initSession();
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }

    public static function user(): ?array
    {
        self::initSession();
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['id'] : null;
    }

    public static function role(): ?string
    {
        $u = self::user();
        return $u ? $u['role'] : null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function isStaff(): bool
    {
        return self::role() === 'staff';
    }

    public static function login(array $userData): void
    {
        self::initSession();
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$userData['id'],
            'username' => $userData['username'],
            'role' => $userData['role'],
            'full_name' => $userData['full_name'] ?? $userData['username'],
        ];
    }

    public static function logout(): void
    {
        self::initSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Response::setFlash('warning', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
            Response::redirect('/login');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            Response::view('errors/403', [
                'title' => '403 Forbidden',
                'message' => 'Akses ditolak. Halaman ini hanya boleh diakses oleh Administrator.'
            ]);
            exit;
        }
    }
}
