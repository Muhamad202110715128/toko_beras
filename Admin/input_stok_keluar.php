<?php
include '../includes/config.php';
include '../includes/header.php';

// =======================
// AMBIL FILTER
// =======================
$filter_jenis = $_GET['jenis'] ?? '';
$filter_merk  = $_GET['merk'] ?? '';

// ambil daftar jenis & merk
$jenis_q = $koneksi->query("SELECT nama_jenis FROM jenis_beras ORDER BY nama_jenis ASC");
$merk_q  = $koneksi->query("SELECT nama_merk  FROM merk_beras ORDER BY nama_merk ASC");

// =======================
// QUERY STOK MASUK (dengan filter)
// =======================
$where = [];

if ($filter_jenis !== '') {
    $where[] = "jenis_beras = '" . $koneksi->real_escape_string($filter_jenis) . "'";
}

if ($filter_merk !== '') {
    $where[] = "merk = '" . $koneksi->real_escape_string($filter_merk) . "'";
}

$sql = "SELECT * FROM stok_masuk";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

// Urutkan stok masuk (FIFO: First In First Out sebaiknya berdasarkan tanggal masuk asc, tapi sesuai request Anda desc)
$sql .= " ORDER BY tanggal DESC, tanggal_kadaluarsa ASC";

$q = $koneksi->query($sql);

// list modal
$modal_list = [];
?>

<style>
    .table-detail thead th {
        background: #f8f9fa;
        font-weight: 700;
    }
</style>
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
            <a href="stok_keluar.php" class="list-group-item list-group-item-action active">Stok Keluar</a>
            <a href="low_stock.php" class="list-group-item list-group-item-action">Low Stock</a>
            <div class="list-group-item">
                <a href="../logout.php" class="btn btn-outline-danger w-100">Logout</a>
            </div>
        </div>
    </div>
</div>


<div class="container mt-4">

    <div class="d-flex justify-content-between mb-3">
        <h4>📤 Input Stok Keluar</h4>
    </div>

    <!-- ================================
         FORM FILTER
    ================================== -->
    <form method="GET" class="row g-3 mb-4 p-3 border rounded bg-light">

        <div class="col-md-4">
            <label class="form-label mb-1">Jenis Beras</label>
            <select name="jenis" class="form-select form-select-sm">
                <option value="">-- Semua Jenis --</option>
                <?php while ($j = $jenis_q->fetch_assoc()): ?>
                    <option value="<?= $j['nama_jenis'] ?>"
                        <?= ($filter_jenis == $j['nama_jenis']) ? 'selected' : '' ?>>
                        <?= $j['nama_jenis'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label mb-1">Merk</label>
            <select name="merk" class="form-select form-select-sm">
                <option value="">-- Semua Merk --</option>
                <?php while ($m = $merk_q->fetch_assoc()): ?>
                    <option value="<?= $m['nama_merk'] ?>"
                        <?= ($filter_merk == $m['nama_merk']) ? 'selected' : '' ?>>
                        <?= $m['nama_merk'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-4 d-flex align-items-end">
            <button class="btn btn-primary btn-sm w-100">Terapkan Filter</button>
        </div>

    </form>

    <!-- ================================
         TABEL STOK MASUK
    ================================== -->

    <div class="card">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-detail table-striped table-hover align-middle">
                    <thead class="text-center">
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Kadaluarsa</th>
                            <th>Jenis Beras</th>
                            <th>Merk</th>
                            <th class="text-end">Jumlah (kg)</th>
                            <th class="text-end">Harga Beli</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        if ($q && $q->num_rows > 0) {
                            $no = 1;

                            while ($row = $q->fetch_assoc()) {
                                $id     = $row['id'];
                                $tgl    = $row['tanggal'];
                                $exp    = $row['tanggal_kadaluarsa'];
                                $jenis  = $row['jenis_beras'];
                                $merk   = $row['merk'];
                                $jumlah = $row['jumlah'];
                                $harga  = number_format($row['harga_beli'], 0, ',', '.');

                                echo "
                                <tr>
                                    <td class='text-center'>$no</td>
                                    <td>$tgl</td>
                                    <td class='text-center'>$exp</td>
                                    <td>$jenis</td>
                                    <td class='text-center'>$merk</td>
                                    <td class='text-end'>$jumlah</td>
                                    <td class='text-end'>Rp $harga</td>
                                    <td class='text-center'>
                                        <button class='btn btn-primary btn-sm'
                                                data-bs-toggle='modal'
                                                data-bs-target='#modalKeluar_$id'>
                                            Pilih
                                        </button>
                                    </td>
                                </tr>
                                ";

                                // --- PERUBAHAN DI SINI (MODAL) ---
                                $modal_list[] = "
                                <div class='modal fade' id='modalKeluar_$id' tabindex='-1'>
                                    <div class='modal-dialog'>
                                        <form action='proses_stok_keluar.php' method='POST' class='modal-content'>

                                            <div class='modal-header'>
                                                <h5 class='modal-title'>Stok Keluar — $jenis</h5>
                                                <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                            </div>

                                            <div class='modal-body'>

                                                <input type='hidden' name='id_stok' value='$id'>

                                                <div class='mb-3'>
                                                    <label class='form-label'>Jumlah yang dikeluarkan (kg)</label>
                                                    <input type='number' 
                                                           name='jumlah_keluar' 
                                                           class='form-control' 
                                                           min='1' 
                                                           max='$jumlah'
                                                           required>
                                                    <small class='text-muted'>Sisa stok tersedia: <b>$jumlah kg</b></small>
                                                </div>

                                                <!-- Bagian Deskripsi diganti Harga Jual -->
                                                <div class='mb-3'>
                                                    <label class='form-label fw-bold'>Harga Jual (Rp)</label>
                                                    <div class='input-group'>
                                                        <span class='input-group-text'>Rp</span>
                                                        <input type='number' 
                                                               name='harga_jual' 
                                                               class='form-control' 
                                                               placeholder='Masukkan harga jual...'
                                                               required>
                                                    </div>
                                                    <small class='text-muted'>Harga Beli: Rp $harga</small>
                                                </div>

                                            </div>

                                            <div class='modal-footer'>
                                                <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Batal</button>
                                                <button type='submit' class='btn btn-success'>Simpan & Keluarkan</button>
                                            </div>

                                        </form>
                                    </div>
                                </div>
                                ";

                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='8' class='text-center text-muted'>Tidak ada data stok masuk.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<!-- TAMPILKAN SEMUA MODAL -->
<?php
foreach ($modal_list as $modal) {
    echo $modal;
}
?>

<?php include '../includes/footer.php'; ?>