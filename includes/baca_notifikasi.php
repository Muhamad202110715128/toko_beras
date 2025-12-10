<?php
// Pastikan file config ada di folder yang sama (includes)
include 'config.php';

// Ambil ID notifikasi dan Link Redirect dari URL
$id = (int)($_GET['id'] ?? 0);
$redirect = $_GET['redirect'] ?? '#';

// 1. Update Status Notifikasi di Database
if ($id > 0) {
    $koneksi->query("UPDATE notifikasi SET status = 'read' WHERE id = $id");
}

// 2. Logika Redirect (Pengalihan Halaman)
if ($redirect == '#' || empty($redirect)) {
    // Jika link kosong/pagar, kembalikan ke halaman sebelumnya (Back)
    if (isset($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        // Jika tidak ada history, lempar ke dashboard admin sebagai fallback
        // Sesuaikan '/toko_beras/' dengan nama folder project Anda di htdocs
        header("Location: /toko_beras/Admin/dashboard.php");
    }
} else {
    // Jika ada link, arahkan langsung ke sana
    header("Location: " . $redirect);
}
exit;
