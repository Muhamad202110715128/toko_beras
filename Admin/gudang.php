<?php
include '../includes/config.php';
include '../includes/header.php';

// Cek Akses Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>window.location='../login.php';</script>";
    exit;
}

// ==========================================
// 1. FILTER TANGGAL (Untuk Tabel Stok Keluar & Analisa)
// ==========================================
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');

// ==========================================
// 2. QUERY UTAMA
// ==========================================

// A. Stok Masuk / Tersedia (Real-time Gudang)
// Menampilkan akumulasi stok fisik yang ada sekarang
$q_tersedia = $koneksi->query("
    SELECT jenis_beras, merk, SUM(jumlah) as total_fisik 
    FROM stok_masuk 
    GROUP BY jenis_beras, merk 
    HAVING total_fisik > 0
    ORDER BY total_fisik DESC
");

// B. Stok Keluar (Berdasarkan Filter Tanggal)
$q_keluar = $koneksi->query("
    SELECT * FROM stok_keluar 
    WHERE DATE(tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
    ORDER BY tanggal DESC
");

// C. Stok Low (Kritis <= 20kg)
$q_low = $koneksi->query("
    SELECT jenis_beras, merk, SUM(jumlah) as sisa 
    FROM stok_masuk 
    GROUP BY jenis_beras, merk 
    HAVING sisa <= 20
    ORDER BY sisa ASC
");

// D. Mendekati Kadaluarsa (30 Hari ke depan)
$tgl_warning = date('Y-m-d', strtotime('+30 days'));
$q_exp = $koneksi->query("
    SELECT * FROM stok_masuk 
    WHERE tanggal_kadaluarsa <= '$tgl_warning' AND jumlah > 0 
    ORDER BY tanggal_kadaluarsa ASC
");

?>

<style>
    /* Style Panel Filter Mirip Gambar */
    .filter-panel {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 25px;
    }

    .table-card {
        border: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
    }

    .card-header-custom {
        background-color: #fff;
        border-bottom: 2px solid #f0f0f0;
        padding: 15px 20px;
        font-weight: bold;
    }
</style>

<!-- SIDEBAR -->
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
            <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
            <a href="gudang.php" class="list-group-item list-group-item-action active">Stok Gudang</a>
            <a href="stok_masuk.php" class="list-group-item list-group-item-action">Input Stok Masuk</a>
            <a href="stok_keluar.php" class="list-group-item list-group-item-action">Stok Keluar</a>
            <a href="low_stock.php" class="list-group-item list-group-item-action">Low Stock</a>
            <div class="list-group-item">
                <a href="../logout.php" class="btn btn-outline-danger w-100">Logout</a>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4">

    <h4 class="mb-4 fw-bold text-dark">📦 Manajemen Gudang</h4>

    <!-- FITUR FILTER TANGGAL (SEPERTI GAMBAR) -->
    <div class="filter-panel">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label text-muted fw-bold small">Dari Tanggal</label>
                <div class="input-group">
                    <input type="date" name="tgl_awal" class="form-control" value="<?= $tgl_awal ?>">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted fw-bold small">Sampai Tanggal</label>
                <div class="input-group">
                    <input type="date" name="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?>">
                </div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <i class="bi bi-funnel"></i> Tampilkan Data
                </button>
            </div>
        </form>
    </div>

    <div class="row">

        <!-- 1. TABEL STOK MASUK / TERSEDIA (KIRI ATAS) -->
        <div class="col-lg-6">
            <div class="card table-card h-100">
                <div class="card-header-custom text-primary">
                    <i class="bi bi-box-seam me-2"></i> Stok Tersedia (Gudang Fisik)
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item Beras</th>
                                <th>Merk</th>
                                <th class="text-end">Total Fisik</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($q_tersedia->num_rows > 0): ?>
                                <?php while ($r = $q_tersedia->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold"><?= $r['jenis_beras'] ?></td>
                                        <td><?= $r['merk'] ?></td>
                                        <td class="text-end fw-bold text-primary"><?= $r['total_fisik'] ?> kg</td>
                                        <td class="text-center"><span class="badge bg-success">Ready</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">Gudang Kosong</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. TABEL STOK LOW (KANAN ATAS) -->
        <div class="col-lg-6">
            <div class="card table-card h-100 border-danger">
                <div class="card-header-custom text-danger bg-danger bg-opacity-10">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Stok Low (Kritis &le; 20kg)
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item Beras</th>
                                <th>Merk</th>
                                <th class="text-end">Sisa Stok</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($q_low->num_rows > 0): ?>
                                <?php while ($r = $q_low->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $r['jenis_beras'] ?></td>
                                        <td><?= $r['merk'] ?></td>
                                        <td class="text-end fw-bold text-danger"><?= $r['sisa'] ?> kg</td>
                                        <td class="text-center"><span class="badge bg-danger">Restok!</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-success fw-bold">Aman! Tidak ada stok kritis.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. TABEL STOK KELUAR (KIRI BAWAH) -->
        <div class="col-lg-8 mt-4">
            <div class="card table-card">
                <div class="card-header-custom text-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-arrow-right me-2"></i> Riwayat Stok Keluar (Terjual)</span>
                        <small class="text-muted fw-normal">Filter: <?= date('d/m/y', strtotime($tgl_awal)) ?> - <?= date('d/m/y', strtotime($tgl_akhir)) ?></small>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Item</th>
                                <th>Merk</th>
                                <th class="text-end">Jumlah</th>
                                <th>Tujuan/Ket</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($q_keluar->num_rows > 0): ?>
                                <?php while ($r = $q_keluar->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                                        <td><?= $r['jenis_beras'] ?></td>
                                        <td><?= $r['merk'] ?></td>
                                        <td class="text-end fw-bold">-<?= $r['jumlah'] ?> kg</td>
                                        <td><small class="text-muted"><?= $r['alasan'] ?: 'Penjualan' ?></small></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">Tidak ada barang keluar di periode ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 4. TABEL MENDEKATI KADALUARSA (KANAN BAWAH) -->
        <div class="col-lg-4 mt-4">
            <div class="card table-card border-warning">
                <div class="card-header-custom text-dark bg-warning bg-opacity-25">
                    <i class="bi bi-clock-history me-2"></i> Mendekati Expired (< 30 Hari)
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th>Exp Date</th>
                                        <th class="text-end">Sisa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($q_exp->num_rows > 0): ?>
                                        <?php while ($r = $q_exp->fetch_assoc()):
                                            $d = date('d/m/y', strtotime($r['tanggal_kadaluarsa']));
                                        ?>
                                            <tr>
                                                <td>
                                                    <strong><?= $r['jenis_beras'] ?></strong><br>
                                                    <small class="text-muted"><?= $r['merk'] ?></small>
                                                </td>
                                                <td class="text-danger fw-bold"><?= $d ?></td>
                                                <td class="text-end"><?= $r['jumlah'] ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted">Aman. Tidak ada barang expired.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                </div>
            </div>

        </div>
    </div>

    <?php include '../includes/footer.php'; ?>