<?php
include '../includes/config.php';
include '../includes/header.php';

// Ambil Data untuk Dropdown
$q_jenis_beras = $koneksi->query("SELECT * FROM jenis_beras ORDER BY nama_jenis ASC");
$q_merk_beras  = $koneksi->query("SELECT * FROM merk_beras ORDER BY nama_merk ASC");

// ==========================================
// PROSES TRANSAKSI (FEFO + INSERT STOK KELUAR)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis = $_POST['jenis_beras'] ?? '';
    $merk = $_POST['merk'] ?? '';
    $jumlah_jual = (int)($_POST['jumlah'] ?? 0);

    // INPUT INI SEKARANG ADALAH TOTAL HARGA (KONTAN)
    $total_bayar_input = (float)($_POST['harga'] ?? 0);

    if ($jenis && $merk && $jumlah_jual > 0 && $total_bayar_input > 0) {

        // 1. CEK TOTAL STOK TERSEDIA
        $cek_stok = $koneksi->query("SELECT SUM(jumlah) as total FROM stok_masuk WHERE jenis_beras='$jenis' AND merk='$merk'");
        $data_stok = $cek_stok->fetch_assoc();
        $total_tersedia = (int)($data_stok['total'] ?? 0);

        if ($total_tersedia < $jumlah_jual) {
            echo '<div class="alert alert-danger">⚠️ Stok tidak cukup! Tersedia: ' . $total_tersedia . ' kg.</div>';
        } else {
            // Stok Cukup, Lanjut Proses

            // Hitung harga rata-rata per kg (Hanya untuk catatan di stok_keluar jika diperlukan)
            // Rumus: Total Bayar / Jumlah Kg
            $harga_per_kg_rata2 = $total_bayar_input / $jumlah_jual;

            // Ambil stok berdasarkan FEFO
            $stok_masuk = $koneksi->query("
                SELECT id, jumlah, tanggal_kadaluarsa 
                FROM stok_masuk 
                WHERE jenis_beras='$jenis' AND merk='$merk' AND jumlah > 0 
                ORDER BY tanggal_kadaluarsa ASC, tanggal ASC
            ");

            $sisa_minta = $jumlah_jual;

            // Mulai Loop Pengurangan Stok
            while ($row = $stok_masuk->fetch_assoc()) {
                if ($sisa_minta <= 0) break;

                $id_masuk = (int)$row['id'];
                $stok_batch = (int)$row['jumlah'];
                $tgl_kadaluarsa = $row['tanggal_kadaluarsa'];

                // Tentukan berapa yang diambil dari batch ini
                $ambil = min($sisa_minta, $stok_batch);

                // A. UPDATE STOK MASUK (Kurangi)
                $update = $koneksi->query("UPDATE stok_masuk SET jumlah = jumlah - $ambil WHERE id = $id_masuk");

                // B. INSERT KE STOK KELUAR
                if ($update) {
                    // Di tabel stok_keluar biasanya kolom harga_jual menyimpan harga satuan. 
                    // Kita simpan harga rata-rata per kg yang tadi dihitung.
                    $koneksi->query("
                        INSERT INTO stok_keluar (tanggal, jenis_beras, merk, jumlah, tanggal_kadaluarsa, harga_jual, alasan)
                        VALUES (NOW(), '$jenis', '$merk', '$ambil', '$tgl_kadaluarsa', '$harga_per_kg_rata2', 'Penjualan Kasir')
                    ");
                }

                $sisa_minta -= $ambil;
            }

            // C. SIMPAN KE TABEL PENJUALAN
            // Perhatikan: Kolom 'harga' di tabel penjualan biasanya satuan. 
            // Jika struktur tabel Anda:
            // - harga: harga satuan
            // - total_harga: total bayar
            // Maka kita masukkan $harga_per_kg_rata2 ke kolom 'harga' dan $total_bayar_input ke 'total_harga'.

            $simpan_transaksi = $koneksi->query("
                INSERT INTO penjualan (tanggal, jenis_beras, merk, jumlah, harga, total_harga)
                VALUES (NOW(), '$jenis', '$merk', '$jumlah_jual', '$harga_per_kg_rata2', '$total_bayar_input')
            ");

            if ($simpan_transaksi) {
                echo '<div class="alert alert-success">✅ Transaksi berhasil! Stok gudang otomatis diperbarui.</div>';
            } else {
                echo '<div class="alert alert-warning">⚠️ Transaksi berhasil diproses stoknya, tapi gagal simpan riwayat penjualan.</div>';
            }
        }
    } else {
        echo '<div class="alert alert-danger">⚠️ Mohon isi semua field dengan benar.</div>';
    }
}

// ===== Ambil daftar penjualan =====
$q_penjualan = $koneksi->query("SELECT * FROM penjualan ORDER BY tanggal DESC LIMIT 10");

?>

<div class="container mt-4">
    <div class="offcanvas offcanvas-end" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
        <div class="offcanvas-header">
            <div class="d-flex align-items-center">
                <div class="user-avatar me-2" style="background: <?= htmlspecialchars($avatarBg) ?>;">
                    <?= $icon ?>
                </div>
                <div>
                    <div class="fw-bold"><?= htmlspecialchars($username ?: $roleLabel) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($roleLabel) ?></small>
                </div>
            </div>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="list-group list-group-flush">
                <a href="/toko_beras/kasir/dashboard.php" class="list-group-item list-group-item-action ">Dashboard</a>
                <a href="/toko_beras/kasir/order.php" class="list-group-item list-group-item-action active">Pecatatan Pemesanan</a>
                <a href="/toko_beras/kasir/items.php" class="list-group-item list-group-item-action ">Stok Beras</a>
                <a href="/toko_beras/kasir/revenue.php" class="list-group-item list-group-item-action">Penjualan Harian</a>
                <a href="/toko_beras/kasir/sales.php" class="list-group-item list-group-item-action">Sales</a>
                <div class="list-group-item">
                    <a href="/toko_beras/logout.php" class="btn btn-outline-danger w-100">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mb-4">🧾 Transaksi Penjualan (Harga Kontan)</h4>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="POST" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Jenis Beras</label>
                    <select name="jenis_beras" class="form-select" required>
                        <option value="">-- Pilih Jenis --</option>
                        <?php
                        if ($q_jenis_beras) {
                            $q_jenis_beras->data_seek(0);
                            while ($j = $q_jenis_beras->fetch_assoc()) {
                                echo "<option value='{$j['nama_jenis']}'>{$j['nama_jenis']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Merk</label>
                    <select name="merk" class="form-select" required>
                        <option value="">-- Pilih Merk --</option>
                        <?php
                        if ($q_merk_beras) {
                            $q_merk_beras->data_seek(0);
                            while ($m = $q_merk_beras->fetch_assoc()) {
                                echo "<option value='{$m['nama_merk']}'>{$m['nama_merk']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jumlah (kg)</label>
                    <input type="number" name="jumlah" class="form-control" min="1" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total Harga (Rp)</label>
                    <input type="number" name="harga" class="form-control" min="1" placeholder="Cth: 50000" required>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-success w-100">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        /* Styling khusus tampilan Nota di dalam Modal */
        #nota-content {
            font-family: 'Courier New', Courier, monospace;
            padding: 20px;
            background: #fff;
            color: #000;
        }

        .nota-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .nota-body {
            font-size: 14px;
        }

        .nota-footer {
            text-align: center;
            font-size: 12px;
            margin-top: 20px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }

        .nota-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .fw-bold {
            font-weight: bold;
        }
    </style>

    <h5 class="mb-3">🗃️ Riwayat Transaksi (Terbaru)</h5>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Merk</th>
                            <th>Jumlah (kg)</th>
                            <th>Total Bayar (Rp)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if ($q_penjualan && $q_penjualan->num_rows > 0) {
                            // Reset pointer data jika perlu
                            $q_penjualan->data_seek(0);
                            while ($row = $q_penjualan->fetch_assoc()) {
                                $tgl = date('d/m/Y H:i', strtotime($row['tanggal']));
                                $total_fmt = number_format($row['total_harga'], 0, ',', '.');
                                $jumlah = $row['jumlah'];

                                // Hitung harga satuan (estimasi) untuk ditampilkan di nota
                                $harga_satuan = $row['total_harga'] / $jumlah;
                                $harga_satuan_fmt = number_format($harga_satuan, 0, ',', '.');

                                echo "<tr>
                                <td>{$no}</td>
                                <td>{$tgl}</td>
                                <td>{$row['jenis_beras']}</td>
                                <td>{$row['merk']}</td>
                                <td>{$jumlah}</td>
                                <td class='fw-bold'>Rp {$total_fmt}</td>
                                <td>
                                    <button type='button' class='btn btn-sm btn-primary' 
                                        onclick=\"tampilNota(
                                            '{$row['id_penjualan']}', 
                                            '{$tgl}', 
                                            '{$row['jenis_beras']}', 
                                            '{$row['merk']}', 
                                            '{$jumlah}', 
                                            '{$harga_satuan_fmt}', 
                                            '{$total_fmt}'
                                        )\">
                                        <i class='bi bi-receipt'></i> Nota
                                    </button>
                                </td>
                            </tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-muted'>Belum ada transaksi.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalNota" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title"><i class="bi bi-printer"></i> Pratinjau Nota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body bg-secondary bg-opacity-10">
                    <div id="area-nota">
                        <div id="nota-content">
                            <div class="nota-header">
                                <h5 style="margin:0; font-weight:bold;">TOKO BERAS JAYA</h5>
                                <small>Jl. Raya Padi No. 88, Jakarta</small><br>
                                <small>Telp: 0812-3456-7890</small>
                            </div>

                            <div class="nota-body">
                                <div class="nota-row">
                                    <span id="n-no">#000</span>
                                    <span id="n-tgl">01/01/2024</span>
                                </div>
                                <hr style="border-top: 1px dashed #000; margin: 5px 0;">

                                <div style="margin-bottom: 5px;">
                                    <strong id="n-item">Beras - Merk</strong><br>
                                    <div class="nota-row">
                                        <span><span id="n-qty">0</span> kg x <span id="n-satuan">0</span></span>
                                        <span id="n-subtotal">0</span>
                                    </div>
                                </div>

                                <hr style="border-top: 1px dashed #000; margin: 5px 0;">

                                <div class="nota-row" style="font-weight:bold; font-size:16px;">
                                    <span>TOTAL</span>
                                    <span>Rp <span id="n-total">0</span></span>
                                </div>
                                <div class="nota-row">
                                    <span>Tunai</span>
                                    <span>Rp <span id="n-tunai">0</span></span>
                                </div>
                            </div>

                            <div class="nota-footer">
                                <p style="margin:0;">Terima Kasih</p>
                                <small>Barang yang dibeli tidak dapat ditukar.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm" onclick="downloadPDF()">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="printNota()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Inisialisasi Modal Bootstrap
        let modalNota;
        document.addEventListener("DOMContentLoaded", function() {
            modalNota = new bootstrap.Modal(document.getElementById('modalNota'));
        });

        // Fungsi Mengisi Data ke Modal
        function tampilNota(id, tgl, jenis, merk, qty, satuan, total) {
            // Isi elemen HTML di dalam modal dengan data parameter
            document.getElementById('n-no').innerText = '#' + id;
            document.getElementById('n-tgl').innerText = tgl;
            document.getElementById('n-item').innerText = jenis + ' - ' + merk;
            document.getElementById('n-qty').innerText = qty;
            document.getElementById('n-satuan').innerText = satuan;
            document.getElementById('n-subtotal').innerText = total; // Karena cuma 1 item, subtotal = total
            document.getElementById('n-total').innerText = total;
            document.getElementById('n-tunai').innerText = total;

            // Tampilkan Modal
            modalNota.show();
        }

        // Fungsi Download PDF
        function downloadPDF() {
            const element = document.getElementById('area-nota');
            const idNota = document.getElementById('n-no').innerText;

            const opt = {
                margin: 0,
                filename: 'Nota_' + idNota + '.pdf',
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2
                },
                jsPDF: {
                    unit: 'in',
                    format: 'a6',
                    orientation: 'portrait'
                }
            };

            html2pdf().set(opt).from(element).save();
        }

        // Fungsi Print Biasa (Browser)
        function printNota() {
            // Teknik print khusus area modal
            const printContent = document.getElementById('area-nota').innerHTML;
            const originalContent = document.body.innerHTML;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            location.reload(); // Reload agar event listener kembali normal setelah print
        }
    </script>

    <?php include '../includes/footer.php'; ?>