<?php
include '../includes/config.php';
include '../includes/header.php';

// PERUBAHAN 1: Cek Akses Admin (Bukan Owner lagi)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>window.location='../login.php';</script>";
    exit;
}

$pesan = '';

// === PROSES TAMBAH / HAPUS JENIS BERAS ===
if (isset($_POST['tambah_jenis'])) {
    $nama = trim($_POST['nama_jenis']);
    if ($nama !== '') {
        $koneksi->query("INSERT INTO jenis_beras (nama_jenis) VALUES ('$nama')");
        $pesan = "Jenis beras berhasil ditambahkan!";
    }
}
if (isset($_GET['hapus_jenis'])) {
    $id = intval($_GET['hapus_jenis']);
    $koneksi->query("DELETE FROM jenis_beras WHERE id_jenis = $id");
    echo "<script>window.location='input_data.php';</script>";
}

// === PROSES TAMBAH / HAPUS MERK BERAS ===
if (isset($_POST['tambah_merk'])) {
    $nama = trim($_POST['nama_merk']);
    if ($nama !== '') {
        $koneksi->query("INSERT INTO merk_beras (nama_merk) VALUES ('$nama')");
        $pesan = "Merk beras berhasil ditambahkan!";
    }
}
if (isset($_GET['hapus_merk'])) {
    $id = intval($_GET['hapus_merk']);
    $koneksi->query("DELETE FROM merk_beras WHERE id_merk = $id");
    echo "<script>window.location='input_data.php';</script>";
}

// Ambil data terbaru
$jenis_beras = $koneksi->query("SELECT * FROM jenis_beras ORDER BY id_jenis DESC");
$merk_beras  = $koneksi->query("SELECT * FROM merk_beras ORDER BY id_merk DESC");
?>

<style>
    .card-header-custom {
        font-weight: bold;
        padding: 15px 20px;
    }
</style>

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
            <a href="stok_masuk.php" class="list-group-item list-group-item-action">Stok Gudang</a>
            <a href="stok_keluar.php" class="list-group-item list-group-item-action">Stok Keluar</a>
            <a href="low_stock.php" class="list-group-item list-group-item-action">Low Stock</a>
            <a href="input_data.php" class="list-group-item list-group-item-action active">Input Data</a>

            <div class="list-group-item">
                <a href="../logout.php" class="btn btn-outline-danger w-100">Logout</a>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4">
    <h4 class="mb-4 fw-bold text-dark"><i class="bi bi-database-gear me-2"></i> Kelola Data Master</h4>
    <p class="text-muted">Tambah atau hapus referensi Jenis & Merk beras.</p>

    <?php if ($pesan): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= $pesan ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-success text-white card-header-custom">
                    <i class="bi bi-tag me-2"></i> Jenis Beras
                </div>
                <div class="card-body">
                    <form method="POST" class="input-group mb-4">
                        <input type="text" name="nama_jenis" class="form-control" placeholder="Nama Jenis (Misal: Pandan Wangi)" required>
                        <button type="submit" name="tambah_jenis" class="btn btn-success">
                            <i class="bi bi-plus-lg"></i> Tambah
                        </button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-center">
                                <tr>
                                    <th width="10%">No</th>
                                    <th>Nama Jenis</th>
                                    <th width="20%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if ($jenis_beras->num_rows > 0):
                                    while ($row = $jenis_beras->fetch_assoc()):
                                ?>
                                        <tr>
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($row['nama_jenis']); ?></td>
                                            <td class="text-center">
                                                <a href="?hapus_jenis=<?= $row['id_jenis']; ?>"
                                                    class="btn btn-outline-danger btn-sm border-0"
                                                    onclick="return confirm('Yakin hapus? Data stok terkait mungkin akan error.')"
                                                    title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php
                                    endwhile;
                                else:
                                    ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Belum ada data.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-primary text-white card-header-custom">
                    <i class="bi bi-award me-2"></i> Merk Beras
                </div>
                <div class="card-body">
                    <form method="POST" class="input-group mb-4">
                        <input type="text" name="nama_merk" class="form-control" placeholder="Nama Merk (Misal: Idola, Maknyus)" required>
                        <button type="submit" name="tambah_merk" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Tambah
                        </button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-center">
                                <tr>
                                    <th width="10%">No</th>
                                    <th>Nama Merk</th>
                                    <th width="20%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if ($merk_beras->num_rows > 0):
                                    while ($row = $merk_beras->fetch_assoc()):
                                ?>
                                        <tr>
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($row['nama_merk']); ?></td>
                                            <td class="text-center">
                                                <a href="?hapus_merk=<?= $row['id_merk']; ?>"
                                                    class="btn btn-outline-danger btn-sm border-0"
                                                    onclick="return confirm('Yakin hapus? Data stok terkait mungkin akan error.')"
                                                    title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php
                                    endwhile;
                                else:
                                    ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Belum ada data.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>