<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\User;
use Throwable;

class UserController
{
    public function index(Request $request): void
    {
        Auth::requireAdmin();

        $users = User::getAll();

        Response::view('users/index', [
            'title' => 'Manajemen Pengguna - Inventory System',
            'users' => $users,
            'currentUserId' => Auth::id(),
            'flash' => Response::getFlash()
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requireAdmin();

        $username = strtolower(trim((string)$request->post('username', '')));
        $password = (string)$request->post('password', '');
        $role = (string)$request->post('role', 'staff');
        $fullName = trim((string)$request->post('full_name', ''));
        $phone = trim((string)$request->post('phone', ''));
        $address = trim((string)$request->post('address', ''));

        $validator = Validator::make([
            'username' => $username,
            'password' => $password,
            'role' => $role,
            'full_name' => $fullName,
        ])
            ->required('username', 'Username')
            ->minLength('username', 'Username', 3)
            ->required('password', 'Password')
            ->minLength('password', 'Password', 6)
            ->required('full_name', 'Nama Lengkap')
            ->inArray('role', 'Role', ['admin', 'staff']);

        if (User::findByUsername($username)) {
            $validator->custom(false, 'username', "Username '{$username}' sudah digunakan.");
        }

        if ($validator->fails()) {
            Response::setFlash('danger', $validator->firstError());
            Response::redirect('/users');
            return;
        }

        try {
            User::create($username, $password, $role, $fullName, $phone ?: null, $address ?: null);
            Response::setFlash('success', "Pengguna '{$username}' berhasil ditambahkan.");
        } catch (Throwable $e) {
            Response::setFlash('danger', "Gagal menambahkan user: " . $e->getMessage());
        }

        Response::redirect('/users');
    }

    public function update(Request $request, array $params): void
    {
        Auth::requireAdmin();
        $id = (int)($params['id'] ?? 0);

        $user = User::findById($id);
        if (!$user) {
            Response::setFlash('danger', 'Pengguna tidak ditemukan.');
            Response::redirect('/users');
            return;
        }

        $username = strtolower(trim((string)$request->post('username', '')));
        $password = (string)$request->post('password', '');
        $role = (string)$request->post('role', 'staff');
        $fullName = trim((string)$request->post('full_name', ''));
        $phone = trim((string)$request->post('phone', ''));
        $address = trim((string)$request->post('address', ''));

        $validator = Validator::make([
            'username' => $username,
            'role' => $role,
            'full_name' => $fullName,
        ])
            ->required('username', 'Username')
            ->minLength('username', 'Username', 3)
            ->required('full_name', 'Nama Lengkap')
            ->inArray('role', 'Role', ['admin', 'staff']);

        if (!empty($password)) {
            $validator->minLength('password', 'Password', 6);
        }

        // Cek duplikasi username jika diubah
        $existing = User::findByUsername($username);
        if ($existing && (int)$existing['id'] !== $id) {
            $validator->custom(false, 'username', "Username '{$username}' sudah digunakan.");
        }

        if ($validator->fails()) {
            Response::setFlash('danger', $validator->firstError());
            Response::redirect('/users');
            return;
        }

        try {
            User::update(
                $id,
                $username,
                $role,
                $fullName,
                $phone ?: null,
                $address ?: null,
                !empty($password) ? $password : null
            );
            Response::setFlash('success', "Data pengguna '{$username}' berhasil diperbarui.");
        } catch (Throwable $e) {
            Response::setFlash('danger', "Gagal memperbarui user: " . $e->getMessage());
        }

        Response::redirect('/users');
    }

    public function delete(Request $request, array $params): void
    {
        Auth::requireAdmin();
        $id = (int)($params['id'] ?? 0);

        if ($id === Auth::id()) {
            Response::setFlash('danger', "Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.");
            Response::redirect('/users');
            return;
        }

        if (User::hasMovements($id)) {
            Response::setFlash('danger', "Pengguna tidak dapat dihapus karena memiliki riwayat pencatatan transaksi mutasi barang.");
            Response::redirect('/users');
            return;
        }

        try {
            User::delete($id);
            Response::setFlash('success', "Pengguna berhasil dihapus.");
        } catch (Throwable $e) {
            Response::setFlash('danger', "Gagal menghapus pengguna: " . $e->getMessage());
        }

        Response::redirect('/users');
    }
}
