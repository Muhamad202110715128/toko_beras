<?php
include '../includes/config.php';
include '../includes/header.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'owner' && $_SESSION['role'] !== 'pemilik')) {
    exit;
}

$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
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
            <a href="input_data.php" class="list-group-item list-group-item-action">Kelola Data Master</a>
            <div class="list-group-item">
                <a href="../logout.php" class="btn btn-outline-danger w-100">Logout</a>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="laporan.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <button onclick="window.print()" class="btn btn-danger"><i class="bi bi-printer"></i> Cetak</button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 text-center">
            <h5 class="fw-bold mb-0 text-danger">LAPORAN BARANG MASUK (BELANJA)</h5>
            <small class="text-muted">Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)) ?></small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-danger text-center">
                        <tr>
                            <th>No</th>
                            <th>Tgl Masuk</th>
                            <th>Item (Merk)</th>
                            <th>Expired Date</th>
                            <th>Qty (kg)</th>
                            <th>Nilai Belanja (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $total_belanja = 0;
                        $total_kg = 0;

                        $q = $koneksi->query("
                            SELECT * FROM stok_masuk 
                            WHERE DATE(tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
                            ORDER BY tanggal DESC
                        ");

                        if ($q->num_rows > 0) {
                            while ($row = $q->fetch_assoc()) {
                                $nilai = $row['harga_beli'];
                                $total_belanja += $nilai;
                                $total_kg += $row['jumlah'];

                                echo "<tr>
                                    <td class='text-center'>{$no}</td>
                                    <td class='text-center'>" . date('d/m/Y', strtotime($row['tanggal'])) . "</td>
                                    <td>{$row['jenis_beras']} ({$row['merk']})</td>
                                    <td class='text-center text-danger'>" . date('d/m/Y', strtotime($row['tanggal_kadaluarsa'])) . "</td>
                                    <td class='text-center'>{$row['jumlah']}</td>
                                    <td class='text-end fw-bold'>Rp " . number_format($nilai, 0, ',', '.') . "</td>
                                </tr>";
                                $no++;
                            }
                            echo "<tr class='table-light fw-bold'>
                                <td colspan='4' class='text-end'>TOTAL PENGELUARAN</td>
                                <td class='text-center'>{$total_kg} kg</td>
                                <td class='text-end text-danger'>Rp " . number_format($total_belanja, 0, ',', '.') . "</td>
                            </tr>";
                        } else {
                            echo "<tr><td colspan='6' class='text-center py-4'>Tidak ada barang masuk pada periode ini.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>