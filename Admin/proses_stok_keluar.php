<?php
include '../includes/config.php';

// Cek kelengkapan input (Deskripsi diganti harga_jual)
if (!isset($_POST['id_stok']) || !isset($_POST['jumlah_keluar']) || !isset($_POST['harga_jual'])) {
    die("<script>alert('Form tidak lengkap!'); window.location='input_stok_keluar.php';</script>");
}

$id_stok = intval($_POST['id_stok']);
$jumlah_keluar = intval($_POST['jumlah_keluar']);
$harga_jual_input = intval($_POST['harga_jual']); // Ambil harga jual dari input
$tanggal = date("Y-m-d");

// Ambil data stok masuk berdasarkan ID
$q = $koneksi->query("SELECT * FROM stok_masuk WHERE id = '$id_stok'");

if ($q->num_rows == 0) {
    die("<script>alert('Data stok tidak ditemukan!'); window.location='input_stok_keluar.php';</script>");
}

$data = $q->fetch_assoc();

$stok_sisa = $data['jumlah'];
$jenis = $data['jenis_beras'];
$merk = $data['merk'];
$tanggal_kadaluarsa = $data['tanggal_kadaluarsa'];

// Validasi stok cukup
if ($jumlah_keluar > $stok_sisa) {
    die("<script>alert('Stok tidak cukup! Sisa stok: $stok_sisa kg'); window.location='input_stok_keluar.php';</script>");
}

// Hitung stok baru di tabel stok_masuk
$stok_baru = $stok_sisa - $jumlah_keluar;

// Update stok sisa di tabel stok_masuk
$koneksi->query("
    UPDATE stok_masuk 
    SET jumlah = '$stok_baru' 
    WHERE id = '$id_stok'
");

// Insert ke tabel stok_keluar
// Kolom alasan diisi '-' karena form deskripsi dihapus
$koneksi->query("
    INSERT INTO stok_keluar (tanggal, jenis_beras, merk, jumlah, tanggal_kadaluarsa, harga_jual, alasan)
    VALUES (
        '$tanggal',
        '$jenis',
        '$merk',
        '$jumlah_keluar',
        '$tanggal_kadaluarsa',
        '$harga_jual_input', 
        '-' 
    )
");

echo "<script>alert('Stok berhasil dikeluarkan!'); window.location='stok_keluar.php';</script>";
