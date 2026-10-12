<?php
use App\Core\Auth;
use App\Core\Response;
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 700;">Daftar Supplier (Pemasok)</h2>
        <p style="color: var(--text-muted); font-size: 0.88rem;">Kelola mitra pemasok barang inventaris perusahaan.</p>
    </div>
    <?php if (Auth::isAdmin()): ?>
    <button class="btn btn-primary" onclick="openModal('modal-add-supplier')" id="btn-add-supplier">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Tambah Supplier
    </button>
    <?php endif; ?>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table class="table" id="table-suppliers">
            <thead>
                <tr>
                    <th>Nama Supplier</th>
                    <th>Kontak Person</th>
                    <th>Telepon / Email</th>
                    <th>Alamat</th>
                    <th>Produk Disuplai</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            Belum ada supplier yang terdaftar.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td>
                                <a href="/suppliers/show/<?= $s['id'] ?>" style="font-weight: 700; color: var(--text-main);">
                                    <?= Response::escape($s['name']) ?>
                                </a>
                            </td>
                            <td><?= Response::escape($s['contact_person'] ?? '-') ?></td>
                            <td>
                                <div><?= Response::escape($s['phone'] ?? '-') ?></div>
                                <small style="color: var(--text-muted);"><?= Response::escape($s['email'] ?? '') ?></small>
                            </td>
                            <td style="max-width: 260px; font-size: 0.85rem; color: var(--text-muted);">
                                <?= Response::escape($s['address'] ?? '-') ?>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?= $s['total_supplied_products'] ?> produk</span>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <div style="display: inline-flex; gap: 0.4rem;">
                                    <a href="/suppliers/show/<?= $s['id'] ?>" class="btn btn-secondary btn-sm">
                                        Detail
                                    </a>
                                    <?php if (Auth::isAdmin()): ?>
                                    <button class="btn btn-secondary btn-sm"
                                            onclick='openEditSupplierModal(<?= json_encode($s) ?>)'>
                                        Edit
                                    </button>
                                    <form action="/suppliers/delete/<?= $s['id'] ?>" method="POST" style="display: inline;"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus supplier ini?');">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color: #ef4444;">
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

<!-- Modal Tambah Supplier -->
<div class="modal-overlay" id="modal-add-supplier">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Supplier Baru</h3>
            <button type="button" class="btn-close" onclick="closeModal('modal-add-supplier')">✕</button>
        </div>
        <form action="/suppliers/create" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama Supplier *</label>
                    <input type="text" name="name" class="form-control" placeholder="PT / CV..." required>
                </div>
                <div class="form-group">
                    <label class="form-label">Kontak Person</label>
                    <input type="text" name="contact_person" class="form-control" placeholder="Nama PIC">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="text" name="phone" class="form-control" placeholder="08...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="supplier@example.com">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Alamat Kantor / Gudang</label>
                    <textarea name="address" class="form-control" placeholder="Alamat lengkap..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add-supplier')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Supplier</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Supplier -->
<div class="modal-overlay" id="modal-edit-supplier">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Edit Supplier</h3>
            <button type="button" class="btn-close" onclick="closeModal('modal-edit-supplier')">✕</button>
        </div>
        <form id="form-edit-supplier" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama Supplier *</label>
                    <input type="text" name="name" id="edit-s-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Kontak Person</label>
                    <input type="text" name="contact_person" id="edit-s-pic" class="form-control">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="text" name="phone" id="edit-s-phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit-s-email" class="form-control">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" id="edit-s-address" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit-supplier')">Batal</button>
                <button type="submit" class="btn btn-primary">Perbarui Supplier</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditSupplierModal(s) {
    document.getElementById('form-edit-supplier').action = '/suppliers/edit/' + s.id;
    document.getElementById('edit-s-name').value = s.name || '';
    document.getElementById('edit-s-pic').value = s.contact_person || '';
    document.getElementById('edit-s-phone').value = s.phone || '';
    document.getElementById('edit-s-email').value = s.email || '';
    document.getElementById('edit-s-address').value = s.address || '';
    openModal('modal-edit-supplier');
}
</script>
