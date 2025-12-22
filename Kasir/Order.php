<?php
include '../includes/config.php';
include '../includes/header.php';

// Ambil Data untuk Dropdown (Supaya tidak manual/hardcode)
$q_jenis_beras = $koneksi->query("SELECT * FROM jenis_beras ORDER BY nama_jenis ASC");
$q_merk_beras  = $koneksi->query("SELECT * FROM merk_beras ORDER BY nama_merk ASC");

// ==========================================
// PROSES TRANSAKSI (FEFO + INSERT STOK KELUAR)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis = $_POST['jenis_beras'] ?? '';
    $merk = $_POST['merk'] ?? '';
    $jumlah_jual = (int)($_POST['jumlah'] ?? 0);
    $harga = (float)($_POST['harga'] ?? 0);

    if ($jenis && $merk && $jumlah_jual > 0 && $harga > 0) {

        // 1. CEK TOTAL STOK TERSEDIA DULU
        $cek_stok = $koneksi->query("SELECT SUM(jumlah) as total FROM stok_masuk WHERE jenis_beras='$jenis' AND merk='$merk'");
        $data_stok = $cek_stok->fetch_assoc();
        $total_tersedia = (int)($data_stok['total'] ?? 0);

        if ($total_tersedia < $jumlah_jual) {
            echo '<div class="alert alert-danger">⚠️ Stok tidak cukup! Tersedia: ' . $total_tersedia . ' kg.</div>';
        } else {
            // Stok Cukup, Lanjut Proses

            // Ambil stok berdasarkan FEFO (First Expired First Out)
            $stok_masuk = $koneksi->query("
                SELECT id, jumlah, tanggal_kadaluarsa 
                FROM stok_masuk 
                WHERE jenis_beras='$jenis' AND merk='$merk' AND jumlah > 0 
                ORDER BY tanggal_kadaluarsa ASC, tanggal ASC
            ");

            $sisa_minta = $jumlah_jual;
            $berhasil = true;

            // Mulai Loop Pengurangan Stok
            while ($row = $stok_masuk->fetch_assoc()) {
                if ($sisa_minta <= 0) break;

                $id_masuk = (int)$row['id'];
                $stok_batch = (int)$row['jumlah'];
                $tgl_kadaluarsa = $row['tanggal_kadaluarsa'];

                // Tentukan berapa yang diambil dari batch ini
                $ambil = min($sisa_minta, $stok_batch);

                // A. UPDATE STOK MASUK (Kurangi)
                $update = $koneksi->query("UPDATE stok_masuk SET jumlah = jumlah - $ambil WHERE id = $id_masuk");

                // B. INSERT KE STOK KELUAR (Agar Admin Tahu) -> INI YANG ANDA MINTA
                if ($update) {
                    $koneksi->query("
                        INSERT INTO stok_keluar (tanggal, jenis_beras, merk, jumlah, tanggal_kadaluarsa, harga_jual, alasan)
                        VALUES (NOW(), '$jenis', '$merk', '$ambil', '$tgl_kadaluarsa', '$harga', 'Penjualan Kasir')
                    ");
                }

                $sisa_minta -= $ambil;
            }

            // C. SIMPAN KE TABEL PENJUALAN (Untuk Riwayat Kasir)
            // Hitung total harga
            $total_bayar = $jumlah_jual * $harga;

            $simpan_transaksi = $koneksi->query("
                INSERT INTO penjualan (tanggal, jenis_beras, merk, jumlah, harga, total_harga)
                VALUES (NOW(), '$jenis', '$merk', '$jumlah_jual', '$harga', '$total_bayar')
            ");

            if ($simpan_transaksi) {
                echo '<div class="alert alert-success">✅ Transaksi berhasil! Stok gudang otomatis diperbarui.</div>';
            } else {
                echo '<div class="alert alert-warning">⚠️ Transaksi berhasil diproses stoknya, tapi gagal simpan riwayat penjualan.</div>';
            }
        }
    } else {
        echo '<div class="alert alert-danger">⚠️ Mohon isi semua field dengan benar.</div>';
    }
}

// ===== Ambil daftar penjualan =====
$q_penjualan = $koneksi->query("SELECT * FROM penjualan ORDER BY tanggal DESC LIMIT 10");

// ===== Ambil stok menipis =====
$stok_low = $koneksi->query("
    SELECT jenis_beras, merk, SUM(jumlah) as stok_tersisa
    FROM stok_masuk
    GROUP BY jenis_beras, merk
    HAVING stok_tersisa <= 20
");
?>

<div class="container mt-4">
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
                <a href="/toko_beras/kasir/dashboard.php" class="list-group-item list-group-item-action ">Dashboard</a>
                <a href="/toko_beras/kasir/order.php" class="list-group-item list-group-item-action active">Transaksi</a>
                <a href="/toko_beras/kasir/items.php" class="list-group-item list-group-item-action ">Items</a>
                <a href="/toko_beras/kasir/revenue.php" class="list-group-item list-group-item-action">revenue</a>
                <a href="/toko_beras/kasir/sales.php" class="list-group-item list-group-item-action">sales</a>
                <div class="list-group-item">
                    <a href="/toko_beras/logout.php" class="btn btn-outline-danger w-100">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mb-4">🧾 Transaksi Penjualan</h4>

    <!-- FORM TRANSAKSI -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="POST" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Jenis Beras</label>
                    <select name="jenis_beras" class="form-select" required>
                        <option value="">-- Pilih Jenis --</option>
                        <!-- Mengambil dari database -->
                        <?php
                        if ($q_jenis_beras) {
                            $q_jenis_beras->data_seek(0);
                            while ($j = $q_jenis_beras->fetch_assoc()) {
                                echo "<option value='{$j['nama_jenis']}'>{$j['nama_jenis']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Merk</label>
                    <select name="merk" class="form-select" required>
                        <option value="">-- Pilih Merk --</option>
                        <!-- Mengambil dari database -->
                        <?php
                        if ($q_merk_beras) {
                            $q_merk_beras->data_seek(0);
                            while ($m = $q_merk_beras->fetch_assoc()) {
                                echo "<option value='{$m['nama_merk']}'>{$m['nama_merk']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jumlah (kg)</label>
                    <input type="number" name="jumlah" class="form-control" min="1" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Harga Jual (Rp/kg)</label>
                    <input type="number" name="harga" class="form-control" min="1" required>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-success w-100">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- TABEL RIWAYAT -->
    <h5 class="mb-3">🗃️ Riwayat Transaksi (Terbaru)</h5>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Merk</th>
                            <th>Jumlah (kg)</th>
                            <th>Harga (Rp/kg)</th>
                            <th>Total (Rp)</th>
                            <th>Cetak Nota</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if ($q_penjualan && $q_penjualan->num_rows > 0) {
                            while ($row = $q_penjualan->fetch_assoc()) {
                                $total = number_format($row['total_harga'], 0, ',', '.');
                                $harga = number_format($row['harga'], 0, ',', '.');
                                echo "<tr>
                                    <td>{$no}</td>
                                    <td>{$row['tanggal']}</td>
                                    <td>{$row['jenis_beras']}</td>
                                    <td>{$row['merk']}</td>
                                    <td>{$row['jumlah']}</td>
                                    <td>Rp {$harga}</td>
                                    <td>Rp {$total}</td>
                                    <td>
                                        <a href='cetak_nota.php?id={$row['id_penjualan']}' target='_blank' class='btn btn-sm btn-primary'>
                                            <i class='bi bi-printer'></i> Cetak
                                        </a>
                                    </td>
                                </tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='8' class='text-muted'>Belum ada transaksi.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TABEL STOK MENIPIS -->
    <h5 class="mb-3 text-danger">📅 Notifikasi Stok Menipis</h5>
    <div class="card border-danger shadow-sm">
        <div class="card-body">
            <?php if ($stok_low && $stok_low->num_rows > 0): ?>
                <table class="table table-sm table-bordered text-center align-middle">
                    <thead class="table-danger">
                        <tr>
                            <th>Jenis Beras</th>
                            <th>Merk</th>
                            <th>Sisa Stok (kg)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($s = $stok_low->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['jenis_beras']) ?></td>
                                <td><?= htmlspecialchars($s['merk']) ?></td>
                                <td class="fw-bold"><?= (int)$s['stok_tersisa'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-success mb-0">✅ Semua stok masih aman.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>