<?php
include '../includes/config.php';
include '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="card-title mb-0">Total Sale (Rekap Penjualan)</h5>
</div>
<hr>

<?php
// =====================
//     TOTAL OMZET
// =====================
$q_total = $koneksi->query("
    SELECT 
        SUM(jumlah) AS total_kg, 
        SUM(total_harga) AS total_pendapatan
    FROM penjualan
");

$d_total = $q_total->fetch_assoc();
$totalKg = $d_total['total_kg'] ?: 0;
$totalPendapatan = $d_total['total_pendapatan'] ?: 0;


// =====================
//     REKAP BULANAN
// =====================
$q_bulan = $koneksi->query("
    SELECT 
        DATE_FORMAT(tanggal, '%Y-%m') AS bulan,
        SUM(jumlah) AS total_kg,
        SUM(total_harga) AS total_pendapatan
    FROM penjualan
    GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
    ORDER BY bulan DESC
");
?>

<div class="row text-center mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm p-3">
            <h6>Total Penjualan (kg)</h6>
            <h3><?= number_format($totalKg) ?> kg</h3>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm p-3">
            <h6>Total Pendapatan</h6>
            <h3>Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></h3>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <h6>Rekap Penjualan Bulanan</h6>
        <table class="table table-striped table-bordered">
            <thead>
                <tr class="text-center">
                    <th>Bulan</th>
                    <th>Total Kg</th>
                    <th>Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($q_bulan->num_rows > 0) {
                    while ($r = $q_bulan->fetch_assoc()) {
                        echo "
                        <tr>
                            <td class='text-center'>{$r['bulan']}</td>
                            <td class='text-end'>" . number_format($r['total_kg']) . " kg</td>
                            <td class='text-end'>Rp " . number_format($r['total_pendapatan'], 0, ',', '.') . "</td>
                        </tr>
                        ";
                    }
                } else {
                    echo "<tr><td colspan='3' class='text-center text-muted'>Belum ada data.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>