<?php
include '../includes/config.php';
include '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="card-title mb-0">Pendapatan Harian</h5>
</div>
<hr>

<form method="GET" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="date" name="tanggal" class="form-control" value="<?= $_GET['tanggal'] ?? date('Y-m-d') ?>">
    </div>
    <div class="col-md-3">
        <button class="btn btn-primary">Tampilkan</button>
    </div>
</form>

<?php
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// HITUNG TOTAL PER HARI (DARI TABEL PENJUALAN)
$q_total = $koneksi->query("
    SELECT 
        SUM(jumlah) AS total_kg,
        SUM(total_harga) AS total_pendapatan,
        COUNT(*) AS total_transaksi
    FROM penjualan
    WHERE DATE(tanggal)='$tanggal'
");

$d = $q_total->fetch_assoc();
?>

<div class="row text-center mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm p-3">
            <h6>Total Kg Keluar</h6>
            <h3><?= number_format($d['total_kg']) ?> kg</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm p-3">
            <h6>Total Pendapatan</h6>
            <h3>Rp <?= number_format($d['total_pendapatan'], 0, ',', '.') ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm p-3">
            <h6>Jumlah Transaksi</h6>
            <h3><?= $d['total_transaksi'] ?></h3>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <h6>Transaksi Pada Tanggal: <b><?= $tanggal ?></b></h6>

        <?php
        // AMBIL DETAIL TRANSAKSI DARI TABEL PENJUALAN
        $q = $koneksi->query("
            SELECT *
            FROM penjualan
            WHERE DATE(tanggal)='$tanggal'
            ORDER BY tanggal DESC
        ");
        ?>

        <table class="table table-bordered table-striped align-middle">
            <thead class="text-center">
                <tr>
                    <th>No</th>
                    <th>Jam</th>
                    <th>Jenis</th>
                    <th>Merk</th>
                    <th>Kg</th>
                    <th>Harga Jual</th>
                    <th>Total</th>
                </tr>
            </thead>

            <tbody>
                <?php
                if ($q->num_rows > 0) {
                    $no = 1;
                    while ($row = $q->fetch_assoc()) {
                        $jam = date('H:i', strtotime($row['tanggal']));

                        echo "
                        <tr>
                            <td class='text-center'>$no</td>
                            <td class='text-center'>$jam</td>
                            <td>{$row['jenis_beras']}</td>
                            <td class='text-center'>{$row['merk']}</td>
                            <td class='text-end'>{$row['jumlah']}</td>
                            <td class='text-end'>Rp " . number_format($row['harga'], 0, ',', '.') . "</td>
                            <td class='text-end'>Rp " . number_format($row['total_harga'], 0, ',', '.') . "</td>
                        </tr>
                        ";
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center text-muted'>Tidak ada transaksi hari ini.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>