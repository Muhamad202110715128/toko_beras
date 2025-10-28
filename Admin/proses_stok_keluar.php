<?php
// jangan include header di file proses agar header() bisa berjalan
include '../includes/config.php';

$tanggal = $_POST['tanggal'] ?? date('Y-m-d');
$jenis = $_POST['jenis_beras'] ?? '';
$merk = $_POST['merk'] ?? null;
$alasan = $_POST['alasan'] ?? '';
$jumlah_keluar = isset($_POST['jumlah']) && is_numeric($_POST['jumlah']) ? (int)$_POST['jumlah'] : 1;

if (trim($jenis) === '' || $jumlah_keluar <= 0) {
    header("Location: stok_keluar.php?error=invalid_input");
    exit;
}

try {
    $koneksi->begin_transaction();

    // tipe parameter: tanggal(s), jenis(s), merk(s), jumlah(i), alasan(s) -> 'sssis'
    $ins = $koneksi->prepare("INSERT INTO stok_keluar (tanggal, jenis_beras, merk, jumlah, alasan) VALUES (?, ?, ?, ?, ?)");
    $ins->bind_param('sssis', $tanggal, $jenis, $merk, $jumlah_keluar, $alasan);
    $ins->execute();
    $ins->close();

    if ($merk) {
        $sel = $koneksi->prepare("SELECT id, jumlah FROM stok_masuk WHERE jenis_beras = ? AND merk = ? AND jumlah > 0 ORDER BY tanggal_kadaluarsa ASC, tanggal ASC FOR UPDATE");
        $sel->bind_param('ss', $jenis, $merk);
    } else {
        $sel = $koneksi->prepare("SELECT id, jumlah FROM stok_masuk WHERE jenis_beras = ? AND jumlah > 0 ORDER BY tanggal_kadaluarsa ASC, tanggal ASC FOR UPDATE");
        $sel->bind_param('s', $jenis);
    }
    $sel->execute();
    $res = $sel->get_result();

    $sisa = $jumlah_keluar;
    $update = $koneksi->prepare("UPDATE stok_masuk SET jumlah = ? WHERE id = ?");

    while ($row = $res->fetch_assoc()) {
        if ($sisa <= 0) break;
        $id = (int)$row['id'];
        $stok_tersedia = (int)$row['jumlah'];

        if ($stok_tersedia <= $sisa) {
            $zero = 0;
            $update->bind_param('ii', $zero, $id);
            $update->execute();
            $sisa -= $stok_tersedia;
        } else {
            $baru = $stok_tersedia - $sisa;
            $update->bind_param('ii', $baru, $id);
            $update->execute();
            $sisa = 0;
        }
    }

    $update->close();
    $sel->close();

    if ($sisa > 0) {
        $koneksi->rollback();
        header("Location: stok_keluar.php?error=insufficient_stock&need=" . $sisa);
        exit;
    }

    $koneksi->commit();
    header("Location: stok_keluar.php?success=1");
    exit;
} catch (Exception $e) {
    if ($koneksi->errno) $koneksi->rollback();
    header("Location: stok_keluar.php?error=server_error");
    exit;
}
