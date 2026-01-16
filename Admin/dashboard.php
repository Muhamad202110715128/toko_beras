<?php
session_start();
include '../includes/config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../includes/header.php';

// Ambil data stok dari database
$stokMasuk = $koneksi->query("SELECT COALESCE(SUM(jumlah), 0) AS total FROM stok_masuk")->fetch_assoc()['total'];
$stokKeluar = $koneksi->query("SELECT COALESCE(SUM(jumlah), 0) AS total FROM stok_keluar")->fetch_assoc()['total'];
$stokTersedia = $stokMasuk - $stokKeluar;
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <!-- side bar -->
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
                <a href="dashboard.php" class="list-group-item list-group-item-action active">Dashboard</a>
                <a href="stok_masuk.php" class="list-group-item list-group-item-action ">Stok Gudang</a>
                <a href="stok_keluar.php" class="list-group-item list-group-item-action">Stok Keluar</a>
                <a href="low_stock.php" class="list-group-item list-group-item-action">Low Stock</a>
                <a href="input_data.php" class="list-group-item list-group-item-action">Input Data</a>
                <div class="list-group-item">
                    <a href="../logout.php" class="btn btn-outline-danger w-100">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <h3 class="mb-4">Dashboard Stok Beras</h3>

        <div class="row text-center">
            <div class="Total col-md-4 mb-3">
                <a href="dashboard.php" class="btn btn-success btn-lg w-100 py-4">
                    <div class="h6 mb-1">Total Stok</div>
                    <div class="h3 mb-0"><?= $stokMasuk ?> kg</div>
                </a>
            </div>

            <div class="col-md-4 mb-3">
                <a href="stok_keluar.php" class="btn btn-danger btn-lg w-100 py-4">
                    <div class="h6 mb-1">Stok Keluar</div>
                    <div class="h3 mb-0"><?= $stokKeluar ?> kg</div>
                </a>
            </div>

            <div class="col-md-4 mb-3">
                <a href="low_stock.php" class="btn btn-warning btn-lg w-100 py-4">
                    <div class="h6 mb-1">Low Stock</div>
                    <div class="h3 mb-0"><?= $stokTersedia ?> kg</div>
                </a>
            </div>
        </div>

        <!-- Diagram + Low Stock Details -->
        <div class="card mt-5">
            <div class="card-header bg-info text-white">
                Grafik & Low Stock Details
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- kiri: chart kecil -->
                    <div class="col-md-6 d-flex justify-content-center align-items-center">
                        <div style="width:220px; height:220px;">
                            <canvas id="stokChartSmall" width="220" height="220"></canvas>
                        </div>
                    </div>

                    <!-- kanan: informasi low stock -->
                    <div class="col-md-6">
                        <h6 class="mb-3">Low Stock Items</h6>
                        <?php
                        // Ambil stok masuk per jenis
                        $items = [];
                        $q = $koneksi->query("SELECT jenis_beras, COALESCE(SUM(jumlah),0) AS masuk FROM stok_masuk GROUP BY jenis_beras");
                        while ($r = $q->fetch_assoc()) {
                            $jenis = $r['jenis_beras'];
                            $masuk = (int)$r['masuk'];
                            // total keluar untuk jenis tersebut
                            $rk = $koneksi->query("SELECT COALESCE(SUM(jumlah),0) AS keluar FROM stok_keluar WHERE jenis_beras = '" . $koneksi->real_escape_string($jenis) . "'")->fetch_assoc();
                            $keluar = (int)$rk['keluar'];
                            $tersedia = $masuk - $keluar;
                            $items[] = ['jenis' => $jenis, 'stok' => $tersedia];
                        }
                        // urutkan ascending berdasarkan stok
                        usort($items, function ($a, $b) {
                            return $a['stok'] <=> $b['stok'];
                        });
                        // batasi tampil ke 6 item terendah
                        $low = array_slice($items, 0, 6);
                        if (count($low) == 0) {
                            echo '<div class="text-muted">Tidak ada data stok.</div>';
                        } else {
                            echo '<ul class="list-group">';
                            foreach ($low as $it) {
                                $badgeClass = $it['stok'] <= 5 ? 'bg-danger' : ($it['stok'] <= 20 ? 'bg-warning text-dark' : 'bg-secondary');
                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                echo '<div>';
                                echo '<div class="fw-bold">' . htmlspecialchars($it['jenis']) . '</div>';
                                echo '<small class="text-muted">Available</small>';
                                echo '</div>';
                                echo '<span class="badge ' . $badgeClass . ' rounded-pill">' . $it['stok'] . ' kg</span>';
                                echo '</li>';
                            }
                            echo '</ul>';
                            echo '<div class="mt-2"><a href="low_stock.php" class="btn btn-sm btn-link">View All</a></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="card mt-4">
            <div class="card-header bg-secondary text-white">
                Data Stok Masuk Terbaru
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Tanggal Masuk</th>
                                <th>Jenis Beras</th>
                                <th>Jumlah (kg)</th>
                                <th>Harga Beli</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $result = $koneksi->query("SELECT * FROM stok_masuk ORDER BY tanggal DESC LIMIT 10");
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                <td>{$no}</td>
                                <td>{$row['tanggal']}</td>
                                <td>{$row['jenis_beras']}</td>
                                <td>{$row['jumlah']}</td>
                                <td>Rp " . number_format($row['harga_beli'], 0, ',', '.') . "</td>
                            </tr>";
                                $no++;
                            }
                            if ($result->num_rows == 0) {
                                echo '<tr><td colspan="5" class="text-center">Belum ada data stok masuk.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script>
        // Chart.js - doughnut kecil
        (function() {
            const ctx = document.getElementById('stokChartSmall').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Stok Masuk', 'Stok Keluar', 'Stok Tersedia'],
                    datasets: [{
                        data: [<?= (int)$stokMasuk ?>, <?= (int)$stokKeluar ?>, <?= (int)$stokTersedia ?>],
                        backgroundColor: ['#28a745', '#dc3545', '#ffc800ff'],
                        hoverOffset: 6,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: false,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        })();
    </script>

</body>

</html>

<?php include '../includes/footer.php'; ?>