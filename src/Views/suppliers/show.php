<?php
use App\Core\Response;
?>

<div style="margin-bottom: 1.5rem;">
    <a href="/suppliers" style="color: var(--text-muted); font-size: 0.88rem; display: inline-flex; align-items: center; gap: 0.35rem;">
        ← Kembali ke Daftar Supplier
    </a>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
        <div>
            <span class="badge badge-primary" style="margin-bottom: 0.5rem;">Supplier Mitra</span>
            <h1 style="font-size: 1.6rem; font-weight: 800;"><?= Response::escape($supplier['name']) ?></h1>
            <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 0.25rem;">
                PIC: <strong><?= Response::escape($supplier['contact_person'] ?? '-') ?></strong> | 
                Telp: <?= Response::escape($supplier['phone'] ?? '-') ?> | 
                Email: <?= Response::escape($supplier['email'] ?? '-') ?>
            </p>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                Alamat: <?= Response::escape($supplier['address'] ?? '-') ?>
            </p>
        </div>
    </div>
</div>

<div class="card">
    <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between;">
        <span>📦 Produk yang Disuplai oleh <?= Response::escape($supplier['name']) ?></span>
        <span class="badge badge-primary"><?= count($suppliedProducts) ?> produk</span>
    </h3>

    <?php if (empty($suppliedProducts)): ?>
        <p style="color: var(--text-muted); font-size: 0.9rem; padding: 1.5rem 0; text-align: center;">
            Belum ada produk yang dikaitkan dengan supplier ini.
        </p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Harga Pasokan Supplier</th>
                        <th>Harga Jual Retail</th>
                        <th>Stok Saat Ini</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliedProducts as $sp): ?>
                        <tr>
                            <td><code><?= Response::escape($sp['sku']) ?></code></td>
                            <td style="font-weight: 600;">
                                <a href="/products/show/<?= $sp['id'] ?>">
                                    <?= Response::escape($sp['name']) ?>
                                </a>
                            </td>
                            <td><?= Response::escape($sp['category_name']) ?></td>
                            <td style="font-weight: 600; color: var(--success);">
                                <?= !empty($sp['supplier_price']) ? Response::formatRupiah($sp['supplier_price']) : '-' ?>
                            </td>
                            <td style="font-weight: 600;">
                                <?= Response::formatRupiah($sp['price']) ?>
                            </td>
                            <td>
                                <strong><?= $sp['stock'] ?></strong> unit
                            </td>
                            <td style="text-align: right;">
                                <a href="/products/show/<?= $sp['id'] ?>" class="btn btn-secondary btn-sm">Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
