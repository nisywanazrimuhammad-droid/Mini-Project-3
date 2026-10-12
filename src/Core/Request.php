<?php

namespace App\Core;

class Request
{
    private array $get;
    private array $post;
    private array $server;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return '/' . trim($path, '/');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    public function getInt(string $key, int $default = 0): int
    {
        $val = $this->get($key);
        return ($val !== null && is_numeric($val)) ? (int)$val : $default;
    }

    public function postInt(string $key, int $default = 0): int
    {
        $val = $this->post($key);
        return ($val !== null && is_numeric($val)) ? (int)$val : $default;
    }

    public function postFloat(string $key, float $default = 0.0): float
    {
        $val = $this->post($key);
        return ($val !== null && is_numeric($val)) ? (float)$val : $default;
    }
}
