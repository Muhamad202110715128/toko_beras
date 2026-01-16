<?php
include '../includes/config.php';
include '../includes/header.php';

// ambil threshold dari query string atau default 20
$threshold = isset($_GET['threshold']) ? (int)$_GET['threshold'] : 20;

// ambil filter merk & jenis beras dari query string
$filter_merk = $_GET['merk'] ?? '';
$filter_jenis = $_GET['jenis'] ?? '';

// ambil daftar merk & jenis beras untuk opsi filter
$merk_q = $koneksi->query("SELECT DISTINCT COALESCE(merk,'') AS merk FROM stok_masuk ORDER BY merk ASC");
$jenis_q = $koneksi->query("SELECT DISTINCT jenis_beras FROM stok_masuk ORDER BY jenis_beras ASC");

// ambil data stok masuk
$masuk_q = $koneksi->query("SELECT jenis_beras, COALESCE(merk,'') AS merk, SUM(jumlah) AS masuk FROM stok_masuk GROUP BY jenis_beras, merk");
$masuk = [];
while ($r = $masuk_q->fetch_assoc()) {
    $k = $r['jenis_beras'] . '||' . $r['merk'];
    $masuk[$k] = (int)$r['masuk'];
}

// ambil data stok keluar
$keluar_q = $koneksi->query("SELECT jenis_beras, COALESCE(merk,'') AS merk, SUM(jumlah) AS keluar FROM stok_keluar GROUP BY jenis_beras, merk");
$keluar = [];
while ($r = $keluar_q->fetch_assoc()) {
    $k = $r['jenis_beras'] . '||' . $r['merk'];
    $keluar[$k] = (int)$r['keluar'];
}

// gabungkan data masuk & keluar
$keys = array_unique(array_merge(array_keys($masuk), array_keys($keluar)));
$items = [];
foreach ($keys as $k) {
    list($jenis, $merk) = explode('||', $k);
    $in = $masuk[$k] ?? 0;
    $out = $keluar[$k] ?? 0;
    $available = $in - $out;

    // filter merk dan jenis
    if ($filter_merk && $merk != $filter_merk) continue;
    if ($filter_jenis && $jenis != $filter_jenis) continue;

    // hanya tampilkan low stok
    if ($available <= $threshold) {
        $items[] = [
            'jenis' => $jenis,
            'merk' => $merk,
            'masuk' => $in,
            'keluar' => $out,
            'available' => $available
        ];
    }
}

// urutkan berdasarkan stok tersedia (ascending)
usort($items, fn($a, $b) => $a['available'] <=> $b['available']);
?>

<!-- SIDEBAR -->
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
            <a href="stok_masuk.php" class="list-group-item list-group-item-action ">Stok Gudang</a>
            <a href="stok_keluar.php" class="list-group-item list-group-item-action ">Stok Keluar</a>
            <a href="low_stock.php" class="list-group-item list-group-item-action active">Low Stock</a>
            <a href="input_data.php" class="list-group-item list-group-item-action">Input Data</a>
            <div class="list-group-item">
                <a href="../logout.php" class="btn btn-outline-danger w-100">Logout</a>
            </div>
        </div>
    </div>
</div>



<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>📉 Low Stok Beras</h4>
        <div>
            <a href="laporan.php" class="btn btn-success btn-sm me-2">
                <i class="bi bi-file-earmark-text"></i> Laporan
            </a>
        </div>
    </div>

    <!-- Form Filter -->
    <form class="row g-3 mb-4" method="GET">
        <div class="col-md-3">
            <label class="form-label mb-1">Merk</label>
            <select name="merk" class="form-select form-select-sm">
                <option value="">-- Semua Merk --</option>
                <?php while ($m = $merk_q->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($m['merk']) ?>" <?= ($filter_merk == $m['merk']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['merk'] ?: '-') ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label mb-1">Jenis Beras</label>
            <select name="jenis" class="form-select form-select-sm">
                <option value="">-- Semua Jenis --</option>
                <?php while ($j = $jenis_q->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($j['jenis_beras']) ?>" <?= ($filter_jenis == $j['jenis_beras']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($j['jenis_beras']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label mb-1">Threshold (kg)</label>
            <input type="number" name="threshold" class="form-control form-control-sm" value="<?= htmlspecialchars($threshold) ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-primary btn-sm w-100" type="submit">Terapkan Filter</button>
        </div>
    </form>

    <?php if (count($items) === 0): ?>
        <div class="alert alert-success">Tidak ada item dengan stok ≤ <?= htmlspecialchars($threshold) ?> kg.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-light text-center">
                    <tr>
                        <th>No</th>
                        <th>Jenis Beras</th>
                        <th>Merk</th>
                        <th class="text-end">Total Masuk (kg)</th>
                        <th class="text-end">Total Keluar (kg)</th>
                        <th class="text-end">Tersedia (kg)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    foreach ($items as $it): ?>
                        <?php
                        $badgeClass = $it['available'] <= 5 ? 'bg-danger' : ($it['available'] <= 20 ? 'bg-warning text-dark' : 'bg-secondary');
                        ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><?= htmlspecialchars($it['jenis']) ?></td>
                            <td><?= htmlspecialchars($it['merk'] ?: '-') ?></td>
                            <td class="text-end"><?= number_format($it['masuk']) ?></td>
                            <td class="text-end"><?= number_format($it['keluar']) ?></td>
                            <td class="text-end">
                                <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-2"><?= number_format($it['available']) ?> kg</span>
                            </td>
                            <td class="text-center">
                                <button
                                    class="btn btn-sm btn-info btn-detail"
                                    type="button"
                                    data-jenis="<?= htmlspecialchars($it['jenis']) ?>"
                                    data-merk="<?= htmlspecialchars($it['merk']) ?>"
                                    data-masuk="<?= (int)$it['masuk'] ?>"
                                    data-keluar="<?= (int)$it['keluar'] ?>"
                                    data-available="<?= (int)$it['available'] ?>">Detail</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="lowDetailModal" tabindex="-1" aria-labelledby="lowDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lowDetailModalLabel">Detail Low Stok</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <th>Jenis Beras</th>
                            <td id="ld-jenis"></td>
                        </tr>
                        <tr>
                            <th>Merk</th>
                            <td id="ld-merk"></td>
                        </tr>
                        <tr>
                            <th>Total Masuk</th>
                            <td id="ld-masuk" class="text-end"></td>
                        </tr>
                        <tr>
                            <th>Total Keluar</th>
                            <td id="ld-keluar" class="text-end"></td>
                        </tr>
                        <tr>
                            <th>Tersedia</th>
                            <td id="ld-available" class="text-end"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <a href="stok_masuk.php" class="btn btn-sm btn-outline-secondary">Stok Masuk</a>
                <a href="stok_keluar.php" class="btn btn-sm btn-primary">Stok Keluar</a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const detailBtns = document.querySelectorAll('.btn-detail');
        const modal = new bootstrap.Modal(document.getElementById('lowDetailModal'));

        detailBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('ld-jenis').textContent = this.dataset.jenis || '-';
                document.getElementById('ld-merk').textContent = this.dataset.merk || '-';
                document.getElementById('ld-masuk').textContent = this.dataset.masuk + ' kg';
                document.getElementById('ld-keluar').textContent = this.dataset.keluar + ' kg';
                document.getElementById('ld-available').textContent = this.dataset.available + ' kg';
                modal.show();
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>