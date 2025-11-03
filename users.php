<?php
include 'includes/config.php';

function tambahUser($koneksi, $username, $plain_password, $nama_lengkap, $role)
{
    $hash = password_hash($plain_password, PASSWORD_DEFAULT);
    $stmt = $koneksi->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        die("Query error: " . $koneksi->error);
    }
    $stmt->bind_param('ssss', $username, $hash, $nama_lengkap, $role);
    $stmt->execute();
    $stmt->close();

    echo "✅ Akun $role berhasil dibuat dengan username: <b>$username</b> dan password: <b>$plain_password</b><br>";
}

// Tambahkan akun admin, kasir, dan pemilik
tambahUser($koneksi, 'admin', 'admin123', 'Administrator', 'admin');
tambahUser($koneksi, 'kasir', 'kasir123', 'Kasir Toko', 'kasir');
tambahUser($koneksi, 'pemilik', 'pemilik123', 'Pemilik Toko', 'pemilik');

echo "<hr><b>Semua akun berhasil dibuat!</b><br>";
echo "Silakan login melalui <a href='login.php'>login.php</a>";
