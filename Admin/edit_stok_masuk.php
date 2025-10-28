<?php
include '../includes/config.php';

// ambil id dulu
$id = (int)($_GET['id'] ?? 0);

// Proses form harus dijalankan sebelum mengeluarkan HTML (include header)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'] ?? '';
    $tanggal_kadaluarsa = $_POST['tanggal_kadaluarsa'] ?? '';
    $jenis_beras = $_POST['jenis_beras'] ?? '';
    $merk = $_POST['merk'] ?? '';
    $jumlah = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 0;
    $harga_beli = isset($_POST['harga_beli']) ? (float)$_POST['harga_beli'] : 0;

    // validasi singkat
    if ($tanggal === '' || $jenis_beras === '') {
        // kembalikan ke form dengan error (opsional)
        header("Location: edit_stok_masuk.php?id={$id}&error=invalid");
        exit;
    }

    $stmt = $koneksi->prepare("UPDATE stok_masuk SET tanggal = ?, tanggal_kadaluarsa = ?, jenis_beras = ?, merk = ?, jumlah = ?, harga_beli = ? WHERE id = ?");
    $stmt->bind_param('ssssidi', $tanggal, $tanggal_kadaluarsa, $jenis_beras, $merk, $jumlah, $harga_beli, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: stok_masuk.php");
    exit;
}

// Ambil data untuk ditampilkan di form
$stmt = $koneksi->prepare("SELECT * FROM stok_masuk WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

// setelah semua pemrosesan, include header dan tampilkan form
include '../includes/header.php';
?>

<div class="container">
    <h4>Edit Data Stok Masuk</h4>
    <form method="POST">
        <div class="mb-3">
            <label>Tanggal Masuk</label>
            <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($data['tanggal'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label>Tanggal Kadaluarsa</label>
            <input type="date" name="tanggal_kadaluarsa" class="form-control" value="<?= htmlspecialchars($data['tanggal_kadaluarsa'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label>Jenis Beras</label>
            <input type="text" name="jenis_beras" class="form-control" value="<?= htmlspecialchars($data['jenis_beras'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label>Merk Beras</label>
            <input type="text" name="merk" class="form-control" value="<?= htmlspecialchars($data['merk'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label>Jumlah (kg)</label>
            <input type="number" name="jumlah" class="form-control" value="<?= htmlspecialchars($data['jumlah'] ?? 0) ?>" required>
        </div>
        <div class="mb-3">
            <label>Harga Beli (Rp)</label>
            <input type="number" name="harga_beli" class="form-control" value="<?= htmlspecialchars($data['harga_beli'] ?? 0) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="stok_masuk.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>