<?php
// Pastikan path ini benar. 
// Asumsi: file ini ada di folder /admin/, jadi config ada di ../includes/
include '../includes/config.php';

// Cek apakah parameter ada
if (isset($_GET['table']) && isset($_GET['id'])) {
    $table = $_GET['table'];
    $id = (int)$_GET['id'];

    // Keamanan: Hanya boleh menghapus tabel tertentu
    $allowed_tables = ['stok_masuk', 'stok_keluar'];

    if (in_array($table, $allowed_tables)) {
        $stmt = $koneksi->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Redirect kembali ke halaman tabel yang bersangkutan
// Karena file ini ada di folder admin, kita langsung panggil nama filenya saja
if (isset($_GET['table']) && $_GET['table'] == 'stok_masuk') {
    header("Location: stok_masuk.php");
} elseif (isset($_GET['table']) && $_GET['table'] == 'stok_keluar') {
    header("Location: stok_keluar.php");
} else {
    // Default redirect jika tabel tidak dikenal
    header("Location: dashboard.php");
}
exit;
