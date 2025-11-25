<?php
include '../includes/config.php';
include '../includes/header.php';

// Ambil data dropdown
$q_jenis = $koneksi->query("SELECT * FROM jenis_beras ORDER BY nama_jenis ASC");
$q_merk  = $koneksi->query("SELECT * FROM merk_beras ORDER BY nama_merk ASC");
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

    <!-- Dropdown Jenis Beras -->
    <div class="mb-3">
        <label for="jenis_beras">Jenis Beras</label>
        <select name="jenis_beras" class="form-control" required>
            <option value="">-- Pilih Jenis Beras --</option>
            <?php while ($j = $q_jenis->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($j['nama_jenis']) ?>">
                    <?= htmlspecialchars($j['nama_jenis']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- Dropdown Merk Beras -->
    <div class="mb-3">
        <label for="merk">Merk Beras</label>
        <select name="merk" class="form-control" required>
            <option value="">-- Pilih Merk Beras --</option>
            <?php while ($m = $q_merk->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($m['nama_merk']) ?>">
                    <?= htmlspecialchars($m['nama_merk']) ?>
                </option>
            <?php endwhile; ?>
        </select>
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