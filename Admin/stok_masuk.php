<?php
include '../includes/config.php';
include '../includes/header.php';

// Cek Akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>window.location='../login.php';</script>";
    exit;
}

// ==========================================
// QUERY UNTUK KARTU RINGKASAN
// ==========================================

// 1. Total Stok Fisik (Semua Barang)
$q_total = $koneksi->query("SELECT SUM(jumlah) as total_kg FROM stok_masuk");
$d_total = $q_total->fetch_assoc();
$total_kg = $d_total['total_kg'] ?? 0;

// 2. Mendekati Kadaluarsa (<= 30 Hari & Stok > 0)
$tgl_warning = date('Y-m-d', strtotime('+30 days'));
$q_exp = $koneksi->query("SELECT COUNT(*) as jumlah_batch FROM stok_masuk WHERE tanggal_kadaluarsa <= '$tgl_warning' AND jumlah > 0");
$d_exp = $q_exp->fetch_assoc();
$total_exp = $d_exp['jumlah_batch'] ?? 0;

?>

<style>
    /* Styling Table Header */
    .table-detail thead th {
        background: #f8f9fa;
        font-weight: 700;
        border-bottom: 2px solid #dee2e6;
    }

    /* Hover Effect untuk Baris Tabel */
    .table-hover tbody tr:hover {
        background-color: #f1f3f5;
    }

    /* Indikator Expired Text */
    .text-expired {
        color: #dc3545 !important;
        /* Merah */
        font-weight: bold;
    }

    /* Styling Card Dashboard */
    .card-stat {
        border: none;
        border-left: 5px solid;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s;
    }

    .card-stat:hover {
        transform: translateY(-3px);
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
            <a href="stok_masuk.php" class="list-group-item list-group-item-action active">Stok Gudang</a>
            <a href="stok_keluar.php" class="list-group-item list-group-item-action">Stok Keluar</a>
            <a href="low_stock.php" class="list-group-item list-group-item-action">Low Stock</a>
            <div class="list-group-item">
                <a href="../logout.php" class="btn btn-outline-danger w-100">Logout</a>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4">

    <!-- HEADER PAGE -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">📥 Data Stok Masuk</h4>
            <small class="text-muted">Manajemen pembelian barang dari supplier</small>
        </div>
        <a href="input_stok_masuk.php" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i> Input Data Baru
        </a>
    </div>

    <!-- KARTU RINGKASAN (YANG DIMINTA) -->
    <div class="row mb-4">
        <!-- Card 1: Total Stok Masuk -->
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card h-100 card-stat border-primary">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                        <i class="bi bi-box-seam fs-2"></i>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase fw-bold mb-1">Total Stok Fisik</h6>
                        <h3 class="fw-bold mb-0 text-primary"><?= number_format($total_kg, 0, ',', '.') ?> <small class="fs-6 text-muted">kg</small></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Mendekati Kadaluarsa -->
        <div class="col-md-6">
            <div class="card h-100 card-stat border-danger">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-danger bg-opacity-10 text-danger rounded p-3 me-3">
                        <i class="bi bi-clock-history fs-2"></i>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase fw-bold mb-1">Mendekati Expired</h6>
                        <h3 class="fw-bold mb-0 text-danger"><?= $total_exp ?> <small class="fs-6 text-muted">Batch</small></h3>
                        <small class="text-muted" style="font-size: 0.75rem;">(Dalam 30 Hari)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL DATA -->
    <div class="card shadow-sm border-0">
        <!-- Fitur Pencarian -->
        <div class="card-header bg-white py-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <h6 class="mb-0 fw-bold text-secondary"><i class="bi bi-list-ul me-2"></i>Daftar Riwayat Masuk</h6>
                </div>
                <div class="col-md-4 ms-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari merk atau jenis...">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <?php
            // Query: Urutkan berdasarkan yang paling cepat expired, lalu tanggal masuk terbaru
            $q = $koneksi->query("SELECT * FROM stok_masuk ORDER BY tanggal_kadaluarsa ASC, tanggal DESC");
            ?>

            <div class="table-responsive">
                <table class="table table-detail table-striped table-hover align-middle mb-0" id="tableData">
                    <thead class="text-center text-nowrap">
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>Tanggal Masuk</th>
                            <th>Tgl Kadaluarsa</th>
                            <th>Jenis Beras</th>
                            <th>Merk</th>
                            <th class="text-end">Jml (kg)</th>
                            <th class="text-end">Harga Beli</th>
                            <th style="width:140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($q && $q->num_rows > 0) {
                            $no = 1;
                            $today = date('Y-m-d');

                            while ($row = $q->fetch_assoc()) {
                                $id = (int)$row['id'];

                                // Format Tanggal (d-m-Y)
                                $tgl_masuk = date('d-m-Y', strtotime($row['tanggal']));
                                $tgl_exp_raw = $row['tanggal_kadaluarsa'];
                                $tgl_exp = date('d-m-Y', strtotime($tgl_exp_raw));

                                // Cek Kadaluarsa (Warna Merah jika sudah lewat atau hari ini)
                                $class_exp = ($tgl_exp_raw <= $today) ? 'text-expired' : '';

                                $jenis = htmlspecialchars($row['jenis_beras']);
                                $merk = htmlspecialchars($row['merk']);
                                $jumlah = number_format($row['jumlah'], 0, ',', '.');
                                $harga = number_format($row['harga_beli'], 0, ',', '.');

                                echo "<tr>
                                    <td class='text-center'>{$no}</td>
                                    <td class='text-center'>{$tgl_masuk}</td>
                                    <td class='text-center {$class_exp}'>{$tgl_exp}</td>
                                    <td>{$jenis}</td>
                                    <td class='text-center'>{$merk}</td>
                                    <td class='text-end'>{$jumlah}</td>
                                    <td class='text-end'>Rp {$harga}</td>
                                    <td class='text-center'>
                                        <a href='edit_stok_masuk.php?id={$id}' class='btn btn-warning btn-sm' title='Edit'>
                                            <i class='bi bi-pencil-square'></i>
                                        </a>
                                        <a href='hapus.php?table=stok_masuk&id={$id}' class='btn btn-danger btn-sm' onclick=\"return confirm('Yakin ingin menghapus data ini? Stok di gudang akan berkurang.')\" title='Hapus'>
                                            <i class='bi bi-trash'></i>
                                        </a>
                                    </td>
                                </tr>";
                                $no++;
                            }
                        } else {
                            echo '<tr><td colspan="8" class="text-center text-muted py-5">Belum ada data stok masuk.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Script Pencarian Sederhana -->
<script>
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#tableData tbody tr');

        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<?php include '../includes/footer.php'; ?>