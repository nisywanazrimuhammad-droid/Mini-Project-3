Inventory Management System

Aplikasi manajemen inventaris berbasis web yang dibangun dengan Laravel dan SQLite/MySQL. Sistem ini dirancang untuk mengelola produk, kategori, supplier, serta pergerakan stok (barang masuk & keluar) dengan dukungan manajemen pengguna berbasis peran (Role-Based Access Control).

## 🚀 Fitur Utama
* *Dashboard* — Ringkasan statistik: total produk, nilai inventaris, stok rendah, total transaksi masuk/keluar.
* *Manajemen Produk* — CRUD produk dengan SKU, harga, stok, dan ambang batas stok minimum.
* *Manajemen Kategori* — Kategorisasi produk.
* *Manajemen Supplier* — Data supplier lengkap dengan relasi ke produk.
* *Transaksi Stok* — Pencatatan barang masuk (IN) dan keluar (OUT) beserta riwayat mutasi.
* *Laporan Inventaris* — Laporan stok secara keseluruhan.
* *Manajemen Pengguna* — CRUD user dengan role Admin & Staff.
* *Autentikasi* — Login/logout dengan middleware role guard.

## 🛠️ Tech Stack
* *Backend*: Laravel (PHP ^8.3)
* *Database*: SQLite (default) / MySQL
* *Frontend*: Blade Templates + Vite
* *Auth*: Laravel Session Auth

## 👥 Role & Akses
* *Staff*: Dapat melihat dashboard, produk, supplier, melakukan input transaksi (IN/OUT), dan melihat laporan inventaris.
* *Admin*: Memiliki akses penuh termasuk CRUD Produk, Kategori, Supplier, dan Manajemen User.

## ⚙️ Cara Instalasi & Menjalankan
1. Clone repository ini ke komputer Anda.
2. Jalankan skrip setup.bat atau lakukan instalasi manual (composer install, php artisan migrate --seed).
3. Jalankan server dengan perintah php artisan serve atau menggunakan file serve.bat.
4. Akses aplikasi melalui browser di http://localhost:8000.

## 🔑 Akun Demo (Seeder)
* *Admin*: admin@example.com / password
* *Staff*: staff@example.com / password
*