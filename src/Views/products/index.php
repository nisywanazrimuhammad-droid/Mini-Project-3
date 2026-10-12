<?php
use App\Core\Auth;
use App\Core\Response;

$currentPage = $pagination['current_page'];
$totalPages = $pagination['total_pages'];
$total = $pagination['total'];
$products = $pagination['data'];

// Build base query string for sorting and pagination links
$queryParams = $filters;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
$queryPrefix = !empty($queryString) ? "&{$queryString}" : "";
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 700;">Katalog & Stok Produk</h2>
        <p style="color: var(--text-muted); font-size: 0.88rem;">Total <?= $total ?> produk terdaftar dalam sistem.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <?php if (Auth::isAdmin()): ?>
        <a href="/products/create" class="btn btn-primary" id="btn-add-product">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Tambah Produk
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter Bar: Search, Category Filter, Low Stock Toggle, Sorting (Allowlist) -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form method="GET" action="/products" class="filter-bar" style="margin-bottom: 0;">
        <div class="filter-group" style="flex: 1; min-width: 280px;">
            <input type="text" name="search" class="form-control" placeholder="Cari nama atau SKU produk..." 
                   value="<?= Response::escape($filters['search'] ?? '') ?>" id="input-search">
        </div>

        <div class="filter-group">
            <select name="category_id" class="form-select" style="min-width: 180px;" id="select-category-filter">
                <option value="0">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($filters['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= Response::escape($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="sort" class="form-select" style="min-width: 140px;" id="select-sort">
                <option value="name" <?= ($filters['sort'] === 'name') ? 'selected' : '' ?>>Urut: Nama</option>
                <option value="price" <?= ($filters['sort'] === 'price') ? 'selected' : '' ?>>Urut: Harga</option>
                <option value="stock" <?= ($filters['sort'] === 'stock') ? 'selected' : '' ?>>Urut: Stok</option>
                <option value="sku" <?= ($filters['sort'] === 'sku') ? 'selected' : '' ?>>Urut: SKU</option>
                <option value="category" <?= ($filters['sort'] === 'category') ? 'selected' : '' ?>>Urut: Kategori</option>
            </select>

            <select name="dir" class="form-select" style="min-width: 100px;" id="select-dir">
                <option value="ASC" <?= ($filters['dir'] === 'ASC') ? 'selected' : '' ?>>Naik (ASC)</option>
                <option value="DESC" <?= ($filters['dir'] === 'DESC') ? 'selected' : '' ?>>Turun (DESC)</option>
            </select>

            <label style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.88rem; cursor: pointer; user-select: none;">
                <input type="checkbox" name="low_stock" value="1" <?= (!empty($filters['low_stock'])) ? 'checked' : '' ?> id="check-low-stock">
                <span>Stok Rendah</span>
            </label>

            <button type="submit" class="btn btn-secondary" id="btn-filter-apply">Terapkan</button>
            <?php if (!empty($filters['search']) || !empty($filters['category_id']) || !empty($filters['low_stock'])): ?>
                <a href="/products" class="btn btn-secondary" style="color: #ef4444;" title="Reset Filter">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Product Table -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table class="table" id="product-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga Jual</th>
                    <th>Stok Saat Ini</th>
                    <th>Status Stok</th>
                    <th>Supplier</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            Tidak ada produk yang sesuai dengan kriteria pencarian.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><code><?= Response::escape($p['sku']) ?></code></td>
                            <td>
                                <a href="/products/show/<?= $p['id'] ?>" style="font-weight: 600; color: var(--text-main);">
                                    <?= Response::escape($p['name']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-secondary"><?= Response::escape($p['category_name'] ?? 'Tanpa Kategori') ?></span>
                            </td>
                            <td style="font-weight: 600;">
                                <?= Response::formatRupiah($p['price']) ?>
                            </td>
                            <td>
                                <span style="font-size: 1.05rem; font-weight: 700;"><?= $p['stock'] ?></span>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">/ min <?= $p['minimum_stock'] ?></span>
                            </td>
                            <td>
                                <?php if ($p['stock'] <= 0): ?>
                                    <span class="badge badge-danger">Habis</span>
                                <?php elseif ($p['stock'] <= $p['minimum_stock']): ?>
                                    <span class="badge badge-warning">Menipis</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Aman</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?= $p['supplier_count'] ?> supplier</span>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <div style="display: inline-flex; gap: 0.4rem;">
                                    <a href="/products/show/<?= $p['id'] ?>" class="btn btn-secondary btn-sm" title="Detail">
                                        Detail
                                    </a>
                                    <a href="/transactions/in?product_id=<?= $p['id'] ?>" class="btn btn-success btn-sm" title="Tambah Stok">
                                        + IN
                                    </a>
                                    <a href="/transactions/out?product_id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" title="Keluarkan Stok">
                                        - OUT
                                    </a>
                                    <?php if (Auth::isAdmin()): ?>
                                    <a href="/products/edit/<?= $p['id'] ?>" class="btn btn-secondary btn-sm" title="Edit">
                                        Edit
                                    </a>
                                    <form action="/products/delete/<?= $p['id'] ?>" method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color: #ef4444;" title="Hapus">
                                            Hapus
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination (PRD Bagian 7.2) -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <a href="?page=1<?= $queryPrefix ?>" class="page-link <?= ($currentPage <= 1) ? 'disabled' : '' ?>">« Awal</a>
    <a href="?page=<?= max(1, $currentPage - 1) ?><?= $queryPrefix ?>" class="page-link <?= ($currentPage <= 1) ? 'disabled' : '' ?>">‹ Prev</a>

    <?php
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    for ($i = $startPage; $i <= $endPage; $i++):
    ?>
        <a href="?page=<?= $i ?><?= $queryPrefix ?>" class="page-link <?= ($i === $currentPage) ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>

    <a href="?page=<?= min($totalPages, $currentPage + 1) ?><?= $queryPrefix ?>" class="page-link <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">Next ›</a>
    <a href="?page=<?= $totalPages ?><?= $queryPrefix ?>" class="page-link <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">Akhir »</a>
</div>
<?php endif; ?>
