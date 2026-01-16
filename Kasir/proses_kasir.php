<?php
include '../includes/config.php';
include '../includes/fungsi_notifikasi.php';

$aksi = $_POST['aksi'] ?? '';

// ==========================================
// 1. PROSES REQUEST STOK (MINTA KE ADMIN)
// ==========================================
if ($aksi === 'request_stok') {
    $jenis = $_POST['jenis_beras'];
    $merk  = $_POST['merk'];
    $jumlah = $_POST['jumlah'];
    $catatan = $_POST['catatan'];

    $judul = "Permintaan Stok: $jenis ($merk)";
    $pesan = "Kasir meminta stok $jumlah kg. Catatan: $catatan";

    // FIXED: Link Absolute ke Admin
    kirimNotifikasi($koneksi, 'admin', $judul, $pesan, '/toko_beras/admin/input_stok_keluar.php');

    // FIXED: Link Konfirmasi ke Kasir
    kirimNotifikasi($koneksi, 'kasir', 'Permintaan Terkirim', "Request $jenis ($jumlah kg) terkirim.", '#');

    echo "<script>alert('Permintaan stok berhasil dikirim ke Admin!'); window.location='items.php';</script>";
}

// ==========================================
// 2. PROSES RETURN BARANG
// ==========================================
elseif ($aksi === 'return_barang') {
    $id_keluar = (int)$_POST['id_stok_keluar'];
    $alasan = $_POST['alasan_return'];

    $q = $koneksi->query("SELECT * FROM stok_keluar WHERE id = '$id_keluar'");
    if ($q->num_rows == 0) {
        die("<script>alert('Data tidak ditemukan!'); window.history.back();</script>");
    }
    $data = $q->fetch_assoc();

    $jenis = $data['jenis_beras'];
    $merk  = $data['merk'];
    $jumlah = (int)$data['jumlah'];

    // Kembalikan ke Stok Masuk
    $update_masuk = $koneksi->query("
        UPDATE stok_masuk 
        SET jumlah = jumlah + $jumlah 
        WHERE jenis_beras = '$jenis' AND merk = '$merk' 
        ORDER BY tanggal DESC LIMIT 1
    ");

    if ($koneksi->affected_rows == 0) {
        $tgl = date('Y-m-d');
        $exp = date('Y-m-d', strtotime('+1 year'));
        $koneksi->query("INSERT INTO stok_masuk (tanggal, tanggal_kadaluarsa, jenis_beras, merk, jumlah, harga_beli) VALUES ('$tgl', '$exp', '$jenis', '$merk', $jumlah, 0)");
    }

    $koneksi->query("DELETE FROM stok_keluar WHERE id = '$id_keluar'");

    // FIXED: Link Absolute untuk Admin & Pemilik
    // Pastikan file laporan Pemilik Anda bernama 'laporan_Pemilik.php' di folder 'Pemilik'
    kirimNotifikasi(
        $koneksi,
        'admin',
        "Barang Return: $jenis",
        "Kasir return stok $jumlah kg. Alasan: $alasan",
        '/toko_beras/admin/stok_masuk.php'
    );

    kirimNotifikasi(
        $koneksi,
        'Pemilik',
        "Laporan Return",
        "Barang $jenis ($merk) direturn $jumlah kg oleh kasir.",
        '/toko_beras/Pemilik/laporan.php'
    );

    echo "<script>alert('Barang berhasil di-return!'); window.location='items.php';</script>";
}
