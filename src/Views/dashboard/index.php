<?php
use App\Core\Auth;
use App\Core\Response;
?>

<!-- Quick Action Banner -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 800;">Ringkasan Operasional</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
            Selamat datang, <strong><?= Response::escape($user['full_name'] ?? $user['username']) ?></strong>. Pantau kondisi stok dan mutasi barang terkini.
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="/transactions/in" class="btn btn-success" id="btn-quick-in">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Barang Masuk
        </a>
        <a href="/transactions/out" class="btn btn-danger" id="btn-quick-out">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
            Barang Keluar
        </a>
        <?php if (Auth::isAdmin()): ?>
        <a href="/products/create" class="btn btn-primary" id="btn-quick-add-product">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3-0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Tambah Produk
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- 5 Widget Utama Sesuai PRD Bagian 9.1 -->
<div class="stats-grid">
    <!-- 1. Total Produk -->
    <div class="card stat-card primary" id="stat-total-products">
        <div class="stat-header">
            <span class="stat-label">Total Produk</span>
            <div class="stat-icon-wrapper primary">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            </div>
        </div>
        <div class="stat-value"><?= number_format($stats['total_products']) ?></div>
        <div class="stat-subtext">Dari <?= $stats['total_categories'] ?> kategori aktif</div>
    </div>

    <!-- 2. Nilai Inventory -->
    <div class="card stat-card cyan" id="stat-inventory-value">
        <div class="stat-header">
            <span class="stat-label">Nilai Inventory</span>
            <div class="stat-icon-wrapper cyan">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
        <div class="stat-value" style="font-size: 1.5rem;"><?= Response::formatRupiah($stats['total_inventory_value']) ?></div>
        <div class="stat-subtext">Kalkulasi: SUM(stock × price)</div>
    </div>

    <!-- 3. Produk Stok Rendah -->
    <div class="card stat-card warning" id="stat-low-stock">
        <div class="stat-header">
            <span class="stat-label">Stok Rendah</span>
            <div class="stat-icon-wrapper warning">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
        </div>
        <div class="stat-value" style="color: #f59e0b;"><?= number_format($stats['low_stock_count']) ?></div>
        <div class="stat-subtext">Produk ≤ Stok Minimum</div>
    </div>

    <!-- 4. Total Barang Masuk -->
    <div class="card stat-card success" id="stat-total-in">
        <div class="stat-header">
            <span class="stat-label">Barang Masuk</span>
            <div class="stat-icon-wrapper success">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path></svg>
            </div>
        </div>
        <div class="stat-value" style="color: #10b981;"><?= number_format($stats['total_in']) ?></div>
        <div class="stat-subtext">Akumulasi total barang masuk (IN)</div>
    </div>

    <!-- 5. Total Barang Keluar -->
    <div class="card stat-card danger" id="stat-total-out">
        <div class="stat-header">
            <span class="stat-label">Barang Keluar</span>
            <div class="stat-icon-wrapper danger">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path></svg>
            </div>
        </div>
        <div class="stat-value" style="color: #ef4444;"><?= number_format($stats['total_out']) ?></div>
        <div class="stat-subtext">Akumulasi total barang keluar (OUT)</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 1.5rem;">
    <!-- Widget Peringatan Stok Rendah -->
    <div class="card" id="card-low-stock-alert">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                <span style="color: #f59e0b;">⚠️</span> Produk Perlu Re-Stock
            </h3>
            <a href="/products?low_stock=1" class="btn btn-secondary btn-sm">Lihat Semua</a>
        </div>

        <?php if (empty($stats['low_stock_products'])): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem; padding: 1rem 0;">Semua stok produk berada di atas batas minimum.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Nama Produk</th>
                            <th>Sisa Stok</th>
                            <th>Min Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['low_stock_products'] as $lp): ?>
                            <tr>
                                <td><code><?= Response::escape($lp['sku']) ?></code></td>
                                <td style="font-weight: 600;"><?= Response::escape($lp['name']) ?></td>
                                <td>
                                    <?php if ($lp['stock'] <= 0): ?>
                                        <span class="badge badge-danger">Habis (0)</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><?= $lp['stock'] ?> unit</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: var(--text-muted);"><?= $lp['minimum_stock'] ?></td>
                                <td>
                                    <a href="/transactions/in?product_id=<?= $lp['id'] ?>" class="btn btn-success btn-sm">+ Re-Stock</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Mutasi Stok Terakhir -->
    <div class="card" id="card-recent-movements">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                <span>⏱️</span> Transaksi Mutasi Terbaru
            </h3>
            <a href="/transactions" class="btn btn-secondary btn-sm">Semua Riwayat</a>
        </div>

        <?php if (empty($stats['recent_movements'])): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem; padding: 1rem 0;">Belum ada riwayat transaksi mutasi barang.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Tipe</th>
                            <th>Produk</th>
                            <th>Jumlah</th>
                            <th>Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['recent_movements'] as $rm): ?>
                            <tr>
                                <td style="font-size: 0.82rem; color: var(--text-muted); white-space: nowrap;">
                                    <?= date('d/m/Y H:i', strtotime($rm['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if ($rm['type'] === 'IN'): ?>
                                        <span class="badge badge-success">MASUK (IN)</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">KELUAR (OUT)</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 600;"><?= Response::escape($rm['product_name']) ?></td>
                                <td><strong><?= $rm['quantity'] ?></strong></td>
                                <td style="font-size: 0.85rem;"><?= Response::escape($rm['user_full_name'] ?? $rm['username']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
