<?php

namespace App\Core;

class Response
{
    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    public static function formatRupiah(float|int|string|null $amount): string
    {
        $num = (float)($amount ?? 0);
        return 'Rp ' . number_format($num, 0, ',', '.');
    }

    public static function setFlash(string $type, string $message): void
    {
        Auth::initSession();
        $_SESSION['flash'] = [
            'type' => $type, // success, danger, warning, info
            'message' => $message,
        ];
    }

    public static function getFlash(): ?array
    {
        Auth::initSession();
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    public static function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    public static function view(string $viewPath, array $data = [], ?string $layout = 'main'): void
    {
        extract($data);
        
        $viewFile = __DIR__ . '/../Views/' . $viewPath . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(500);
            die("View template tidak ditemukan: " . htmlspecialchars($viewPath));
        }

        // Tangkap output view
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = __DIR__ . '/../Views/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            echo $content;
            return;
        }

        require $layoutFile;
    }
}
