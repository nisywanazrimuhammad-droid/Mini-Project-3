# Product Requirement Document (PRD): Inventory Management System

## 1. Latar Belakang & Fokus Utama
Data produk, kategori, supplier, pengguna, dan transaksi saling berhubungan. Stok harus konsisten dan tidak boleh menjadi negatif. Aplikasi mulai membutuhkan pencarian, laporan, session, role, dan keamanan. 
**Fokus utama:** bagaimana data berubah secara benar ketika proses bisnis terjadi.

## 2. Tujuan Sistem
* Mengelola kategori, produk, supplier, dan user.
* Mencatat barang masuk dan barang keluar.
* Menghitung serta menampilkan stok secara konsisten.
* Menampilkan produk dengan stok rendah.
* Mencari produk berdasarkan nama atau SKU.
* Menyediakan laporan inventory berbasis fungsi agregat.
* Membatasi fitur berdasarkan role pengguna.

## 3. Business Rules (Aturan Bisnis)
1. SKU harus unik.
2. Harga dan quantity tidak boleh negatif.
3. Barang keluar tidak boleh melebihi stok.
4. Stok tidak boleh menjadi negatif.
5. Produk yang memiliki histori tidak boleh dihapus sembarangan.
6. Halaman internal membutuhkan login.
7. Staff tidak boleh mengakses administrasi user.
8. Sorting hanya menggunakan kolom yang diizinkan (*allowlist*).
9. **Formula Mutasi Stok:** 
   `stok akhir = stok awal + total barang masuk − total barang keluar`

## 4. 9 Fitur Wajib
| No | Fitur | Keterangan |
| :---: | :--- | :--- |
| **1** | **Authentication** | Login |
| **2** | **Logout** | Logout dan password hashing |
| **3** | **Authorization** | Role admin dan staff |
| **4** | **Master data** | Kategori, produk, supplier |
| **5** | **Many-to-many** | Produk dan supplier |
| **6** | **Transaksi** | Barang masuk, barang keluar, histori |
| **7** | **Listing** | Search, filter, sorting, pagination |
| **8** | **Dashboard** | Statistik dan laporan inventory |
| **9** | **Security** | Prepared statement, validation, escaping |

---

## 5. Arsitektur Database & Skema Relasional

Relasi membantu database menjaga makna dan konsistensi data. Tentukan relasi terlebih dahulu sebelum menulis halaman CRUD.

* **One-to-one:** `users` $\leftrightarrow$ `user_profiles` (Satu user memiliki satu profile. Menggunakan FK + `UNIQUE`. Cocok untuk memisahkan data login dan profil).
* **One-to-many:** `categories` $\rightarrow$ `products` (Satu kategori dapat memiliki banyak produk. FK pada tabel anak `products`. Satu product hanya memiliki satu `category_id`).
* **Many-to-many:** `products` $\leftrightarrow$ `suppliers` (Satu produk dapat memiliki banyak supplier dan sebaliknya. Membutuhkan junction table yang menyimpan pasangan `product_id` dan `supplier_id`. Gunakan composite primary key agar pasangan tidak duplikat).

### 5.1 Integritas Referensial (Foreign Key)
* Child record tidak boleh merujuk parent yang tidak ada.
* Pilih aturan DELETE dan UPDATE sesuai kebutuhan bisnis.
* Untuk histori transaksi, `RESTRICT` biasanya lebih aman daripada `CASCADE` (mencegah penghapusan produk yang memiliki histori).
  ```sql
  FOREIGN KEY (category_id) REFERENCES categories(id) 
  ON UPDATE CASCADE 
  ON DELETE RESTRICT
  ```

### 5.2 Skema Database Inventory (Minimal)
```text
users
 └── stock_movements

categories ──< products ──< stock_movements
           └──< product_suppliers >── suppliers
```
*(Catatan: Tanda `<` menunjukkan relasi one-to-many)*

### 5.3 Implementasi Tabel SQL Utama
**Tabel `user_profiles`**
```sql
CREATE TABLE user_profiles (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL UNIQUE,
  full_name VARCHAR(100) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**Tabel `product_suppliers` (Junction Table)**
```sql
CREATE TABLE product_suppliers (
  product_id INT NOT NULL,
  supplier_id INT NOT NULL,
  supplier_price DECIMAL(12,2),
  PRIMARY KEY (product_id, supplier_id)
);
```

**Tabel `products`**
```sql
CREATE TABLE products (
  id INT PRIMARY KEY AUTO_INCREMENT,
  category_id INT NOT NULL,
  sku VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  price DECIMAL(12,2) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  minimum_stock INT NOT NULL DEFAULT 5,
  FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

---

## 6. Operasi Data Kueri (Query)

### 6.1 JOIN
Menggabungkan data dari beberapa tabel:
* **INNER JOIN:** Hanya data yang memiliki pasangan di kedua tabel.
* **LEFT JOIN:** Semua data tabel kiri tetap muncul.
* **RIGHT JOIN:** Semua data tabel kanan tetap muncul.
```sql
SELECT p.name, c.name AS category_name
FROM products p
JOIN categories c ON c.id = p.category_id;
```

### 6.2 Filter (WHERE & LIKE)
Filter data berdasarkan kondisi dan pola teks:
* `keyboard%` = diawali kata keyboard.
* `%keyboard` = diakhiri kata keyboard.
* `%keyboard%` = mengandung kata keyboard.
```sql
SELECT * FROM products
WHERE name LIKE '%keyboard%' AND price >= 100000
ORDER BY price DESC;
```

### 6.3 GROUP BY & Aggregate Functions
Menghasilkan ringkasan dan laporan dari sekumpulan data:
* **COUNT:** Menghitung jumlah baris/data.
* **SUM:** Menjumlahkan nilai numerik.
* **AVG:** Menghitung rata-rata.
```sql
SELECT c.name, COUNT(p.id) AS total_products, SUM(p.stock) AS total_stock, AVG(p.price) AS average_price
FROM categories c
LEFT JOIN products p ON p.category_id = c.id
GROUP BY c.id, c.name;
```

### 6.4 WHERE vs HAVING
Keduanya memfilter, tetapi pada tahap yang berbeda. Gunakan `HAVING` untuk kondisi yang melibatkan fungsi agregat.
* **WHERE:** Filter baris sebelum `GROUP BY` (Contoh: `price >= 100000`).
* **HAVING:** Filter hasil setelah `GROUP BY` (Contoh: `COUNT(*) >= 3`).

---

## 7. Fitur Listing

### 7.1 Search, Filter, Sorting
Listing produk harus mudah digunakan dan tetap aman.
* **Search:** nama produk atau SKU.
* **Filter:** `category_id`.
* **Sorting:** nama, harga, atau stok.
* **Direction:** `ASC` atau `DESC`.
* **Keamanan:** Kolom sorting harus dipilih dari allowlist, bukan input mentah.

### 7.2 Pagination
Membatasi jumlah data yang ditampilkan per halaman. Pertahankan parameter search, filter, dan sorting saat berpindah halaman.
```php
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// offset = (page - 1) x data per halaman.
// total_page = ceiling(total data / data per halaman).

SELECT ... LIMIT :limit OFFSET :offset;
```

---

## 8. Business Logic: Transaksi Barang

Mencegah stok berubah tanpa histori atau sebaliknya. Semua berhasil bersama-sama atau semuanya dibatalkan. Contoh kegagalan yang harus dicegah: stok berkurang, tetapi histori gagal disimpan.

### 8.1 Barang Masuk
Satu proses bisnis dapat mengubah lebih dari satu tabel.
1. Validasi `product_id` dan `quantity`.
2. Tambah stok produk.
3. Catat histori `stock_movements`.
4. Simpan user yang melakukan transaksi.
5. Gunakan database transaction agar proses konsisten.

### 8.2 Barang Keluar
Barang keluar tidak boleh melebihi stok tersedia.
```text
BEGIN TRANSACTION
1. SELECT stock ... FOR UPDATE
2. Validasi quantity <= stock
3. UPDATE products SET stock = stock - quantity
4. INSERT stock_movements (type = 'OUT')
COMMIT / ROLLBACK
```

### 8.3 Implementasi Database Transaction (PDO)
```php
$pdo->beginTransaction();
try {
  // update stok
  // insert histori
  $pdo->commit();
} catch (Throwable $e) {
  $pdo->rollBack();
  throw $e;
}
```

---

## 9. Dashboard & Laporan

### 9.1 Dashboard yang Diharapkan
Informasi ringkas untuk membantu pengambilan keputusan.

| Widget | Sumber Data |
| :--- | :--- |
| **Total produk** | `COUNT(products.id)` |
| **Nilai inventory** | `SUM(stock * price)` |
| **Produk stok rendah** | `stock <= minimum_stock` |
| **Barang masuk** | `SUM(quantity) WHERE type = 'IN'` |
| **Barang keluar** | `SUM(quantity) WHERE type = 'OUT'` |

### 9.2 Contoh Laporan Inventory
Aggregate query membantu aplikasi menjawab pertanyaan bisnis.
```sql
SELECT p.sku, p.name,
  SUM(CASE WHEN sm.type='IN' THEN sm.quantity ELSE 0 END) AS total_in,
  SUM(CASE WHEN sm.type='OUT' THEN sm.quantity ELSE 0 END) AS total_out
FROM products p
LEFT JOIN stock_movements sm ON sm.product_id = p.id
GROUP BY p.id, p.sku, p.name;
```

---

## 10. Security & Input Validation

### 10.1 SQL Injection
Terjadi ketika input pengguna digabungkan langsung ke query SQL. Jangan gunakan concatenation untuk nilai input. Prepared statement adalah pertahanan utama terhadap SQL Injection. Pisahkan struktur query dari nilai input pengguna. Tambahkan validasi server-side.
```php
// LEBIH AMAN (Prepared Statement)
$stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
$stmt->execute(['username' => $_POST['username']]);
```

### 10.2 XSS & Output Encoding
Data dari user harus dianggap tidak tepercaya ketika ditampilkan.
```php
// AMAN UNTUK HTML
echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
```

### 10.3 Input Validation
Validasi format dan makna bisnis dilakukan di server.
* **Syntactic validation:** tipe, format, panjang.
* **Semantic validation:** stok cukup, produk ada, harga tidak negatif.
```php
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
if ($quantity === false || $quantity <= 0) {
  $errors[] = 'Jumlah harus lebih dari nol.';
}
```

---

## 11. Autentikasi & Otorisasi
Login menjawab siapa pengguna; authorization menjawab apa yang boleh dilakukan.

### 11.1 Autentikasi & Session (Password Hashing)
Username dan password valid. Menyimpan user yang sedang login. Password tidak boleh disimpan sebagai teks biasa.
```php
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($_POST['password'], $user['password_hash'])) {
  // Setelah login
  session_regenerate_id(true);
  $_SESSION['user'] = [
    'id' => $user['id'],
    'role' => $user['role']
  ];
}
```

### 11.2 Basic Authorization
Hak akses diterapkan pada server, bukan hanya disembunyikan dari menu.
* **Admin:** User, kategori, produk, supplier, transaksi.
* **Staff:** Melihat produk dan mencatat transaksi (tidak boleh mengakses administrasi user).
* **Guest:** Halaman publik saja.

```php
if ($_SESSION['user']['role'] !== 'admin') {
  http_response_code(403);
  exit('Forbidden');
}
```