<?php

declare(strict_types=1);

// Error reporting for development
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// PSR-4 Autoloader untuk namespace App\
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\DashboardController;
use App\Controllers\ProductController;
use App\Controllers\ReportController;
use App\Controllers\SupplierController;
use App\Controllers\TransactionController;
use App\Controllers\UserController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

$request = new Request();
$router = new Router();

// 1. Root & Auth Routes
$router->get('/', function () {
    Response::redirect('/dashboard');
});
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/logout', [AuthController::class, 'logout']);

// 2. Dashboard
$router->get('/dashboard', [DashboardController::class, 'index']);

// 3. Products
$router->get('/products', [ProductController::class, 'index']);
$router->get('/products/create', [ProductController::class, 'create']);
$router->post('/products/create', [ProductController::class, 'store']);
$router->get('/products/show/{id}', [ProductController::class, 'show']);
$router->get('/products/edit/{id}', [ProductController::class, 'edit']);
$router->post('/products/edit/{id}', [ProductController::class, 'update']);
$router->post('/products/delete/{id}', [ProductController::class, 'delete']);

// 4. Categories
$router->get('/categories', [CategoryController::class, 'index']);
$router->post('/categories/create', [CategoryController::class, 'store']);
$router->post('/categories/edit/{id}', [CategoryController::class, 'update']);
$router->post('/categories/delete/{id}', [CategoryController::class, 'delete']);

// 5. Suppliers
$router->get('/suppliers', [SupplierController::class, 'index']);
$router->post('/suppliers/create', [SupplierController::class, 'store']);
$router->get('/suppliers/show/{id}', [SupplierController::class, 'show']);
$router->post('/suppliers/edit/{id}', [SupplierController::class, 'update']);
$router->post('/suppliers/delete/{id}', [SupplierController::class, 'delete']);

// 6. Transactions (IN / OUT / Histori)
$router->get('/transactions', [TransactionController::class, 'index']);
$router->get('/transactions/in', [TransactionController::class, 'showInForm']);
$router->post('/transactions/in', [TransactionController::class, 'storeIn']);
$router->get('/transactions/out', [TransactionController::class, 'showOutForm']);
$router->post('/transactions/out', [TransactionController::class, 'storeOut']);

// 7. Reports
$router->get('/reports/inventory', [ReportController::class, 'inventory']);

// 8. Users (Admin Only)
$router->get('/users', [UserController::class, 'index']);
$router->post('/users/create', [UserController::class, 'store']);
$router->post('/users/edit/{id}', [UserController::class, 'update']);
$router->post('/users/delete/{id}', [UserController::class, 'delete']);

// Dispatch Request
$router->dispatch($request);
