<?php
include '../includes/config.php';
include '../includes/header.php';

// === PROSES TAMBAH / HAPUS JENIS BERAS ===
if (isset($_POST['tambah_jenis'])) {
    $nama = trim($_POST['nama_jenis']);
    if ($nama !== '') {
        $koneksi->query("INSERT INTO jenis_beras (nama_jenis) VALUES ('$nama')");
    }
}
if (isset($_GET['hapus_jenis'])) {
    $id = intval($_GET['hapus_jenis']);
    $koneksi->query("DELETE FROM jenis_beras WHERE id_jenis = $id");
}

// === PROSES TAMBAH / HAPUS MERK BERAS ===
if (isset($_POST['tambah_merk'])) {
    $nama = trim($_POST['nama_merk']);
    if ($nama !== '') {
        $koneksi->query("INSERT INTO merk_beras (nama_merk) VALUES ('$nama')");
    }
}
if (isset($_GET['hapus_merk'])) {
    $id = intval($_GET['hapus_merk']);
    $koneksi->query("DELETE FROM merk_beras WHERE id_merk = $id");
}

// Ambil data dari database
$jenis_beras = $koneksi->query("SELECT * FROM jenis_beras ORDER BY id_jenis DESC");
$merk_beras  = $koneksi->query("SELECT * FROM merk_beras ORDER BY id_merk DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Jenis & Merk Beras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <h3 class="mb-4">🧺 Kelola Jenis dan Merk Beras</h3>

    <div class="row g-4">
        <!-- === Kelola Jenis Beras === -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <strong>Jenis Beras</strong>
                </div>
                <div class="card-body">
                    <form method="POST" class="d-flex mb-3">
                        <input type="text" name="nama_jenis" class="form-control me-2" placeholder="Masukkan jenis beras baru" required>
                        <button type="submit" name="tambah_jenis" class="btn btn-success">Tambah</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Jenis</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; while($row = $jenis_beras->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nama_jenis']); ?></td>
                                    <td>
                                        <a href="?hapus_jenis=<?= $row['id_jenis']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Yakin ingin menghapus jenis ini?')">Hapus</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- === Kelola Merk Beras === -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <strong>Merk Beras</strong>
                </div>
                <div class="card-body">
                    <form method="POST" class="d-flex mb-3">
                        <input type="text" name="nama_merk" class="form-control me-2" placeholder="Masukkan merk beras baru" required>
                        <button type="submit" name="tambah_merk" class="btn btn-primary">Tambah</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Merk</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; while($row = $merk_beras->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nama_merk']); ?></td>
                                    <td>
                                        <a href="?hapus_merk=<?= $row['id_merk']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Yakin ingin menghapus merk ini?')">Hapus</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
