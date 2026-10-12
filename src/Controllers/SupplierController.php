<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Supplier;
use Throwable;

class SupplierController
{
    public function index(Request $request): void
    {
        Auth::requireLogin();

        $suppliers = Supplier::getAll();

        Response::view('suppliers/index', [
            'title' => 'Manajemen Supplier - Inventory System',
            'suppliers' => $suppliers,
            'flash' => Response::getFlash()
        ]);
    }

    public function show(Request $request, array $params): void
    {
        Auth::requireLogin();
        $id = (int)($params['id'] ?? 0);

        $supplier = Supplier::findById($id);
        if (!$supplier) {
            Response::setFlash('danger', 'Supplier tidak ditemukan.');
            Response::redirect('/suppliers');
            return;
        }

        $suppliedProducts = Supplier::getSuppliedProducts($id);

        Response::view('suppliers/show', [
            'title' => "Detail Supplier: {$supplier['name']}",
            'supplier' => $supplier,
            'suppliedProducts' => $suppliedProducts,
            'flash' => Response::getFlash()
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requireAdmin();

        $name = trim((string)$request->post('name', ''));
        $contactPerson = trim((string)$request->post('contact_person', ''));
        $phone = trim((string)$request->post('phone', ''));
        $email = trim((string)$request->post('email', ''));
        $address = trim((string)$request->post('address', ''));

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
        ])
            ->required('name', 'Nama Supplier');

        if (!empty($email)) {
            $validator->email('email', 'Email');
        }

        if ($validator->fails()) {
            Response::setFlash('danger', $validator->firstError());
            Response::redirect('/suppliers');
            return;
        }

        try {
            Supplier::create($name, $contactPerson ?: null, $phone ?: null, $email ?: null, $address ?: null);
            Response::setFlash('success', "Supplier '{$name}' berhasil ditambahkan.");
        } catch (Throwable $e) {
            Response::setFlash('danger', "Gagal menambahkan supplier: " . $e->getMessage());
        }

        Response::redirect('/suppliers');
    }

    public function update(Request $request, array $params): void
    {
        Auth::requireAdmin();
        $id = (int)($params['id'] ?? 0);

        $name = trim((string)$request->post('name', ''));
        $contactPerson = trim((string)$request->post('contact_person', ''));
        $phone = trim((string)$request->post('phone', ''));
        $email = trim((string)$request->post('email', ''));
        $address = trim((string)$request->post('address', ''));

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
        ])
            ->required('name', 'Nama Supplier');

        if (!empty($email)) {
            $validator->email('email', 'Email');
        }

        if ($validator->fails()) {
            Response::setFlash('danger', $validator->firstError());
            Response::redirect('/suppliers');
            return;
        }

        try {
            Supplier::update($id, $name, $contactPerson ?: null, $phone ?: null, $email ?: null, $address ?: null);
            Response::setFlash('success', "Data supplier '{$name}' berhasil diperbarui.");
        } catch (Throwable $e) {
            Response::setFlash('danger', "Gagal memperbarui supplier: " . $e->getMessage());
        }

        Response::redirect('/suppliers');
    }

    public function delete(Request $request, array $params): void
    {
        Auth::requireAdmin();
        $id = (int)($params['id'] ?? 0);

        if (Supplier::countProducts($id) > 0) {
            Response::setFlash('danger', "Supplier tidak dapat dihapus karena masih terhubung dengan beberapa produk.");
            Response::redirect('/suppliers');
            return;
        }

        try {
            Supplier::delete($id);
            Response::setFlash('success', "Supplier berhasil dihapus.");
        } catch (Throwable $e) {
            Response::setFlash('danger', "Gagal menghapus supplier: " . $e->getMessage());
        }

        Response::redirect('/suppliers');
    }
}
