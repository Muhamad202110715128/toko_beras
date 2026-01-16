<?php
include '../includes/config.php';
include '../includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan hanya pemilik yang bisa akses


// ====== Query Dashboard ======
// Total barang
// (diganti: hitung berdasarkan data nyata di tabel stok_masuk — jumlah item unik berdasarkan kombinasi jenis+merk)
$totQ = $koneksi->query("SELECT COUNT(DISTINCT CONCAT(jenis_beras, '||', COALESCE(merk,''))) AS total_item FROM stok_masuk");
$stokMasuk = $totQ ? $totQ->fetch_assoc() : ['total_item' => 0];

// Total penjualan
$penjualanQuery = $koneksi->query("SELECT SUM(total_harga) AS total_penjualan FROM penjualan");
$penjualanData = $penjualanQuery->fetch_assoc();

// Produk terlaris
$produkLaris = $koneksi->query("
    SELECT jenis_beras, merk, SUM(jumlah) AS total_terjual
    FROM penjualan
    GROUP BY jenis_beras, merk
    ORDER BY total_terjual DESC
    LIMIT 5
");
?>

<div class="container mt-4">
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
                <a href="dashboard.php" class="list-group-item list-group-item-action active">Dashboard</a>
                <a href="laporan.php" class="list-group-item list-group-item-action ">Laporan Eksekutif</a>
                <div class="list-group-item">
                    <a href="../logout.php" class="btn btn-outline-danger w-100">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <h3>Dashboard Pemilik</h3>

    <div class="row mt-4">
        <!-- Kartu Total Barang -->
        <div class="col-md-6 mb-3">
            <div class="card text-center shadow-sm border-success">
                <div class="card-body">
                    <h5>Total Barang</h5>
                    <h3><?= $stokMasuk['total_item'] ?? 0; ?></h3>
                </div>
            </div>
        </div>

        <!-- Kartu Total Penjualan -->
        <div class="col-md-6 mb-3">
            <div class="card text-center shadow-sm border-primary">
                <div class="card-body">
                    <h5>Total Penjualan</h5>
                    <h3>Rp <?= number_format($penjualanData['total_penjualan'] ?? 0, 0, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Diagram (contoh placeholder, nanti bisa pakai Chart.js) -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="text-center">📦 Diagram Stok Gudang</h5>
                    <canvas id="chartGudang" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="text-center">💰 Diagram Penjualan Kasir</h5>
                    <canvas id="chartKasir" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Produk Terlaris -->
    <div class="card mt-4 shadow-sm">
        <div class="card-body">
            <h5>🏆 Produk Terlaris</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Jenis Beras</th>
                            <th>Merk</th>
                            <th>Total Terjual (kg)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if ($produkLaris->num_rows > 0) {
                            while ($row = $produkLaris->fetch_assoc()) {
                                echo "<tr>
                                    <td>{$no}</td>
                                    <td>{$row['jenis_beras']}</td>
                                    <td>{$row['merk']}</td>
                                    <td>{$row['total_terjual']}</td>
                                </tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-muted'>Belum ada data penjualan.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Script Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctxGudang = document.getElementById('chartGudang');
    const ctxKasir = document.getElementById('chartKasir');

    // Contoh dummy data (bisa diganti query PHP)
    new Chart(ctxGudang, {
        type: 'bar',
        data: {
            labels: ['Pulen', 'Pandan Wangi', 'Merah', 'Putih'],
            datasets: [{
                label: 'Stok Gudang (kg)',
                data: [300, 200, 150, 400],
                borderWidth: 1,
                backgroundColor: '#198754'
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    new Chart(ctxKasir, {
        type: 'line',
        data: {
            labels: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
            datasets: [{
                label: 'Penjualan Harian (kg)',
                data: [50, 70, 60, 90, 120],
                borderColor: '#0d6efd',
                fill: false,
                tension: 0.2
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>