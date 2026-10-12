<?php
/**
 * End-to-End HTTP Integration Test for Inventory Management System
 */

declare(strict_types=1);

$baseUrl = 'http://127.0.0.1:8000';

function httpRequest(string $url, string $method = 'GET', ?array $postData = null, ?string &$cookies = null): array {
    $ch = curl_init();
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 5,
    ];

    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        if ($postData !== null) {
            $options[CURLOPT_POSTFIELDS] = http_build_query($postData);
        }
    }

    if ($cookies !== null) {
        $options[CURLOPT_COOKIE] = $cookies;
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    $headerStr = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    // Extract cookies
    if (preg_match_all('/^Set-Cookie:\s*([^;]+)/mi', $headerStr, $matches)) {
        $cookies = implode('; ', $matches[1]);
    }

    // Extract Location header
    $location = null;
    if (preg_match('/^Location:\s*(.+)$/mi', $headerStr, $locMatch)) {
        $location = trim($locMatch[1]);
    }

    curl_close($ch);

    return [
        'code' => $httpCode,
        'headers' => $headerStr,
        'location' => $location,
        'body' => $body,
    ];
}

echo "=== MEMULAI TEST HTTP INTEGRATION SUITE ===" . PHP_EOL . PHP_EOL;

// 1. Test Login Page Response
$res = httpRequest("{$baseUrl}/login");
echo "1. GET /login -> HTTP {$res['code']}";
if ($res['code'] === 200 && str_contains($res['body'], 'InvenTrack')) {
    echo " [PASS]" . PHP_EOL;
} else {
    echo " [FAIL]" . PHP_EOL;
}

// 2. Test Admin Login Flow
$adminCookies = null;
$loginAdmin = httpRequest("{$baseUrl}/login", 'POST', ['username' => 'admin', 'password' => 'admin123'], $adminCookies);
echo "2. POST /login (Admin) -> HTTP {$loginAdmin['code']} Redirect to: {$loginAdmin['location']}";
if ($loginAdmin['code'] === 302 && $loginAdmin['location'] === '/dashboard') {
    echo " [PASS]" . PHP_EOL;
} else {
    echo " [FAIL]" . PHP_EOL;
}

// 3. Test Dashboard (with Admin Cookie)
$dash = httpRequest("{$baseUrl}/dashboard", 'GET', null, $adminCookies);
echo "3. GET /dashboard (Admin) -> HTTP {$dash['code']}";
if ($dash['code'] === 200 && str_contains($dash['body'], 'Total Produk') && str_contains($dash['body'], 'Nilai Inventory')) {
    echo " [PASS]" . PHP_EOL;
} else {
    echo " [FAIL]" . PHP_EOL;
}

// 4. Test Product Listing & Search
$products = httpRequest("{$baseUrl}/products?search=Keyboard", 'GET', null, $adminCookies);
echo "4. GET /products?search=Keyboard -> HTTP {$products['code']}";
if ($products['code'] === 200 && str_contains($products['body'], 'Mechanical Keyboard')) {
    echo " [PASS]" . PHP_EOL;
} else {
    echo " [FAIL]" . PHP_EOL;
}

// 5. Test Aggregate Inventory Report
$report = httpRequest("{$baseUrl}/reports/inventory", 'GET', null, $adminCookies);
echo "5. GET /reports/inventory -> HTTP {$report['code']}";
if ($report['code'] === 200 && str_contains($report['body'], 'TOTAL KESELURUHAN') && str_contains($report['body'], 'Valuasi')) {
    echo " [PASS]" . PHP_EOL;
} else {
    echo " [FAIL]" . PHP_EOL;
}

// 6. Test Admin Access to /users
$usersAdmin = httpRequest("{$baseUrl}/users", 'GET', null, $adminCookies);
echo "6. GET /users (Admin) -> HTTP {$usersAdmin['code']}";
if ($usersAdmin['code'] === 200 && str_contains($usersAdmin['body'], 'Manajemen Pengguna')) {
    echo " [PASS]" . PHP_EOL;
} else {
    echo " [FAIL]" . PHP_EOL;
}

// 7. Test Staff Login Flow
$staffCookies = null;
$loginStaff = httpRequest("{$baseUrl}/login", 'POST', ['username' => 'staff', 'password' => 'staff123'], $staffCookies);
echo "7. POST /login (Staff) -> HTTP {$loginStaff['code']} Redirect to: {$loginStaff['location']}";
if ($loginStaff['code'] === 302 && $loginStaff['location'] === '/dashboard') {
    echo " [PASS]" . PHP_EOL;
} else {
    echo " [FAIL]" . PHP_EOL;
}

// 8. Test Staff accessing Products (Permitted)
$staffProd = httpRequest("{$baseUrl}/products", 'GET', null, $staffCookies);
echo "8. GET /products (Staff) -> HTTP {$staffProd['code']}";
if ($staffProd['code'] === 200) {
    echo " [PASS]" . PHP_EOL;
} else {
    echo " [FAIL]" . PHP_EOL;
}

// 9. Test Staff accessing /users (STRICT FORBIDDEN - HTTP 403) - PRD 11.2
$staffUsers = httpRequest("{$baseUrl}/users", 'GET', null, $staffCookies);
echo "9. GET /users (Staff) -> HTTP {$staffUsers['code']}";
if ($staffUsers['code'] === 403 && str_contains($staffUsers['body'], '403 - Akses Ditolak')) {
    echo " [PASS] (Berhasil dilindungi: HTTP 403 Forbidden)" . PHP_EOL;
} else {
    echo " [FAIL]" . PHP_EOL;
}

// 10. Test Recording Stock In Transaction via HTTP POST
$transIn = httpRequest("{$baseUrl}/transactions/in", 'POST', [
    'product_id' => 1,
    'quantity' => 10,
    'notes' => 'Uji HTTP Stock In'
], $staffCookies);
echo "10. POST /transactions/in (Staff) -> HTTP {$transIn['code']} Redirect: {$transIn['location']}";
if ($transIn['code'] === 302 && $transIn['location'] === '/transactions') {
    echo " [PASS]" . PHP_EOL;
} else {
    echo " [FAIL]" . PHP_EOL;
}

echo PHP_EOL . "=== SEMUA TEST HTTP INTEGRATION SELESAI DENGAN SUKSES! ===" . PHP_EOL;
