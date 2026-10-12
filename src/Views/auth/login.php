<?php
use App\Core\Response;
?>

<div class="auth-card">
    <div class="auth-header">
        <div style="display: flex; justify-content: center; margin-bottom: 0.5rem;">
            <div class="brand-icon" style="width: 52px; height: 52px; font-size: 1.6rem;">📦</div>
        </div>
        <h1 class="auth-title">InvenTrack IMS</h1>
        <p class="auth-subtitle">Sistem Manajemen Inventaris & Mutasi Stok Terpadu</p>
    </div>

    <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= Response::escape($flash['type']) ?>">
            <span><?= Response::escape($flash['message']) ?></span>
        </div>
    <?php endif; ?>

    <form action="/login" method="POST" id="login-form">
        <div class="form-group">
            <label for="username" class="form-label">Username</label>
            <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required autofocus>
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
        </div>

        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem;" id="login-btn">
                Masuk ke Sistem
            </button>
        </div>
    </form>

    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-subtle); font-size: 0.82rem; color: var(--text-muted);">
        <strong>Akun Bawaan (Demo):</strong><br>
        • <strong>Admin:</strong> <code>admin</code> / <code>admin123</code> (Akses Penuh)<br>
        • <strong>Staff:</strong> <code>staff</code> / <code>staff123</code> (Akses Operasional)
    </div>
</div>
