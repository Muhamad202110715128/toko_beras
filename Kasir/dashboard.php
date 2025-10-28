<?php
include '../includes/config.php';
include '../includes/header.php';

// Ambil data penjualan dari database
$query = mysqli_query($koneksi, "SELECT * FROM penjualan");
$penjualan = [];
$subtotal = 0;

while ($row = mysqli_fetch_assoc($query)) {
    $total = $row['harga'] * $row['jumlah'];
    $subtotal += $total;
    $penjualan[] = $row + ['total' => $total];
}

// Hitung pajak dan total akhir
$pajak = $subtotal * 0.10;
$total_akhir = $subtotal + $pajak;

// Hitung data untuk summary card
$total_sales = number_format($subtotal, 2);
$new_orders = mysqli_num_rows($query);
$todays_revenue = number_format($pajak, 2);
$total_items = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(jumlah) as total_item FROM penjualan"))['total_item'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container py-4">

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <a href="sales.php" class="card-link">
                    <div class="summary-card">
                        <h6>Total Sales</h6>
                        <h4>Rp<?= $total_sales; ?></h4>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="orders.php" class="card-link">
                    <div class="summary-card">
                        <h6>New Orders</h6>
                        <h4><?= $new_orders; ?></h4>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="revenue.php" class="card-link">
                    <div class="summary-card">
                        <h6>Today's Revenue</h6>
                        <h4>Rp<?= $todays_revenue; ?></h4>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="items.php" class="card-link">
                    <div class="summary-card">
                        <h6>Total Items</h6>
                        <h4><?= $total_items; ?></h4>
                    </div>
                </a>
            </div>
        </div>

        <!-- Complete Sale Section -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3 fw-semibold">Complete Sale</h5>
                <input type="text" class="form-control mb-3" placeholder="Search or add products...">

                <div class="table-responsive">
                    <table class="table table-borderless order-summary align-middle">
                        <thead class="border-bottom">
                            <tr>
                                <th>Nama Produk</th>
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($penjualan as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['nama_produk']); ?></td>
                                    <td>Rp<?= number_format($p['harga'], 2); ?></td>
                                    <td><?= $p['jumlah']; ?></td>
                                    <td>Rp<?= number_format($p['total'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="border-top">
                            <tr>
                                <td colspan="3" class="text-end fw-semibold">Subtotal</td>
                                <td>Rp<?= number_format($subtotal, 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fw-semibold">Tax (10%)</td>
                                <td>Rp<?= number_format($pajak, 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fw-bold fs-5">Total</td>
                                <td class="fw-bold fs-5">Rp<?= number_format($total_akhir, 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <button class="btn btn-complete w-100 mt-3">Complete Sale</button>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php include '../includes/footer.php'; ?>