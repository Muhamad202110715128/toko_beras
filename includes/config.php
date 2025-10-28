<?php
$koneksi = new mysqli("localhost", "root", "", "db_stok_beras");
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}
