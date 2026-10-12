<?php
use App\Core\Response;
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 700;">Laporan Mutasi & Nilai Inventory</h2>
        <p style="color: var(--text-muted); font-size: 0.88rem;">
            Laporan kalkulasi agregat mutasi stok dan valuasi aset berdasarkan kueri SQL GROUP BY & Aggregate.
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <button class="btn btn-secondary" onclick="window.print()" id="btn-print-report">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Cetak Laporan
        </button>
    </div>
</div>

<!-- Ringkasan Agregat -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.5rem;">
    <div class="card stat-card cyan">
        <span class="stat-label">Total Valuasi Inventory</span>
        <div class="stat-value" style="font-size: 1.4rem; color: var(--accent-cyan);">
            <?= Response::formatRupiah($summary['total_val']) ?>
        </div>
        <div class="stat-subtext">SUM(stock × price)</div>
    </div>
    <div class="card stat-card primary">
        <span class="stat-label">Total Unit Tersedia</span>
        <div class="stat-value"><?= number_format($summary['total_stock']) ?></div>
        <div class="stat-subtext">Unit barang di gudang</div>
    </div>
    <div class="card stat-card success">
        <span class="stat-label">Total Barang Masuk (IN)</span>
        <div class="stat-value" style="color: #10b981;"><?= number_format($summary['total_in']) ?></div>
        <div class="stat-subtext">Akumulasi penerimaan</div>
    </div>
    <div class="card stat-card danger">
        <span class="stat-label">Total Barang Keluar (OUT)</span>
        <div class="stat-value" style="color: #ef4444;"><?= number_format($summary['total_out']) ?></div>
        <div class="stat-subtext">Akumulasi pengeluaran</div>
    </div>
</div>

<!-- Filter Kategori -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form method="GET" action="/reports/inventory" class="filter-bar" style="margin-bottom: 0;">
        <div class="filter-group">
            <label for="filter-cat" style="font-size: 0.88rem; font-weight: 600;">Filter Kategori:</label>
            <select name="category_id" id="filter-cat" class="form-select" style="min-width: 220px;" onchange="this.form.submit()">
                <option value="0">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($selectedCategoryId == $cat['id']) ? 'selected' : '' ?>>
                        <?= Response::escape($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($selectedCategoryId)): ?>
                <a href="/reports/inventory" class="btn btn-secondary" style="color: #ef4444;">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tabel Laporan Agregasi SQL (PRD Bagian 9.2) -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table class="table" id="table-inventory-report">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga Satuan</th>
                    <th>Total Masuk (IN)</th>
                    <th>Total Keluar (OUT)</th>
                    <th>Sisa Stok Fisik</th>
                    <th>Nilai Aset Stok</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($report)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            Tidak ada data untuk laporan inventory.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($report as $r): ?>
                        <tr>
                            <td><code><?= Response::escape($r['sku']) ?></code></td>
                            <td>
                                <a href="/products/show/<?= $r['id'] ?>" style="font-weight: 600;">
                                    <?= Response::escape($r['name']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-secondary"><?= Response::escape($r['category_name']) ?></span>
                            </td>
                            <td><?= Response::formatRupiah($r['price']) ?></td>
                            <td>
                                <span style="color: #10b981; font-weight: 600;">+<?= number_format($r['total_in']) ?></span>
                            </td>
                            <td>
                                <span style="color: #ef4444; font-weight: 600;">-<?= number_format($r['total_out']) ?></span>
                            </td>
                            <td>
                                <strong style="font-size: 1.05rem;"><?= number_format($r['stock']) ?></strong> unit
                                <?php if ($r['stock'] <= $r['minimum_stock']): ?>
                                    <span class="badge badge-warning" style="font-size: 0.68rem; margin-left: 0.35rem;">Min</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: 700; color: var(--primary);">
                                <?= Response::formatRupiah($r['inventory_value']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background: var(--bg-surface); font-weight: 800;">
                    <td colspan="4" style="text-align: right; padding: 1.25rem 1rem;">TOTAL KESELURUHAN:</td>
                    <td style="color: #10b981; padding: 1.25rem 1rem;">+<?= number_format($summary['total_in']) ?></td>
                    <td style="color: #ef4444; padding: 1.25rem 1rem;">-<?= number_format($summary['total_out']) ?></td>
                    <td style="padding: 1.25rem 1rem;"><?= number_format($summary['total_stock']) ?> unit</td>
                    <td style="color: var(--accent-cyan); padding: 1.25rem 1rem; font-size: 1.05rem;">
                        <?= Response::formatRupiah($summary['total_val']) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
