<?php
use App\Core\Response;

$currentPage = $history['current_page'];
$totalPages = $history['total_pages'];
$total = $history['total'];
$movements = $history['data'];

$queryParams = $filters;
unset($queryParams['page']);
$queryString = http_build_query(array_filter($queryParams));
$queryPrefix = !empty($queryString) ? "&{$queryString}" : "";
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 700;">Riwayat Mutasi Stok</h2>
        <p style="color: var(--text-muted); font-size: 0.88rem;">Catatan kronologis seluruh arus barang masuk dan keluar.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="/transactions/in" class="btn btn-success" id="btn-trans-in-top">
            + Catat Barang Masuk
        </a>
        <a href="/transactions/out" class="btn btn-danger" id="btn-trans-out-top">
            - Catat Barang Keluar
        </a>
    </div>
</div>

<!-- Filter Box -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form method="GET" action="/transactions" class="filter-bar" style="margin-bottom: 0;">
        <div class="filter-group" style="flex: 1; min-width: 250px;">
            <select name="product_id" class="form-select" id="filter-product">
                <option value="0">Semua Produk</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($filters['product_id'] == $p['id']) ? 'selected' : '' ?>>
                        <?= Response::escape($p['sku']) ?> - <?= Response::escape($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <select name="type" class="form-select" style="min-width: 150px;" id="filter-type">
                <option value="">Semua Jenis Mutasi</option>
                <option value="IN" <?= ($filters['type'] === 'IN') ? 'selected' : '' ?>>Barang Masuk (IN)</option>
                <option value="OUT" <?= ($filters['type'] === 'OUT') ? 'selected' : '' ?>>Barang Keluar (OUT)</option>
            </select>

            <button type="submit" class="btn btn-secondary">Filter</button>
            <?php if (!empty($filters['product_id']) || !empty($filters['type'])): ?>
                <a href="/transactions" class="btn btn-secondary" style="color: #ef4444;">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Table History -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table class="table" id="table-stock-movements">
            <thead>
                <tr>
                    <th>Waktu Transaksi</th>
                    <th>Tipe Mutasi</th>
                    <th>SKU / Nama Produk</th>
                    <th>Jumlah Mutasi</th>
                    <th>Sisa Stok Saat Ini</th>
                    <th>Petugas (User)</th>
                    <th>Keterangan / Catatan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            Belum ada riwayat transaksi mutasi yang tercatat.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movements as $m): ?>
                        <tr>
                            <td style="font-size: 0.85rem; color: var(--text-muted); white-space: nowrap;">
                                <?= date('d M Y, H:i', strtotime($m['created_at'])) ?>
                            </td>
                            <td>
                                <?php if ($m['type'] === 'IN'): ?>
                                    <span class="badge badge-success">+ MASUK (IN)</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">- KELUAR (OUT)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/products/show/<?= $m['product_id'] ?>" style="font-weight: 600;">
                                    <?= Response::escape($m['product_name']) ?>
                                </a>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><code><?= Response::escape($m['product_sku']) ?></code></div>
                            </td>
                            <td>
                                <strong style="font-size: 1.05rem;"><?= $m['quantity'] ?></strong> unit
                            </td>
                            <td>
                                <?= $m['current_stock'] ?> unit
                            </td>
                            <td>
                                <span style="font-weight: 500; font-size: 0.88rem;">
                                    <?= Response::escape($m['user_full_name'] ?? $m['username']) ?>
                                </span>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-muted); max-width: 250px;">
                                <?= Response::escape($m['notes'] ?? '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <a href="?page=1<?= $queryPrefix ?>" class="page-link <?= ($currentPage <= 1) ? 'disabled' : '' ?>">« Awal</a>
    <a href="?page=<?= max(1, $currentPage - 1) ?><?= $queryPrefix ?>" class="page-link <?= ($currentPage <= 1) ? 'disabled' : '' ?>">‹ Prev</a>

    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
        <a href="?page=<?= $i ?><?= $queryPrefix ?>" class="page-link <?= ($i === $currentPage) ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>

    <a href="?page=<?= min($totalPages, $currentPage + 1) ?><?= $queryPrefix ?>" class="page-link <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">Next ›</a>
    <a href="?page=<?= $totalPages ?><?= $queryPrefix ?>" class="page-link <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">Akhir »</a>
</div>
<?php endif; ?>
