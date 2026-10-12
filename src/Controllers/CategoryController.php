<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Category;
use Throwable;

class CategoryController
{
    public function index(Request $request): void
    {
        Auth::requireLogin();

        $categories = Category::getAll();

        Response::view('categories/index', [
            'title' => 'Manajemen Kategori - Inventory System',
            'categories' => $categories,
            'flash' => Response::getFlash()
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requireAdmin();

        $name = trim((string)$request->post('name', ''));
        $description = trim((string)$request->post('description', ''));

        $validator = Validator::make(['name' => $name])
            ->required('name', 'Nama Kategori');

        if ($validator->fails()) {
            Response::setFlash('danger', $validator->firstError());
            Response::redirect('/categories');
            return;
        }

        try {
            Category::create($name, $description ?: null);
            Response::setFlash('success', "Kategori '{$name}' berhasil ditambahkan.");
        } catch (Throwable $e) {
            Response::setFlash('danger', "Gagal menambah kategori: " . $e->getMessage());
        }

        Response::redirect('/categories');
    }

    public function update(Request $request, array $params): void
    {
        Auth::requireAdmin();
        $id = (int)($params['id'] ?? 0);

        $name = trim((string)$request->post('name', ''));
        $description = trim((string)$request->post('description', ''));

        $validator = Validator::make(['name' => $name])
            ->required('name', 'Nama Kategori');

        if ($validator->fails()) {
            Response::setFlash('danger', $validator->firstError());
            Response::redirect('/categories');
            return;
        }

        try {
            Category::update($id, $name, $description ?: null);
            Response::setFlash('success', "Kategori '{$name}' berhasil diperbarui.");
        } catch (Throwable $e) {
            Response::setFlash('danger', "Gagal memperbarui kategori: " . $e->getMessage());
        }

        Response::redirect('/categories');
    }

    public function delete(Request $request, array $params): void
    {
        Auth::requireAdmin();
        $id = (int)($params['id'] ?? 0);

        if (Category::countProducts($id) > 0) {
            Response::setFlash('danger', "Kategori tidak dapat dihapus karena masih memiliki produk terkait.");
            Response::redirect('/categories');
            return;
        }

        try {
            Category::delete($id);
            Response::setFlash('success', "Kategori berhasil dihapus.");
        } catch (Throwable $e) {
            Response::setFlash('danger', "Gagal menghapus kategori: " . $e->getMessage());
        }

        Response::redirect('/categories');
    }
}
