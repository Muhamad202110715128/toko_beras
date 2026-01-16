<?php
// Pastikan path include benar
include '../includes/config.php';
include '../includes/header.php';

// Cek akses (HANYA PEMILIK)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pemilik') {
    echo "<script>window.location='../login.php';</script>";
    exit;
}


// ==========================================
// 1. FILTER TANGGAL (Default: Bulan Ini)
// ==========================================
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');

// ==========================================
// 2. QUERY CARD RINGKASAN (DASHBOARD)
// ==========================================

// A. Total Omset (Penjualan Kasir)
$q_omset = $koneksi->query("
    SELECT SUM(total_harga) as total, COUNT(*) as jumlah_transaksi 
    FROM penjualan 
    WHERE DATE(tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$d_omset = $q_omset->fetch_assoc();
$total_omset = $d_omset['total'] ?? 0;
$total_transaksi = $d_omset['jumlah_transaksi'] ?? 0;

// B. Total Belanja Stok (Barang Masuk - Pengeluaran)
$q_belanja = $koneksi->query("
    SELECT SUM(harga_beli) as total_beli, SUM(jumlah) as total_kg_masuk 
    FROM stok_masuk 
    WHERE DATE(tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$d_belanja = $q_belanja->fetch_assoc();
$total_belanja = $d_belanja['total_beli'] ?? 0;
$total_kg_masuk = $d_belanja['total_kg_masuk'] ?? 0;

// C. Stok Keluar (Gudang Terjual)
$q_keluar = $koneksi->query("
    SELECT SUM(jumlah) as total_kg 
    FROM stok_keluar 
    WHERE DATE(tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$total_kg_keluar = $q_keluar->fetch_assoc()['total_kg'] ?? 0;

// D. Stok Menipis (Global < 20kg)
$q_low = $koneksi->query("
    SELECT COUNT(*) as jumlah_item FROM (
        SELECT SUM(jumlah) as total_sisa 
        FROM stok_masuk 
        GROUP BY jenis_beras, merk 
        HAVING total_sisa <= 20
    ) as subquery
");
$total_low_stock = $q_low->fetch_assoc()['jumlah_item'] ?? 0;

// E. Hampir Kadaluarsa (30 Hari ke depan)
$tgl_warning = date('Y-m-d', strtotime('+30 days'));
$q_exp = $koneksi->query("
    SELECT COUNT(*) as jumlah_batch 
    FROM stok_masuk 
    WHERE tanggal_kadaluarsa <= '$tgl_warning' AND jumlah > 0
");
$total_expired = $q_exp->fetch_assoc()['jumlah_batch'] ?? 0;

?>

<link rel="stylesheet" href="laporan.css">

<div class="container mt-4">

    <!-- SIDEBAR OWNER (Agar bisa navigasi) -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="sidebarMenu">
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
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="list-group list-group-flush">
                <!-- Sesuaikan link 'laporan.php' dengan nama file laporan Anda yang benar -->
                <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                <a href="laporan.php" class="list-group-item list-group-item-action active">Laporan Eksekutif</a>
                <div class="list-group-item">
                    <a href="../logout.php" class="btn btn-outline-danger w-100">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <!-- HEADER & FILTER -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h4 class="mb-0 fw-bold">📊 Laporan Eksekutif</h4>
            <small class="text-muted">Ringkasan aktivitas Bisnis (Kasir & Gudang)</small>
        </div>
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer"></i> Cetak Laporan
        </button>
    </div>

    <!-- FORM FILTER TANGGAL -->
    <div class="card mb-4 shadow-sm no-print">
        <div class="card-body py-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Dari Tanggal</label>
                    <input type="date" name="tgl_awal" class="form-control" value="<?= $tgl_awal ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Sampai Tanggal</label>
                    <input type="date" name="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter"></i> Tampilkan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 1. RINGKASAN KARTU (DASHBOARD) -->
    <div class="row mb-4">
        <!-- Card Omset -->
        <div class="col-md">
            <div class="card shadow-sm h-100 card-dashboard border-success">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold">Total Pemasukan</small>
                    <h4 class="fw-bold text-success mt-2">Rp <?= number_format($total_omset, 0, ',', '.') ?></h4>
                    <small class="text-muted"><i class="bi bi-receipt"></i> <?= $total_transaksi ?> Transaksi</small>
                </div>
            </div>
        </div>

        <!-- Card Belanja -->
        <div class="col-md">
            <div class="card shadow-sm h-100 card-dashboard border-danger">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold">Total Belanja Stok</small>
                    <h4 class="fw-bold text-danger mt-2">Rp <?= number_format($total_belanja, 0, ',', '.') ?></h4>
                    <small class="text-muted"><i class="bi bi-box-seam"></i> Masuk: <?= $total_kg_masuk ?> kg</small>
                </div>
            </div>
        </div>

        <!-- Card Profit -->
        <div class="col-md">
            <div class="card shadow-sm h-100 card-dashboard border-primary">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold">Selisih (Cashflow)</small>
                    <?php $selisih = $total_omset - $total_belanja; ?>
                    <h4 class="fw-bold <?= $selisih >= 0 ? 'text-primary' : 'text-warning' ?> mt-2">
                        Rp <?= number_format($selisih, 0, ',', '.') ?>
                    </h4>
                    <small class="text-muted">Pemasukan - Belanja</small>
                </div>
            </div>
        </div>

        <!-- Card Peringatan -->
        <div class="col-md">
            <div class="card shadow-sm h-100 card-dashboard border-warning">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold">Perlu Perhatian</small>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="text-danger fw-bold"><?= $total_low_stock ?> <small class="text-muted fw-normal">Menipis</small></span>
                        <span class="text-warning fw-bold"><?= $total_expired ?> <small class="text-muted fw-normal">Exp</small></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- KOLOM KIRI: LAPORAN KEUANGAN & STOK MASUK -->
        <div class="col-lg-8 mb-4">

            <!-- 2. TABEL PENJUALAN (INCOME) -->
            <div class="card shadow-sm mb-4">
                <!-- PERUBAHAN DI SINI: D-FLEX UNTUK JUDUL DAN TOMBOL -->
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-success"><i class="bi bi-graph-up-arrow"></i> Laporan Penjualan (Kasir)</h6>
                    <a href="detail_penjualan.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn btn-sm btn-outline-success">
                        Lihat Detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q_jual = $koneksi->query("
                                SELECT * FROM penjualan 
                                WHERE DATE(tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
                                ORDER BY tanggal DESC LIMIT 5
                            ");
                            if ($q_jual->num_rows > 0) {
                                while ($r = $q_jual->fetch_assoc()) {
                                    echo "<tr>
                                        <td>" . date('d/m/y H:i', strtotime($r['tanggal'])) . "</td>
                                        <td>{$r['jenis_beras']} <small class='text-muted'>({$r['merk']})</small></td>
                                        <td class='text-center'>{$r['jumlah']} kg</td>
                                        <td class='text-end fw-bold'>Rp " . number_format($r['total_harga'], 0, ',', '.') . "</td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center py-3 text-muted'>Tidak ada penjualan periode ini.</td></tr>";
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-center bg-light small text-muted">
                                    <i>Menampilkan 5 transaksi terbaru.</i>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- 3. TABEL BARANG MASUK (EXPENSE) -->
            <div class="card shadow-sm">
                <!-- PERUBAHAN DI SINI: D-FLEX UNTUK JUDUL DAN TOMBOL -->
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-danger"><i class="bi bi-box-seam"></i> Laporan Barang Masuk (Gudang)</h6>
                    <a href="detail_stok_masuk.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn btn-sm btn-outline-danger">
                        Lihat Detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal Masuk</th>
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Nilai Pembelian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query Barang Masuk
                            $q_masuk_list = $koneksi->query("
                                SELECT * FROM stok_masuk 
                                WHERE DATE(tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
                                ORDER BY tanggal DESC LIMIT 5
                            ");

                            if ($q_masuk_list->num_rows > 0) {
                                while ($rm = $q_masuk_list->fetch_assoc()) {
                                    $nilai_beli = $rm['harga_beli'] ?? 0;
                                    echo "<tr>
                                        <td>" . date('d/m/Y', strtotime($rm['tanggal'])) . "</td>
                                        <td>
                                            {$rm['jenis_beras']} <small class='text-muted'>({$rm['merk']})</small>
                                            <br><small class='text-danger' style='font-size:0.75rem'>Exp: " . date('d/m/y', strtotime($rm['tanggal_kadaluarsa'])) . "</small>
                                        </td>
                                        <td class='text-center'>{$rm['jumlah']} kg</td>
                                        <td class='text-end text-danger'>Rp " . number_format($nilai_beli, 0, ',', '.') . "</td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center py-3 text-muted'>Tidak ada barang masuk periode ini.</td></tr>";
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-center bg-light small text-muted">
                                    <i>Menampilkan 5 transaksi terbaru.</i>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>

        <!-- KOLOM KANAN: PERINGATAN (ALERT) -->
        <div class="col-lg-4 mb-4">

            <!-- STOK MENIPIS -->
            <div class="card shadow-sm mb-3">
                <!-- PERUBAHAN DI SINI: D-FLEX UNTUK JUDUL DAN TOMBOL -->
                <div class="card-header bg-danger text-white py-2 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold"><i class="bi bi-exclamation-triangle"></i> Stok Kritis</h6>
                    <a href="detail_low_stok.php" class="btn btn-sm btn-light text-danger py-0" style="font-size: 0.8rem;">Detail</a>
                </div>
                <ul class="list-group list-group-flush">
                    <?php
                    $q_low_list = $koneksi->query("
                        SELECT jenis_beras, merk, SUM(jumlah) as sisa 
                        FROM stok_masuk 
                        GROUP BY jenis_beras, merk 
                        HAVING sisa <= 20
                        ORDER BY sisa ASC LIMIT 5
                    ");
                    if ($q_low_list->num_rows > 0) {
                        while ($l = $q_low_list->fetch_assoc()) {
                            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>
                                <div>
                                    <strong>{$l['jenis_beras']}</strong> <br>
                                    <small class='text-muted'>{$l['merk']}</small>
                                </div>
                                <span class='badge bg-danger rounded-pill'>Sisa {$l['sisa']} kg</span>
                            </li>";
                        }
                    } else {
                        echo "<li class='list-group-item text-center text-muted'>Stok aman.</li>";
                    }
                    ?>
                </ul>
            </div>

            <!-- HAMPIR KADALUARSA -->
            <div class="card shadow-sm">
                <!-- PERUBAHAN DI SINI: D-FLEX UNTUK JUDUL DAN TOMBOL -->
                <div class="card-header bg-warning text-dark py-2 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold"><i class="bi bi-clock-history"></i> Mendekati Expired</h6>
                    <a href="detail_expired.php" class="btn btn-sm btn-light text-dark py-0" style="font-size: 0.8rem;">Detail</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Exp</th>
                                <th>Sisa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q_exp_list = $koneksi->query("
                                SELECT jenis_beras, merk, tanggal_kadaluarsa, jumlah 
                                FROM stok_masuk 
                                WHERE tanggal_kadaluarsa <= '$tgl_warning' AND jumlah > 0
                                ORDER BY tanggal_kadaluarsa ASC LIMIT 5
                            ");
                            if ($q_exp_list->num_rows > 0) {
                                while ($e = $q_exp_list->fetch_assoc()) {
                                    $days = (strtotime($e['tanggal_kadaluarsa']) - time()) / (60 * 60 * 24);
                                    $badge = $days < 7 ? 'text-danger fw-bold' : 'text-dark';

                                    echo "<tr>
                                        <td>{$e['jenis_beras']} ({$e['merk']})</td>
                                        <td class='$badge'>" . date('d/m/y', strtotime($e['tanggal_kadaluarsa'])) . "</td>
                                        <td class='text-center'>{$e['jumlah']}</td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' class='text-center text-muted'>Aman.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>