<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. INCLUDE FILE PENTING
// Gunakan dirname agar path aman dari manapun file ini di-include
include_once dirname(__FILE__) . '/config.php';
include_once dirname(__FILE__) . '/fungsi_notifikasi.php';

$role = $_SESSION['role'] ?? null;
$username = $_SESSION['username'] ?? '';

// 2. LOGIKA TAMPILAN ROLE
$icon = '👤'; // Saya kembalikan ke icon orang standar agar tidak error encoding
$avatarBg = '#6c757d';
$roleLabel = '';

if ($role === 'admin') {
  $roleLabel = 'Admin';
  $avatarBg = '#15d736ff';
} elseif ($role === 'kasir') {
  $avatarBg = '#15d736ff';
  $roleLabel = 'Kasir';
} elseif ($role === 'pemilik' || $role === 'owner') {
  $avatarBg = '#15d736ff';
  $roleLabel = 'Owner';
}

// 3. AUTO CHECK TRIGGER (jalankan sekali per request)
if (isset($koneksi) && !defined('NOTIF_CHECK_DONE')) {
  define('NOTIF_CHECK_DONE', true);
  cekPeringatanStokOtomatis($koneksi);
}

// 4. AMBIL DATA NOTIFIKASI
$jumlah_notif = 0;
$list_notif = null;

if ($role && isset($koneksi)) {
  // gunakan role asli; fungsi notifikasi akan menormalisasi ('owner' <-> 'pemilik')
  $role_db = $role;
  $list_notif = ambilNotifikasi($koneksi, $role_db);
  // hitung unread (fungsi menangani normalisasi)
  $jumlah_notif = hitungNotifikasiBelumDibaca($koneksi, $role_db);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Stok Beras</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="style.css">

  <style>
    /* Styling User Avatar */
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

    .btn-hamburger .bars {
      font-size: 20px;
      line-height: 1;
      color: #fff;
    }

    /* Styling Notifikasi */
    .notification-wrapper {
      position: relative;
      cursor: pointer;
      text-decoration: none;
    }

    .notification-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      padding: 3px 6px;
      border-radius: 50%;
      font-size: 10px;
      min-width: 18px;
      text-align: center;
    }

    .notif-item {
      white-space: normal;
      border-bottom: 1px solid #f0f0f0;
      padding: 10px 15px;
      transition: background 0.2s;
    }

    .notif-item:hover {
      background-color: #f1f1f1;
    }

    .notif-unread {
      background-color: #e3f2fd;
      font-weight: 500;
    }

    .dropdown-menu-notif {
      max-height: 350px;
      overflow-y: auto;
      width: 320px;
      z-index: 9999;
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg bg-success py-2 mb-4">
    <div class="container d-flex justify-content-between align-items-center">

      <!-- BRAND -->
      <div class="d-flex align-items-center">
        <span class="navbar-brand mb-0 h4 text-white" id="brandTitle">Agen Beras Idola</span>
      </div>

      <!-- AREA KANAN -->
      <div class="d-flex align-items-center">

        <!-- A. NOTIFIKASI -->
        <?php if ($role): ?>

          <?php
          // Normalisasi output $list_notif agar kompatibel baik mysqli_result maupun array
          $notif_rows = [];
          $notif_count = 0;
          if ($list_notif) {
            if (is_object($list_notif) && method_exists($list_notif, 'fetch_assoc')) {
              // mysqli_result
              while ($r = $list_notif->fetch_assoc()) $notif_rows[] = $r;
              $notif_count = count($notif_rows);
            } elseif (is_array($list_notif)) {
              $notif_rows = $list_notif;
              $notif_count = count($notif_rows);
            }
          }
          ?>

          <div class="dropdown me-3">
            <a class="text-white notification-wrapper" href="#" role="button" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-bell-fill" style="font-size: 1.3rem;"></i>
              <?php if ($jumlah_notif > 0): ?>
                <span class="badge bg-danger notification-badge"><?= $jumlah_notif ?></span>
              <?php endif; ?>
            </a>

            <ul class="dropdown-menu dropdown-menu-end shadow dropdown-menu-notif" aria-labelledby="notifDropdown">
              <li>
                <h6 class="dropdown-header fw-bold text-uppercase">Notifikasi <?= $roleLabel ?></h6>
              </li>
              <li>
                <hr class="dropdown-divider my-0">
              </li>

              <?php if ($notif_count > 0): ?>
                <?php foreach ($notif_rows as $n): ?>
                  <li>
                    <a class="dropdown-item notif-item <?= ($n['status'] ?? '') == 'unread' ? 'notif-unread' : '' ?>"
                      href="/toko_beras/includes/baca_notifikasi.php?id=<?= htmlspecialchars($n['id']) ?>&redirect=<?= urlencode($n['link'] ?? '#') ?>">
                      <div class="d-flex justify-content-between mb-1">
                        <strong class="text-primary small"><?= htmlspecialchars($n['judul'] ?? '') ?></strong>
                        <span class="text-muted" style="font-size:0.65rem;"><?= !empty($n['created_at']) ? date('d/m H:i', strtotime($n['created_at'])) : '' ?></span>
                      </div>
                      <div class="text-secondary small" style="line-height:1.3;">
                        <?= htmlspecialchars($n['pesan'] ?? '') ?>
                      </div>
                    </a>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="text-center py-4 text-muted small">
                  <i class="bi bi-bell-slash d-block mb-2" style="font-size: 1.5rem;"></i>
                  Tidak ada notifikasi baru
                </li>
              <?php endif; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- B. USER INFO -->
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

        <!-- C. HAMBURGER -->
        <button class="btn btn-hamburger ms-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"
          aria-controls="sidebarMenu" title="Menu">
          <span class="bars">☰</span>
        </button>

      </div>
    </div>
  </nav>

  <!-- SCRIPT BOOTSTRAP (Wajib ada) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>