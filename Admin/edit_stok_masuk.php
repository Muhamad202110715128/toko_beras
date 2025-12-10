<?php
include '../includes/config.php';

// Ambil ID
$id = (int)($_GET['id'] ?? 0);

// Jika ID 0 atau tidak valid, kembalikan ke halaman stok masuk
if ($id === 0) {
    header("Location: stok_masuk.php");
    exit;
}

// ==========================================
// PROSES SIMPAN DATA (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'] ?? '';
    $tanggal_kadaluarsa = $_POST['tanggal_kadaluarsa'] ?? '';

    // Ambil ID yang dikirim dari form (ini masih berupa angka)
    $id_jenis_input = $_POST['id_jenis'] ?? '';
    $id_merk_input = $_POST['id_merk'] ?? '';

    $jumlah = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 0;
    $harga_beli = isset($_POST['harga_beli']) ? (float)$_POST['harga_beli'] : 0;

    // --- PERBAIKAN UTAMA DI SINI ---
    // Kita tidak boleh menyimpan ID (angka), kita harus cari NAMA-nya dulu.

    // 1. Cari Nama Jenis Beras
    $nama_jenis_fix = '';
    if (!empty($id_jenis_input)) {
        $q_jenis = $koneksi->query("SELECT nama_jenis FROM jenis_beras WHERE id_jenis = '$id_jenis_input'");
        $d_jenis = $q_jenis->fetch_assoc();
        if ($d_jenis) {
            $nama_jenis_fix = $d_jenis['nama_jenis']; // Ambil teks "Pandan Wangi"
        }
    }

    // 2. Cari Nama Merk
    $nama_merk_fix = '';
    if (!empty($id_merk_input)) {
        $q_merk = $koneksi->query("SELECT nama_merk FROM merk_beras WHERE id_merk = '$id_merk_input'");
        $d_merk = $q_merk->fetch_assoc();
        if ($d_merk) {
            $nama_merk_fix = $d_merk['nama_merk']; // Ambil teks "Idola"
        }
    }
    // -------------------------------

    // Validasi sederhana
    if ($tanggal === '' || $nama_jenis_fix === '') {
        echo "<script>alert('Harap lengkapi data jenis beras!'); window.history.back();</script>";
        exit;
    }

    // Update Database
    // Perhatikan: Kita menyimpan variabel $nama_jenis_fix (Teks), BUKAN $id_jenis_input (Angka)
    $stmt = $koneksi->prepare("UPDATE stok_masuk SET tanggal = ?, tanggal_kadaluarsa = ?, jenis_beras = ?, merk = ?, jumlah = ?, harga_beli = ? WHERE id = ?");
    $stmt->bind_param('ssssidi', $tanggal, $tanggal_kadaluarsa, $nama_jenis_fix, $nama_merk_fix, $jumlah, $harga_beli, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Data berhasil diperbaiki!'); window.location.href='stok_masuk.php';</script>";
    } else {
        echo "<script>alert('Gagal mengubah data.'); window.history.back();</script>";
    }
    $stmt->close();
    exit;
}

// ==========================================
// AMBIL DATA LAMA UNTUK FORM
// ==========================================
$stmt = $koneksi->prepare("SELECT * FROM stok_masuk WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    echo "Data tidak ditemukan.";
    exit;
}

include '../includes/header.php';
?>

<!-- Sidebar (Disederhanakan) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="sidebarMenu">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menu</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="list-group list-group-flush">
            <a href="/toko_beras/admin/dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
            <a href="/toko_beras/Admin/stok_masuk.php" class="list-group-item list-group-item-action">Stok Masuk</a>
        </div>
    </div>
</div>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">Edit Data Stok Masuk</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Tanggal Masuk</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($data['tanggal'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tanggal Kadaluarsa</label>
                    <input type="date" name="tanggal_kadaluarsa" class="form-control" value="<?= htmlspecialchars($data['tanggal_kadaluarsa'] ?? '') ?>">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jenis Beras</label>
                        <select name="id_jenis" class="form-select" required>
                            <option value="">-- Pilih Jenis Beras --</option>
                            <?php
                            $jenis = $koneksi->query("SELECT * FROM jenis_beras ORDER BY nama_jenis ASC");
                            while ($row = $jenis->fetch_assoc()) {
                                // PERBAIKAN LOGIKA SELECTED:
                                // Kita bandingkan NAMA vs NAMA (karena di database tersimpan "Pandan Wangi", bukan "1")
                                $selected = ($row['nama_jenis'] == $data['jenis_beras']) ? 'selected' : '';
                                echo "<option value='{$row['id_jenis']}' $selected>{$row['nama_jenis']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Merk Beras</label>
                        <select name="id_merk" class="form-select" required>
                            <option value="">-- Pilih Merk --</option>
                            <?php
                            $merk = $koneksi->query("SELECT * FROM merk_beras ORDER BY nama_merk ASC");
                            while ($row = $merk->fetch_assoc()) {
                                // Sama, bandingkan NAMA vs NAMA
                                $selected = ($row['nama_merk'] == $data['merk']) ? 'selected' : '';
                                echo "<option value='{$row['id_merk']}' $selected>{$row['nama_merk']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jumlah (kg)</label>
                    <input type="number" name="jumlah" class="form-control" value="<?= htmlspecialchars($data['jumlah'] ?? 0) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Harga Beli (Rp)</label>
                    <input type="number" name="harga_beli" class="form-control" value="<?= htmlspecialchars($data['harga_beli'] ?? 0) ?>" required>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="stok_masuk.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>