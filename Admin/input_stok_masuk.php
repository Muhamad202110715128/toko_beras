<?php
include '../includes/config.php';
include '../includes/header.php';
?>

<!-- side bar -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header">
        <div class="d-flex align-items-center">
            <div class="user-avatar me-2" style="background: <?= htmlspecialchars($avatarBg) ?>;">
                <?= $icon ?>
            </div>
            <div>
                <div class="fw-bold"><?= htmlspecialchars($username ?: $roleLabel) ?></div>
                <small class="text-muted"><?= htmlspecialchars($roleLabel) ?></small>
            </div>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="list-group list-group-flush">
            <a href="/toko_beras/admin/dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
            <a href="/toko_beras/Admin/stok_masuk.php" class="list-group-item list-group-item-action">Stok Masuk</a>
            <a href="/toko_beras/admin/stok_keluar.php" class="list-group-item list-group-item-action">Stok Keluar</a>
            <a href="/toko_beras/admin/low_stock.php" class="list-group-item list-group-item-action">Low Stock</a>
            <div class="list-group-item">
                <a href="/toko_beras/logout.php" class="btn btn-outline-danger w-100">Logout</a>
            </div>
        </div>
    </div>
</div>


<h4>Input Stok Masuk</h4>
<form action="proses_stok_masuk.php" method="POST">
    <div class="mb-3">
        <label for="tanggal">Tanggal Masuk</label>
        <input type="date" name="tanggal" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="tanggal_kadaluarsa">Tanggal Kadaluarsa</label>
        <input type="date" name="tanggal_kadaluarsa" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="jenis_beras">Jenis Beras</label>
        <input type="text" name="jenis_beras" class="form-control" required>
    </div>

    <!-- Tambahkan field Merk Beras -->
    <div class="mb-3">
        <label for="merk">Merk Beras</label>
        <input type="text" name="merk" class="form-control" placeholder="Contoh: Cap X" required>
    </div>

    <div class="mb-3">
        <label for="jumlah">Jumlah (kg)</label>
        <input type="number" name="jumlah" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="harga_beli">Harga Beli (Rp)</label>
        <input type="number" name="harga_beli" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Simpan</button>
</form>
<?php include '../includes/footer.php'; ?>