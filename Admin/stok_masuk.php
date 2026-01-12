<?php
include '../includes/config.php';
include '../includes/header.php';

// Cek Akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>window.location='../login.php';</script>";
    exit;
}

// ==========================================
// 1. QUERY RINGKASAN & RINCIAN MODAL
// ==========================================

// A. Total Stok Fisik (Semua Barang)
$q_total = $koneksi->query("SELECT SUM(jumlah) as total_kg FROM stok_masuk");
$d_total = $q_total->fetch_assoc();
$total_kg = $d_total['total_kg'] ?? 0;

// B. Data Rincian Stok per Jenis (Untuk Modal 1)
$q_breakdown = $koneksi->query("
    SELECT jenis_beras, SUM(jumlah) as total 
    FROM stok_masuk 
    GROUP BY jenis_beras 
    ORDER BY total DESC
");

// C. Data Barang Mendekati Kadaluarsa (<= 30 Hari) (Untuk Counter & Modal 2)
$tgl_warning = date('Y-m-d', strtotime('+30 days'));
$today = date('Y-m-d');

// Hitung Jumlah Batch
$q_exp_count = $koneksi->query("SELECT COUNT(*) as jumlah_batch FROM stok_masuk WHERE tanggal_kadaluarsa <= '$tgl_warning' AND jumlah > 0");
$d_exp = $q_exp_count->fetch_assoc();
$total_exp = $d_exp['jumlah_batch'] ?? 0;

// Ambil List Detailnya
$q_exp_list = $koneksi->query("SELECT * FROM stok_masuk WHERE tanggal_kadaluarsa <= '$tgl_warning' AND jumlah > 0 ORDER BY tanggal_kadaluarsa ASC");
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
        font-weight: bold;
    }

    /* Styling Card Dashboard (Clickable) */
    .card-stat {
        border: none;
        border-left: 5px solid;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s;
        cursor: pointer;
        /* Menandakan bisa diklik */
    }

    .card-stat:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }
</style>

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

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">📥 Data Stok Masuk</h4>
            <small class="text-muted">Manajemen pembelian barang dari supplier</small>
        </div>
        <a href="input_stok_masuk.php" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i> Input Data Baru
        </a>
    </div>

    <div class="row mb-4">

        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card h-100 card-stat border-primary" data-bs-toggle="modal" data-bs-target="#modalRincianStok" title="Klik untuk rincian">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                        <i class="bi bi-box-seam fs-2"></i>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase fw-bold mb-1">Total Stok Fisik</h6>
                        <h3 class="fw-bold mb-0 text-primary"><?= number_format($total_kg, 0, ',', '.') ?> <small class="fs-6 text-muted">kg</small></h3>
                        <small class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-hand-index-thumb"></i> Klik untuk rincian</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 card-stat border-danger" data-bs-toggle="modal" data-bs-target="#modalExpired" title="Klik untuk cek barang">
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

    <div class="card shadow-sm border-0">
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
            // Query Table Utama
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
                            $today = date('Y-m-d'); // Tanggal hari ini

                            while ($row = $q->fetch_assoc()) {
                                $id = (int)$row['id'];

                                // 1. Ambil Data Tanggal
                                $tgl_masuk = date('d-m-Y', strtotime($row['tanggal']));
                                $tgl_exp_raw = $row['tanggal_kadaluarsa']; // Format Y-m-d untuk logika
                                $tgl_exp = date('d-m-Y', strtotime($tgl_exp_raw)); // Format d-m-Y untuk tampilan

                                // 2. Logika Warna Merah (Kadaluarsa)
                                // Jika tanggal expired LEBIH KECIL dari hari ini, maka sudah kadaluarsa
                                if ($tgl_exp_raw < $today) {
                                    $row_class = 'table-danger'; // Class Bootstrap untuk warna merah
                                    $status_text = '<span class="badge bg-danger">Expired</span>';
                                } elseif ($tgl_exp_raw == $today) {
                                    $row_class = 'table-danger'; // Hari ini expired juga dianggap merah
                                    $status_text = '<span class="badge bg-danger">Hari Ini!</span>';
                                } else {
                                    $row_class = ''; // Putih/Normal
                                    $status_text = '';
                                }

                                // 3. Data Lainnya
                                $jenis = htmlspecialchars($row['jenis_beras']);
                                $merk = htmlspecialchars($row['merk']);
                                $jumlah = number_format($row['jumlah'], 0, ',', '.');
                                $harga = number_format($row['harga_beli'], 0, ',', '.');

                                // 4. Tampilkan Baris dengan Class Warna
                                echo "<tr class='{$row_class}'>
                <td class='text-center'>{$no}</td>
                <td class='text-center'>{$tgl_masuk}</td>
                <td class='text-center fw-bold'>
                    {$tgl_exp}
                    <div style='font-size: 0.7em;'>{$status_text}</div>
                </td>
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

<div class="modal fade" id="modalRincianStok" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary bg-opacity-25">
                <h5 class="modal-title fw-bold text-primary"><i class="bi bi-pie-chart-fill"></i> Rincian Stok Gudang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Akumulasi stok fisik berdasarkan jenis beras:</p>
                <ul class="list-group">
                    <?php
                    if ($q_breakdown && $q_breakdown->num_rows > 0) {
                        while ($br = $q_breakdown->fetch_assoc()) {
                            echo '<li class="list-group-item d-flex justify-content-between align-items-center">
                                    ' . htmlspecialchars($br['jenis_beras']) . '
                                    <span class="badge bg-primary rounded-pill">' . number_format($br['total'], 0, ',', '.') . ' kg</span>
                                  </li>';
                        }
                    } else {
                        echo '<li class="list-group-item text-center text-muted">Belum ada data.</li>';
                    }
                    ?>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalExpired" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill"></i> Stok Mendekati Expired</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="alert alert-warning m-3 small">
                    <i class="bi bi-info-circle"></i> Menampilkan barang dengan sisa stok > 0 yang akan kadaluarsa dalam 30 hari ke depan.
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="bg-light text-center">
                            <tr>
                                <th>Tgl Expired</th>
                                <th>Jenis</th>
                                <th>Merk</th>
                                <th>Sisa Stok</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($q_exp_list && $q_exp_list->num_rows > 0) {
                                while ($ex = $q_exp_list->fetch_assoc()) {
                                    $expDate = $ex['tanggal_kadaluarsa'];
                                    $fmtDate = date('d-m-Y', strtotime($expDate));

                                    // Hitung hari sisa
                                    $d1 = new DateTime($today);
                                    $d2 = new DateTime($expDate);
                                    $diff = $d1->diff($d2);
                                    $daysLeft = (int)$diff->format("%r%a");

                                    // Tentukan Warna & Label
                                    if ($daysLeft < 0) {
                                        $badge = "<span class='badge bg-danger'>Sudah Expired!</span>";
                                        $rowColor = "table-danger";
                                    } else {
                                        $badge = "<span class='badge bg-warning text-dark'>{$daysLeft} hari lagi</span>";
                                        $rowColor = "";
                                    }

                                    echo "<tr class='{$rowColor}'>
                                            <td class='text-center fw-bold'>{$fmtDate}</td>
                                            <td>" . htmlspecialchars($ex['jenis_beras']) . "</td>
                                            <td class='text-center'>" . htmlspecialchars($ex['merk']) . "</td>
                                            <td class='text-end'>" . number_format($ex['jumlah'], 0, ',', '.') . " kg</td>
                                            <td class='text-center'>{$badge}</td>
                                          </tr>";
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center text-success py-4"><i class="bi bi-check-circle-fill fs-4 d-block mb-2"></i>Aman! Tidak ada stok mendekati expired.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

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