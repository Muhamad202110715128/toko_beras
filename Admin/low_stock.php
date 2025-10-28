<?php
include '../includes/config.php';
include '../includes/header.php';

// ambil threshold dari query string atau default 20
$threshold = isset($_GET['threshold']) ? (int)$_GET['threshold'] : 20;

// ambil data masuk per jenis+merk
$masuk_q = $koneksi->query("SELECT jenis_beras, COALESCE(merk,'') AS merk, SUM(jumlah) AS masuk FROM stok_masuk GROUP BY jenis_beras, merk");
$masuk = [];
while ($r = $masuk_q->fetch_assoc()) {
    $k = $r['jenis_beras'] . '||' . $r['merk'];
    $masuk[$k] = (int)$r['masuk'];
}

// ambil data keluar per jenis+merk
$keluar_q = $koneksi->query("SELECT jenis_beras, COALESCE(merk,'') AS merk, SUM(jumlah) AS keluar FROM stok_keluar GROUP BY jenis_beras, merk");
$keluar = [];
while ($r = $keluar_q->fetch_assoc()) {
    $k = $r['jenis_beras'] . '||' . $r['merk'];
    $keluar[$k] = (int)$r['keluar'];
}

// gabungkan kunci dari kedua set
$keys = array_unique(array_merge(array_keys($masuk), array_keys($keluar)));

$items = [];
foreach ($keys as $k) {
    list($jenis, $merk) = explode('||', $k);
    $in = $masuk[$k] ?? 0;
    $out = $keluar[$k] ?? 0;
    $available = $in - $out;
    // tampilkan hanya yg <= threshold (low stock) atau jika available <=0 juga tampil
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

// urutkan ascending berdasarkan available
usort($items, function ($a, $b) {
    return $a['available'] <=> $b['available'];
});
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Low Stock Items</h4>
        <form class="d-flex" method="GET" style="gap:.5rem;">
            <label class="small text-muted align-self-center mb-0">Threshold (kg):</label>
            <input type="number" name="threshold" class="form-control form-control-sm" style="width:90px;" value="<?= htmlspecialchars($threshold) ?>">
            <button class="btn btn-sm btn-primary" type="submit">Filter</button>
        </form>
    </div>

    <?php if (count($items) === 0): ?>
        <div class="alert alert-success">Tidak ada item dengan stok ≤ <?= htmlspecialchars($threshold) ?> kg.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-light text-center">
                    <tr>
                        <th style="width:64px;">No</th>
                        <th>Jenis Beras</th>
                        <th>Merk</th>
                        <th class="text-end">Total Masuk (kg)</th>
                        <th class="text-end">Total Keluar (kg)</th>
                        <th class="text-end">Tersedia (kg)</th>
                        <th style="width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    foreach ($items as $it):
                        $badgeClass = $it['available'] <= 5 ? 'bg-danger' : ($it['available'] <= 20 ? 'bg-warning text-dark' : 'bg-secondary');
                    ?>
                        <tr>
                            <td class="text-center"><?= $no ?></td>
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
                                <a href="../Admin/stok_keluar.php?jenis=<?= urlencode($it['jenis']) ?>&merk=<?= urlencode($it['merk']) ?>" class="btn btn-sm btn-outline-primary ms-1">Keluar</a>
                            </td>
                        </tr>
                    <?php $no++;
                    endforeach; ?>
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
                <h5 class="modal-title" id="lowDetailModalLabel">Detail Low Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <th class="w-50">Jenis Beras</th>
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
                <a href="stok_masuk.php" class="btn btn-sm btn-outline-secondary">Lihat Stok Masuk</a>
                <a href="stok_keluar.php" class="btn btn-sm btn-primary">Buat Stok Keluar</a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const detailBtns = document.querySelectorAll('.btn-detail');
        const modalEl = document.getElementById('lowDetailModal');
        const modal = new bootstrap.Modal(modalEl);

        detailBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('ld-jenis').textContent = this.dataset.jenis || '-';
                document.getElementById('ld-merk').textContent = this.dataset.merk || '-';
                document.getElementById('ld-masuk').textContent = (this.dataset.masuk || '0') + ' kg';
                document.getElementById('ld-keluar').textContent = (this.dataset.keluar || '0') + ' kg';
                document.getElementById('ld-available').textContent = (this.dataset.available || '0') + ' kg';
                modal.show();
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>