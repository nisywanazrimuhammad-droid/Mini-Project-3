<?php
use App\Core\Auth;
use App\Core\Response;
?>

<div style="margin-bottom: 1.5rem;">
    <a href="/products" style="color: var(--text-muted); font-size: 0.88rem; display: inline-flex; align-items: center; gap: 0.35rem;">
        ← Kembali ke Katalog Produk
    </a>
</div>

<!-- Header & Quick Action -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <span style="font-size: 0.85rem; font-weight: 700; color: var(--primary); background: var(--primary-light); padding: 0.25rem 0.6rem; border-radius: var(--radius-sm);">
                    <?= Response::escape($product['sku']) ?>
                </span>
                <span class="badge badge-secondary"><?= Response::escape($product['category_name'] ?? 'Kategori') ?></span>
                <?php if ($product['stock'] <= 0): ?>
                    <span class="badge badge-danger">Stok Habis</span>
                <?php elseif ($product['stock'] <= $product['minimum_stock']): ?>
                    <span class="badge badge-warning">Stok Menipis</span>
                <?php else: ?>
                    <span class="badge badge-success">Stok Aman</span>
                <?php endif; ?>
            </div>
            <h1 style="font-size: 1.6rem; font-weight: 800;"><?= Response::escape($product['name']) ?></h1>
            <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 0.25rem;">
                Terdaftar sejak: <?= date('d F Y, H:i', strtotime($product['created_at'])) ?>
            </p>
        </div>

        <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
            <a href="/transactions/in?product_id=<?= $product['id'] ?>" class="btn btn-success" id="btn-in-detail">
                + Tambah Stok (IN)
            </a>
            <a href="/transactions/out?product_id=<?= $product['id'] ?>" class="btn btn-danger" id="btn-out-detail">
                - Kurangi Stok (OUT)
            </a>
            <?php if (Auth::isAdmin()): ?>
            <a href="/products/edit/<?= $product['id'] ?>" class="btn btn-secondary" id="btn-edit-detail">
                Edit
            </a>
            <form action="/products/delete/<?= $product['id'] ?>" method="POST" style="display: inline;"
                  onsubmit="return confirm('Yakin ingin menghapus produk ini?');">
                <button type="submit" class="btn btn-secondary" style="color: #ef4444;" id="btn-delete-detail">
                    Hapus
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistik Ringkas Produk -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-subtle);">
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Harga Jual Satuan</div>
            <div style="font-size: 1.35rem; font-weight: 800; color: var(--primary);">
                <?= Response::formatRupiah($product['price']) ?>
            </div>
        </div>
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Stok Tersedia</div>
            <div style="font-size: 1.35rem; font-weight: 800;">
                <?= $product['stock'] ?> <span style="font-size: 0.85rem; font-weight: 400; color: var(--text-muted);">unit</span>
            </div>
        </div>
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Stok Minimum</div>
            <div style="font-size: 1.35rem; font-weight: 800; color: var(--warning);">
                <?= $product['minimum_stock'] ?> <span style="font-size: 0.85rem; font-weight: 400; color: var(--text-muted);">unit</span>
            </div>
        </div>
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Nilai Total Stok</div>
            <div style="font-size: 1.35rem; font-weight: 800; color: var(--accent-cyan);">
                <?= Response::formatRupiah($product['stock'] * $product['price']) ?>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem;">
    <!-- Daftar Supplier Terkait (Many-to-Many) -->
    <div class="card" id="card-product-suppliers">
        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between;">
            <span>🏢 Supplier Terkait (Many-to-Many)</span>
            <span class="badge badge-primary"><?= count($suppliers) ?> supplier</span>
        </h3>

        <?php if (empty($suppliers)): ?>
            <p style="color: var(--text-muted); font-size: 0.88rem; padding: 1rem 0;">Belum ada supplier yang dikaitkan dengan produk ini.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama Supplier</th>
                            <th>Kontak / Telp</th>
                            <th>Harga Beli Supplier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $s): ?>
                            <tr>
                                <td>
                                    <a href="/suppliers/show/<?= $s['id'] ?>" style="font-weight: 600;">
                                        <?= Response::escape($s['name']) ?>
                                    </a>
                                </td>
                                <td style="font-size: 0.85rem; color: var(--text-muted);">
                                    <?= Response::escape($s['contact_person'] ?? '-') ?><br>
                                    <?= Response::escape($s['phone'] ?? '-') ?>
                                </td>
                                <td style="font-weight: 600; color: var(--success);">
                                    <?= !empty($s['supplier_price']) ? Response::formatRupiah($s['supplier_price']) : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Riwayat Mutasi Stok Produk Ini -->
    <div class="card" id="card-product-movements">
        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between;">
            <span>⏱️ Riwayat Mutasi Stok</span>
            <a href="/transactions?product_id=<?= $product['id'] ?>" class="btn btn-secondary btn-sm">Lihat Semua</a>
        </h3>

        <?php if (empty($history['data'])): ?>
            <p style="color: var(--text-muted); font-size: 0.88rem; padding: 1rem 0;">Belum ada catatan mutasi stok untuk produk ini.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Tipe</th>
                            <th>Jumlah</th>
                            <th>Petugas</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history['data'] as $h): ?>
                            <tr>
                                <td style="font-size: 0.82rem; color: var(--text-muted); white-space: nowrap;">
                                    <?= date('d/m/y H:i', strtotime($h['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if ($h['type'] === 'IN'): ?>
                                        <span class="badge badge-success">+ IN</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">- OUT</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= $h['quantity'] ?></strong></td>
                                <td style="font-size: 0.82rem;"><?= Response::escape($h['user_full_name'] ?? $h['username']) ?></td>
                                <td style="font-size: 0.82rem; color: var(--text-muted);"><?= Response::escape($h['notes'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
