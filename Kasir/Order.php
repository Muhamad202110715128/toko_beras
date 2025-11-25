<?php
include '../includes/config.php';
include '../includes/header.php';

// ===== FEFO: Ambil stok paling cepat kadaluarsa =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis = $_POST['jenis_beras'] ?? '';
    $merk = $_POST['merk'] ?? '';
    $jumlah_jual = (int)($_POST['jumlah'] ?? 0);
    $harga = (float)($_POST['harga'] ?? 0);

    if ($jenis && $merk && $jumlah_jual > 0 && $harga > 0) {
        $stok_masuk = $koneksi->query("
            SELECT id, jumlah, tanggal_kadaluarsa 
            FROM stok_masuk 
            WHERE jenis_beras='$jenis' AND merk='$merk' AND jumlah > 0 
            ORDER BY tanggal_kadaluarsa ASC
        ");

        $sisa = $jumlah_jual;
        while ($row = $stok_masuk->fetch_assoc()) {
            if ($sisa <= 0) break;
            $ambil = min($sisa, $row['jumlah']);
            $id_masuk = (int)$row['id'];

            // Kurangi stok berdasarkan FEFO
            $koneksi->query("UPDATE stok_masuk SET jumlah = jumlah - $ambil WHERE id = $id_masuk");
            $sisa -= $ambil;
        }

        // Hitung total harga
        $total = $jumlah_jual * $harga;

        // Simpan ke tabel PENJUALAN (bukan stok_keluar)
        $koneksi->query("
            INSERT INTO penjualan (tanggal, jenis_beras, merk, jumlah, harga, total_harga)
            VALUES (NOW(), '$jenis', '$merk', '$jumlah_jual', '$harga', '$total')
        ");

        echo '<div class="alert alert-success">✅ Transaksi berhasil disimpan dan stok telah diperbarui (FEFO).</div>';
    } else {
        echo '<div class="alert alert-danger">⚠️ Mohon isi semua field dengan benar.</div>';
    }
}

// ===== Ambil daftar penjualan =====
$q_penjualan = $koneksi->query("SELECT * FROM penjualan ORDER BY tanggal DESC");

// ===== Ambil stok menipis =====
$stok_low = $koneksi->query("
    SELECT 
        masuk.jenis_beras AS jenis_beras,
        masuk.merk AS merk,
        (SUM(masuk.jumlah) - IFNULL((
            SELECT SUM(penjualan.jumlah) FROM penjualan 
            WHERE penjualan.jenis_beras = masuk.jenis_beras 
              AND penjualan.merk = masuk.merk
        ), 0)) AS stok_tersisa
    FROM stok_masuk AS masuk
    GROUP BY masuk.jenis_beras, masuk.merk
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
                <a href="/toko_beras/kasir/dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                <a href="/toko_beras/kasir/stok_masuk.php" class="list-group-item list-group-item-action">Transaksik</a>
                <a href="/toko_beras/kasir/stok_keluar.php" class="list-group-item list-group-item-action">Laporan</a>
                <a href="/toko_beras/kasir/low_stock.php" class="list-group-item list-group-item-action">Low Stock</a>
                <div class="list-group-item">
                    <a href="/toko_beras/logout.php" class="btn btn-outline-danger w-100">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <h4 class="mb-4">🧾 Transaksi Penjualan</h4>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="POST" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Jenis Beras</label>
                    <select name="jenis_beras" class="form-select" required>
                        <option value="">-- Pilih Jenis --</option>
                        <option value="Pulen">Pulen</option>
                        <option value="Pandan Wangi">Pandan Wangi</option>
                        <option value="Merah">Merah</option>
                        <option value="Putih">Putih</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Merk</label>
                    <select name="merk" class="form-select" required>
                        <option value="">-- Pilih Merk --</option>
                        <option value="Idola">Idola</option>
                        <option value="MM">MM</option>
                        <option value="SB">SB</option>
                        <option value="HJ">HJ</option>
                        <option value="DT">DT</option>
                        <option value="TW">TW</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jumlah (kg)</label>
                    <input type="number" name="jumlah" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Harga (Rp/kg)</label>
                    <input type="number" name="harga" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-success w-100">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <h5 class="mb-3">🗃️ Riwayat Transaksi</h5>
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
                        if ($q_penjualan->num_rows > 0) {
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
                                    <td><a href='cetak_nota.php?id={$row['id_penjualan']}' class='btn btn-sm btn-primary'>Cetak</a></td>
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

    <h5 class="mb-3 text-danger">📅 Notifikasi Stok Menipis</h5>
    <div class="card border-danger shadow-sm">
        <div class="card-body">
            <?php if ($stok_low->num_rows > 0): ?>
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
                                <td><?= (int)$s['stok_tersisa'] ?></td>
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