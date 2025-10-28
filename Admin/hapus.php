<?php
include '../includes/config.php';

$table = $_GET['table']; // stok_masuk atau stok_keluar
$id = $_GET['id'];

if (($table === 'stok_masuk' || $table === 'stok_keluar') && is_numeric($id)) {
    $koneksi->query("DELETE FROM $table WHERE id = $id");
}

header("Location: " . $table . ".php");
exit;
