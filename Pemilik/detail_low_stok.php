<?php
include '../includes/config.php';
include '../includes/header.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'owner' && $_SESSION['role'] !== 'pemilik')) {
    exit;
}
?>

<style>
    @media print {

        .no-print,
        .offcanvas,
        .btn {
            display: none !important;
        }
    }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="laporan_owner.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
        <button onclick="window.print()" class="btn btn-warning"><i class="bi bi-printer"></i> Cetak</button>
    </div>

    <div class="card shadow-sm border-danger">
        <div class="card-header bg-danger text-white py-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle-fill"></i> DATA STOK KRITIS (MENIPIS)</h5>
            <small>Menampilkan item dengan total stok &le; 20 kg (Kondisi Saat Ini)</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Jenis Beras</th>
                            <th>Merk</th>
                            <th>Total Stok Tersisa (kg)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        // Query Grouping untuk hitung total sisa per item
                        $q = $koneksi->query("
                            SELECT jenis_beras, merk, SUM(jumlah) as total_sisa 
                            FROM stok_masuk 
                            GROUP BY jenis_beras, merk 
                            HAVING total_sisa <= 20
                            ORDER BY total_sisa ASC
                        ");

                        if ($q->num_rows > 0) {
                            while ($row = $q->fetch_assoc()) {
                                $sisa = $row['total_sisa'];
                                $bg = $sisa == 0 ? 'bg-danger text-white' : 'text-danger fw-bold';
                                $status = $sisa == 0 ? 'HABIS' : 'KRITIS';

                                echo "<tr>
                                    <td>{$no}</td>
                                    <td class='text-start'>{$row['jenis_beras']}</td>
                                    <td>{$row['merk']}</td>
                                    <td class='{$bg}' style='font-size:1.1rem;'>{$sisa} kg</td>
                                    <td><span class='badge bg-danger'>{$status}</span></td>
                                </tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-5 text-success fw-bold'>
                                <i class='bi bi-check-circle-fill fs-1'></i><br>Semua Stok Aman!
                            </td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="alert alert-info mt-3 no-print">
                <i class="bi bi-info-circle"></i> Data ini dihitung berdasarkan akumulasi seluruh batch stok yang masih ada di gudang.
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>