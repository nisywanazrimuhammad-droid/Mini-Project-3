<?php
use App\Core\Auth;
use App\Core\Response;
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 700;">Kategori Produk</h2>
        <p style="color: var(--text-muted); font-size: 0.88rem;">Klasifikasi produk dan statistik agregat stok per kategori.</p>
    </div>
    <?php if (Auth::isAdmin()): ?>
    <button class="btn btn-primary" onclick="openModal('modal-add-category')" id="btn-add-category">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Tambah Kategori
    </button>
    <?php endif; ?>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table class="table" id="table-categories">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th>Total Produk</th>
                    <th>Total Stok</th>
                    <th>Rata-rata Harga</th>
                    <?php if (Auth::isAdmin()): ?>
                    <th style="text-align: right;">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            Belum ada kategori yang ditambahkan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>#<?= $cat['id'] ?></td>
                            <td>
                                <strong><?= Response::escape($cat['name']) ?></strong>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.88rem;">
                                <?= Response::escape($cat['description'] ?? '-') ?>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?= $cat['total_products'] ?> produk</span>
                            </td>
                            <td>
                                <strong><?= number_format($cat['total_stock']) ?></strong> unit
                            </td>
                            <td style="font-weight: 600;">
                                <?= Response::formatRupiah($cat['average_price']) ?>
                            </td>
                            <?php if (Auth::isAdmin()): ?>
                            <td style="text-align: right; white-space: nowrap;">
                                <div style="display: inline-flex; gap: 0.4rem;">
                                    <button class="btn btn-secondary btn-sm" 
                                            onclick="openEditCategoryModal(<?= $cat['id'] ?>, '<?= Response::escape(addslashes($cat['name'])) ?>', '<?= Response::escape(addslashes($cat['description'] ?? '')) ?>')">
                                        Edit
                                    </button>
                                    <form action="/categories/delete/<?= $cat['id'] ?>" method="POST" style="display: inline;"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color: #ef4444;">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div class="modal-overlay" id="modal-add-category">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Kategori Baru</h3>
            <button type="button" class="btn-close" onclick="closeModal('modal-add-category')">✕</button>
        </div>
        <form action="/categories/create" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="cat-name" class="form-label">Nama Kategori *</label>
                    <input type="text" name="name" id="cat-name" class="form-control" placeholder="Contoh: Perangkat Audio" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="cat-desc" class="form-label">Deskripsi</label>
                    <textarea name="description" id="cat-desc" class="form-control" placeholder="Keterangan singkat kategori..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add-category')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal-overlay" id="modal-edit-category">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Edit Kategori</h3>
            <button type="button" class="btn-close" onclick="closeModal('modal-edit-category')">✕</button>
        </div>
        <form id="form-edit-cat" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit-cat-name" class="form-label">Nama Kategori *</label>
                    <input type="text" name="name" id="edit-cat-name" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="edit-cat-desc" class="form-label">Deskripsi</label>
                    <textarea name="description" id="edit-cat-desc" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit-category')">Batal</button>
                <button type="submit" class="btn btn-primary">Perbarui Kategori</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditCategoryModal(id, name, desc) {
    document.getElementById('form-edit-cat').action = '/categories/edit/' + id;
    document.getElementById('edit-cat-name').value = name;
    document.getElementById('edit-cat-desc').value = desc;
    openModal('modal-edit-category');
}
</script>
