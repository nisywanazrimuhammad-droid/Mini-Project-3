<?php
use App\Core\Response;
?>
<div class="card" style="max-width: 600px; margin: 3rem auto; text-align: center; padding: 3rem;">
    <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
    <h2 style="font-size: 1.8rem; margin-bottom: 0.5rem;">404 - Halaman Tidak Ditemukan</h2>
    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
        <?= Response::escape($message ?? 'Halaman yang Anda tuju tidak ditemukan atau URL salah.') ?>
    </p>
    <div>
        <a href="/dashboard" class="btn btn-primary" id="btn-back-dashboard-404">Kembali ke Dashboard</a>
    </div>
</div>
