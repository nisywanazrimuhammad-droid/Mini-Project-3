<?php
use App\Core\Response;
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 700;">Manajemen Pengguna & Profil (Admin)</h2>
        <p style="color: var(--text-muted); font-size: 0.88rem;">
            Kelola hak akses role (Admin & Staff) dan data profil 1-to-1 pengguna sistem.
        </p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-add-user')" id="btn-add-user">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Tambah Pengguna
    </button>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table class="table" id="table-users">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Nama Lengkap (Profil)</th>
                    <th>Role</th>
                    <th>Kontak (Telp / Alamat)</th>
                    <th>Transaksi Dicatat</th>
                    <th>Terdaftar Sejak</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: var(--text-main);">
                                <code>@<?= Response::escape($u['username']) ?></code>
                            </div>
                        </td>
                        <td>
                            <strong><?= Response::escape($u['full_name']) ?></strong>
                        </td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge badge-primary">Admin</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Staff</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 0.85rem; color: var(--text-muted);">
                            <div><?= Response::escape($u['phone'] ?? '-') ?></div>
                            <small><?= Response::escape($u['address'] ?? '') ?></small>
                        </td>
                        <td>
                            <span class="badge badge-secondary"><?= $u['movement_count'] ?> transaksi</span>
                        </td>
                        <td style="font-size: 0.82rem; color: var(--text-muted);">
                            <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <div style="display: inline-flex; gap: 0.4rem;">
                                <button class="btn btn-secondary btn-sm"
                                        onclick='openEditUserModal(<?= json_encode($u) ?>)'>
                                    Edit
                                </button>
                                <?php if ($u['id'] !== $currentUserId): ?>
                                    <form action="/users/delete/<?= $u['id'] ?>" method="POST" style="display: inline;"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?');">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color: #ef4444;">
                                            Hapus
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge badge-secondary" style="font-size: 0.7rem;">Anda</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal-overlay" id="modal-add-user">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Pengguna Baru</h3>
            <button type="button" class="btn-close" onclick="closeModal('modal-add-user')">✕</button>
        </div>
        <form action="/users/create" method="POST">
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" placeholder="staff_baru" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Role Akses *</label>
                    <select name="role" class="form-select" required>
                        <option value="staff" selected>Staff (Akses Produk & Transaksi)</option>
                        <option value="admin">Administrator (Akses Penuh)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Lengkap (Profil) *</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Nama lengkap petugas" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nomor Telepon</label>
                    <input type="text" name="phone" class="form-control" placeholder="08...">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" class="form-control" placeholder="Alamat tempat tinggal..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add-user')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal-overlay" id="modal-edit-user">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Edit Pengguna</h3>
            <button type="button" class="btn-close" onclick="closeModal('modal-edit-user')">✕</button>
        </div>
        <form id="form-edit-user" method="POST">
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" id="edit-u-username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak diubah">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Role Akses *</label>
                    <select name="role" id="edit-u-role" class="form-select" required>
                        <option value="staff">Staff</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Lengkap (Profil) *</label>
                    <input type="text" name="full_name" id="edit-u-fullname" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nomor Telepon</label>
                    <input type="text" name="phone" id="edit-u-phone" class="form-control">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" id="edit-u-address" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit-user')">Batal</button>
                <button type="submit" class="btn btn-primary">Perbarui Pengguna</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditUserModal(u) {
    document.getElementById('form-edit-user').action = '/users/edit/' + u.id;
    document.getElementById('edit-u-username').value = u.username || '';
    document.getElementById('edit-u-role').value = u.role || 'staff';
    document.getElementById('edit-u-fullname').value = u.full_name || '';
    document.getElementById('edit-u-phone').value = u.phone || '';
    document.getElementById('edit-u-address').value = u.address || '';
    openModal('modal-edit-user');
}
</script>
