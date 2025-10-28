<?php
include '../includes/config.php';
include '../includes/header.php';
?>
<style>
    .table-detail thead th {
        background: #f8f9fa;
        font-weight: 700;
    }
</style>

<div class="card">
    <div class="card-body">
        <h5 class="card-title mb-3">Data Stok Masuk</h5>

        <?php $q = $koneksi->query("SELECT * FROM stok_masuk ORDER BY tanggal DESC, tanggal_kadaluarsa ASC"); ?>

        <div class="table-responsive">
            <table class="table table-detail table-striped table-hover align-middle">
                <thead class="text-center">
                    <tr>
                        <th style="width:64px;">No</th>
                        <th>Tanggal</th>
                        <th>Tanggal Kadaluarsa</th>
                        <th>Jenis Beras</th>
                        <th>Merk</th>
                        <th class="text-end">Jumlah (kg)</th>
                        <th class="text-end">Harga Beli (Rp)</th>
                        <th style="width:140px;">Aksi</th>
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
                            $harga = number_format((float)($row['harga_beli'] ?? 0), 0, ',', '.');
                            echo "<tr>
                <td class='text-center'>{$no}</td>
                <td>{$tgl}</td>
                <td class='text-center'>{$tk}</td>
                <td>{$jenis}</td>
                <td class='text-center'>{$merk}</td>
                <td class='text-end'>{$jumlah}</td>
                <td class='text-end'>Rp {$harga}</td>
                <td class='text-center'>
                  <a href='edit_stok_masuk.php?id={$id}' class='btn btn-warning btn-sm'>Edit</a>
                  <a href='../../hapus.php?table=stok_masuk&id={$id}' class='btn btn-danger btn-sm' onclick=\"return confirm('Yakin?')\">Hapus</a>
                </td>
              </tr>";
                            $no++;
                        }
                    } else {
                        echo '<tr><td colspan="8" class="text-center text-muted">Belum ada data stok masuk.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>