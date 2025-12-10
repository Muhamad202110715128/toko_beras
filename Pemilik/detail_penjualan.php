<?php
include '../includes/config.php';
include '../includes/header.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'owner' && $_SESSION['role'] !== 'pemilik')) {
    echo "<script>window.location='../login.php';</script>";
    exit;
}

$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
?>

<style>
    @media print {

        .no-print,
        .offcanvas,
        .btn,
        form {
            display: none !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="laporan_owner.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <button onclick="window.print()" class="btn btn-success"><i class="bi bi-printer"></i> Cetak</button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 text-center">
            <h5 class="fw-bold mb-0">LAPORAN RINCIAN PENJUALAN</h5>
            <small class="text-muted">Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)) ?></small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-success text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th>Tanggal & Waktu</th>
                            <th>Item (Merk)</th>
                            <th>Qty (kg)</th>
                            <th>Harga Satuan</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $grand_total = 0;
                        $total_qty = 0;

                        $q = $koneksi->query("
                            SELECT * FROM penjualan 
                            WHERE DATE(tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
                            ORDER BY tanggal DESC
                        ");

                        if ($q->num_rows > 0) {
                            while ($row = $q->fetch_assoc()) {
                                $grand_total += $row['total_harga'];
                                $total_qty += $row['jumlah'];
                                echo "<tr>
                                    <td class='text-center'>{$no}</td>
                                    <td class='text-center'>" . date('d/m/Y H:i', strtotime($row['tanggal'])) . "</td>
                                    <td>{$row['jenis_beras']} <small class='text-muted'>({$row['merk']})</small></td>
                                    <td class='text-center'>{$row['jumlah']}</td>
                                    <td class='text-end'>Rp " . number_format($row['harga'], 0, ',', '.') . "</td>
                                    <td class='text-end fw-bold'>Rp " . number_format($row['total_harga'], 0, ',', '.') . "</td>
                                </tr>";
                                $no++;
                            }
                            echo "<tr class='table-light fw-bold'>
                                <td colspan='3' class='text-end'>TOTAL KESELURUHAN</td>
                                <td class='text-center'>{$total_qty} kg</td>
                                <td></td>
                                <td class='text-end text-success'>Rp " . number_format($grand_total, 0, ',', '.') . "</td>
                            </tr>";
                        } else {
                            echo "<tr><td colspan='6' class='text-center py-4'>Tidak ada data penjualan pada periode ini.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>