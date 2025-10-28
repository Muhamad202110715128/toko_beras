<?php
include 'includes/config.php';

// Data akun admin
$username = 'admin';
$password_plain = 'admin123';
$password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

// Cek apakah user sudah ada
$cek = $koneksi->query("SELECT * FROM users WHERE username = '$username'");
if ($cek->num_rows > 0) {
    echo "Akun admin sudah ada. Tidak perlu dibuat lagi.";
    exit;
}

// Tambahkan user ke database
$query = "INSERT INTO users (username, password) VALUES ('$username', '$password_hash')";
if ($koneksi->query($query)) {
    echo "Akun admin berhasil dibuat!<br>";
    echo "Username: <b>$username</b><br>";
    echo "Password: <b>$password_plain</b><br>";
    echo "<br><a href='login.php'>Klik untuk login</a>";
} else {
    echo "Gagal membuat akun admin: " . $koneksi->error;
}
