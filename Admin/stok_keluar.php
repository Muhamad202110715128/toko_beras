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

<!-- side bar -->
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
            <a href="/toko_beras/admin/dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
            <a href="/toko_beras/Admin/stok_masuk.php" class="list-group-item list-group-item-action">Stok Masuk</a>
            <a href="/toko_beras/admin/stok_keluar.php" class="list-group-item list-group-item-action">Stok Keluar</a>
            <a href="/toko_beras/admin/low_stock.php" class="list-group-item list-group-item-action">Low Stock</a>
            <div class="list-group-item">
                <a href="/toko_beras/logout.php" class="btn btn-outline-danger w-100">Logout</a>
            </div>
        </div>
    </div>
</div>


<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="card-title mb-0">Data Stok Keluar</h5>
    <a href="input_stok_keluar.php" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Input Data
    </a>
</div>


<hr>
<div class="card">
    <div class="card-body">

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
                    $q = $koneksi->query("
                        SELECT 
                            id,
                            tanggal,
                            tanggal_kadaluarsa,
                            jenis_beras,
                            merk,
                            jumlah,
                            harga_jual,
                            alasan
                        FROM stok_keluar
                        ORDER BY tanggal DESC
                    ");

                    if ($q && $q->num_rows > 0) {
                        $no = 1;

                        while ($row = $q->fetch_assoc()) {

                            $id = $row['id'];
                            $tgl = $row['tanggal'] ?: '-';
                            $tk = $row['tanggal_kadaluarsa'] ?: '-';
                            $jenis = $row['jenis_beras'] ?: '-';
                            $merk = $row['merk'] ?: '-';
                            $jumlah = $row['jumlah'] ?: 0;

                            $harga = number_format($row['harga_jual'], 0, ',', '.');
                            $alasan = $row['alasan'] ?: '-';

                            echo "
        <tr>
            <td class='text-center'>{$no}</td>
            <td>{$tgl}</td>
            <td class='text-center'>{$tk}</td>
            <td>{$jenis}</td>
            <td class='text-center'>{$merk}</td>
            <td class='text-end'>{$jumlah}</td>
            <td class='text-end'>Rp {$harga}</td>

            <td class='text-center'>
                <button 
                    class='btn btn-info btn-sm btn-detail'
                    data-bs-toggle='modal'
                    data-bs-target='#detailModal'

                    data-tanggal='{$tgl}'
                    data-tanggal_kadaluarsa='{$tk}'
                    data-jenis='{$jenis}'
                    data-merk='{$merk}'
                    data-jumlah='{$jumlah}'
                    data-harga='Rp {$harga}'
                    data-alasan='{$alasan}'
                >
                    Detail
                </button>
            </td>
        </tr>
        ";

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
    document.addEventListener("DOMContentLoaded", function() {

        const buttons = document.querySelectorAll(".btn-detail");

        buttons.forEach(btn => {
            btn.addEventListener("click", function() {

                document.getElementById("d-tanggal").textContent = this.dataset.tanggal;
                document.getElementById("d-tanggal-kadaluarsa").textContent = this.dataset.tanggal_kadaluarsa;
                document.getElementById("d-jenis").textContent = this.dataset.jenis;
                document.getElementById("d-merk").textContent = this.dataset.merk;
                document.getElementById("d-jumlah").textContent = this.dataset.jumlah + " kg";
                document.getElementById("d-harga").textContent = this.dataset.harga;
                document.getElementById("d-alasan").textContent = this.dataset.alasan;

            });
        });

    });
</script>

<?php include '../includes/footer.php'; ?>