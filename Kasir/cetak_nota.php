<?php
include '../includes/config.php';

// 1. Ambil ID dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. Ambil Data Transaksi
$q = $koneksi->query("SELECT * FROM penjualan WHERE id_penjualan = '$id'");
$data = $q->fetch_assoc();

if (!$data) {
    die("Data transaksi tidak ditemukan.");
}

// Format Data
$tgl = date('d/m/Y H:i', strtotime($data['tanggal']));
$total = number_format($data['total_harga'], 0, ',', '.');
// Jika di database tidak ada harga satuan, kita hitung manual (Total / Jumlah)
// Atau sesuaikan jika kolom 'harga' menyimpan harga satuan
$harga_satuan = number_format($data['total_harga'] / $data['jumlah'], 0, ',', '.');
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota #<?= $id ?></title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            /* Font struk */
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            padding-top: 20px;
        }

        #nota-box {
            background: white;
            width: 300px;
            /* Lebar standar struk thermal */
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
        }

        .header p {
            margin: 2px 0;
            font-size: 12px;
        }

        .detail-item {
            font-size: 13px;
            margin-bottom: 5px;
        }

        .garis {
            border-bottom: 1px dashed #000;
            margin: 10px 0;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 14px;
        }

        .footer {
            text-align: center;
            font-size: 11px;
            margin-top: 20px;
        }

        /* Tombol Aksi (Tidak ikut ter-print/download jika diatur) */
        .btn-area {
            margin-top: 20px;
            text-align: center;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
        }

        .btn-pdf {
            background: #dc3545;
            color: white;
        }

        .btn-print {
            background: #0d6efd;
            color: white;
        }

        .btn-back {
            background: #6c757d;
            color: white;
        }

        /* Sembunyikan tombol saat mode PDF/Print berjalan */
        @media print {

            .btn-area,
            body {
                background: white;
                padding: 0;
            }

            #nota-box {
                box-shadow: none;
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <div>
        <div id="area-nota">
            <div id="nota-box">
                <div class="header">
                    <h2>TOKO BERAS JAYA</h2>
                    <p>Jl. Raya Padi No. 88, Jakarta</p>
                    <p>Telp: 0812-3456-7890</p>
                </div>

                <div style="font-size: 12px; margin-bottom: 10px;">
                    <div>No Nota : #<?= $id ?></div>
                    <div>Tgl : <?= $tgl ?></div>
                    <div>Kasir : Admin</div>
                </div>

                <div class="garis"></div>

                <div class="detail-item">
                    <strong><?= $data['jenis_beras'] ?> - <?= $data['merk'] ?></strong><br>
                    <?= $data['jumlah'] ?> kg x Rp <?= $harga_satuan ?>
                    <span style="float: right;">Rp <?= $total ?></span>
                </div>

                <div class="garis"></div>

                <div class="total-row">
                    <span>TOTAL BAYAR</span>
                    <span>Rp <?= $total ?></span>
                </div>

                <div class="total-row" style="font-weight: normal; font-size: 12px; margin-top: 5px;">
                    <span>Tunai</span>
                    <span>Rp <?= $total ?></span>
                </div>

                <div class="footer">
                    <p>Terima Kasih atas Kunjungan Anda</p>
                    <p>Barang yang sudah dibeli<br>tidak dapat ditukar kembali.</p>
                </div>
            </div>
        </div>

        <div class="btn-area" data-html2canvas-ignore="true">
            <button onclick="downloadPDF()" class="btn btn-pdf">
                Download PDF
            </button>
            <button onclick="window.print()" class="btn btn-print">
                Print Biasa
            </button>
            <a href="order.php" class="btn btn-back">Kembali</a>
        </div>
    </div>

    <script>
        function downloadPDF() {
            // Ambil elemen nota
            var element = document.getElementById('area-nota');

            // Konfigurasi PDF
            var opt = {
                margin: 0,
                filename: 'Nota_<?= $id ?>.pdf',
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2
                }, // Scale tinggi agar teks tajam
                jsPDF: {
                    unit: 'in',
                    format: 'a6',
                    orientation: 'portrait'
                } // Format A6 mirip struk
            };

            // Eksekusi download
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>

</html>