<?php
session_start();
include 'includes/config.php';

// Jika sudah login, langsung ke dashboard masing-masing
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            exit;
        case 'kasir':
            header("Location: kasir/dashboard.php");
            exit;
        case 'pemilik':
            header("Location: pemilik/dashboard.php");
            exit;
    }
}

// Jika form dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = $koneksi->prepare("SELECT * FROM users WHERE username = ?");
    $query->bind_param("s", $username);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        if (password_verify($password, $data['password'])) {
            $_SESSION['id_user'] = $data['id_user'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
            $_SESSION['role'] = $data['role'];

            if ($data['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            } elseif ($data['role'] == 'kasir') {
                header("Location: kasir/dashboard.php");
            } elseif ($data['role'] == 'pemilik') {
                header("Location: pemilik/dashboard.php");
            }
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Login - Sistem Stok Beras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
</head>

<body class=" d-flex justify-content-center align-items-center" style="height:100vh;">
    <div class="card shadow p-4" style="width: 350px;">
        <h4 class="text-center mb-3">Login</h4>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger"><?= $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</body>

</html>