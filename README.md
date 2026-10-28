Inventory Management System

Aplikasi manajemen inventaris berbasis web yang dibangun dengan Laravel dan SQLite/MySQL. Sistem ini dirancang untuk mengelola produk, kategori, supplier, serta pergerakan stok (barang masuk & keluar) dengan dukungan manajemen pengguna berbasis peran (Role-Based Access Control).

## 🚀 Fitur Utama
* *Dashboard* — Ringkasan statistik: total produk, nilai inventaris, stok rendah, total transaksi masuk/keluar[span_1](start_span)[span_1](end_span).
* *Manajemen Produk* — CRUD produk dengan SKU, harga, stok, dan ambang batas stok minimum[span_2](start_span)[span_2](end_span).
* *Manajemen Kategori* — Kategorisasi produk[span_3](start_span)[span_3](end_span).
* *Manajemen Supplier* — Data supplier lengkap dengan relasi ke produk[span_4](start_span)[span_4](end_span).
* *Transaksi Stok* — Pencatatan barang masuk (IN) dan keluar (OUT) beserta riwayat mutasi[span_5](start_span)[span_5](end_span).
* *Laporan Inventaris* — Laporan stok secara keseluruhan[span_6](start_span)[span_6](end_span).
* *Manajemen Pengguna* — CRUD user dengan role Admin & Staff[span_7](start_span)[span_7](end_span).
* *Autentikasi* — Login/logout dengan middleware role guard[span_8](start_span)[span_8](end_span).

## 🛠️ Tech Stack
* *Backend*: Laravel (PHP ^8.3)[span_9](start_span)[span_9](end_span)
* *Database*: SQLite (default) / MySQL[span_10](start_span)[span_10](end_span)
* *Frontend*: Blade Templates + Vite[span_11](start_span)[span_11](end_span)
* *Auth*: Laravel Session Auth[span_12](start_span)[span_12](end_span)

## 👥 Role & Akses
* *Staff*: Dapat melihat dashboard, produk, supplier, melakukan input transaksi (IN/OUT), dan melihat laporan inventaris[span_13](start_span)[span_13](end_span).
* *Admin*: Memiliki akses penuh termasuk CRUD Produk, Kategori, Supplier, dan Manajemen User[span_14](start_span)[span_14](end_span).

## ⚙️ Cara Instalasi & Menjalankan
1. Clone repository ini ke komputer Anda[span_15](start_span)[span_15](end_span).
2. Jalankan skrip setup.bat atau lakukan instalasi manual (composer install, php artisan migrate --seed)[span_16](start_span)[span_16](end_span)[span_17](start_span)[span_17](end_span)[span_18](start_span)[span_18](end_span).
3. Jalankan server dengan perintah php artisan serve atau menggunakan file serve.bat[span_19](start_span)[span_19](end_span).
4. Akses aplikasi melalui browser di http://localhost:8000[span_20](start_span)[span_20](end_span).

## 🔑 Akun Demo (Seeder)
* *Admin*: admin@example.com / password[span_21](start_span)[span_21](end_span)
* *Staff*: staff@example.com / password[span_22](start_span)[span_22](end_span)
*