<?php
include '../includes/config.php';
include '../includes/header.php';
?>

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