<?php
include 'includes/config.php';
$sql = "ALTER TABLE stok_masuk ADD COLUMN merk VARCHAR(100) NOT NULL DEFAULT ''";
if ($koneksi->query($sql) === TRUE) {
    echo "Kolom merk berhasil ditambahkan.";
} else {
    echo "Gagal: " . $koneksi->error;
}
