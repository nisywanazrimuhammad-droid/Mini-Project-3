<?php
use App\Core\Response;
?>

<div style="margin-bottom: 1.5rem;">
    <a href="/transactions" style="color: var(--text-muted); font-size: 0.88rem; display: inline-flex; align-items: center; gap: 0.35rem;">
        ← Kembali ke Riwayat Mutasi
    </a>
</div>

<div class="card" style="max-width: 680px; margin: 0 auto;">
    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-subtle);">
        <div class="stat-icon-wrapper danger" style="width: 44px; height: 44px;">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
        </div>
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 700;">Catat Barang Keluar (Stock Out)</h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">Mengurangi kuantitas stok produk untuk penjualan atau distribusi.</p>
        </div>
    </div>

    <form action="/transactions/out" method="POST" id="form-stock-out" onsubmit="return validateStockOutForm()">
        <div class="form-group">
            <label for="product_id" class="form-label">Pilih Produk Sumber *</label>
            <select name="product_id" id="product_id" class="form-select" required onchange="updateProductStockDisplay(this)">
                <option value="">-- Pilih Produk yang Dikeluarkan --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>" data-stock="<?= $p['stock'] ?>" data-sku="<?= Response::escape($p['sku']) ?>"
                            <?= ($selectedProductId == $p['id']) ? 'selected' : '' ?>>
                        [<?= Response::escape($p['sku']) ?>] <?= Response::escape($p['name']) ?> (Sisa: <?= $p['stock'] ?> unit)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="product-info-box" style="display: none; padding: 0.75rem 1rem; background: var(--bg-body); border-radius: var(--radius-sm); margin-bottom: 1.25rem; font-size: 0.88rem;">
            Stok yang dapat dikeluarkan: <strong id="current-stock-label" style="color: var(--primary);">0</strong> unit.
        </div>

        <div class="form-group">
            <label for="quantity" class="form-label">Jumlah Barang Keluar (Quantity) *</label>
            <input type="number" min="1" name="quantity" id="quantity" class="form-control" placeholder="1" required autofocus>
            <small style="color: #ef4444; font-size: 0.75rem;">Aturan Bisnis: Barang keluar tidak boleh melebihi stok yang tersedia.</small>
        </div>

        <div class="form-group">
            <label for="notes" class="form-label">Tujuan Pengiriman / Alasan Pengeluaran</label>
            <textarea name="notes" id="notes" class="form-control" placeholder="Contoh: Pengiriman pesanan invoice #INV-9821 atau pemakaian kantor..."></textarea>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <a href="/transactions" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-danger" id="btn-submit-out">Keluarkan Barang</button>
        </div>
    </form>
</div>

<script>
let currentMaxStock = 0;

function updateProductStockDisplay(selectElem) {
    const selectedOpt = selectElem.options[selectElem.selectedIndex];
    const infoBox = document.getElementById('product-info-box');
    const label = document.getElementById('current-stock-label');
    const qtyInput = document.getElementById('quantity');

    if (selectedOpt && selectedOpt.value) {
        currentMaxStock = parseInt(selectedOpt.getAttribute('data-stock'), 10) || 0;
        label.textContent = currentMaxStock;
        infoBox.style.display = 'block';
        qtyInput.setAttribute('max', currentMaxStock);
    } else {
        currentMaxStock = 0;
        infoBox.style.display = 'none';
        qtyInput.removeAttribute('max');
    }
}

function validateStockOutForm() {
    const qty = parseInt(document.getElementById('quantity').value, 10);
    if (qty > currentMaxStock) {
        alert('Gagal: Jumlah pengeluaran (' + qty + ') melebihi stok yang tersedia (' + currentMaxStock + ')!');
        return false;
    }
    return true;
}

document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('product_id');
    if (sel && sel.value) updateProductStockDisplay(sel);
});
</script>
