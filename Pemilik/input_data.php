<?php
include '../includes/config.php';
include '../includes/header.php';

// Proses Tambah Jenis Beras
if (isset($_POST['tambah_jenis'])) {
    $nama_jenis = trim($_POST['nama_jenis']);
    if ($nama_jenis != '') {
        $koneksi->query("INSERT INTO jenis_beras (nama_jenis) VALUES ('$nama_jenis')");
    }
}

// Proses Tambah Merk Beras
if (isset($_POST['tambah_merk'])) {
    $nama_merk = trim($_POST['nama_merk']);
    if ($nama_merk != '') {
        $koneksi->query("INSERT INTO merk_beras (nama_merk) VALUES ('$nama_merk')");
    }
}

// Proses Hapus Jenis
if (isset($_GET['hapus_jenis'])) {
    $id = $_GET['hapus_jenis'];
    $koneksi->query("DELETE FROM jenis_beras WHERE id_jenis = '$id'");
}

// Proses Hapus Merk
if (isset($_GET['hapus_merk'])) {
    $id = $_GET['hapus_merk'];
    $koneksi->query("DELETE FROM merk_beras WHERE id_merk = '$id'");
}

// Ambil data dari database
$jenis = $koneksi->query("SELECT * FROM jenis_beras ORDER BY id_jenis DESC");
$merk = $koneksi->query("SELECT * FROM merk_beras ORDER BY id_merk DESC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Jenis & Merk Beras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">
        <h2 class="mb-4 text-center fw-bold">Kelola Data Jenis & Merk Beras</h2>

        <div class="row g-4">
            <!-- Form Jenis Beras -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white fw-semibold">Jenis Beras</div>
                    <div class="card-body">
                        <form method="post" class="d-flex mb-3">
                            <input type="text" name="nama_jenis" class="form-control me-2" placeholder="Tambah Jenis Beras" required>
                            <button type="submit" name="tambah_jenis" class="btn btn-success">Tambah</button>
                        </form>

                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Jenis</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                while ($row = $jenis->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><?= htmlspecialchars($row['nama_jenis']); ?></td>
                                        <td>
                                            <a href="?hapus_jenis=<?= $row['id_jenis']; ?>" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Yakin hapus jenis ini?')">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Form Merk Beras -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white fw-semibold">Merk Beras</div>
                    <div class="card-body">
                        <form method="post" class="d-flex mb-3">
                            <input type="text" name="nama_merk" class="form-control me-2" placeholder="Tambah Merk Beras" required>
                            <button type="submit" name="tambah_merk" class="btn btn-primary">Tambah</button>
                        </form>

                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Merk</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                while ($row = $merk->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><?= htmlspecialchars($row['nama_merk']); ?></td>
                                        <td>
                                            <a href="?hapus_merk=<?= $row['id_merk']; ?>" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Yakin hapus merk ini?')">Hapus</a>
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

</body>

</html>