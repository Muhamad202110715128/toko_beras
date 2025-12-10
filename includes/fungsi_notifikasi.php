<?php
// Fungsi untuk mengirim notifikasi ke database
function kirimNotifikasi($koneksi, $role_target, $judul, $pesan, $link = '#')
{
    // Validasi data
    $judul = $koneksi->real_escape_string($judul);
    $pesan = $koneksi->real_escape_string($pesan);

    // --- ANTI SPAM ---
    // Cek apakah hari ini sudah ada notifikasi persis sama?
    $sql_cek = "SELECT id FROM notifikasi 
                WHERE user_role='$role_target' 
                AND judul='$judul' 
                AND DATE(created_at) = CURDATE()";

    $cek = $koneksi->query($sql_cek);

    // Jika belum ada, baru kirim
    if ($cek->num_rows == 0) {
        $sql = "INSERT INTO notifikasi (user_role, judul, pesan, link, status, created_at) 
                VALUES ('$role_target', '$judul', '$pesan', '$link', 'unread', NOW())";
        $koneksi->query($sql);
    }
}

function hitungNotifikasiBelumDibaca($koneksi, $role)
{
    $q = $koneksi->query("SELECT COUNT(*) as jumlah FROM notifikasi WHERE user_role = '$role' AND status = 'unread'");
    $d = $q->fetch_assoc();
    return $d['jumlah'] ?? 0;
}

function ambilNotifikasi($koneksi, $role, $limit = 10)
{
    return $koneksi->query("SELECT * FROM notifikasi WHERE user_role = '$role' ORDER BY created_at DESC LIMIT $limit");
}

// ==========================================
// FUNGSI AUTO CHECK (FIX FOLDER PATH)
// ==========================================
function cekPeringatanStokOtomatis($koneksi)
{
    // 1. CEK STOK MENIPIS (<= 20kg)
    $q_low = $koneksi->query("
        SELECT jenis_beras, merk, SUM(jumlah) as total_sisa 
        FROM stok_masuk 
        GROUP BY jenis_beras, merk 
        HAVING total_sisa <= 20
    ");

    while ($r = $q_low->fetch_assoc()) {
        $item = "{$r['jenis_beras']} ({$r['merk']})";
        $sisa = $r['total_sisa'];
        $judul = "Stok Menipis: $item";

        // A. Notif ADMIN -> Link ke Folder 'Admin'
        kirimNotifikasi($koneksi, 'admin', $judul, "Stok sisa $sisa kg. Segera restok.", '/toko_beras/Admin/stok_masuk.php');

        // B. Notif KASIR -> Info saja
        kirimNotifikasi($koneksi, 'kasir', $judul, "Info: Stok fisik menipis ($sisa kg).", '#');

        // C. Notif PEMILIK -> Link ke Folder 'Pemilik' (Huruf Besar P) & Role 'pemilik'
        kirimNotifikasi($koneksi, 'pemilik', $judul, "Laporan: Stok kritis sisa $sisa kg.", '/toko_beras/Pemilik/laporan.php');
    }

    // 2. CEK KADALUARSA (<= 30 Hari)
    $tgl_warning = date('Y-m-d', strtotime('+30 days'));
    $q_exp = $koneksi->query("
        SELECT jenis_beras, merk, tanggal_kadaluarsa 
        FROM stok_masuk 
        WHERE tanggal_kadaluarsa <= '$tgl_warning' AND jumlah > 0
    ");

    while ($r = $q_exp->fetch_assoc()) {
        $item = "{$r['jenis_beras']} ({$r['merk']})";
        $tgl  = date('d/m/Y', strtotime($r['tanggal_kadaluarsa']));
        $judul = "Warning Expired: $item";

        // Admin
        kirimNotifikasi($koneksi, 'admin', $judul, "Batch expired pada $tgl. Proses segera.", '/toko_beras/Admin/stok_masuk.php');

        // Pemilik
        kirimNotifikasi($koneksi, 'pemilik', $judul, "Batch akan expired ($tgl). Pantau gudang.", '/toko_beras/Pemilik/laporan.php');
    }
}
