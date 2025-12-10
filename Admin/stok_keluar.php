<?php
include '../includes/config.php';
include '../includes/header.php';

// Aktifkan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek Akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>window.location='../login.php';</script>";
    exit;
}

// ==========================================
// 1. QUERY CARD RINGKASAN
// ==========================================

// A. Total Stok Keluar (Semua Waktu)
$q_total = $koneksi->query("SELECT SUM(jumlah) as total_kg FROM stok_keluar");
$d_total = $q_total->fetch_assoc();
$total_keluar = $d_total['total_kg'] ?? 0;

// B. Permintaan Masuk (Dari Notifikasi Kasir)
// Kita hitung notifikasi yang judulnya mengandung "Permintaan Stok" dan statusnya unread
$q_req = $koneksi->query("
    SELECT COUNT(*) as jum 
    FROM notifikasi 
    WHERE user_role = 'admin' 
    AND status = 'unread' 
    AND judul LIKE 'Permintaan Stok%'
");
$d_req = $q_req->fetch_assoc();
$total_request = $d_req['jum'] ?? 0;


// AMBIL DATA MASTER UNTUK DROPDOWN
$jenis_q = $koneksi->query("SELECT * FROM jenis_beras ORDER BY nama_jenis ASC");
$merk_q  = $koneksi->query("SELECT * FROM merk_beras ORDER BY nama_merk ASC");
?>

<style>
    /* Styling Table Header */
    .table-out thead th {
        background: #f8f9fa;
        font-weight: 700;
        border-bottom: 2px solid #dee2e6;
    }

    .table-hover tbody tr:hover {
        background-color: #f1f3f5;
    }

    /* Styling Card Stat */
    .card-stat {
        border: none;
        border-left: 5px solid;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s;
    }

    .card-stat:hover {
        transform: translateY(-3px);
    }
</style>

<!-- SIDEBAR -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="sidebarMenu">
    <div class="offcanvas-header">
        <div class="d-flex align-items-center">
            <div class="user-avatar me-2" style="background: <?= htmlspecialchars($avatarBg ?? '#eee') ?>;">
                <?= $icon ?? '<i class="bi bi-person"></i>' ?>
            </div>
            <div>
                <div class="fw-bold"><?= htmlspecialchars($username ?: ($roleLabel ?? 'Admin')) ?></div>
                <small class="text-muted"><?= htmlspecialchars($roleLabel ?? 'Administrator') ?></small>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="list-group list-group-flush">
            <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
            <a href="gudang.php" class="list-group-item list-group-item-action">Stok Gudang</a>
            <a href="stok_masuk.php" class="list-group-item list-group-item-action">Input Stok Masuk</a>
            <a href="stok_keluar.php" class="list-group-item list-group-item-action active">Stok Keluar</a>
            <a href="low_stock.php" class="list-group-item list-group-item-action">Low Stock</a>
            <div class="list-group-item">
                <a href="../logout.php" class="btn btn-outline-danger w-100">Logout</a>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4">

    <!-- HEADER PAGE -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">📤 Data Stok Keluar</h4>
            <small class="text-muted">Riwayat barang keluar atau terjual</small>
        </div>
        <a href="input_stok_keluar.php" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i> Input Data
        </a>
    </div>

    <!-- KARTU RINGKASAN (DASHBOARD) -->
    <div class="row mb-4">

        <!-- Card 1: Total Stok Keluar -->
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card h-100 card-stat border-warning">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 text-dark rounded p-3 me-3">
                        <i class="bi bi-box-arrow-up fs-2"></i>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase fw-bold mb-1">Total Barang Keluar</h6>
                        <h3 class="fw-bold mb-0 text-dark">
                            <?= number_format($total_keluar, 0, ',', '.') ?> <small class="fs-6 text-muted">kg</small>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Permintaan Masuk -->
        <div class="col-md-6">
            <div class="card h-100 card-stat border-info">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-info bg-opacity-10 text-primary rounded p-3 me-3">
                        <i class="bi bi-envelope-exclamation fs-2"></i>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase fw-bold mb-1">Permintaan Masuk</h6>
                        <h3 class="fw-bold mb-0 text-primary">
                            <?= $total_request ?> <small class="fs-6 text-muted">Pesan</small>
                        </h3>
                        <?php if ($total_request > 0): ?>
                            <small class="text-danger fst-italic">Cek notifikasi Anda!</small>
                        <?php else: ?>
                            <small class="text-success fst-italic">Tidak ada permintaan baru.</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- CARD TABEL -->
    <div class="card shadow-sm border-0">
        <!-- Fitur Pencarian -->
        <div class="card-header bg-white py-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <h6 class="mb-0 fw-bold text-secondary"><i class="bi bi-list-check me-2"></i>Daftar Transaksi Keluar</h6>
                </div>
                <div class="col-md-4 ms-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari merk, jenis, atau tanggal...">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-out table-striped table-hover align-middle mb-0" id="tableData">
                    <thead class="text-center text-nowrap">
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>Tanggal Keluar</th>
                            <th>Tgl Kadaluarsa</th>
                            <th>Jenis Beras</th>
                            <th>Merk</th>
                            <th class="text-end">Jml (kg)</th>
                            <th class="text-end">Harga Jual</th>
                            <th style="width:100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = $koneksi->query("SELECT * FROM stok_keluar ORDER BY tanggal DESC");

                        if ($q && $q->num_rows > 0) {
                            $no = 1;
                            while ($row = $q->fetch_assoc()) {

                                $id = $row['id'];
                                // Format Tanggal
                                $tgl = !empty($row['tanggal']) ? date('d-m-Y', strtotime($row['tanggal'])) : '-';
                                $tk  = !empty($row['tanggal_kadaluarsa']) ? date('d-m-Y', strtotime($row['tanggal_kadaluarsa'])) : '-';

                                $jenis = htmlspecialchars($row['jenis_beras'] ?? '');
                                $merk  = htmlspecialchars($row['merk'] ?? '');
                                $jumlah = number_format($row['jumlah'] ?? 0, 0, ',', '.');
                                $harga_tampil = number_format((float)($row['harga_jual'] ?? 0), 0, ',', '.');
                                $harga_raw = $row['harga_jual'] ?? 0;

                                echo "<tr>
                                    <td class='text-center'>{$no}</td>
                                    <td class='text-center'>{$tgl}</td>
                                    <td class='text-center'>{$tk}</td>
                                    <td>{$jenis}</td>
                                    <td class='text-center'>{$merk}</td>
                                    <td class='text-end'>{$jumlah}</td>
                                    <td class='text-end'>Rp {$harga_tampil}</td>
                                    <td class='text-center'>
                                        <button class='btn btn-warning btn-sm btn-edit'
                                            data-bs-toggle='modal'
                                            data-bs-target='#editModal'
                                            data-id='{$id}'
                                            data-tanggal='" . ($row['tanggal'] ?? '') . "' 
                                            data-jenis='{$jenis}'
                                            data-merk='{$merk}'
                                            data-jumlah='" . ($row['jumlah'] ?? 0) . "'
                                            data-harga='{$harga_raw}'
                                            title='Edit Data'>
                                            <i class='bi bi-pencil-square'></i> Edit
                                        </button>
                                    </td>
                                </tr>";
                                $no++;
                            }
                        } else {
                            echo '<tr><td colspan="8" class="text-center text-muted py-5">Belum ada data stok keluar.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- FORM EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="proses_edit_stok_keluar.php" method="POST" class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-dark"><i class="bi bi-pencil-square me-2"></i> Edit Stok Keluar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="id" id="e-id">

                <div class="mb-3">
                    <label class="form-label">Tanggal Keluar</label>
                    <input type="date" name="tanggal" id="e-tanggal" class="form-control" required>
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Jenis Beras</label>
                        <select name="id_jenis" id="e-jenis" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <?php
                            if ($jenis_q && $jenis_q->num_rows > 0) {
                                $jenis_q->data_seek(0);
                                while ($j = $jenis_q->fetch_assoc()): ?>
                                    <option value="<?= $j['id_jenis'] ?>"><?= htmlspecialchars($j['nama_jenis']) ?></option>
                            <?php endwhile;
                            } ?>
                        </select>
                    </div>

                    <div class="col-6 mb-3">
                        <label class="form-label">Merk</label>
                        <select name="id_merk" id="e-merk" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <?php
                            if ($merk_q && $merk_q->num_rows > 0) {
                                $merk_q->data_seek(0);
                                while ($m = $merk_q->fetch_assoc()): ?>
                                    <option value="<?= $m['id_merk'] ?>"><?= htmlspecialchars($m['nama_merk']) ?></option>
                            <?php endwhile;
                            } ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jumlah (kg)</label>
                    <input type="number" name="jumlah" id="e-jumlah" class="form-control" required>
                    <small class="text-success" style="font-size: 0.8rem;">
                        <i class="bi bi-info-circle"></i> Stok gudang akan otomatis disesuaikan (dikembalikan/dikurangi).
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Harga Jual (Rp)</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="harga_jual" id="e-harga" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- JAVASCRIPT: Modal & Pencarian -->
<script>
    document.addEventListener("DOMContentLoaded", function() {

        // 1. Script Pencarian Tabel
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#tableData tbody tr');

            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        // 2. Script Tombol Edit
        const buttons = document.querySelectorAll(".btn-edit");
        buttons.forEach(btn => {
            btn.addEventListener("click", function() {
                // Isi input standar
                document.getElementById("e-id").value = this.dataset.id;
                document.getElementById("e-tanggal").value = this.dataset.tanggal;
                document.getElementById("e-jumlah").value = this.dataset.jumlah;
                document.getElementById("e-harga").value = this.dataset.harga;

                // Auto Select Dropdown Jenis Beras
                let ddJenis = document.getElementById("e-jenis");
                let textJenis = this.dataset.jenis;
                ddJenis.selectedIndex = 0; // Reset
                for (let i = 0; i < ddJenis.options.length; i++) {
                    if (ddJenis.options[i].text === textJenis) {
                        ddJenis.selectedIndex = i;
                        break;
                    }
                }

                // Auto Select Dropdown Merk
                let ddMerk = document.getElementById("e-merk");
                let textMerk = this.dataset.merk;
                ddMerk.selectedIndex = 0; // Reset
                for (let i = 0; i < ddMerk.options.length; i++) {
                    if (ddMerk.options[i].text === textMerk) {
                        ddMerk.selectedIndex = i;
                        break;
                    }
                }
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>