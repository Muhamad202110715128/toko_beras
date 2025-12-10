<?php
include '../includes/config.php';

// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // =========================================================
    // 1. TANGKAP DATA DARI FORM (DATA BARU)
    // =========================================================
    $id = (int) ($_POST['id'] ?? 0);
    $tanggal_baru = $_POST['tanggal'] ?? '';

    // Dropdown mengirim ID, nanti kita konversi jadi Nama
    $id_jenis_input = $_POST['id_jenis'] ?? '';
    $id_merk_input = $_POST['id_merk'] ?? '';

    $jumlah_baru = (int) ($_POST['jumlah'] ?? 0);
    $harga_jual_baru = (int) str_replace(['Rp', '.', ' '], '', $_POST['harga_jual'] ?? 0);

    // Validasi input
    if ($id <= 0 || empty($tanggal_baru) || $jumlah_baru <= 0 || empty($id_jenis_input)) {
        echo "<script>alert('Gagal! Data tidak lengkap.'); window.history.back();</script>";
        exit;
    }

    // =========================================================
    // 2. KONVERSI ID DROPDOWN -> MENJADI NAMA (TEKS)
    // =========================================================

    // Cari Nama Jenis Beras Baru
    $nama_jenis_baru = $id_jenis_input;
    if (is_numeric($id_jenis_input)) {
        $qj = $koneksi->query("SELECT nama_jenis FROM jenis_beras WHERE id_jenis = '$id_jenis_input'");
        if ($qj && $qj->num_rows > 0) $nama_jenis_baru = $qj->fetch_assoc()['nama_jenis'];
    }

    // Cari Nama Merk Baru
    $nama_merk_baru = $id_merk_input;
    if (is_numeric($id_merk_input)) {
        $qm = $koneksi->query("SELECT nama_merk FROM merk_beras WHERE id_merk = '$id_merk_input'");
        if ($qm && $qm->num_rows > 0) $nama_merk_baru = $qm->fetch_assoc()['nama_merk'];
    }

    // =========================================================
    // 3. MULAI TRANSAKSI DATABASE (SAFETY MODE)
    // =========================================================
    // Ini penting! Jika ada error di tengah jalan, semua perubahan dibatalkan.
    $koneksi->begin_transaction();

    try {
        // =========================================================
        // A. AMBIL DATA TRANSAKSI LAMA (SEBELUM DIEDIT)
        // =========================================================
        $q_lama = $koneksi->query("SELECT * FROM stok_keluar WHERE id = '$id'");
        if ($q_lama->num_rows === 0) {
            throw new Exception("Data stok keluar tidak ditemukan.");
        }
        $data_lama = $q_lama->fetch_assoc();

        $jumlah_lama = (int)$data_lama['jumlah'];
        $jenis_lama  = $data_lama['jenis_beras'];
        $merk_lama   = $data_lama['merk'];

        // =========================================================
        // B. KEMBALIKAN STOK LAMA KE GUDANG (RESTOCK)
        // =========================================================
        // Kita masukkan kembali barangnya ke stok_masuk yang cocok (Paling baru masuk)
        $stmt_restock = $koneksi->prepare("
            UPDATE stok_masuk 
            SET jumlah = jumlah + ? 
            WHERE jenis_beras = ? AND merk = ? 
            ORDER BY tanggal DESC 
            LIMIT 1
        ");
        $stmt_restock->bind_param("iss", $jumlah_lama, $jenis_lama, $merk_lama);
        $stmt_restock->execute();

        // Cek: Jika tidak ada stok_masuk yang cocok (mungkin sudah dihapus semua),
        // Kita buat baris baru di stok_masuk sebagai penampung pengembalian (Safety Net)
        if ($stmt_restock->affected_rows === 0) {
            $tgl_hari_ini = date('Y-m-d');
            // Estimasi kadaluarsa 3 bulan dr sekarang jika tidak ada data
            $tgl_exp = date('Y-m-d', strtotime('+3 months'));

            $stmt_insert_safety = $koneksi->prepare("
                INSERT INTO stok_masuk (tanggal, tanggal_kadaluarsa, jenis_beras, merk, jumlah, harga_beli)
                VALUES (?, ?, ?, ?, ?, 0)
            ");
            $stmt_insert_safety->bind_param("ssssi", $tgl_hari_ini, $tgl_exp, $jenis_lama, $merk_lama, $jumlah_lama);
            $stmt_insert_safety->execute();
        }

        // =========================================================
        // C. CEK KETERSEDIAAN STOK UNTUK DATA BARU
        // =========================================================

        // Hitung total stok yang tersedia sekarang untuk beras tipe BARU
        $cek_stok = $koneksi->query("
            SELECT SUM(jumlah) as total_tersedia 
            FROM stok_masuk 
            WHERE jenis_beras = '$nama_jenis_baru' AND merk = '$nama_merk_baru'
        ");
        $data_stok = $cek_stok->fetch_assoc();
        $total_tersedia = (int)($data_stok['total_tersedia'] ?? 0);

        // Jika stok kurang dari permintaan baru
        if ($total_tersedia < $jumlah_baru) {
            throw new Exception("Stok gudang tidak cukup! Tersedia: $total_tersedia kg, Anda meminta: $jumlah_baru kg.");
        }

        // =========================================================
        // D. POTONG STOK BARU (METODE FIFO)
        // Ambil dari yang expired paling cepat
        // =========================================================
        $sisa_minta = $jumlah_baru;

        $q_batch = $koneksi->query("
            SELECT id, jumlah 
            FROM stok_masuk 
            WHERE jenis_beras = '$nama_jenis_baru' AND merk = '$nama_merk_baru' AND jumlah > 0
            ORDER BY tanggal_kadaluarsa ASC, tanggal ASC
        ");

        while ($batch = $q_batch->fetch_assoc()) {
            if ($sisa_minta <= 0) break;

            $id_batch = $batch['id'];
            $qty_batch = $batch['jumlah'];

            if ($qty_batch >= $sisa_minta) {
                // Batch ini cukup
                $koneksi->query("UPDATE stok_masuk SET jumlah = jumlah - $sisa_minta WHERE id = '$id_batch'");
                $sisa_minta = 0;
            } else {
                // Batch ini kurang, habiskan, lalu lanjut ke batch berikutnya
                $koneksi->query("UPDATE stok_masuk SET jumlah = 0 WHERE id = '$id_batch'");
                $sisa_minta -= $qty_batch;
            }
        }

        // =========================================================
        // E. UPDATE DATA DI TABEL STOK_KELUAR (AKHIR)
        // =========================================================
        $stmt_update = $koneksi->prepare("UPDATE stok_keluar SET tanggal = ?, jenis_beras = ?, merk = ?, jumlah = ?, harga_jual = ? WHERE id = ?");
        $stmt_update->bind_param("sssiii", $tanggal_baru, $nama_jenis_baru, $nama_merk_baru, $jumlah_baru, $harga_jual_baru, $id);
        $stmt_update->execute();

        // Jika sampai sini tidak ada error, simpan permanen!
        $koneksi->commit();

        echo "<script>alert('Sukses! Data diedit & stok gudang disesuaikan.'); window.location='stok_keluar.php';</script>";
    } catch (Exception $e) {
        // Jika ada error (misal stok kurang), batalkan semua perubahan di atas
        $koneksi->rollback();
        echo "<script>alert('Gagal: " . $e->getMessage() . "'); window.history.back();</script>";
    }
} else {
    header("Location: stok_keluar.php");
}
