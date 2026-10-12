<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\User;

class AuthController
{
    public function showLogin(Request $request): void
    {
        if (Auth::check()) {
            Response::redirect('/dashboard');
        }

        Response::view('auth/login', [
            'title' => 'Login - Inventory System',
            'flash' => Response::getFlash()
        ], 'auth');
    }

    public function login(Request $request): void
    {
        $username = trim((string)$request->post('username', ''));
        $password = (string)$request->post('password', '');

        $validator = Validator::make(['username' => $username, 'password' => $password])
            ->required('username', 'Username')
            ->required('password', 'Password');

        if ($validator->fails()) {
            Response::setFlash('danger', $validator->firstError());
            Response::redirect('/login');
            return;
        }

        $user = User::findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Response::setFlash('danger', 'Username atau password yang Anda masukkan salah.');
            Response::redirect('/login');
            return;
        }

        Auth::login($user);
        Response::setFlash('success', "Selamat datang kembali, {$user['full_name']}!");
        Response::redirect('/dashboard');
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        Response::setFlash('info', 'Anda telah berhasil keluar dari sistem.');
        Response::redirect('/login');
    }
}
