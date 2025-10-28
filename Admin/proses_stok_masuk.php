<?php
include '../includes/config.php';

$tanggal = $_POST['tanggal'] ?? date('Y-m-d');
$tanggal_kadaluarsa = $_POST['tanggal_kadaluarsa'] ?? null;
$jenis = $_POST['jenis_beras'] ?? '';
$merk = $_POST['merk'] ?? '';
$jumlah = isset($_POST['jumlah']) && is_numeric($_POST['jumlah']) ? (int)$_POST['jumlah'] : 0;
$harga_beli = isset($_POST['harga_beli']) ? (float)$_POST['harga_beli'] : 0;

// validasi singkat
if (trim($jenis) === '' || $jumlah <= 0) {
    header('Location: input_stok_masuk.php?error=invalid');
    exit;
}

$stmt = $koneksi->prepare("INSERT INTO stok_masuk (tanggal, tanggal_kadaluarsa, jenis_beras, merk, jumlah, harga_beli) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('ssssid', $tanggal, $tanggal_kadaluarsa, $jenis, $merk, $jumlah, $harga_beli);
$stmt->execute();
$stmt->close();

header('Location: stok_masuk.php?success=1');
exit;
