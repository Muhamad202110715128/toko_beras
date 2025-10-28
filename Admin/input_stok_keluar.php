<?php
include '../includes/config.php';
include '../includes/header.php';
?>


<h4>Input Stok Keluar (FEFO)</h4>
<form action="proses_stok_keluar.php" method="POST">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label>Tanggal</label>
            <input type="date" name="tanggal" class="form-control" required>
        </div>

        <div class="col-md-4 mb-3">
            <label>Jenis Beras</label>
            <input type="text" name="jenis_beras" class="form-control" placeholder="Contoh: Beras Putih" required>
        </div>

        <div class="col-md-4 mb-3">
            <label>Merk Beras</label>
            <input type="text" name="merk" class="form-control" placeholder="Contoh: Cap X" required>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label>Jumlah Keluar (kg)</label>
            <input type="number" name="jumlah" class="form-control" min="1" value="1" required>
        </div>

        <div class="col-md-4 mb-3">
            <label>Harga Jual (Rp)</label>
            <input type="number" name="harga_jual" class="form-control" min="0" required>
        </div>

        <div class="col-md-4 mb-3">
            <label>Alasan Keluar</label>
            <select name="alasan" class="form-select" required>
                <option value="">-- Pilih Alasan --</option>
                <option value="penjualan">Penjualan</option>
                <option value="retur">Retur</option>
                <option value="sampel">Sampel</option>
                <option value="lainnya">Lainnya</option>
            </select>
        </div>
    </div>

    <!-- Jika ingin alasan panjang, bisa gunakan textarea (opsional) -->
    <div class="mb-3">
        <label>Keterangan (opsional)</label>
        <textarea name="keterangan" class="form-control" rows="2" placeholder="Detail tambahan (mis. nama pembeli, nota...)"></textarea>
    </div>

    <button type="submit" class="btn btn-success">Simpan</button>
</form>

<?php include '../includes/footer.php'; ?>