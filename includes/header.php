<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? null;
$username = $_SESSION['username'] ?? '';
// Tentukan ikon, label, dan warna berdasarkan role
$icon = '👤';
$avatarBg = '#6c757d'; // default abu
$roleLabel = '';
if ($role === 'admin') {
  $roleLabel = 'Admin';
  $icon = '👤';
  $avatarBg = '#6c757d';
} elseif ($role === 'kasir') {
  $icon = '👤';
  $avatarBg = '#6c757d';
  $roleLabel = 'Kasir';
} elseif ($role === 'pemilik' || $role === 'owner') {
  $icon = '👤';
  $avatarBg = '#6c757d';
  $roleLabel = 'Owner';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Stok Beras</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    /* avatar bulat seperti gambar */
    .user-avatar {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 20px;
      font-weight: 600;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
    }

    .user-info {
      display: inline-block;
      vertical-align: middle;
      margin-left: .6rem;
      text-align: left;
    }

    .user-info .name {
      font-weight: 600;
      font-size: .95rem;
      color: #fff;
    }

    .user-info .role {
      font-size: .75rem;
      opacity: .9;
      color: #f8f9fa;
      margin-top: -2px;
      display: block;
    }

    /* tombol hamburger kanan, lebih cerah */
    .btn-hamburger {
      filter: brightness(1.2);
      padding: .35rem .5rem;
      border-radius: .375rem;
      color: #fff;
      background: rgba(255, 255, 255, 0.06);
      border: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-hamburger:hover {
      filter: brightness(1.35);
      background: rgba(255, 255, 255, 0.09);
    }

    /* icon garis putih */
    .btn-hamburger .bars {
      font-size: 20px;
      line-height: 1;
      color: #fff;
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg bg-secondary py-2 mb-4">
    <div class="container d-flex justify-content-between align-items-center">
      <!-- Brand di kiri -->
      <div class="d-flex align-items-center">
        <span class="navbar-brand mb-0 h4 text-white" id="brandTitle">Agen Beras Idola</span>
      </div>

      <!-- kanan: tombol hamburger di samping kiri avatar -->
      <div class="d-flex align-items-center">
        <!-- hamburger button (di kiri gambar user) -->
        <button class="btn btn-hamburger me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"
          aria-controls="sidebarMenu" title="Menu">
          <span class="bars">☰</span>
        </button>

        <!-- avatar bulat + nama/role -->
        <?php if ($role): ?>
          <div class="d-flex align-items-center">
            <div class="user-avatar" style="background: <?= htmlspecialchars($avatarBg) ?>;">
              <?= $icon ?>
            </div>
            <div class="user-info ms-2 d-none d-sm-block">
              <div class="name"><?= htmlspecialchars($username ?: $roleLabel) ?></div>
              <div class="role"><?= htmlspecialchars($roleLabel) ?></div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <!-- Offcanvas sidebar yang muncul saat tombol hamburger diklik -->
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
        <a href="/toko_beras/dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
        <a href="/toko_beras/Admin/stok_masuk.php" class="list-group-item list-group-item-action">Stok Masuk</a>
        <a href="/toko_beras/dasboard/stok_keluar.php" class="list-group-item list-group-item-action">Stok Keluar</a>
        <a href="/toko_beras/low_stock.php" class="list-group-item list-group-item-action">Low Stock</a>
        <div class="list-group-item">
          <a href="/toko_beras/logout.php" class="btn btn-outline-danger w-100">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <div class="container mt-4">