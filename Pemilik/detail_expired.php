<?php
include '../includes/config.php';
include '../includes/header.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'owner' && $_SESSION['role'] !== 'pemilik')) {
    exit;
}

$limit_date = date('Y-m-d', strtotime('+30 days'));
?>

<link rel="stylesheet" href="detail.css">

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

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="laporan.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
        <button onclick="window.print()" class="btn btn-warning"><i class="bi bi-printer"></i> Cetak</button>
    </div>

    <div class="card shadow-sm border-warning">
        <div class="card-header bg-warning text-dark py-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-clock-history"></i> MONITORING KADALUARSA</h5>
            <small>Menampilkan batch yang akan expired dalam 30 hari ke depan</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th>No</th>
                            <th>Item (Merk)</th>
                            <th>Tgl Masuk</th>
                            <th>Tgl Kadaluarsa</th>
                            <th>Sisa Hari</th>
                            <th>Sisa Stok (kg)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $today = time();

                        $q = $koneksi->query("
                            SELECT * FROM stok_masuk 
                            WHERE tanggal_kadaluarsa <= '$limit_date' AND jumlah > 0
                            ORDER BY tanggal_kadaluarsa ASC
                        ");

                        if ($q->num_rows > 0) {
                            while ($row = $q->fetch_assoc()) {
                                $exp_time = strtotime($row['tanggal_kadaluarsa']);
                                $diff = $exp_time - $today;
                                $days = round($diff / (60 * 60 * 24));

                                // Styling berdasarkan urgensi
                                $row_class = '';
                                if ($days < 0) {
                                    $days_txt = "SUDAH BASI (" . abs($days) . " hari)";
                                    $row_class = 'table-danger';
                                } elseif ($days <= 7) {
                                    $days_txt = "$days Hari Lagi";
                                    $row_class = 'table-warning';
                                } else {
                                    $days_txt = "$days Hari Lagi";
                                }

                                echo "<tr class='{$row_class}'>
                                    <td class='text-center'>{$no}</td>
                                    <td>
                                        <strong>{$row['jenis_beras']}</strong> <br> 
                                        <small>Merk: {$row['merk']}</small>
                                    </td>
                                    <td class='text-center'>" . date('d/m/Y', strtotime($row['tanggal'])) . "</td>
                                    <td class='text-center fw-bold'>" . date('d/m/Y', $exp_time) . "</td>
                                    <td class='text-center fw-bold text-danger'>{$days_txt}</td>
                                    <td class='text-center fs-5'>{$row['jumlah']}</td>
                                </tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center py-5 text-success fw-bold'>
                                <i class='bi bi-shield-check fs-1'></i><br>Tidak ada barang yang mendekati expired.
                            </td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>