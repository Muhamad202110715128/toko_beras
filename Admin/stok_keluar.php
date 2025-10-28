<?php
include '../includes/config.php';
include '../includes/header.php';
?>
<style>
    .table-out thead th {
        background: #f8f9fa;
        font-weight: 700;
    }
</style>

<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="sidebarMenuLabel">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="list-group list-group-flush">
            <a href="dashboard.php" class="list-group-item list-group-item-action active">Dashboard</a>
            <a href="stok_masuk.php" class="list-group-item list-group-item-action">Stok Masuk</a>
            <a href="stok_keluar.php" class="list-group-item list-group-item-action">Stok Keluar</a>
        </div>
    </div>
</div>

<div class="mb-3">
    <a href="input_stok_keluar.php" class="btn btn-success"> Input data</a>
</div>

<hr>
<div class="card">
    <div class="card-body">
        <h5 class="card-title mb-3">Data Stok Keluar</h5>

        <?php $q = $koneksi->query("SELECT * FROM stok_keluar ORDER BY tanggal DESC"); ?>

        <div class="table-responsive">
            <table class="table table-out table-striped table-hover align-middle">
                <thead class="text-center">
                    <tr>
                        <th style="width:64px;">No</th>
                        <th>Tanggal Keluar</th>
                        <th>Tanggal Kadaluarsa</th>
                        <th>Jenis Beras</th>
                        <th>Merk</th>
                        <th class="text-end">Jumlah (kg)</th>
                        <th class="text-end">Harga Jual (Rp)</th>
                        <th style="width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($q && $q->num_rows > 0) {
                        $no = 1;
                        while ($row = $q->fetch_assoc()) {
                            $id = (int)$row['id'];
                            $tgl = htmlspecialchars($row['tanggal'] ?? '-');
                            $tk = htmlspecialchars($row['tanggal_kadaluarsa'] ?? '-');
                            $jenis = htmlspecialchars($row['jenis_beras'] ?? '-');
                            $merk = $row['merk'] !== '' ? htmlspecialchars($row['merk']) : '-';
                            $jumlah = (int)($row['jumlah'] ?? 0);
                            $harga = number_format((float)($row['harga_jual'] ?? 0), 0, ',', '.');
                            echo "<tr>
                <td class='text-center'>{$no}</td>
                <td>{$tgl}</td>
                <td class='text-center'>{$tk}</td>
                <td>{$jenis}</td>
                <td class='text-center'>{$merk}</td>
                <td class='text-end'>{$jumlah}</td>
                <td class='text-end'>Rp {$harga}</td>
                <td class='text-center'>
                  <button class='btn btn-info btn-sm btn-detail' data-id='{$id}'>Detail</button>
                </td>
              </tr>";
                            $no++;
                        }
                    } else {
                        echo '<tr><td colspan="8" class="text-center text-muted">Belum ada data stok keluar.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">Detail Stok Keluar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <th class="w-50">Tanggal Keluar</th>
                            <td id="d-tanggal"></td>
                        </tr>
                        <tr>
                            <th>Tanggal Kadaluarsa</th>
                            <td id="d-tanggal-kadaluarsa"></td>
                        </tr>
                        <tr>
                            <th>Jenis Beras</th>
                            <td id="d-jenis"></td>
                        </tr>
                        <tr>
                            <th>Merk Beras</th>
                            <td id="d-merk"></td>
                        </tr>
                        <tr>
                            <th>Jumlah Keluar (kg)</th>
                            <td id="d-jumlah" class="text-end"></td>
                        </tr>
                        <tr>
                            <th>Harga Jual</th>
                            <td id="d-harga" class="text-end"></td>
                        </tr>
                        <tr>
                            <th>Alasan</th>
                            <td id="d-alasan"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const detailButtons = document.querySelectorAll('.btn-detail');
        const modalEl = document.getElementById('detailModal');
        const modal = new bootstrap.Modal(modalEl);

        detailButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                // ambil data dari atribut
                const dTanggal = this.dataset.tanggal || '-';
                const dKadaluarsa = this.dataset.tanggalKadaluarsa || '-';
                const dJenis = this.dataset.jenis || '-';
                const dMerk = this.dataset.merk || '-';
                const dJumlah = this.dataset.jumlah || '-';
                const dHarga = this.dataset.harga || '-';
                const dAlasan = this.dataset.alasan || '-';

                // isi modal
                document.getElementById('d-tanggal').textContent = dTanggal;
                document.getElementById('d-tanggal-kadaluarsa').textContent = dKadaluarsa;
                document.getElementById('d-jenis').textContent = dJenis;
                document.getElementById('d-merk').textContent = dMerk;
                document.getElementById('d-jumlah').textContent = dJumlah + ' kg';
                document.getElementById('d-harga').textContent = dHarga;
                document.getElementById('d-alasan').textContent = dAlasan;

                modal.show();
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>