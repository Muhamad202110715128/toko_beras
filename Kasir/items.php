<?php
include '../includes/config.php';
include '../includes/header.php';

// Ambil Data untuk Dropdown Modal Request
$jenis_q = $koneksi->query("SELECT * FROM jenis_beras ORDER BY nama_jenis ASC");
$merk_q  = $koneksi->query("SELECT * FROM merk_beras ORDER BY nama_merk ASC");

?>
<style>
    .table-out thead th {
        background: #f8f9fa;
        font-weight: 700;
    }
</style>

<!-- side bar -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
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
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="list-group list-group-flush">
            <a href="/toko_beras/kasir/dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
            <a href="/toko_beras/kasir/order.php" class="list-group-item list-group-item-action">Transaksi</a>
            <a href="/toko_beras/kasir/items.php" class="list-group-item list-group-item-action active">Items</a>
            <a href="/toko_beras/kasir/revenue.php" class="list-group-item list-group-item-action">revenue</a>
            <a href="/toko_beras/kasir/sales.php" class="list-group-item list-group-item-action">sales</a>
            <div class="list-group-item">
                <a href="/toko_beras/logout.php" class="btn btn-outline-danger w-100">Logout</a>
            </div>
        </div>
    </div>
</div>


<div class="d-flex align-items-center justify-content-between mb-3 p-2">
    <div>
        <h4 class="card-title mb-0">Riwayat Item Keluar</h4>
        <small class="text-muted">Daftar barang yang tersedia</small>
    </div>

    <div class="column-gap-3" role="group" aria-label="Basic example">
        <button type="button" class="btn btn-outline-info"
            data-bs-toggle="modal" data-bs-target="#modalInfoStok">Info</button>

        <!-- BUTTON 1: PERMINTAAN STOK KE ADMIN -->
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalRequest">
            <i class="bi bi-box-arrow-in-down"></i> Minta Stok ke Admin
        </button>
    </div>

</div>

<hr>

<!-- TABEL DATA -->
<div class="card shadow-sm">
    <div class="card-body">

        <?php $q = $koneksi->query("SELECT * FROM stok_keluar ORDER BY tanggal DESC"); ?>

        <div class="table-responsive">
            <table class="table table-out table-striped table-hover align-middle">
                <thead class="text-center">
                    <tr>
                        <th style="width:50px;">No</th>
                        <th>Tanggal</th>
                        <th>Jenis Beras</th>
                        <th>Merk</th>
                        <th class="text-end">Jumlah (kg)</th>
                        <th class="text-end">Harga Jual</th>
                        <th style="width:100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($q && $q->num_rows > 0) {
                        $no = 1;
                        while ($row = $q->fetch_assoc()) {
                            $id = (int)$row['id'];
                            $tgl = htmlspecialchars($row['tanggal'] ?? '-');
                            $jenis = htmlspecialchars($row['jenis_beras'] ?? '-');
                            $merk = $row['merk'] !== '' ? htmlspecialchars($row['merk']) : '-';
                            $jumlah = (int)($row['jumlah'] ?? 0);
                            $harga = number_format((float)($row['harga_jual'] ?? 0), 0, ',', '.');

                            echo "<tr>
                                <td class='text-center'>{$no}</td>
                                <td>{$tgl}</td>
                                <td>{$jenis}</td>
                                <td class='text-center'>{$merk}</td>
                                <td class='text-end'>{$jumlah}</td>
                                <td class='text-end'>Rp {$harga}</td>
                                <td class='text-center'>
                                    <!-- BUTTON 2: RETURN (Menggantikan Detail) -->
                                    <button class='btn btn-outline-danger btn-sm btn-return' 
                                            data-id='{$id}'
                                            data-jenis='{$jenis}'
                                            data-merk='{$merk}'
                                            data-jumlah='{$jumlah}'>
                                        <i class='bi bi-arrow-counterclockwise'></i> Return
                                    </button>
                                </td>
                            </tr>";
                            $no++;
                        }
                    } else {
                        echo '<tr><td colspan="8" class="text-center text-muted py-4">Belum ada data stok keluar.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================== -->
<!-- MODAL 1: REQUEST STOK -->
<!-- ========================== -->
<div class="modal fade" id="modalRequest" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="proses_kasir.php" method="POST" class="modal-content">
            <input type="hidden" name="aksi" value="request_stok">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-send"></i> Form Permintaan Stok</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Jenis Beras</label>
                    <select name="jenis_beras" class="form-select" required>
                        <option value="">-- Pilih Jenis --</option>
                        <?php
                        if ($jenis_q) {
                            $jenis_q->data_seek(0);
                            while ($j = $jenis_q->fetch_assoc()) {
                                echo "<option value='{$j['nama_jenis']}'>{$j['nama_jenis']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Merk</label>
                    <select name="merk" class="form-select" required>
                        <option value="">-- Pilih Merk --</option>
                        <?php
                        if ($merk_q) {
                            $merk_q->data_seek(0);
                            while ($m = $merk_q->fetch_assoc()) {
                                echo "<option value='{$m['nama_merk']}'>{$m['nama_merk']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah Permintaan (kg)</label>
                    <input type="number" name="jumlah" class="form-control" min="1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan (Opsional)</label>
                    <textarea name="catatan" class="form-control" rows="2" placeholder="Contoh: Stok menipis, butuh cepat."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Kirim Permintaan</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================== -->
<!-- MODAL 2: RETURN BARANG -->
<!-- ========================== -->
<div class="modal fade" id="modalReturn" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="proses_kasir.php" method="POST" class="modal-content">
            <input type="hidden" name="aksi" value="return_barang">
            <input type="hidden" name="id_stok_keluar" id="ret-id">

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise"></i> Return Barang</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div>Tindakan ini akan <strong>membatalkan transaksi</strong> dan <strong>mengembalikan stok</strong> ke gudang.</div>
                </div>

                <table class="table table-borderless">
                    <tr>
                        <th width="100">Item</th>
                        <td>: <span id="ret-item" class="fw-bold"></span></td>
                    </tr>
                    <tr>
                        <th>Jumlah</th>
                        <td>: <span id="ret-jumlah" class="fw-bold"></span></td>
                    </tr>
                </table>

                <div class="mb-3">
                    <label class="form-label">Alasan Return</label>
                    <textarea name="alasan_return" class="form-control" required placeholder="Contoh: Salah input, Pembeli batal, Barang rusak..."></textarea>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" required id="confirmCheck">
                    <label class="form-check-label small" for="confirmCheck">
                        Saya yakin ingin mengembalikan stok ini ke gudang.
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Proses Return</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================== -->
<!-- MODAL INFO STOK GUDANG -->
<!-- ========================== -->
<div class="modal fade" id="modalInfoStok" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="bi bi-info-circle"></i> Informasi Stok Gudang
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <?php
                $qInfo = $koneksi->query("
                    SELECT 
                        jenis_beras,
                        merk,
                        SUM(jumlah) AS total_stok,
                        MIN(tanggal_kadaluarsa) AS kadaluarsa
                    FROM stok_masuk
                    GROUP BY jenis_beras, merk
                    ORDER BY jenis_beras ASC
                ");

                $today = new DateTime(); // Tanggal hari ini
                ?>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="text-center bg-light">
                            <tr>
                                <th>No</th>
                                <th>Jenis Beras</th>
                                <th>Merk</th>
                                <th>Total Stok (kg)</th>
                                <th>Kadaluarsa Terdekat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($qInfo && $qInfo->num_rows > 0) {
                                $no = 1;
                                while ($r = $qInfo->fetch_assoc()) {
                                    $stok = (float)$r['total_stok'];
                                    $tgl_kadaluarsa = $r['kadaluarsa'];

                                    // Logika Pewarnaan
                                    $rowClass = "";
                                    if (!empty($tgl_kadaluarsa)) {
                                        $expDate = new DateTime($tgl_kadaluarsa);
                                        $diff = $today->diff($expDate);
                                        $daysRemaining = (int)$diff->format("%r%a"); // %r untuk tanda negatif jika sudah lewat

                                        if ($daysRemaining < 0) {
                                            // Merah: Sudah lewat kadaluarsa
                                            $rowClass = "table-danger";
                                        } elseif ($daysRemaining <= 30) {
                                            // Orange: Mendekati kadaluarsa (kurang dari 30 hari)
                                            $rowClass = "style='background-color: #f7b100ff; color: white;'";
                                        }
                                    }

                                    // Kuning: Stok menipis (contoh: di bawah 50kg)
                                    // Jika sudah merah/orange, warna ini akan ditimpa jika kita pakai if-else, 
                                    // tapi di sini saya buat prioritas: Kadaluarsa > Stok Tipis.
                                    if ($rowClass == "" && $stok <= 50) {
                                        $rowClass = "table-warning";
                                    }

                                    // Render Baris
                                    // Gunakan class jika menggunakan bootstrap default, atau style manual
                                    $attr = (strpos($rowClass, 'style=') !== false) ? $rowClass : "class='$rowClass'";

                                    echo "<tr $attr>
                                        <td class='text-center'>{$no}</td>
                                        <td>{$r['jenis_beras']}</td>
                                        <td class='text-center'>{$r['merk']}</td>
                                        <td class='text-end fw-bold'>{$stok} kg</td>
                                        <td class='text-center'>" . ($tgl_kadaluarsa ?: '-') . "</td>
                                    </tr>";
                                    $no++;
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center text-muted'>Data stok belum tersedia.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex flex-wrap gap-2">
                    <small><span class="badge bg-danger">Merah</span> Kadaluarsa</small>
                    <small><span class="badge bg-warning text-dark">Kuning</span> Stok < 50kg</small>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Script untuk Modal Return
        const returnButtons = document.querySelectorAll('.btn-return');
        const modalReturnEl = document.getElementById('modalReturn');
        const modalReturn = new bootstrap.Modal(modalReturnEl);

        returnButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                // Ambil data dari tombol
                const id = this.dataset.id;
                const item = this.dataset.jenis + ' (' + this.dataset.merk + ')';
                const jumlah = this.dataset.jumlah + ' kg';

                // Isi ke dalam modal
                document.getElementById('ret-id').value = id;
                document.getElementById('ret-item').textContent = item;
                document.getElementById('ret-jumlah').textContent = jumlah;

                modalReturn.show();
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>